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

use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Ocsinventoryng\Ocslink;
use GlpiPlugin\Ocsinventoryng\OcsProcess;
use GlpiPlugin\Ocsinventoryng\OcsServer;

Session::checkRight("computer", READ);

/**
 * The link named by the request, once the user is allowed to update its computer
 *
 * @param mixed $links_id
 *
 * @return Ocslink
 */
$check_link = static function ($links_id): Ocslink {
   $link = new Ocslink();
   if (!$link->getFromDB((int) $links_id)) {
      throw new BadRequestHttpException();
   }
   $computer = new Computer();
   $computer->check((int) $link->fields['computers_id'], UPDATE);

   return $link;
};

if (isset($_POST["launch_ocs_resynch"]) || isset($_POST["force_ocs_resynch"])) {
   // The computer checked is the one of the link synchronised, not another posted id
   $link = $check_link($_POST["resynch_id"] ?? 0);

   //Update the computer
   $ocsservers_id = (int) $link->fields['plugin_ocsinventoryng_ocsservers_id'];
   $sync_params = ['ID' => (int) $link->getID(),
                   'plugin_ocsinventoryng_ocsservers_id' => $ocsservers_id,
                   'cfg_ocs' => OcsServer::getConfig($ocsservers_id),
                   'force' => isset($_POST["force_ocs_resynch"]) ? 1 : 0];
   OcsProcess::synchronizeComputer($sync_params);
   Html::back();
} else if (isset ($_POST["update"])) {
   $link = $check_link($_POST["link_id"] ?? 0);
   $link->update([
      'id'              => $link->getID(),
      'use_auto_update' => (int) ($_POST["use_auto_update"] ?? 0),
   ]);
   Html::back();

} else if (isset ($_POST["delete_link"])) {
   // Removes the link and the inventory rows of the plugin, and possibly the machine in OCS
   Session::checkRight("plugin_ocsinventoryng_clean", UPDATE);
   $comp = new Computer();
   $comp->check((int) ($_POST["items_id"] ?? 0), UPDATE);
   $link = new Ocslink();
   $link->purgeComputer($comp);
   Html::back();
} else {
    throw new BadRequestHttpException();

}
