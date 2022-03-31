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
 * Class PluginOcsinventoryngMonitor
 */
class PluginOcsinventoryngMonitor extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Computer';
   static public $items_id = 'computers_id';

   static $rightname = "plugin_ocsinventoryng";


   /**
    *
    * Import monitors from OCS
    * @since 1.0
    *
    * @param $monitor_params
    *
    * @throws \GlpitestSQLError
    * @internal param computer $ocsid 's id in OCS
    * @internal param the $entity entity in which the monitor will be created
    */
   static function importMonitor($monitor_params) {
      global $DB, $CFG_GLPI;

      $cfg_ocs       = $monitor_params["cfg_ocs"];
      $computers_id  = $monitor_params["computers_id"];
      $ocsservers_id = $monitor_params["plugin_ocsinventoryng_ocsservers_id"];
      $ocsComputer   = $monitor_params["datas"];
      $entity        = $monitor_params["entities_id"];
      $force         = $monitor_params["force"];

      $uninstall_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_monitor'] == 1 || $cfg_ocs['history_monitor'] == 3)) {
         $uninstall_history = 1;
      }
      $install_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_monitor'] == 1 || $cfg_ocs['history_monitor'] == 2)) {
         $install_history = 1;
      }

      if ($force || $cfg_ocs["import_monitor"] == 1) { // Only reset monitor as global in unit management
         self::resetMonitors($computers_id, $uninstall_history);    // try to link monitor with existing
      }

      $already_processed = [];
      $m                 = new Monitor();
      $conn              = new Computer_Item();

      $monitors = [];

      // First pass - check if all serial present

      foreach ($ocsComputer as $monitor) {
         // Config says import monitor with serial number only
         // Restrict SQL query ony for monitors with serial present
         if ($cfg_ocs["import_monitor"] > 2 && empty($monitor["SERIAL"])) {
            unset($monitor);
         } else {
            $monitors[] = Glpi\Toolbox\Sanitizer::sanitize($monitor);
         }
      }

      if (count($monitors) > 0 && $cfg_ocs["import_monitor"] > 0) {

         foreach ($monitors as $monitor) {

            $mon = [];
            if (!empty($monitor["CAPTION"])) {
               $mon["name"]             = PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($cfg_ocs["ocs_db_utf8"],
                                                                    $monitor["CAPTION"]);
               $mon["monitormodels_id"] = Dropdown::importExternal('MonitorModel', $monitor["CAPTION"]);
            }
            if (empty($monitor["CAPTION"]) && !empty($monitor["MANUFACTURER"])) {
               $mon["name"] = $monitor["MANUFACTURER"];
            }
            if (empty($monitor["CAPTION"]) && !empty($monitor["TYPE"])) {
               if (!empty($monitor["MANUFACTURER"])) {
                  $mon["name"] .= " ";
               }
               $mon["name"] .= $monitor["TYPE"];
            }
            if (!empty($monitor["TYPE"])) {
               $mon["monitortypes_id"] = Dropdown::importExternal('MonitorType', $monitor["TYPE"]);
            }
            $mon["serial"]     = $monitor["SERIAL"];
            $mon["is_dynamic"] = 1;
            //Look for a monitor with the same name (and serial if possible) already connected
            //to this computer
            //15012021 : Unactivated because block link for good computer
//            $query = "SELECT `m`.`id`, `gci`.`is_deleted`
//                      FROM `glpi_monitors` as `m`, `glpi_computers_items` as `gci`
//                      WHERE `m`.`id` = `gci`.`items_id`
//                         AND `gci`.`is_dynamic` = 1
//                         AND `computers_id`= $computers_id
//                         AND `itemtype`= 'Monitor'
//                         AND `m`.`name`='" . $mon["name"] . "'";
//            if ($cfg_ocs["import_monitor"] > 2 && !empty($mon["serial"])) {
//               $query .= " AND `m`.`serial`='" . $mon["serial"] . "'";
//            }
//            $results = $DB->query($query);
            $id      = false;
//            if ($DB->numrows($results) == 1) {
//               $id = $DB->result($results, 0, 'id');
//            }

            if ($id == false) {
               // Clean monitor object
               $m->reset();
               $mon["manufacturers_id"] = Dropdown::importExternal('Manufacturer', $monitor["MANUFACTURER"]);
               if ($cfg_ocs["import_monitor_comment"]) {
                  $mon["comment"] = $monitor["DESCRIPTION"];
               }
               $id_monitor = 0;

               if ($cfg_ocs["import_monitor"] == 1) {
                  //Config says : manage monitors as global
                  //check if monitors already exists in GLPI
                  $mon["is_global"] = 1;
                  $query            = "SELECT `id`
                               FROM `glpi_monitors`
                               WHERE `name` = '" . $mon["name"] . "'
                                  AND `is_global` = 1 ";
                  if (Entity::getUsedConfig('transfers_strategy', $entity, 'transfers_id', 0) < 1) {
                     $query .= " AND `entities_id` = $entity";
                  }
                  $result_search = $DB->query($query);

                  if ($DB->numrows($result_search) > 0) {
                     //Periph is already in GLPI
                     //Do not import anything just get periph ID for link
                     $id_monitor = $DB->result($result_search, 0, "id");
                  } else {
                     $input = $mon;
                     //for rule asset
                     $input['_auto']      = 1;
                     $input["entities_id"] = $entity;
                     $id_monitor           = $m->add($input, [], $install_history);
                  }
               } else if ($cfg_ocs["import_monitor"] >= 2) {
                  //Config says : manage monitors as single units
                  //Import all monitors as non global.
                  $mon["is_global"] = 0;

                  // Try to find a monitor with the same serial.
                  if (!empty($mon["serial"])) {
                     $query = "SELECT `id`
                               FROM `glpi_monitors`
                               WHERE `serial` LIKE '%" . $mon["serial"] . "%'
                                  AND `is_global` = 0 ";
                     if (Entity::getUsedConfig('transfers_strategy', $entity, 'transfers_id', 0) < 1) {
                        $query .= " AND `entities_id` = $entity";
                     }
                     $result_search = $DB->query($query);
                     if ($DB->numrows($result_search) == 1) {
                        //Monitor founded
                        $id_monitor = $DB->result($result_search, 0, "id");
                     }
                  }

                  //Search by serial failed, search by name
                  if ($cfg_ocs["import_monitor"] == 2
                      && !$id_monitor) {
                     //Try to find a monitor with no serial, the same name and not already connected.
                     if (!empty($mon["name"])) {
                        $query = "SELECT `glpi_monitors`.`id`
                                  FROM `glpi_monitors`
                                  LEFT JOIN `glpi_computers_items`
                                       ON (`glpi_computers_items`.`itemtype`='Monitor'
                                           AND `glpi_computers_items`.`items_id`
                                                   =`glpi_monitors`.`id`)
                                  WHERE `serial` = ''
                                        AND `name` = '" . $mon["name"] . "'
                                              AND `is_global` = 0
                                              AND `glpi_computers_items`.`computers_id` IS NULL";
                        if (Entity::getUsedConfig('transfers_strategy', $entity, 'transfers_id', 0) < 1) {
                           $query .= " AND `entities_id` = '$entity'";
                        }
                        $result_search = $DB->query($query);
                        if ($DB->numrows($result_search) == 1) {
                           $id_monitor = $DB->result($result_search, 0, "id");
                        }
                     }
                  }

                  if (!$id_monitor) {
                     $input = $mon;
                     //for rule asset
                     $input['_auto']       = 1;
                     $input["entities_id"] = $entity;
                     $input["is_dynamic"]  = 1;
                     $id_monitor           = $m->add($input, [], $install_history);
                  }
               } // ($cfg_ocs["import_monitor"] >= 2)

               if ($id_monitor) {
                  //Import unique : Disconnect monitor on other computer done in Connect function
                  $conn->add(['computers_id' => $computers_id,
                              'itemtype'     => 'Monitor',
                              'items_id'     => $id_monitor,
                              'is_dynamic'   => 1,
                              'is_deleted'   => 0], [], $install_history);
                  $already_processed[] = $id_monitor;

                  //Update column "is_deleted" set value to 0 and set status to default
                  $input = [];
                  $old   = new Monitor();
                  if ($old->getFromDB($id_monitor)) {
                     //for rule asset
                     $input['_auto']      = 1;
                     if ($old->fields["is_deleted"]) {
                        $input["is_deleted"] = 0;
                     }

                     if (empty($old->fields["name"])
                         && !empty($mon["name"])) {
                        $input["name"] = $mon["name"];
                     }
                     if (empty($old->fields["serial"])
                         && !empty($mon["serial"])) {
                        $input["serial"] = $mon["serial"];
                     }
                     $input["id"] = $id_monitor;
                     if (count($input)) {
                        $input['entities_id'] = $entity;
                        $m->update($input, $install_history);
                     }
                  }
               }
            } else {
               $already_processed[] = $id;
            }
            //Look for all monitors, not locked, not linked to the computer anymore
            $query = "SELECT `id`
                         FROM `glpi_computers_items`
                         WHERE `itemtype`='Monitor'
                            AND `computers_id`= $computers_id
                            AND `is_dynamic` = 1
                            AND `is_deleted`= 0 ";
            if (!empty($already_processed)) {
               $query .= "AND `items_id` NOT IN (" . implode(',', $already_processed) . ")";
            }

            foreach ($DB->request($query) as $data) {
               // Delete all connexions
               //Get OCS configuration
               $ocs_config = PluginOcsinventoryngOcsServer::getConfig($ocsservers_id);

               //Get the management mode for this device
               $mode     = PluginOcsinventoryngOcsServer::getDevicesManagementMode($ocs_config, 'Monitor');
               $decoConf = $ocs_config["deconnection_behavior"];

               //Change status if :
               // 1 : the management mode IS NOT global
               // 2 : a deconnection's status have been defined
               // 3 : unique with serial
               if (($mode >= 2) && (strlen($decoConf) > 0)) {

                  //Delete periph from glpi
                  if ($decoConf == "delete") {
                     $query  = "DELETE
                         FROM `glpi_computers_items`
                         WHERE `id`= " . $data['id'];
                     $DB->query($query);
                     //Put periph in dustbin
                  } else if ($decoConf == "trash") {
                     $query = "UPDATE
                         `glpi_computers_items`
                        SET `is_deleted` = 1
                         WHERE `id`= " . $data['id'];
                     $DB->query($query);
                  }
               }
            }
         }
      }
   }

   /**
    * Delete all old monitors of a computer.
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @param $uninstall_history
    * @return void .
    * @throws \GlpitestSQLError
    */
   static function resetMonitors($glpi_computers_id, $uninstall_history) {
      global $DB;

      $query  = "SELECT *
                FROM `glpi_computers_items`
                WHERE `computers_id` = $glpi_computers_id
                      AND `itemtype` = 'Monitor'
                      AND `is_dynamic` = 1";
      $result = $DB->query($query);

      $mon = new Monitor();
      if ($DB->numrows($result) > 0) {
         $conn = new Computer_Item();

         while ($data = $DB->fetchAssoc($result)) {

            $conn->delete(['id' => $data['id'], '_no_history' => !$uninstall_history], true, $uninstall_history);
//Really used ???
//            $query2  = "SELECT COUNT(*)
//                       FROM `glpi_computers_items`
//                       WHERE `items_id` = " . $data['items_id'] . "
//                             AND `itemtype` = 'Monitor'";
//            $result2 = $DB->query($query2);
//
//            if ($DB->result($result2, 0, 0) == 1) {
//               $mon->delete(['id' => $data['items_id'], '_no_history' => !$uninstall_history], true, $uninstall_history);
//            }
         }
      }
   }
}
