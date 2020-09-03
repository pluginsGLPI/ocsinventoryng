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
 * Class PluginOcsinventoryngBitlockerstatus
 */
class PluginOcsinventoryngBitlockerstatus extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Computer';
   static public $items_id = 'computers_id';

   static $rightname = "plugin_ocsinventoryng";

   /**
    * @param int $nb
    *
    * @return string
    */
   static function getTypeName($nb = 0) {
      return __('Bitlocker', 'ocsinventoryng');
   }

   /**
    * Update config of the Bitlockers
    *
    * This function erase old data and import the new ones about Bitlockers
    *
    * @param $computers_id integer : glpi computer id.
    * @param $ocsComputer
    * @param $history_plugins boolean
    * @param $force
    */
   static function updateBitlocker($computers_id, $ocsBitlockerStatus, $disk, $cfg_ocs, $force = 0) {

      $uninstall_history = 0;
      $item_disk = new Item_Disk();
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_plugins'] == 1 || $cfg_ocs['history_plugins'] == 3)) {
         $uninstall_history = 1;
      }
      $install_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_plugins'] == 1 || $cfg_ocs['history_plugins'] == 2)) {
         $install_history = 1;
      }

      $bitlockers = new self();
      //update data
      foreach ($ocsBitlockerStatus as $bitlockerstatus) {

         $bitlocker             = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($bitlockerstatus));
         $input                 = [];
         $inputBitlockers       = [];
         $inputBitlockers["computers_id"] = $computers_id;
         $inputBitlockers["item_disks_id"] = $disk['id'];

         if (!empty($bitlocker) && isset($bitlocker["DRIVE"])) {
            if($bitlocker["DRIVE"] == $disk['mountpoint']){
               $statusText = isset($bitlocker["CONVERSIONSTATUS"]) ? $bitlocker["CONVERSIONSTATUS"] : '';
               $status = [
                  'FullyDecrypted' => 0,
                  'FullyEncrypted' => 1,
                  'EncryptionInProgress' => 2,
                  'DecryptionInProgress' => 2,
                  'EncryptionPaused' => 2,
                  'DecryptionPaused' => 2
               ];
               $input["encryption_status"]           = $status[$statusText];
               $input["encryption_tool"]             = 'bitlocker';
               $input["encryption_algorithm"]        = isset($bitlocker["ENCRYPMETHOD"]) ? $bitlocker["ENCRYPMETHOD"] : '';
//               $input["encryption_type"] =
               $inputBitlockers["volume_type"]       = isset($bitlocker["VOLUMETYPE"]) ? $bitlocker["VOLUMETYPE"] : '';
               $inputBitlockers["protection_status"] = isset($bitlocker["PROTECTIONSTATUS"]) ? $bitlocker["PROTECTIONSTATUS"] : '';
               $inputBitlockers["init_project"]      = isset($bitlocker["INITPROTECT"]) ? $bitlocker["INITPROTECT"] : '';

               if($item_disk->getFromDB($disk['id'])){
                  $input['id'] = $disk['id'];
                  $item_disk->update($input, $install_history, ['disable_unicity_check' => true]);
               }
               if($bitlockers->getFromDBByCrit(["computers_id" => $computers_id])){
                  $inputBitlockers['id'] = $bitlockers->getID();
                  $bitlockers->update($inputBitlockers, $install_history, ['disable_unicity_check' => true]);
               } else{
                  $bitlockers->add($inputBitlockers, ['disable_unicity_check' => true], $install_history);
               }
            }
//            $input["drive"]             = isset($bitlocker["DRIVE"]) ? $bitlocker["DRIVE"] : '';
//            $input["volume_type"]       = isset($bitlocker["VOLUMETYPE"]) ? $bitlocker["VOLUMETYPE"] : '';
//            $input["conversion_status"] = isset($bitlocker["CONVERSIONSTATUS"]) ? $bitlocker["CONVERSIONSTATUS"] : '';
//            $input["protection_status"] = isset($bitlocker["PROTECTIONSTATUS"]) ? $bitlocker["PROTECTIONSTATUS"] : '';
//            $input["encryp_method"]     = isset($bitlocker["ENCRYPMETHOD"]) ? $bitlocker["ENCRYPMETHOD"] : '';
//            $input["init_project"]      = isset($bitlocker["INITPROTECT"]) ? $bitlocker["INITPROTECT"] : '';

//            if($bitlockers->getFromDBByCrit(["computers_id" => $input["computers_id"]])){
//               $input['id'] = $bitlockers->getID();
//               $bitlockers->update($input, $install_history, ['disable_unicity_check' => true]);
//            } else{
//               $bitlockers->add($input, ['disable_unicity_check' => true], $install_history);
//            }
         }
      }
   }

   /**
    * Delete old Bitlocker entries
    *
    * @param $glpi_computers_id integer : glpi computer id.
    * @param $uninstall_history boolean
    */
   static function resetBitlocker($glpi_computers_id, $uninstall_history) {

      $bitlocker = new self();
      $bitlocker->deleteByCriteria(['computers_id' => $glpi_computers_id], 1, $uninstall_history);

   }

   /**
    * @see CommonGLPI::getTabNameForItem()
    *
    * @param \CommonGLPI $item
    * @param int         $withtemplate
    *
    * @return array|string
    * @throws \GlpitestSQLError
    */
//   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
//
//      $plugin_ocsinventoryng_ocsservers_id = PluginOcsinventoryngOcslink::getOCSServerForItem($item);
//      if ($plugin_ocsinventoryng_ocsservers_id > 0
//         && PluginOcsinventoryngOcsServer::serverIsActive($plugin_ocsinventoryng_ocsservers_id)) {
//
//         PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
//         $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
//         // can exists for template
//         if (($item->getType() == 'Computer')
//            && Computer::canView()
//            && $cfg_ocs["import_bitlocker"]) {
//            $nb = 0;
//            if ($_SESSION['glpishow_count_on_tabs']) {
//               $dbu = new DbUtils();
//               $nb = $dbu->countElementsInTable('glpi_plugin_ocsinventoryng_bitlockerstatuses',
//                  ["computers_id" => $item->getID()]);
//            }
//            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
//         }
//         return '';
//      }
//      return '';
//   }


   /**
    * @param $item            CommonGLPI object
    * @param $tabnum (default 1)
    * @param $withtemplate (default 0)
    *
    * @return bool|true
    */
//   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
//
//      self::showForComputer($item, $withtemplate);
//      return true;
//   }

   /**
    * Print the computers windows update states
    *
    * @param             $comp                  Computer object
    * @param bool|string $withtemplate boolean  Template or basic item (default '')
    *
    * @return bool
    */
   // TODO
   static function showForDisk($item) {
      global $DB;
      if(get_class($item['item']) == Item_Disk::class){
         $bitlockerstatus = new self();

         $computers_id = $item['item']->getField('items_id');
         $item_disks_id = $item['item']->getField('id');

         if ($bitlockerstatus->getFromDBByCrit(['computers_id' => $computers_id, 'item_disks_id' => $item_disks_id])) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Volume type', 'ocsinventoryng') . "</td>";
            echo "<td>" . Html::input('volume_type', ['value' => $bitlockerstatus->getField('volume_type')]) . "</td>";
            echo "<td>" . __('Protection status', 'ocsinventoryng') . "</td>";
            echo "<td>" . Html::input('protection_status', ['value' => $bitlockerstatus->getField('protection_status')]) . "</td>";
            echo "</tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Volume initialization for protection', 'ocsinventoryng') . "</td>";
            echo "<td>" . Html::input('init_project', ['value' => $bitlockerstatus->getField('init_project')]) . "</td>";
            echo "</tr>";
            }
      }
   }
}