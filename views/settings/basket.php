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
namespace Docalist\Biblio\UserData\Views;

use Docalist\Biblio\UserData\SettingsPage;
use Docalist\Biblio\UserData\Settings;
use Docalist\Forms\Form;

/**
 * Paramètres du panier.
 *
 * @var SettingsPage    $this       Les paramètres du panier.
 * @var Settings        $settings   Les paramètres du panier.
 */
?>
<div class="wrap">
    <h1><?= __('Paramètres du panier', 'docalist-biblio-userdata') ?></h1>

    <p class="description"><?php
        echo __(
            'Le panier vous permet de sélectionner les notices qui vous intéressent puis de les exploiter.',
            'docalist-biblio-userdata'
        );
    ?></p>

    <?php
        $form = new Form();
        $form->select('basketpage')->setOptions(pagesList())->setFirstOption(false);
        $form->input('htmlInactive')->addClass('large-text code');
        $form->input('htmlActive')->addClass('large-text code');
        $form->checkbox('linksBeforeContent');
        $form->submit(__('Enregistrer les modifications', 'docalist-biblio-userdata'))
             ->addClass('button button-primary');

        $form->bind($settings)->display();
    ?>
</div>

<?php
/**
 * Retourne la liste hiérarchique des pages sous la forme d'un tableau
 * utilisable dans un select.
 *
 * @return array Un tableau de la forme PageID => PageTitle
 */
function pagesList()
{
    $pages = ['…'];
    foreach (get_pages() as $page) { /** @var \WP_Post $page */
        $pages[$page->ID] = str_repeat('   ', count($page->ancestors)) . $page->post_title;
    }

    return $pages;
}
