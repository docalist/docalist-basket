<?php
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Basket\Service;

use Docalist\Basket\Service\BasketService;
use Docalist\Basket\Basket;
use WP_Post;
use WP_Query;
use Docalist\Basket\Settings\ButtonSettings;
use Docalist\Basket\Settings\ButtonLocation;

/**
 * Injecte le bouton du panier dans les notices.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ButtonGenerator
{
    /**
     * Priorité des filtres ajoutés dans onLoopStart.
     *
     * Important : pour the_content() et get_the_excerpt(), la priorité doit être supérieure à celle utilisée
     * dans docalist-data/Database (9999) pour générer le contenu des notices docalist.
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
     * Nombre de notices "basketables" trouvées dans la page.
     *
     * @var int
     */
    protected $count;

    /**
     * Les paramètres du bouton panier à générer.
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
     * Constructeur.
     *
     * @param BasketService $basketService Le service panier à utiliser.
     */
    public function __construct(BasketService $basketService)
    {
        // Stocke le service panier
        $this->basketService = $basketService;

        // Récupère le panier de l'utilisateur
        $this->basket = $basketService->getBasket();

        // Si l'utilisateur a un panier, injecte les boutons dans la boucle WordPress
        if (! is_null($this->basket)) {
            add_action('loop_start', [$this, 'onLoopStart']);
        }
    }

    /**
     * Début de la boucle WordPress.
     *
     * @param WP_Query $query
     */
    public function onLoopStart(WP_Query $query): void
    {
        // On ne fait rien si on n'est pas dans la boucle WordPress principale
        if (! $query->is_main_query()) {
            return;
        }

        // Détermine les paramètres du bouton en fonction du contexte de la page
        $this->buttonSettings = $this->getButtonSettings();

        // On ne fait rien si on n'est pas sur une page is_single() ou is_archive()
        if (is_null($this->buttonSettings)) {
            return;
        }

        // Initialise le code html des boutons
        $this->initButtons();

        // Installe les filtres requis en fonction de l'emplacement du bouton
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

            default: // ButtonLocation::NO_BUTTON ou emplacement invalide
                return;
        }

        // Ajoute des classes CSS si on génère un bouton
        add_filter('post_class', [$this, 'filterPostClass'], self::PRIORITY, 3);

        // Initialise le compteur de notices basketables
        $this->count = 0;

        // Quand WordPress aura fini sa boucle, on supprimera les filtres ajoutés
        add_action('loop_end', [$this, 'onLoopEnd']);

        // On n'a plus besoin du filtre "début de boucle"
        remove_action('loop_start', [$this, 'onLoopStart']);
    }

    /**
     * Fin de la boucle WordPress.
     */
    public function onLoopEnd(): void
    {
        // Supprime les filtres installés pour générer le bouton
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


            default: // ButtonLocation::NO_BUTTON ou emplacement invalide
                return;
        }

        // Supprime le filtre ajouté pour générer les classes CSS
        remove_filter('post_class', [$this, 'filterPostClass'], self::PRIORITY, 3);

        // Supprime l'action de fin de boucle
        remove_action('loop_end', [$this, 'onLoopEnd']);

        // Insère la CSS et le JS du panier si on a au moins une notice basketable dans la page
        $this->count && $this->enqueueAssets();
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
        // On ne fait rien si le post indiqué ne peut pas être ajouté au panier
        if (! $this->isBasketable($postID)) {
            return $classes;
        }

        // Met à jour le nombre de notices basketables rencontrées
        $this->count++;

        //  Ajoute les classes CSS
        $settings = $this->basketService->getSettings();
        $setting = $this->basket->has($postID) ? $settings->classactive : $settings->classinactive;
        array_unshift($classes, $setting->getPhpValue());

        /* Remarque : on ajoute la classe en premier pour qu'elle soit prioritaire sur les autres */

        // Ok
        return $classes;
    }

    /**
     * Ajoute le bouton panier avant le contenu passé en paramètre.
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
     * Ajoute le bouton panier après le contenu passé en paramètre.
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
     * @return ButtonSettings|null
     */
    private function getButtonSettings(): ?ButtonSettings
    {
        $settings = $this->basketService->getSettings();

        if (is_single()) {
            return $settings->single;
        }

        if (is_archive() || is_search()) {
            return $settings->list;
        }

        return null;
    }

    /**
     * Initialise le code html des boutons add/remove en fonction du settings passés en paramètre.
     *
     * Le code html est compressé pour éviter que wordpress ne génère des retours chariots.
     */
    private function initButtons(): void
    {
        foreach (['add', 'remove'] as $button) {
            // Récupère le code html du bouton
            $html = $this->buttonSettings->$button->getPhpValue();

            // Minifie le code pour contourner wpautop qui nous convertit les retours à la lign en <br>
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
}
