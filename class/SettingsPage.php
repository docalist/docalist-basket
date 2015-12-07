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
 */
namespace Docalist\Biblio\UserData;

use Docalist\AdminPage;

/**
 * Options de configuration du plugin.
 */
class SettingsPage extends AdminPage
{
    /**
     * Action par défaut du contrôleur.
     *
     * @var string
     */
    protected $defaultAction = 'BasketSettings';

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
            'docalist-biblio-userdata-settings',                // ID
            'options-general.php',                              // page parent
            __('Panier de notices', 'docalist-biblio-userdata') // libellé menu
        );

        // Ajoute un lien "Réglages" dans la page des plugins
        $filter = 'plugin_action_links_docalist-biblio-userdata/docalist-biblio-userdata.php';
        add_filter($filter, function ($actions) {
            $action = sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    esc_attr($this->url()),
                    $this->menuTitle(),
                    __('Réglages', 'docalist-biblio-userdata')
            );
            array_unshift($actions, $action);

            return $actions;
        });
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
                    __('Options enregistrées.', 'docalist-biblio-userdata')
                );

                return $this->redirect($this->url($this->defaultAction()), 303);
            } catch (Exception $e) {
                docalist('admin-notices')->error($e->getMessage());
            }
        }

        return $this->view('docalist-biblio-userdata:settings/basket', [
            'settings' => $this->settings,
        ]);
    }
}
