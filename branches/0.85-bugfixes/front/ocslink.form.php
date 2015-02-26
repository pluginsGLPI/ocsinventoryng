<?php
/*
 * @version $Id: HEADER 15930 2012-12-15 11:10:55Z tsmr $
-------------------------------------------------------------------------
Ocsinventoryng plugin for GLPI
Copyright (C) 2012-2013 by the ocsinventoryng plugin Development Team.

https://forge.indepnet.net/projects/ocsinventoryng
-------------------------------------------------------------------------

LICENSE

This file is part of ocsinventoryng.

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
-------------------------------------------------------------------------- */

include ('../../../inc/includes.php');

Session::checkRight("computer", READ);

if (isset($_POST["force_ocs_resynch"])) {
   $computer = new Computer();
   $computer->check($_POST['id'], UPDATE);

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
?>