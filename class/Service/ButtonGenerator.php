<?php
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2020 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Basket\Service;

use Docalist\Basket\Basket;
use Docalist\Basket\Service\BasketService;
use Docalist\Basket\Settings\ButtonSettings;
use Docalist\Basket\Settings\ButtonLocation;
use WP_Query;
use WP_Post;

/**
 * Injecte le bouton du panier dans les notices.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ButtonGenerator
{
    /**
     * Priorité des filtres
     *
     * Important : pour the_content() et get_the_excerpt(), la priorité doit être supérieure à celle
     * utilisée dans docalist-data/Database (9999) pour générer le contenu des notices docalist.
     */
    private const PRIORITY = 10000;

    /**
     * Le service panier.
     *
     * @var BasketService
     */
    protected $basketService;

    /**
     * Le panier de l'utilisateur en cours.
     *
     * @var Basket|null
     */
    protected $basket;

    /**
     * Nombre de boutons générés.
     *
     * @var int
     */
    protected $count;

    /**
     * Les paramètres des boutons à générer.
     *
     * @var ButtonSettings|null
     */
    protected $buttonSettings;

    /**
     * Le code html des boutons ajouter au panier('add') et enlever du panier ('remove')
     *
     * @var string[]
     */
    protected $buttons;

    /**
     * Une pile qui nous permet de savoir si le post en cours est issu de la boucle WordPress principale.
     *
     * @var bool[]
     */
    protected $stack;

    /**
     * Constructeur.
     *
     * @param BasketService $basketService Le service panier à utiliser.
     */
    public function __construct(BasketService $basketService)
    {
        // Stocke le service panier
        $this->basketService = $basketService;

        // Si l'utilisateur n'a pas le droit à un panier, on ne fait rien
        $this->basket = $basketService->getBasket();
        if (is_null($this->basket)) {
            return;
        }

        /*
         * L'utilisateur a le droit à un panier, on va injecter des boutons et des classes CSS dans
         * les posts affichés.
         *
         * Mais il faut le faire uniquement pour les posts issus de la boucle WordPress principale
         * (celle pour laquelle is_main_query() retourne true), pas pour les posts qui figurent dans
         * les sidebars, les carrousels, ou les listes de posts générés par des shortcodes comme
         * 'docalist_search_results'.
         *
         * Pour gérer ça, on utilise une pile qui contient l'état (is_main_query) des requêtes
         * (éventuellement imbriquées) générées par les templates. Cette pile est mise à jour quand
         * WordPress déclenche les actions loop_start (push) et loop_end (pop).
         *
         * Le dernier élément de la pile nous permet ainsi de savoir si le post en cours (global $post)
         * est issu de la boucle principale ou non et les différentes méthodes qui génèrent les boutons
         * ne font rien si on n'est pas dans la boucle principale.
         */
        $this->stack = [];

        /*
         * Lorsqu'une boucle démarre, empile l'état de la requête en cours et initialise le process
         * si c'est la boucle principale qui démarre.
         */
        add_action('loop_start', function (WP_Query $query): void {
            $this->stack[] = $query->is_main_query();

            if (! $this->isMainQuery()) {
                return;
            }

            $this->buttonSettings = $this->getButtonSettings($query);
            if (is_null($this->buttonSettings)) {
                return;
            }

            if ($this->buttonSettings->location->getPhpValue() === ButtonLocation::NO_BUTTON) {
                return;
            }

            $this->initButtons();
            $this->count = 0;
            $this->addFilters();
        });

        /*
         * Lorsqu'une boucle se termine, dépile l'état de la requête en cours et termine le process
         * si c'est la boucle principale qui vient de se terminer.
         */
        add_action('loop_end', function (WP_Query $query): void {
            if (! $this->isMainQuery()) {
                return;
            }

            array_pop($this->stack);

            !empty($this->buttonSettings) && $this->removeFilters();
            $this->count && $this->enqueueAssets();
        });
    }

    /**
     * Indique si on est dans la boucle WordPress principale.
     *
     * @return bool
     */
    private function isMainQuery(): bool
    {
        return end($this->stack); // retourne false si la pile est vide
    }

    /**
     * Installe les filtres utilisés pour injecter les boutons du panier dans les posts.
     */
    private function addFilters(): void
    {
        add_filter('post_class', [$this, 'filterPostClass'], self::PRIORITY, 3);

        switch ($this->buttonSettings->location->getPhpValue()) {
            case ButtonLocation::BEFORE_TITLE:
                add_filter('the_title', [$this, 'prependButton'], self::PRIORITY, 2);       // title + post_id
                break;

            case ButtonLocation::AFTER_TITLE:
                add_filter('the_title', [$this, 'appendButton'], self::PRIORITY, 2);        // title + post_id
                break;

            case ButtonLocation::BEFORE_CONTENT:
                add_filter('get_the_excerpt', [$this, 'prependButton'], self::PRIORITY, 2); // excerpt + post_object
                add_filter('the_content', [$this, 'prependButton'], self::PRIORITY, 1);     // content uniquement
                break;

            case ButtonLocation::AFTER_CONTENT:
                add_filter('get_the_excerpt', [$this, 'appendButton'], self::PRIORITY, 2);  // excerpt + post_object
                add_filter('the_content', [$this, 'appendButton'], self::PRIORITY, 1);      // content uniquement
                break;
        }
    }

    /**
     * Désinstalle les filtres utilisés pour injecter les boutons du panier dans les posts.
     */
    private function removeFilters(): void
    {
        remove_filter('post_class', [$this, 'filterPostClass'], self::PRIORITY);

        switch ($this->buttonSettings->location->getPhpValue()) {
            case ButtonLocation::BEFORE_TITLE:
                remove_filter('the_title', [$this, 'prependButton'], self::PRIORITY);
                break;

            case ButtonLocation::AFTER_TITLE:
                remove_filter('the_title', [$this, 'appendButton'], self::PRIORITY);
                break;

            case ButtonLocation::BEFORE_CONTENT:
                remove_filter('get_the_excerpt', [$this, 'prependButton'], self::PRIORITY);
                remove_filter('the_content', [$this, 'prependButton'], self::PRIORITY);
                break;

            case ButtonLocation::AFTER_CONTENT:
                remove_filter('get_the_excerpt', [$this, 'appendButton'], self::PRIORITY);
                remove_filter('the_content', [$this, 'appendButton'], self::PRIORITY);
                break;
        }
    }

    /**
     * Filtre "post_class" utilisé pour ajouter les classes CSS du panier au post en cours.
     *
     * @param string[]  $classes    Les classes CSS déterminées par get_post_class().
     * @param string[]  $class      Classes additionnelles fournies lors de l'appel à get_post_class().
     * @param int       $postID     L'ID du post en cours.
     *
     * @return array Le tableau $classes éventuellement modifié.
     */
    public function filterPostClass(array $classes, array $class, int $postID): array
    {
        // On ne fait rien si le post ne provient pas de la boucle WordPress principale
        if (! $this->isMainQuery()) {
            return $classes;
        }

        // On ne fait rien si le post indiqué ne peut pas être ajouté au panier
        if (! $this->isBasketable($postID)) {
            return $classes;
        }

        //  Ajoute les classes CSS (en premier pour qu'elles soient prioritaires sur les autres)
        $settings = $this->basketService->getSettings();
        $setting = $this->basket->has($postID) ? $settings->classactive : $settings->classinactive;
        array_unshift($classes, $setting->getPhpValue());

        array_unshift($classes, 'basketable');

        // Ok
        return $classes;
    }

    /**
     * Ajoute le bouton du panier avant le contenu passé en paramètre.
     *
     * @param string $content
     * @param int|WP_Post|null $post
     *
     * @return string
     */
    public function prependButton(string $content, $post = null): string
    {
        return $this->getButton($post) . $content;
    }

    /**
     * Ajoute le bouton du panier après le contenu passé en paramètre.
     *
     * @param string $content
     * @param int|WP_Post|null $post
     *
     * @return string
     */
    public function appendButton(string $content, $post = null): string
    {
        return $content . $this->getButton($post);
    }

    /**
     * Ajoute un bouton sélectionner/déselectionner dans le contenu passé en paramètre si le post en cours est
     * supporté par le panier.
     *
     * @param int|WP_Post|null $post
     *
     * @return string
     */
    private function getButton($post = null): string
    {
        // On ne fait rien si le post ne provient pas de la boucle WordPress principale
        if (! $this->isMainQuery()) {
            return '';
        }

        // Détermine le post à traiter (exit si aucun)
        // Il est soit passé en paramètre (ID pour the_title, post object pour get_the_excerpt), soit récupéré dans
        // la global $post (pour the_content qui ne transmet ni ID ni post)
        $post = get_post($post);
        if (empty($post)) {
            return '';
        }

        // Récupère son ID
        $postID = $post->ID;

        // On ne fait rien si l'ID obtenu ne correspond pas à l'ID du post en cours dans la boucle WordPress
        // (i.e. si l'un des filtres installés a été appellé pour un autre post que le post en cours),
        if ($postID !== get_the_ID()) {
            return '';
        }

        // On ne génère aucun bouton si le panier n'accepte pas ce type de post
        if (! $this->isBasketable($postID)) {
            return '';
        }

        // Met à jour le nombre de boutons générés
        $this->count++;

        // Retourne le code html du bouton ("add" si la notice est déjà dans le panier, "remove" sinon)
        return $this->basket->has($postID) ? $this->buttons['remove'] : $this->buttons['add'];
    }

    /**
     * Indique si le post indiqué peut être ajouté au panier.
     *
     * @param int $postID L'ID du post à tester.
     *
     * @return bool
     */
    private function isBasketable(int $postID): bool
    {
        // Récupère le type du post
        $type = (string) get_post_type($postID); // peut retourner false, on caste en string

        // Retourne true si c'est un type supporté
        return $this->basketService->isSupportedType($type);
    }

    /**
     * Détermine les paramètres du bouton panier en fonction du contexte de la page en cours.
     *
     * @param WP_Query $query
     *
     * @return ButtonSettings|null
     */
    private function getButtonSettings(WP_Query $query): ?ButtonSettings
    {
        $settings = $this->basketService->getSettings();

        if ($query->is_single() || $query->is_page()) {
            return $settings->single;
        }

        if ($query->is_archive() || $query->is_search()) {
            return $settings->list;
        }

        return null;
    }

    /**
     * Initialise le code html des boutons add/remove en fonction du settings passés en paramètre.
     *
     * Le code html est compressé pour contourner wpautop et éviter que WordPress nous
     * génère des retours chariots.
     */
    private function initButtons(): void
    {
        foreach (['add', 'remove'] as $button) {
            // Récupère le code html du bouton
            $html = $this->buttonSettings->$button->getPhpValue();

            // Minifie le code pour contourner wpautop qui nous convertit les retours à la ligne en <br>
            // Source : https://stackoverflow.com/a/6225706
            $html = preg_replace(['~\>[^\S ]+~s', '~[^\S ]+\<~s', '~\s+~s'], ['>', '<', ' '], $html);

            // Stocke le code html compressé
            $this->buttons[$button] = $html;
        }
    }

    /**
     * Insère la CSS et le JS du panier dans la page en cours.
     */
    private function enqueueAssets(): void
    {
        $settings = $this->basketService->getSettings();

        wp_enqueue_script('docalist-basket');
        wp_localize_script(
            'docalist-basket',  // Handle du JS
            'docalistBasketSettings', // Nom de la variable javascript générée
            [
                // URL du controleur ajax
                'url' => $this->basketService->getAjaxController()->getBaseUrl(),

                // Code HTML du bouton "Ajouter au panier"
                'addButton' => $this->buttons['add'],

                // Code HTML du bouton "Enlever du panier"
                'removeButton' => $this->buttons['remove'],

                // Classe CSS d'une notice sélectionnée
                'basket-active' => $settings->classactive->getPhpValue(),

                // Classe CSS d'une notice non sélectionnée
                'basket-inactive' => $settings->classinactive->getPhpValue(),
            ]
        );
    }

//     private function log(WP_Query $query, string $title): void
//     {
//         $message = sprintf(
//             "%s, query=%d, stack=%s, main=%s, query=\n%s",
//             $title,
//             spl_object_id($query),
//             json_encode($this->stack),
//             var_export($this->isMainQuery(), true),
//             $query->request
//         );

//         $message = wp_slash($message);
//         $message = str_replace("\n", '\n', $message);

//         printf('<script>console.log("%s");</script>', $message);
//     }
}
