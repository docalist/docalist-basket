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

namespace Docalist\Basket\Storage;

use Docalist\Basket\Storage;

/**
 * Stockage des paniers en mémoire (pour les tests).
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class InMemoryBasketStorage implements Storage
{
    private $baskets = [];

    public function loadBasketList(): array
    {
        return $this->baskets['list'] ?? [];
    }

    public function saveBasketList(array $list): void
    {
        $this->baskets['list'] = $list;
    }

    public function loadBasketData(int $basket): array
    {
        return $this->baskets[$basket] ?? [];
    }

    public function saveBasketData(int $basket, array $data): void
    {
        $this->baskets[$basket] = $data;
    }
}
