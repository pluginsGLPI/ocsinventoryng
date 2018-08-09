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

Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "syncsnmp");


$display_list = true;

if (isset($_POST["delete"])
   && isset($_POST["toupdate"])
) {
   if ($count = count($_POST["toupdate"])) {

      foreach ($_POST['toupdate'] as $key => $val) {
         if ($val == "on") {
            $link = new PluginOcsinventoryngSnmpOcslink();
            $link->delete(['id' => $key], 1);
         }
      }
      Html::redirect($_SERVER['PHP_SELF']);

   }

}

if (isset($_SESSION["ocs_updatesnmp"]['id'])) {
   if ($count = count($_SESSION["ocs_updatesnmp"]['id'])) {
      $percent = min(100,
         round(100 * ($_SESSION["ocs_updatesnmp_count"] - $count) / $_SESSION["ocs_updatesnmp_count"],
            0));


      $key = array_pop($_SESSION["ocs_updatesnmp"]['id']);
      $action = PluginOcsinventoryngSnmpOcslink::updateSnmp($key,
         $_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
      PluginOcsinventoryngOcsProcess::manageImportStatistics($_SESSION["ocs_updatesnmp"]['statistics'],
         $action['status'], true);
      PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_updatesnmp"]['statistics'], false, true);
      Html::displayProgressBar(400, $percent);

      Html::redirect($_SERVER['PHP_SELF']);

   } else {

      if (isset($_SESSION["ocs_updatesnmp"]['statistics'])) {
         PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_updatesnmp"]['statistics'], false, true);
      } else {
         echo "<div class='center b red'>";
         echo __('No synchronization: the plugin will not synchronize these elements', 'ocsinventoryng');
         echo "</div>";
      }
      unset($_SESSION["ocs_updatesnmp"]);
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
   //PluginOcsinventoryngOcsProcess::manageDeleted($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
   if ($display_list) {
      PluginOcsinventoryngSnmpOcslink::showSnmpDeviceToUpdate($_SESSION["plugin_ocsinventoryng_ocsservers_id"],
         $_GET['check'], $_GET['start']);
   }

} else {
   if (count($_POST['toupdate']) > 0) {
      $_SESSION["ocs_updatesnmp_count"] = 0;

      foreach ($_POST['toupdate'] as $key => $val) {
         if ($val == "on") {
            $_SESSION["ocs_updatesnmp"]['id'][] = $key;
            $_SESSION["ocs_updatesnmp_count"]++;
         }
      }
   }
   Html::redirect($_SERVER['PHP_SELF']);
}

Html::footer();
