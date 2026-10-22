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
use GlpiPlugin\Ocsinventoryng\OcsProcess;
use GlpiPlugin\Ocsinventoryng\OcsServer;
use GlpiPlugin\Ocsinventoryng\OcsSoapClient;

Session::checkRight("plugin_ocsinventoryng", UPDATE);

// The OCS server of the session, chosen on front/ocsng.php: still allowed for the active
// profile, otherwise "no server"
OcsServer::checkSessionServer();


Html::header(Menu::getTypeName(), '', "tools", Menu::class, "deleted_equiv");

if (!isset($_SESSION["plugin_ocsinventoryng_ocsservers_id"])
    || $_SESSION["plugin_ocsinventoryng_ocsservers_id"] == -1) {
    OcsServer::showNoServer();
} elseif (!OcsServer::checkOCSconnection($_SESSION["plugin_ocsinventoryng_ocsservers_id"])) {
    OcsServer::showMessage([
        'title' => __('Connection to the database failed', 'ocsinventoryng'),
        'icon'  => 'ti-plug-connected-x',
        'color' => 'bg-red-lt',
        'links' => [OcsServer::getBackLink()],
    ]);
} else {
    $title     = __('Clean OCSNG deleted computers', 'ocsinventoryng');
    $ocsClient = OcsServer::getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
    if ($ocsClient->getConnectionType() == OcsSoapClient::class) {
        OcsProcess::manageDeleted($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
        if ($_SESSION["ocs_deleted_equiv"]['computers_to_del']) {
            Html::redirect(PLUGIN_OCS_WEBDIR . '/front/deleted_equiv.php');
        } elseif ($_SESSION["ocs_deleted_equiv"]['computers_deleted'] === 0) {
            OcsServer::showMessage([
                'title' => $title,
                'lines' => [__('No new computers to delete', 'ocsinventoryng')],
                'icon'  => 'ti-circle-check',
                'color' => 'bg-green-lt',
                'links' => [OcsServer::getBackLink()],
            ]);
        } else {
            OcsServer::showMessage([
                'title' => $title,
                'lines' => [(int) $_SESSION["ocs_deleted_equiv"]['computers_deleted'] . " "
                    . __('deleted computers into OCS Inventory NG', 'ocsinventoryng')],
                'icon'  => 'ti-circle-check',
                'color' => 'bg-green-lt',
                'links' => [OcsServer::getBackLink()],
            ]);
        }
    } else {
        if (empty($_SESSION["ocs_deleted_equiv"]["total"])) {
            OcsProcess::manageDeleted($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
        }

        if ($_SESSION["ocs_deleted_equiv"]["total"] != $_SESSION["ocs_deleted_equiv"]["deleted"]
            && isset($_SESSION["ocs_deleted_equiv"]["last_req"])) {
            // Batch in progress: the page reloads itself until every deleted computer is processed
            $count = (int) $_SESSION["ocs_deleted_equiv"]["deleted"];
            $total = (int) $_SESSION["ocs_deleted_equiv"]["total"];
            $message = [
                'title' => $title,
                'lines' => [$count . " / " . $total],
                'icon'  => 'ti-loader-2',
            ];
            if ($total > 0) {
                $message['progress'] = min(100, (int) round(100 * $count / $total, 0, PHP_ROUND_HALF_UP));
            }
            OcsServer::showMessage($message);

            OcsProcess::manageDeleted($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
        } elseif ($_SESSION["ocs_deleted_equiv"]["total"] === 0) {
            OcsServer::showMessage([
                'title' => $title,
                'lines' => [__('No new computers to delete', 'ocsinventoryng')],
                'icon'  => 'ti-circle-check',
                'color' => 'bg-green-lt',
                'links' => [OcsServer::getBackLink()],
            ]);
        } else {
            $total                                    = (int) $_SESSION["ocs_deleted_equiv"]["total"];
            $_SESSION["ocs_deleted_equiv"]["total"]   = 0;
            $count                                    = (int) $_SESSION["ocs_deleted_equiv"]["deleted"];
            $_SESSION["ocs_deleted_equiv"]["deleted"] = 0;
            OcsServer::showMessage([
                'title'    => $title,
                'lines'    => [$count . " / " . $total . " " . __('deleted computers into OCS Inventory NG', 'ocsinventoryng')],
                'icon'     => 'ti-circle-check',
                'color'    => 'bg-green-lt',
                'progress' => 100,
                'links'    => [OcsServer::getBackLink()],
            ]);
        }
    }
}
Html::footer();
