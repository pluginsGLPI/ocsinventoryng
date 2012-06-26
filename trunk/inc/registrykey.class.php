<?php
/*
 * @version $Id: registrykey.class.php 14685 2011-06-11 06:40:30Z remi $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Olivier Andreotti
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginOcsinventoryngRegistryKey extends CommonDBTM {

   static function getTypeName($nb=0) {
      // No plural
      return __('Registry');
   }


   function canCreate() {
      // Only create on ocsng sync
      return plugin_ocsinventoryng_haveRight('sync_ocsng', 'w');
   }


   function canView() {
      return plugin_ocsinventoryng_haveRight('ocsng', 'r');
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

      if (!Session::haveRight("computer","r")) {
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
            echo "<tr><th colspan='4'>".sprintf(__('%d registry key found'), $DB->numrows($result));
            echo "</th></tr>\n";

            echo "<tr><th>".__('OCSNG Nname')."</th>";
            echo "<th>".__('Hive')."</th>";
            echo "<th>".__('Path')."</th>";
            echo "<th>".__('Key/value')."</th></tr>\n";
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
            echo "<tr class='tab_bg_2'><th>".__('Registry')."</th></tr>";
            echo "<tr class='tab_bg_2'>";
            echo "<td class='center b'>".__('No key found in registry')."</td></tr>";
            echo "</table></div>";
         }
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (in_array($item->getType(), PluginOcsinventoryngOcsServer::getTypes(true))
          && $this->canView()) {

         switch ($item->getType()) {
            case 'Computer' :
               return self::getTypeName(2);
         }
      }
      return '';
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