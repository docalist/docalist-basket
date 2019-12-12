<?php
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Basket;

use Docalist\Basket\Storage;
use Docalist\Basket\Basket;
use IteratorAggregate;
use ArrayIterator;
use Countable;
use InvalidArgumentException;

/**
 * Gère la liste des paniers d'un utilisateur.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Baskets implements Countable, IteratorAggregate
{
    /**
     * Numéro du panier par défaut.
     *
     * @var int
     */
    const DEFAULT = 1;

    /**
     * Nom de la clé utilisée en interne pour stocker le numéro du panier en cours.
     */
    private const CURRENT = 'current';

    /**
     * Le dépôt dans lequel sont stockés les paniers de l'utilisateur.
     *
     * @var Storage
     */
    private $storage;

    /**
     * Nombre maximum de paniers autorisés.
     *
     * @var int
     */
    private $maxBaskets;

    /**
     * Nombre maximum de notices par panier.
     *
     * @var int
     */
    private $basketCapacity;

    /**
     * La liste des paniers de l'utilisateur.
     *
     * @var string[] Un tableau de la forme numéro du panier => nom du panier
     */
    private $list = [];

    /**
     * Le numéro du panier en cours.
     *
     * @var int
     */
    private $current = self::DEFAULT;

    /**
     * La liste des paniers instanciés.
     *
     * @var Basket[]
     */
    private $baskets = [];

    /**
     * Initialise la liste des paniers de l'utilisateur.
     *
     * @param Storage   $storage        Le dépôt dans lequel sont stockés les paniers.
     * @param int       $maxBaskets     Nombre maximum de paniers autorisés.
     * @param int       $basketCapacity Nombre maximum de notices par panier.
     */
    public function __construct(Storage $storage, int $maxBaskets = 1, int $basketCapacity = 1234)
    {
        // Stocke le dépôt
        $this->storage = $storage;

        // Stocke le nombre maximum de paniers
        if ($maxBaskets < 1) {
            throw new InvalidArgumentException('maxBaskets must be greater than 0');
        }
        $this->maxBaskets = $maxBaskets;

        // Stocke la capacité des paniers
        if ($basketCapacity < 1) {
            throw new InvalidArgumentException('basketCapacity must be greater than 0');
        }
        $this->basketCapacity = $basketCapacity;

        // Charge la liste des paniers
        $this->load();
    }

    /**
     * Retourne le nombre maximum de paniers autorisés.
     *
     * @return int
     */
    public function getMaxBaskets(): int
    {
        return $this->maxBaskets;
    }

    /**
     * Retourne le nombre maximum de notices par panier.
     *
     * @return int
     */
    public function getBasketCapacity(): int
    {
        return $this->basketCapacity;
    }

    /**
     * Charge la liste des paniers.
     */
    private function load(): void
    {
        $this->list = $this->storage->loadBasketList();
        if (isset($this->list[self::CURRENT])) {
            $this->current = $this->list[self::CURRENT];
            unset($this->list[self::CURRENT]);
        }
        if (empty($this->list)) {
            $this->list = [
                self::DEFAULT => $this->defaultBasketName()
            ];
        }
    }

    /**
     * Enregistre la liste des paniers.
     */
    private function save(): void
    {
        $list = $this->list;
        if ($this->current !== self::DEFAULT) {
            $list[self::CURRENT] = $this->current;
        }
        $this->storage->saveBasketList($list);
    }

    /**
     * Retourne la liste des paniers de l'utilisateur.
     *
     * @return string[] Un tableau de la forme numéro du panier => nom du panier.
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * Retourne le nombre de paniers dont dispose l'utilisateur.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->getList());
    }

    /**
     * Retourne un itérateur permettant d'utiliser l'objet Baskets dans une boucle foreach.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->getList());
    }

    /**
     * Teste si le panier indiqué existe.
     *
     * @param int $basket
     *
     * @return bool
     */
    public function hasBasket(int $basket = self::DEFAULT): bool
    {
        return array_key_exists($basket, $this->list);
    }

    /**
     * Retourne le panier qui a le numéro indiqué.
     *
     * @param int|null $basket Optionnel, numéro du panier à retourner (le panier en cours par défaut).
     *
     * @return Basket La méthode retourne toujours la même instance.
     *
     * @throws InvalidArgumentException si le panier indiqué n'existe pas.
     */
    public function getBasket(int $basket = null): Basket
    {
        // Utilise le panier en cours si le numéro de panier n'a pas été indiqué
        is_null($basket) && $basket = $this->getCurrentBasket();

        // Instancie le panier demandé si ce n'est pas déjà fait
        if (!isset($this->baskets[$basket])) {
            $this->checkBasket($basket);
            $this->baskets[$basket] = new Basket($basket, $this->storage, $this->getBasketCapacity());
        }

        // TODO : passe capacity à Basket

        // Retourne l'objet Basket
        return $this->baskets[$basket];
    }

    /**
     * Crée un nouveau panier.
     *
     * @param string $name Nom du panier.
     *
     * @return int Le numéro du panier créé.
     */
    public function createBasket(string $name = ''): int
    {
        // TODO : tester max_baskets

        // Ajoute le panier à la fin de la liste
        $this->list[] = $name;

        // Détermine le numéro qui lui a été attribué
        end($this->list);
        $basket = key($this->list);

        // Si aucun nom n'a été indiqué, attribue un nom par défaut au panier
        if (empty($name)) {
            $this->list[$basket] = $this->defaultBasketName($basket);
        }

        // Stocke la liste
        $this->save();

        // Retourne le numéro du panier créé
        return $basket;
    }

    /**
     * Supprime un panier.
     *
     * @param int $basket Numéro du panier à supprimer.
     *
     * @throws InvalidArgumentException si le panier indiqué n'existe pas.
     */
    public function removeBasket(int $basket = self::DEFAULT)
    {
        // Vérifie que le panier indiqué existe
        $this->checkBasket($basket);

        // Vide le panier
        $this->getBasket($basket)->clear();
        unset($this->baskets[$basket]);

        // Enlève le panier de la liste (sauf si c'est le panier par défaut qui ne peut pas être supprimé)
        if ($basket !== self::DEFAULT) {
            unset($this->list[$basket]);
        }

        // Stocke la liste
        $this->save();
    }

    /**
     * Renomme un panier.
     *
     * @param int       $basket     Numéro du panier à renommer.
     * @param string    $newName    Nouveau nom du panier.
     *
     * @throws InvalidArgumentException si le panier indiqué n'existe pas.
     */
    public function renameBasket(int $basket, string $newName): void
    {
        // Vérifie que le panier indiqué existe
        $this->checkBasket($basket);

        // Renomme le panier
        $this->list[$basket] = $newName;

        // Stocke la liste
        $this->save();
    }

    /**
     * Retourne le numéro du panier en cours.
     *
     * @eturn int
     */
    public function getCurrentBasket(): int
    {
        return $this->current;
    }

    /**
     * Modifie le panier en cours.
     *
     * @param int $basket Le numéro du nouveau panier en cours.
     *
     * @throws InvalidArgumentException si le panier indiqué n'existe pas.
     */
    public function setCurrentBasket(int $basket): void
    {
        $this->checkBasket($basket);
        $this->current = $basket;
        $this->save();
    }

    /**
     * Détermine le nom par défaut d'un panier.
     *
     * @param int $basket Numéro du panier.
     *
     * @return string
     */
    public static function defaultBasketName(int $basket = self::DEFAULT): string
    {
        $name = ($basket === self::DEFAULT)
            ? __('Ma sélection', 'docalist-basket')
            : sprintf(__('Panier %s', 'docalist-basket'), $basket);

        return apply_filters('docalist_basket_get_default_basket_name', $name, $basket);
    }

    /**
     * Génère une exception si la panier indiqué n'existe pas.
     *
     * @param int $basket
     *
     * @throws InvalidArgumentException si le panier indiqué n'existe pas.
     */
    private function checkBasket(int $basket): void
    {
        if (! $this->hasBasket($basket)) {
            throw new InvalidArgumentException('Basket ' . $basket . ' does not exist');
        }
    }
}
