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

Session::checkRight("plugin_ocsinventoryng", UPDATE);

Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "importipdiscover");

$plugin = new Plugin();
if ($plugin->isActivated("ocsinventoryng")) {
   $ip = new PluginOcsinventoryngIpdiscoverOcslink();

   if (!isset($_GET['action'])) {
      $_GET['action'] = "import";
   }

   if (isset($_GET["ip"])
       || isset($_POST["ip"])
   ) {
      $ocsServerId   = $_SESSION["plugin_ocsinventoryng_ocsservers_id"];
      $status        = $_GET["status"];
      $glpiListLimit = $_SESSION["glpilist_limit"];
      $ipAdress      = "";
      if (isset($_GET["ip"])) {
         $ipAdress = $_GET["ip"];
      } else {
         $ipAdress = $_POST["ip"];
      }

      $subnet           = PluginOcsinventoryngIpdiscoverOcslink::getSubnetIDbyIP($ipAdress);
      $hardware         = [];
      $knownMacAdresses = $ip->getKnownMacAdresseFromGlpi();
      if (isset($status)) {
         $hardware = $ip->getHardware($ipAdress, $ocsServerId, $status, $knownMacAdresses);
         $lim      = count($hardware);
         if ($lim > $glpiListLimit) {
            if (isset($_GET["start"])) {
               $ip->showHardware($hardware, $glpiListLimit, intval($_GET["start"]), $ipAdress, $status, $subnet, $_GET['action']);
            } else {
               $ip->showHardware($hardware, $glpiListLimit, 0, $ipAdress, $status, $subnet, $_GET['action']);
            }
         } else {
            if (isset($_GET["start"])) {
               $ip->showHardware($hardware, $lim, intval($_GET["start"]), $ipAdress, $status, $subnet, $_GET['action']);
            } else {
               $ip->showHardware($hardware, $lim, 0, $ipAdress, $status, $subnet, $_GET['action']);
            }
         }
      }
   }

   if (isset($_POST["Import"])
       || isset($_SESSION["ocs_importipdiscover"]["datas"])
   ) {
      $percent = 0;
      if (isset($_POST["Import"])
          && isset($_POST["mactoimport"])
          && sizeof($_POST["mactoimport"]) > 0
      ) {
         $macAdresses      = $_POST["mactoimport"];
         $itemsTypes       = $_POST["glpiitemstype"];
         $itemsNames       = $_POST["itemsname"];
         $itemsDescription = $_POST["itemsdescription"];
         $itemsIp          = $_POST["itemsip"];
         $entities         = [];

         if (isset($_POST["entities"])) {
            $entities = $_POST["entities"];
         }
         $ipObjects = $ip->getIpDiscoverobject($macAdresses, $entities, $itemsTypes, $itemsNames, $itemsDescription, $itemsIp);
         $action    = null;
         while ($ipObject = array_pop($ipObjects)) {
            $percent = min(100, round(100 * (sizeof($macAdresses) - sizeof($ipObjects)) / sizeof($macAdresses), 0));

            $action = $ip->processIpDiscover($ipObject, $_SESSION["plugin_ocsinventoryng_ocsservers_id"], $_POST["subnet"]);
            PluginOcsinventoryngOcsProcess::manageImportStatistics($_SESSION["ocs_importipdiscover"]['statistics'], $action['status'], false, true);
            PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_importipdiscover"]['statistics'], false, false, true);
            Html::displayProgressBar(400, $percent);
            $_SESSION["ocs_importipdiscover"]["datas"]["ipObjects"]   = $ipObjects;
            $_SESSION["ocs_importipdiscover"]["datas"]["b"]           = $_GET["b"];
            $_SESSION["ocs_importipdiscover"]["datas"]["macAdresses"] = $macAdresses;
            Html::redirect($_SERVER['PHP_SELF']);
         }
      } else if (isset($_SESSION["ocs_importipdiscover"]["datas"])) {
         $action = null;


         while ($ipObject = array_pop($_SESSION["ocs_importipdiscover"]["datas"]["ipObjects"])) {
            $percent = min(100, round(100 * (sizeof($_SESSION["ocs_importipdiscover"]["datas"]["macAdresses"]) - sizeof($_SESSION["ocs_importipdiscover"]["datas"]["ipObjects"])) / sizeof($_SESSION["ocs_importipdiscover"]["datas"]["macAdresses"]), 0));
            $action  = $ip->processIpDiscover($ipObject, $_SESSION["plugin_ocsinventoryng_ocsservers_id"], $_POST["subnet"]);
            PluginOcsinventoryngOcsProcess::manageImportStatistics($_SESSION["ocs_importipdiscover"]['statistics'], $action['status'], false, true);
            PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_importipdiscover"]['statistics'], false, false, true);
            Html::displayProgressBar(400, $percent);
            Html::redirect($_SERVER['PHP_SELF']);
         }
         PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_importipdiscover"]['statistics'], false, false, true);
         if (isset($_GET["b"])) {
            $b        = $_GET["b"];
            $ipAdress = $b[0];
            $status   = $b[1];
            echo "<div class='center b'><br>";
            echo "<a href='" . $_SERVER['PHP_SELF'] . "?ip=$ipAdress&status=$status'>" . __('Back') . "</a></div>";
         }
         if (isset($_SESSION["ocs_importipdiscover"]["datas"]["b"])) {
            $b        = $_SESSION["ocs_importipdiscover"]["datas"]["b"];
            $ipAdress = $b[0];
            $status   = $b[1];
            echo "<div class='center b'><br>";
            echo "<a href='" . $_SERVER['PHP_SELF'] . "?ip=$ipAdress&status=$status'>" . __('Back') . "</a></div>";
         }

         if (isset($_SESSION["ocs_importipdiscover"]["datas"])) {
            unset($_SESSION["ocs_importipdiscover"]["datas"]);
         }

         if (isset($_SESSION["ocs_importipdiscover"]['statistics'])) {
            unset($_SESSION["ocs_importipdiscover"]['statistics']);
         }
      } else {
         if (isset($_GET["b"])) {
            $b        = $_GET["b"];
            $ipAdress = $b[0];
            $status   = $b[1];

            Html::redirect($_SERVER['PHP_SELF'] . "?ip=$ipAdress&status=$status");
         }
      }
   } else if (isset($_POST["IdentifyAndImport"])
              || isset($_SESSION["ocs_importipdiscover"]["datas"])
   ) {

      $percent = 0;
      if (isset($_POST["IdentifyAndImport"])
          && isset($_POST["mactoimport"])
          && sizeof($_POST["mactoimport"]) > 0
      ) {
         $macAdresses      = $_POST["mactoimport"];
         $glpiItemsTypes   = $_POST["glpiitemstype"];
         $itemsNames       = $_POST["itemsname"];
         $itemsDescription = $_POST["itemsdescription"];
         $ocsItemstypes    = $_POST["ocsitemstype"];
         $itemsIp          = $_POST["itemsip"];
         $entities         = [];

         if (isset($_POST["entities"])) {
            $entities = $_POST["entities"];
         }
         $ipObjects = $ip->getIpDiscoverobject($macAdresses, $entities, $glpiItemsTypes, $itemsNames, $itemsDescription, $itemsIp, $ocsItemstypes);
         $action    = null;
         while ($ipObject = array_pop($ipObjects)) {
            $percent = min(100, round(100 * (sizeof($macAdresses) - sizeof($ipObjects)) / sizeof($macAdresses), 0));

            $action = $ip->processIpDiscover($ipObject, $_SESSION["plugin_ocsinventoryng_ocsservers_id"], $_POST["subnet"]);
            PluginOcsinventoryngOcsProcess::manageImportStatistics($_SESSION["ocs_importipdiscover"]['statistics'], $action['status'], false, true);
            PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_importipdiscover"]['statistics'], false, false, true);
            Html::displayProgressBar(400, $percent);
            $_SESSION["ocs_importipdiscover"]["datas"]["ipObjects"]   = $ipObjects;
            $_SESSION["ocs_importipdiscover"]["datas"]["b"]           = $_GET["b"];
            $_SESSION["ocs_importipdiscover"]["datas"]["macAdresses"] = $macAdresses;
            Html::redirect($_SERVER['PHP_SELF']);
         }
      } else if (isset($_SESSION["ocs_importipdiscover"]["datas"])) {
         $action = null;


         while ($ipObject = array_pop($_SESSION["ocs_importipdiscover"]["datas"]["ipObjects"])) {
            $percent = min(100, round(100 * (sizeof($_SESSION["ocs_importipdiscover"]["datas"]["macAdresses"]) - sizeof($_SESSION["ocs_importipdiscover"]["datas"]["ipObjects"])) / sizeof($_SESSION["ocs_importipdiscover"]["datas"]["macAdresses"]), 0));
            $action  = $ip->processIpDiscover($ipObject, $_SESSION["plugin_ocsinventoryng_ocsservers_id"], $_POST["subnet"]);
            PluginOcsinventoryngOcsProcess::manageImportStatistics($_SESSION["ocs_importipdiscover"]['statistics'], $action['status'], false, true);
            PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_importipdiscover"]['statistics'], false, false, true);
            Html::displayProgressBar(400, $percent);
            Html::redirect($_SERVER['PHP_SELF']);
         }
         PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_importipdiscover"]['statistics'], false, false, true);
         if (isset($_GET["b"])) {
            $b        = $_GET["b"];
            $ipAdress = $b[0];
            $status   = $b[1];
            echo "<div class='center b'><br>";
            echo "<a href='" . $_SERVER['PHP_SELF'] . "?ip=$ipAdress&status=$status'>" . __('Back') . "</a></div>";
         }
         if (isset($_SESSION["ocs_importipdiscover"]["datas"]["b"])) {
            $b        = $_SESSION["ocs_importipdiscover"]["datas"]["b"];
            $ipAdress = $b[0];
            $status   = $b[1];
            echo "<div class='center b'><br>";
            echo "<a href='" . $_SERVER['PHP_SELF'] . "?ip=$ipAdress&status=$status'>" . __('Back') . "</a></div>";
         }

         if (isset($_SESSION["ocs_importipdiscover"]["datas"])) {
            unset($_SESSION["ocs_importipdiscover"]["datas"]);
         }

         if (isset($_SESSION["ocs_importipdiscover"]['statistics'])) {
            unset($_SESSION["ocs_importipdiscover"]['statistics']);
         }
      } else {
         if (isset($_GET["b"])) {
            $b        = $_GET["b"];
            $ipAdress = $b[0];
            $status   = $b[1];

            Html::redirect($_SERVER['PHP_SELF'] . "?ip=$ipAdress&status=$status");
         }
      }
   } else if (isset($_POST["Link"])) {

      if ((isset($_POST["tolink_itemtype"])
           && sizeof($_POST["tolink_itemtype"]) > 0)
          && (isset($_POST["tolink_items"])
              && sizeof($_POST["tolink_items"]) > 0)
      ) {
         $itemtypes        = $_POST["tolink_itemtype"];
         $items_id         = $_POST["tolink_items"];
         $macAdresses      = $_POST["mactoimport"];
         $itemsDescription = (isset($_POST["itemsdescription"]) ? $_POST["itemsdescription"] : []);
         $ocsItemstypes    = (isset($_POST["ocsitemstype"]) ? $_POST["ocsitemstype"] : []);

         $ip->linkIpDiscover($_SESSION["plugin_ocsinventoryng_ocsservers_id"], $itemtypes, $items_id, $macAdresses, $ocsItemstypes, $itemsDescription, $_POST["subnet"], 0);
      }
      Html::back();

   } else if (isset($_POST["IdentifyAndLink"])) {

      if ((isset($_POST["tolink_itemtype"])
           && sizeof($_POST["tolink_itemtype"]) > 0)
          && (isset($_POST["tolink_items"])
              && sizeof($_POST["tolink_items"]) > 0)
      ) {
         $itemtypes        = $_POST["tolink_itemtype"];
         $items_id         = $_POST["tolink_items"];
         $macAdresses      = $_POST["mactoimport"];
         $itemsDescription = $_POST["itemsdescription"];
         $ocsItemstypes    = $_POST["ocsitemstype"];

         $ip->linkIpDiscover($_SESSION["plugin_ocsinventoryng_ocsservers_id"], $itemtypes, $items_id, $macAdresses, $ocsItemstypes, $itemsDescription, $_POST["subnet"], 1);
      }
      Html::back();

   } else if (isset($_POST["delete"])) {

      if (isset($_POST["mactoimport"])
          && sizeof($_POST["mactoimport"]) > 0
      ) {
         $macAdresses = $_POST["mactoimport"];
         $ip->deleteMACFromOCS($_SESSION["plugin_ocsinventoryng_ocsservers_id"], $macAdresses);
      }
      Html::back();

   } else if (isset($_POST["deletelink"])) {

      if (isset($_POST["mactoimport"])
          && sizeof($_POST["mactoimport"]) > 0
      ) {
         $ids = $_POST["mactoimport"];
         $ip->deleteLink($ids);

      }
      Html::back();

   }
}

Html::footer();
