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

use GlpiPlugin\Ocsinventoryng\Ocslink;
use GlpiPlugin\Ocsinventoryng\OcsServer;
use GlpiPlugin\Ocsinventoryng\Profile;

if (isset($_POST['update_lock'])) {

    // A synchronization of the computer: the right of the plugin, on a server allowed for the profile
    Session::checkRight(Profile::RIGHT_SYNC, UPDATE);
    $plugin_ocsinventoryng_ocsservers_id = OcsServer::checkAllowedServer($_POST['plugin_ocsinventoryng_ocsservers_id'] ?? 0);
    $computers_id = (int) ($_POST['computers_id'] ?? 0);
    $ocsid = (int) ($_POST['ocsid'] ?? 0);
    $field = (string) ($_POST['field'] ?? '');

    // The user must be allowed to update the computer, and the OCS machine must be the one
    // linked to it on that server
    $computer = new Computer();
    $computer->check($computers_id, UPDATE);
    $link = new Ocslink();
    if (!$link->getFromDBByCrit([
        'computers_id'                        => $computers_id,
        'ocsid'                               => $ocsid,
        'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
    ])) {
        throw new \Glpi\Exception\Http\BadRequestHttpException();
    }

    Ocslink::updateLock($plugin_ocsinventoryng_ocsservers_id, $computers_id, $ocsid, $field);
}
