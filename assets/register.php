<?php declare(strict_types=1);
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Basket;

add_action('init', function () {
    $url = DOCALIST_BASKET_URL . '/assets/';

    wp_register_script('docalist-basket', $url . 'basket.js', ['jquery-core'], '180625', true);
    wp_register_style('docalist-basket', $url . 'basket.css', [], '180625');
});
