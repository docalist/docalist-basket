<?php declare(strict_types=1);
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Basket\Tests;

use WP_UnitTestCase;
use Docalist\Basket\Basket;
use Docalist\Basket\Storage;
use Docalist\Basket\Storage\InMemoryBasketStorage;
use Docalist\Basket\Storage\UserMetaBasketStorage;

/**
 * Teste la classe Basket.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class BasketTest extends WP_UnitTestCase
{
    /**
     * Retourne un objet Storage initialisé.
     *
     * @param string $class Nom de la classe Storage à créer.
     *
     * @return Storage
     */
    private function getStorage(string $class): Storage
    {
        $userID = 456; // fake user id
        $storage = new $class($userID); /* @var Storage $storage */

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
     * Vérifie qu'une exception est générée si __construct() est appelée avec une capacité invalide.
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage basketCapacity must be greater than 0
     */
    public function testConstructWithInvalidCapacity(string $class): void
    {
        new Basket(1, $this->getStorage($class), 0);
    }

    /**
     * Vérifie que la méthode add() ajoute les notices au panier.
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testAdd(string $class): void
    {
        $storage = $this->getStorage($class);

        // Crée un panier et vérifie qu'il est vide
        $basket = new Basket(1, $storage);
        $this->assertSame($storage->loadBasketData(1), []);

        // Ajoute des notices et vérifie que add() retourne les notices ajoutées
        $this->assertSame([15], $basket->add(15));
        $this->assertSame([], $basket->add(15));

        // Vérifie que les données sont bien enregistrées
        $this->assertSame($storage->loadBasketData(1), [15]);

        // Vérifie qu'on peut ajouter plusieurs notices en une seule étape et que l'ordre d'ajout est conservé
        $this->assertSame([16, 17, 18], $basket->add(16, 17, 18));
        $this->assertSame($storage->loadBasketData(1), [15, 16, 17, 18]);

        // Vérifie qu'aucune erreur n'est générée si on ajoute des notices déjà sélectionnées
        $this->assertSame([], $basket->add(16, 18)); // rien n'a été ajouté
        $this->assertSame($storage->loadBasketData(1), [15, 16, 17, 18]); // et l'ordre n'a pas changé

        // Vérifie que add() peut être appellée sans paramètre (effet de bord de ...$postID)
        $this->assertSame([], $basket->add());
        $this->assertSame($storage->loadBasketData(1), [15, 16, 17, 18]);
    }

    /**
     * Teste le fonctionnement de add() quand le panier est plein.
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testAddWhenBasketIsFull(string $class): void
    {
        $storage = $this->getStorage($class);

        // Crée un panier pouvant contenir deux notices
        $basket = new Basket(1, $storage, 2);

        // Essaie d'ajouter trois notices, seules les deux premières sont ajoutées et le panier est plein
        $this->assertSame([1, 2], $basket->add(1, 2, 3));
        $this->assertSame($storage->loadBasketData(1), [1, 2]);
        $this->assertTrue($basket->isFull());

        // Enlève une notice, il nous reste de la place pour une
        $this->assertSame([2], $basket->remove(2));
        $this->assertSame($storage->loadBasketData(1), [1]);
        $this->assertFalse($basket->isFull());

        // On peut à nouveau ajouter une notice et le panier sera à nouveau plein
        $this->assertSame([2], $basket->add(2));
        $this->assertSame($storage->loadBasketData(1), [1, 2]);
        $this->assertTrue($basket->isFull());
    }

    /**
     * Vérifie que la méthode remove() supprime des notices au panier.
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testRemove(string $class): void
    {
        $storage = $this->getStorage($class);

        // Crée deux paniers, un vide et un avec quatre notices
        $basket1 = new Basket(1, $storage);
        $basket2 = new Basket(2, $storage);
        $basket2->add(20, 21, 22, 23);
        $this->assertSame($storage->loadBasketData(1), []);
        $this->assertSame($storage->loadBasketData(2), [20, 21, 22, 23]);

        // Supprime une notice qui n'existe pas, vérifie qu'on n'a aucune erreur et que le panier est toujours vide
        $this->assertSame([], $basket1->remove(10)); // rien n'a été supprimé
        $this->assertSame($storage->loadBasketData(1), []);
        $this->assertSame([], $basket2->remove(29)); // rien n'a été supprimé
        $this->assertSame($storage->loadBasketData(2), [20, 21, 22, 23]);

        // Supprime une notice existante
        $this->assertSame([21], $basket2->remove(21));
        $this->assertSame($storage->loadBasketData(2), [20, 22, 23]);

        // Vérifie qu'on peut supprimer plusieurs notices d'un coup
        $this->assertSame([20, 23], $basket2->remove(20, 23));
        $this->assertSame($storage->loadBasketData(2), [22]);

        // Vérifie que remove() peut être appellée sans paramètre (effet de bord de ...$postID)
        $this->assertSame([], $basket2->remove());
        $this->assertSame($storage->loadBasketData(2), [22]);
    }

    /**
     * Vérifie que la méthode clear() vide le panier.
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testClear(string $class): void
    {
        $storage = $this->getStorage($class);

        // Crée deux paniers
        $basket1 = new Basket(1, $storage);
        $basket2 = new Basket(2, $storage);
        $this->assertSame($storage->loadBasketData(1), []);
        $this->assertSame($storage->loadBasketData(2), []);

        // Teste clear() sur un panier vide
        $basket1->clear();
        $basket2->clear();
        $this->assertSame($storage->loadBasketData(1), []);
        $this->assertSame($storage->loadBasketData(2), []);

        // Vérifie que clear() vide le bon panier
        $basket1->add(10);
        $this->assertSame($storage->loadBasketData(1), [10]);
        $basket2->clear();
        $this->assertSame($storage->loadBasketData(1), [10]);
        $basket1->clear();
        $this->assertSame($storage->loadBasketData(1), []);
    }

    /**
     * Vérifie que la méthode has() indique si la panier contient ou non une notice donnée.
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testHas(string $class): void
    {
        $storage = $this->getStorage($class);

        // Crée un panier
        $basket = new Basket(1, $storage);
        $this->assertSame($storage->loadBasketData(1), []);

        // Teste has() sur un panier vide
        $this->assertFalse($basket->has(10));

        // Ajoute une notice et vérifie que has() retourne true
        $basket->add(20);
        $this->assertFalse($basket->has(10));
        $this->assertTrue($basket->has(20));

        // Vide le panier et reteste
        $basket->clear();
        $this->assertFalse($basket->has(10));
        $this->assertFalse($basket->has(20));
    }

    /**
     * Vérifie que le panier implémente Countable et que count() retourne le nombre de notices dans le panier.
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testCount(string $class): void
    {
        $storage = $this->getStorage($class);

        // Crée un panier
        $basket = new Basket(1, $storage);
        $this->assertSame($storage->loadBasketData(1), []);

        // Teste count() sur un panier vide
        $this->assertSame(0, count($basket));

        // Ajoute des notices et vérifie que count() retourne le bon nombre
        $basket->add(20);
        $this->assertSame(1, count($basket));
        $basket->add(21, 22, 23);
        $this->assertSame(4, count($basket));

        // Supprime des notices
        $basket->remove(20, 22);
        $this->assertSame(2, count($basket));

        // Vide le panier
        $basket->clear();
        $this->assertSame(0, count($basket));
    }

    /**
     * Vérifie que getContent() retourne le contenu du panier.
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testGetContent(string $class): void
    {
        $storage = $this->getStorage($class);

        // Crée un panier
        $basket = new Basket(1, $storage);
        $this->assertSame($basket->getContents(), []);

        // Teste getContents() sur un panier vide
        $this->assertSame([], $basket->getContents());

        // Ajoute des notices et vérifie que getContents() retourne le contenu
        $basket->add(20);
        $this->assertSame([20], $basket->getContents());
        $basket->add(21, 22, 23);
        $this->assertSame([20, 21, 22, 23], $basket->getContents());

        // Supprime des notices
        $basket->remove(20, 22);
        $this->assertSame([21, 23], $basket->getContents());

        // Vide le panier
        $basket->clear();
        $this->assertSame([], $basket->getContents());
    }

    /**
     * Vérifie qu'un objet Basket est itérable et que l'itérateur retourne la même chose que getContents().
     *
     * @param string $class Nom de la classe BasketStorage à utiliser.
     *
     * @dataProvider storageProvider
     */
    public function testIterator(string $class): void
    {
        $basket = new Basket(1, $this->getStorage($class));
        $this->assertSame($basket->getContents(), iterator_to_array($basket));

        $basket->add(10, 11, 12);
        $this->assertSame($basket->getContents(), iterator_to_array($basket));

        $basket->remove(11);
        $this->assertSame($basket->getContents(), iterator_to_array($basket));

        $basket->clear();
        $this->assertSame($basket->getContents(), iterator_to_array($basket));
    }
}
