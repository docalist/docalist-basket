<?php declare(strict_types=1);
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Basket\Widget;

use WP_Widget;
use Docalist\Forms\Container;

/**
 * Widget panier docalist.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class BasketWidget extends WP_Widget
{
    public function __construct()
    {
        $id = 'docalist-basket';
        parent::__construct(
            // Base ID. Inutile de préfixer avec "widget", WordPress le fait
            $id,

            // Titre (nom) du widget affiché en back office
            __('Panier Docalist', 'docalist-basket'),

            // Args
            [
                'description' => __('Panier Docalist', 'docalist-basket'),
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
    public function widget($context, $settings)
    {
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
        $basket = docalist('user-data')->basket(); /** @var Basket $basket */
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
        $link = '<li class="%s" style="%s" title="%s"><a href="%s">%s</a></li>';
        echo '<ul>';

        // Détermine l'url de la page "afficher le panier
        $isBasketPage = docalist('basket-controller')->isBasketPage();

        // Lien "Afficher le panier"
        $label = strtr($settings['show'], ['%d' => '<span class="basket-count">' . $count . '</span>']);
        $label && printf($link,
            'basket-show',
            ($count && !$isBasketPage) ? '' : 'display:none',
            __('Affiche les notices sélectionnées.', 'docalist-basket'),
            esc_url(docalist('basket-controller')->basketPageUrl()),
            $label
        );

        // Lien "Ajouter les notices de la page'
        $label = strtr($settings['addpage'], ['%d' => '<span class="basket-addpage-count"></span>']);
        $label && printf($link,
            'basket-addpage',
            'display:none',
            __('Ajoute à la sélection toutes les notices de la page en cours qui ne sont pas encore sélectionnées.', 'docalist-basket'),
            '#',
            $label
        );

        // Lien "Supprimer les notices de la page'
        $label = strtr($settings['removepage'], ['%d' => '<span class="basket-removepage-count"></span>']);
        $label && printf($link,
            'basket-removepage',
            'display:none',
            __('Enlève de la sélection toutes les notices de la page en cours qui sont actuellement sélectionnées.', 'docalist-basket'),
            '#',
            $label
        );


        // Lien "Vider le panier"
        $label = strtr($settings['clear'], ['%d' => '<span class="basket-count">' . $count . '</span>']);
        $label && printf($link,
            'basket-clear',
            $count ? '' : 'display:none',
            __('Vide la sélection et enlève toutes les notices actuellement sélectionnées.', 'docalist-basket'),
            '#',
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
    protected function settingsForm()
    {
        $form = new Container();

        $form->tag('a')
            ->addClass('dashicons dashicons-editor-help alignright')
            ->setAttribute('onclick', 'jQuery(".basket-help").toggle("slow");return false;');

        $help = __('
            Les zones suivantes vous permettent de paramétrer le titre et les libellés des liens affichés dans le widget panier.
            Vous pouvez utiliser <code>%d</code> pour indiquer le nombre de notices ajoutées/supprimées/présentes dans la sélection.
            Pour les liens, si vous n\'indiquez aucun libellé, le lien correspondant ne sera pas affiché dans le widget.
            ', 'docalist-basket');
        $form->tag('p.basket-help', $help)->setAttribute('style', 'display: none');

        $form->input('title')
            ->setAttribute('id', $this->get_field_id('title')) // pour que le widget affiche le bon titre en backoffice. cf widgets.dev.js, fonction appendTitle(), L250
            ->setLabel(__('<b>Titre du widget</b>', 'docalist-basket'))
            ->addClass('widefat');

        $form->input('show')
            ->setLabel(__('<b>Afficher la sélection</b>', 'docalist-basket'))
            ->addClass('widefat');

        $form->input('addpage')
            ->setLabel(__('<b>Ajouter les notices de la page</b>', 'docalist-basket'))
            ->addClass('widefat');

        $form->input('removepage')
            ->setLabel(__('<b>Enlever les notices de la page</b>', 'docalist-basket'))
            ->addClass('widefat');

        $form->input('clear')
            ->setLabel(__('<b>Vider la sélection</b>', 'docalist-basket'))
            ->addClass('widefat');


        return $form;
    }

    /**
     * Retourne les paramètres par défaut du widget.
     *
     * @return array
     */
    protected function defaultSettings()
    {
        return [
            'title' => __('Panier (%d)', 'docalist-basket'),
            'show' => __('Afficher', 'docalist-basket'),
            'addpage' => __('Ajouter tout (%d)', 'docalist-basket'),
            'removepage' => __('Enlever la page (%d)', 'docalist-basket'),
            'clear' => __('Vider', 'docalist-basket'),
        ];
    }

    /**
     * Affiche le formulaire qui permet de paramètrer le widget.
     *
     * @see WP_Widget::form()
     */
    public function form($instance)
    {
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
        $form->setName($name);

        // Affiche le formulaire
        $form->display();
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
    public function update($new, $old)
    {
        $settings = $this->settingsForm()->bind($new)->getData();

        // TODO validation

        return $settings;
    }
}
