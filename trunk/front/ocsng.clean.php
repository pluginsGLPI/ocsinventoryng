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

Html::header('OCS Inventory NG', "", "plugins","ocsinventoryng", "clean");

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
      echo "<div class='center b'>".__('Clean links between GLPI and OCSNG', 'ocsinventoryng').
            "<br>". __('Operation successful')."<br>";
      displayBackLink();
      echo "</div>";
   }
}

Html::footer();
?>