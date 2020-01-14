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

namespace Docalist\Basket\Tests\Service;

use Docalist\Basket\Tests\Service\BasketUnitTestCase;
use Docalist\Basket\Service\AjaxController;
use Docalist\Http\JsonResponse;

/**
 * Teste la classe BasketAjax.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class AjaxControllerTest extends BasketUnitTestCase
{
    /**
     * Retourne le contrôleur ajax à tester.
     *
     * @return AjaxController
     */
    protected function getAjaxController(): AjaxController
    {
        return $this->getService()->getAjaxController();
    }

    /**
     * Fournit des rôles pour lesquels le panier est autorisé.
     *
     * @return array[]
     */
    public function allowedProvider(): array
    {
        return [
            ['editor'],
            ['administrator'],
        ];
    }

    /**
     * Fournit des rôles pour lesquels le panier n'est pas autorisé.
     *
     * @return array[]
     */
    public function forbiddenProvider(): array
    {
        return [
            ['anonymous'],
            ['subscriber'],
            ['author'],
        ];
    }

    /**
     * Teste le endpoint add
     *
     * @dataProvider allowedProvider
     */
    public function testAdd(string $role): void
    {
        $this->setCurrentRole($role);
        $ajax = $this->getAjaxController();

        $json = $ajax->actionAdd('1');
        $this->assertInstanceOf(JsonResponse::class, $json);
        $this->assertSame(200, $json->getStatusCode());
        $this->assertSame('{"action":"add","result":[1],"count":1,"full":false}', $json->getContent());

        $json = $ajax->actionAdd('2');
        $this->assertInstanceOf(JsonResponse::class, $json);
        $this->assertSame(200, $json->getStatusCode());
        $this->assertSame('{"action":"add","result":[2],"count":2,"full":false}', $json->getContent());

        $json = $ajax->actionAdd('2,3');
        $this->assertInstanceOf(JsonResponse::class, $json);
        $this->assertSame(200, $json->getStatusCode());
        $this->assertSame('{"action":"add","result":[3],"count":3,"full":false}', $json->getContent());

        $json = $ajax->actionAdd('');
        $this->assertInstanceOf(JsonResponse::class, $json);
        $this->assertSame(200, $json->getStatusCode());
        $this->assertSame('{"action":"add","result":[],"count":3,"full":false}', $json->getContent());
    }

    /**
     * Teste le endpoint remove.
     *
     * @dataProvider allowedProvider
     */
    public function testRemove(string $role): void
    {
        $this->setCurrentRole($role);
        $ajax = $this->getAjaxController();
        $json = $ajax->actionAdd('1,2,3');

        $json = $ajax->actionRemove('1');
        $this->assertInstanceOf(JsonResponse::class, $json);
        $this->assertSame(200, $json->getStatusCode());
        $this->assertSame('{"action":"remove","result":[1],"count":2,"full":false}', $json->getContent());

        $json = $ajax->actionRemove('2');
        $this->assertInstanceOf(JsonResponse::class, $json);
        $this->assertSame(200, $json->getStatusCode());
        $this->assertSame('{"action":"remove","result":[2],"count":1,"full":false}', $json->getContent());

        $json = $ajax->actionRemove('1,2');
        $this->assertInstanceOf(JsonResponse::class, $json);
        $this->assertSame(200, $json->getStatusCode());
        $this->assertSame('{"action":"remove","result":[],"count":1,"full":false}', $json->getContent());

        $json = $ajax->actionRemove('');
        $this->assertInstanceOf(JsonResponse::class, $json);
        $this->assertSame(200, $json->getStatusCode());
        $this->assertSame('{"action":"remove","result":[],"count":1,"full":false}', $json->getContent());

        $json = $ajax->actionRemove('3');
        $this->assertInstanceOf(JsonResponse::class, $json);
        $this->assertSame(200, $json->getStatusCode());
        $this->assertSame('{"action":"remove","result":[3],"count":0,"full":false}', $json->getContent());
    }

    /**
     * Teste le endpoint clear.
     *
     * @dataProvider allowedProvider
     */
    public function testClear(string $role): void
    {
        $this->setCurrentRole($role);
        $ajax = $this->getAjaxController();

        $json = $ajax->actionClear();
        $this->assertInstanceOf(JsonResponse::class, $json);
        $this->assertSame(200, $json->getStatusCode());
        $this->assertSame('{"action":"clear","result":[],"count":0,"full":false}', $json->getContent());

        $json = $ajax->actionAdd('1,2,3');

        $json = $ajax->actionClear();
        $this->assertInstanceOf(JsonResponse::class, $json);
        $this->assertSame(200, $json->getStatusCode());
        $this->assertSame('{"action":"clear","result":[1,2,3],"count":0,"full":false}', $json->getContent());
    }

    /**
     * Teste le endpoint dump.
     *
     * @dataProvider allowedProvider
     */
    public function testDump(string $role): void
    {
        $this->setCurrentRole($role);
        $ajax = $this->getAjaxController();

        $json = $ajax->actionDump();
        $this->assertInstanceOf(JsonResponse::class, $json);
        $this->assertSame(200, $json->getStatusCode());
        $this->assertSame('{"action":"dump","result":[],"count":0,"full":false}', $json->getContent());

        $json = $ajax->actionAdd('1,2,3');

        $json = $ajax->actionDump();
        $this->assertInstanceOf(JsonResponse::class, $json);
        $this->assertSame(200, $json->getStatusCode());
        $this->assertSame('{"action":"dump","result":[1,2,3],"count":3,"full":false}', $json->getContent());
    }

    /**
     * Vérifie que tous les endpoints retourne 403 forbidden si l'utilisateur n'a pas les droits suffisants
     * pour avoir un panier.
     *
     * @dataProvider forbiddenProvider
     */
    public function testForbidden(string $role): void
    {
        $this->setCurrentRole($role);
        $ajax = $this->getAjaxController();

        foreach (['add', 'remove', 'clear', 'dump'] as $action) {
            $action = $ajax::ACTION_PREFIX . ucfirst($action);

            $json = $ajax->$action('1');
            $this->assertInstanceOf(JsonResponse::class, $json);
            $this->assertSame(403, $json->getStatusCode());
            $this->assertSame(json_encode(AjaxController::FORBIDDEN_MESSAGE), $json->getContent());
        }
    }
}
