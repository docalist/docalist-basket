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

use Docalist\Type\ListEntry;

/**
 * Emplacement du bouton "ajouter/supprimer" du panier.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ButtonLocation extends ListEntry
{
    /**
     * Ne pas générer de bouton.
     *
     * @var integer
     */
    const NO_BUTTON = 'none';

    /**
     * Générer un bouton avant le titre.
     *
     * @var integer
     */
    const BEFORE_TITLE = 'before-title';

    /**
     * Générer un bouton après le titre.
     *
     * @var integer
     */
    const AFTER_TITLE = 'after-title';

    /**
     * Générer un bouton avant le contenu de la notice.
     *
     * @var integer
     */
    const BEFORE_CONTENT = 'before-content';

    /**
     * Générer un bouton après le contenu de la notice.
     *
     * @var integer
     */
    const AFTER_CONTENT = 'after-content';

    public static function loadSchema(): array
    {
        return [
            'label' => __('Position du bouton', 'docalist-basket'),
            'description' => __(
                'Choisissez l\'emplacement du bouton parmi les options proposées dans la liste. Si vous souhaitez
                positionner vous-même le bouton, vous pouvez choisir l\'option "Ne pas générer" et appeler la
                fonction <code>docalistBasketGetButton()</code> dans votre thème.',
                'docalist-basket'
            ),
            'default' => self::BEFORE_CONTENT,
        ];
    }

    /**
     * Retourne la liste des emplacements disponibles pour le bouton du panier.
     *
     * @return string[] Un tableau de la forme constante d'emplacement => libellé.
     */
    protected function getEntries(): array
    {
        return [
            self::BEFORE_TITLE    => __('Avant le titre', 'docalist-basket'),
            self::AFTER_TITLE     => __('Après le titre', 'docalist-basket'),
            self::BEFORE_CONTENT  => __('Avant le contenu', 'docalist-basket'),
            self::AFTER_CONTENT   => __('Après le contenu', 'docalist-basket'),
            self::NO_BUTTON       => __('Ne pas générer de bouton', 'docalist-basket'),
        ];
    }

    public function getDefaultEditor(): string
    {
        return 'select';
    }
}
