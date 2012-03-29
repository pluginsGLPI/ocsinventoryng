<?php
/*
 * @version $Id: HEADER 14684 2011-06-11 06:32:40Z remi $
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

// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', '../../..');
}

include (GLPI_ROOT . "/inc/includes.php");


plugin_ocsinventoryng_checkRight("ocsng", "w");


function configHeader() {

   echo "<div class='center'>";
   echo "<table class='tab_cadre_fixe'>";
   echo "<tr><th colspan='2'>" . __('?????') . "</th></tr>";
   echo "<tr class='tab_bg_1'><td class='center'>";
   echo "<a href='https://forge.indepnet.net/projects/ocsinventoryng/wiki' target='_blank'>" .
          __('Use mode') . "</a></td></tr>";
}

$config = new PluginOcsinventoryngConfig();

if (isset ($_POST["update"])) {
   $config->update($_POST);
   Html::back();
}
if (isset ($_POST["soft_lock"])) {
   $config->setScriptLock();
}
if (isset ($_POST["soft_unlock"])) {
   $config->removeScriptLock();
}

$plugin = new Plugin();
if ($plugin->isInstalled("ocsinventoryng") && $plugin->isActivated("ocsinventoryng")) {
   Html::header(__('History of automatic tasks'), "", "plugins", "ocsinventoryng");

   if (!countElementsInTable("glpi_plugin_ocsinventoryng_ocsservers")) {
      configHeader();
      echo "<tr class='tab_bg_2'><td class='center'>". __('No server configured');
      echo "<a href='".getItemTypeSearchURL("PluginOcsinventoryngOcsServer")."'>".
             __('Configuration')."</a></th></tr>";
      echo "</table></div>";
   } else {
      $config->showConfigForm($_SERVER['PHP_SELF']);
   }
} else {
   Html::header(__('Number of processed computers'), "", "config", "plugins");
   echo "<div class='center'><br><br>";
   echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/warning.png\" alt=\"warning\"><br><br>";
   echo "<b>Please activate the plugin</b></div>";
}

Html::footer();

?>