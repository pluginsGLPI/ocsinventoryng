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

Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "link");

$CFG_GLPI["use_ajax"] = 1;

//First time this screen is displayed : set the import mode to 'basic'
if (!isset($_SESSION["change_import_mode"])) {
   $_SESSION["change_import_mode"] = false;
}

//Changing the import mode
if (isset($_POST["change_import_mode"])) {
   if ('id' == "false") {
      $_SESSION["change_import_mode"] = false;
   } else {
      $_SESSION["change_import_mode"] = true;
   }
}

if (isset($_SESSION["ocs_link"])) {
   if ($count = count($_SESSION["ocs_link"])) {
      $percent = min(100,
                     round(100*($_SESSION["ocs_link_count"]-$count)/$_SESSION["ocs_link_count"], 0));

      Html::displayProgressBar(400,$percent);

      $key = array_pop($_SESSION["ocs_link"]);
      PluginOcsinventoryngOcsServer::linkComputer($key["ocsid"],
                                                  $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                                                  $key["computers_id"]);
      Html::redirect($_SERVER['PHP_SELF']);
   } else {
      Html::displayProgressBar(400,100);

      unset($_SESSION["ocs_link"]);
      echo "<div class='center b'>".__('Successful importation')."<br>";
      echo "<a href='".$_SERVER['PHP_SELF']."'>".__('Back')."</a></div>";
   }
}

if (!isset($_POST["import_ok"])) {
   if (!isset($_GET['check'])) {
      $_GET['check'] = 'all';
   }
   if (!isset($_GET['start'])) {
      $_GET['start'] = 0;
   }
   PluginOcsinventoryngOcsServer::manageDeleted($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
   PluginOcsinventoryngOcsServer::showComputersToAdd($_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                                                     $_SESSION["change_import_mode"], $_GET['check'],
                                                     $_GET['start'], $_SESSION['glpiactiveentities'],
                                                     1);

} else {
   if (isset($_POST['tolink']) && count($_POST['tolink']) >0) {
      $_SESSION["ocs_link_count"] = 0;

      foreach ($_POST['tolink'] as $ocsid => $computers_id) {
         if ($computers_id >0) {
            $_SESSION["ocs_link"][] = array('ocsid'        => $ocsid,
                                            'computers_id' => $computers_id);
            $_SESSION["ocs_link_count"]++;
         }
      }
   }
   Html::redirect($_SERVER['PHP_SELF']);
}

Html::footer();
?>