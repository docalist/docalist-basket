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
use Docalist\Basket\Settings\BasketPostType;
use Docalist\Basket\Settings\LimitsByRole;
use Docalist\Basket\Settings\ButtonSettings;
use Docalist\Type\WordPressPage;
use Docalist\Type\Text;

/**
 * Paramètres du module panier.
 *
 * @property LimitsByRole[]     $role           Rôles utilisateurs autorisés et limites associées.
 * @property BasketPostType[]   $types          Post types autorisés.
 * @property ButtonSettings     $single         Paramètres du bouton panier pour une notice seule.
 * @property ButtonSettings     $list           Paramètres du bouton panier dans une liste de notices.
 * @property WordPressPage      $basketpage     ID de la page WordPress permettant d'afficher le panier.
 * @property Text               $classinactive  Classe CSS des notices sélectionnables.
 * @property Text               $classactive    Classe CSS des notices sélectionnées.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class BasketSettings extends Settings
{
    protected $id = 'docalist-basket';

    public static function loadSchema(): array
    {
        return [
            'fields' => [
                'role' => [
                    'type' => LimitsByRole::class,
                    'label' => __('Utilisateurs autorisés', 'docalist-basket'),
                    'description' => __(
                        'Choisissez les utilisateurs qui auront un panier en choisissant, pour chaque rôle WordPress
                        le nombre maximum de paniers autorisés et le nombre maximum de notices que chaque panier peut
                        contenir.<br/><b>Remarque</b> : pour le moment, l\'interface du widget panier ne permet de
                        gérer qu\'un seul panier.',
                        'docalist-basket'
                    ),
                ],

                'types' =>  [
                    'type' => BasketPostType::class,
                    'repeatable' => true,
                    'key' => true,
                    'label' => __('Types de posts', 'docalist-basket'),
                    'description' => __(
                        'Choisissez les types de contenus qui peuvent être ajoutés au panier. Le bouton du panier
                        ne sera généré que pour les contenus de ce type.',
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

                'single' => [
                    'type' => ButtonSettings::class,
                    'label' => __('Bouton du panier pour une notice seule', 'docalist-basket'),
                    'description' => __(
                        'Ces paramètres s\'appliquent lorsque vous êtes sur le permalien d\'une notice
                        (une page affichant le détail d\'une notice).',
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

                'classinactive' => [
                    'type' => Text::class,
                    'label' => __('Classe CSS des notices sélectionnables', 'docalist-basket'),
                    'description' => __(
                        'Nom de la classe CSS qui sera ajoutée aux notices qui peuvent être ajoutées au panier
                        (<code>basket-inactive</code> par défaut).',
                        'docalist-basket'
                    ),
                    'default' => 'basket-inactive',
                ],

                'classactive' => [
                    'type' => Text::class,
                    'label' => __('Classe CSS des notices sélectionnées', 'docalist-basket'),
                    'description' => __(
                        'Nom de la classe CSS qui sera ajoutée aux notices sélectionnées.
                        (<code>basket-active</code> par défaut).<br />
                        Pour que les notices sélectionnées soient mises en surbrillance, votre thème doit appliquer
                        un style particulier à cette classe CSS. Si vous ne pouvez pas modifier votre thème, utilisez
                        le customizer WordPress pour ajouter du code CSS additionnel. Par exemple :
                        <code>.basket-active {background-color: yellow; transition: background-color 1s;}</code>',
                        'docalist-basket'
                    ),
                    'default' => 'basket-active',
                ],
            ],
        ];
    }
}
