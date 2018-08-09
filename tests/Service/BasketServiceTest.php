<?php declare(strict_types=1);
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Basket\Tests\Service;

use Docalist\Basket\Tests\Service\BasketUnitTestCase;
use Docalist\Basket\Baskets;
use Docalist\Basket\Basket;

/**
 * Teste la classe BasketService.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class BasketServiceTest extends BasketUnitTestCase
{
    /**
     * Jeu de tests pour testCoreMethods.
     *
     * @return array[]
     */
    public function roleProvider(): array
    {
        return [
            ['anonymous',       false,  0, 0    ], // role, active, maxBaskets, basketCapacity
            ['subscriber',      false,  0, 0    ],
            ['author',          false,  0, 0    ],
            ['editor',          true,   1, 10   ],
            ['administrator',   true,   5, 100  ],
        ];
    }

    /**
     * Teste les méthodes isActive(), getMaxBaskets(), getBasketCapacity(), getBaskets() et getBasket().
     *
     * @param string    $role           Rôle à tester.
     * @param bool      $active         Est-ce que le panier est actif pour ce rôle ?
     * @param int       $maxBaskets     Nombre maximum de paniers autorisés pour ce rôle.
     * @param int       $basketCapacity Nombre maximum de notices par panier pour ce rôle.
     *
     * @dataProvider roleProvider
     */
    public function testCoreMethods(string $role, bool $active, int $maxBaskets, int $basketCapacity): void
    {
        // Active un utilisateur ayant le rôle demandé
        $this->setCurrentRole($role);

        // Récupère le service basket
        $service = $this->getService();

        // Vérifie que les méthdoes retournent les paramètres fournis
        $this->assertSame($active, $service->isActive());
        $this->assertSame($maxBaskets, $service->getMaxBaskets());
        $this->assertSame($basketCapacity, $service->getBasketCapacity());

        // Récupère la liste des paniers et le panier en cours
        $baskets = $service->getBaskets();
        $basket = $service->getBasket();

        // Si le sevice panier n'est pas actif, les deux doivent être à null
        if (!$active) {
            $this->assertNull($baskets);
            $this->assertNull($basket);

            return;
        }

        // Sinon, vérifie que la liste des paniers est un objet Baskets correctement initialisé
        $this->assertInstanceOf(Baskets::class, $baskets);
        $this->assertSame($maxBaskets, $baskets->getMaxBaskets());
        $this->assertSame($basketCapacity, $baskets->getBasketCapacity());

        // Vérifie que le panier en cours est un objet Basket correctement initialisé
        $this->assertInstanceOf(Basket::class, $basket);
        $this->assertSame($basketCapacity, $basket->getCapacity());
    }

    /**
     * Teste la méthode reset().
     *
     */
    public function testReset(): void
    {
        $service = $this->getService();
        foreach ($this->roleProvider() as $test) {
            list($role, $active, $maxBaskets, $basketCapacity) = $test;

            $this->setCurrentRole($role);
            $service->reset();

            $this->assertSame($active, $service->isActive());
            $this->assertSame($maxBaskets, $service->getMaxBaskets());
            $this->assertSame($basketCapacity, $service->getBasketCapacity());
        }
    }
}
