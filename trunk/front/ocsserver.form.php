<?php

/*
 * @version $Id: ocsserver.form.php 14685 2011-06-11 06:40:30Z remi $
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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

plugin_ocsinventoryng_checkRight("ocsng", "w");
$ocs = new PluginOcsinventoryngOcsServer();

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

commonHeader($LANG['plugin_ocsinventoryng'][0], '', "plugins", "ocsinventoryng");

//Delete template or server
if (isset ($_POST["delete"])) {
   $ocs->delete($_POST);
   $ocs->redirectToList();

//Update server
} else if (isset ($_POST["update"])) {
   $ocs->update($_POST);
   glpi_header($_SERVER["HTTP_REFERER"]);

//Update server
} else if (isset ($_POST["update_server"])) {
   $ocs->update($_POST);
   glpi_header($_SERVER["HTTP_REFERER"]);

//Add new server
} else if (isset ($_POST["add"])) {
   $newid = $ocs->add($_POST);
   glpi_header($_SERVER["HTTP_REFERER"]);

//Other
} else {
   $ocs->showForm($_GET["id"]);
}
commonFooter();

?>
