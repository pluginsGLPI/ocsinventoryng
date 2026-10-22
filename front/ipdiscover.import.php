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


use GlpiPlugin\Ocsinventoryng\IpdiscoverOcslink;
use GlpiPlugin\Ocsinventoryng\Menu;
use GlpiPlugin\Ocsinventoryng\OcsServer;
use GlpiPlugin\Ocsinventoryng\OcsProcess;

Session::checkRight("plugin_ocsinventoryng", UPDATE);

// The OCS server of the session, chosen on front/ocsng.php: still allowed for the active profile
if (OcsServer::checkSessionServer() <= 0) {
    throw new \Glpi\Exception\Http\AccessDeniedHttpException();
}


// Every value of the request reaches queries, links or the page: checked once here.
// The subnet and the IP addresses, the status and the action of the lists
$_GET['action'] = IpdiscoverOcslink::checkAction($_GET['action'] ?? 'import');
if (isset($_GET["ip"])) {
   $_GET["ip"] = IpdiscoverOcslink::checkIp($_GET["ip"]);
}
if (isset($_POST["ip"])) {
   $_POST["ip"] = IpdiscoverOcslink::checkIp($_POST["ip"]);
}
if (isset($_GET["status"])) {
   $_GET["status"] = IpdiscoverOcslink::checkStatus($_GET["status"]);
}
if (isset($_POST["subnet"])) {
   $_POST["subnet"] = is_string($_POST["subnet"]) && filter_var($_POST["subnet"], FILTER_VALIDATE_IP) !== false
      ? $_POST["subnet"] : '';
}
// The lists of the forms, keyed by MAC address: arrays of scalars, entities and items as
// integers (types, entities and MAC addresses are checked again by the import and link code)
foreach (['mactoimport', 'glpiitemstype', 'itemsname', 'itemsdescription', 'itemsip', 'ocsitemstype',
          'entities', 'tolink_itemtype', 'tolink_items'] as $list) {
   if (isset($_POST[$list])) {
      $_POST[$list] = is_array($_POST[$list])
         ? array_map(static fn($value) => is_scalar($value) ? (string) $value : '', $_POST[$list])
         : [];
   }
}
foreach (['entities', 'tolink_items'] as $list) {
   if (isset($_POST[$list])) {
      $_POST[$list] = array_map('intval', $_POST[$list]);
   }
}
// The "back" values: an IP address and a status
if (isset($_GET["b"])) {
   $_GET["b"] = [
      IpdiscoverOcslink::checkIp($_GET["b"][0] ?? ''),
      IpdiscoverOcslink::checkStatus($_GET["b"][1] ?? ''),
   ];
}

Html::header('OCS Inventory NG', '', "tools", Menu::class, "importipdiscover");

if (Plugin::isPluginActive("ocsinventoryng")) {
   $ip = new IpdiscoverOcslink();

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

      $subnet           = IpdiscoverOcslink::getSubnetIDbyIP($ipAdress);
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
            $percent = min(100, round(100 * (sizeof($macAdresses) - sizeof($ipObjects)) / sizeof($macAdresses), 0, PHP_ROUND_HALF_UP));

            $action = $ip->processIpDiscover($ipObject, $_SESSION["plugin_ocsinventoryng_ocsservers_id"], $_POST["subnet"]);
            OcsProcess::manageImportStatistics($_SESSION["ocs_importipdiscover"]['statistics'], $action['status'], false, true);
             OcsProcess::showStatistics($_SESSION["ocs_importipdiscover"]['statistics'], false, false, true);
             Html::getProgressBar($percent);
            $_SESSION["ocs_importipdiscover"]["datas"]["ipObjects"]   = $ipObjects;
            $_SESSION["ocs_importipdiscover"]["datas"]["b"]           = $_GET["b"];
            $_SESSION["ocs_importipdiscover"]["datas"]["macAdresses"] = $macAdresses;
            Html::redirect($_SERVER['PHP_SELF']);
         }
      } else if (isset($_SESSION["ocs_importipdiscover"]["datas"])) {
         $action = null;


         while ($ipObject = array_pop($_SESSION["ocs_importipdiscover"]["datas"]["ipObjects"])) {
            $percent = min(100, round(100 * (sizeof($_SESSION["ocs_importipdiscover"]["datas"]["macAdresses"]) - sizeof($_SESSION["ocs_importipdiscover"]["datas"]["ipObjects"])) / sizeof($_SESSION["ocs_importipdiscover"]["datas"]["macAdresses"]), 0, PHP_ROUND_HALF_UP));
            $action  = $ip->processIpDiscover($ipObject, $_SESSION["plugin_ocsinventoryng_ocsservers_id"], $_POST["subnet"]);
             OcsProcess::manageImportStatistics($_SESSION["ocs_importipdiscover"]['statistics'], $action['status'], false, true);
             OcsProcess::showStatistics($_SESSION["ocs_importipdiscover"]['statistics'], false, false, true);
             Html::getProgressBar($percent);
            Html::redirect($_SERVER['PHP_SELF']);
         }
          OcsProcess::showStatistics($_SESSION["ocs_importipdiscover"]['statistics'], false, false, true);
         if (isset($_GET["b"])) {
            $b        = $_GET["b"];
            $ipAdress = $b[0];
            $status   = $b[1];
            echo "<div class='center b'><br>";
            echo "<a href='" . htmlescape($_SERVER['PHP_SELF'] . "?" . http_build_query(['ip' => $ipAdress, 'status' => $status])) . "'>" . __s('Back') . "</a></div>";
         }
         if (isset($_SESSION["ocs_importipdiscover"]["datas"]["b"])) {
            $b        = [
               IpdiscoverOcslink::checkIp($_SESSION["ocs_importipdiscover"]["datas"]["b"][0] ?? ''),
               IpdiscoverOcslink::checkStatus($_SESSION["ocs_importipdiscover"]["datas"]["b"][1] ?? ''),
            ];
            $ipAdress = $b[0];
            $status   = $b[1];
            echo "<div class='center b'><br>";
            echo "<a href='" . htmlescape($_SERVER['PHP_SELF'] . "?" . http_build_query(['ip' => $ipAdress, 'status' => $status])) . "'>" . __s('Back') . "</a></div>";
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

            Html::redirect($_SERVER['PHP_SELF'] . "?" . http_build_query(['ip' => $ipAdress, 'status' => $status]));
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
            $percent = min(100, round(100 * (sizeof($macAdresses) - sizeof($ipObjects)) / sizeof($macAdresses), 0, PHP_ROUND_HALF_UP));

            $action = $ip->processIpDiscover($ipObject, $_SESSION["plugin_ocsinventoryng_ocsservers_id"], $_POST["subnet"]);
             OcsProcess::manageImportStatistics($_SESSION["ocs_importipdiscover"]['statistics'], $action['status'], false, true);
             OcsProcess::showStatistics($_SESSION["ocs_importipdiscover"]['statistics'], false, false, true);
             Html::getProgressBar($percent);
            $_SESSION["ocs_importipdiscover"]["datas"]["ipObjects"]   = $ipObjects;
            $_SESSION["ocs_importipdiscover"]["datas"]["b"]           = $_GET["b"];
            $_SESSION["ocs_importipdiscover"]["datas"]["macAdresses"] = $macAdresses;
            Html::redirect($_SERVER['PHP_SELF']);
         }
      } else if (isset($_SESSION["ocs_importipdiscover"]["datas"])) {
         $action = null;


         while ($ipObject = array_pop($_SESSION["ocs_importipdiscover"]["datas"]["ipObjects"])) {
            $percent = min(100, round(100 * (sizeof($_SESSION["ocs_importipdiscover"]["datas"]["macAdresses"]) - sizeof($_SESSION["ocs_importipdiscover"]["datas"]["ipObjects"])) / sizeof($_SESSION["ocs_importipdiscover"]["datas"]["macAdresses"]), 0, PHP_ROUND_HALF_UP));
            $action  = $ip->processIpDiscover($ipObject, $_SESSION["plugin_ocsinventoryng_ocsservers_id"], $_POST["subnet"]);
             OcsProcess::manageImportStatistics($_SESSION["ocs_importipdiscover"]['statistics'], $action['status'], false, true);
             OcsProcess::showStatistics($_SESSION["ocs_importipdiscover"]['statistics'], false, false, true);
             Html::getProgressBar($percent);
            Html::redirect($_SERVER['PHP_SELF']);
         }
          OcsProcess::showStatistics($_SESSION["ocs_importipdiscover"]['statistics'], false, false, true);
         if (isset($_GET["b"])) {
            $b        = $_GET["b"];
            $ipAdress = $b[0];
            $status   = $b[1];
            echo "<div class='center b'><br>";
            echo "<a href='" . htmlescape($_SERVER['PHP_SELF'] . "?" . http_build_query(['ip' => $ipAdress, 'status' => $status])) . "'>" . __s('Back') . "</a></div>";
         }
         if (isset($_SESSION["ocs_importipdiscover"]["datas"]["b"])) {
            $b        = [
               IpdiscoverOcslink::checkIp($_SESSION["ocs_importipdiscover"]["datas"]["b"][0] ?? ''),
               IpdiscoverOcslink::checkStatus($_SESSION["ocs_importipdiscover"]["datas"]["b"][1] ?? ''),
            ];
            $ipAdress = $b[0];
            $status   = $b[1];
            echo "<div class='center b'><br>";
            echo "<a href='" . htmlescape($_SERVER['PHP_SELF'] . "?" . http_build_query(['ip' => $ipAdress, 'status' => $status])) . "'>" . __s('Back') . "</a></div>";
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

            Html::redirect($_SERVER['PHP_SELF'] . "?" . http_build_query(['ip' => $ipAdress, 'status' => $status]));
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

      if (is_array($_POST["mactoimport"] ?? null)
          && sizeof($_POST["mactoimport"]) > 0
      ) {
         $macAdresses = $_POST["mactoimport"];
         // The subnet of the list (validated above): no MAC address of another subnet
         $ip->deleteMACFromOCS($_SESSION["plugin_ocsinventoryng_ocsservers_id"], $macAdresses, (string) ($_POST["subnet"] ?? ''));
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
