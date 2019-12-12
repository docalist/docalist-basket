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

namespace Docalist\Basket;

/**
 * Interface d'un dépôt permettant de stocker les paniers de notices d'un utilisateur.
 *
 * Un dépôt de paniers est spécifique à un utilisateur donné (qui est soit passé en paramètre, soit déterminé
 * automatiquement) et permet :
 *
 * - de charger et d'enregistrer la liste des paniers de l'utilisateur,
 * - de lire et de modifier la liste des notices qui figurent dans un panier donné (identifié par son numéro).
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Storage
{
    /**
     * Charge la liste des paniers de l'utilisateur.
     *
     * @return string[] Retourne un tableau de la forme numéro du panier => nom du panier ou un tableau vide si
     * l'utilisateur n'a aucun panier.
     */
    public function loadBasketList(): array;

    /**
     * Enregistre la liste des paniers de l'utilisateur.
     *
     * @param string[] $list Un tableau (éventuellement vide) de la forme numéro du panier => nom du panier.
     */
    public function saveBasketList(array $list): void;

    /**
     * Retourne les données d'un panier.
     *
     * Aucune erreur n'est générée si le panier indiqué n'existe pas (un tableau vide est retourné).
     *
     * @param int $basket Numéro du panier à charger.
     *
     * @return int[] Les ID des notices qui figurent dans le panier.
     */
    public function loadBasketData(int $basket): array;

    /**
     * Enregistre les données d'un panier.
     *
     * Si le panier indiqué n'existe pas, aucune erreur n'est générée mais le panier n'est pas ajouté
     * autormatiquement à la liste des paniers.
     *
     * @param int   $basket Numéro du panier à modifier.
     * @param int[] $data   Les ID des notices sélectionnées.
     */
    public function saveBasketData(int $basket, array $data): void;
}
