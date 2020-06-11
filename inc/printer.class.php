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
 * Class PluginOcsinventoryngPrinter
 */
class PluginOcsinventoryngPrinter extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Computer';
   static public $items_id = 'computers_id';

   static $rightname = "plugin_ocsinventoryng";


   /**
    *
    * Import printers from OCS
    * @since 1.0
    *
    * @param $printer_params
    *
    * @throws \GlpitestSQLError
    * @internal param computer $ocsid 's id in OCS
    */
   static function importPrinter($printer_params) {
      global $DB, $CFG_GLPI;

      $cfg_ocs       = $printer_params["cfg_ocs"];
      $computers_id  = $printer_params["computers_id"];
      $ocsservers_id = $printer_params["plugin_ocsinventoryng_ocsservers_id"];
      $ocsComputer   = $printer_params["datas"];
      $entity        = $printer_params["entities_id"];
      $force         = $printer_params["force"];

      $uninstall_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_printer'] == 1 || $cfg_ocs['history_printer'] == 3)) {
         $uninstall_history = 1;
      }
      $install_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_printer'] == 1 || $cfg_ocs['history_printer'] == 2)) {
         $install_history = 1;
      }

      if ($force) {
         PluginOcsinventoryngPrinter::resetPrinters($computers_id, $uninstall_history);
      }

      $already_processed = [];

      $conn = new Computer_Item();
      $p    = new Printer();

      foreach ($ocsComputer as $printer) {
         $printer = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($printer));
         $print   = [];
         // TO TEST : PARSE NAME to have real name.
         $print['name'] = PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($cfg_ocs["ocs_db_utf8"], $printer['NAME']);

         if (empty($print["name"])) {
            $print["name"] = $printer["DRIVER"];
         }

         $management_process = $cfg_ocs["import_printer"];

         //Params for the dictionnary
         $params['name']         = $print['name'];
         $params['manufacturer'] = "";
         $params['DRIVER']       = $printer['DRIVER'];
         $params['PORT']         = $printer['PORT'];

         if (!empty($print["name"])) {
            $rulecollection = new RuleDictionnaryPrinterCollection();
            $res_rule       = Toolbox::addslashes_deep($rulecollection->processAllRules(Toolbox::stripslashes_deep($params), [], []));

            if (!isset($res_rule["_ignore_import"]) || !$res_rule["_ignore_import"]) {

               foreach ($res_rule as $key => $value) {
                  if ($value != '' && $value[0] != '_') {
                     $print[$key] = $value;
                  }
               }

               if (isset($res_rule['is_global'])) {
                  if (!$res_rule['is_global']) {
                     $management_process = 2;
                  } else {
                     $management_process = 1;
                  }
               }

               //Look for a printer with the same name (and serial if possible) already connected
               //to this computer
               $query   = "SELECT `p`.`id`, `gci`.`is_deleted`
                            FROM `glpi_printers` as `p`, `glpi_computers_items` as `gci`
                            WHERE `p`.`id` = `gci`.`items_id`
                               AND `gci`.`is_dynamic` = 1
                               AND `computers_id`= $computers_id
                               AND `itemtype`= 'Printer'
                               AND `p`.`name`= '" . $print["name"] . "'";
               $results = $DB->query($query);
               $id      = false;
               if ($DB->numrows($results) > 0) {
                  $id = $DB->result($results, 0, 'id');
               }

               if (!$id) {
                  // Clean printer object
                  $p->reset();
                  $print["comment"] = $printer["PORT"] . "\r\n" . $printer["DRIVER"];
                  self::analyzePrinterPorts($print, $printer["PORT"]);
                  $id_printer = 0;

                  if ($management_process == 1) {
                     //Config says : manage printers as global
                     //check if printers already exists in GLPI
                     $print["is_global"] = MANAGEMENT_GLOBAL;
                     $query              = "SELECT `id`
                                         FROM `glpi_printers`
                                         WHERE `name` = '" . $print["name"] . "'
                                            AND `is_global` = 1 ";
                     if ($CFG_GLPI['transfers_id_auto'] < 1) {
                        $query .= " AND `entities_id` = '$entity'";
                     }
                     $result_search = $DB->query($query);

                     if ($DB->numrows($result_search) > 0) {
                        //Periph is already in GLPI
                        //Do not import anything just get periph ID for link
                        $id_printer          = $DB->result($result_search, 0, "id");
                        $already_processed[] = $id_printer;
                     } else {
                        $input = $print;

                        //for rule asset
                        $input['_auto']       = 1;
                        $input["entities_id"] = $entity;

                        $id_printer           = $p->add($input, [], $install_history);
                     }
                  } else if ($management_process == 2) {
                     //Config says : manage printers as single units
                     //Import all printers as non global.
                     $input              = $print;
                     $input["is_global"] = MANAGEMENT_UNITARY;

                     //for rule asset
                     $input['_auto']       = 1;
                     $input["entities_id"] = $entity;
                     $input['is_dynamic']  = 1;
                     $id_printer           = $p->add($input, [], $install_history);
                  }

                  if ($id_printer) {
                     $already_processed[] = $id_printer;
                     $conn->add(['computers_id' => $computers_id,
                                 'itemtype'     => 'Printer',
                                 'items_id'     => $id_printer,
                                 'is_dynamic'   => 1], [], $install_history);
                     //Update column "is_deleted" set value to 0 and set status to default
                     $input                = [];
                     $input["id"]          = $id_printer;
                     $input["is_deleted"]  = 0;
                     $input["entities_id"] = $entity;

                     //for rule asset
                     $input['_auto'] = 1;
                     $input["is_dynamic"]  = 1;
                     $p->update($input, $install_history);
                  }
               } else {
                  $already_processed[] = $id;
               }
            }
         }
      }

      //Look for all printers, not locked, not linked to the computer anymore
      $query = "SELECT `id`
                    FROM `glpi_computers_items`
                    WHERE `itemtype`='Printer'
                       AND `computers_id`= $computers_id
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
         $mode     = PluginOcsinventoryngOcsServer::getDevicesManagementMode($ocs_config, 'Printer');
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
               $query = "UPDATE
                `glpi_computers_items`
                SET `is_deleted` = 1
                WHERE `id`= " . $data['id'];
               $DB->query($query);
            }
         }
      }
   }

   /**
    * @param        $printer_infos
    * @param string $port
    */
   static function analyzePrinterPorts(&$printer_infos, $port = '') {

      if (preg_match("/USB[0-9]*/i", $port)) {
         $printer_infos['have_usb'] = 1;
      } else if (preg_match("/IP_/i", $port)) {
         $printer_infos['have_ethernet'] = 1;
      } else if (preg_match("/LPT[0-9]:/i", $port)) {
         $printer_infos['have_parallel'] = 1;
      }
   }

   /**
    * Delete all old printers of a computer.
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @param $uninstall_history
    *
    * @return void .
    * @throws \GlpitestSQLError
    */
   static function resetPrinters($glpi_computers_id, $uninstall_history) {
      global $DB;

      $query  = "SELECT *
                FROM `glpi_computers_items`
                WHERE `computers_id` = $glpi_computers_id
                      AND `itemtype` = 'Printer'
                      AND `is_dynamic` = 1";
      $result = $DB->query($query);

      if ($DB->numrows($result) > 0) {
         $conn = new Computer_Item();

         while ($data = $DB->fetchAssoc($result)) {
            $conn->delete(['id' => $data['id'], '_no_history' => !$uninstall_history], true, $uninstall_history);

            $query2  = "SELECT COUNT(*)
                       FROM `glpi_computers_items`
                       WHERE `items_id` = " . $data['items_id'] . "
                             AND `itemtype` = 'Printer'";
            $result2 = $DB->query($query2);

            $printer = new Printer();
            if ($DB->result($result2, 0, 0) == 1) {
               $printer->delete(['id' => $data['items_id'], '_no_history' => !$uninstall_history], true, $uninstall_history);
            }
         }
      }
   }
}
