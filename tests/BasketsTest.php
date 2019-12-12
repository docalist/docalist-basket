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

namespace Docalist\Basket\Tests;

use WP_UnitTestCase;
use Docalist\Basket\Baskets;
use Docalist\Basket\Storage;
use Docalist\Basket\Storage\InMemoryBasketStorage;
use Docalist\Basket\Storage\UserMetaBasketStorage;
use Docalist\Basket\Basket;
use Countable;
use Traversable;
use InvalidArgumentException;

/**
 * Teste la classe Baskets.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class BasketsTest extends WP_UnitTestCase
{
    /**
     * Retourne un objet Storage initialisé avec le nombre de paniers passés en paramètre.
     *
     * @param string    $class      Nom de la classe Storage à créer.
     * @param int       $count      Nombre de paniers souhaités.
     * @param int       $current    Numéro du panier actif.
     *
     * @return Storage
     */
    private function getStorage(string $class, int $count = 1, int $current = Baskets::DEFAULT): Storage
    {
        $userID = 456; // fake user id
        $storage = new $class($userID); /* @var Storage $storage */
        $baskets = new Baskets($storage);
        for ($i = 2; $i <= $count; $i++) {
            $baskets->createBasket();
        }
        ($current !== Baskets::DEFAULT) && $baskets->setCurrentBasket($current);

        return $storage;
    }

    /**
     * Fournit la liste des Storage avec lesquels on exécute les différents tests.
     *
     * @return array[]
     */
    public function storageProvider(): array
    {
        return [
            [InMemoryBasketStorage::class],
            [UserMetaBasketStorage::class],
        ];
    }

    /**
     * Vérifie qu'une exception est générée si __construct() est appelée avec un maxBasket invalide.
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testConstructWithInvalidMaxBaskets(string $class): void
    {
        $storage = $this->getStorage($class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('maxBaskets must be greater than 0');

        new Baskets($storage, 0, 100);
    }

    /**
     * Vérifie qu'une exception est générée si __construct() est appelée avec une capacité invalide.
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testConstructWithInvalidCapacity(string $class): void
    {
        $storage = $this->getStorage($class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('basketCapacity must be greater than 0');

        new Baskets($storage, 10, 0);
    }

    /**
     * Vérifie que la méthode getList() retourne bien la liste des paniers existants et que le panier par défaut
     * existe toujours.
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testGetList(string $class): void
    {
        $baskets = new Baskets($this->getStorage($class, 1));
        $this->assertSame([
            Baskets::DEFAULT => $baskets->defaultBasketName()
        ], $baskets->getList());

        $baskets = new Baskets($this->getStorage($class, 3));
        $this->assertSame([
            Baskets::DEFAULT => $baskets->defaultBasketName(),
            2 => Baskets::defaultBasketName(2),
            3 => Baskets::defaultBasketName(3),
        ], $baskets->getList());
    }

    /**
     * Vérifie que la classe Baskets implémente l'interface Countable et que ça retourne bien le nombre de paniers
     * existants.
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testCount(string $class): void
    {
        $baskets = new Baskets($this->getStorage($class, 1));
        $this->assertInstanceOf(Countable::class, $baskets);

        $baskets = new Baskets($this->getStorage($class, 1));
        $this->assertSame(1, count($baskets));

        $baskets = new Baskets($this->getStorage($class, 3));
        $this->assertSame(3, count($baskets));
    }

    /**
     * Vérifie qu'un objet Baskets est itérable et que l'itérateur retourne la même chose que getList().
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testIterator(string $class): void
    {
        $baskets = new Baskets($this->getStorage($class, 1));
        $this->assertInstanceOf(Traversable::class, $baskets);

        $baskets = new Baskets($this->getStorage($class, 1));
        $this->assertSame($baskets->getList(), iterator_to_array($baskets));

        $baskets = new Baskets($this->getStorage($class, 3));
        $this->assertSame($baskets->getList(), iterator_to_array($baskets));
    }

    /**
     * Vérifie que la méthode hasBasket() retourne true pour les paniers qui existent et qu'elle retourne false
     * sans générer d'erreur pour ceux qui n'existent pas.
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testHasBasket(string $class): void
    {
        $baskets = new Baskets($this->getStorage($class, 1));
        $this->assertTrue($baskets->hasBasket(Baskets::DEFAULT));

        $baskets = new Baskets($this->getStorage($class, 2));
        $this->assertTrue($baskets->hasBasket(Baskets::DEFAULT));
        $this->assertTrue($baskets->hasBasket(2));

        $this->assertFalse($baskets->hasBasket(0));
        $this->assertFalse($baskets->hasBasket(3));
    }

    /**
     * Vérifie que la méthode getBasket() retourne les paniers qui existent et que c'est toujours la même
     * instance qui est retournée.
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testGetBasket(string $class): void
    {
        $baskets = new Baskets($this->getStorage($class, 2));

        $basket1 = $baskets->getBasket(Baskets::DEFAULT);
        $this->assertInstanceOf(Basket::class, $basket1);

        $basket2 = $baskets->getBasket(2);
        $this->assertInstanceOf(Basket::class, $basket2);

        $this->assertSame($basket1, $baskets->getBasket(Baskets::DEFAULT)); // même instance
        $this->assertSame($basket2, $baskets->getBasket(2)); // même instance
    }

    /**
     * Vérifie qu'une exception est générée si le panier demandé n'existe pas.
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testGetBasketNotFound(string $class): void
    {
        $baskets = new Baskets($this->getStorage($class, 2));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Basket 0 does not exist');

        $baskets->getBasket(0);
    }

    /**
     * Vérifie que la méthode createBasket() crée des paniers et stocke le nom indiqué.
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testCreateBasket(string $class): void
    {
        $baskets = new Baskets($this->getStorage($class));

        $basket = $baskets->createBasket(); // Génère un nom par défaut
        $this->assertTrue($baskets->hasBasket($basket));
        $this->assertSame(2, count($baskets));
        $this->assertInstanceOf(Basket::class, $baskets->getBasket($basket));

        $basket = $baskets->createBasket('another cart');
        $this->assertTrue($baskets->hasBasket($basket));
        $this->assertSame(3, count($baskets));
        $this->assertInstanceOf(Basket::class, $baskets->getBasket($basket));

        $this->assertSame([
            Baskets::DEFAULT => $baskets->defaultBasketName(),
            2 => Baskets::defaultBasketName(2),
            3 => 'another cart',
        ], $baskets->getList());
    }

    /**
     * Vérifie que la méthode removeBasket() supprime les paniers.
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testRemoveBasket(string $class): void
    {
        // Initialise 3 paniers
        $storage = $this->getStorage($class, 3);
        $baskets = new Baskets($storage);
        $this->assertSame(3, count($baskets));

        // Supprime le panier 2
        $baskets->removeBasket(2);
        $this->assertFalse($baskets->hasBasket(2));
        $this->assertSame(2, count($baskets));

        // Ajoute quelques notices au panier 3 et vérifie que les données figurent dans le storage
        $basket = $baskets->getBasket(3);
        $basket->add(10, 11, 12);
        $this->assertNotEmpty($storage->loadBasketData(3));
        unset($basket);

        // Supprime le panier 3 et vérifie que ça supprime également les données du panier dans le storage
        $baskets->removeBasket(3);
        $this->assertFalse($baskets->hasBasket(3));
        $this->assertSame(1, count($baskets));
        $this->assertEmpty($storage->loadBasketData(3));

        // Renomme le panier par défaut et ajoute quelques notices
        $baskets->renameBasket(Baskets::DEFAULT, 'my default cart');
        $basket = $baskets->getBasket();
        $basket->add(20, 21, 22);
        unset($basket);

        // Si on essaie de supprimer le panier par défaut, on ne peut pas mais ça le vide et ça ne change pas son nom
        $baskets->removeBasket();
        $this->assertTrue($baskets->hasBasket());
        $this->assertSame(1, count($baskets));
        $this->assertEmpty($storage->loadBasketData(Baskets::DEFAULT));
        $this->assertSame('my default cart', $baskets->getList()[Baskets::DEFAULT]);
    }

    /**
     * Teste les méthodes get/set CurrentBasket().
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testGetSetCurrentBasket(string $class): void
    {
        $baskets = new Baskets($this->getStorage($class));
        $this->assertSame(Baskets::DEFAULT, $baskets->getCurrentBasket());

        $baskets = new Baskets($storage = $this->getStorage($class, 3, 3));
        $this->assertSame(3, $baskets->getCurrentBasket());

        $baskets->setCurrentBasket(2);
        $this->assertSame(2, $baskets->getCurrentBasket());
    }

    /**
     * Vérifie qu'une exception est générée si setCurentBasket() est appellée avec un panier inexistant.
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testSetCurentBasketInvalidNumber(string $class): void
    {
        $baskets = new Baskets($this->getStorage($class, 2));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Basket 4 does not exist');

        $baskets->setCurrentBasket(4);
    }
}
