<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2025 by the ocsinventoryng Development Team.

 https://github.com/pluginsGLPI/ocsinventoryng
 -------------------------------------------------------------------------

 LICENSE

 This file is part of ocsinventoryng.

 ocsinventoryng is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 ocsinventoryng is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with ocsinventoryng. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */


use GlpiPlugin\Ocsinventoryng\Menu;
use GlpiPlugin\Ocsinventoryng\Ocslink;
use GlpiPlugin\Ocsinventoryng\OcsServer;

// The OCS server of the session, chosen on front/ocsng.php: still allowed for the active
// profile, otherwise "no server"
OcsServer::checkSessionServer();

Html::header(Menu::getTypeName(), '', "tools", Menu::class, "clean");

if (isset($_SESSION["plugin_ocsinventoryng_ocsservers_id"])
    && $_SESSION["plugin_ocsinventoryng_ocsservers_id"] > -1) {
    if (!isset($_POST["clean_ok"])) {

        Session::checkRight("plugin_ocsinventoryng_clean", READ);

        $deleted_pcs = 0;
        if (OcsServer::checkOCSconnection($_SESSION["plugin_ocsinventoryng_ocsservers_id"])) {
            $deleted_pcs = OcsServer::getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"])->getTotalDeletedComputers();
        } else {
            OcsServer::showMessage([
                'title' => __('Connection to the database failed', 'ocsinventoryng'),
                'icon'  => 'ti-plug-connected-x',
                'color' => 'bg-red-lt',
                'links' => [OcsServer::getBackLink()],
            ]);
        }
        if ($deleted_pcs > 0) {
            $links = [];
            if (Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
                $links[] = [
                    'title' => __('Clean OCSNG deleted computers', 'ocsinventoryng'),
                    'url'   => PLUGIN_OCS_WEBDIR . "/front/deleted_equiv.php",
                    'icon'  => 'ti-trash',
                    'class' => 'btn-primary',
                ];
            }
            OcsServer::showMessage([
                'title' => __('You have', 'ocsinventoryng') . " " . $deleted_pcs . " "
                    . __('deleted computers into OCS Inventory NG', 'ocsinventoryng'),
                'lines' => [__('Please clean them before import or synchronize computers', 'ocsinventoryng')],
                'icon'  => 'ti-alert-triangle',
                'color' => 'bg-red-lt',
                'links' => $links,
            ]);
        }
        $show_params = ['plugin_ocsinventoryng_ocsservers_id' => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                        'check'                               => ($_GET['check'] ?? 'all') === 'none' ? 'none' : 'all',
                        'start'                               => (int) ($_GET['start'] ?? 0)];
        OcsServer::showComputersToClean($show_params);

    } else {
        Session::checkRight("plugin_ocsinventoryng_clean", UPDATE);
        if (is_array($_POST['toclean'] ?? null) && count($_POST['toclean']) > 0) {
            // Posted ids: only the computers the user may delete or update are cleaned
            Ocslink::cleanLinksFromList($_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                                                            $_POST['toclean'], true);
            OcsServer::showMessage([
                'title' => __('Clean links between GLPI and OCSNG', 'ocsinventoryng'),
                'lines' => [__('Operation successful')],
                'icon'  => 'ti-circle-check',
                'color' => 'bg-green-lt',
                'links' => [
                    [
                        'title' => __('Clean links between GLPI and OCSNG', 'ocsinventoryng'),
                        'url'   => PLUGIN_OCS_WEBDIR . "/front/ocsng.clean.php",
                        'icon'  => 'ti-recycle',
                    ],
                    OcsServer::getBackLink(),
                ],
            ]);
        } else {
            Html::back();
        }
    }
} else {
    OcsServer::showNoServer();
}

Html::footer();
