<?php
/*
 * @version $Id: HEADER 15930 2012-12-15 11:10:55Z tsmr $
-------------------------------------------------------------------------
Ocsinventoryng plugin for GLPI
Copyright (C) 2012-2013 by the ocsinventoryng plugin Development Team.

https://forge.indepnet.net/projects/ocsinventoryng
-------------------------------------------------------------------------

LICENSE

This file is part of ocsinventoryng.

Ocsinventoryng plugin is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

Ocsinventoryng plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with ocsinventoryng. If not, see <http://www.gnu.org/licenses/>.
-------------------------------------------------------------------------- */

include ('../../../inc/includes.php');

Session::checkRight("plugin_ocsinventoryng", UPDATE);

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
      $percent = min(100,
                     round(100*($_SESSION["ocs_import_count"]-$count)/$_SESSION["ocs_import_count"],
                           0));

      $key = array_pop($_SESSION["ocs_import"]["id"]);

      if (isset($_SESSION["ocs_import"]["entities_id"][$key])) {
         $entity = $_SESSION["ocs_import"]["entities_id"][$key];
      } else {
         $entity = -1;
      }

      if (isset($_SESSION["ocs_import"]["locations_id"][$key])) {
         $location = $_SESSION["ocs_import"]["locations_id"][$key];
      } else {
         $location = -1;
      }

      $conf   = PluginOcsinventoryngOcsServer::getConfig($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
      $action = PluginOcsinventoryngOcsServer::processComputer($key,
                                                               $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                                                               0, $entity, $location);
      PluginOcsinventoryngOcsServer::manageImportStatistics($_SESSION["ocs_import"]['statistics'],
                                                            $action['status']);
      PluginOcsinventoryngOcsServer::showStatistics($_SESSION["ocs_import"]['statistics']);
      Html::displayProgressBar(400, $percent);
      Html::redirect($_SERVER['PHP_SELF']);
   } else {
      //displayProgressBar(400, 100);
      if (isset($_SESSION["ocs_import"]['statistics'])) {
         PluginOcsinventoryngOcsServer::showStatistics($_SESSION["ocs_import"]['statistics'],true);
      } else {
         echo "<div class='center b red'>";
         _e('No import: the plugin will not import these elements', 'ocsinventoryng');
         echo "</div>";
      }
      unset($_SESSION["ocs_import"]);

      echo "<div class='center b'><br>";
      echo "<a href='".$_SERVER['PHP_SELF']."'>".__('Back')."</a></div>";
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
   PluginOcsinventoryngOcsServer::manageDeleted($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
   if ($display_list) {
      PluginOcsinventoryngOcsServer::showComputersToAdd($_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                                                        $_SESSION["change_import_mode"],
                                                        $_GET['check'], $_GET['start'],
                                                        $_SESSION['glpiactiveentities']);
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
            if (isset($_POST['toimport_locations'])) {
               $_SESSION["ocs_import"]["locations_id"][$key] = $_POST['toimport_locations'][$key];
            }
            $_SESSION["ocs_import_count"]++;
         }
      }
   }
   Html::redirect($_SERVER['PHP_SELF']);
}

Html::footer();
?>