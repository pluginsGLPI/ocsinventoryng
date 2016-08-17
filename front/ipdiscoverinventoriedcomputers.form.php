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


include ('../../../inc/includes.php');
Session::checkRight("plugin_ocsinventoryng", UPDATE);
Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu");
//Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "ipdiscmodifynetwork");
$ip = new PluginOcsinventoryngIpDiscover();
if (isset($_GET["ip"]) || isset($_POST["ip"])) {
   $ocsServerId   = $_SESSION["plugin_ocsinventoryng_ocsservers_id"];
   $status        = $_GET["status"];
   $glpiListLimit = $_SESSION["glpilist_limit"];
   $ipAdress      = "";
   if (isset($_GET["ip"])) {
      $ipAdress = $_GET["ip"];
   } else {
      $ipAdress = $_POST["ip"];
   }
   $hardware         = array();
   $knownMacAdresses = $ip->getKnownMacAdresseFromGlpi();
   if (isset($status)) {
      $hardware = $ip->getHardware($ipAdress, $ocsServerId, $status, $knownMacAdresses);
      $lim      = count($hardware);
      if ($lim > $glpiListLimit) {
         if (isset($_GET["start"])) {
            $ip->showHardware($hardware, $glpiListLimit, intval($_GET["start"]), $ipAdress, $status);
         } else {
            $ip->showHardware($hardware, $glpiListLimit, 0, $ipAdress, $status);
         }
      } else {
         if (isset($_GET["start"])) {
            $ip->showHardware($hardware, $lim, intval($_GET["start"]), $ipAdress, $status);
         } else {
            $ip->showHardware($hardware, $lim, 0, $ipAdress, $status);
         }
      }
   }
}

if (isset($_POST["Import"])) {
   if (isset($_POST["macToImport"]) && sizeof($_POST["macToImport"]) > 0) {
      $macAdresses      = $_POST["macToImport"];
      $itemsTypes       = $_POST["itemstypes"];
      $itemsNames       = $_POST["itemsname"];
      $itemsDescription = $_POST["itemsdescription"];
      $entities         = array();
      if (isset($_POST["entities"])) {
         $entities = $_POST["entities"];
      }

      $ipObjects = $ip->getIpDiscoverobject($macAdresses, $entities, $itemsTypes, $itemsNames, $itemsDescription);
      $action    = null;
      while ($ipObject  = array_pop($ipObjects)) {
         $action = $ip->processIpDiscover($ipObject, $_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
      }
      /* $array=array();
        PluginOcsinventoryngOcsServer::manageImportStatistics($array,
        $action['status'], false);
        PluginOcsinventoryngOcsServer::showStatistics($array, false, false,true);
        echo "<a href='".$_SERVER['PHP_SELF']."'>".__('Back')."</a></div>" */
   }
}

html::footer();
?>