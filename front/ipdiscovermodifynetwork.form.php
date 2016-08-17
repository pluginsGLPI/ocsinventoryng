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

include ('../../../inc/includes.php');

Session::checkRight("plugin_ocsinventoryng", UPDATE);
//Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu",'ipdiscmodifynetwork');
Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu");
$ip = new PluginOcsinventoryngIpDiscover();

if (isset($_GET["ip"])) {
   $_POST["ip"] = $_GET["ip"];
}
if (isset($_POST["ip"])) {
   $ip       = new PluginOcsinventoryngIpDiscover();
   $ipAdress = $_POST["ip"];
   $values   = array();
   if (isset($_POST["subnetName"]) && isset($_POST["subnetChoise"]) && isset($_POST["SubnetMask"])) {
      $values = array("subnetName" => $_POST["subnetName"], "subnetChoise" => $_POST["subnetChoise"], "subnetMask" => $_POST["SubnetMask"]);
   }
   $ip->modifyNetworkForm($ipAdress, $values);
}

Html::footer();
?>