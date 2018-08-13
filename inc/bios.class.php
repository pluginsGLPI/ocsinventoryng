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
 * Class PluginOcsinventoryngBios
 */
class PluginOcsinventoryngBios extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Computer';
   static public $items_id = 'computers_id';

   static $rightname = "plugin_ocsinventoryng";


   /**
    * @param int $plugin_ocsinventoryng_ocsservers_id
    *
    * @return array
    */
   static function getBiosLockableFields($plugin_ocsinventoryng_ocsservers_id = 0) {

      if ($plugin_ocsinventoryng_ocsservers_id > 0) {

         $locks   = [];
         $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);

         if (intval($cfg_ocs["import_general_manufacturer"]) > 0
             && intval($cfg_ocs["import_device_bios"]) > 0) {
            $locks["manufacturers_id"] = __('Manufacturer');
         }

         if (intval($cfg_ocs["import_general_model"]) > 0
             && intval($cfg_ocs["import_device_bios"]) > 0) {
            $locks["computermodels_id"] = __('Model');
         }

         if (intval($cfg_ocs["import_general_serial"]) > 0
             && intval($cfg_ocs["import_device_bios"]) > 0) {
            $locks["serial"] = __('Serial number');
         }

         if (intval($cfg_ocs["import_general_type"]) > 0
             && intval($cfg_ocs["import_device_bios"]) > 0) {
            $locks["computertypes_id"] = __('Type');
         }
      } else {
         $locks = ["manufacturers_id"  => __('Manufacturer'),
                   "computermodels_id" => __('Model'),
                   "serial"            => __('Serial number'),
                   "computertypes_id"  => __('Type')];
      }

      return $locks;
   }

   /**
    * Update the computer bios configuration
    *
    * @param array $params
    *
    */
   static function updateComputerBios($params = []) {

      $compupdate = [];

      if (isset($params["BIOS"])) {
         $bios        = $params['BIOS'];
         $cfg_ocs     = $params['cfg_ocs'];
         $ocs_db_utf8 = $params['cfg_ocs']['ocs_db_utf8'];
         $force       = $params["force"];

         $update_history = 0;
         if ($cfg_ocs['dohistory'] == 1 && $cfg_ocs['history_bios'] == 1) {
            $update_history = 1;
         }

         if ($params['cfg_ocs']["import_general_serial"]
             && $params['cfg_ocs']["import_general_serial"] > 0
             && intval($params['cfg_ocs']["import_device_bios"]) > 0
             && !in_array("serial", $params['computers_updates'])) {
            $compupdate["serial"] = PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($ocs_db_utf8, $bios["SSN"]);
         }

         if (intval($params['cfg_ocs']["import_general_model"]) > 0
             && intval($params['cfg_ocs']["import_device_bios"]) > 0
             && !in_array("computermodels_id", $params['computers_updates'])) {

            $compupdate["computermodels_id"] = Dropdown::importExternal('ComputerModel',
                                                                        PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($ocs_db_utf8,
                                                                                                                            $bios["SMODEL"]),
                                                                        -1,
               (isset($bios["SMANUFACTURER"]) ? ["manufacturer" => $bios["SMANUFACTURER"]] : []));
         }

         if (intval($params['cfg_ocs']["import_general_manufacturer"]) > 0
             && intval($params['cfg_ocs']["import_device_bios"]) > 0
             && !in_array("manufacturers_id", $params['computers_updates'])
         ) {

            $compupdate["manufacturers_id"] = Dropdown::importExternal('Manufacturer',
                                                                       PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($ocs_db_utf8,
                                                                                                                           $bios["SMANUFACTURER"]));
         }

         if (intval($params['cfg_ocs']["import_general_type"]) > 0
             && intval($params['cfg_ocs']["import_device_bios"]) > 0
             && !empty($bios["TYPE"])
             && !in_array("computertypes_id", $params['computers_updates'])) {

            $compupdate["computertypes_id"] = Dropdown::importExternal('ComputerType',
                                                                       PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($ocs_db_utf8,
                                                                                                                           $bios["TYPE"]));
         }

         if (count($compupdate) || $force) {
            Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($compupdate));
            $compupdate["id"]          = $params['computers_id'];
            $compupdate["entities_id"] = $params['entities_id'];
            $comp                      = new Computer();
            $comp->update($compupdate, $update_history);
         }
      }
   }
}
