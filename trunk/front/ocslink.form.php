<?php
/*
 * @version $Id: HEADER 14684 2011-06-11 06:32:40Z remi $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', '../../..');
}
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("computer", "r");

$computer = new Computer();

if (isset($_POST["unlock_monitor"])) {
   $computer->check($_POST['id'], 'w');
   if (isset($_POST["lockmonitor"]) && count($_POST["lockmonitor"])) {
      foreach ($_POST["lockmonitor"] as $key => $val) {
         OcsServer::deleteInOcsArray($_POST["id"], $key, "import_monitor");
      }
   }
   Html::back();

} else if (isset($_POST["unlock"])) {
   $computer->check($_POST['id'], 'w');
   $actions = array("lockprinter" => "import_printer",
                    "locksoft"    => "import_software",
                    "lockdisk"    => "import_disk",
                    "lockmonitor" => "import_monitor",
                    "lockperiph"  => "import_peripheral",
                    "lockip"      => "import_ip",
                    "lockdevice"  => "import_device",
                    "lockfield"   => "computer_update");
   foreach ($actions as $lock => $field) {
      if (isset($_POST[$lock]) && count($_POST[$lock])) {
         foreach ($_POST[$lock] as $key => $val) {
            OcsServer::deleteInOcsArray($_POST["id"], $key, $field);
         }
      }
   }
   Html::back();

} else {
   Html::displayErrorAndDie("lost");
}
/*
$link = new PluginOcsinventoryngOcslink();

if (isset ($_POST["update"])) {

   $values["id"] = $_POST["link_id"];
   $values["use_auto_update"] = $_POST["use_auto_update"];
   $link->update($values);
   Html::back();
}
*/
?>