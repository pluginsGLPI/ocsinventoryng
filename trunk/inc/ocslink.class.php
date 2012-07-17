<?php
/*
 * @version $Id: ocslink.class.php 14685 2011-06-11 06:40:30Z remi $
 -------------------------------------------------------------------------
 ocinventoryng - TreeView browser plugin for GLPI
 Copyright (C) 2012 by the ocinventoryng Development Team.

 https://forge.indepnet.net/projects/ocinventoryng
 -------------------------------------------------------------------------

 LICENSE

 This file is part of ocinventoryng.

 ocinventoryng is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 ocinventoryng is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with ocinventoryng; If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// CLASSES PluginOcsinventoryngOcslink
class PluginOcsinventoryngOcslink extends CommonDBTM {
   const HISTORY_OCS_IMPORT         = 8;
   const HISTORY_OCS_DELETE         = 9;
   const HISTORY_OCS_IDCHANGED      = 10;
   const HISTORY_OCS_LINK           = 11;
   const HISTORY_OCS_TAGCHANGED     = 12;


   static function getTypeName($nb=0) {
      return _n('OCSNG link', 'OCSNG links', $nb);
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
   * @param $item                   CommonDBTM object
   * @param $withtemplate  integer  withtemplate param (default '')
   *
   * @return nothing
   **/
   static function showForItem(CommonDBTM $item, $withtemplate='') {
      global $DB;

      $target = Toolbox::getItemTypeFormURL(__CLASS__);

      if (in_array($item->getType(), array('Computer'))) {
         $items_id = $item->getField('id');

         if (!empty($items_id )
             && $item->fields["is_ocs_import"]
             && plugin_ocsinventoryng_haveRight("view_ocsng","r")) {

            $query = "SELECT *
                      FROM `glpi_plugin_ocsinventoryng_ocslinks`
                      WHERE `computers_id` = '$items_id' ".
                            getEntitiesRestrictRequest("AND", "glpi_plugin_ocsinventoryng_ocslinks");

            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               $data = $DB->fetch_assoc($result);
               $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));

               if (count($data)) {
                  echo "<div class='center'>";
                  echo "<form method='post' action=\"$target\">";
                  echo "<input type='hidden' name='id' value='$items_id'>";
                  echo "<table class='tab_cadre_fixe'>";
                  echo "<tr><th colspan = '4'>OCS Inventory NG</th>";

                  echo "<tr class='tab_bg_1'>";
                  echo "<td class='center' colspan='2'>".__('Server')."&nbsp;";
                  echo "<a href='ocsserver.form.php?id=".
                         PluginOcsinventoryngOcsServer::getByMachineID($items_id)."'>".
                         PluginOcsinventoryngOcsServer::getServerNameByID($items_id)."</a>";

                  $query = "SELECT `ocs_agent_version`, `ocsid`
                            FROM `glpi_plugin_ocsinventoryng_ocslinks`
                            WHERE `computers_id` = '$items_id'";

                  $result_agent_version = $DB->query($query);
                  $data_version         = $DB->fetch_array($result_agent_version);

                  $ocs_config = PluginOcsinventoryngOcsServer::getConfig(PluginOcsinventoryngOcsServer::getByMachineID($items_id));

                  //If have write right on OCS and ocsreports url is not empty in OCS config
                  if (plugin_ocsinventoryng_haveRight("ocsng","w")
                      && ($ocs_config["ocs_url"] != '')) {
                     echo ", ".PluginOcsinventoryngOcsServer::getComputerLinkToOcsConsole(PluginOcsinventoryngOcsServer::getByMachineID($items_id),
                                                                                          $data_version["ocsid"],
                                                                                          __('Interface'));
                  }

                  if ($data_version["ocs_agent_version"] != NULL) {
                     echo " , ".sprintf(__('%1$s: %2$s'), __('Inventory agent'),
                                        $data_version["ocs_agent_version"]);
                  }

                  echo "</td>";

                  echo "<td class='center' colspan='2'>";
                  printf(__('%1$s: %2$s'), __('Last OCSNG inventory date'),
                         Html::convDateTime($data["last_ocs_update"]));
                  echo "<br>";
                  printf(__('%1$s: %2$s'), __('Date of import in GLPI'),
                         Html::convDateTime($data["last_update"]));
                  echo "</td></tr>";

                  echo "<tr class='tab_bg_1'>";

                  $colspan = 4;
                  if (plugin_ocsinventoryng_haveRight("view_ocsng","r")
                      && plugin_ocsinventoryng_haveRight("sync_ocsng","w")) {

                     $colspan = 2;
                     echo "<td class='center'>".__('Automatic OCSNG update')."</td>";
                     echo "<td class='left'>";
                     Dropdown::showYesNo("use_auto_update", $data["use_auto_update"]);
                     echo "</td>";
                  }
                  echo "<td class='center' colspan='".$colspan."'>";
                  printf(__('%1$s: %2$s'), __('TAG'), $data['tag']);
                  echo "</td></tr>";

                  echo "<tr class='tab_bg_1'>";
                  echo "<td class='center' colspan='4'>";
                  echo "<input type='hidden' name='link_id' value='" . $data["id"] . "'>";
                  echo "<input class=submit type='submit' name='update' value=\"" .
                         _sx('button', 'validate')."\">";
                  echo "</td></tr>";
                  echo "</table>\n";
                  Html::closeForm();
                  echo "</div>";
               }
            }
         }
      }
   }


   /**
    * Update lockable fields of an item
    *
    * @param $item                     CommonDBTM object
    * @param $withtemplate    integer  withtemplate param (default '')
    *
    * @return nothing
   **/
   static function updateComputer(CommonDBTM $item, $withtemplate='') {
      global $DB;

      // Manage changes for OCS if more than 1 element (date_mod)
      // Need dohistory==1 if dohistory==2 no locking fields
      if ($item->fields["is_ocs_import"]
          && ($item->dohistory == 1)
          && (count($item->updates) > 1)) {

         PluginOcsinventoryngOcsServer::mergeOcsArray($item->fields["id"], $item->updates,
                                                      "computer_update");
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
    * @param $item                     CommonDBTM object
    * @param $withtemplate    integer  withtemplate param (default '')
    *
    * @return nothing
   **/
   static function addComputer_Item(CommonDBTM $item, $withtemplate='') {
      global $DB;

      switch ($item->input['itemtype']) {
         case 'Monitor' :
            $link   = new Monitor();
            $ocstab = 'import_monitor';
            break;

         case 'Phone' :
            // shoul really never occurs as OCS doesn't sync phone
            $link   = new Phone();
            $ocstab = '';
            break;

         case 'Printer' :
            $link   = new Printer();
            $ocstab = 'import_printer';
            break;

         case 'Peripheral' :
            $link   = new Peripheral();
            $ocstab = 'import_peripheral';
            break;

         default :
            return false;
      }
      if (!$link->getFromDB($item->input['items_id'])) {
         return false;
      }
      if (!$link->getField('is_global') ) {
         // Handle case where already used, should never happen (except from OCS sync)
         $query = "SELECT `id`, `computers_id`
                   FROM `glpi_computers_items`
                   WHERE `glpi_computers_items`.`items_id` = '".$item->input['items_id']."'
                         AND `glpi_computers_items`.`itemtype` = '".$item->input['itemtype']."'";
         $result = $DB->query($query);

         while ($data = $DB->fetch_assoc($result)) {
            $temp = clone $item;
            $temp->delete($data);
            if ($ocstab) {
               PluginOcsinventoryngOcsServer::deleteInOcsArray($data["computers_id"], $data["id"],
                                                               $ocstab);
            }
         }
      }
   }


   /**
    * if Computer deleted
    *
    * @param $comp   Computer object
   **/
   static function purgeComputer(Computer $comp) {

      $link = new self();
      $link->deleteByCriteria(array('computers_id' => $comp->getField("id")));

      $reg = new PluginOcsinventoryngRegistryKey();
      $reg->deleteByCriteria(array('computers_id' => $comp->getField("id")));
   }


   /**
    * if Computer_Item deleted
    *
    * @param $comp   Computer_Item object
   **/
   static function purgeComputer_Item(Computer_Item $comp) {
      //TODO see Computer_Item function cleanDBonPurge()
   }


   /**
    * @param $comp   Computer object
   **/
   static function editLock(Computer $comp) {
      global $DB;

      $ID     = $comp->getID();
      $target = Toolbox::getItemTypeFormURL(__CLASS__);

      if (!Session::haveRight("computer","w")) {
         return false;
      }
      $query = "SELECT *
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `computers_id` = '$ID'";

      $result = $DB->query($query);
      if ($DB->numrows($result) == 1) {
         $data = $DB->fetch_assoc($result);
         if (plugin_ocsinventoryng_haveRight("sync_ocsng","w")) {
            echo "<form method='post' action=\"$target\">";
            echo "<input type='hidden' name='id' value='$ID'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'><td class='center'>";
            echo "<input type='hidden' name='resynch_id' value='" . $data["id"] . "'>";
            echo "<input class=submit type='submit' name='force_ocs_resynch' value=\"" .
                   _sx('button', 'Force synchronization'). "\">";
            echo "</table>\n";
            eHtml::closeForm();
         }

         echo "</table></div>";

         $header = false;
         echo "<div width='50%'>";
         echo "<form method='post' action=\"$target\">";
         echo "<input type='hidden' name='id' value='$ID'>\n";
         echo "<table class='tab_cadre_fixe'>";

         // Print lock fields for OCSNG
         $lockable_fields = PluginOcsinventoryngOcsServer::getLockableFields();
         $locked          = importArrayFromDB($data["computer_update"]);

         if (!in_array(PluginOcsinventoryngOcsServer::IMPORT_TAG_078, $locked)) {
            $locked = PluginOcsinventoryngOcsServer::migrateComputerUpdates($ID, $locked);
         }

         if (count($locked) > 0) {
            foreach ($locked as $key => $val) {
               if (!isset($lockable_fields[$val])) {
                  unset($locked[$key]);
               }
            }
         }

         if (count($locked)) {
            $header = true;
            echo "<tr><th colspan='2'>". _n('Locked field', 'Locked fields', 2)."</th></tr>\n";

            foreach ($locked as $key => $val) {
               echo "<tr class='tab_bg_1'>";
               echo "<td class='right' width='50%'>" . $lockable_fields[$val] . "</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='lockfield[" . $key . "]'></td></tr>\n";
            }
         }

         //Search locked monitors
         $locked_monitor = importArrayFromDB($data["import_monitor"]);
         $first          = true;

         foreach ($locked_monitor as $key => $val) {
            if ($val != "_version_070_") {
               $querySearchLockedMonitor = "SELECT `items_id`
                                            FROM `glpi_computers_items`
                                            WHERE `id` = '$key'";
               $resultSearchMonitor = $DB->query($querySearchLockedMonitor);

               if ($DB->numrows($resultSearchMonitor) == 0) {
                  $header = true;
                  if ($first) {
                     echo "<tr><th colspan='2'>"._n('Locked monitor', 'Locked monitors', 2)."</th>".
                          "</tr>\n";
                     $first = false;
                  }

                  echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $val . "</td>";
                  echo "<td class='left' width='50%'>";
                  echo "<input type='checkbox' name='lockmonitor[" . $key . "]'></td></tr>\n";
               }
            }
         }

         //Search locked printers
         $locked_printer = importArrayFromDB($data["import_printer"]);
         $first          = true;

         foreach ($locked_printer as $key => $val) {
            $querySearchLockedPrinter = "SELECT `items_id`
                                         FROM `glpi_computers_items`
                                         WHERE `id` = '$key'";
            $resultSearchPrinter = $DB->query($querySearchLockedPrinter);

            if ($DB->numrows($resultSearchPrinter) == 0) {
               $header = true;
               if ($first) {
                  echo "<tr><th colspan='2'>"._n('Locked printer', 'Locked printers', 2)."</th>".
                       "</tr>\n";
                  $first = false;
               }

               echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $val . "</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='lockprinter[" . $key . "]'></td></tr>\n";
            }
         }

         // Search locked peripherals
         $locked_periph = importArrayFromDB($data["import_peripheral"]);
         $first         = true;

         foreach ($locked_periph as $key => $val) {
            $querySearchLockedPeriph = "SELECT `items_id`
                                        FROM `glpi_computers_items`
                                        WHERE `id` = '$key'";
            $resultSearchPeriph = $DB->query($querySearchLockedPeriph);

            if ($DB->numrows($resultSearchPeriph) == 0) {
               $header = true;
               if ($first) {
                  echo "<tr><th colspan='2'>"._n('Locked device', 'Locked devices', 2)."</th>".
                       "</tr>\n";
                  $first = false;
               }

               echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $val . "</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='lockperiph[" . $key . "]'></td></tr>\n";
            }
         }

         // Search locked IP
         $locked_ip = importArrayFromDB($data["import_ip"]);

         if (!in_array(PluginOcsinventoryngOcsServer::IMPORT_TAG_072,$locked_ip)) {
            $locked_ip = PluginOcsinventoryngOcsServer::migrateImportIP($ID,$locked_ip);
         }
         $first = true;

         foreach ($locked_ip as $key => $val) {
            if ($key>0) {
               $tmp = explode(PluginOcsinventoryngOcsServer::FIELD_SEPARATOR,$val);
               $querySearchLockedIP = "SELECT *
                                       FROM `glpi_networkports`
                                       WHERE `items_id` = '$ID'
                                             AND `itemtype` = 'Computer'
                                             AND `ip` = '".$tmp[0]."'
                                             AND `mac` = '".$tmp[1]."'";
               $resultSearchIP = $DB->query($querySearchLockedIP);

               if ($DB->numrows($resultSearchIP) == 0) {
                  $header = true;
                  if ($first) {
                     echo "<tr><th colspan='2'>" ._n('Locked IP', 'Locked IP', 2). "</th></tr>\n";
                     $first = false;
                  }
                  echo "<tr class='tab_bg_1'><td class='right' width='50%'>" .
                         str_replace(PluginOcsinventoryngOcsServer::FIELD_SEPARATOR, ' / ', $val) .
                       "</td>";
                  echo "<td class='left' width='50%'>";
                  echo "<input type='checkbox' name='lockip[" . $key . "]'></td></tr>\n";
               }
            }
         }

         // Search locked softwares
         $locked_software = importArrayFromDB($data["import_software"]);
         $first           = true;

         foreach ($locked_software as $key => $val) {
            if ($val != "_version_070_") {
               $querySearchLockedSoft = "SELECT `id`
                                         FROM `glpi_computers_softwareversions`
                                         WHERE `id` = '$key'";
               $resultSearchSoft = $DB->query($querySearchLockedSoft);

               if ($DB->numrows($resultSearchSoft) == 0) {
                  $header = true;
                  if ($first) {
                     echo "<tr><th colspan='2'>"._n('Locked software', 'Locked software', 2).
                          "</th></tr>\n";
                     $first = false;
                  }
                  echo "<tr class='tab_bg_1'>";
                  echo "<td class='right'width='50%'>" . str_replace('$$$$$',' v. ',$val) . "</td>";
                  echo "<td class='left'width='50%'>";
                  echo "<input type='checkbox' name='locksoft[" . $key . "]'></td></tr>";
               }
            }
         }

         // Search locked computerdisks
         $locked_disk = importArrayFromDB($data["import_disk"]);
         $first       = true;

         foreach ($locked_disk as $key => $val) {
            $querySearchLockedDisk = "SELECT `id`
                                       FROM `glpi_computerdisks`
                                       WHERE `id` = '$key'";
            $resultSearchDisk = $DB->query($querySearchLockedDisk);

            if ($DB->numrows($resultSearchDisk) == 0) {
               $header = true;
               if ($first) {
                  echo "<tr><th colspan='2'>" ._n('Locked disk', 'Locked disks', 2). "</th></tr>\n";
                  $first = false;
               }
               echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $val . "</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='lockdisk[" . $key . "]'></td></tr>\n";
            }
         }

         // Search locked computervirtualmachines
         $locked_vm = importArrayFromDB($data["import_vm"]);
         $first     = true;

         foreach ($locked_vm as $key => $val) {
            $nb = countElementsInTable('glpi_computervirtualmachines', "`id`='$key'");
            if ($nb == 0) {
               $header = true;
               if ($first) {
                  echo "<tr><th colspan='2'>"._n('Virtual machine', 'Virtual machines', 2)."</th>".
                       "</tr>\n";
                  $first = false;
               }
               echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $val . "</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='lockvm[" . $key . "]'></td></tr>\n";
            }
         }

         // Search for locked devices
         $locked_dev = importArrayFromDB($data["import_device"]);
         if (!in_array(PluginOcsinventoryngOcsServer::IMPORT_TAG_078, $locked_dev)) {
            $locked_dev = PluginOcsinventoryngOcsServer::migrateImportDevice($ID, $locked_dev);
         }
         $types = Computer_Device::getDeviceTypes();
         $first = true;
         foreach ($locked_dev as $key => $val) {
            if (!$key) { // OcsServer::IMPORT_TAG_078
               continue;
            }
            list($type, $nomdev) = explode(PluginOcsinventoryngOcsServer::FIELD_SEPARATOR, $val);
            list($type, $iddev)  = explode(PluginOcsinventoryngOcsServer::FIELD_SEPARATOR, $key);
            if (!isset($types[$type])) { // should never happen
               continue;
            }
            $compdev = new Computer_Device($types[$type]);
            if (!$compdev->getFromDB($iddev)) {
               $header = true;
               if ($first) {
                  echo "<tr><th colspan='2'>"._n('Locked component', 'Locked components', 2).
                       "</th></tr>\n";
                  $first = false;
               }
               $device = new $types[$type]();
               echo "<tr class='tab_bg_1'><td align='right' width='50%'>";
               echo $device->getTypeName()."&nbsp;: $nomdev</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='lockdevice[" . $key . "]'></td></tr>\n";
            }
         }

         if ($header) {
            echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
            echo "<input class='submit' type='submit' name='unlock' value='".
                  _sx('button', 'Unlock'). "'></td></tr>";
         } else {
            echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
            echo __('No locked field')."</td></tr>";
         }

         echo "</table>";
         Html::closeForm();
         echo "</div>\n";
      }
   }


   /**
    * @see inc/CommonGLPI::getTabNameForItem()
    *
    * @param $item               CommonGLPI object
    * @param$withtemplate        (default 0)
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (in_array($item->getType(), PluginOcsinventoryngOcsServer::getTypes(true))
          && $this->canView()) {

         switch ($item->getType()) {
            case 'Computer' :
               return array('1' => __('OCSNG mode'));
         }
      }
      return '';
   }


   /**
    * @param $item            CommonGLPI object
    * @param $tabnum          (default 1)
    * @param $withtemplate    (default 0)
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if (in_array($item->getType(), PluginOcsinventoryngOcsServer::getTypes(true))) {
         switch ($item->getType()) {
            case 'Computer' :
               self::showForItem($item, $withtemplate);
               self::editLock($item);
               break;
         }
      }
      return true;
   }


   /**
    * Add an history entry to a computer
    *
    * @param $computers_id Integer, ID of the computer
    * @param $changes      Array, see Log::history
    * @param $action       Integer in PluginOcsinventoryngOcslink::HISTORY_OCS_*
    *
    * @return Integer id of the inserted entry
   **/
   static function history($computers_id, $changes, $action) {
      return Log::history($computers_id, 'Computer', $changes, __CLASS__, Log::HISTORY_PLUGIN+$action);
   }

   /**
    * Get an history entry message
    *
    * @param $data Array from glpi_logs table
    *
    * @return string
   **/
   static function getHistoryEntry($data) {

      if (plugin_ocsinventoryng_haveRight('ocsng', 'r')) {
         switch($data['linked_action'] - Log::HISTORY_PLUGIN) {
            case self::HISTORY_OCS_IMPORT :
               return sprintf(__('%1$s: %2$s'), __('Imported from OCS'), $data['new_value']);

            case self::HISTORY_OCS_DELETE :
               return sprintf(__('%1$s: %2$s'), __('Deleted in OCSNG'), $data['old_value']);

            case self::HISTORY_OCS_LINK :
               return sprintf(__('%1$s: %2$s'), __('Linked with an OCSNG computer'), $data['new_value']);

            case self::HISTORY_OCS_IDCHANGED :
               return  sprintf(__('The OCSNG ID of the computer changed from %1$s to %2$s'),
                                  $data['old_value'], $data['new_value']);

            case self::HISTORY_OCS_TAGCHANGED :
               return  sprintf(__('The OCSNG TAG of the computer changed from %1$s to %2$s'),
                                  $data['old_value'], $data['new_value']);
         }
      }
      return '';
   }
}
?>