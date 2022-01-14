<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
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
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

global $CFG_GLPI;

// Make a select box
if (isset($_POST["itemtype"])
) {
   $dbu = new DbUtils();
   $table = $dbu->getTableForItemType($_POST["itemtype"]);

   $rand = mt_rand();
   if (isset($_POST["rand"])) {
      $rand = $_POST["rand"];
   }
   echo Html::hidden("tolink_itemtype[". $_POST["id"] ."]", ['value' => $_POST["itemtype"]]);
   echo "<br>";
   $field_id = Html::cleanId("dropdown_" . $_POST['myname'] . $rand);
   $p = ['itemtype' => $_POST["itemtype"],
      //'entity_restrict'     => $_POST['entity_restrict'],
      'table' => $table,
      //'multiple'            => $_POST["multiple"],
      'myname' => $_POST["myname"],
      'rand' => $_POST["rand"]];

   if (isset($_POST["used"]) && !empty($_POST["used"])) {
      if (isset($_POST["used"][$_POST["itemtype"]])) {
         $p["used"] = $_POST["used"][$_POST["itemtype"]];
      }
   }

   echo Html::jsAjaxDropdown($_POST['myname'], $field_id,
                             PLUGIN_OCS_WEBDIR . "/ajax/getDropdownFindItem.php",
      $p);

}
