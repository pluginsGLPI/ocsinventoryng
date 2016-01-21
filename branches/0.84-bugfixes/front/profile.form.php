<?php
/*
 * @version $Id: HEADER 15930 2012-12-15 11:10:55Z tsmr $
-------------------------------------------------------------------------
Ocsinventoryng plugin for GLPI
Copyright (C) 2012-2013 by the ocsinventoryng plugin Development Team.

https://forge.indepnet.net/projects/ocsinventoryng
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

Session::checkRight("profile","r");

$prof = new PluginOcsinventoryngProfile();
$profservers = new PluginOcsinventoryngOcsserver_Profile();

if (isset($_POST["addocsserver"]) && ($_POST['plugin_ocsinventoryng_ocsservers_id'] > 0)) {
   $input['profiles_id'] = $_POST['profile'];
   $input['ocsservers_id'] = $_POST['plugin_ocsinventoryng_ocsservers_id'];

   $newID = $profservers->add($input);
}

if (isset($_POST["addallocsservers"])) {
   global $DB;
   $input['profiles_id'] = $_POST['profile'];
   $query = "SELECT `glpi_plugin_ocsinventoryng_ocsservers`.`id`
             FROM `glpi_plugin_ocsinventoryng_ocsservers`
             LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers_profiles`
               ON (`glpi_plugin_ocsinventoryng_ocsservers_profiles`.`ocsservers_id` = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
                    AND `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`profiles_id` = ".$_POST['profile'].")
             WHERE `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`id` IS NULL
                   AND `glpi_plugin_ocsinventoryng_ocsservers`.`is_active` = 1";
   foreach ($DB->request($query) as $data) {
      $input['ocsservers_id'] = $data['id'];
      $newID = $profservers->add($input);
   }
}

if (isset ($_POST['delete']) && isset($_POST['item'])) {
   $input = array();
   foreach ($_POST['item'] as $id => $val) {
      $input['id'] = $id;
      $profservers->delete($input);
   }
}

if (isset ($_POST['deleteall'])) {
   $profservers->deleteByCriteria(array('profiles_id' => $_POST['profile']), true);
}

//Save profile
if (isset ($_POST['update'])) {
   $prof->update($_POST);
}
Html::back();
?>