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
namespace Docalist\Tests\UserData;

// Environnement de test
$GLOBALS['wp_tests_options'] = [
    'active_plugins' => [
        'docalist-core/docalist-core.php',
        'docalist-search/docalist-search.php',
        'docalist-data/docalist-data.php',
        'docalist-userdata/docalist-userdata.php',
    ],
];

// wordpress-tests doit être dans le include_path de php
// sinon, modifier le chemin d'accès ci-dessous
require_once 'wordpress-develop/tests/phpunit/includes/bootstrap.php';
