<?php
/*
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

$plugin = new Plugin();
if ($plugin->isActivated("ocsinventoryng")) {
   $state    = new PluginOcsinventoryngNotificationState();
   $ocsalert = new PluginOcsinventoryngOcsAlert();

   if (isset($_POST["add"])) {

      if ($ocsalert->canUpdate()) {
         $newID = $ocsalert->add($_POST);
      }
      Html::back();

   } else if (isset($_POST["update"])) {

      if ($ocsalert->canUpdate()) {
         $ocsalert->update($_POST);
      }
      Html::back();

   } else if (isset($_POST["add_state"])) {

      if ($ocsalert->canUpdate()) {
         $newID = $state->add($_POST);
      }
      Html::back();

   } else if (isset($_POST["delete_state"])) {

      if ($ocsalert->canUpdate()) {
         $state->getFromDB($_POST["id"]);
         foreach ($_POST["item"] as $key => $val) {
            if ($val == 1) {
               $state->delete(['id' => $key]);
            }
         }
      }
      Html::back();

   }
}
