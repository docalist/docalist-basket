<?php declare(strict_types=1);
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Basket;

use Docalist\Basket\Storage;
use IteratorAggregate;
use ArrayIterator;
use Countable;
use InvalidArgumentException;

/**
 * Un panier de notices.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Basket implements Countable, IteratorAggregate
{
    /**
     * Le numéro du panier.
     *
     * @var int
     */
    private $basket;

    /**
     * Le dépôt dans lequel est stocké le panier.
     *
     * @var Storage
     */
    private $storage;

    /**
     * Le nombre maximum de notices que le panier peut contenir.
     *
     * @var int
     */
    private $basketCapacity;

    /**
     * Les données du panier.
     *
     * Les ID des notices sont stockées dans les clés du tableau pour qu'on puisse utiliser isset().
     * Les valeurs ne sont pas utilisées (toujours 1).
     *
     * @var int[]
     */
    private $data;

    /**
     * Initialise le panier.
     *
     * @param int       $basket         Le numéro du panier.
     * @param Storage   $storage        Le dépôt dans lequel est stocké le panier.
     * @param int       $basketCapacity Le nombre maximum de notices que le panier peut contenir.
     *
     * @throws InvalidArgumentException Si la capacité indiquée n'est pas valide (inférieure à 1).
     */
    public function __construct(int $basket, Storage $storage, int $basketCapacity = 5678)
    {
        //wp_rand(); wp_generate_password(); random_int($min, $max); random_bytes($bytes);

        $this->basket = $basket;
        $this->storage = $storage;
        if ($basketCapacity < 1) {
            throw new InvalidArgumentException('basketCapacity must be greater than 0');
        }
        $this->basketCapacity = $basketCapacity;

        $this->load();
    }

    /**
     * Charge le panier
     */
    private function load(): void
    {
        $this->data = array_fill_keys($this->storage->loadBasketData($this->basket), 1);
    }

    /**
     * Enregistre le panier.
     */
    private function save(): void
    {
        $this->storage->saveBasketData($this->basket, array_keys($this->data));
    }

    /**
     * Ajoute une ou plusieurs notices au panier.
     *
     * La méthode ajoute les notices au panier dans l'ordre où elles sont fournies. Les notices qui figurent déjà
     * dans le panier sont ignorées. Si le panier est plein, les notices qui suivent sont ignorées.
     *
     * @param int ...$postID La liste des ID des notices à ajouter au panier.
     *
     * @return int[] La liste des ID des notices effectivement ajoutées au panier (i.e. les notices fournies en
     * paramètres moins celles qui figuraient déjà dans le panier ou qui n'ont pas pu être ajoutées parce que le panier
     * était plein.
     */
    public function add(int ...$postID): array
    {
        // Ajoute toutes les notices indiquées
        $result = [];
        foreach ($postID as $postID) {
            // Si le numéro de notice n'est pas valide, on ignore
            if ($postID < 1) {
                continue;
            }

            // Si la notice est déjà dans le panier, on ignore
            if (isset($this->data[$postID])) {
                continue;
            }

            // Si le panier est plein, on s'arrête
            if ($this->isFull()) {
                break;
            }

            // Ajoute la notice au panier
            $this->data[$postID] = 1;

            // Stocke la liste des notices effectivement ajoutées
            $result[] = $postID;
        }

        // Enregistre le panier s'il a été modifié
        !empty($result) && $this->save();

        // Retourne la liste des ID ajoutés au panier
        return $result;
    }

    /**
     * Supprime une ou plusieurs notices du panier.
     *
     * La méthode supprime les notices au panier dans l'ordre où elles sont fournies. Les notices qui ne figurent pas
     * dans le panier sont ignorées.
     *
     * @param int ...$postID La liste des ID des notices à enlever du panier.
     *
     * @return int[] La liste des ID des notices effectivement supprimées du panier (i.e. les notices fournies en
     * paramètres moins celles qui ne figuraient pas dans le panier).
     */
    public function remove(int ...$postID): array
    {
        // Supprime les notices indiquées
        $result = [];
        foreach ($postID as $postID) {
            // Si le numéro de notice n'est pas valide, on ignore
            if ($postID < 1) {
                continue;
            }

            // Si la notice n'est pas dans le panier, on ignore
            if (! isset($this->data[$postID])) {
                continue;
            }

            // Supprime la notice du panier
            unset($this->data[$postID]);

            // Stocke la liste des notices effectivement supprimées
            $result[] = $postID;
        }

        // Enregistre le panier s'il a été modifié
        !empty($result) && $this->save();

        // Retourne la liste des ID supprimés du panier
        return $result;
    }

    /**
     * Vide le panier.
     *
     * @return int[] La liste des ID des notices qui figuraient dans le panier.
     */
    public function clear()
    {
        // SI le panier est déjà vide, rien à faire
        if (empty($this->data)) {
            return [];
        }

        // Stocke les notices qu'on avait et vide le panier
        $result = $this->getContents();
        $this->data = [];
        $this->save();

        // Retourne l'ancien contenu du panier
        return $result;
    }

    /**
     * Indique si le panier contient la notice indiquée.
     *
     * @param int|int[] $postID
     *
     * @return bool
     */
    public function has(int $postID): bool
    {
        return isset($this->data[$postID]);
    }

    /**
     * Retourne le nombre de notices dans le panier.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Retourne le contenu du panier.
     *
     * @return int[] Les post_id des notices qui figurent dans le panier.
     */
    public function getContents(): array
    {
        return array_keys($this->data);
    }

    /**
     * Retourne un itérateur permettant d'utiliser l'objet Basket dans une boucle foreach.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->getContents());
    }

    /**
     * Retourne la contenance du panier.
     *
     * @return int Le nombre maximum de notices que le panier peut contenir.
     */
    public function getCapacity(): int
    {
        return $this->basketCapacity;
    }

    public function isFull(): bool
    {
        return $this->count() >= $this->getCapacity();
    }
}
