<?php
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Basket\Service;

use Docalist\Basket\Basket;
use Docalist\Basket\Baskets;
use Docalist\Basket\Service\AjaxController;
use Docalist\Basket\Service\ButtonGenerator;
use Docalist\Basket\Settings\BasketSettings;
use Docalist\Basket\Storage\UserMetaBasketStorage;
use Docalist\Search\QueryDSL;
use Docalist\Search\SearchEngine;
use Docalist\Search\SearchRequest;
use Docalist\Search\SearchUrl;
use WP_Post;
use WP_Query;
use WP_User;

/**
 * Service basket.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class BasketService
{
    /**
     * Les paramètres du panier.
     *
     * @var BasketSettings
     */
    protected $settings;

    /**
     * Le contrôleur utilisé pour les requêtes Ajax.
     *
     * @var AjaxController
     */
    protected $ajaxController;

    /**
     * Le générateur chargé d'injecter le bouton panier dans les notices.
     *
     * @var ButtonGenerator
     */
    protected $buttonGenerator;

    /**
     * L'objet Baskets de l'utilisateur en cours.
     *
     * Initialement, la propriété est à false pour indiquer "non initialisé". Elle est initialisée par getBaskets()
     * lors du premier appel et contient ensuite soit un objet Baskets, soit null si l'utilisateur en cours n'a pas
     * les droits suffisants pour avoir un panier.
     *
     * @var bool|Baskets|null
     */
    protected $baskets = false;

    /**
     * Initialise le service basket.
     *
     * @param BasketSettings $settings Paramètres du service basket.
     */
    public function __construct(BasketSettings $settings)
    {
        // Stocke les paramètres du service
        $this->settings = $settings;

        // Crée le contrôleur ajax
        $this->ajaxController = new AjaxController($this);

        // Crée le générateur de bouton
        $this->buttonGenerator = new ButtonGenerator($this);

        // Crée le pseudo type "in:basket"
        $this->createBasketPseudoType();

        // Gère la page "panier" indiquée dans les paramètres
        $this->handleBasketPage();

        // Affiche la mention "Page du panier docalist" dans la liste des pages du back office WordPress
        $this->showStateForBasketPage();
    }

    /**
     * Retourne les paramètres du service panier.
     *
     * @return BasketSettings
     */
    public function getSettings(): BasketSettings
    {
        return $this->settings;
    }

    /**
     * Retourne le contrôleur chargé de traiter les requêtes AJAX sur le panier.
     *
     * @return AjaxController
     */
    public function getAjaxController(): AjaxController
    {
        return $this->ajaxController;
    }

    /**
     * Retourne le générateur chargé d'injecter le bouton panier dans les notices.
     *
     * @return ButtonGenerator
     */
    public function getButtonGenerator(): ButtonGenerator
    {
        return $this->buttonGenerator;
    }

    /**
     * Réinitialise le service panier.
     *
     * Cette méthode est utile lorsque l'utilisateur WordPress en cours change. Elle permet de réinitialiser le
     * service panier pour prendre en compte les droits du nouvel utilisateur.
     */
    public function reset(): void
    {
        $this->baskets = false;
    }

    /**
     * Indique si le panier est actif ou non pour l'utilisateur en cours.
     *
     * @return bool Retourne true si l'utilisateur a le droit d'avoir un panier, false sinon.
     */
    public function isActive(): bool
    {
        return !is_null($this->getBaskets());
    }

    /**
     * Retourne le nombre maximum de paniers autorisés pour l'utilisateur en cours.
     *
     * @return int Le nombre de paniers ou 0 si l'utilisateur n'a pas le droit d'avoir de paniers.
     */
    public function getMaxBaskets(): int
    {
        $baskets = $this->getBaskets();

        return is_null($baskets) ? 0 : $baskets->getMaxBaskets();
    }

    /**
     * Retourne le nombre maximum de notices par panier pour l'utilisateur en cours.
     *
     * @return int La capacité des paniers ou 0 si l'utilisateur n'a pas le droit d'avoir de paniers.
     */
    public function getBasketCapacity(): int
    {
        $baskets = $this->getBaskets();

        return is_null($baskets) ? 0 : $baskets->getBasketCapacity();
    }

    /**
     * Retourne les paniers de l'utilisateur en cours.
     *
     * @return Baskets|null Retourne un objet Baskets contenant les paniers de l'utilisateur.
     * Retourne null si l'utilisateur en cours ne peut pas avoir de panier.
     */
    public function getBaskets(): ?Baskets
    {
        // Si la propriété a déjà été initialisée, terminé
        if (false !== $this->baskets) {
            return $this->baskets;
        }

        // Par défaut, personne n'a de panier
        $this->baskets = null;

        // Seuls les utilisateurs connectés peuvent avoir des paniers
        if (!is_user_logged_in()) {
            return null;
        }

        // Récupère les rôles de l'utilisateur en cours
        $user = wp_get_current_user(); /** @var WP_User $user */
        $roles = $user->roles;

        // Teste si l'un des rôles lui permet d'avoir un panier
        $maxBaskets = $basketCapacity = 0;
        foreach ($roles as $role) {
            // Si ce rôle ne permet pas d'avoir un panier terminé
            if (! isset($this->settings->role[$role])) {
                continue;
            }

            // Récupère les limites permises pour ce rôle
            $limits = $this->settings->role[$role];
            $roleBaskets = $limits->maxBaskets->getPhpValue();
            $roleCapacity = $limits->basketCapacity->getPhpValue();

            // On fusionne les limites en prenant le max : si on a role1=5x10 et role2=3x100, au final
            // l'utilisateur aura le droit à 5 paniers de 100
            ($roleBaskets > $maxBaskets) && $maxBaskets = $roleBaskets;
            ($roleCapacity > $basketCapacity) && $basketCapacity = $roleCapacity;
        }

        // Si l'utilisateur a le droit d'avoir des paniers, on les crée
        if ($maxBaskets > 0 && $basketCapacity > 0) {
            $storage = new UserMetaBasketStorage($user->ID);
            $this->baskets = new Baskets($storage, $maxBaskets, $basketCapacity);
        }

        return $this->baskets;
    }

    /**
     * Retourne le panier de l'utilisateur en cours.
     *
     * @return Basket|null Retourne le panier actuellement sélectionné pour l'utilisateur en cours.
     * Retourne null si l'utilisateur en cours ne peut pas avoir de panier.
     */
    public function getBasket(): ?Basket
    {
        $baskets = $this->getBaskets();

        return is_null($baskets) ? null : $baskets->getBasket();
    }

    /**
     * Indique si un post d'un type donné peut être ajouté au panier.
     *
     * @param string $type Le type de post à tester.
     *
     * @return bool Retourne true si le panier supporte le post type indiqué, false sinon.
     */
    public function isSupportedType(string $type): bool
    {
        return isset($this->settings->types[$type]);
    }

    /**
     * Retourne l'url de la page permettant d'afficher le contenu du panier.
     *
     * Si l'utilisateur a indiqué une page WordPress spécifique dans les paramètres du panier, c'est l'url de cette
     * page qui est retournée.
     *
     * Sinon, c'est l'url de la page "résultats de recherche" qui est utilisée, avec un paramètre "?in=basket".
     *
     * @return string
     */
    public function getUrl(): string
    {
        // Retourne la page indiquée dans les paramètres du panier
        $page = $this->settings->basketpage->getPhpValue();
        if ($page) {
            return get_the_permalink($page);
        }

        // Retourne l'url d'une recherche "in:basket"
        $docalistSearch = docalist('docalist-search-engine'); /** @var SearchEngine $docalistSearch */
        return $docalistSearch->getSearchPageUrl() . '?in=basket';
    }

    /**
     * Teste si on est sur une page qui affiche le contenu du panier.
     *
     * La méthode teste si la requête docalist-search en cours porte sur le pseudo type "basket".
     *
     * @return bool
     */
    public function isBasketRequest(): bool
    {
        $docalistSearch = docalist('docalist-search-engine'); /** @var SearchEngine $docalistSearch */
        $request = $docalistSearch->getSearchRequest();

        return !is_null($request) && in_array('basket', $request->getTypes(), true);
    }

    /**
     * Permet de lancer des recherches docalist-search de la forme "in:basket".
     *
     * Quand docalist-search rencontre une clause "in" avec un type qu'il ne connaît pas, il déclenche le filtre
     * "docalist_search_type_query". On intercepte ce filtre pour générer une requête elasticsearch qui porte sur
     * tous les documents présents dans le panier. Si l'utilisateur en cours n'a pas de panier ou si son panier
     * est vide, on génère une requête qui ne retourne aucun résultat (équivalent matchNone).
     */
    private function createBasketPseudoType(): void
    {
        add_filter(
            'docalist_search_type_query',
            function (array $filter, string $type): array {
                if ($type !== 'basket') {
                    return $filter;
                }

                // Récupère les ID des notices qui figurent dans le panier
                $basket = $this->getBasket();
                $ids = is_null($basket) ? [] : $basket->getContents();

                // Génère le filtre
                $dsl = docalist('elasticsearch-query-dsl'); /** @var QueryDSL $dsl */
                return $dsl->ids($ids);
            },
            10,
            2
        );
    }

    /**
     * Génère une requête docalist-search "in:basket" quand on est sur la page panier indiquée dans les paramètres.
     */
    private function handleBasketPage(): void
    {
        // On ne fait rien si on n'a pas de page spécifique pour le panier
        $page = $this->settings->basketpage->getPhpValue();
        if (empty($page)) {
            return;
        }

        // Génère une requête quand on est sur la page panier
        add_filter(
            'docalist_search_create_request',
            function (SearchRequest $request = null, WP_Query $query) use ($page): ?SearchRequest {
                // On ne fait rien si on n'est pas sur la page du panier
                if ($query->get_queried_object_id() !== $page) {
                    return $request;
                }

                // Génère une requête "in:basket"
                return (new SearchUrl($this->getUrl(), ['basket']))->getSearchRequest();
            },
            10,
            2
        );
    }

    /**
     * Affiche la mention "Page du panier docalist" dans la liste des pages du back office WordPress.
     *
     * Ne fait rien si aucune page n'a été indiquée dans les paramètres du panier.
     */
    private function showStateForBasketPage(): void
    {
        // Teste si une page spécifique a été choisie pour la panier
        $page = $this->settings->basketpage->getPhpValue();
        if (empty($page)) {
            return;
        }

        add_filter('display_post_states', function (array $states, WP_Post $post) use ($page): array {
            if ($post->ID === $page) {
                $states['docalist-basket'] = __('Page du panier Docalist', 'docalist-basket');
            }

            return $states;
        }, 10, 2);
    }
}
