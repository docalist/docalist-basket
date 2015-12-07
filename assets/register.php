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
 */
namespace Docalist\Biblio\UserData;

add_action('init', function () {
    $url = plugins_url('docalist-biblio-userdata/assets');

    wp_register_script(
        'docalist-biblio-userdata-basket',
        "$url/basket.js",
        ['jquery'],
        '150420',
        true
    );

    wp_register_style(
        'docalist-biblio-userdata-basket',
        "$url/basket.css",
        [],
        '150420'
    );
});
