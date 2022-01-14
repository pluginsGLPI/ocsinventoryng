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

Session::checkRight("plugin_ocsinventoryng", UPDATE);

if (!isset($_GET["plugin_ocsinventoryng_ocsservers_id"])) {
   $_GET["plugin_ocsinventoryng_ocsservers_id"] = "0";
}

$thread = new PluginOcsinventoryngThread();


Html::header(__('Processes execution of automatic actions', 'ocsinventoryng'), '', "tools", "pluginocsinventoryngmenu", "thread");

if (isset ($_POST["delete_processes"])) {

   if (count($_POST["item"])) {
      foreach ($_POST["item"] as $key => $val) {
         $thread->deleteThreadsByProcessId($key);
      }
   }
   Html::back();

} else {
   $thread->showProcesses($_SERVER["PHP_SELF"], $_GET["plugin_ocsinventoryng_ocsservers_id"]);
}

Html::footer();
