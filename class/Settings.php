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

use Docalist\Type\Settings as TypeSettings;
use Docalist\Type\Integer;
use Docalist\Type\Text;
use Docalist\Type\Boolean;

/**
 * Options de configuration du plugin.
 *
 * @property Integer $basketpage            ID de la page "panier".
 * @property Text    $htmlInactive          Code html du lien "sélectionner".
 * @property Text    $htmlActive            Code html du lien "désélectionner".
 * @property Boolean $linksBeforeContent    Afficher les liens avant le contenu.
 */
class Settings extends TypeSettings
{
    protected $id = 'docalist-userdata';

    static public function loadSchema()
    {
        return [
            'fields' => [
                'basketpage' => [
                    'type' => 'Docalist\Type\Integer',
                    'label' => __('Page du panier', 'docalist-userdata'),
                    'description' => __('Page WordPress sur laquelle sera affiché le panier.', 'docalist-userdata'),
                    'default' => 0,
                ],
                'htmlInactive' => [
                    'type' => 'Docalist\Type\Text',
                    'label' => __('Lien "sélectionner"', 'docalist-userdata'),
                    'description' => __("Code html qui sera inséré pour une notice qui ne figure pas dans le panier. Important : l'élément parent doit avoir la classe css <code>basket-add</code>.", 'docalist-userdata'),
                    'default' => sprintf('<p class="basket-add">+ <a href="#">%s</a></p>', __('Sélectionner', 'docalist-userdata')),
                ],
                'htmlActive' => [
                    'type' => 'Docalist\Type\Text',
                    'label' => __('Lien "désélectionner"', 'docalist-userdata'),
                    'description' => __("Code html qui sera inséré pour une notice qui figure déjà dans le panier. Important : l'élément parent doit avoir la classe css <code>basket-remove</code>.", 'docalist-userdata'),
                    'default' => sprintf('<p class="basket-remove">- <a href="#">%s</a></p>', __('Désélectionner', 'docalist-userdata')),
                ],
                'linksBeforeContent' => [
                    'type' => 'Docalist\Type\Boolean',
                    'label' => __('Liens avant le contenu', 'docalist-userdata'),
                    'description' => __('Par défaut, les liens sélectionner/désélectionner sont ajoutés après le contenu de la notice. Vous pouvez choisir de les insérer avant le contenu (i.e. après le titre).', 'docalist-userdata'),
                    'default' => false,
                ],
            ],
        ];
    }
}
