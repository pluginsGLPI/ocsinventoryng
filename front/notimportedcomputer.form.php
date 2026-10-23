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

use GlpiPlugin\Ocsinventoryng\Notimportedcomputer;
use GlpiPlugin\Ocsinventoryng\OcsServer;
use GlpiPlugin\Ocsinventoryng\Profile;

$dropdown = new Notimportedcomputer();

if (isset($_POST['action'])) {
    $input = ['id' => (int) ($_POST['id'] ?? 0)];
    // The posted rejection: one of the entities of the user, from an OCS server allowed to
    // the profile
    if (!$dropdown->can($input['id'], READ)) {
        throw new \Glpi\Exception\Http\AccessDeniedHttpException();
    }
    OcsServer::checkAllowedServer($dropdown->fields['plugin_ocsinventoryng_ocsservers_id']);
    switch ($_POST['action']) {
        case 'plugin_ocsinventoryng_import':
            $input['force'] = true;

            // no break
        case 'plugin_ocsinventoryng_replayrules':
            Session::checkRight(Profile::RIGHT_IMPORT, UPDATE);
            // The computer is imported into an entity of the user
            $entity = (int) ($_POST['entity'] ?? -1);
            if ($entity >= 0 && !Session::haveAccessToEntity($entity)) {
                throw new \Glpi\Exception\Http\AccessDeniedHttpException();
            }
            $input['entity'] = $entity;
            if (Notimportedcomputer::computerImport($input)) {
                $dropdown->redirectToList();
            } else {
                Html::redirect(Toolbox::getItemTypeFormURL(Notimportedcomputer::class) .
                   '?id=' . $input['id']);
            }
            break;

        case 'plugin_ocsinventoryng_link':
            Session::checkRight(Profile::RIGHT_LINK, UPDATE);
            // The computer whose data the OCS machine will overwrite must be one the user may update
            $input['computers_id'] = (int) ($_POST['computers_id'] ?? 0);
            $computer = new Computer();
            $computer->check($input['computers_id'], UPDATE);
            $dropdown->linkComputer($input);
            $dropdown->redirectToList();
            break;
    }
}

include(GLPI_ROOT . "/front/dropdown.common.form.php");
