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

Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "clean");

if (!isset($_POST["clean_ok"])) {

   Session::checkRight("plugin_ocsinventoryng_clean", READ);

   if (!isset($_GET['check'])) {
      $_GET['check'] = 'all';
   }
   if (!isset($_GET['start'])) {
      $_GET['start'] = 0;
   }
   PluginOcsinventoryngOcsProcess::manageDeleted($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
   $show_params = ['plugin_ocsinventoryng_ocsservers_id' => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                   'check'                               => $_GET['check'],
                   'start'                               => $_GET['start']];
   PluginOcsinventoryngOcsServer::showComputersToClean($show_params);

} else {
   Session::checkRight("plugin_ocsinventoryng_clean", UPDATE);
   if (count($_POST['toclean']) > 0) {
      PluginOcsinventoryngOcslink::cleanLinksFromList($_SESSION["plugin_ocsinventoryng_ocsservers_id"],
         $_POST['toclean']);
      echo "<div class='center b'>" . __('Clean links between GLPI and OCSNG', 'ocsinventoryng') .
         "<br>" . __('Operation successful') . "<br>";
      Html::displayBackLink();
      echo "</div>";
   }
}

Html::footer();
