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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginOcsinventoryngRegistryKey extends CommonDBTM {
   
   static $rightname = "plugin_ocsinventoryng";
   
   static function getTypeName($nb=0) {
      // No plural
      return __('Registry', 'ocsinventoryng');
   }



   function cleanDBonPurge() {

      $self = new self();
      $self->deleteByCriteria(array('computers_id' => $this->fields['id']));

   }


   /** Display registry values for a computer
    *
   * @param $ID integer : computer ID
   *
   */
   static function showForComputer($ID) {
      global $DB;

      if (!Session::haveRight("computer",READ)) {
         return false;
      }

      //REGISTRY HIVE
      $REGISTRY_HIVE = array("HKEY_CLASSES_ROOT",
                             "HKEY_CURRENT_USER",
                             "HKEY_LOCAL_MACHINE",
                             "HKEY_USERS",
                             "HKEY_CURRENT_CONFIG",
                             "HKEY_DYN_DATA");

      $query = "SELECT *
                FROM `glpi_plugin_ocsinventoryng_registrykeys`
                WHERE `computers_id` = '$ID'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)!=0) {
            echo "<div class='center'><table class='tab_cadre_fixe'>";
            echo "<tr><th colspan='4'>".sprintf(_n('%d registry key found',
             '%d registry keys found', $DB->numrows($result),'ocsinventoryng'), $DB->numrows($result));
            echo "</th></tr>\n";

            echo "<tr><th>".__('Name')."</th>";
            echo "<th>".__('Hive', 'ocsinventoryng')."</th>";
            echo "<th>".__('Path', 'ocsinventoryng')."</th>";
            echo "<th>".__('Key/Value', 'ocsinventoryng')."</th></tr>\n";
            while ($data=$DB->fetch_assoc($result)) {
               echo "<tr class='tab_bg_1'>";
               echo "<td>".$data["ocs_name"]."</td>";
               if (isset($REGISTRY_HIVE[$data["hive"]])) {
                  echo "<td>".$REGISTRY_HIVE[$data["hive"]]."</td>";
               } else {
                  echo "<td>(".$data["hive"].")</td>";
               }
               echo "<td>".$data["path"]."</td>";
               echo "<td>".$data["value"]."</td>";
               echo "</tr>";
            }
            echo "</table></div>\n\n";
         } else {
            echo "<div class='center'><table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th>".__('Registry', 'ocsinventoryng')."</th></tr>";
            echo "<tr class='tab_bg_2'>";
            echo "<td class='center b'>".__('No key found in registry', 'ocsinventoryng')."</td></tr>";
            echo "</table></div>";
         }
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (in_array($item->getType(), PluginOcsinventoryngOcsServer::getTypes(true))
          && $this->canView()) {

         switch ($item->getType()) {
            case 'Computer' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(self::getTypeName(2), self::countForItem($item));
               }
               return self::getTypeName(2);
         }
      }
      return '';
   }

   static function countForItem(CommonDBTM $item) {

      return countElementsInTable('glpi_plugin_ocsinventoryng_registrykeys',
                                  "`computers_id` = '".$item->getID()."'");
   }
   
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if (in_array($item->getType(), PluginOcsinventoryngOcsServer::getTypes(true))) {
         switch ($item->getType()) {
            case 'Computer' :
               self::showForComputer($item->getField('id'));
               break;
         }
      }
      return true;
   }
}
?>