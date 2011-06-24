<?php
/*
 * @version $Id: ocsng.clean.php 14685 2011-06-11 06:40:30Z remi $
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

commonHeader($LANG['plugin_ocsinventoryng'][0], $_SERVER['PHP_SELF'], "plugins","ocsinventoryng");

if (!isset($_POST["clean_ok"])) {
   plugin_ocsinventoryng_checkRight("clean_ocsng", "r");

   if (!isset($_GET['check'])) {
      $_GET['check'] = 'all';
   }
   if (!isset($_GET['start'])) {
      $_GET['start'] = 0;
   }
   PluginOcsinventoryngOcsServer::manageDeleted($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
   PluginOcsinventoryngOcsServer::showComputersToClean($_SESSION["plugin_ocsinventoryng_ocsservers_id"], $_GET['check'], $_GET['start']);

} else {
   plugin_ocsinventoryng_checkRight("clean_ocsng", "w");
   if (count($_POST['toclean']) >0) {
      PluginOcsinventoryngOcsServer::cleanLinksFromList($_SESSION["plugin_ocsinventoryng_ocsservers_id"], $_POST['toclean']);
      echo "<div class='center b'>".$LANG['plugin_ocsinventoryng'][3]." - ".$LANG['log'][45]."<br>";
      displayBackLink();
      echo "</div>";
   }
}

commonFooter();

?>