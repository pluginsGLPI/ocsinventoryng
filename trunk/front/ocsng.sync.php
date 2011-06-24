<?php
/*
 * @version $Id: ocsng.sync.php 14685 2011-06-11 06:40:30Z remi $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

plugin_ocsinventoryng_checkRight("ocsng","w");

commonHeader($LANG['plugin_ocsinventoryng'][0], $_SERVER['PHP_SELF'], "plugins","ocsinventoryng");

$display_list = true;

if (isset($_SESSION["ocs_update"]['computers'])) {
   if ($count = count($_SESSION["ocs_update"]['computers'])) {
      $percent = min(100,
                     round(100*($_SESSION["ocs_update_count"]-$count)/$_SESSION["ocs_update_count"],
                           0));


      $key    = array_pop($_SESSION["ocs_update"]['computers']);
      $action = PluginOcsinventoryngOcsServer::updateComputer($key, $_SESSION["plugin_ocsinventoryng_ocsservers_id"], 2);
      PluginOcsinventoryngOcsServer::manageImportStatistics($_SESSION["ocs_update"]['statistics'], $action['status']);
      PluginOcsinventoryngOcsServer::showStatistics($_SESSION["ocs_update"]['statistics']);
      displayProgressBar(400, $percent);

      glpi_header($_SERVER['PHP_SELF']);

   } else {
      PluginOcsinventoryngOcsServer::showStatistics($_SESSION["ocs_update"]['statistics'], true);
      unset($_SESSION["ocs_update"]);
      $display_list = false;
      echo "<div class='center b'><br>";
      echo "<a href='".$_SERVER['PHP_SELF']."'>".$LANG['buttons'][13]."</a></div>";
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
      PluginOcsinventoryngOcsServer::showComputersToUpdate($_SESSION["plugin_ocsinventoryng_ocsservers_id"], $_GET['check'], $_GET['start']);
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
   glpi_header($_SERVER['PHP_SELF']);
}

commonFooter();

?>