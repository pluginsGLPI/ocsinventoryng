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
 * Class PluginOcsinventoryngOS
 */
class PluginOcsinventoryngOS extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Computer';
   static public $items_id = 'computers_id';

   static $rightname = "plugin_ocsinventoryng";


   /**
    * @param array $params
    *
    * @return array
    */
   static function updateComputerOS($options = []) {
      global $DB;

      $is_utf8     = $options['cfg_ocs']["ocs_db_utf8"];
      $ocsServerId = $options['plugin_ocsinventoryng_ocsservers_id'];

      $options['do_history'] = $options['cfg_ocs']["history_hardware"];

      if (isset($options['HARDWARE'])) {
         $hardware = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($options['HARDWARE']));

         $updates        = 0;
         $license_number = null;
         if (intval($options['cfg_ocs']["import_os_serial"]) > 0
             && !in_array("license_number", $options['computers_updates'])) {

            if (!empty($hardware["WINPRODKEY"])) {
               $license_number = PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($is_utf8, $hardware["WINPRODKEY"]);
               $updates++;
            }
         }
         $license_id = null;
         if (intval($options['cfg_ocs']["import_os_serial"]) > 0
             && !in_array("license_id", $options['computers_updates'])) {
            if (!empty($hardware["WINPRODID"])) {
               $license_id = PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($is_utf8, $hardware["WINPRODID"]);
               $updates++;
            }
         }

         if ($options['check_history']) {
            $sql_computer = "SELECT `glpi_operatingsystems`.`name` AS os_name,
                                    `glpi_operatingsystemservicepacks`.`name` AS os_sp
                             FROM `glpi_computers`
                           LEFT JOIN `glpi_plugin_ocsinventoryng_ocslinks`
                           ON `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id` = `glpi_computers`.`id`
                           LEFT JOIN `glpi_items_operatingsystems`
                           ON (`glpi_computers`.`id` = `glpi_items_operatingsystems`.`items_id` AND `glpi_items_operatingsystems`.`itemtype` = 'Computer')
                           LEFT JOIN `glpi_operatingsystems`
                           ON (`glpi_operatingsystems`.`id` = `glpi_items_operatingsystems`.`operatingsystems_id`)
                           LEFT JOIN `glpi_operatingsystemservicepacks`
                           ON (`glpi_operatingsystemservicepacks`.`id` = `glpi_items_operatingsystems`.`operatingsystemservicepacks_id`)
                             WHERE `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid`
                                          = " . $options['ocs_id'] . "
                                   AND `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id`
                                          = $ocsServerId";

            $res_computer = $DB->query($sql_computer);

            if ($DB->numrows($res_computer) == 1) {
               $data_computer = $DB->fetch_array($res_computer);
               $computerOS    = $data_computer["os_name"];
               $computerOSSP  = $data_computer["os_sp"];

               //Do not log software history in case of OS or Service Pack change
               if (!$options['do_history']
                   || $computerOS != $hardware["OSNAME"]
                   || $computerOSSP != $hardware["OSCOMMENTS"]) {
                  $options['dohistory'] = 0;
               }
            }
         }

         if (intval($options['cfg_ocs']["import_general_os"]) > 0) {
            $operatingsystems_id = 0;
            if (!in_array("operatingsystems_id", $options['computers_updates'])) {
               $os_data             = PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($is_utf8, $hardware['OSNAME']);
               $operatingsystems_id = Dropdown::importExternal('OperatingSystem', $os_data);
               if ($operatingsystems_id > 0) {
                  $updates++;
               }
            }
            $operatingsystemversions_id = 0;
            if (!in_array("operatingsystemversions_id", $options['computers_updates'])) {
               $osv_data                   = PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($is_utf8, $hardware['OSVERSION']);
               $operatingsystemversions_id = Dropdown::importExternal('OperatingSystemVersion', $osv_data);
               if ($operatingsystemversions_id > 0) {
                  $updates++;
               }
            }
            $operatingsystemservicepacks_id = 0;
            if (!in_array("operatingsystemservicepacks_id", $options['computers_updates'])) {

               $ossp_data                      = PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($is_utf8, $hardware['OSCOMMENTS']);
               $operatingsystemservicepacks_id = Dropdown::importExternal('OperatingSystemServicePack', $ossp_data);
               if ($operatingsystemservicepacks_id > 0) {
                  $updates++;
               }
            }
            $operatingsystemarchitectures_id = 0;
            if (!in_array("operatingsystemarchitectures_id", $options['computers_updates'])
                && isset($hardware["ARCH"])) {
               $osa_data                        = PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($is_utf8, $hardware['ARCH']);
               $operatingsystemarchitectures_id = Dropdown::importExternal('OperatingSystemArchitecture', $osa_data);
               if ($operatingsystemarchitectures_id > 0) {
                  $updates++;
               }
            }
         }

         if ($updates > 0) {
            self::resetOS($options['computers_id'], $options['cfg_ocs']);

            $device = new Item_OperatingSystem();

            //            if ($operatingsystems_id) {
            $device->add(['items_id'                        => $options['computers_id'],
                          'itemtype'                        => 'Computer',
                          'operatingsystems_id'             => $operatingsystems_id,
                          'operatingsystemversions_id'      => $operatingsystemversions_id,
                          'operatingsystemservicepacks_id'  => $operatingsystemservicepacks_id,
                          'operatingsystemarchitectures_id' => $operatingsystemarchitectures_id,
                          'license_number'                  => $license_number,
                          'license_id'                      => $license_id,
                          '_nolock'                         => true,
                          'is_dynamic'                      => 1,
                          'entities_id'                     => $options['entities_id']
                         ], $options['dohistory']);
            //            }
         }
      }
   }

   /**
    * Delete old os settings
    *
    * @param $glpi_computers_id integer : glpi computer id.
    * @param $itemtype integer : device type identifier.
    *
    * @param $cfg_ocs
    *
    * @return nothing .
    */
   static function resetOS($glpi_computers_id, $history_hardware) {

      $linktype = 'Item_OperatingSystem';

      $item = new $linktype();
      $item->deleteByCriteria(['items_id'   => $glpi_computers_id,
                               'itemtype'   => 'Computer',
                               'is_dynamic' => 1
                              ], 1, $history_hardware
      );
   }
}
