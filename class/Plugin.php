<?php
/**
 * This file is part of the "Docalist Biblio UserData" plugin.
 *
 * Copyright (C) 2015-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist\Biblio
 * @subpackage  UserData
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Docalist\Biblio\UserData;

/**
 * Extension pour Docalist Biblio : génère une image à la une par défaut pour
 * les notices qui ont un lien.
 */
class Plugin {
    public function __construct() {
        // Charge les fichiers de traduction du plugin
        load_plugin_textdomain('docalist-biblio-userdata', false, 'docalist-biblio-userdata/languages');

        // Créée le service de gestion des données utilisateur
        docalist('services')->add('user-data', function() {
            return new UserData();
        });

        // Déclare le widget "Basket"
        add_action('widgets_init', function() {
            register_widget('Docalist\Biblio\UserData\BasketWidget');
        });

        // Déclare nos JS et CSS
        require_once dirname(__DIR__) . '/assets/register.php';

//         $settings = [
//             'administrator' => 1,
//             'author' => 1,
//         ];

        // Crée les actions ajax pour le panier
        if (is_user_logged_in()) {
            docalist('services')->add('basket-controller', new BasketController());
        }
    }
}