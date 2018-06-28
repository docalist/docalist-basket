<?php declare(strict_types=1);
/**
 * This file is part of Docalist UserData.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\UserData;

use Docalist\AdminPage;

/**
 * Options de configuration du plugin.
 */
class SettingsPage extends AdminPage
{
    /**
     * Paramètres du plugin.
     *
     * @var Settings
     */
    protected $settings;

    /**
     * Crée la page de réglages des paramètres du plugin.
     *
     * @param Settings $settings Paramètres du plugin.
     */
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;

        parent::__construct(
            'docalist-userdata-settings',               // ID
            'options-general.php',                      // page parent
            __('Panier Docalist', 'docalist-userdata')  // libellé menu
        );

        // Ajoute un lien "Réglages" dans la page des plugins
        $filter = 'plugin_action_links_docalist-userdata/docalist-userdata.php';
        add_filter($filter, function ($actions) {
            return [
                'settings' => sprintf(
                    '<a href="%s">%s</a>',
                    esc_attr($this->getUrl()),
                    __('Réglages', 'docalist-userdata')
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
                $this->settings->basketpage = (int) $_POST['basketpage'];
                $this->settings->htmlInactive = $_POST['htmlInactive'];
                $this->settings->htmlActive = $_POST['htmlActive'];
                $this->settings->linksBeforeContent = (bool) $_POST['linksBeforeContent'];

                // $settings->validate();
                $this->settings->save();

                docalist('admin-notices')->success(
                    __('Options enregistrées.', 'docalist-userdata')
                );

                return $this->redirect($this->getUrl($this->getDefaultAction()), 303);
            } catch (Exception $e) {
                docalist('admin-notices')->error($e->getMessage());
            }
        }

        return $this->view('docalist-userdata:settings/basket', [
            'settings' => $this->settings,
        ]);
    }
}
