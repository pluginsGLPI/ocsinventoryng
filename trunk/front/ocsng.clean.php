<?php
/*
 * @version $Id: ocsng.clean.php 14685 2011-06-11 06:40:30Z remi $
 -------------------------------------------------------------------------
 ocinventoryng - TreeView browser plugin for GLPI
 Copyright (C) 2012 by the ocinventoryng Development Team.

 https://forge.indepnet.net/projects/ocinventoryng
 -------------------------------------------------------------------------

 LICENSE

 This file is part of ocinventoryng.

 ocinventoryng is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 ocinventoryng is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with ocinventoryng; If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', '../../..');
}
include (GLPI_ROOT . "/inc/includes.php");

Html::header('OCS Inventory NG', "", "plugins","ocsinventoryng");

if (!isset($_POST["clean_ok"])) {
   plugin_ocsinventoryng_checkRight("clean_ocsng", "r");

   if (!isset($_GET['check'])) {
      $_GET['check'] = 'all';
   }
   if (!isset($_GET['start'])) {
      $_GET['start'] = 0;
   }
   PluginOcsinventoryngOcsServer::manageDeleted($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
   PluginOcsinventoryngOcsServer::showComputersToClean($_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                                                       $_GET['check'], $_GET['start']);

} else {
   plugin_ocsinventoryng_checkRight("clean_ocsng", "w");
   if (count($_POST['toclean']) > 0) {
      PluginOcsinventoryngOcsServer::cleanLinksFromList($_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                                                        $_POST['toclean']);
      echo "<div class='center b'>".__('Clean links between GLPI and OCSNG')."<br>".
             __('Successful')."<br>";
      displayBackLink();
      echo "</div>";
   }
}

Html::footer();
?>