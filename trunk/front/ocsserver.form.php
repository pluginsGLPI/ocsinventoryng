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

Session::checkRight("plugin_ocsinventoryng", UPDATE);

$ocs = new PluginOcsinventoryngOcsServer();

if (!isset($_GET["id"]) || $_GET["id"] == -1) {
   $_GET["id"] = "";
}

Html::header('OCS Inventory NG', '', "tools", "pluginocsinventoryngmenu", "ocsserver");

//Delete template or server
if (isset ($_POST["purge"])) {
   $ocs->check($_POST['id'],PURGE);
   $ocs->delete($_POST);
   $ocs->redirectToList();

//Update server
} else if (isset ($_POST["update"])) {
   $ocs->check($_POST['id'],UPDATE);
   $ocs->update($_POST);
   Html::back();

//Add new server
} else if (isset ($_POST["add"])) {
   $ocs->check(-1, CREATE, $_POST);
   $newID= $ocs->add($_POST);
   if ($_SESSION['glpibackcreated']) {
      Html::redirect($ocs->getFormURL()."?id=".$newID);
   }
   Html::back();

//Other
} else {
   $ocs->display($_GET);
}
Html::footer();
?>