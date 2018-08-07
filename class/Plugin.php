<?php declare(strict_types=1);
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Basket;

use Docalist\Basket\Settings\BasketSettings;
use Docalist\Views;
use Docalist\Basket\AdminPage\BasketSettingsPage;
use Docalist\Basket\Api\BasketService;
use Docalist\Basket\Widget\BasketWidget;
use Docalist\Basket\Api\BasketAjax;

/**
 * Plugin docalist-basket.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Plugin
{
    public function __construct()
    {
        // Charge les fichiers de traduction du plugin
        load_plugin_textdomain('docalist-basket', false, 'docalist-basket/languages');

        // Charge la configuration du plugin
        $settings = new BasketSettings(docalist('settings-repository'));

        // Crée le service docalist('basket')
        docalist('services')->add('basket', function () use ($settings) {
            return new BasketService($settings);
        });

        // Crée le service docalist('basket-ajax')
        docalist('services')->add('basket-ajax', new BasketAjax(docalist('basket')));

        // Ajoute notre répertoire "views" au service "docalist-views"
        add_filter('docalist_service_views', function (Views $views) {
            return $views->addDirectory('docalist-basket', DOCALIST_BASKET_DIR . '/views');
        });

        // Crée la page de réglages du plugin
        add_action('admin_menu', function () use ($settings) {
            new BasketSettingsPage($settings);
        });

        // Déclare le widget "Basket"
        add_action('widgets_init', function () {
            register_widget(BasketWidget::class);
        });

        // Déclare nos assets
        require_once dirname(__DIR__) . '/assets/register.php';
    }
}
