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

use Docalist\Type\ListEntry;

/**
 * Un type de contenu qui peut être ajouté au panier.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class BasketPostType extends ListEntry
{
    /**
     * Retourne la liste des types de contenus qui peuvent être ajoutés au panier.
     *
     * @return string[] Un tableau de la forme post_type => libellé.
     */
    protected function getEntries()
    {
        $list = [];
        foreach (docalist('docalist-data')->databases() as $name => $database) {
            $list[$name] = $database->label();
        }

        return $list;
    }

    public function getDefaultEditor(): string
    {
        return 'entry-picker';
    }
}
