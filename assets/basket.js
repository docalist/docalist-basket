/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
jQuery(document).ready(function($) {
    /**
     * Simplifie le nom de la variable fournie à wp_localize_script() dans BasketController
     */
    var settings = docalistBasketSettings;

    /**
     * Appelle l'API du panier.
     */
    function call(action, refs) {
        var url = settings['url'] + '&m=' + action;
        if (refs) {
            url += '&postID=' + refs;
        }
        $.getJSON(url, function(response) {
            $(document).trigger('docalist-basket-changed', response);
        });
    }

    /**
     * Retourne l'ID du post qui contient l'élément passé en paramètre.
     */
    function postID(e) {
        var hentry = $(e).parents('.hentry');

        if (hentry.length === 0) {
            return alert('hentry not found');
        }

        var match = hentry.attr('class').match(/\bpost-(\d+)\b/);
        if (match === null) {
            return alert('Post ID not found');
        }

        return match[1];
    }

    /**
     * Modifie l'état du post dont l'id est indiqué (sélectionné ou non).
     */
    function togglePost(id, state) {
        var hentry = $('.post-' + id);

        // La notice est sélectionnée
        if (state === true) {
            hentry.removeClass(settings['basket-inactive']).addClass(settings['basket-active']);
            $('.basket-add', hentry).replaceWith(settings['removeButton']);
        }

        // La notice n'est pas sélectionnée
        else {
            hentry.removeClass(settings['basket-active']).addClass(settings['basket-inactive']);
            $('.basket-remove', hentry).replaceWith(settings['addButton']);
        }
    }

    /**
     * Ajout d'une notice unique.
     */
    $(document).on('click', '.basket-add', function(e) {
        var id = postID(this);

        call('add', id);
        togglePost(id, true);

        e.preventDefault();
    });

    /**
     * Suppression d'une notice unique.
     */
    $(document).on('click', '.basket-remove', function(e) {
        var id = postID(this);

        call('remove', id);
        togglePost(id, false);

        e.preventDefault();
    });

    /**
     * Ajout de toutes les notices de la page.
     */
    $(document).on('click', '.basket-addpage a', function(e) {
        var id, refs = [];

        $('.basket-add').each(function() {
            id = postID(this);
            refs.push(id);
            togglePost(id, true);
        });
        refs.length && call('add', refs.join(','));

        e.preventDefault();
    });

    /**
     * Suppression de toutes les notices de la page.
     */
    $(document).on('click', '.basket-removepage a', function(e) {
        var id, refs = [];

        $('.basket-remove').each(function() {
            id = postID(this);
            refs.push(id);
            togglePost(id, false);
        });
        refs.length && call('remove', refs.join(','));

        e.preventDefault();
    });

    /**
     * Vider le panier.
     */
    $(document).on('click', '.basket-clear a', function(e) {
        call('clear');

        $('.basket-remove').each(function() {
            togglePost(postID(this), false);
        });

        e.preventDefault();
    });

    /**
     * Mise à jour du widget et de l'état des notices.
     */
    $(document).on('docalist-basket-changed', function(e, response) {
        // Met à jour les liens du widget
        if (response) {
            $('.basket-count').html(response.count);
            $('.basket-show,.basket-clear').toggle(response.count !== 0)
        }

        var addpageCount = $('.basket-add').length;
        var removepageCount = $('.basket-remove').length;
        $('.basket-addpage-count').html(addpageCount);
        $('.basket-removepage-count').html(removepageCount);
        $('.basket-addpage').toggle(addpageCount !== 0);
        $('.basket-removepage').toggle(removepageCount !== 0);

        // Met à jour l'état des notices de la page
        if (response && response.result) {
            for (var id in response.result) {
                togglePost(id, response.result[id]);
            }
        }

        // Si on a vidé le panier, désélectionne tout
        if (response && response.action === 'clear') {
            $('.' + settings['basket-active'])
                .removeClass(settings['basket-active'])
                .addClass(settings['basket-inactive']);
            $('.basket-remove').replaceWith(settings['addButton']);
        }

    });

    $(document).trigger('docalist-basket-changed');
});