<?php declare(strict_types=1);
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Basket\Api;

use Docalist\Basket\Settings\BasketSettings;
use Docalist\Basket\Baskets;
use Docalist\Basket\Basket;
use Docalist\Basket\Storage\UserMetaBasketStorage;
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
        $this->settings = $settings;
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

        // Seuls les utilisateurs connectés ont un panier
        if (is_user_logged_in()) {
            // Récupère le rôle principal de l'utilisateur (le premier)
            $user = wp_get_current_user(); /** @var WP_User $user */
            $role = reset($user->roles);

            // Teste si ce rôle a le droit d'avoir un panier
            if (isset($this->settings->role[$role])) {
                $limits = $this->settings->role[$role];
                $maxBaskets = $limits->maxBaskets->getPhpValue();
                $basketCapacity = $limits->basketCapacity->getPhpValue();
                if ($maxBaskets > 0 && $basketCapacity > 0) {
                    $storage = new UserMetaBasketStorage($user->ID);
                    $this->baskets = new Baskets($storage, $maxBaskets, $basketCapacity);
                }
            }
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
}
