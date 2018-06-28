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
namespace Docalist\UserData\Views;

use Docalist\UserData\SettingsPage;
use Docalist\UserData\Settings;
use Docalist\Forms\Form;

/**
 * Paramètres du panier.
 *
 * @var SettingsPage    $this       Les paramètres du panier.
 * @var Settings        $settings   Les paramètres du panier.
 */
?>
<div class="wrap">
    <h1><?= __('Panier Docalist', 'docalist-userdata') ?></h1>

    <p class="description"><?php
        echo __(
            'Le panier Docalist vous permet de sélectionner les documents qui vous intéressent.',
            'docalist-userdata'
        );
    ?></p>

    <?php
        $form = new Form();
        $form->select('basketpage')->setOptions(pagesList())->setFirstOption(false);
        $form->input('htmlInactive')->addClass('large-text code');
        $form->input('htmlActive')->addClass('large-text code');
        $form->checkbox('linksBeforeContent');
        $form->submit(__('Enregistrer les modifications', 'docalist-userdata'))
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
