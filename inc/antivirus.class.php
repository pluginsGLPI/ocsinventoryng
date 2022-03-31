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
 * Class PluginOcsinventoryngAntivirus
 */
class PluginOcsinventoryngAntivirus extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Computer';
   static public $items_id = 'computers_id';

   static $rightname = "plugin_ocsinventoryng";

   /**
    * Update config of the antivirus
    *
    * This function erase old data and import the new ones about antivirus
    *
    * @param $computers_id integer : glpi computer id.
    * @param $ocsComputer
    * @param $cfg_ocs array : ocs config
    * @param $force
    */
   static function updateAntivirus($computers_id, $ocsComputer, $cfg_ocs, $force) {

      $uninstall_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_plugins'] == 1 || $cfg_ocs['history_plugins'] == 3)) {
         $uninstall_history = 1;
      }
      $install_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_plugins'] == 1 || $cfg_ocs['history_plugins'] == 2)) {
         $install_history = 1;
      }

      if ($force) {
         self::resetAntivirus($computers_id, $uninstall_history);
      }
      $av = new ComputerAntivirus();
      //update data
      foreach ($ocsComputer as $anti) {

         $antivirus = Glpi\Toolbox\Sanitizer::sanitize($anti);
         $input     = [];

         if (isset($antivirus["CATEGORY"]) && $antivirus["CATEGORY"] == "AntiVirus") {
            $input["computers_id"]      = $computers_id;
            $input["name"]              = $antivirus["PRODUCT"];
            $input["manufacturers_id"]  = Dropdown::importExternal('Manufacturer',
                                                                   PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'],
                                                                                             $antivirus["COMPANY"]));
            $input["antivirus_version"] = $antivirus["VERSION"];
            $input["is_active"]         = $antivirus["ENABLED"];
            $input["is_uptodate"]       = $antivirus["UPTODATE"];
            $input["is_dynamic"]        = 1;
            $av->add($input, ['disable_unicity_check' => true], $install_history);
         }
      }
   }

   /**
    * Delete old antivirus entries
    *
    * @param $glpi_computers_id integer : glpi computer id.
    * @param $uninstall_history boolean
    *
    */
   static function resetAntivirus($glpi_computers_id, $uninstall_history) {

      $av = new ComputerAntivirus();
      $av->deleteByCriteria(['computers_id' => $glpi_computers_id,
                             'is_dynamic'   => 1], 1, $uninstall_history);

   }
}
