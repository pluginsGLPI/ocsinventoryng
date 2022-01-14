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

Session::checkRight("computer", READ);

if (isset($_POST["launch_ocs_resynch"])) {
   $computer = new Computer();
   $computer->check($_POST['id'], UPDATE);

   //Get the ocs server id associated with the machine
   $ocsservers_id = PluginOcsinventoryngOcsServer::getServerByComputerID($_POST["id"]);
   //Update the computer
   $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($ocsservers_id);
   $sync_params = ['ID' => $_POST["resynch_id"],
                   'plugin_ocsinventoryng_ocsservers_id' => $ocsservers_id,
                   'cfg_ocs' => $cfg_ocs,
                  'force' => 0];
   PluginOcsinventoryngOcsProcess::synchronizeComputer($sync_params);
   Html::back();
} else if (isset($_POST["force_ocs_resynch"])) {
   $computer = new Computer();
   $computer->check($_POST['id'], UPDATE);

   //Get the ocs server id associated with the machine
   $ocsservers_id = PluginOcsinventoryngOcsServer::getServerByComputerID($_POST["id"]);
   //Update the computer
   $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($ocsservers_id);
   $sync_params = ['ID' => $_POST["resynch_id"],
                   'plugin_ocsinventoryng_ocsservers_id' => $ocsservers_id,
                   'cfg_ocs' => $cfg_ocs,
                   'force' => 1];
   PluginOcsinventoryngOcsProcess::synchronizeComputer($sync_params);
   Html::back();
} else if (isset ($_POST["update"])) {
   $link = new PluginOcsinventoryngOcslink();
   $values["id"] = $_POST["link_id"];
   $values["use_auto_update"] = $_POST["use_auto_update"];
   $link->update($values);
   Html::back();

} else if (isset ($_POST["delete_link"])) {
   $comp = new Computer();
   $link = new PluginOcsinventoryngOcslink();
   if ($comp->getFromDB($_POST["items_id"])) {
      $link->purgeComputer($comp);
      Html::back();
   }
} else {
   Html::displayErrorAndDie("lost");
}
