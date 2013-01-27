<?php
/*
 * @version $Id: HEADER 15930 2012-12-15 11:10:55Z tsmr $
-------------------------------------------------------------------------
Ocsinventoryng plugin for GLPI
Copyright (C) 2012-2013 by the ocsinventoryng plugin Development Team.

https://forge.indepnet.net/projects/ocsinventoryng
-------------------------------------------------------------------------

LICENSE

This file is part of ocsinventoryng.

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

include ('../../../inc/includes.php');

plugin_ocsinventoryng_checkRight("ocsng", "w");

function configHeader() {

   echo "<div class='center'>";
   echo "<table class='tab_cadre_fixe'>";
   echo "<tr><th colspan='2'>" . __('?????') . "</th></tr>";
   echo "<tr class='tab_bg_1'><td class='center'>";
   echo "<a href='https://forge.indepnet.net/projects/ocsinventoryng/wiki' target='_blank'>" .
          __('Use mode', 'ocsinventoryng') . "</a></td></tr>";
}

$config = new PluginOcsinventoryngConfig();

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

$plugin = new Plugin();
if ($plugin->isInstalled("ocsinventoryng") && $plugin->isActivated("ocsinventoryng")) {
   Html::header(__('History of automatic actions', 'ocsinventoryng'), "", "plugins",
                "ocsinventoryng", "config");

   if (!countElementsInTable("glpi_plugin_ocsinventoryng_ocsservers")) {
      configHeader();
      echo "<tr class='tab_bg_2'><td class='center'>". __('No server configured',  'ocsinventoryng');
      echo "<a href='".getItemTypeSearchURL("PluginOcsinventoryngOcsServer")."'>".
             __('Configuration', 'ocsinventoryng')."</a></th></tr>";
      echo "</table></div>";
   } else {
      $config->showConfigForm($_SERVER['PHP_SELF']);
   }
} else {
   Html::header(__('Number of processed computers', 'ocsinventoryng'), "", "plugins",
                "ocsinventoryng", "config");
   echo "<div class='center'><br><br>";
   echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/warning.png\" alt='".__s('Warning')."'><br><br>";
   echo "<b>__('Please activate the plugin', 'ocsinventoryng')</b></div>";
}

Html::footer();
?>