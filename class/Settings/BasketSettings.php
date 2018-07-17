<?php declare(strict_types=1);
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Basket\Settings;

use Docalist\Type\Settings;
use Docalist\Basket\Settings\LimitsByRole;
use Docalist\Basket\Settings\ButtonSettings;
use Docalist\Type\WordPressPage;

/**
 * Paramètres du module panier.
 *
 * @property LimitsByRole   $role       Rôles utilisateurs autorisés et limites associées.
 * @property ButtonSettings $single     Paramètres du bouton panier pour une notice seule.
 * @property ButtonSettings $list       Paramètres du bouton panier dans une liste de notices.
 * @property WordPressPage  $basketpage ID de la page WordPress permettant d'afficher le panier.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class BasketSettings extends Settings
{
    protected $id = 'docalist-basket';

    public static function loadSchema()
    {
        return [
            'fields' => [
/*
                'basketable' =>  [
                    'type' => BasketableContent::class,
                ],
*/
                'role' => [
                    'type' => LimitsByRole::class,
                    'label' => __('Utilisateurs autorisés', 'docalist-basket'),
                    'description' => __(
                        'Choisissez les utilisateurs qui auront un panier en choisissant, pour chaque rôle WordPress
                        le nombre maximum de paniers autorisés et le nombre maximum de notices que chaque panier peut
                        contenir',
                        'docalist-basket'
                    ),
                ],

                'single' => [
                    'type' => ButtonSettings::class,
                    'label' => __('Bouton du panier pour une notice seule', 'docalist-basket'),
                    'description' => __(
                        'Ces paramètres s\'appliquent lorsque vous êtes sur le permalien d\'une notice
                        (une page affichant le détail d\'une notice).',
                        'docalist-basket'
                    ),
                ],

                'list' => [
                    'type' => ButtonSettings::class,
                    'label' => __('Bouton du panier dans une liste de notices', 'docalist-basket'),
                    'description' => __(
                        'Ces paramètres s\'appliquent lorsque vous êtes sur une page qui affiche une liste
                        de notices (résultats d\'une recherche ou page d\'archives).',
                        'docalist-basket'
                    ),
                ],

                'basketpage' => [
                    'type' => WordPressPage::class,
                    'label' => __('Page du panier', 'docalist-basket'),
                    'description' => __(
                        'Choisissez la page WordPress qui servira à afficher le contenu du panier.',
                        'docalist-basket'
                    ),
                    'default' => 0,
                ],
            ],
        ];
    }
}
