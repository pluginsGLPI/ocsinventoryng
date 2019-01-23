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

$plugin = new Plugin();
if ($plugin->isActivated("ocsinventoryng")) {
   $config = new PluginOcsinventoryngConfig();

   global $CFG_GLPI;

   if (isset($_POST["update"])) {
      $config->update($_POST);
      Html::back();
   }
   if (isset($_POST["soft_lock"])) {
      $config->setScriptLock();
   }
   if (isset($_POST["soft_unlock"])) {
      $config->removeScriptLock();
   }

   Html::header(__("Automatic synchronization's configuration", 'ocsinventoryng'), '', "tools", "pluginocsinventoryngmenu", "config");

   $config->display(['id' => 1]);
} else {
   Html::header(__('Setup'), '', "tools", "pluginocsinventoryngmenu", "config");
   echo "<div class='center'><br><br>";
   echo "<i class='fas fa-exclamation-triangle fa-4x' style='color:orange'></i><br><br>";
   echo "<b>" . __('Please activate the plugin', 'ocsinventoryng') . "</b></div>";
}

Html::footer();
