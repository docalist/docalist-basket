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

namespace Docalist\Basket\Tests\Storage;

use WP_UnitTestCase;
use Docalist\Basket\Storage;
use Docalist\Basket\Storage\InMemoryBasketStorage;

/**
 * Teste la classe InMemoryBasketStorage.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class InMemoryBasketStorageTest extends WP_UnitTestCase
{
    /**
     * Retourne le Storage à tester.
     *
     * @return Storage
     */
    protected function getStorage(): Storage
    {
        return new InMemoryBasketStorage();
    }

    /**
     * Teste les méthodes loadBasketList() et saveBasketList().
     */
    public function testBasketList(): void
    {
        $storage = $this->getStorage();

        $this->assertSame([], $storage->loadBasketList());

        $storage->saveBasketList([1 => 'default']);
        $this->assertSame([1 => 'default'], $storage->loadBasketList());

        $storage->saveBasketList([1 => 'default', 2 => 'panier 2']);
        $this->assertSame([1 => 'default', 2 => 'panier 2'], $storage->loadBasketList());

        $storage->saveBasketList([]);
        $this->assertSame([], $storage->loadBasketList());
    }

    /**
     * Teste les méthodes loadBasketData() et saveBasketData().
     */
    public function testBasketData(): void
    {
        $storage = $this->getStorage();

        $this->assertSame([], $storage->loadBasketData(1));

        $storage->saveBasketData(1, [10]);
        $this->assertSame([10], $storage->loadBasketData(1));

        $storage->saveBasketData(1, [10, 20]);
        $this->assertSame([10, 20], $storage->loadBasketData(1));

        $storage->saveBasketData(1, []);
        $this->assertSame([], $storage->loadBasketData(1));
    }
}
