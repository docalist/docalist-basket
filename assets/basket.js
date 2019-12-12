/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2019 Daniel Ménard
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
    function call(action, refs, callback) {
        var url = settings['url'] + '&m=' + action;
        
        if (refs) {
            url += '&postID=' + refs;
        }
        
        $.getJSON(url, function(response) {
            callback && callback(response);
            $(document).trigger('docalist-basket-changed', response);
        });
    }

    /**
     * Retourne l'ID du post qui contient l'élément passé en paramètre.
     */
    function postID(e) {
        var hentry = $(e).parents('.hentry');

        if (hentry.length === 0) {
            alert('Docalist-basket: incompatible template, CSS class ".hentry" not found');
            
            return null;
        }

        var match = hentry.attr('class').match(/\bpost-(\d+)\b/);
        if (match === null) {
            alert('Docalist-basket: incompatible template, CSS class ".post-id" not found');
            
            return null; 
        }

        return match[1];
    }

    /**
     * Modifie l'état du post dont l'id est indiqué (sélectionné ou non).
     */
    function togglePost(id, state) {
        // Trouve le post ayant l'id indiqué
        var hentry = $('.post-' + id);
        if (hentry.length === 0) {
            return;
        }
        
        // La notice est sélectionnée
        if (state === true) {
            hentry.removeClass(settings['basket-inactive']).addClass(settings['basket-active']);
            $('.basket-add', hentry).replaceWith(settings['removeButton']);
            
            return;
        }

        // La notice n'est pas sélectionnée
        hentry.removeClass(settings['basket-active']).addClass(settings['basket-inactive']);
        $('.basket-remove', hentry).replaceWith(settings['addButton']);
    }

    /**
     * Ajout d'une notice unique.
     */
    $(document).on('click', '.basket-add', function(e) {
        e.preventDefault();
        
        var id = postID(this);
        if (id === null) {
            return;
        }
        
        call('add', id, function(response) {
            if (response.full && response.result.length === 0) {
                alert("Votre panier est plein, impossible d'ajouter la notice.");
                
                return;
            }
            togglePost(response.result[0], true);
        });
    });

    /**
     * Suppression d'une notice unique.
     */
    $(document).on('click', '.basket-remove', function(e) {
        e.preventDefault();
        
        var id = postID(this);
        if (id === null) {
            return;
        }

        call('remove', id, function(response) {
            if (response.result.length > 0) { // sanity check, si la notice n'était pas dans le panier
                togglePost(response.result[0], false);
            }
        });
    });

    /**
     * Ajout de toutes les notices de la page.
     */
    $(document).on('click', '.basket-addpage a', function(e) {
        e.preventDefault();
        
        var refs = [];

        $('.basket-add').each(function() {
            var id = postID(this);
            if (id !== null) {
                refs.push(id);
            }
        });
        if (refs.length === 0) {
            return;
        } 
        
        call('add', refs.join(','), function(response) {
            $.each(response.result, function(index, id) {
                togglePost(id, true);
            })
            if (response.full) {
                if (response.result.length === 0) {
                    alert("Votre panier est plein, aucune notice n'a été ajoutée.");
                    
                    return;
                }
                if (response.result.length !== refs.length) {
                    alert("Votre panier est plein, seulement " + response.result.length + " notice(s) ajoutée(s).");
                }
            }
        });
    });

    /**
     * Suppression de toutes les notices de la page.
     */
    $(document).on('click', '.basket-removepage a', function(e) {
        e.preventDefault();
        
        var refs = [];

        $('.basket-remove').each(function() {
            var id = postID(this);
            if (id !== null) {
                refs.push(id);
            }
        });
        if (refs.length === 0) {
            return;
        } 
        
        call('remove', refs.join(','), function(response) {
            $.each(refs, function(index, id) { // on se base sur refs quelle que soit la réponse
                togglePost(id, false);
            })
        });
    });

    /**
     * Vider le panier.
     */
    $(document).on('click', '.basket-clear a', function(e) {
        e.preventDefault();
        call('clear', null, function(response) {
            $('.basket-remove').each(function() {
                togglePost(postID(this), false);
            });
        });
    });

    /**
     * Mise à jour du widget et de l'état des notices.
     */
    $(document).on('docalist-basket-changed', function(e, response) {
        // Détermine le nombre de notices "baskettables" qu'on a sur la page
        var addpageCount = $('.basket-add').length;
        
        // Détermine le nombre de notices de la page qui sont déjà dans le panier  
        var removepageCount = $('.basket-remove').length;
        
        // Met à jour les liens du widget
        $('.basket-addpage').toggle(addpageCount !== 0);        // Affiche ou masque le lien "ajouter le page"
        $('.basket-addpage-count').html(addpageCount);          // Nombre de notices dans le lien "ajouter la page"
        
        $('.basket-removepage').toggle(removepageCount !== 0);  // Affiche ou masque le lien "enlever le page"
        $('.basket-removepage-count').html(removepageCount);    // Nombre de notices dans le lien "enlever la page"
        
        // Si on n'a pas de réponse (lors de l'initialisation), on ne fait rien de plus
        if (!response) {
            return;
        }
        
        // Met à jour le nombre total de notices dans le panier
        $('.basket-count').html(response.count);
        
        // Affiche ou masque les liens "Afficher le panier" et "Vider le panier"
        $('.basket-show,.basket-clear').toggle(response.count !== 0)
return;
        // Met à jour l'état des notices de la page
        if (response.action === 'add' && response.result) {
            for (var id in response.result) {
                togglePost(id, true);
            }
            if (response.full) {
                alert('Votre panier est plein.');
            }
        }

        // Si on a vidé le panier, désélectionne tout
        if (response.action === 'clear') {
            $('.' + settings['basket-active'])
                .removeClass(settings['basket-active'])
                .addClass(settings['basket-inactive']);
            $('.basket-remove').replaceWith(settings['addButton']);
        }
        
    });

    $(document).trigger('docalist-basket-changed');
});