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
    * Update config of the registry
    *
    * This function erase old data and import the new ones about registry (Microsoft OS after Windows 95)
    *
    * @param $computers_id integer : glpi computer id.
    * @param $ocsComputer
    * @param $history_plugins boolean
    * @param $force
    */
   static function updateRegistry($computers_id, $ocsComputer, $cfg_ocs, $force) {

      $uninstall_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_plugins'] == 1 || $cfg_ocs['history_plugins'] == 3)) {
         $uninstall_history = 1;
      }
      $install_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_plugins'] == 1 || $cfg_ocs['history_plugins'] == 2)) {
         $install_history = 1;
      }

      if ($force) {
         self::resetRegistry($computers_id, $uninstall_history);
      }
      $reg = new self();
      //update data
      foreach ($ocsComputer as $registry) {
         $registry              = Glpi\Toolbox\Sanitizer::sanitize($registry);
         $input                 = [];
         $input["computers_id"] = $computers_id;
         $input["hive"]         = $registry["regtree"];
         $input["value"]        = $registry["regvalue"];
         $input["path"]         = $registry["regkey"];
         $input["ocs_name"]     = $registry["name"];
         $reg->add($input, ['disable_unicity_check' => true], $install_history);
      }

   }

   /**
    * Delete old registry entries
    *
    * @param $glpi_computers_id integer : glpi computer id.
    * @param $uninstall_history boolean
    *
    * */
   static function resetRegistry($glpi_computers_id, $uninstall_history) {

      $registry = new self();
      $registry->deleteByCriteria(['computers_id' => $glpi_computers_id], 1, $uninstall_history);
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
                WHERE `computers_id` = $ID";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 0) {
            echo "<div class='center'><table class='tab_cadre_fixe'>";
            echo "<tr><th colspan='4'>" . sprintf(_n('%d registry key found',
                                                     '%d registry keys found',
                                                     $DB->numrows($result), 'ocsinventoryng'),
                                                  $DB->numrows($result));
            echo "</th></tr>\n";

            echo "<tr><th>" . __('Name') . "</th>";
            echo "<th>" . __('Hive', 'ocsinventoryng') . "</th>";
            echo "<th>" . __('Path', 'ocsinventoryng') . "</th>";
            echo "<th>" . __('Key/Value', 'ocsinventoryng') . "</th></tr>\n";
            while ($data = $DB->fetchAssoc($result)) {
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

      $dbu = new DbUtils();
      return $dbu->countElementsInTable('glpi_plugin_ocsinventoryng_registrykeys',
                                        ["computers_id" => $item->getID()]);
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

   /**
    * @return array
    */
   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id'   => 'common',
         'name' => self::getTypeName(2)
      ];

      $tab[] = [
         'id'       => '1',
         'table'    => $this->getTable(),
         'field'    => 'id',
         'name'     => __('ID'),
         'datatype' => 'number'
      ];

      return $tab;
   }
}
