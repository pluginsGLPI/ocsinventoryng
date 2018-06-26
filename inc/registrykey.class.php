<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2016 by the ocsinventoryng Development Team.

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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginOcsinventoryngRegistryKey
 */
class PluginOcsinventoryngRegistryKey extends CommonDBTM {

   static $rightname = "plugin_ocsinventoryng";

   /**
    * @param int $nb
    *
    * @return string|translated
    */
   static function getTypeName($nb = 0) {
      // No plural
      return __('Registry', 'ocsinventoryng');
   }


   /**
    *
    */
   function cleanDBonPurge() {

      $self = new self();
      $self->deleteByCriteria(['computers_id' => $this->fields['id']]);

   }


   /** Display registry values for a computer
    *
    * @param $ID integer : computer ID
    *
    * @return bool
    */
   static function showForComputer($ID) {
      global $DB;

      if (!Session::haveRight("computer", READ)) {
         return false;
      }

      //REGISTRY HIVE
      $REGISTRY_HIVE = ["HKEY_CLASSES_ROOT",
                             "HKEY_CURRENT_USER",
                             "HKEY_LOCAL_MACHINE",
                             "HKEY_USERS",
                             "HKEY_CURRENT_CONFIG",
                             "HKEY_DYN_DATA"];

      $query = "SELECT *
                FROM `glpi_plugin_ocsinventoryng_registrykeys`
                WHERE `computers_id` = '$ID'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 0) {
            echo "<div class='center'><table class='tab_cadre_fixe'>";
            echo "<tr><th colspan='4'>" . sprintf(_n('%d registry key found',
                                                     '%d registry keys found', $DB->numrows($result), 'ocsinventoryng'), $DB->numrows($result));
            echo "</th></tr>\n";

            echo "<tr><th>" . __('Name') . "</th>";
            echo "<th>" . __('Hive', 'ocsinventoryng') . "</th>";
            echo "<th>" . __('Path', 'ocsinventoryng') . "</th>";
            echo "<th>" . __('Key/Value', 'ocsinventoryng') . "</th></tr>\n";
            while ($data = $DB->fetch_assoc($result)) {
               echo "<tr class='tab_bg_1'>";
               echo "<td>" . $data["ocs_name"] . "</td>";
               if (isset($REGISTRY_HIVE[$data["hive"]])) {
                  echo "<td>" . $REGISTRY_HIVE[$data["hive"]] . "</td>";
               } else {
                  echo "<td>(" . $data["hive"] . ")</td>";
               }
               echo "<td>" . $data["path"] . "</td>";
               echo "<td>" . $data["value"] . "</td>";
               echo "</tr>";
            }
            echo "</table></div>\n\n";
         } else {
            echo "<div class='center'><table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th>" . __('Registry', 'ocsinventoryng') . "</th></tr>";
            echo "<tr class='tab_bg_2'>";
            echo "<td class='center b'>" . __('No key found in registry', 'ocsinventoryng') . "</td></tr>";
            echo "</table></div>";
         }
      }
   }


   /**
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return array|string|translated
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (in_array($item->getType(), PluginOcsinventoryngOcsServer::getTypes(true))
          && Computer::canView()) {

         switch ($item->getType()) {
            case 'Computer' :
               if (!$withtemplate) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     return self::createTabEntry(self::getTypeName(2), self::countForItem($item));
                  }
                  return self::getTypeName(2);
               }
         }
      }
      return '';
   }

   /**
    * @param CommonDBTM $item
    *
    * @return int
    */
   static function countForItem(CommonDBTM $item) {

      return countElementsInTable('glpi_plugin_ocsinventoryng_registrykeys',
                                  "`computers_id` = '" . $item->getID() . "'");
   }

   /**
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if (in_array($item->getType(), PluginOcsinventoryngOcsServer::getTypes(true))) {
         switch ($item->getType()) {
            case 'Computer' :
               self::showForComputer($item->getField('id'));
               break;
         }
      }
      return true;
   }

   function getSearchOptions() {

      $tab = [];

      $tab['common'] = self::getTypeName(2);

      $tab[1]['table']    = $this->getTable();
      $tab[1]['field']    = 'id';
      $tab[1]['name']     = __('ID');
      $tab[1]['datatype'] = 'integer';

      return $tab;
   }
}
