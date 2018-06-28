<?php declare(strict_types=1);
/**
 * This file is part of Docalist UserData.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\UserData;

use Docalist\Views;

class Plugin
{
    /**
     * Les paramètres du plugin.
     *
     * @var Settings
     */
    protected $settings;

    public function __construct()
    {
        // Charge les fichiers de traduction du plugin
        load_plugin_textdomain('docalist-userdata', false, 'docalist-userdata/languages');

        // Ajoute notre répertoire "views" au service "docalist-views"
        add_filter('docalist_service_views', function(Views $views) {
            return $views->addDirectory('docalist-userdata', DOCALIST_USERDATA_DIR . '/views');
        });

        // Charge la configuration du plugin
        $this->settings = new Settings(docalist('settings-repository'));

        // Crée la page de réglages du plugin
        add_action('admin_menu', function () {
            new SettingsPage($this->settings);
        });

        // Déclare le widget "Basket"
        add_action('widgets_init', function () {
            register_widget('Docalist\UserData\BasketWidget');
        });

        // Si l'utilisateur en cours n'est pas connecté, aucun service n'est créé
        if (! is_user_logged_in()) {
            return;
        }

        // Créée le service de gestion des données utilisateur
        docalist('services')->add('user-data', function () {
            return new UserData();
        });

        // Crée les actions ajax pour le panier
        docalist('services')->add('basket-controller', new BasketController($this->settings));

        // Déclare nos assets
        require_once dirname(__DIR__) . '/assets/register.php';
    }
}
