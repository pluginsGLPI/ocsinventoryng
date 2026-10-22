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

use GlpiPlugin\Ocsinventoryng\Menu;
use GlpiPlugin\Ocsinventoryng\OcsServer;
use GlpiPlugin\Ocsinventoryng\OcsProcess;
use GlpiPlugin\Ocsinventoryng\SnmpOcslink;

Session::checkRight("plugin_ocsinventoryng", UPDATE);

// The OCS server of the session, chosen on front/ocsng.php: still allowed for the active profile
if (OcsServer::checkSessionServer() <= 0) {
    throw new \Glpi\Exception\Http\AccessDeniedHttpException();
}


// The URL of this page (PHP_SELF is the GLPI 11 entry point)
$self_url = PLUGIN_OCS_WEBDIR . '/front/ocsngsnmp.sync.php';

Html::header(Menu::getTypeName(), '', "tools", Menu::class, "syncsnmp");


$display_list = true;

if (isset($_POST["delete"])
   && isset($_POST["toupdate"])
) {
   if ($count = count($_POST["toupdate"])) {

      foreach ($_POST['toupdate'] as $key => $val) {
         if ($val == "on") {
            // A link of the current server, whose item (a SNMP type) the user may update
            $link = new SnmpOcslink();
            if (!$link->getFromDB((int) $key)
                || (int) $link->fields['plugin_ocsinventoryng_ocsservers_id'] !== (int) ($_SESSION["plugin_ocsinventoryng_ocsservers_id"] ?? 0)
                || !in_array($link->fields['itemtype'], SnmpOcslink::$snmptypes, true)
                || !($item = getItemForItemtype($link->fields['itemtype']))
                || !$item->can((int) $link->fields['items_id'], UPDATE)) {
               continue;
            }
            $link->delete(['id' => $link->getID()], 1);
         }
      }
      Html::redirect($self_url);

   }

}

if (isset($_SESSION["ocs_updatesnmp"]['id'])) {
   if ($count = count($_SESSION["ocs_updatesnmp"]['id'])) {
      $percent = min(100,
         round(100 * ($_SESSION["ocs_updatesnmp_count"] - $count) / $_SESSION["ocs_updatesnmp_count"],
            0, PHP_ROUND_HALF_UP));


      $key = array_pop($_SESSION["ocs_updatesnmp"]['id']);
      $action = SnmpOcslink::updateSnmp($key,
         $_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
      OcsProcess::manageImportStatistics($_SESSION["ocs_updatesnmp"]['statistics'],
         $action['status'], true);
       OcsProcess::showStatistics($_SESSION["ocs_updatesnmp"]['statistics'], false, true);
       Html::getProgressBar($percent);

      Html::redirect($self_url);

   } else {

      if (isset($_SESSION["ocs_updatesnmp"]['statistics'])) {
          OcsProcess::showStatistics($_SESSION["ocs_updatesnmp"]['statistics'], false, true);
      } else {
         echo "<div class='center b red'>";
         echo __('No synchronization: the plugin will not synchronize these elements', 'ocsinventoryng');
         echo "</div>";
      }
      unset($_SESSION["ocs_updatesnmp"]);
      $display_list = false;
      echo "<div class='center b'><br>";
      echo "<a href='" . $self_url . "'>" . __('Back') . "</a></div>";
   }
}

if (!isset($_POST["update_ok"])) {
   if (!isset($_GET['check'])) {
      $_GET['check'] = 'all';
   }
   if (!isset($_GET['start'])) {
      $_GET['start'] = 0;
   }
   //OcsProcess::manageDeleted($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
   if ($display_list) {
      SnmpOcslink::showSnmpDeviceToUpdate($_SESSION["plugin_ocsinventoryng_ocsservers_id"],
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
   Html::redirect($self_url);
}

Html::footer();
