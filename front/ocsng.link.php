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
use GlpiPlugin\Ocsinventoryng\OcsProcess;
use GlpiPlugin\Ocsinventoryng\OcsServer;

Session::checkRight("plugin_ocsinventoryng_link", READ);

Html::header('OCS Inventory NG', '', "tools", Menu::class, "link");

if (isset($_POST["id"]) && is_array($_POST["id"])) {
    foreach ($_POST['id'] as $key => $id) {
        $_SESSION["ocs_link"][$id]["ocsid"] = $id;
        foreach ($_POST['item_to_link'] as $k => $item_to_link) {
            if ($k == $key) {
                $_SESSION["ocs_link"][$id]["computers_id"] = $item_to_link;
            }
        }
    }

    $_SESSION["ocs_link_count"] = count($_POST['id']);
} else {
    $_SESSION["ocs_link_count"] = 0;
    unset($_SESSION["ocs_link"]);
}

$CFG_GLPI["use_ajax"] = 1;

//First time this screen is displayed : set the import mode to 'basic'
if (!isset($_SESSION["change_import_mode"])) {
    $_SESSION["change_import_mode"] = 0;
}

//Changing the import mode
if (isset($_POST["change_import_mode"])) {
    if ('id' == "false") {
        $_SESSION["change_import_mode"] = 0;
    } else {
        $_SESSION["change_import_mode"] = 1;
    }
}

if (!isset($_SESSION["change_link_mode"])) {
    $_SESSION["change_link_mode"] = 0;
}

if (isset($_POST["change_link_mode"])) {
    if ($_POST['id'] == "false") {
        $_SESSION["change_link_mode"] = 0;
    } else {
        $_SESSION["change_link_mode"] = 1;
    }
}

if (isset($_POST["delete_link"])) {
    $link = new Ocslink();
    if (isset($_POST["toimport"]) && (count($_POST['toimport']) > 0)) {
        foreach ($_POST['toimport'] as $key => $val) {
            if ($val == "on") {
                $link->deleteByCriteria(['ocsid' => $key]);
            }
        }
    }
    Html::back();
}

if (isset($_SESSION["ocs_link"])) {
    if ($count = count($_SESSION["ocs_link"])) {
        if ((isset($_SESSION["ocs_link_data"]["connection"]) && $_SESSION["ocs_link_data"]["connection"] == false) || !isset($_SESSION["ocs_link_data"]["connection"])) {
            if (!OcsServer::checkOCSconnection($_SESSION["plugin_ocsinventoryng_ocsservers_id"])) {
                $_SESSION["ocs_link"] = [];

                Html::redirect($_SERVER['PHP_SELF']);
            } else {
                $_SESSION["ocs_link_data"]["connection"] = true;
            }
        }
        $percent = min(
            100,
            round(100 * ($_SESSION["ocs_link_count"] - $count) / $_SESSION["ocs_link_count"], 0, PHP_ROUND_HALF_UP)
        );

        Html::getProgressBar($percent);

        $key         = array_pop($_SESSION["ocs_link"]);
        $link_params = ['ocsid'                               => $key["ocsid"],
                        'plugin_ocsinventoryng_ocsservers_id' => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                        'computers_id'                        => $key["computers_id"]];
        OcsProcess::linkComputer($link_params);
        Html::redirect($_SERVER['PHP_SELF']);
    } else {
        Html::getProgressBar(100);

        unset($_SESSION["ocs_link"]);
        echo "<div class='center b'>" . __('Successful link', 'ocsinventoryng') . "<br>";
        echo "<a href='" . $_SERVER['PHP_SELF'] . "'>" . __('Back') . "</a></div>";
    }
}

if (!isset($_POST["import_ok"])) {
    if (!isset($_GET['check'])) {
        $_GET['check'] = 'all';
    }
    if (!isset($_GET['start'])) {
        $_GET['start'] = 0;
    }
    if (isset($_GET['start'])) {
        $_SESSION['ocs_import']['start'] = $_GET['start'];
    }

    if (isset($_SESSION["plugin_ocsinventoryng_ocsservers_id"])) {
        $ocsClient = OcsServer::getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
        $deleted_pcs   = $ocsClient->getTotalDeletedComputers();
        if ($deleted_pcs > 0) {
            echo "<div class='center'>";
            echo "<span style='color:firebrick'>";
            echo "<i class='ti ti-alert-triangle fa-5x'></i><br><br>";
            echo __('You have', 'ocsinventoryng')." ". $deleted_pcs . " " . __('deleted computers into OCS Inventory NG', 'ocsinventoryng');
            echo "<br>";
            echo __('Please clean them before import or synchronize computers', 'ocsinventoryng');
            echo "</span></div><br>";
        }

        $show_params = ['plugin_ocsinventoryng_ocsservers_id' => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                        'import_mode'                         => $_SESSION["change_import_mode"],
                        'link_mode'                         => $_SESSION["change_link_mode"],
                        'entities_id'                         => $_SESSION['glpiactiveentities']];
        OcsServer::showComputersToAdd($show_params);
    }
} else {
    if (isset($_POST["toimport"]) && (count($_POST['toimport']) > 0)) {
        $_SESSION["ocs_link_count"] = 0;

        foreach ($_POST['toimport'] as $key => $val) {
            if ($val == "on") {
                if (isset($_POST['tolink']) && count($_POST['tolink']) > 0) {
                    foreach ($_POST['tolink'] as $ocsid => $computers_id) {
                        if ($computers_id > 0 && $key == $ocsid) {
                            $_SESSION["ocs_link"][] = ['ocsid'        => $ocsid,
                                                       'computers_id' => $computers_id];
                        }
                    }
                }
                $_SESSION["ocs_link_count"]++;
            }
        }
    }
    Html::redirect($_SERVER['PHP_SELF']);
}

Html::footer();
