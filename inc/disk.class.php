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
 * Class PluginOcsinventoryngDisk
 */
class PluginOcsinventoryngDisk extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Computer';
   static public $items_id = 'computers_id';

   static $rightname = "plugin_ocsinventoryng";


   /**
    * Update config of a new Disk
    *
    * This function create a new disk in GLPI with some general datas.
    *
    * @param $computers_id integer : glpi computer id.
    * @param $ocsComputer
    * @param $ocsservers_id integer : ocs server id
    * @param $bitlockerstatus
    * @param $ocsBitlockerStatus
    * @param $cfg_ocs
    * @param $force
    *
    * @return void .
    * @throws \GlpitestSQLError
    * @internal param int $ocsid : ocs computer id (ID).
    */
   static function updateDisk($computers_id, $ocsComputer, $ocsservers_id, $bitlockerstatus, $ocsBitlockerStatus, $cfg_ocs, $force) {
      global $DB;

      $uninstall_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_drives'] == 1 || $cfg_ocs['history_drives'] == 3)) {
         $uninstall_history = 1;
      }
      $install_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_drives'] == 1 || $cfg_ocs['history_drives'] == 2)) {
         $install_history = 1;
      }

      if ($force) {
         self::resetDisks($computers_id,$ocsBitlockerStatus, $uninstall_history);
      }

      $already_processed = [];
      $logical_drives    = $ocsComputer;

      $d = new Item_Disk();
      if (count($logical_drives) > 0) {
         foreach ($logical_drives as $logical_drive) {
            $logical_drive = Glpi\Toolbox\Sanitizer::sanitize($logical_drive);

            // Only not empty disk
            if ($logical_drive['TOTAL'] > 0) {
               $disk               = [];
               $disk['items_id']   = $computers_id;
               $disk['itemtype']   = 'Computer';
               $disk['is_dynamic'] = 1;

               // TYPE : vxfs / ufs  : VOLUMN = mount / FILESYSTEM = device
               if (in_array($logical_drive['TYPE'], ["vxfs", "ufs"])) {
                  $disk['name']           = $logical_drive['VOLUMN'];
                  $disk['mountpoint']     = $logical_drive['VOLUMN'];
                  $disk['device']         = $logical_drive['FILESYSTEM'];
                  $disk['filesystems_id'] = Dropdown::importExternal('Filesystem', $logical_drive["TYPE"]);
               } else if (in_array($logical_drive['FILESYSTEM'], ['ext2', 'ext3', 'ext4', 'ffs',
                                                                  'fuseblk', 'fusefs', 'hfs', 'jfs',
                                                                  'jfs2', 'Journaled HFS+', 'nfs',
                                                                  'ocfs2', 'smbfs', 'reiserfs', 'vmfs',
                                                                  'VxFS', 'ufs', 'xfs', 'zfs'])) {
                  // Try to detect mount point : OCS database is dirty
                  $disk['mountpoint'] = $logical_drive['VOLUMN'];
                  $disk['device']     = $logical_drive['TYPE'];

                  // Found /dev in VOLUMN : invert datas
                  if (strstr($logical_drive['VOLUMN'], '/dev/')) {
                     $disk['mountpoint'] = $logical_drive['TYPE'];
                     $disk['device']     = $logical_drive['VOLUMN'];
                  }

                  if ($logical_drive['FILESYSTEM'] == "vmfs") {
                     $disk['name'] = basename($logical_drive['TYPE']);
                  } else {
                     $disk['name'] = $disk['mountpoint'];
                  }
                  $disk['filesystems_id'] = Dropdown::importExternal('Filesystem', $logical_drive["FILESYSTEM"]);
               } else if (in_array($logical_drive['FILESYSTEM'], ['FAT', 'FAT32', 'NTFS'])) {
                  if (!empty($logical_drive['VOLUMN'])) {
                     $disk['name'] = $logical_drive['VOLUMN'];
                  } else {
                     $disk['name'] = $logical_drive['LETTER'];
                  }
                  $disk['mountpoint']     = $logical_drive['LETTER'];
                  $disk['filesystems_id'] = Dropdown::importExternal('Filesystem', $logical_drive["FILESYSTEM"]);
               }

               // Ok import disk
               if (isset($disk['name']) && !empty($disk["name"])) {
                  $disk['totalsize'] = $logical_drive['TOTAL'];
                  $disk['freesize']  = $logical_drive['FREE'];

                  $query   = "SELECT `id`
                            FROM `glpi_items_disks`
                            WHERE `items_id`= $computers_id
                              AND `itemtype`= 'Computer'
                               AND `name`='" . $disk['name'] . "'
                               AND `is_dynamic` = 1";
                  $results = $DB->query($query);
                  if ($DB->numrows($results) == 1) {
                     $id = $DB->result($results, 0, 'id');
                  } else {
                     $id = false;
                  }

                  if (!$id) {
                     $d->reset();
                     $disk['is_dynamic']  = 1;
                     $id_disk             = $d->add($disk, [], $install_history);
                     $disk['id'] = $id_disk;
                     $already_processed[] = $id_disk;
                  } else {
                     // Only update if needed
                     if ($d->getFromDB($id)) {

                        // Update on type, total size change or variation of 5%
                        if ($d->fields['totalsize'] != $disk['totalsize']
                            || ($d->fields['filesystems_id'] != $disk['filesystems_id'])
                            || ((abs($disk['freesize'] - $d->fields['freesize']) / $disk['totalsize']) > 0.05)
                        ) {

                           $toupdate['id']             = $id;
                           $toupdate['totalsize']      = $disk['totalsize'];
                           $toupdate['freesize']       = $disk['freesize'];
                           $toupdate['filesystems_id'] = $disk['filesystems_id'];
                           $d->update($toupdate, $install_history);
                           $disk['id'] = $id;
                        }
                        $already_processed[] = $id;
                     }
                  }
                  if ($bitlockerstatus && isset($ocsBitlockerStatus)) {
                     //import bitlocker
                     PluginOcsinventoryngBitlockerstatus::updateBitlocker($computers_id, $ocsBitlockerStatus, $disk,
                                                                          $cfg_ocs, $force);
                  }
               }
            }
         }
      }
      // Delete Unexisting Items not found in OCS
      //Look for all ununsed disks
      $query = "SELECT `id`
                FROM `glpi_items_disks`
                WHERE `items_id`= $computers_id
                   AND `itemtype`= 'Computer'
                   AND `is_dynamic` = 1 ";
      if (!empty($already_processed)
          && count($already_processed) > 0) {
         $query .= " AND `id` NOT IN (" . implode(',', $already_processed) . ")";
      }
      foreach ($DB->request($query) as $data) {
         //Delete all connexions
         $d->delete(['id'             => $data['id'],
                     '_ocsservers_id' => $ocsservers_id,
                     '_no_history'    => !$uninstall_history],
                    true,
                    $uninstall_history);
      }
   }

   /**
    * Delete all old disks of a computer.
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @param $uninstall_history
    * @return void .
    */
   static function resetDisks($glpi_computers_id, $ocsBitlockerStatus, $uninstall_history) {

      $dd = new Item_Disk();
      $dd->deleteByCriteria(['items_id'   => $glpi_computers_id,
                             'itemtype'   => 'Computer',
                             'is_dynamic' => 1], 1, $uninstall_history);
      if(!empty($ocsBitlockerStatus)){
         PluginOcsinventoryngBitlockerstatus::resetBitlocker($glpi_computers_id, $uninstall_history);
      }
   }
}
