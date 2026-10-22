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
use GlpiPlugin\Ocsinventoryng\OcsServer;
use GlpiPlugin\Ocsinventoryng\SnmpOcslink;

/**
 * The SNMP link named by the request, once the user is allowed to update its item (one of the
 * SNMP types only) and has the right of the plugin
 *
 * @return SnmpOcslink
 */
$check_link = static function (): SnmpOcslink {
   Session::checkRight("plugin_ocsinventoryng", UPDATE);

   $link = new SnmpOcslink();
   if (!$link->getFromDB((int) ($_POST["id"] ?? 0))
       || !in_array($link->fields['itemtype'], SnmpOcslink::$snmptypes, true)
       || !($item = getItemForItemtype($link->fields['itemtype']))) {
      throw new BadRequestHttpException();
   }
   $item->check((int) $link->fields['items_id'], UPDATE);
   // The OCS server of the link: allowed for the active profile
   OcsServer::checkAllowedServer($link->fields['plugin_ocsinventoryng_ocsservers_id']);

   return $link;
};

if (isset($_POST["force_ocssnmp_resynch"])) {
   $link = $check_link();
   SnmpOcslink::updateSnmp($link->getID(), $link->fields["plugin_ocsinventoryng_ocsservers_id"]);
   Html::back();

} else if (isset($_POST["delete_link"])) {
   $link = $check_link();
   $link->delete(['id' => $link->getID()], 1);
   Html::back();

} else {
    throw new BadRequestHttpException();
}
