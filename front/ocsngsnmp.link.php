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

Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "synclink");

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

if (isset($_SESSION["ocs_linksnmp"]["id"])) {
   if ($count = count($_SESSION["ocs_linksnmp"]["id"])) {
      $percent = min(100,
         round(100 * ($_SESSION["ocs_linksnmp_count"] - $count) / $_SESSION["ocs_linksnmp_count"], 0));

      Html::displayProgressBar(400, $percent);

      $key = array_pop($_SESSION["ocs_linksnmp"]["id"]);

      if (isset($_SESSION["ocs_linksnmp"]["items"][$key])) {
         $params['items_id'] = $_SESSION["ocs_linksnmp"]["items"][$key];
      } else {
         $params['items_id'] = -1;
      }

      if (isset($_SESSION["ocs_linksnmp"]["itemtype"][$key])) {
         $params['itemtype'] = $_SESSION["ocs_linksnmp"]["itemtype"][$key];
      } else {
         $params['itemtype'] = -1;
      }

      PluginOcsinventoryngSnmpOcslink::linkSnmpDevice($key,
         $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
         $params);
      Html::redirect($_SERVER['PHP_SELF']);
   } else {
      Html::displayProgressBar(400, 100);

      unset($_SESSION["ocs_link"]);
      echo "<div class='center b'>" . __('Successful link', 'ocsinventoryng') . "<br>";
      echo "<a href='" . $_SERVER['PHP_SELF'] . "'>" . __('Back') . "</a></div>";
   }
}

if (!isset($_POST["import_ok"])) {
   if (!isset($_GET['check'])) {
      $_GET['check'] = 'all';
   }
   if (!isset($_GET['start'])) {
      $_GET['start'] = 0;
   }
   if (isset($_SESSION["ocs_linksnmp"])) {
      unset($_SESSION["ocs_linksnmp"]);
   }

   $values = $_GET;
   if (isset($_POST["search"])) {
      $values = $_POST;
   }
   $values['plugin_ocsinventoryng_ocsservers_id'] = $_SESSION["plugin_ocsinventoryng_ocsservers_id"];
   //$values['change_import_mode'] = $_SESSION["change_import_mode"];
   //$values['glpiactiveentities'] = $_SESSION["glpiactiveentities"];
   $values['tolinked'] = 1;
   PluginOcsinventoryngSnmpOcslink::searchForm($values);
   PluginOcsinventoryngSnmpOcslink::showSnmpDeviceToAdd($values);


} else {


   if (isset($_POST['tolink_items']) && count($_POST['tolink_items']) > 0) {
      $_SESSION["ocs_linksnmp_count"] = 0;

      foreach ($_POST['tolink_items'] as $key => $val) {

         if ($val) {
            $_SESSION["ocs_linksnmp"]["id"][] = $key;
            $_SESSION["ocs_linksnmp"]["items"][$key] = $_POST['tolink_items'][$key];
         }
         $_SESSION["ocs_linksnmp_count"]++;
      }
      foreach ($_POST['tolink_itemtype'] as $key => $val) {

         if ($val) {
            $_SESSION["ocs_linksnmp"]["itemtype"][$key] = $_POST['tolink_itemtype'][$key];
         }
      }
   }
   Html::redirect($_SERVER['PHP_SELF']);
}

Html::footer();
