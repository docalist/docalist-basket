<?php
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Basket\Settings;

use Docalist\Type\Composite;
use Docalist\Type\LargeText;
use Docalist\Basket\Settings\ButtonLocation;

/**
 * Paramètres du bouton "ajouter/supprimer" du panier.
 *
 * Pour chaque contexte (notice seule, liste de notices...) on peut choisir l'emplacement du bouton et le
 * code html à générer pour le bouton.
 *
 * @property ButtonLocation $location   Emplacement du bouton.
 * @property LargeText      $add        Code html du bouton "ajouter au panier".
 * @property LargeText      $remove     Code html du bouton "enlever du panier".
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ButtonSettings extends Composite
{
    public static function loadSchema(): array
    {
        return [
            'fields' => [
                'location' => [
                    'type' => ButtonLocation::class,
                ],

                'add' => [
                    'type' => LargeText::class,
                    'editor' => 'htmleditor',
                    'label' => __('Ajouter au panier', 'docalist-basket'),
                    'description' => __(
                        'Vous pouvez personnaliser le code HTML comme vous le souhaitez mais le tag du bouton doit
                        obligatoirement avoir la classe CSS <code>basket-add</code>.',
                        'docalist-basket'
                    ),
                    'default' => sprintf(
                        '<button class="basket-add">%s</button>',
                        __('Sélectionner', 'docalist-basket')
                    ),
                ],

                'remove' => [
                    'type' => LargeText::class,
                    'editor' => 'htmleditor',
                    'label' => __('Enlever du panier', 'docalist-basket'),
                    'description' => __(
                        'Vous pouvez personnaliser le code HTML comme vous le souhaitez mais le tag du bouton doit
                        obligatoirement avoir la classe CSS <code>basket-remove</code>.',
                        'docalist-basket'
                    ),
                    'default' => sprintf(
                        '<button class="basket-remove">%s</button>',
                        __('Désélectionner', 'docalist-basket')
                    ),
                ],
            ],
        ];
    }
}
