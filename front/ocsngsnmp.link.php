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
use GlpiPlugin\Ocsinventoryng\SnmpOcslink;

Session::checkRight("plugin_ocsinventoryng", UPDATE);

// The OCS server of the session, chosen on front/ocsng.php: still allowed for the active profile
if (OcsServer::checkSessionServer() <= 0) {
    throw new \Glpi\Exception\Http\AccessDeniedHttpException();
}


// The URL of this page (PHP_SELF is the GLPI 11 entry point)
$self_url = PLUGIN_OCS_WEBDIR . '/front/ocsngsnmp.link.php';

Html::header(Menu::getTypeName(), '', "tools", Menu::class, "synclink");

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
         round(100 * ($_SESSION["ocs_linksnmp_count"] - $count) / $_SESSION["ocs_linksnmp_count"], 0, PHP_ROUND_HALF_UP));

       Html::getProgressBar($percent);

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

      SnmpOcslink::linkSnmpDevice($key,
         $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
         $params);
      Html::redirect($self_url);
   } else {
       Html::getProgressBar(100);

      unset($_SESSION["ocs_link"]);
      echo "<div class='center b'>" . __('Successful link', 'ocsinventoryng') . "<br>";
      echo "<a href='" . $self_url . "'>" . __('Back') . "</a></div>";
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
    SnmpOcslink::searchForm($values);
    SnmpOcslink::showSnmpDeviceToAdd($values);


} else {


   if (isset($_POST['tolink_items']) && count($_POST['tolink_items']) > 0) {
      $_SESSION["ocs_linksnmp_count"] = 0;

      // Queued for the processing above: an OCS id, a SNMP type and an item the user may
      // update, as the synchronisation overwrites it
      foreach ($_POST['tolink_items'] as $key => $val) {
         $key      = (int) $key;
         $val      = (int) $val;
         $itemtype = (string) ($_POST['tolink_itemtype'][$key] ?? '');
         if ($key > 0 && $val > 0) {
            if (!in_array($itemtype, SnmpOcslink::$snmptypes, true)
                || !($item = getItemForItemtype($itemtype))) {
               throw new \Glpi\Exception\Http\BadRequestHttpException();
            }
            $item->check($val, UPDATE);
            $_SESSION["ocs_linksnmp"]["id"][]           = $key;
            $_SESSION["ocs_linksnmp"]["items"][$key]    = $val;
            $_SESSION["ocs_linksnmp"]["itemtype"][$key] = $itemtype;
         }
         $_SESSION["ocs_linksnmp_count"]++;
      }
   }
   Html::redirect($self_url);
}

Html::footer();
