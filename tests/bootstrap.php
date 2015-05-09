<?php
/**
 * This file is part of the "Docalist Biblio UserData" plugin.
 *
 * Copyright (C) 2015-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Tests\Biblio\UserData
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */
// Environnement de test
$GLOBALS['wp_tests_options'] = [
    'active_plugins' => [
        'docalist-core/docalist-core.php',
        'docalist-search/docalist-search.php',
        'docalist-biblio/docalist-biblio.php',
        'docalist-biblio-userdata/docalist-biblio-userdata.php',
    ],
];

// wordpress-tests doit être dans le include_path de php
// sinon, modifier le chemin d'accès ci-dessous
require_once 'wordpress-develop/tests/phpunit/includes/bootstrap.php';
