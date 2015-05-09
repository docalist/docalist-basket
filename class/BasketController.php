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
     * Actiob par défaut du contrôleur.
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
     * Settings du panier.
     *
     * @var array
     */
    protected $settings = [
        'active' => '<p class="basket-remove">- <a href="#">Désélectionner</a></p>',
        'inactive' => '<p class="basket-add">+ <a href="#">Sélectionner</a></p>',
        'before-content' => false,
    ];

    /**
     * Nombre de notices "baskettables" trouvées entre loop-start et loop-end
     * Utilisé pour déterminer s'il faut appeller enqueue_scripts.
     * Mis à jour par addClass()
     *
     * @var int
     */
    protected $count;

    public function __construct() {
        parent::__construct('docalist-biblio-basket', 'admin-ajax.php');

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
                        'active' => $this->settings['active'],
                        'inactive' => $this->settings['inactive'],
                        'url' => $this->baseUrl()
                    ];
                    wp_localize_script('docalist-biblio-userdata-basket', 'docalistBiblioUserdataBasketSettings', $settings);
                }
            }
        });

        add_filter('docalist_search_create_request', function(SearchRequest $request = null) {
            if (isset($request) && $request->isSearch() && isset($_REQUEST['_basket'])) {
                $request->idsFilter($this->basket->data());
            }

            return $request;
        }, 100);
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
            $html = $this->settings[$this->basket->has($post->ID) ? 'active' : 'inactive'];
            $content = $this->settings['before-content'] ? ($html . $content) : ($content . $html);
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