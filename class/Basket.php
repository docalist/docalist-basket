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

class Basket extends UserDataObject
{
    /**
     * Initialise le panier.
     *
     * @param string $name Le nom du panier.
     * @param int $user L'ID de l'utilisateur du panier.
     */
    public function __construct($name, $user)
    {
        parent::__construct('basket', $name, $user);
    }

    public function data()
    {
        return array_keys($this->data);
    }

    protected function unserialize($data)
    {
        return array_flip(parent::unserialize($data));
    }

    /**
     * Ajoute une ou plusieurs références au panier.
     *
     * @param int|array $refs
     *
     * @return self
     */
    public function add($refs)
    {
        !$this->isModified && $previous = $this->data;
        $this->data += array_flip((array) $refs);
        !$this->isModified && $this->isModified = ($this->data !== $previous);

        return $this;
    }

    /**
     * Supprime une ou plusieurs références du panier.
     *
     * @param int|array $refs
     *
     * @return self
     */
    public function remove($refs)
    {
        !$this->isModified && $previous = $this->data;
        $this->data = array_diff_key($this->data, array_flip((array) $refs));
        !$this->isModified && $this->isModified = ($this->data !== $previous);

        return $this;
    }
}
