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
 * Class PluginOcsinventoryngPeripheral
 */
class PluginOcsinventoryngPeripheral extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Computer';
   static public $items_id = 'computers_id';

   static $rightname = "plugin_ocsinventoryng";


   /**
    *
    * Import peripherals from OCS
    * @since 1.0
    *
    * @param $periph_params
    *
    * @throws \GlpitestSQLError
    * @internal param computer $ocsid 's id in OCS
    */
   static function importPeripheral($periph_params) {
      global $DB, $CFG_GLPI;

      $cfg_ocs       = $periph_params["cfg_ocs"];
      $computers_id  = $periph_params["computers_id"];
      $ocsservers_id = $periph_params["plugin_ocsinventoryng_ocsservers_id"];
      $ocsComputer   = $periph_params["datas"];
      $entity        = $periph_params["entities_id"];
      $force         = $periph_params["force"];

      $uninstall_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_peripheral'] == 1 || $cfg_ocs['history_peripheral'] == 3)) {
         $uninstall_history = 1;
      }
      $install_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_peripheral'] == 1 || $cfg_ocs['history_peripheral'] == 2)) {
         $install_history = 1;
      }

      if ($force) {
         self::resetPeripherals($computers_id, $uninstall_history);
      }

      $already_processed = [];
      $p                 = new Peripheral();
      $conn              = new Computer_Item();

      foreach ($ocsComputer as $peripheral) {

         if ($peripheral["CAPTION"] !== '') {
            $peripheral     = Glpi\Toolbox\Sanitizer::sanitize($peripheral);
            $periph         = [];
            $periph["name"] = PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($cfg_ocs["ocs_db_utf8"], $peripheral["CAPTION"]);
            //Look for a monitor with the same name (and serial if possible) already connected
            //to this computer
            $query   = "SELECT `p`.`id`, `gci`.`is_deleted`
                        FROM `glpi_printers` as `p`, `glpi_computers_items` as `gci`
                        WHERE `p`.`id` = `gci`.`items_id`
                        AND `gci`.`is_dynamic` = 1
                        AND `computers_id`= $computers_id
                        AND `itemtype`= 'Peripheral'
                        AND `p`.`name`= '" . $periph["name"] . "'";
            $results = $DB->query($query);
            $id      = false;
            if ($DB->numrows($results) > 0) {
               $id = $DB->result($results, 0, 'id');
            }
            if (!$id) {
               // Clean peripheral object
               $p->reset();
               if ($peripheral["MANUFACTURER"] != "NULL") {
                  $periph["brand"] = PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($cfg_ocs["ocs_db_utf8"],
                                                               $peripheral["MANUFACTURER"]);
               }
               if ($peripheral["INTERFACE"] != "NULL") {
                  $periph["comment"] = PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($cfg_ocs["ocs_db_utf8"],
                                                                 $peripheral["INTERFACE"]);
               }
               $periph["peripheraltypes_id"] = Dropdown::importExternal('PeripheralType',
                                                                        $peripheral["TYPE"]);
               $id_periph                    = 0;
               if ($cfg_ocs["import_periph"] == 1) {
                  //Config says : manage peripherals as global
                  //check if peripherals already exists in GLPI
                  $periph["is_global"] = 1;
                  $query               = "SELECT `id`
                                           FROM `glpi_peripherals`
                                           WHERE `name` = '" . $periph["name"] . "'
                                           AND `is_global` = 1 ";
                  if (Entity::getUsedConfig('transfers_strategy', $entity, 'transfers_id', 0) < 1) {
                     $query .= " AND `entities_id` = '$entity'";
                  }
                  $result_search = $DB->query($query);
                  if ($DB->numrows($result_search) > 0) {
                     //Periph is already in GLPI
                     //Do not import anything just get periph ID for link
                     $id_periph = $DB->result($result_search, 0, "id");
                  } else {
                     $input = $periph;
                     //for rule asset
                     $input['_auto']       = 1;
                     $input["is_dynamic"]  = 1;
                     $input["entities_id"] = $entity;
                     $id_periph            = $p->add($input, [], $install_history);
                  }
               } else if ($cfg_ocs["import_periph"] == 2) {
                  //Config says : manage peripherals as single units
                  //Import all peripherals as non global.
                  $input              = $periph;
                  $input["is_global"] = 0;
                  //for rule asset
                  $input['_auto'] = 1;
                  $input["is_dynamic"]  = 1;
                  $input["entities_id"] = $entity;
                  $id_periph            = $p->add($input, [], $install_history);
               }

               if ($id_periph) {
                  $already_processed[] = $id_periph;
                  if ($conn->add(['computers_id' => $computers_id,
                                  'itemtype'     => 'Peripheral',
                                  'items_id'     => $id_periph,
                                  'is_dynamic'   => 1], [], $install_history)) {
                     //Update column "is_deleted" set value to 0 and set status to default
                     $input                = [];
                     $input["id"]          = $id_periph;
                     $input["is_deleted"]  = 0;
                     $input["entities_id"] = $entity;
                     //for rule asset
                     $input['_auto']      = 1;
                     $input["is_dynamic"]  = 1;
                     $p->update($input, $install_history);
                  }
               }
            } else {
               $already_processed[] = $id;
            }
         }
      }

      //Look for all peripherals, not locked, not linked to the computer anymore
      $query = "SELECT `id`
                FROM `glpi_computers_items`
                WHERE `itemtype`='Peripheral'
                   AND `computers_id`=$computers_id
                   AND `is_dynamic` = 1
                   AND `is_deleted` = 0 ";
      if (!empty($already_processed)) {
         $query .= "AND `items_id` NOT IN (" . implode(',', $already_processed) . ")";
      }
      foreach ($DB->request($query) as $data) {
         // Delete all connexions
         //Get OCS configuration
         $ocs_config = PluginOcsinventoryngOcsServer::getConfig($ocsservers_id);

         //Get the management mode for this device
         $mode     = PluginOcsinventoryngOcsServer::getDevicesManagementMode($ocs_config, 'Peripheral');
         $decoConf = $ocs_config["deconnection_behavior"];

         //Change status if :
         // 1 : the management mode IS NOT global
         // 2 : a deconnection's status have been defined
         // 3 : unique with serial
         if (($mode >= 2) && (strlen($decoConf) > 0)) {

            //Delete periph from glpi
            if ($decoConf == "delete") {
               $query = "DELETE
                         FROM `glpi_computers_items`
                         WHERE `id`= " . $data['id'];
               $DB->query($query);
               //Put periph in dustbin
            } else if ($decoConf == "trash") {
               $query = "UPDATE `glpi_computers_items`
                         SET `is_deleted` = 1
                         WHERE `id`=" . $data['id'];
               $DB->query($query);
            }
         }
      }
   }

   /**
    * Delete all old periphs for a computer.
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @param $uninstall_history
    * @return void .
    * @throws \GlpitestSQLError
    */
   static function resetPeripherals($glpi_computers_id, $uninstall_history) {
      global $DB;

      $query  = "SELECT *
                FROM `glpi_computers_items`
                WHERE `computers_id` = $glpi_computers_id
                      AND `itemtype` = 'Peripheral'
                      AND `is_dynamic` = 1";
      $result = $DB->query($query);

      $per = new Peripheral();
      if ($DB->numrows($result) > 0) {
         $conn = new Computer_Item();
         while ($data = $DB->fetchAssoc($result)) {
            $conn->delete(['id' => $data['id'], '_no_history' => !$uninstall_history], true, $uninstall_history);

            $query2  = "SELECT COUNT(*)
                       FROM `glpi_computers_items`
                       WHERE `items_id` = " . $data['items_id'] . "
                             AND `itemtype` = 'Peripheral'";
            $result2 = $DB->query($query2);

            if ($DB->result($result2, 0, 0) == 1) {
               $per->delete(['id'          => $data['items_id'],
                             '_no_history' => !$uninstall_history],
                            true,
                            $uninstall_history);
            }
         }
      }
   }
}
