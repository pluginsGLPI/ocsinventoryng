<?php
/*
 * @version $Id: ocsng.php 14685 2011-06-11 06:40:30Z remi $
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

plugin_ocsinventoryng_checkSeveralRightsOr(array('ocsng'       => 'r',
                                                 'clean_ocsng' => 'r'));

Html::header('OCS Inventory NG', "", "plugins","ocsinventoryng");
if (isset ($_SESSION["ocs_import"])) {
   unset ($_SESSION["ocs_import"]);
}
if (isset ($_SESSION["ocs_link"])) {
   unset ($_SESSION["ocs_link"]);
}
if (isset ($_SESSION["ocs_update"])) {
   unset ($_SESSION["ocs_update"]);
}

if (isset($_GET["plugin_ocsinventoryng_ocsservers_id"])
    && $_GET["plugin_ocsinventoryng_ocsservers_id"]) {

   PluginOcsinventoryngOcsServer::ocsMenu($_GET["plugin_ocsinventoryng_ocsservers_id"]);

   PluginOcsinventoryngOcsServer::manageDeleted($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);

} else {
   PluginOcsinventoryngOcsServer::showFormServerChoice();
}
Html::footer();
?>