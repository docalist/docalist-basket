<?php
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * Plugin Name: Docalist Basket
 * Plugin URI:  https://docalist.org/
 * Description: Docalist : panier de notices.
 * Version:     3.1.0
 * Author:      Daniel Ménard
 * Author URI:  https://docalist.org/
 * Text Domain: docalist-basket
 * Domain Path: /languages
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
declare(strict_types=1);

namespace Docalist\Basket;

/**
 * Version du plugin.
 */
define('DOCALIST_BASKET_VERSION', '3.1.0'); // Garder synchro avec la version indiquée dans l'entête

/**
 * Path absolu du répertoire dans lequel le plugin est installé.
 *
 * Par défaut, on utilise la constante magique __DIR__ qui retourne le path réel du répertoire et résoud les liens
 * symboliques.
 *
 * Si le répertoire du plugin est un lien symbolique, la constante doit être définie manuellement dans le fichier
 * wp_config.php et pointer sur le lien symbolique et non sur le répertoire réel.
 */
!defined('DOCALIST_BASKET_DIR') && define('DOCALIST_BASKET_DIR', __DIR__);

/**
 * Path absolu du fichier principal du plugin.
 */
define('DOCALIST_BASKET', DOCALIST_BASKET_DIR . DIRECTORY_SEPARATOR . basename(__FILE__));

/**
 * Url de base du plugin.
 */
define('DOCALIST_BASKET_URL', plugins_url('', DOCALIST_BASKET));

/**
 * Initialise le plugin.
 */
add_action('plugins_loaded', function () {
    // Auto désactivation si les plugins dont on a besoin ne sont pas activés
    $dependencies = ['DOCALIST_CORE', 'DOCALIST_DATA', 'DOCALIST_SEARCH'];
    foreach ($dependencies as $dependency) {
        if (! defined($dependency)) {
            return add_action('admin_notices', function () use ($dependency) {
                deactivate_plugins(DOCALIST_BASKET);
                unset($_GET['activate']); // empêche wp d'afficher "extension activée"
                printf(
                    '<div class="%s"><p><b>%s</b> has been deactivated because it requires <b>%s</b>.</p></div>',
                    'notice notice-error is-dismissible',
                    'Docalist Basket',
                    ucwords(strtolower(strtr($dependency, '_', ' ')))
                );
            });
        }
    }

    // Ok
    docalist('autoloader')
        ->add(__NAMESPACE__, __DIR__ . '/class')
        ->add(__NAMESPACE__ . '\Tests', DOCALIST_BASKET_DIR . '/tests');

    docalist('services')->add('docalist-basket-plugin', new Plugin());
});
