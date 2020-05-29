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

Session::checkRight("plugin_ocsinventoryng_sync", READ);

Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "sync");


$display_list = true;

if (isset($_SESSION["ocs_update"]['computers'])) {
   if ($count = count($_SESSION["ocs_update"]['computers'])) {
      $percent = min(100,
                     round(100 * ($_SESSION["ocs_update_count"] - $count) / $_SESSION["ocs_update_count"],
                           0));


      $key         = array_pop($_SESSION["ocs_update"]['computers']);
      $cfg_ocs     = PluginOcsinventoryngOcsServer::getConfig($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
      $sync_params = ['ID'                                  => $key,
                      'plugin_ocsinventoryng_ocsservers_id' => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                      'cfg_ocs'                             => $cfg_ocs,
                      'force'                               => 0];
      $action      = PluginOcsinventoryngOcsProcess::synchronizeComputer($sync_params);
      PluginOcsinventoryngOcsProcess::manageImportStatistics($_SESSION["ocs_update"]['statistics'],
                                                             $action['status']);
      PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_update"]['statistics']);
      Html::displayProgressBar(400, $percent);

      Html::redirect($_SERVER['PHP_SELF']);

   } else {

      if (isset($_SESSION["ocs_update"]['statistics'])) {
         PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_update"]['statistics'], true);
      } else {
         echo "<div class='center b red'>";
         echo __('No synchronization: the plugin will not synchronize these elements', 'ocsinventoryng');
         echo "</div>";
      }

      unset($_SESSION["ocs_update"]);
      $display_list = false;
      echo "<div class='center b'><br>";
      echo "<a href='" . $_SERVER['PHP_SELF'] . "'>" . __('Back') . "</a></div>";
   }
}

if (!isset($_POST["update_ok"])) {
   if (!isset($_GET['check'])) {
      $_GET['check'] = 'all';
   }
   if (!isset($_GET['start'])) {
      $_GET['start'] = 0;
   }
   if (isset($_SESSION["plugin_ocsinventoryng_ocsservers_id"])) {
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
      $deleted_pcs   = $ocsClient->getTotalDeletedComputers();
      if ($deleted_pcs > 0) {
         echo "<div class='center'>";
         echo "<span style='color:firebrick'>";
         echo "<i class='fas fa-exclamation-triangle fa-5x'></i><br><br>";
         echo __('You have', 'ocsinventoryng')." ". $deleted_pcs . " " . __('deleted computers into OCS Inventory NG', 'ocsinventoryng');
         echo "<br>";
         echo __('Please clean them before import or synchronize computers', 'ocsinventoryng');
         echo "</span></div>";
      }
      if ($display_list) {
         $show_params = ['plugin_ocsinventoryng_ocsservers_id' => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                         'check'                               => $_GET['check'],
                         'start'                               => $_GET['start']];
         PluginOcsinventoryngOcsServer::showComputersToSynchronize($show_params);
      }
   }
} else {
   if (count($_POST['toupdate']) > 0) {
      $_SESSION["ocs_update_count"] = 0;

      foreach ($_POST['toupdate'] as $key => $val) {
         if ($val == "on") {
            $_SESSION["ocs_update"]['computers'][] = $key;
            $_SESSION["ocs_update_count"]++;
         }
      }
   }
   Html::redirect($_SERVER['PHP_SELF']);
}

Html::footer();
