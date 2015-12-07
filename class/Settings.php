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
    protected $id = 'docalist-biblio-userdata';

    protected static function loadSchema()
    {
        return [
            'fields' => [
                'basketpage' => [
                    'type' => 'Docalist\Type\Integer',
                    'label' => __('Page du panier', 'docalist-biblio-userdata'),
                    'description' => __('Page WordPress sur laquelle sera affiché le panier.', 'docalist-biblio-userdata'),
                    'default' => 0,
                ],
                'htmlInactive' => [
                    'type' => 'Docalist\Type\Text',
                    'label' => __('Lien "sélectionner"', 'docalist-biblio-userdata'),
                    'description' => __("Code html qui sera inséré pour une notice qui ne figure pas dans le panier. Important : l'élément parent doit avoir la classe css <code>basket-add</code>.", 'docalist-biblio-userdata'),
                    'default' => sprintf('<p class="basket-add">+ <a href="#">%s</a></p>', __('Sélectionner', 'docalist-biblio-userdata')),
                ],
                'htmlActive' => [
                    'type' => 'Docalist\Type\Text',
                    'label' => __('Lien "désélectionner"', 'docalist-biblio-userdata'),
                    'description' => __("Code html qui sera inséré pour une notice qui figure déjà dans le panier. Important : l'élément parent doit avoir la classe css <code>basket-remove</code>.", 'docalist-biblio-userdata'),
                    'default' => sprintf('<p class="basket-remove">- <a href="#">%s</a></p>', __('Désélectionner', 'docalist-biblio-userdata')),
                ],
                'linksBeforeContent' => [
                    'type' => 'Docalist\Type\Boolean',
                    'label' => __('Liens avant le contenu', 'docalist-biblio-userdata'),
                    'description' => __('Par défaut, les liens sélectionner/désélectionner sont ajoutés après le contenu de la notice. Vous pouvez choisir de les insérer avant le contenu (i.e. après le titre).', 'docalist-biblio-userdata'),
                    'default' => false,
                ],
            ],
        ];
    }
}
