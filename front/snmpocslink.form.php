<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2025 by the ocsinventoryng Development Team.

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



if (isset($_POST["force_ocssnmp_resynch"])) {
   $item = new $_POST['itemtype']();
   $item->check($_POST['items_id'], UPDATE);

   PluginOcsinventoryngSnmpOcslink::updateSnmp($_POST["id"], $_POST["plugin_ocsinventoryng_ocsservers_id"]);
   Html::back();

} else if (isset($_POST["delete_link"])) {
   $link = new PluginOcsinventoryngSnmpOcslink();
   $link->delete(['id' => $_POST["id"]], 1);
   Html::back();

} else {
   Html::displayErrorAndDie("lost");
}
