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

namespace Docalist\Basket\Service;

use Docalist\Controller;
use Docalist\Basket\Service\BasketService;
use Docalist\Http\JsonResponse;

/**
 * API ajax pour le panier.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class AjaxController extends Controller
{
    /**
     * Message généré quand l'utilisateur n'a pas les droits suffisants pour accéder à l'API du panier.
     *
     * @var string
     */
    const FORBIDDEN_MESSAGE = 'You do not have sufficient permissions to access the Docalist Basket API';

    /**
     * Définit les droits requis pour exécuter les actions de ce contrôleur.
     *
     * Le tableau est de la forme "nom de l'action" => "capacité requise".
     */
    protected $capability = [
        'default' => 'read',
    ];

    /**
     * Le service basket utilisé.
     *
     * @var BasketService
     */
    protected $basketService;

    /**
     * Construit le gestionnaire de panier.
     *
     * @param BasketService $basketService Le service basket à utiliser.
     */
    public function __construct(BasketService $basketService)
    {
        $this->basketService = $basketService;
        parent::__construct('docalist-basket', 'admin-ajax.php');
    }

    protected function register(): void
    {
        if ($this->canRun()) {
            $callback = function (): void {
                $this->run()->send();
                exit();
            };
            add_action('wp_ajax_' . $this->getID(), $callback);
            add_action('wp_ajax_nopriv_' . $this->getID(), $callback);
        }
    }

    protected function getDefaultAction(): string
    {
        return 'Dump';
    }

    /**
     * Ajoute une ou plusieurs notices dans le panier en cours de l'utilisateur.
     *
     * @param string $postID Une chaine de caractères contenant les ID des notices à ajouter au panier, séparées
     * par une vigule (par exemple "10,20,30").
     *
     * @return JsonResponse Retourne une réponse JSON de la forme suivante :
     *
     * status: 200 OK
     * <code>
     * {
     *     "action": "add",         // L'opération exécutée
     *     "result": [10,20,30],    // La liste des ID des notices qui ont été effectivement ajoutées au panier
     *     "count": 12,             // Le nombre de notices dans le panier après l'opération
     *     "full": false            // Indique si le panier est plein à l'issue de l'opération
     * }
     * </code>
     *
     * Si l'utilisateur en cours n'a pas les droits suffisants, la méthode retourne une erreur 403 forbidden.
     */
    public function actionAdd(string $postID): JsonResponse
    {
        // Récupère le panier de l'utilisateur, génère une erreur s'il n'a pas les droits suffisants
        if (is_null($basket = $this->basketService->getBasket())) {
            return $this->forbidden();
        }

        // Ajoute les notices indiquées
        $result = $basket->add(...$this->normalizePostID($postID));

        // Retourne le résultat
        return $this->json([
            'action' => 'add',
            'result' => $result,
            'count' => $basket->count(),
            'full' => $basket->isFull(),
        ]);
    }

    /**
     * Supprime une ou plusieurs notices dans le panier en cours de l'utilisateur.
     *
     * @param string $postID Une chaine de caractères contenant les ID des notices à supprimer du panier, séparées
     * par une vigule (par exemple "10,20,30").
     *
     * @return JsonResponse Retourne une réponse JSON de la forme suivante :
     *
     * <code>
     * status: 200 OK
     * {
     *     "action": "remove",      // L'opération exécutée
     *     "result": [10,20,30],    // La liste des ID des notices qui ont été effectivement supprimées du panier
     *     "count": 9,              // Le nombre de notices dans le panier après l'opération
     *     "full": false            // Indique si le panier est plein à l'issue de l'opération
     * }
     * </code>
     *
     * Si l'utilisateur en cours n'a pas les droits suffisants, la méthode retourne une erreur 403 forbidden.
     */
    public function actionRemove(string $postID): JsonResponse
    {
        // Récupère le panier de l'utilisateur, génère une erreur s'il n'a pas les droits suffisants
        if (is_null($basket = $this->basketService->getBasket())) {
            return $this->forbidden();
        }

        // Supprime les notices indiquées du panier
        $result = $basket->remove(...$this->normalizePostID($postID));

        // Retourne le résultat
        return $this->json([
            'action' => 'remove',
            'result' => $result,
            'count' => $basket->count(),
            'full' => $basket->isFull(),
        ]);
    }

    /**
     * Vide le panier en cours de l'utilisateur.
     *
     * @return JsonResponse Retourne une réponse JSON de la forme suivante :
     *
     * <code>
     * status: 200 OK
     * {
     *     "action": "clear",       // L'opération exécutée
     *     "result": [10,20,30],    // La liste des ID des notices qui étaient dans le panier
     *     "count": 0,              // Le nombre de notices dans le panier après l'opération
     *     "full": false            // Indique si le panier est plein à l'issue de l'opération
     * }
     * </code>
     *
     * Si l'utilisateur en cours n'a pas les droits suffisants, la méthode retourne une erreur 403 forbidden.
     */
    public function actionClear(): JsonResponse
    {
        // Récupère le panier de l'utilisateur, génère une erreur s'il n'a pas les droits suffisants
        if (is_null($basket = $this->basketService->getBasket())) {
            return $this->forbidden();
        }

        // Vide le panier
        $result = $basket->clear();

        // Retourne le résultat
        return $this->json([
            'action' => 'clear',
            'result' => $result,
            'count' => $basket->count(),
            'full' => $basket->isFull(),
        ]);
    }

    /**
     * Affiche le contenu du panier.
     *
     * @return JsonResponse Retourne une réponse JSON de la forme suivante :
     *
     * <code>
     * status: 200 OK
     * {
     *     "action": "dump",        // L'opération exécutée
     *     "result": [10,20,30],    // Les ID des notices du panier
     *     "count": 3,              // Le nombre de notices dans le panier
     *     "full": false            // Indique si le panier est plein
     * }
     * </code>
     *
     * Si l'utilisateur en cours n'a pas les droits suffisants, la méthode retourne une erreur 403 forbidden.
     */
    public function actionDump(): JsonResponse
    {
        // Récupère le panier de l'utilisateur, génère une erreur s'il n'a pas les droits suffisants
        if (is_null($basket = $this->basketService->getBasket())) {
            return $this->forbidden();
        }

        // Retourne le résultat
        return $this->json([
            'action' => 'dump',
            'result' => $basket->getContents(),
            'count' => $basket->count(),
            'full' => $basket->isFull(),
        ]);
    }

    // list : retourne la liste des paniers
    // create : crée un panier
    // delete : supprime un panier
    // rename : renomme un panier
    // select : change le panier actif

    /**
     * Normalise une chaine contenant des numéros de référence séparés par une virgule.
     *
     * @param string $postID Une chaine de la forme "10,12,15"
     *
     * @return int[] Un tableau d'entiers contenant les ID indiqués.
     */
    protected function normalizePostID(string $postID): array
    {
        $postID = trim($postID);

        return empty($postID) ? [] : array_map('intval', explode(',', $postID));
    }

    /**
     * Génère une réponse JSON "Accès non autorisé".
     *
     * @return JsonResponse Retourne une réponse JSON de la forme suivante :
     *
     * <code>
     * status: 403 Forbidden
     * "Accès non autorisé"
     * </code>
     */
    private function forbidden(): JsonResponse
    {
        return $this->json(self::FORBIDDEN_MESSAGE, 403);
    }
}
