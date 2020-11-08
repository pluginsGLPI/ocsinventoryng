<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2016 by the ocsinventoryng Development Team.

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

include('../../../inc/includes.php');

Session::checkRight("plugin_ocsinventoryng_import", READ);

Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "import");

$display_list = true;
//First time this screen is displayed : set the import mode to 'basic'
if (!isset($_SESSION["change_import_mode"])) {
   $_SESSION["change_import_mode"] = false;
}

//Changing the import mode
if (isset($_POST["change_import_mode"])) {
   if ($_POST['id'] == "false") {
      $_SESSION["change_import_mode"] = false;
   } else {
      $_SESSION["change_import_mode"] = true;
   }
}

if (isset($_SESSION["ocs_import"]["id"])) {

   if ($count = count($_SESSION["ocs_import"]["id"])) {
      if ((isset($_SESSION["ocs_import"]["connection"]) && $_SESSION["ocs_import"]["connection"] == false) || !isset($_SESSION["ocs_import"]["connection"])) {
         if (!PluginOcsinventoryngOcsServer::checkOCSconnection($_SESSION["plugin_ocsinventoryng_ocsservers_id"])) {
            PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_import"]['statistics']);
            $_SESSION["ocs_import"]["id"] = [];

            Html::redirect($_SERVER['PHP_SELF']);
         } else {
            $_SESSION["ocs_import"]["connection"] = true;
         }
      }
      $percent = min(100,
                     round(100 * ($_SESSION["ocs_import_count"] - $count) / $_SESSION["ocs_import_count"],
                           0));
      $key     = array_pop($_SESSION["ocs_import"]["id"]);
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

      if (isset($_SESSION["plugin_ocsinventoryng_ocsservers_id"])) {
         $process_params = ['ocsid'                               => $key,
                            'plugin_ocsinventoryng_ocsservers_id' => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                            'lock'                                => 0,
                            'defaultentity'                       => $entity,
                            'defaultrecursive'                    => $recursive,
                            'disable_unicity_check'               => $disable_unicity_check];
         $action         = PluginOcsinventoryngOcsProcess::processComputer($process_params);
      }
      PluginOcsinventoryngOcsProcess::manageImportStatistics($_SESSION["ocs_import"]['statistics'],
                                                             $action['status']);
      PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_import"]['statistics']);
      Html::displayProgressBar(400, $percent);

      Html::redirect($_SERVER['PHP_SELF']);
   } else {
      //displayProgressBar(400, 100);
      if (isset($_SESSION["ocs_import"]['statistics'])) {
         PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_import"]['statistics'], true);
      } else {
         echo "<div class='center b red'>";
         echo __('No import: the plugin will not import these elements', 'ocsinventoryng');
         echo "</div>";
      }
      unset($_SESSION["ocs_import"]);

      echo "<div class='center b'><br>";
      echo "<a href='" . $_SERVER['PHP_SELF'] . "'>" . __('Back') . "</a></div>";
      $display_list = false;
   }
}

if (!isset($_POST["import_ok"])) {
   if (!isset($_GET['check'])) {
      $_GET['check'] = 'all';
   }
   if (!isset($_GET['start'])) {
      $_GET['start'] = 0;
   }
   if (isset($_SESSION["ocs_import"])) {
      unset($_SESSION["ocs_import"]);
   }
   $ocsClient   = PluginOcsinventoryngOcsServer::getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
   $deleted_pcs = $ocsClient->getTotalDeletedComputers();
   if ($deleted_pcs > 0) {
      echo "<div class='center'>";
      echo "<span style='color:firebrick'>";
      echo "<i class='fas fa-exclamation-triangle fa-5x'></i><br><br>";
      echo __('You have', 'ocsinventoryng') . " " . $deleted_pcs . " " . __('deleted computers into OCS Inventory NG', 'ocsinventoryng');
      echo "<br>";
      echo __('Please clean them before import or synchronize computers', 'ocsinventoryng');
      echo "</span></div>";
   }
   if ($display_list) {
      $show_params = ['plugin_ocsinventoryng_ocsservers_id' => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                      'import_mode'                         => $_SESSION["change_import_mode"],
                      'check'                               => $_GET['check'],
                      'start'                               => $_GET['start'],
                      'entities_id'                         => $_SESSION['glpiactiveentities'],
                      'tolinked'                            => false];
      PluginOcsinventoryngOcsServer::showComputersToAdd($show_params);
   }

} else {
   if (isset($_POST["toimport"]) && (count($_POST['toimport']) > 0)) {
      $_SESSION["ocs_import_count"] = 0;

      foreach ($_POST['toimport'] as $key => $val) {
         if ($val == "on") {
            $_SESSION["ocs_import"]["id"][] = $key;

            if (isset($_POST['toimport_entities'])) {
               $_SESSION["ocs_import"]["entities_id"][$key] = $_POST['toimport_entities'][$key];
            }
            if (isset($_POST['toimport_recursive'])) {
               $_SESSION["ocs_import"]["is_recursive"][$key] = $_POST['toimport_recursive'][$key];
            }
            if (isset($_POST['toimport_disable_unicity_check'])) {
               $_SESSION["ocs_import"]["disable_unicity_check"][$key] = $_POST['toimport_disable_unicity_check'][$key];
            }
            $_SESSION["ocs_import_count"]++;
         }
      }
   }
   Html::redirect($_SERVER['PHP_SELF']);
}

Html::footer();
