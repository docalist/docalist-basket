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
namespace Docalist\Biblio\UserData;

use Countable;
use Exception;

class UserDataObject implements Countable {
    /**
     * Le type de l'objet.
     *
     * @var string
     */
    protected $type;

    /**
     * Le nom de l'objet.
     *
     * @var string
     */
    protected $name;

    /**
     * ID de l'utilisateur.
     *
     * @var int
     */
    protected $user;

    /**
     * L'identifiant de l'objet.
     *
     * @var string
     */
    protected $id;

    /**
     * Les données de l'objet.
     *
     * @var array
     */
    protected $data;

    /**
     * Indique si les données de l'objet ont été modifiées.
     *
     * @var bool
     */
    protected $isModified;

    /**
     * Initialise et charge un objet User Data
     *
     * @param string $type Le type de l'objet.
     * @param string $name L'identifiant de l'objet.
     * @param int $user L'ID de l'utilisateur propriétaire de l'objet.
     */
    public function __construct($type, $name, $user) {
        if (!ctype_alnum($type)) {
            throw new Exception("Invalid user data object type '$type'");
        }
        if (!ctype_alnum($name)) {
            throw new Exception("Invalid user data object name '$name'");
        }
        if (!is_int($user) || $user < 1) {
            throw new Exception("Invalid user ID $user");
        }

        $this->type = $type;
        $this->name = $name;
        $this->user = $user;
        $this->id = "docalist-$type-$name";
        $this->isModified = false;

        $this->load();
    }

    /**
     * Retourne le type de l'objet.
     *
     * @return string
     */
    public function type() {
        return $this->type;
    }

    /**
     * Retourne le nom de l'objet.
     *
     * @return string
     */
    public function name() {
        return $this->name;
    }

    /**
     * Retourne l'ID de l'utilisateur.
     *
     * @return string
     */
    public function user() {
        return $this->user;
    }

    /**
     * Retourne l'identifiant de l'objet.
     *
     * @return string
     */
    public function id() {
        return $this->id;
    }

    /**
     * Retourne les données de l'objet.
     *
     * @return array
     */
    public function data() {
        return $this->data;
    }

    /**
     * Indique si les données de l'objet ont été modifiées.
     *
     * @return boolean
     */
    public function isModified() {
        return $this->isModified;
    }

    /**
     * Retourne le nombre d'éléments dans les données de de l'objet.
     *
     * @return int;
     *
     * @see Countable::count()
     */
    public function count() {
        return count($this->data);
    }

    /**
     * Indique si l'objet contient l'élément dont la clé est indiquée.
     *
     * @param scalar $key
     *
     * @return boolean
     */
    public function has($key) {
        return isset($this->data[$key]);
    }

    /**
     * Retourne un élément de l'objet.
     *
     * @param scalar $key La clé de l'élément à retourner.

     * @return mixed Les données de l'élément ou null si l'élément n'existe pas.
     */
    public function get($key) {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * Ajoute ou modifie un élément dans les données de l'objet.
     *
     * @param scalar $key La clé de l'élément à ajouter.
     * @param mixed $data Les données de l'élément à ajouter.
     *
     * @return self
     */
    public function set($key, $data) {
        if (! isset($this->data[$key]) || $this->data[$key] !== $data) {
            $this->data[$key] = $data;
            $this->isModified = true;
        }

        return $this;
    }

    /**
     * Supprime un élément ou toutes les données de l'objet.
     *
     * @param string $key Optionnel, la clé de l'élément à supprimer. Si aucune
     * clé n'est indiquée, toutes les données de l'objet sont supprimées.
     *
     * @return self
     */
    public function clear($key = null) {
        // Vider tout
        if (is_null($key)) {
            if (!empty($this->data)) {
                $this->data = [];
                $this->isModified = true;
            }

            return $this;
        }

        // Supprimer un élément
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
            $this->isModified = true;
        }

        return $this;
    }

    /**
     * Charge les données de l'objet.
     *
     * @throws Exception en cas d'erreur.
     *
     * @return self
     */
    protected function load() {
        // Essaie de charger le meta
        $data = get_user_meta($this->user, $this->id, true);

        // If the meta value does not exist and $single is true the function will
        // return an empty string. If $single is false an empty array is returned.
        // Mais dans la pratique, ça peut aussi retourner false (par exemple si
        // le $user indiqué n'existe pas). Du coup, on teste avec empty().

        // Décode le json
        $this->data = empty($data) ? [] : $this->unserialize($data);

        // Ok
        return $this;
    }

    /**
     * Enregistre les données de l'objet.
     *
     * @throws Exception En cas d'erreur.
     *
     * @return self
     */
    public function save() {
        // Rien à faire si l'objet n'a pas été modifié
        if (! $this->isModified) {
            return $this;
        }

        // Supprime le meta si les données sont vides
        if (empty($this->data)) {
            delete_user_meta($this->user, $this->id);
        }

        // Enregistre le meta sinon
        else {
            // Encode les données
            $data = $this->serialize();

            // Enregistre le meta
            if (false === update_user_meta($this->user, $this->id, $data)) {
                $msg = __('Unable to save user data %s', 'docalist-biblio');
                throw new Exception(sprintf($msg, $this->id));
            }
        }

        // Ok
        $this->isModified = false;

        return $this;
    }

    protected function serialize() {
        return json_encode($this->data(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    protected function unserialize($data) {
        // Décode le json
        $data = json_decode($data, true, 512, JSON_BIGINT_AS_STRING);

        // On doit obtenir un tableau (éventuellement vide), sinon erreur
        if (! is_array($data)) {
            $msg = __('JSON error while decoding user data %s: error %s', 'docalist-biblio');
            throw new Exception(sprintf($msg, $this->id, json_last_error()));
        }

        // Ok
        return $data;
    }
}