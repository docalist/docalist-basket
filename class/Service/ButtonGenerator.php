<?php declare(strict_types=1);
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Basket\Service;

use Docalist\Basket\Service\BasketService;
use Docalist\Basket\Basket;
use WP_Query;
use Docalist\Basket\Settings\ButtonSettings;
use Docalist\Basket\Settings\ButtonLocation;

/**
 * Injecte les boutons du panier dans les notices.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ButtonGenerator
{
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
    public function onLoopStart(WP_Query $query)
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

        // Installe les filtres requis en fonction de l'emplacement du bouton
        switch ($this->buttonSettings->location->getPhpValue()) {
            case ButtonLocation::BEFORE_TITLE:
                add_filter('the_title', [$this, 'prependButton'], 99999);
                break;

            case ButtonLocation::AFTER_TITLE:
                add_filter('the_title', [$this, 'appendButton'], 99999);
                break;

            case ButtonLocation::BEFORE_CONTENT:
                add_filter('the_excerpt', [$this, 'prependButton'], 99999);
                add_filter('the_content', [$this, 'prependButton'], 99999);
                break;

            case ButtonLocation::AFTER_CONTENT:
                add_filter('the_excerpt', [$this, 'appendButton'], 99999);
                add_filter('the_content', [$this, 'appendButton'], 99999);
                break;

            case ButtonLocation::NO_BUTTON:
            default:
                return;

        }

        // Ajoute des classes CSS si on génère un bouton
        add_filter('post_class', [$this, 'filterPostClass'], 10, 3);

        // Initialise le compteur de notices basketables
        $this->count = 0;

        // Quand WordPress aura fini sa boucle, on supprimera les filtres ajoutés
        add_action('loop_end', [$this, 'onLoopEnd']);

        // On n'a plus besoin du filtre "début de boucle"
        remove_action('loop_start', [$this, 'onLoopStart']);
    }

    /**
     * Fin de la boucle WordPress.
     *
     * @param WP_Query $query
     */
    public function onLoopEnd(WP_Query $query)
    {
        // Supprime les filtres installés pour générer le bouton
        switch ($this->buttonSettings->location->getPhpValue()) {
            case ButtonLocation::BEFORE_TITLE:
                remove_filter('the_title', [$this, 'prependButton'], 99999);
                break;

            case ButtonLocation::AFTER_TITLE:
                remove_filter('the_title', [$this, 'appendButton'], 99999);
                break;

            case ButtonLocation::BEFORE_CONTENT:
                remove_filter('the_excerpt', [$this, 'prependButton'], 99999);
                remove_filter('the_content', [$this, 'prependButton'], 99999);
                break;

            case ButtonLocation::AFTER_CONTENT:
                remove_filter('the_excerpt', [$this, 'appendButton'], 99999);
                remove_filter('the_content', [$this, 'appendButton'], 99999);
                break;

            case ButtonLocation::NO_BUTTON:
            default:
                return;

        }

        // Supprime le filtre ajouté pour générer les classes CSS
        remove_filter('post_class', [$this, 'filterPostClass'], 10, 3);

        // Initialise le compteur de notices basketables
        $this->count = 0;

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
        $classes[] = 'basket';
        $classes[] = $this->basket->has($postID) ? 'basket-active' : 'basket-inactive';

        // Ok
        return $classes;
    }

    /**
     * Ajoute le bouton panier avant le contenu passé en paramètre.
     *
     * @param string $content
     *
     * @return string
     */
    public function prependButton(string $content): string
    {
        return $this->getButton() . $content;
    }

    /**
     * Ajoute le bouton panier après le contenu passé en paramètre.
     *
     * @param string $content
     *
     * @return string
     */
    public function appendButton(string $content): string
    {
        return $content . $this->getButton();
    }

    /**
     * Ajoute un bouton sélectionner/déselectionner dans le contenu passé en paramètre si le post en cours est
     * supporté par le panier.
     *
     * @return string
     */
    private function getButton(): string
    {
        // Récupère l'ID du post en cours
        $postID = (int) get_the_ID();

        // On ne génère aucun bouton si le post ne peut pas être ajouté au panier
        if (! $this->isBasketable($postID)) {
            return '';
        }

        // Génère un bouton "enlever" si la notice est déjà dans le panier
        if ($this->basket->has($postID)) {
            return $this->buttonSettings->remove->getPhpValue();
        }

        // Génère un bouton "ajouter" sinon
        return $this->buttonSettings->add->getPhpValue();
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
     * Insère la CSS et le JS du panier dans la page en cours.
     */
    private function enqueueAssets()
    {
        wp_enqueue_style('docalist-basket');
        wp_enqueue_script('docalist-basket');
        wp_localize_script(
            'docalist-basket',  // Handle du JS
            'docalistBasketSettings', // Nom de la variable javascript générée
            [
                'active' => $this->buttonSettings->add(),
                'inactive' => $this->buttonSettings->remove(),
                'url' => 'xxxxxxxxxxxx', //$this->baseUrl(),
            ]
        );
    }
}