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

use WP_Widget;
use Docalist\Forms\Fragment;
use Docalist\Forms\Themes;
use Docalist\Utils;

class BasketWidget extends WP_Widget {
    public function __construct() {
        $id = 'docalist-biblio-basket';
        parent::__construct(
            // Base ID. Inutile de préfixer avec "widget", WordPress le fait
            $id,

            // Titre (nom) du widget affiché en back office
            __('Panier de notices', 'docalist-biblio'),

            // Args
            [
                'description' => __('Panier de notices', 'docalist-biblio'),
                'classname' => $id, // par défaut, WordPress met 'widget_'.$id
            ]
        );
    }

    /**
     * Affiche le widget.
     *
     * @param array $context Les paramètres d'affichage du widget. Il s'agit
     * des paramètres définis par le thème lors de l'appel à la fonction
     * WordPress.
     *
     * Le tableau passé en paramètre inclut notamment les clés :
     * - before_widget : code html à afficher avant le widget.
     * - after_widget : texte html à affiche après le widget.
     * - before_title : code html à générer avant le titre (exemple : '<h2>')
     * - after_title  : code html à générer après le titre (exemple : '</h2>')
     *
     * @param array $settings Les paramètres du widget que l'administrateur
     * a saisi dans le formulaire de paramétrage (cf. {createSettingsForm()}).
     *
     * @see http://codex.wordpress.org/Function_Reference/register_sidebar
     */
    public function widget($context, $settings) {
        // Seuls les utilisateurs loggués peuvent avoir un panier
        if (! is_user_logged_in()) {
            return;
        }

        // TODO: à étudier, avec le widget customizer, on peut être appellé avec
        // des settings vides. Se produit quand on ajoute un nouveau widget dans
        // une sidebar, tant qu'on ne modifie aucun paramètre. Dès qu'on modifie
        // l'un des paramètres du widget, celui-ci est correctement enregistré
        // et dès lors on a les settings.
        $settings += $this->defaultSettings();

        // Charge le panier
        $basket = docalist('user-data')->basket('default'); /*@var $basket Basket */
        $count = $basket->count();

        // Début du widget
        echo $context['before_widget'];

        // Titre du widget
        $title = apply_filters('widget_title', $settings['title'], $settings, $this->id_base);
        $title = strtr($title, ['%d' => '<span class="basket-count">' . $count . '</span>']);
        if ($title) {
            echo $context['before_title'], $title, $context['after_title'];
        }

        // Début des liens
        $link = '<li class="%s" style="%s" title="%s"><a href="#">%s</a></li>';
        echo '<ul>';

        // Lien "Afficher le panier"
        $label = strtr($settings['show'], ['%d' => '<span class="basket-count">' . $count . '</span>']);
        $label && printf($link,
            'basket-show',
            $count ? '' : 'display:none',
            __('Lance une nouvelle recherche pour afficher la sélection et vous permettre de l\'exploiter.', 'docalist-biblio'),
            $label
        );

        // Lien "Ajouter les notices de la page'
        $label = strtr($settings['addpage'], ['%d' => '<span class="basket-addpage-count"></span>']);
        $label && printf($link,
            'basket-addpage',
            'display:none',
            __('Ajoute à la sélection toutes les notices de la page en cours qui ne sont pas encore sélectionnées.', 'docalist-biblio'),
            $label
        );

        // Lien "Supprimer les notices de la page'
        $label = strtr($settings['removepage'], ['%d' => '<span class="basket-removepage-count"></span>']);
        $label && printf($link,
            'basket-removepage',
            'display:none',
            __('Enlève de la sélection toutes les notices de la page en cours qui sont actuellement sélectionnées.', 'docalist-biblio'),
            $label
        );


        // Lien "Vider le panier"
        $label = strtr($settings['clear'], ['%d' => '<span class="basket-count">' . $count . '</span>']);
        $label && printf($link,
            'basket-clear',
            $count ? '' : 'display:none',
            __('Vide la sélection et enlève toutes les notices actuellement sélectionnées.', 'docalist-biblio'),
            $label
        );

        // Fin des liens
        echo '</ul>';

        // Fin du widget
        echo $context['after_widget'];
    }

    /**
     * Crée le formulaire permettant de paramètrer le widget.
     *
     * @return Fragment
     */
    protected function settingsForm() {
        $form = new Fragment();

        $form->tag('a')
            ->attribute('class', 'dashicons dashicons-editor-help alignright')
            ->attribute('onclick', 'jQuery(".basket-help").toggle("slow");return false;');

        $help = __('
            Les zones suivantes vous permettent de paramétrer le titre et les libellés des liens affichés dans le widget panier.
            Vous pouvez utiliser <code>%d</code> pour indiquer le nombre de notices ajoutées/supprimées/présentes dans la sélection.
            Pour les liens, si vous n\'indiquez aucun libellé, le lien correspondant ne sera pas affiché dans le widget.
            ', 'docalist-biblio');
        $form->tag('p.basket-help', $help)->attribute('style', 'display: none');

        $form->input('title')
            ->attribute('id', $this->get_field_id('title')) // pour que le widget affiche le bon titre en backoffice. cf widgets.dev.js, fonction appendTitle(), L250
            ->label(__('<b>Titre du widget</b>', 'docalist-biblio'))
            ->addClass('widefat');

        $form->input('show')
            ->label(__('<b>Afficher la sélection</b>', 'docalist-biblio'))
            ->addClass('widefat');

        $form->input('addpage')
            ->label(__('<b>Ajouter les notices de la page</b>', 'docalist-biblio'))
            ->addClass('widefat');

        $form->input('removepage')
            ->label(__('<b>Enlever les notices de la page</b>', 'docalist-biblio'))
            ->addClass('widefat');

        $form->input('clear')
            ->label(__('<b>Vider la sélection</b>', 'docalist-biblio'))
            ->addClass('widefat');


        return $form;
    }

    /**
     * Retourne les paramètres par défaut du widget.
     *
     * @return array
     */
    protected function defaultSettings() {
        return [
            'title'      => __('Panier (%d)', 'docalist-biblio'),
            'show'       => __('Afficher', 'docalist-biblio'),
            'addpage'    => __('Ajouter tout (%d)', 'docalist-biblio'),
            'removepage' => __('Enlever la page (%d)', 'docalist-biblio'),
            'clear'      => __('Vider', 'docalist-biblio'),
        ];
    }

    /**
     * Affiche le formulaire qui permet de paramètrer le widget.
     *
     * @see WP_Widget::form()
     */
    public function form($instance) {
        // Récupère le formulaire à afficher
        $form = $this->settingsForm();

        // Lie le formulaire aux paramètres du widget
        $form->bind($instance ?: $this->defaultSettings());

        // Dans WordPress, les widget ont un ID et sont multi-instances. Le
        // formulaire doit donc avoir le même nom que le widget.
        // Par ailleurs, l'API Widgets de WordPress attend des noms
        // de champ de la forme "widget-id_base-[number][champ]". Pour générer
        // cela facilement, on donne directement le bon nom au formulaire.
        // Pour que les facettes soient orrectement clonées, le champ facets
        // définit explicitement repeatLevel=2 (cf. settingsForm)
        $name = 'widget-' . $this->id_base . '[' . $this->number . ']';
        $form->name($name);

        // Envoie les assets requis par ce formulaire
        // Comme le début de la page a déjà été envoyé, les assets vont
        // être ajoutés en fin de page. On n'a pas de FOUC car le formulaire
        // ne sera affiché que lorsque l'utilisateur le demandera.
        $theme = 'base';
        Utils::enqueueAssets(Themes::assets($theme)->add($form->assets()));

        // Affiche le formulaire
        $form->render($theme);
    }

    /**
     * Enregistre les paramètres du widget.
     *
     * La méthode vérifie que les nouveaux paramètres sont valides et retourne
     * la version corrigée.
     *
     * @param array $new les nouveaux paramètres du widget.
     * @param array $old les anciens paramètres du widget
     *
     * @return array La version corrigée des paramètres.
     */
    public function update($new, $old) {
        $settings = $this->settingsForm()->bind($new)->data();

        // TODO validation

        return $settings;
    }
}