<?php
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Basket\Settings;

use Docalist\Type\Composite;
use Docalist\Type\UserRole;
use Docalist\Type\Integer;

/**
 * Rôles WordPress autorisés à avoir un panier et paramètres correspondants.
 *
 * @property UserRole   $role           Rôle WordPress.
 * @property Integer    $maxBaskets     Nombre maximum de paniers autorisés.
 * @property Integer    $basketCapacity Nombre maximum de notices par panier.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class LimitsByRole extends Composite
{
    public static function loadSchema(): array
    {
        return [
            'repeatable' => true,
            'key' => 'role',
            'editor' => 'table',
            'fields' => [
                'role' => [
                    'type' => UserRole::class,
                    'label' => __('Rôle WordPress', 'docalist-basket'),
                    'editor' => 'select',
                ],

                'maxBaskets' => [
                    'type' => Integer::class,
                    'label' => __('Nombre de paniers', 'docalist-basket'),
                    'default' => 1,
                ],

                'basketCapacity' => [
                    'type' => Integer::class,
                    'label' => __('Limite par panier', 'docalist-basket'),
                    'default' => 100,
                ],
            ],
        ];
    }
}
