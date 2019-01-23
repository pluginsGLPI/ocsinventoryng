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

global $CFG_GLPI;

$plugin = new Plugin();
if ($plugin->isActivated("ocsinventoryng")
   && Session::haveRight("plugin_ocsinventoryng", UPDATE)) {

   Html::header('OCSInventory NG', '', "tools", "pluginocsinventoryngmenu", "ocsinventoryng");

   // choose config server or config synchro
   PluginOcsinventoryngConfig::showMenu();

} else {
   Html::header(__('Setup'), '', "tools", "pluginocsinventoryngmenu", "config");
   echo "<div class='center'><br><br>";
   echo "<i class='fas fa-exclamation-triangle fa-4x' style='color:orange'></i><br><br>";
   echo "<b>" . __('Please activate the plugin', 'ocsinventoryng') . "</b></div>";
}

Html::footer();
