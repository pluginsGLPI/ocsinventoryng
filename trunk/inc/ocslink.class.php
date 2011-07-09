<?php
/*
 * @version $Id: ocslink.class.php 14685 2011-06-11 06:40:30Z remi $
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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// CLASSES PluginOcsinventoryngOcslink
class PluginOcsinventoryngOcslink extends CommonDBTM {

   static function getTypeName() {
      global $LANG;

      return $LANG['plugin_ocsinventoryng'][58];
   }


   function canCreate() {
      return plugin_ocsinventoryng_haveRight('ocsng', 'w');
   }


   function canView() {
      return plugin_ocsinventoryng_haveRight('ocsng', 'r');
   }


   /**
   * Show OcsLink of an item
   *
   * @param $item CommonDBTM object
   * @param $withtemplate integer : withtemplate param
   * @return nothing
   **/
   static function showForItem(CommonDBTM $item, $withtemplate='') {
      global $DB, $LANG;

      if (in_array($item->getType(),array('Computer'))) {
         $items_id = $item->getField('id');

         $query = "SELECT `glpi_plugin_ocsinventoryng_ocslinks`.`tag` AS tag
                   FROM `glpi_plugin_ocsinventoryng_ocslinks`
                   WHERE `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id` = '$items_id' ".
                         getEntitiesRestrictRequest("AND","glpi_plugin_ocsinventoryng_ocslinks");

         $result = $DB->query($query);
         if ($DB->numrows($result) > 0) {
            $data = $DB->fetch_assoc($result);
            $data = clean_cross_side_scripting_deep(addslashes_deep($data));

            echo "<div class='center'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . $LANG['plugin_ocsinventoryng'][0] . "</th>";
            echo "<tr class='tab_bg_2'>";
            echo "<td class='center'>".$LANG['plugin_ocsinventoryng']['config'][39]."&nbsp;: ".$data['tag']."</td></tr>";
         }
      }
   }
   
   /**
   * Update lockable fields of an item
   *
   * @param $item CommonDBTM object
   * @param $withtemplate integer : withtemplate param
   * @return nothing
   **/
   static function updateComputer(CommonDBTM $item, $withtemplate='') {
      global $DB, $LANG;

      // Manage changes for OCS if more than 1 element (date_mod)
      // Need dohistory==1 if dohistory==2 no locking fields
      if ($item->fields["is_ocs_import"]  && $item->dohistory==1 && count($item->updates)>1) {
         PluginOcsinventoryngOcsServer::mergeOcsArray($item->fields["id"], $item->updates, "computer_update");
      }

      if (isset($item->input["_auto_update_ocs"])) {
         $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                   SET `use_auto_update` = '".$item->input["_auto_update_ocs"]."'
                   WHERE `computers_id` = '".$item->input["id"]."'";
         $DB->query($query);
      }
   }
   
   /**
   * Update lockable linked items of an item
   *
   * @param $item CommonDBTM object
   * @param $withtemplate integer : withtemplate param
   * @return nothing
   **/
   static function addComputer_Item(CommonDBTM $item, $withtemplate='') {
      global $DB, $LANG;
      
      switch ($item->input['itemtype']) {
         case 'Monitor' :
            $link = new Monitor();
            $ocstab = 'import_monitor';
            break;

         case 'Phone' :
            // shoul really never occurs as OCS doesn't sync phone
            $link = new Phone();
            $ocstab = '';
            break;

         case 'Printer' :
            $link = new Printer();
            $ocstab = 'import_printer';
            break;

         case 'Peripheral' :
            $link = new Peripheral();
            $ocstab = 'import_peripheral';
            break;

         default :
            return false;
      }
      if (!$link->getField('is_global') ) {
         // Handle case where already used, should never happen (except from OCS sync)
         $query = "SELECT `id`, `computers_id`
                   FROM `glpi_computers_items`
                   WHERE `glpi_computers_items`.`items_id` = '".$item->input['items_id']."'
                         AND `glpi_computers_items`.`itemtype` = '".$item->input['itemtype']."'";
         $result = $DB->query($query);

         while ($data=$DB->fetch_assoc($result)) {
            $temp = clone $item;
            $temp->delete($data);
            if ($ocstab) {
               PluginOcsinventoryngOcsServer::deleteInOcsArray($data["computers_id"], $data["id"],$ocstab);
            }
         }
      }
   }
}

?>