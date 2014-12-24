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
-------------------------------------------------------------------------- */

include ('../../../inc/includes.php');

Session::checkSeveralRightsOr(array("plugin_ocsinventoryng"  => READ,
                                    "plugin_ocsinventoryng_clean"  => READ));
                                                      
Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "ocsinventoryng");

if (isset ($_SESSION["ocs_import"])) {
   unset ($_SESSION["ocs_import"]);
}
if (isset ($_SESSION["ocs_link"])) {
   unset ($_SESSION["ocs_link"]);
}
if (isset ($_SESSION["ocs_update"])) {
   unset ($_SESSION["ocs_update"]);
}
if (isset($_POST["plugin_ocsinventoryng_ocsservers_id"])) {
   $_SESSION["plugin_ocsinventoryng_ocsservers_id"] = $_POST["plugin_ocsinventoryng_ocsservers_id"];
} else {
   $_SESSION["plugin_ocsinventoryng_ocsservers_id"] = PluginOcsinventoryngOcsServer::getFirstServer();
}

PluginOcsinventoryngOcsServer::ocsMenu($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
PluginOcsinventoryngOcsServer::checkOCSconnection($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);

Html::footer();

?>