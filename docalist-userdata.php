<?php
/**
 * This file is part of Docalist UserData.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * Plugin Name: Docalist User Data
 * Plugin URI:  https://docalist.org/
 * Description: Docalist : données utilisateurs.
 * Version:     0.3.0
 * Author:      Daniel Ménard
 * Author URI:  https://docalist.org/
 * Text Domain: docalist-userdata
 * Domain Path: /languages
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\UserData;

/**
 * Version du plugin.
 */
define('DOCALIST_USERDATA_VERSION', '0.3.0'); // Garder synchro avec la version indiquée dans l'entête

/**
 * Path absolu du répertoire dans lequel le plugin est installé.
 *
 * Par défaut, on utilise la constante magique __DIR__ qui retourne le path réel du répertoire et résoud les liens
 * symboliques.
 *
 * Si le répertoire du plugin est un lien symbolique, la constante doit être définie manuellement dans le fichier
 * wp_config.php et pointer sur le lien symbolique et non sur le répertoire réel.
 */
!defined('DOCALIST_USERDATA_DIR') && define('DOCALIST_USERDATA_DIR', __DIR__);

/**
 * Path absolu du fichier principal du plugin.
 */
define('DOCALIST_USERDATA', DOCALIST_USERDATA_DIR . DIRECTORY_SEPARATOR . basename(__FILE__));

/**
 * Url de base du plugin.
 */
define('DOCALIST_USERDATA_URL', plugins_url('', DOCALIST_USERDATA));

/**
 * Initialise le plugin.
 */
add_action('plugins_loaded', function () {
    // Auto désactivation si les plugins dont on a besoin ne sont pas activés
    $dependencies = ['DOCALIST_CORE', 'DOCALIST_DATA'];
    foreach ($dependencies as $dependency) {
        if (! defined($dependency)) {
            return add_action('admin_notices', function () use ($dependency) {
                deactivate_plugins(DOCALIST_USERDATA);
                unset($_GET['activate']); // empêche wp d'afficher "extension activée"
                printf(
                    '<div class="%s"><p><b>%s</b> has been deactivated because it requires <b>%s</b>.</p></div>',
                    'notice notice-error is-dismissible',
                    'Docalist Databases',
                    ucwords(strtolower(strtr($dependency, '_', ' ')))
                );
            });
        }
    }

    // Ok
    docalist('autoloader')->add(__NAMESPACE__, __DIR__ . '/class');
    docalist('services')->add('docalist-userdata', new Plugin());
});
