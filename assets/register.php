<?php
/**
 * This file is part of the "Docalist Biblio UserData" plugin.
 *
 * Copyright (C) 2015-2017 Daniel Ménard
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
    $url = DOCALIST_BIBLIO_USERDATA_URL;

    wp_register_script(
        'docalist-biblio-userdata-basket',
        "$url/assets/basket.js",
        ['jquery'],
        '150420',
        true
    );

    wp_register_style(
        'docalist-biblio-userdata-basket',
        "$url/assets/basket.css",
        [],
        '150420'
    );
});
