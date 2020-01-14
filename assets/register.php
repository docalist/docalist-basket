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

namespace Docalist\Basket;

add_action('init', function () {
    $url = DOCALIST_BASKET_URL . '/assets/';

    wp_register_script('docalist-basket', $url . 'basket.js', ['jquery-core'], '180931', true);
});
