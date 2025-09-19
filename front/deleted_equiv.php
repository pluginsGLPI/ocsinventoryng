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

Html::header('OCSInventory NG', '', "tools", Menu::class, "deleted_equiv");

global $CFG_GLPI;


if (!isset($_SESSION["plugin_ocsinventoryng_ocsservers_id"])
    || $_SESSION["plugin_ocsinventoryng_ocsservers_id"] == -1) {
    echo "<div align='center'>";
    echo "<i class='ti ti-alert-triangle fa-4x' style='color:orange'></i>";
    echo "<br>";
    echo "<div class='red b'>";
    echo __('No OCSNG server defined', 'ocsinventoryng');
    echo "<br>";
    echo __('You must to configure a OCSNG server', 'ocsinventoryng');
    echo " : <a href='" . PLUGIN_OCS_WEBDIR . "/front/ocsserver.form.php'>";
    echo __('Add a OCSNG server', 'ocsinventoryng');
    echo "</a>";
    echo "</div></div>";
} else {
    echo "<div class='center'>";
    $ocsClient = OcsServer::getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
    if ($ocsClient->getConnectionType() == OcsSoapClient::class) {
        OcsProcess::manageDeleted($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
        if ($_SESSION["ocs_deleted_equiv"]['computers_to_del']) {
            echo "<div class='center b'>" . $_SESSION["ocs_deleted_equiv"]['computers_deleted'] . " " . __('deleted computers into OCS Inventory NG', 'ocsinventoryng');
            Html::redirect($_SERVER['PHP_SELF']);
        } else {
            if ($_SESSION["ocs_deleted_equiv"]['computers_deleted'] === 0) {
                echo "<div class='center b'>" . __('No new computers to delete', 'ocsinventoryng') . ".</div>";
            } else {
                echo "<div class='center b'>";
                echo "<table class='tab_cadre_fixe'>";
                echo "<th colspan='2'>" . __('Clean OCSNG deleted computers', 'ocsinventoryng');
                echo "</th>";
                echo "<tr class='tab_bg_1'><td>" . __('deleted computers into OCS Inventory NG', 'ocsinventoryng') . "</td><td>" . $_SESSION["ocs_deleted_equiv"]['computers_deleted'] . "</td></tr>";
                echo "</table></div>";
            }
            echo "<a href='" . PLUGIN_OCS_WEBDIR . "/front/ocsng.php'>";
            echo __('Back');
            echo "</a>";
        }
    } else {
        if (empty($_SESSION["ocs_deleted_equiv"]["total"])) {
            OcsProcess::manageDeleted($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
        }

        if ($_SESSION["ocs_deleted_equiv"]["total"] != $_SESSION["ocs_deleted_equiv"]["deleted"]
            && isset($_SESSION["ocs_deleted_equiv"]["last_req"])) {
            echo $_SESSION["ocs_deleted_equiv"]["deleted"] . "/" . $_SESSION["ocs_deleted_equiv"]["total"];
            echo "<br><br>";
            $count   = $_SESSION["ocs_deleted_equiv"]["deleted"];
            if ($_SESSION["ocs_deleted_equiv"]["total"] > 0) {
                $percent = min(
                    100,
                    round(
                        100 * ($count) / $_SESSION["ocs_deleted_equiv"]["total"],
                        0
                    )
                );
                Html::getProgressBar($percent, "%");
            }


            OcsProcess::manageDeleted($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
        } else {
            if ($_SESSION["ocs_deleted_equiv"]["total"] === 0) {
                echo "<div class='center b'>" . __('No new computers to delete', 'ocsinventoryng') . ".</div>";
            } else {
                $total                                    = $_SESSION["ocs_deleted_equiv"]["total"];
                $_SESSION["ocs_deleted_equiv"]["total"]   = 0;
                $count                                    = $_SESSION["ocs_deleted_equiv"]["deleted"];
                $_SESSION["ocs_deleted_equiv"]["deleted"] = 0;
                echo "<div class='center b'>";
                echo "<table class='tab_cadre_fixe'>";
                echo "<th colspan='2'>" . __('Clean OCSNG deleted computers', 'ocsinventoryng');
                echo "</th>";
                echo "<tr class='tab_bg_1'><td>" . __('deleted computers into OCS Inventory NG', 'ocsinventoryng') . "</td><td>" . $count . "/" . $total . "</td></tr>";
                echo "</table></div>";
            }
            echo "<a href='" . PLUGIN_OCS_WEBDIR . "/front/ocsng.php'>";
            echo __('Back');
            echo "</a>";
        }
    }

    echo "</div>";
}
Html::footer();
