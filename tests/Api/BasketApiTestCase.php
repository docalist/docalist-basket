<?php declare(strict_types=1);
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Basket\Tests\Api;

use WP_UnitTestCase;
use Docalist\Repository\SettingsRepository;
use Docalist\Basket\Settings\BasketSettings;
use Docalist\Basket\Api\BasketService;
use Docalist\Basket\Api\BasketAjax;

/**
 * Classe de base pour les tests de l'api.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class BasketApiTestCase extends WP_UnitTestCase
{
    // Remarque : en cas d'erreur "Object WP_Error can not be converted to int", il faut supprimer les users
    // de la table wp_users dans la base sql utilisée pour les tests (wordpress-tests).
    // (supprimer tous les users sauf admin).

    /**
     * ID des utilisateurs WordPress avec lequel on teste le module panier (initialisé dans setUp).
     *
     * @var int[] Un tableau de la forme rôle => UserID
     */
    protected $users;

    /**
     * Initialisation des tests.
     *
     * Crée des users WordPress avec différents rôles et stocke leur ID.
     */
    public function setUp(): void
    {
        parent::setUp();

        // Crée des users WordPress avec différents rôles et stocke leur ID
        $this->users = ['anonymous' => 0];
        foreach (['subscriber', 'author', 'editor', 'administrator'] as $role) {
            $this->users[$role] = $this->factory->user->create(['role' => $role]);
        }
    }

    /**
     * Retourne les paramètres du module panier utilisés dans les tests.
     *
     * @return BasketSettings Retourne des settings paramétrés de la façon suivante :
     *
     * - utilisateur anonyme : pas de panier
     * - subscriber : figure dans la liste des settings, mais maxBasket est à 0 donc pas de panier
     * - author : ne figure pas dans la liste des settings, donc pas de panier
     * - editor : dans la liste, 1 seul panier de 10 notices
     * - admin : dans la liste, 5 paniers de 100 notices
     */
    protected function getServiceSettings(): BasketSettings
    {
        static $settings = null;

        if (is_null($settings)) {
            $repository = new SettingsRepository();
            $settings = new BasketSettings($repository);
            $settings->assign([
                'role' => [
                    ['role' => 'subscriber',    'maxBaskets' => 0, 'basketCapacity' => 0],
                    ['role' => 'editor',        'maxBaskets' => 1, 'basketCapacity' => 10],
                    ['role' => 'administrator', 'maxBaskets' => 5, 'basketCapacity' => 100],
                ]
            ]);
        }

        return $settings;
    }

    /**
     * Retourne le service BasketService à tester.
     *
     * @return BasketService
     */
    protected function getService(): BasketService
    {
        return new BasketService($this->getServiceSettings());
    }

    /**
     * Retourne l'API ajax à tester.
     *
     * @return BasketAjax
     */
    protected function getAjax(): BasketAjax
    {
        return new BasketAjax($this->getService());
    }

    /**
     * Change l'utilisateur WordPress en cours pour un utilisateur ayant le rôle demandé.
     *
     * @param string $role Rôle souhaité : 'anonymous', 'subscriber', 'author', 'editor' ou 'administrator'.
     */
    protected function setCurrentRole(string $role): void
    {
        wp_set_current_user($this->users[$role]);
    }
}
