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
 * @version     SVN: $Id$
 */
namespace Docalist\Biblio\UserData\Views;

use Docalist\Biblio\UserData\Settings;
use Docalist\Forms\Form;

/**
 * Paramètres du panier.
 *
 * @param Settings $settings Les paramètres du panier.
 */
?>
<div class="wrap">
    <?= screen_icon() ?>
    <h2><?= __("Paramètres du panier", 'docalist-biblio-userdata') ?></h2>

    <p class="description"><?php
        echo __(
            "Le panier vous permet de sélectionner les notices qui vous intéressent puis de les exploiter.",
            'docalist-biblio-userdata'
        );
    ?></p>

    <?php
        $form = new Form();
        $form->select('basketpage')->options(pagesList())->firstOption(false);
        $form->input('htmlInactive')->addClass('large-text code');
        $form->input('htmlActive')->addClass('large-text code');
        $form->checkbox('linksBeforeContent');
        $form->submit(__('Enregistrer les modifications', 'docalist-biblio-userdata'));

        $form->bind($settings)->render('wordpress');
    ?>
</div>

<?php
/**
 * Retourne la liste hiérarchique des pages sous la forme d'un tableau
 * utilisable dans un select.
 *
 * @return array Un tableau de la forme PageID => PageTitle
 */
function pagesList() {
    $pages = ['…'];
    foreach(get_pages() as $page) { /* @var $page \WP_Post */
        $pages[$page->ID] = str_repeat('   ', count($page->ancestors)) . $page->post_title;
    }

    return $pages;
}
