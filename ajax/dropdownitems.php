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

use GlpiPlugin\Ocsinventoryng\IpdiscoverOcslink;
use GlpiPlugin\Ocsinventoryng\SnmpOcslink;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

// Used by the link forms of IPDiscover and SNMP devices
Session::checkRight("plugin_ocsinventoryng", UPDATE);

// Make a select box, for the types offered by the link forms only
$itemtype = (string) ($_POST["itemtype"] ?? '');
$allowed  = array_unique(array_merge(IpdiscoverOcslink::$hardwareItemTypes, SnmpOcslink::$snmptypes));
if (in_array($itemtype, $allowed, true)) {
    $rand = (int) ($_POST["rand"] ?? mt_rand());
    $id   = (int) ($_POST["id"] ?? 0);
    // The name of the field is the one of the line of the link form
    $myname = "tolink_items[" . $id . "]";

    echo Html::hidden("tolink_itemtype[" . $id . "]", ['value' => $itemtype]);
    echo "<br>";
    $field_id = Html::cleanId("dropdown_" . $myname . $rand);
    $p = ['itemtype' => $itemtype,
        'myname' => $myname,
        'rand' => $rand];

    if (!empty($_POST["used"][$itemtype])) {
        $p["used"] = array_map('intval', (array) $_POST["used"][$itemtype]);
    }

    echo Html::jsAjaxDropdown(
        $myname,
        $field_id,
        PLUGIN_OCS_WEBDIR . "/ajax/getDropdownFindItem.php",
        $p,
    );
}
