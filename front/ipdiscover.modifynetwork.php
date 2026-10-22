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


use GlpiPlugin\Ocsinventoryng\IpdiscoverOcslink;
use GlpiPlugin\Ocsinventoryng\Menu;
use GlpiPlugin\Ocsinventoryng\OcsServer;

Session::checkRight("plugin_ocsinventoryng", UPDATE);

// The OCS server of the session, chosen on front/ocsng.php: still allowed for the active profile
if (OcsServer::checkSessionServer() <= 0) {
    throw new \Glpi\Exception\Http\AccessDeniedHttpException();
}

Html::header(Menu::getTypeName(), '', "tools", Menu::class, "modifysubnet");

$ip = new IpdiscoverOcslink();

// The subnet to show comes from the link (GET), the changes from the form only (POST)
$ipAdress = $_POST["ip"] ?? $_GET["ip"] ?? null;
if ($ipAdress !== null) {
    $ipAdress = IpdiscoverOcslink::checkIp($ipAdress);
    $values = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST["subnetName"]) && isset($_POST["subnetChoice"]) && isset($_POST["SubnetMask"])) {
        $values = ["subnetName" => (string) $_POST["subnetName"], "subnetChoice" => (string) $_POST["subnetChoice"], "subnetMask" => (string) $_POST["SubnetMask"]];
    }
    $ip->modifyNetworkForm($ipAdress, $values);
}

Html::footer();
