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

Session::checkRight("plugin_ocsinventoryng_import", READ);

Html::header('OCS Inventory NG', '', "tools", Menu::class, "import");

//First time this screen is displayed : set the import mode to 'basic'
if (!isset($_SESSION["change_import_mode"])) {
    $_SESSION["change_import_mode"] = 0;
}

if (!isset($_SESSION["change_link_mode"])) {
    $_SESSION["change_link_mode"] = 0;
}

//Changing the import mode
if (isset($_POST["simple_mode"])) {
    $_SESSION["change_import_mode"] = 0;
    $_SESSION["change_link_mode"] = 0;
}

if (isset($_POST["change_import_mode"])) {
    $_SESSION["change_import_mode"] = 1;
}

if (isset($_POST["change_link_mode"])) {
    $_SESSION["change_link_mode"] = 1;
}

if (isset($_SESSION["ocs_import"]['computers'])) {
    if ((isset($_SESSION["ocs_import"]["connection"])
         && $_SESSION["ocs_import"]["connection"] == false)
        || !isset($_SESSION["ocs_import"]["connection"])) {
        if (!OcsServer::checkOCSconnection($_SESSION["plugin_ocsinventoryng_ocsservers_id"])) {
            OcsProcess::showStatistics($_SESSION["ocs_import_statistics"]);
            $_SESSION["ocs_import"]["id"] = [];

            Html::redirect($_SERVER['PHP_SELF']);
        } else {
            $_SESSION["ocs_import"]["connection"] = true;
        }
    }

    if ($count = count($_SESSION["ocs_import"]['computers'])) {
        $percent = min(
            100,
            round(
                100 * ($_SESSION["ocs_import_count"] - $count) / $_SESSION["ocs_import_count"],
                0, PHP_ROUND_HALF_UP
            )
        );

        $key = array_pop($_SESSION["ocs_import"]['computers']);

        if (isset($_SESSION["ocs_import"]["entities_id"][$key])) {
            $entity = $_SESSION["ocs_import"]["entities_id"][$key];
        } else {
            $entity = -1;
        }

        if (isset($_SESSION["ocs_import"]["is_recursive"][$key])) {
            $recursive = $_SESSION["ocs_import"]["is_recursive"][$key];
        } else {
            $recursive = -1;
        }

        if (isset($_SESSION["ocs_import"]["disable_unicity_check"][$key])) {
            $disable_unicity_check = $_SESSION["ocs_import"]["disable_unicity_check"][$key];
        } else {
            $disable_unicity_check = false;
        }

        if (isset($_SESSION["ocs_import"]["tolink"][$key])) {
            $computers_id = $_SESSION["ocs_import"]["tolink"][$key];
        } else {
            $computers_id = false;
        }

        $process_params = ['ocsid'                               => $key,
            'plugin_ocsinventoryng_ocsservers_id' => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
            'lock'                                => 0,
            'defaultentity'                       => $entity,
            'defaultrecursive'                    => $recursive,
            'disable_unicity_check'               => $disable_unicity_check,
            'computers_id'                        => $computers_id];

        $action = OcsProcess::processComputer($process_params);

        OcsProcess::manageImportStatistics(
            $_SESSION["ocs_import"]['statistics'],
            $action['status']
        );
        Html::getProgressBar($percent);

        Html::redirect(PLUGIN_OCS_WEBDIR . '/front/ocsng.import.php');
    }
}

if (isset($_SESSION["plugin_ocsinventoryng_ocsservers_id"])
    && $_SESSION["plugin_ocsinventoryng_ocsservers_id"] > -1) {
    if (!isset($_POST["import_ok"])) {
        $ocsClient   = OcsServer::getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
        $deleted_pcs = $ocsClient->getTotalDeletedComputers();
        if ($deleted_pcs > 0) {
            echo "<div class='alert alert-important alert-warning d-flex'>";
            echo __('You have', 'ocsinventoryng') . " " . $deleted_pcs . " " . __('deleted computers into OCS Inventory NG', 'ocsinventoryng');
            echo "<br>";
            echo __('Please clean them before import or synchronize computers', 'ocsinventoryng');
            echo "</div><br>";
        }

        $show_params = ['plugin_ocsinventoryng_ocsservers_id' => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
            'import_mode'                         => $_SESSION["change_import_mode"],
            'link_mode'                           => $_SESSION["change_link_mode"],
            'entities_id'                         => $_SESSION['glpiactiveentities']];
        OcsServer::showComputersToAdd($show_params);
    } else {
        if (isset($_POST['toadd']) && count($_POST['toadd']) > 0) {
            $_SESSION["ocs_import_count"] = 0;

            foreach ($_POST['toadd'] as $key => $val) {
                $_SESSION["ocs_import"]['computers'][] = $val;
                $_SESSION["ocs_import_count"]++;
            }
            if (isset($_POST['disable_unicity_check'])) {
                foreach ($_POST['disable_unicity_check'] as $key => $val) {
                    $_SESSION["ocs_import"]['disable_unicity_check'][$key] = $val;
                }
            }
            if (isset($_POST['toimport_entities'])) {
                foreach ($_POST['toimport_entities'] as $key => $val) {
                    $_SESSION["ocs_import"]['entities_id'][$key] = $val;
                }
            }
            if (isset($_POST['toimport_recursive'])) {
                foreach ($_POST['toimport_recursive'] as $key => $val) {
                    $_SESSION["ocs_import"]['is_recursive'][$key] = $val;
                }
            }
            if (isset($_POST['tolink'])) {
                foreach ($_POST['tolink'] as $key => $val) {
                    $_SESSION["ocs_import"]['tolink'][$key] = $val;
                }
            }
        }
        Html::redirect(PLUGIN_OCS_WEBDIR . '/front/ocsng.import.php');
    }
} else {
    echo "<div class='center'>";
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
}
Html::footer();
