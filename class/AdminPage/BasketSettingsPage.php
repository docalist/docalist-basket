<?php declare(strict_types=1);
/**
 * This file is part of Docalist Basket.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Basket\AdminPage;

use Docalist\AdminPage;
use Docalist\Basket\Settings\BasketSettings;

/**
 * Page de configuration du module panier.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class BasketSettingsPage extends AdminPage
{
    /**
     * Paramètres du module panier.
     *
     * @var BasketSettings
     */
    protected $settings;

    /**
     * Crée la page de réglages des paramètres du plugin.
     *
     * @param BasketSettings $settings Paramètres du plugin.
     */
    public function __construct(BasketSettings $settings)
    {
        $this->settings = $settings;

        parent::__construct(
            'docalist-basket-settings',                 // ID
            'options-general.php',                      // Page parent
            __('Panier Docalist', 'docalist-basket')    // Libellé du menu
        );

        // Ajoute un lien "Réglages" dans la page des plugins
        $filter = 'plugin_action_links_docalist-basket/docalist-basket.php';
        add_filter($filter, function ($actions) {
            return [
                'settings' => sprintf(
                    '<a href="%s">%s</a>',
                    esc_attr($this->getUrl()),
                    __('Réglages', 'docalist-basket')
                )
            ] + $actions;
        });
    }

    protected function getDefaultAction()
    {
        return 'BasketSettings';
    }

    /**
     * Paramètres du panier.
     */
    public function actionBasketSettings()
    {
        if ($this->isPost()) {
            try {
                $_POST = wp_unslash($_POST);
                $this->settings->role= $_POST['role'];
                $this->settings->types = $_POST['types'] ?? [];
                $this->settings->single = $_POST['single'];
                $this->settings->list = $_POST['list'];
                $this->settings->basketpage = (int) $_POST['basketpage'];

                // $settings->validate();
                $this->settings->save();

                docalist('admin-notices')->success(
                    __('Les options du panier Docalist ont été enregistrées.', 'docalist-basket')
                );

                return $this->redirect($this->getUrl($this->getDefaultAction()), 303);
            } catch (Exception $e) {
                docalist('admin-notices')->error($e->getMessage());
            }
        }

        return $this->view('docalist-basket:settings', [
            'settings' => $this->settings,
        ]);
    }
}
