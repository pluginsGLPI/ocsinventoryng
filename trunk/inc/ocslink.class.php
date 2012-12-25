<?php
/*
 * @version $Id: HEADER 15930 2012-12-15 11:10:55Z tsmr $
-------------------------------------------------------------------------
Ocsinventoryng plugin for GLPI
Copyright (C) 2012-2013 by the ocsinventoryng plugin Development Team.

https://forge.indepnet.net/projects/ocsinventoryng
-------------------------------------------------------------------------

LICENSE

This file is part of accounts.

Ocsinventoryng plugin is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

Ocsinventoryng plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with ocsinventoryng. If not, see <http://www.gnu.org/licenses/>.
---------------------------------------------------------------------------------------------------------------------------------------------------- */

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
      return _n('OCSNG link', 'OCSNG links', $nb, 'ocsinventoryng');
   }


   static function canCreate() {
      return plugin_ocsinventoryng_haveRight('ocsng', 'w');
   }


   static function canView() {
      return plugin_ocsinventoryng_haveRight('ocsng', 'r');
   }

   /**
   * Show simple inventory information of an item
   *
   * @param $item                   CommonDBTM object
   *
   * @return nothing
   **/
   static function showSimpleForItem(CommonDBTM $item) {
      global $DB, $CFG_GLPI;

      $target = Toolbox::getItemTypeFormURL(__CLASS__);

      if (in_array($item->getType(), array('Computer'))) {
         $items_id = $item->getField('id');

         if (!empty($items_id)
             && $item->fields["is_dynamic"]
             && plugin_ocsinventoryng_haveRight("view_ocsng","r")) {
            $query = "SELECT *
                      FROM `glpi_plugin_ocsinventoryng_ocslinks`
                      WHERE `computers_id` = '$items_id' ".
                            getEntitiesRestrictRequest("AND", "glpi_plugin_ocsinventoryng_ocslinks");

            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               $data = $DB->fetch_assoc($result);

               if (count($data)) {
                  $ocs_config = PluginOcsinventoryngOcsServer::getConfig($data['plugin_ocsinventoryng_ocsservers_id']);
            
                  echo "<table class='tab_glpi'>";
                  echo "<th colspan='2'>".__('OCS Inventory NG')."</th>";
                  echo '<tr><td>'.__('Last OCSNG inventory date', 'ocsinventoryng');
                  echo "</td><td>".Html::convDateTime($data["last_ocs_update"]).'</td></tr>';
                  echo '<tr><td>'.__('GLPI import date',  'ocsinventoryng');
                  echo "</td><td>".Html::convDateTime($data["last_update"]).'</td></tr>';
                  echo '<tr><td>'.__('Inventory agent',  'ocsinventoryng');
                  echo "</td><td>".$data["ocs_agent_version"].'</td></tr>';
                  echo '<tr><td>'.__('Server');
                  echo "</td><td>";
                  if (plugin_ocsinventoryng_haveRight("ocsng","r")) {
                     echo "<a href='".$CFG_GLPI["root_doc"]."/plugins/ocsinventoryng/front/ocsserver.form.php?id="
                           .$ocs_config['id']."'>".$ocs_config['name']."</a>";
                  } else {
                     echo $ocs_config['name'];
                  }
                  echo '</td></tr>';
                 //If have write right on OCS and ocsreports url is not empty in OCS config
                  if (plugin_ocsinventoryng_haveRight("ocsng","w")
                      && ($ocs_config["ocs_url"] != '')) {
                     echo "<td colspan='2' class='center'>";
                     echo PluginOcsinventoryngOcsServer::getComputerLinkToOcsConsole($ocs_config['id'],
                                                                                    $data["ocsid"],
                                                                                    __('OCS NG Interface','ocsinventoryng'));
                     echo "</td>";
                  }
                  echo '</table>';
               }
            }
         }
      }
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
      global $DB, $CFG_GLPI;

      $target = Toolbox::getItemTypeFormURL(__CLASS__);

      if (in_array($item->getType(), array('Computer'))) {
         $items_id = $item->getField('id');

         if (!empty($items_id )
             && $item->fields["is_dynamic"]
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
                  $ocs_config = PluginOcsinventoryngOcsServer::getConfig(PluginOcsinventoryngOcsServer::getByMachineID($items_id));
               
                  echo "<div class='center'>";
                  echo "<form method='post' action=\"$target\">";
                  echo "<input type='hidden' name='id' value='$items_id'>";
                  echo "<table class='tab_cadre_fixe'>";
                  echo "<tr><th colspan = '4'>OCS Inventory NG</th>";

                  echo "<tr class='tab_bg_1'>";

                  $colspan = 4;
                  if (plugin_ocsinventoryng_haveRight("view_ocsng","r")
                      && plugin_ocsinventoryng_haveRight("sync_ocsng","w")) {

                     $colspan = 2;
                     echo "<td class='center'>".__('Automatic update OCSNG', 'ocsinventoryng').
                          "</td>";
                     echo "<td class='left'>";
                     Dropdown::showYesNo("use_auto_update", $data["use_auto_update"]);
                     echo "</td>";
                  }
                  echo "<td class='center' colspan='".$colspan."'>";
                  printf(__('%1$s: %2$s'), __('OCSNG TAG', 'ocsinventoryng'), $data['tag']);
                  echo "</td></tr>";

                  echo "<tr class='tab_bg_1'>";
                  echo "<td class='center' colspan='4'>";
                  echo "<input type='hidden' name='link_id' value='" . $data["id"] . "'>";
                  echo "<input class=submit type='submit' name='update' value=\"" .
                         _sx('button', 'Save')."\">";
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
      
      if ($device = getItemForItemtype($comp->fields['itemtype'])) {
         if ($device->getFromDB($comp->fields['items_id'])) {
            
            if (isset($comp->input['_ocsservers_id'])) {
               $ocsservers_id = $comp->input['_ocsservers_id'];
            } else {
               $ocsservers_id = PluginOcsinventoryngOcsServer::getByMachineID($comp->fields['computers_id']);
            }

            if ($ocsservers_id > 0) {
               //Get OCS configuration
               $ocs_config = PluginOcsinventoryngOcsServer::getConfig($ocsservers_id);

               //Get the management mode for this device
               $mode = PluginOcsinventoryngOcsServer::getDevicesManagementMode($ocs_config,
                                                                              $comp->fields['itemtype']);
               $decoConf = $ocs_config["deconnection_behavior"];

               //Change status if :
               // 1 : the management mode IS NOT global
               // 2 : a deconnection's status have been defined
               // 3 : unique with serial
               if (($mode >= 2)
               && (strlen($decoConf) > 0)) {

                  //Delete periph from glpi
                  if ($decoConf == "delete") {
                     $tmp["id"] = $comp->fields['items_id'];
                     $device->delete($tmp, 1);

                  //Put periph in dustbin
                  } else if ($decoConf == "trash") {
                     $tmp["id"] = $comp->fields['items_id'];
                     $device->delete($tmp, 0);
                  }
               }
            } // $ocsservers_id>0
         }
      }
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
                   _sx('button', 'Force synchronization', 'ocsinventoryng'). "\">";
            echo "</table>\n";
            Html::closeForm();
         }

         echo "</table></div>";

         $header = false;
         echo "<div width='50%'>";
         echo "<form method='post' id='ocsng_form' name='ocsng_form' action=\"$target\">";
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
            echo "<tr><th colspan='2'>". _n('Locked field', 'Locked fields', 2, 'ocsinventoryng').
                 "</th></tr>\n";

            foreach ($locked as $key => $val) {
               echo "<tr class='tab_bg_1'>";
               echo "<td class='right' width='50%'>" . $lockable_fields[$val] . "</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='lockfield[" . $key . "]'></td></tr>\n";
            }
         }

         $types = array('Monitor', 'Printer', 'Peripheral');
         
         foreach($types as $itemtype) {
            $item   = new $itemtype();
            $params = array('is_dynamic' => 1, 'is_deleted' => 1, 'computers_id' => $comp->getID(),
                            'itemtype' => $itemtype);
            $first  = true;
            $locale = "Locked ".strtolower($itemtype);
            foreach ($DB->request('glpi_computers_items', $params, array('id', 'items_id')) as $line) {
               $item->getFromDB($line['items_id']);
               $header = true;
               if ($first) {
                  echo "<tr><th colspan='2'>"._n($locale, $locale.'s', 2, 'ocsinventoryng')."</th>".
                        "</tr>\n";
                  $first = false;
               }
            
               echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $item->getName() . "</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='Computer_Item[" . $line['id'] . "]'></td></tr>\n";
            }
            
         }
         
         $types = array('ComputerDisk' => 'disk', 'ComputerVirtualMachine' => 'Virtual machine');
         foreach($types as $itemtype => $label) {
            $params = array('is_dynamic' => 1, 'is_deleted' => 1, 'computers_id' => $comp->getID());

            $first  = true;
            $locale = "Locked ".$label;
            foreach ($DB->request(getTableForItemType($itemtype), $params,
                      array('id', 'name')) as $line) {
               $header = true;
               if ($first) {
                  echo "<tr><th colspan='2'>"._n($locale, $locale.'s', 2, 'ocsinventoryng')."</th>".
                        "</tr>\n";
                  $first = false;
               }
         
               echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $line['name'] . "</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='".$itemtype."[" . $line['id'] . "]'></td></tr>\n";
            }
         }

         //Software versions
         $params = array('is_dynamic' => 1, 'is_deleted' => 1, 'computers_id' => $comp->getID());
         $first  = true;
         $query = "SELECT `csv`.`id` as `id`, `sv`.`name` as `version`, `s`.`name` as `software`
                   FROM `glpi_computers_softwareversions` AS csv
                      LEFT JOIN `glpi_softwareversions` AS sv
                         ON (`csv`.`softwareversions_id`=`sv`.`id`)
                      LEFT JOIN `glpi_softwares` AS s
                         ON (`sv`.`softwares_id`=`s`.`id`)
                   WHERE `csv`.`is_deleted`='1'
                      AND `csv`.`is_dynamic`='1'
                         AND `csv`.`computers_id`='".$comp->getID()."'";
         foreach ($DB->request($query) as $line) {
            $header = true;
            if ($first) {
               echo "<tr><th colspan='2'>"._n('Software', 'Softwares', 2, 'ocsinventoryng')."</th>".
                     "</tr>\n";
               $first = false;
            }
                
            echo "<tr class='tab_bg_1'><td class='right' width='50%'>" .
               $line['software']." ".$line['version']. "</td>";
            echo "<td class='left' width='50%'>";
            echo "<input type='checkbox' name='Computer_SoftwareVersion[" . $line['id'] . "]'></td></tr>\n";
         }

         //Software licenses
         $params = array('is_dynamic' => 1, 'is_deleted' => 1, 'computers_id' => $comp->getID());
         $first  = true;
         $query = "SELECT `csv`.`id` as `id`, `sv`.`name` as `version`, `s`.`name` as `software`
                   FROM `glpi_computers_softwarelicenses` AS csv
                      LEFT JOIN `glpi_softwarelicenses` AS sv
                         ON (`csv`.`softwarelicenses_id`=`sv`.`id`)
                      LEFT JOIN `glpi_softwares` AS s
                         ON (`sv`.`softwares_id`=`s`.`id`)
                   WHERE `csv`.`is_deleted`='1'
                      AND `csv`.`is_dynamic`='1'
                         AND `csv`.`computers_id`='".$comp->getID()."'";
         foreach ($DB->request($query) as $line) {
            $header = true;
            if ($first) {
               echo "<tr><th colspan='2'>"._n('License', 'Licenses', 2, 'ocsinventoryng')."</th>".
                     "</tr>\n";
               $first = false;
            }
         
            echo "<tr class='tab_bg_1'><td class='right' width='50%'>" .
                  $line['software']." ".$line['version']. "</td>";
            echo "<td class='left' width='50%'>";
            echo "<input type='checkbox' name='Computer_SoftwareLicense[" . $line['id'] . "]'></td></tr>\n";
         }
          
         $params = array('is_dynamic' => 1, 'is_deleted' => 1, 'items_id' => $comp->getID(),
                          'itemtype' => 'Computer');
         $first  = true;
         $item = new NetworkPort();
         foreach ($DB->request('glpi_networkports', $params, array('id', 'items_id')) as $line) {
            $item->getFromDB($line['id']);
            $header = true;
            if ($first) {
               echo "<tr><th colspan='2'>"._n('Locked IP', 'Locked IP', 2, 'ocsinventoryng')."</th>".
                     "</tr>\n";
               $first = false;
            }
         
            echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $item->getName() . "</td>";
            echo "<td class='left' width='50%'>";
            echo "<input type='checkbox' name='NetworkPort[" . $line['id'] . "]'></td></tr>\n";
         }
          
         /*
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
                                       LEFT JOIN `glpi_networknames`
                                          ON (`glpi_networkports`.`id` = `glpi_networknames`.`items_id`)
                                       LEFT JOIN `glpi_ipaddresses`
                                          ON (`glpi_ipaddresses`.`items_id` = `glpi_networknames`.`id`)
                                       WHERE `glpi_networkports`.`items_id` = '$ID'
                                             AND `glpi_networkports`.`itemtype` = 'Computer'
                                             AND `glpi_ipaddresses`.`name` = '".$tmp[0]."'
                                             AND `glpi_networkports`.`mac` = '".$tmp[1]."'";
               $resultSearchIP = $DB->query($querySearchLockedIP);

               if ($DB->numrows($resultSearchIP) == 0) {
                  $header = true;
                  if ($first) {
                     echo "<tr><th colspan='2'>" ._n('Locked IP', 'Locked IP', 2, 'ocsinventoryng').
                          "</th></tr>\n";
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
         */
         $types = Item_Devices::getDeviceTypes();
         $nb    = 0;
         foreach ($types as $old => $itemtype) {
            $nb += countElementsInTable(getTableForItemType($itemtype),
                                          "`items_id`='".$comp->getID()."'
                                            AND `itemtype`='Computer'
                                               AND `is_dynamic`='1'
                                                  AND `is_deleted`='1'");
         }
         if ($nb) {
            $header = true;
            echo "<tr><th colspan='2'>"._n('Locked component', 'Locked components', 2,
                  'ocsinventoryng')."</th></tr>\n";
            foreach ($types as $old => $itemtype) {
               $associated_type = str_replace('Item_', '', $itemtype);
               $associated_table = getTableForItemType($associated_type);
               $fk              = getForeignKeyFieldForTable($associated_table);
               $query = "SELECT `i`.`id`, `t`.`designation` as `name`
                         FROM `".getTableForItemType($itemtype)."` as i
                         LEFT JOIN `$associated_table` as t ON (`t`.`id`=`i`.`$fk`)
                         WHERE `itemtype`='Computer'
                            AND `items_id`='".$comp->getID()."'
                            AND `is_dynamic`='1'
                            AND `is_deleted`='1'";
               foreach ($DB->request($query) as $data) {
                  echo "<tr class='tab_bg_1'><td class='right' width='50%'>";
                  echo $associated_type::getTypeName()."&nbsp;: ".$data['name']."</td>";
                  echo "<td class='left' width='50%'>";
                  echo "<input type='checkbox' name='".$itemtype."[" . $data['id'] . "]'></td></tr>\n";
               }
            }
         }

         if ($header) {
            echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
            PluginOcsinventoryngOcsServer::checkBox($target);
            echo "</td></tr>";
         }
         
         if ($header) {
            echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
            echo "<input class='submit' type='submit' name='unlock' value='".
                  _sx('button', 'Unlock', 'ocsinventoryng'). "'></td></tr>";
         } else {
            echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
            echo __('No locked field', 'ocsinventoryng')."</td></tr>";
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
               return array('1' => _n('OCSNG link', 'OCSNG links', 1, 'ocsinventoryng'));
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

      return Log::history($computers_id, 'Computer', $changes, __CLASS__,
                          Log::HISTORY_PLUGIN+$action);
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
               return sprintf(__('%1$s: %2$s'), __('Imported from OCSNG', 'ocsinventoryng'),
                              $data['new_value']);

            case self::HISTORY_OCS_DELETE :
               return sprintf(__('%1$s: %2$s'), __('Deleted in OCSNG', 'ocsinventoryng'),
                              $data['old_value']);

            case self::HISTORY_OCS_LINK :
               return sprintf(__('%1$s: %2$s'), __('Linked with an OCSNG computer', 'ocsinventoryng'),
                              $data['new_value']);

            case self::HISTORY_OCS_IDCHANGED :
               return  sprintf(__('The OCSNG ID of the computer changed from %1$s to %2$s',
                                  'ocsinventoryng'),
                               $data['old_value'], $data['new_value']);

            case self::HISTORY_OCS_TAGCHANGED :
               return  sprintf(__('The OCSNG TAG of the computer changed from %1$s to %2$s',
                                  'ocsinventoryng'),
                               $data['old_value'], $data['new_value']);
         }
      }
      return '';
   }
}
?>