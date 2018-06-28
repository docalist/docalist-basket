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

add_action('init', function () {
    $url = DOCALIST_USERDATA_URL . '/assets/';

    wp_register_script('docalist-userdata-basket', $url . 'basket.js', ['jquery-core'], '180625', true);
    wp_register_style('docalist-userdata-basket', $url . 'basket.css', [], '180625');
});
