<?php
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2020 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
declare(strict_types=1);

namespace Docalist\Tests\Basket;

// Environnement de test
$GLOBALS['wp_tests_options'] = [
    'active_plugins' => [
        'docalist-core/docalist-core.php',
        'docalist-data/docalist-data.php',
        'docalist-search/docalist-search.php',
        'docalist-basket/docalist-basket.php',
    ],
];

// wordpress-tests doit être dans le include_path de php
// sinon, modifier le chemin d'accès ci-dessous
require_once 'wordpress-develop/tests/phpunit/includes/bootstrap.php';
