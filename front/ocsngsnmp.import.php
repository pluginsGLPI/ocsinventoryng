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

Session::checkRight("plugin_ocsinventoryng", UPDATE);

Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "importsnmp");

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

if (isset($_SESSION["ocs_importsnmp"]["id"])) {
   if ($count = count($_SESSION["ocs_importsnmp"]["id"])) {
      if((isset($_SESSION["ocs_importsnmp"]["connection"]) && $_SESSION["ocs_importsnmp"]["connection"] == false ) || !isset($_SESSION["ocs_importsnmp"]["connection"]) ){
         if(!PluginOcsinventoryngOcsServer::checkOCSconnection($_SESSION["plugin_ocsinventoryng_ocsservers_id"])){
            PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_importsnmp"]['statistics']);
            $_SESSION["ocs_importsnmp"]["id"] = [];

            Html::redirect($_SERVER['PHP_SELF']);
         }else{
            $_SESSION["ocs_importsnmp"]["connection"] = true;
         }
      }
      $percent = min(100,
         round(100 * ($_SESSION["ocs_importsnmp_count"] - $count) / $_SESSION["ocs_importsnmp_count"],
            0));

      $key = array_pop($_SESSION["ocs_importsnmp"]["id"]);

      if (isset($_SESSION["ocs_importsnmp"]["entities_id"][$key])) {
         $params['entity'] = $_SESSION["ocs_importsnmp"]["entities_id"][$key];
      } else {
         $params['entity'] = -1;
      }

      if (isset($_SESSION["ocs_importsnmp"]["itemtype"][$key])) {
         $params['itemtype'] = $_SESSION["ocs_importsnmp"]["itemtype"][$key];
      } else {
         $params['itemtype'] = -1;
      }

      $conf = PluginOcsinventoryngOcsServer::getConfig($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
      $action = PluginOcsinventoryngSnmpOcslink::processSnmp($key,
         $_SESSION["plugin_ocsinventoryng_ocsservers_id"], $params);
      PluginOcsinventoryngOcsProcess::manageImportStatistics($_SESSION["ocs_importsnmp"]['statistics'],
         $action['status'], true);
      PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_importsnmp"]['statistics'], false, true);
      Html::displayProgressBar(400, $percent);
      Html::redirect($_SERVER['PHP_SELF']);
   } else {
      //displayProgressBar(400, 100);
      if (isset($_SESSION["ocs_importsnmp"]['statistics'])) {
         PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_importsnmp"]['statistics'], false, true);
      } else {
         echo "<div class='center b red'>";
         echo __('No import: the plugin will not import these elements', 'ocsinventoryng');
         echo "</div>";
      }
      unset($_SESSION["ocs_importsnmp"]);

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
   if (isset($_SESSION["ocs_importsnmp"])) {
      unset($_SESSION["ocs_importsnmp"]);
   }
   //PluginOcsinventoryngOcsProcess::manageDeleted($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
   if ($display_list) {
      $values = $_GET;
      if (isset($_POST["search"])) {
         $values = $_POST;
      }
      $values['plugin_ocsinventoryng_ocsservers_id'] = $_SESSION["plugin_ocsinventoryng_ocsservers_id"];
      //$values['change_import_mode'] = $_SESSION["change_import_mode"];
      //$values['glpiactiveentities'] = $_SESSION["glpiactiveentities"];
      $values['tolinked'] = 0;
      PluginOcsinventoryngSnmpOcslink::searchForm($values);
      PluginOcsinventoryngSnmpOcslink::showSnmpDeviceToAdd($values);
   }

} else {
   if (isset($_POST["toimport"]) && (count($_POST['toimport']) > 0)) {
      $_SESSION["ocs_importsnmp_count"] = 0;

      foreach ($_POST['toimport'] as $key => $val) {
         if ($val == "on") {
            $_SESSION["ocs_importsnmp"]["id"][] = $key;

            if (isset($_POST['toimport_entities'])) {
               $_SESSION["ocs_importsnmp"]["entities_id"][$key] = $_POST['toimport_entities'][$key];
            }

            if (isset($_POST['toimport_itemtype'])) {
               $_SESSION["ocs_importsnmp"]["itemtype"][$key] = $_POST['toimport_itemtype'][$key];
            }
            $_SESSION["ocs_importsnmp_count"]++;
         }
      }
   }
   Html::redirect($_SERVER['PHP_SELF']);
}

Html::footer();
