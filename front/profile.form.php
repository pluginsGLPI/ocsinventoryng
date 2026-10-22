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

use GlpiPlugin\Ocsinventoryng\OcsServer;
use GlpiPlugin\Ocsinventoryng\Ocsserver_Profile;
use GlpiPlugin\Ocsinventoryng\Profile;

Session::checkRight("profile", READ);

$profservers = new Ocsserver_Profile();
$prof        = new Profile();

// Granting or revoking the servers of a profile changes the profile: its update right, and the
// same hierarchy as the profile form of GLPI (no profile with more rights than the active one)
$can_update_profile = static function (int $profiles_id): bool {
    $profile = new \Profile();
    return $profiles_id > 0
       && $profile->can($profiles_id, UPDATE)
       && \Profile::currentUserHaveMoreRightThan([$profiles_id => $profiles_id]);
};
if (isset($_POST["addocsserver"]) || isset($_POST['delete'])) {
    Session::checkRight("profile", UPDATE);
}
if (isset($_POST["addocsserver"]) && !$can_update_profile((int) ($_POST['profile'] ?? 0))) {
    throw new \Glpi\Exception\Http\AccessDeniedHttpException();
}

$ocsservers_id = (int) ($_POST['plugin_ocsinventoryng_ocsservers_id'] ?? 0);
if (isset($_POST["addocsserver"]) && $ocsservers_id > 0) {
    $input['profiles_id']                         = (int) $_POST['profile'];
    $input['plugin_ocsinventoryng_ocsservers_id'] = $ocsservers_id;

    $newID = $profservers->add($input);
    Html::back();

} elseif (isset($_POST["addocsserver"]) && $ocsservers_id == -1) {
    $prof::addAllServers((int) $_POST['profile']);
    Html::back();
}

// stock selected servers in session
$_SESSION["plugin_ocsinventoryng_ocsservers_id"] = OcsServer::getFirstServer();

if (isset($_POST['delete'])) {
    foreach ((array) ($_POST['item'] ?? []) as $id => $val) {
        // A posted link: only the links of a profile the user may update
        if ($profservers->getFromDB((int) $id)
            && $can_update_profile((int) $profservers->fields['profiles_id'])) {
            $profservers->delete(['id' => (int) $id]);
        }
    }
    Html::back();
}
