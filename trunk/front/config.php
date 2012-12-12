<?php
/*
 * @version $Id: HEADER 14684 2011-06-11 06:32:40Z remi $
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

$plugin = new Plugin();
	if ($plugin->isActivated("ocsinventoryng")
         && plugin_ocsinventoryng_haveRight("ocsng", "w")) {

      Html::header('OCSInventory NG', "", "plugins", "ocsinventoryng");

      // choose config server or config synchro
      echo "<table class='tab_cadre'>";
      echo "<tr><th>".__('Configuration')."</th></tr>";
      echo "<tr class='tab_bg_1'><td class='center b'>";
      echo "<a href='".$CFG_GLPI['root_doc']."/plugins/ocsinventoryng/front/ocsserver.php'>".
             _n('OCSNG server', 'OCSNG servers', 1,'ocsinventoryng')."</a>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td class='center b'>";
      echo "<a href='".$CFG_GLPI['root_doc']."/plugins/ocsinventoryng/front/config.form.php'>".
             __('Synchronization')."</a>";
      echo "</td></tr>";
      echo "</table>";

   } else {
      Html::header(__('Setup'),'',"config","plugins");
      echo "<div class='center'><br><br>";
      echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/warning.png\" alt='".__s('Warning')."'><br><br>";
      echo "<b>".__('Please activate the plugin', 'ocsinventoryng')."</b></div>";
   }

Html::footer();
?>