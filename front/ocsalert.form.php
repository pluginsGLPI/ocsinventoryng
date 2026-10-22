<?php
/*
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

use GlpiPlugin\Ocsinventoryng\NotificationState;
use GlpiPlugin\Ocsinventoryng\OcsAlert;

// The router of GLPI refuses the pages of an inactive plugin
$state    = new NotificationState();
$ocsalert = new OcsAlert();

// Every write needs the right of the plugin; a refusal is an error, not a silent no-op
if (isset($_POST["add"]) || isset($_POST["update"]) || isset($_POST["add_state"]) || isset($_POST["delete_state"])) {
   if (!$ocsalert->canUpdate()) {
      throw new \Glpi\Exception\Http\AccessDeniedHttpException();
   }
}

if (isset($_POST["add"]) || isset($_POST["update"])) {
   // The alert settings of an entity of the user: on update, the entity of the stored row
   $input = $_POST;
   if (isset($_POST["update"])) {
      if (!$ocsalert->getFromDB((int) ($_POST["id"] ?? 0))) {
         throw new \Glpi\Exception\Http\BadRequestHttpException();
      }
      $input['entities_id'] = $ocsalert->fields['entities_id'];
   }
   if (!Session::haveAccessToEntity((int) ($input['entities_id'] ?? -1))) {
      throw new \Glpi\Exception\Http\AccessDeniedHttpException();
   }
   if (isset($_POST["add"])) {
      unset($input['id']);
      $ocsalert->add($input);
   } else {
      $ocsalert->update($input);
   }
   Html::back();

} else if (isset($_POST["add_state"])) {

   $state->add(['states_id' => (int) ($_POST['states_id'] ?? 0)]);
   Html::back();

} else if (isset($_POST["delete_state"])) {

   foreach ((array) ($_POST["item"] ?? []) as $key => $val) {
      if ($val == 1) {
         $state->delete(['id' => (int) $key]);
      }
   }
   Html::back();

}
