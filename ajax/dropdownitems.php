<?php
/*
 * @version $Id: HEADER 15930 2012-12-15 11:10:55Z tsmr $
-------------------------------------------------------------------------
Ocsinventoryng plugin for GLPI
Copyright (C) 2012-2016 by the ocsinventoryng plugin Development Team.

https://forge.glpi-project.org/projects/ocsinventoryng
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
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

// Make a select box
if (isset($_POST["itemtype"])
    ) {
   $table = getTableForItemType($_POST["itemtype"]);

   $rand = mt_rand();
   if (isset($_POST["rand"])) {
      $rand = $_POST["rand"];
   }
   echo "<input type='hidden' name='tolink_itemtype[" . $_POST["id"] . "]' value='" . $_POST["itemtype"] . "'>";
   echo "<br>";
   $field_id = Html::cleanId("dropdown_".$_POST['myname'].$rand);
   $p = array('itemtype'            => $_POST["itemtype"],
              //'entity_restrict'     => $_POST['entity_restrict'],
              'table'               => $table,
              //'multiple'            => $_POST["multiple"],
              'myname'              => $_POST["myname"],
              'rand'                => $_POST["rand"]);

   if (isset($_POST["used"]) && !empty($_POST["used"])) {
      if (isset($_POST["used"][$_POST["itemtype"]])) {
         $p["used"] = $_POST["used"][$_POST["itemtype"]];
      }
   }

   echo Html::jsAjaxDropdown($_POST['myname'], $field_id,
                             $CFG_GLPI['root_doc']."/plugins/ocsinventoryng/ajax/getDropdownFindItem.php",
                             $p);

}
?>