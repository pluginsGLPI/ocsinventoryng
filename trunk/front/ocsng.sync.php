<?php
/*
 * @version $Id: ocsng.sync.php 14685 2011-06-11 06:40:30Z remi $
 -------------------------------------------------------------------------
 ocinventoryng - TreeView browser plugin for GLPI
 Copyright (C) 2012 by the ocinventoryng Development Team.

 https://forge.indepnet.net/projects/ocinventoryng
 -------------------------------------------------------------------------

 LICENSE

 This file is part of ocinventoryng.

 ocinventoryng is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 ocinventoryng is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with ocinventoryng; If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', '../../..');
}
include (GLPI_ROOT . "/inc/includes.php");

plugin_ocsinventoryng_checkRight("ocsng","w");

Html::header('OCS Inventory NG', "", "plugins", "ocsinventoryng");

$display_list = true;

if (isset($_SESSION["ocs_update"]['computers'])) {
   if ($count = count($_SESSION["ocs_update"]['computers'])) {
      $percent = min(100,
                     round(100*($_SESSION["ocs_update_count"]-$count)/$_SESSION["ocs_update_count"],
                           0));


      $key    = array_pop($_SESSION["ocs_update"]['computers']);
      $action = PluginOcsinventoryngOcsServer::updateComputer($key,
                                                              $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                                                              2);
      PluginOcsinventoryngOcsServer::manageImportStatistics($_SESSION["ocs_update"]['statistics'],
                                                            $action['status']);
      PluginOcsinventoryngOcsServer::showStatistics($_SESSION["ocs_update"]['statistics']);
      displayProgressBar(400, $percent);

      Html::back();

   } else {
      PluginOcsinventoryngOcsServer::showStatistics($_SESSION["ocs_update"]['statistics'], true);
      unset($_SESSION["ocs_update"]);
      $display_list = false;
      echo "<div class='center b'><br>";
      echo "<a href='".$_SERVER['PHP_SELF']."'>".__('Back')."</a></div>";
   }
}

if (!isset($_POST["update_ok"])) {
   if (!isset($_GET['check'])) {
      $_GET['check'] = 'all';
   }
   if (!isset($_GET['start'])) {
      $_GET['start'] = 0;
   }
   PluginOcsinventoryngOcsServer::manageDeleted($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
   if ($display_list) {
      PluginOcsinventoryngOcsServer::showComputersToUpdate($_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                                                           $_GET['check'], $_GET['start']);
   }

} else {
   if (count($_POST['toupdate']) >0) {
      $_SESSION["ocs_update_count"] = 0;

      foreach ($_POST['toupdate'] as $key => $val) {
         if ($val == "on") {
            $_SESSION["ocs_update"]['computers'][] = $key;
            $_SESSION["ocs_update_count"]++;
         }
      }
   }
   Html::back();
}

Html::footer();
?>