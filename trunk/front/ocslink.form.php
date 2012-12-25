<?php
/*
 * @version $Id: HEADER 15930 2012-12-15 11:10:55Z tsmr $
-------------------------------------------------------------------------
Ocsinventoryng plugin for GLPI
Copyright (C) 2012-2013 by the ocsinventoryng plugin Development Team.

https://forge.indepnet.net/projects/ocsinventoryng
-------------------------------------------------------------------------

LICENSE

This file is part of accounts.

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
---------------------------------------------------------------------------------------------------------------------------------------------------- */

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', '../../..');
}
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("computer", "r");
$computer = new Computer();
if (isset($_POST["unlock"])) {
   $computer->check($_POST['id'], 'w');
   $actions = array("Computer_Item", "Computer_SoftwareVersion", "ComputerDisk" ,
                     "ComputerVirtualMachine", "NetworkPort", "Computer_SoftwareLicense");
   $devices = Item_Devices::getDeviceTypes();
   $actions = array_merge($actions, array_values($devices));
   foreach ($actions as $itemtype) {
      if (isset($_POST[$itemtype]) && count($_POST[$itemtype])) {
         $item = new $itemtype();
         foreach ($_POST[$itemtype] as $key => $val) {
            //Force unlock
            $item->update(array('id' => $key, 'is_deleted' => 0));
         }
      }
   }
   if (isset($_POST["unlock"])) {
      $computer->check($_POST['id'], 'w');
      if (isset($_POST["lockfield"]) && count($_POST["lockfield"])) {
         foreach ($_POST["lockfield"] as $key => $val) {
            PluginOcsinventoryngOcsServer::deleteInOcsArray($_POST["id"], $key, "computer_update");
         }
      }
   }
   Html::back();
      
} else if (isset($_POST["force_ocs_resynch"])) {
   $computer->check($_POST['id'], 'w');
   //Get the ocs server id associated with the machine
   $ocsservers_id = PluginOcsinventoryngOcsServer::getByMachineID($_POST["id"]);
   //Update the computer
   PluginOcsinventoryngOcsServer::updateComputer($_POST["resynch_id"], $ocsservers_id, 1, 1);
   Html::back();

} else if (isset ($_POST["update"])) {
   $link                      = new PluginOcsinventoryngOcslink();
   $values["id"]              = $_POST["link_id"];
   $values["use_auto_update"] = $_POST["use_auto_update"];
   $link->update($values);
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