<?php declare(strict_types=1);
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Basket\Tests\Storage;

use Docalist\Basket\Tests\Storage\InMemoryBasketStorageTest;
use Docalist\Basket\Storage;
use Docalist\Basket\Storage\UserMetaBasketStorage;

/**
 * Teste la classe UserMetaBasketStorage.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class UserMetaBasketStorageTest extends InMemoryBasketStorageTest
{
    protected function getStorage(): Storage
    {
        return new UserMetaBasketStorage(456); // fake user id
    }
}
