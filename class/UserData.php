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

use WP_User;
use InvalidArgumentException;

/**
 * Gestionnaire de données utilisateurs (paniers, recherches enregistrées, etc.)
 *
 */
class UserData {
    /**
     * Map type d'objet => classe
     *
     * @var array
     */
    static protected $classmap = [
        'basket' => 'Docalist\Biblio\UserData\Basket',
    ];

    /**
     * Liste des objets déjà créés, regroupés par type.
     *
     * @var array[]
     */
    protected $instances = [];

    /**
     * Retourne le nom de la classe utilisée pour gérer les objets d'un type
     * donné.
     *
     * @param string $type
     *
     * @return string
     *
     * @throws InvalidArgumentException Si le type indiqué n'est pas géré.
     */
    protected function className($type) {
        if (!isset(self::$classmap[$type])) {
            throw new InvalidArgumentException("Invalid user data type '$type'");
        }

        return self::$classmap[$type];
    }

    /**
     * Retourne l'ID de l'utilisateur.
     *
     * @param null|int|string|WP_User $user L'utilisateur recherché :
     * - aucun paramètre (ou null), l'utilisateur en cours.
     * - int : l'utilisateur ayant cet ID.
     * - string : l'utilisateur avec ce login.
     * - WP_User : l'objet passé.
     *
     * @return int L'ID de l'utilisateur s'il existe, 0 sinon.
     */
    public function userID($user = null) {
        if (is_null($user)) {
            $user = wp_get_current_user();
        } elseif (is_int($user)) {
            $user = get_user_by('ID', $user);
        } elseif (is_string($user)) {
            $user = get_user_by('login', $user);
        }

        if (! $user instanceof WP_User || ! $user->exists()) {
            throw new InvalidArgumentException('Invalid user');
        }

        return $user->ID;
    }

    /**
     * Retourne le nom des objets du type indiqué qui existent dans la base pour
     * l'utilisateur indiqué.
     *
     * @param string $type
     * @param int|string|WP_User L'utilsateur (null = utilisateur en cours).
     *
     * @return array Un tableau contenant le nom des objets (trié par ordre de
     * création).
     */
    public function all($type, $user = null) {
        global $wpdb;

        // Vérifie que le type indiqué existe
        $this->className($type);

        // Si l'utilisateur n'existe pas, il ne peut pas avoir de données
        if (0 === $user = $this->userID($user)) {
            return [];
        }

        // Construit la requête sql
        $id = "docalist-$type-%";
        $table = _get_meta_table('user');
        $sql = "SELECT SUBSTRING(meta_key, %d) FROM $table WHERE user_id=%d AND meta_key LIKE %s ORDER BY umeta_id";
        $sql = $wpdb->prepare($sql, strlen($id), $user, $id);

        // Exécute la requête et retourne le résultat
        return $wpdb->get_col($sql);
    }

    /**
     * Retourne la liste des paniers existants pour l'utilisateur indiqué.
     *
     * @param int|string|WP_User L'utilsateur (null = utilisateur en cours).
     *
     * @return array
     */
    public function baskets($user = null) {
        return $this->all('basket', $user);
    }

    /**
     * Retourne un objet en l'instanciant si nécessaire.
     *
     * @param string $type
     * @param string $name
     * @param int|string|WP_User L'utilsateur (null = utilisateur en cours).
     *
     * @return UserDataObject
     */
    protected function get($type, $name, $user = null) {
        if (!isset($this->instances[$type][$name])) {
            $class = $this->className($type);
            $user = $this->userID($user);
            $this->instances[$type][$name] = new $class($name, $user);
        }

        return $this->instances[$type][$name];
    }

    /**
     *
     * @param string $name
     * @param int|string|WP_User L'utilsateur (null = utilisateur en cours).
     *
     * @return Basket
     */
    public function basket($name, $user = null) {
        return $this->get('basket', $name, $user);
    }
}