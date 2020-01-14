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

namespace Docalist\Basket\Storage;

use Docalist\Basket\Storage;

/**
 * Stockage des paniers des utilisateurs connectés dans la table user meta.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class UserMetaBasketStorage implements Storage
{
    /**
     * Nom du méta qui contient la liste des paniers de l'utilisateur.
     *
     * @var string
     */
    const BASKETS_LIST = '_docalist-baskets';

    /**
     * Préfixe utilisé pour les clés des métas contenant des paniers.
     *
     * @var string
     */
    const BASKET_PREFIX = '_docalist-basket-';

    /**
     * ID de l'utilisateur en cours.
     *
     * @var int
     */
    private $userID;

    public function __construct(int $userID)
    {
        $this->userID = $userID;
    }

    public function loadBasketList(): array
    {
        $list = get_user_meta($this->userID, self::BASKETS_LIST, true);

        return empty($list) ? [] : $list;
    }

    public function saveBasketList(array $list): void
    {
        update_user_meta($this->userID, self::BASKETS_LIST, $list);
    }

    public function loadBasketData(int $basket): array
    {
        $data = get_user_meta($this->userID, self::BASKET_PREFIX . $basket, true);

        // Les données sont sérialisées sous la forme d'une chaine : '1 2 3'
        return empty($data) ? [] : array_map('intval', explode(' ', $data));
    }

    public function saveBasketData(int $basket, array $data): void
    {
        $key = self::BASKET_PREFIX . $basket;

        if (empty($data)) {
            delete_user_meta($this->userID, $key);

            return;
        }

        // Sérialise les données sous la forme d'une chaine : '1 2 3'
        update_user_meta($this->userID, $key, implode(' ', $data));
    }
}
