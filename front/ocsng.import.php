<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2022 by the ocsinventoryng Development Team.

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

//$display_list = true;
//First time this screen is displayed : set the import mode to 'basic'
if (!isset($_SESSION["change_import_mode"])) {
   $_SESSION["change_import_mode"] = 0;
}

//Changing the import mode
if (isset($_POST["change_import_mode"])) {
   if ($_POST['id'] == "false") {
      $_SESSION["change_import_mode"] = 0;
   } else {
      $_SESSION["change_import_mode"] = 1;
   }
}

if (isset($_SESSION["ocs_import"]['computers'])) {

   if ((isset($_SESSION["ocs_import"]["connection"])
        && $_SESSION["ocs_import"]["connection"] == false)
       || !isset($_SESSION["ocs_import"]["connection"])) {
      if (!PluginOcsinventoryngOcsServer::checkOCSconnection($_SESSION["plugin_ocsinventoryng_ocsservers_id"])) {
         PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_import_statistics"]);
         $_SESSION["ocs_import"]["id"] = [];

         Html::redirect($_SERVER['PHP_SELF']);

      } else {
         $_SESSION["ocs_import"]["connection"] = true;
      }
   }

   if ($count = count($_SESSION["ocs_import"]['computers'])) {

      $percent = min(100,
                     round(100 * ($_SESSION["ocs_import_count"] - $count) / $_SESSION["ocs_import_count"],
                           0));

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

      $action         = PluginOcsinventoryngOcsProcess::processComputer($process_params);

      PluginOcsinventoryngOcsProcess::manageImportStatistics($_SESSION["ocs_import"]['statistics'],
                                                             $action['status']);
      Html::displayProgressBar(400, $percent);

      Html::redirect($_SERVER['PHP_SELF']);

   }
}

if (!isset($_POST["import_ok"])) {

   $ocsClient   = PluginOcsinventoryngOcsServer::getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
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
                   'entities_id'                         => $_SESSION['glpiactiveentities']];
   PluginOcsinventoryngOcsServer::showComputersToAdd($show_params);

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
   Html::redirect($_SERVER['PHP_SELF']);
}

Html::footer();
