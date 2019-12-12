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

namespace Docalist\Basket\Views;

use Docalist\Basket\AdminPage\BasketSettingsPage;
use Docalist\Basket\Settings\BasketSettings;
use Docalist\Forms\Form;

/**
 * Page de configuration du module panier.
 *
 * @var BasketSettingsPage  $this       La page de configuration du panier.
 * @var BasketSettings      $settings   Les paramètres du panier.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
?>
<div class="wrap">
    <h1><?= __('Panier Docalist', 'docalist-basket') ?></h1>

    <p class="description"><?php
        _e("Le panier Docalist permet de sélectionner et d'exploiter des listes de notices.", 'docalist-basket');
    ?></p>

    <?php
        $form = new Form();
        $form->addItems($this->settings->getEditorForm()->getItems());
        $form->submit(__('Enregistrer les modifications', 'docalist-basket'))
            ->addClass('button button-primary');
        $form->bind($settings);
        $form->display('wordpress');
    ?>
</div>
