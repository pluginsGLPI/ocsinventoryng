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

Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "modifysubnet");

$ip = new PluginOcsinventoryngIpdiscoverOcslink();

if (isset($_GET["ip"])) {
   $_POST["ip"] = $_GET["ip"];
}
if (isset($_POST["ip"])) {

   $ipAdress = $_POST["ip"];
   $values = [];
   if (isset($_POST["subnetName"]) && isset($_POST["subnetChoice"]) && isset($_POST["SubnetMask"])) {
      $values = ["subnetName" => $_POST["subnetName"], "subnetChoice" => $_POST["subnetChoice"], "subnetMask" => $_POST["SubnetMask"]];
   }
   $ip->modifyNetworkForm($ipAdress, $values);
}

Html::footer();
