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

use Docalist\Controller;
use Docalist\Search\SearchRequest;
use Docalist\Http\JsonResponse;
use WP_Query;
use WP_Post;

class BasketController extends Controller{
    /**
     * Action par défaut du contrôleur.
     *
     * @var string
     */
    protected $defaultAction = 'Dump';

    /**
     * Panier en cours
     *
     * @var Basket
     */
    protected $basket;

    /**
     * Les paramètres du plugin.
     *
     * @var Settings
     */
    protected $settings;

    /**
     * Indique si on est sur la page du panier.
     *
     * @var boolean
     */
    protected $isBasketPage = false;

    /**
     * Nombre de notices "baskettables" trouvées entre loop-start et loop-end
     * Utilisé pour déterminer s'il faut appeller enqueue_scripts.
     * Mis à jour par addClass()
     *
     * @var int
     */
    protected $count;

    /**
     * Construit le gestionnaire de panier.
     *
     * @param Settings $settings Paramètres du plugin.
     */
    public function __construct(Settings $settings) {
        parent::__construct('docalist-biblio-basket', 'admin-ajax.php');

        $this->settings = $settings;
        $this->basket = docalist('user-data')->basket(); /* @var $basket Basket */

        add_action('loop_start', function(WP_Query $query) {
            if ($query->is_main_query()) {
                $this->count = 0;
                add_filter('post_class' , [$this, 'addClass' ], 10);
                add_filter('the_excerpt', [$this, 'addToggle'], 99999);
                add_filter('the_content', [$this, 'addToggle'], 99999);
            }
        });

        add_action('loop_end', function(WP_Query $query) {
            if ($query->is_main_query()) {
                remove_filter('post_class' , [$this, 'addClass' ], 10);
                remove_filter('the_excerpt', [$this, 'addToggle'], 99999);
                remove_filter('the_content', [$this, 'addToggle'], 99999);
                if ($this->count) {
                    wp_enqueue_style('docalist-biblio-userdata-basket'); // debug only
                    wp_enqueue_script('docalist-biblio-userdata-basket');
                    $settings = [
                        'active' => $this->settings->htmlActive(),
                        'inactive' => $this->settings->htmlInactive(),
                        'url' => $this->baseUrl()
                    ];
                    wp_localize_script('docalist-biblio-userdata-basket', 'docalistBiblioUserdataBasketSettings', $settings);
                }
            }
        });

        add_filter('docalist_search_create_request', function(SearchRequest $request = null, WP_Query $query) {
            // Si on a une page spécifique pour le panier, teste si on est dessus
            if ($query->get_queried_object_id() === $this->settings->basketpage()) {
                $this->isBasketPage = true;
            }

            // Pas de page "panier", le panier est une recherche avec "?_basket"
            elseif (isset($request) && $request->isSearch() && isset($_REQUEST['_basket'])) {
                $this->isBasketPage = true;
            }

            if ($this->isBasketPage) {
                if (! $this->basket->isEmpty()) {
                    if (is_null($request)) {
                        $request = docalist('docalist-search-engine')->defaultRequest();
                    }
                    $request->searchPageUrl($this->basketPageUrl());

                    // cf. https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-ids-filter.html
                    $request->addHiddenFilter([
                        'ids' => ['values' => $this->basket->data()]
                    ]);

                    /*
                     * Remarque : la requête génèrée n'est pas optimale car on
                     * n'indique pas le type, seulement l'ID.
                     * Du coup, pour chaque ID, ES génère un "OR" pour tous les types :
                     * (_uid:prisme#id1 OR _uid:page#id1 _uid:post#id1) OR (idem pour id2) OR etc.
                     * Néanmoins, cela fonctionne car nos ID sont uniques.
                     * Le filtre ids accepte un argument "type", mais à notre stade, on
                     * ne connaît pas le type, seulement l'id.
                     * Comme ça fonctionne très bien comme ça, inutile de s'embêter.
                     */

                    // Indique à docalist-search qu'on veut afficher les réponses
                    $request->isSearch(true);
                }
                // else : le panier est vide, laisse wp afficher la page panier
            }

            return $request;
        }, 100, 2);
    }

    /**
     * Indique si on est sur la page du panier.
     *
     * @return boolean
     */
    public function isBasketPage() {
        return $this->isBasketPage;
    }

    /**
     * Retourne l'url de la page du panier.
     *
     * @return string
     */
    public function basketPageUrl() {
        if ($this->settings->basketpage()) {
            return get_the_permalink($this->settings->basketpage());
        }

        return get_permalink(docalist('docalist-search-engine')->searchPage());
    }

    /**
     * Indique si le post passé en paramètre peut être ajouté ou non au panier.
     *
     * @param WP_Post $post
     *
     * @return boolean
     */
    public function isBasketable(WP_Post $post) {
        static $databases = null;

        is_null($databases) && $databases = docalist('docalist-biblio')->databases();

        return isset($databases[$post->post_type]);
    }

    /**
     * Ajoute les classes CSS 'basket' et 'active' ou 'inactive' dans le tableau
     * passé en paramètre si le post en cours peut être ajouté au panier.
     *
     * @param array $classes
     *
     * @return string
     */
    public function addClass(array $classes) {
        global $post;

        if ($this->isBasketable($post)) {
            ++$this->count;
            $classes[] = 'basket';
            $classes[] = $this->basket->has($post->ID) ? 'basket-active' : 'basket-inactive';
        }

        return $classes;
    }

    /**
     * Ajoute le lien permettant de sélectionner ou de déselectionner la notice
     * dans le contenu passé en paramètre si le post en cours peut être ajouté
     * au panier.
     *
     * @param string $content
     *
     * @return string
     */
    public function addToggle($content) {
        global $post;

        if ($this->isBasketable($post)) {
            $html = $this->basket->has($post->ID) ? $this->settings->htmlActive() : $this->settings->htmlInactive();
            $content = $this->settings->linksBeforeContent() ? ($html . $content) : ($content . $html);
        }

        return $content;
    }

    protected function register() {
        if ($this->canRun()) {
            $callback = function() {
                $this->run()->send();
                exit();
            };
            add_action('wp_ajax_' . $this->id(), $callback);
            add_action('wp_ajax_nopriv_' . $this->id(), $callback);
        }
    }

    /**
     * Normalise une chaine contenant des numéros de référence séparés par une
     * virgule.
     *
     * @param string $refs Une chaine de la forme "10,12,15"
     *
     * @return int|int[]
     */
    protected function refs($refs) {
        is_string($refs) && $refs = array_map('trim', explode(',', $refs));

        return $refs;
    }

    /**
     * Ajoute des notices au panier.
     *
     * @param string $refs
     *
     * @return JsonResponse
     */
    public function actionAdd($refs) {
        $nb = $this->basket->count();
        $refs = $this->refs($refs);
        $this->basket->add($refs)->save();
        $count = $this->basket->count();
        $nb = $count - $nb;

        $result = [];
        foreach($refs as $ref) {
            $result[$ref] = $this->basket->has($ref);
        }
        return $this->json(['action' => $this->action(), 'nb' => $nb, 'count' => $count, 'result' => $result]);
    }

    /**
     * Enlève des notices du panier.
     *
     * @param string $refs
     *
     * @return JsonResponse
     */
    public function actionRemove($refs) {
        $nb = $this->basket->count();
        $refs = $this->refs($refs);
        $this->basket->remove($refs)->save();
        $count = $this->basket->count();
        $nb -= $count;

        $result = [];
        foreach($refs as $ref) {
            $result[$ref] = $this->basket->has($ref);
        }
        return $this->json(['action' => $this->action(), 'nb' => $nb, 'count' => $count, 'result' => $result]);
    }

    /**
     * Vide le panier.
     *
     * @return JsonResponse
     */
    public function actionClear() {
        $nb = $this->basket->count();
        $this->basket->clear()->save();

        return $this->json(['action' => $this->action(), 'nb' => $nb, 'count' => $this->basket->count()]);
    }

    /**
     * Affiche le contenu du panier.
     *
     * @return JsonResponse
     */
    public function actionDump() {
        return $this->json(['action' => $this->action(), 'count' => $this->basket->count(), 'refs' => $this->basket->data()]);
    }
}