<?php
/*
 * @version $Id: HEADER 15930 2012-12-15 11:10:55Z tsmr $
-------------------------------------------------------------------------
Ocsinventoryng plugin for GLPI
Copyright (C) 2012-2016 by the ocsinventoryng plugin Development Team.

https://forge.glpi-project.org/projects/ocsinventoryng
-------------------------------------------------------------------------

LICENSE

This file is part of ocsinventoryng.

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
-------------------------------------------------------------------------- */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginOcsinventoryngSnmpOcslink extends CommonDBTM {

   /**
    * if Printer purged
    *
    * @param $print   Printer object
   **/
   static function purgePrinter(Printer $print) {
      $snmp = new self();
      $snmp->deleteByCriteria(array('items_id' => $print->getField("id"),
                                     'itemtype' => $print->getType()));

   }
   
   /**
    * if Printer purged
    *
    * @param $per   Peripheral object
   **/
   static function purgePeripheral(Peripheral $per) {
      $snmp = new self();
      $snmp->deleteByCriteria(array('items_id' => $per->getField("id"),
                                     'itemtype' => $per->getType()));

   }
   
   /**
    * if NetworkEquipment purged
    *
    * @param $comp   NetworkEquipment object
   **/
   static function purgeNetworkEquipment(NetworkEquipment $net) {
      $snmp = new self();
      $snmp->deleteByCriteria(array('items_id' => $net->getField("id"),
                                     'itemtype' => $net->getType()));

   }
   
   /**
    * if Computer purged
    *
    * @param $comp   Computer object
   **/
   static function purgeComputer(Computer $comp) {
      $snmp = new self();
      $snmp->deleteByCriteria(array('items_id' => $comp->getField("id"),
                                     'itemtype' => $comp->getType()));

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

      if (in_array($item->getType(), array('Computer', 'Printer', 'NetworkEquipment', 'Peripheral'))) {
         $items_id = $item->getField('id');

         if (!empty($items_id)
             && $item->fields["is_dynamic"]
             && Session::haveRight("plugin_ocsinventoryng_view", READ)) {
            $query = "SELECT *
                      FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
                      WHERE `items_id` = '".$items_id."' AND  `itemtype` = '".$item->getType()."'";

            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               $data = $DB->fetch_assoc($result);

               if (count($data)) {
                  $ocs_config = PluginOcsinventoryngOcsServer::getConfig($data['plugin_ocsinventoryng_ocsservers_id']);
                  echo "<table class='tab_glpi'>";
                  echo "<tr class='tab_bg_1'><th colspan='2'>".__('SNMP informations OCS NG')."</th>";
                  
                  echo "<tr class='tab_bg_1'><td>".__('Import date in GLPI', 'ocsinventoryng');
                  echo "</td><td>".Html::convDateTime($data["last_update"]).'</td></tr>';
                  
                  $linked_ids [] = $data['ocs_id'];
                  $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($data['plugin_ocsinventoryng_ocsservers_id']);
                  $ocsResult = $ocsClient->getSnmp(array(
                     'MAX_RECORDS' => 1,
                     'FILTER'      => array(
                        'IDS' => $linked_ids,
                     )
                  ));
                  if (isset($ocsResult['SNMP'])) {
                     if (count($ocsResult['SNMP']) > 0) {
                        foreach ($ocsResult['SNMP'] as $snmp) {
                           $LASTDATE   = $snmp['META']['LASTDATE'];
                           $UPTIME     = $snmp['META']['UPTIME'];
                  
                           echo "<tr class='tab_bg_1'><td>".__('Last OCSNG SNMP inventory date', 'ocsinventoryng');
                           echo "</td><td>".Html::convDateTime($LASTDATE).'</td></tr>';
                           
                           echo "<tr class='tab_bg_1'><td>".__('Uptime', 'ocsinventoryng');
                           echo "</td><td>".$UPTIME.'</td></tr>';
                        }
                     }
                  }
                  
                  
                  
                  if (Session::haveRight("plugin_ocsinventoryng_sync", UPDATE)) {
                     echo "<tr class='tab_bg_1'><td class='center' colspan='2'>";
                     Html::showSimpleForm($target, 'force_ocssnmp_resynch',
                                          _sx('button', 'Force SNMP synchronization', 'ocsinventoryng'),
                                          array('items_id' => $items_id,
                                                 'itemtype' => $item->getType(),
                                                 'id' => $data["id"],
                                                  'plugin_ocsinventoryng_ocsservers_id' => $data["plugin_ocsinventoryng_ocsservers_id"]));
                     echo "</td></tr>";
                     
                  }
                  echo '</table>';
               }
            }
         }
      }
   }
   
   // SNMP PART HERE

   static function processSnmp($ocsid, $plugin_ocsinventoryng_ocsservers_id, $lock = 0, $params) {
      global $DB;

      PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);

      //Check it machine is already present AND was imported by OCS AND still present in GLPI
      $query                                      = "SELECT `glpi_plugin_ocsinventoryng_snmpocslinks`.`id`
             FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
             WHERE `ocs_id` = '$ocsid'
                   AND `plugin_ocsinventoryng_ocsservers_id` = '$plugin_ocsinventoryng_ocsservers_id'";
      $result_glpi_plugin_ocsinventoryng_ocslinks = $DB->query($query);

      if ($DB->numrows($result_glpi_plugin_ocsinventoryng_ocslinks)) {
         $datas = $DB->fetch_array($result_glpi_plugin_ocsinventoryng_ocslinks);
         //Return code to indicates that the machine was synchronized
         //or only last inventory date changed
         return self::updateSnmp($datas["id"], $plugin_ocsinventoryng_ocsservers_id);
      }
      return self::importSnmp($ocsid, $plugin_ocsinventoryng_ocsservers_id, $lock, $params);
   }

   static function importSnmp($ocsid, $plugin_ocsinventoryng_ocsservers_id, $lock = 0, $params) {
      global $DB;

      $p['entity']   = -1;
      $p['itemtype'] = -1;
      foreach ($params as $key => $val) {
         $p[$key] = $val;
      }

      PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $cfg_ocs   = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
      //TODOSNMP entites_id ?

      $ocsSnmp = $ocsClient->getSnmpDevice($ocsid);
      
      if ($ocsSnmp['META']['ID'] == $ocsid && $p['itemtype'] != -1) {
         $itemtype = $p['itemtype'];

         $loc_id = Dropdown::importExternal('Location', PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['META']['LOCATION']));

         $dom_id = Dropdown::importExternal('Domain', PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['META']['DOMAIN']));

         if ($itemtype == "NetworkEquipment") {

            $id = self::addOrUpdateNetworkEquipment($plugin_ocsinventoryng_ocsservers_id, $itemtype, $ID, $ocsSnmp, $loc_id, $dom_id, "add");
         } else if ($itemtype == "Printer") {

            $id = self::addOrUpdatePrinter($plugin_ocsinventoryng_ocsservers_id, $itemtype, 0, $ocsSnmp, $loc_id, $dom_id, "add");
         } else if ($itemtype == "Peripheral" || $itemtype == "Computer") {

            $id = self::addOrUpdateOther($plugin_ocsinventoryng_ocsservers_id, $itemtype, 0, $ocsSnmp, $loc_id, $dom_id, "add");
         }
         //TODOSNMP 
         //Monitor & Phone ???
         if ($id) {
            $date = date("Y-m-d H:i:s");
            //Add to snmp link

            $query = "INSERT INTO `glpi_plugin_ocsinventoryng_snmpocslinks`
                       SET `items_id` = '" . $id . "',
                            `ocs_id` = '" . $ocsid . "',
                            `itemtype` = '" . $itemtype . "',
                            `last_update` = '" . $date . "',
                           `plugin_ocsinventoryng_ocsservers_id` = '" . $plugin_ocsinventoryng_ocsservers_id . "'";

            $DB->query($query);

            return array('status' => PluginOcsinventoryngOcsServer::SNMP_IMPORTED,
               //'entities_id'  => $data['entities_id'],
            );
         } else {
            return array('status' => PluginOcsinventoryngOcsServer::SNMP_FAILED_IMPORT,
               //'entities_id'  => $data['entities_id'],
            );
         }
      } else {
         return array('status' => PluginOcsinventoryngOcsServer::SNMP_FAILED_IMPORT,
            //'entities_id'  => $data['entities_id'],
         );
      }
   }

   // Check if object already exist NOR create it with ocs snmp data
   /* static function checkIfExist($object, $data){

     // Check for loc and all theses stuff
     if ($data != "" or !empty($data) or is_null($data)){
     // Check for domain / loc / network .
     $location = new $object();
     $reponse = $location->find("name = '".$data."'");
     if (is_null($reponse) or empty($reponse)){
     $input = array(
     "entities_id" => $_SESSION['glpidefault_entity'],
     "name" => $data,
     );
     $id = $location->add($input, array('unicity_error_message' => false));
     }else{
     foreach($reponse as $ident => $fields){
     $id = $fields['id'];
     }
     }

     return $id;
     }

     return "";
     } */

   static function addOrUpdatePrinter($plugin_ocsinventoryng_ocsservers_id, $itemtype, $ID = 0, $ocsSnmp, $loc_id, $dom_id, $action) {

      $snmpDevice = new $itemtype();

      $input = array(
         "is_dynamic"    => 1,
         "entities_id"   => $_SESSION['glpidefault_entity'],
         "have_ethernet" => 1,
      );

      //TODOSNMP TO TEST:
      //'PRINTER' => 
      // array (size=1)
      //   0 => 
      //     array (size=6)
      //       'SNMP_ID' => string '4' (length=1)
      //       'NAME' => string 'MP C3003' (length=8)
      //       'SERIALNUMBER' => string 'E1543632108' (length=11)
      //       'COUNTER' => string '98631 sheets' (length=12)
      //       'STATUS' => string 'idle' (length=4)
      //       'ERRORSTATE' => string '' (length=0)
      
      $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
      
      if ($cfg_ocs['importsnmp_name']) {
         $input["name"]               = $ocsSnmp['PRINTER'][0]['NAME'];
      }
      if ($cfg_ocs['importsnmp_contact']) {
         $input["contact"]            = $ocsSnmp['META']['CONTACT'];
      }
      if ($cfg_ocs['importsnmp_comment']) {
         $input["comment"]            = $ocsSnmp['META']['DESCRIPTION'];
      }
      if ($cfg_ocs['importsnmp_serial']) {
         $input["serial"]             = $ocsSnmp['PRINTER'][0]['SERIALNUMBER'];
      }
      if ($cfg_ocs['importsnmp_last_pages_counter']) {
         $input["last_pages_counter"] = $ocsSnmp['PRINTER'][0]['COUNTER'];
      }

      $input["locations_id"] = $loc_id;
      $input["domains_id"]   = $dom_id;

      $id_printer = 0;

      if ($action == "add") {
         $id_printer = $snmpDevice->add($input, array('unicity_error_message' => false));
      } else {
         $id_printer = $ID;
         $input["id"] = $ID;
         $snmpDevice->update($input, array('unicity_error_message' => false));
      }
      

      if ($id_printer > 0 
            && $cfg_ocs['importsnmp_createport']) {

         //Add network port
         $ip  = $ocsSnmp['META']['IPADDR'];
         $mac = $ocsSnmp['META']['MACADDR'];
         
         $np    = new NetworkPort();
         $np->getFromDBByQuery("WHERE `mac` = '$mac' ");
         if(empty($np->fields)) {
      
            $newinput = array(
               "itemtype"                 => $itemtype,
               "items_id"                 => $id_printer,
               //TODOSNMP entities_id
               "entities_id"              => $_SESSION["glpiactive_entity"],
               "name"                     => $ocsSnmp['PRINTER'][0]['NAME'],
               "instantiation_type"       => "NetworkPortEthernet",
               "mac"                      => $mac,
               "NetworkName__ipaddresses" => array("-100" => $ip),
               "speed"                    => "0",
               "speed_other_value"        => "",
               "add"                      => __("Add"),
            );

            
            $np->splitInputForElements($newinput);
            $newID = $np->add($newinput);
            $np->updateDependencies(1);
         }

         //TODOSNMP TO TEST:
         //'PRINTER' => 
         // array (size=1)
         //   0 => 
         //array (size=7)
         //  'ID' => string '6' (length=1)
         //  'SNMP_ID' => string '4' (length=1)
         //  'DESCRIPTION' => string 'Toner cyan' (length=10)
         //  'TYPE' => string 'toner' (length=5)
         //  'LEVEL' => string '30' (length=2)
         //  'MAXCAPACITY' => string '100' (length=3)
         //  'COLOR' => string '' (length=0)
         //TODOSNMP But complicated
         //if(!empty($ocsSnmp['CARTRIDGES'])){
         //   foreach($ocsSnmp['CARTRIDGES'] as $k => $val){
         //     $cartridge_item = new CartridgeItem();
         //     $input = array (
         //         "name" => $val['DESCRIPTION'],
         //         "entities_id" => $_SESSION['glpidefault_entity'],
         //"comment" => $ocsSnmp['CARTRIDGES']['DESCRIPTION'],
         //         "locations_id" => $loc_id,
         //     );
         //     $type_id = Dropdown::importExternal('CartridgeItemType',
         //      PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'],
         //      $val['TYPE']));
         //      $input['cartridgeitemtypes_id'] = $type_id;
         //     $cartridge_items_id = $cartridge_item->add($input, array('unicity_error_message' => false));
         //     $cartridges = new Cartridge();
         //     $values = array (
         //         "entities_id" => $_SESSION['glpidefault_entity'],
         //         "cartridgeitems_id" => $cartridge_items_id,
         //         "printers_id" => $id_printer,
         //         "date_use" => date("Y-m-d")
         //     );
         //     $cartridges->add($values, array('unicity_error_message' => false));
         //   }
         //}
      }

      return $id_printer;
   }

   static function addOrUpdateNetworkEquipment($plugin_ocsinventoryng_ocsservers_id, $itemtype, $ID = 0, $ocsSnmp, $loc_id, $dom_id, $action) {

      $snmpDevice = new $itemtype();

      $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);

      $input = array(
         "is_dynamic"   => 1,
         "entities_id"  => $_SESSION['glpidefault_entity'],
         "is_recursive" => 0,
      );
      
      if ($cfg_ocs['importsnmp_name']) {
         if ($ocsSnmp['META']['NAME'] != "N/A") {
            $input["name"] = $ocsSnmp['META']['NAME'];
         } else {
            $input["name"] = $ocsSnmp['META']['DESCRIPTION'];
         }
      }
      if ($cfg_ocs['importsnmp_contact']) {
         $input["contact"] = $ocsSnmp['META']['CONTACT'];
      }
      if ($cfg_ocs['importsnmp_comment']) {
         $input["comment"] = $ocsSnmp['META']['DESCRIPTION'];
      }

      $input["locations_id"] = $loc_id;
      $input["domains_id"]   = $dom_id;

      //if($ocsSnmp['META']['TYPE'] == null){
      //   $type_id = self::checkIfExist("NetworkEquipmentType", "Network Device");
      //} else {
      //   $type_id = self::checkIfExist("network", $ocsSnmp['META']['TYPE']);
      //}

      if (!empty($ocsSnmp['SWITCH'])) {
         
         if ($cfg_ocs['importsnmp_manufacturer']) {
            $man_id = Dropdown::importExternal('Manufacturer', PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['SWITCH'][0]['MANUFACTURER']));
            $input['manufacturers_id'] = $man_id;
         }
         
         if ($cfg_ocs['importsnmp_firmware']) {
            $firm_id = Dropdown::importExternal('NetworkEquipmentFirmware', PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['SWITCH'][0]['FIRMVERSION']));
            $input['networkequipmentfirmwares_id'] = $firm_id;
         }
         
         if ($cfg_ocs['importsnmp_serial']) {
            $input['serial'] = $ocsSnmp['SWITCH'][0]['SERIALNUMBER'];
         }
         //TODOSNMP = chassis ??
         //$mod_id = Dropdown::importExternal('NetworkEquipmentModel',
         //PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'],
         //$ocsSnmp['SWITCH'][0]['REFERENCE']));
         //$input['networkequipmentmodels_id'] = $mod_id;
         // TODOSNMP ?
         //$input['networkequipmenttypes_id'] = self::checkIfExist("NetworkEquipmentType", "Switch");
      }
      if (!empty($ocsSnmp['FIREWALLS'])) {
         
         if ($cfg_ocs['importsnmp_serial']) {
            $input['serial'] = $ocsSnmp['FIREWALLS']['SERIALNUMBER'];
         }
         // TODOSNMP ?
         //$input['networkequipmenttypes_id'] = self::checkIfExist("NetworkEquipmentType", "Firewall");
      }
      $id_network = 0;
      if ($action == "add") {
         $id_network = $snmpDevice->add($input, array('unicity_error_message' => false));
      } else {
         $input["id"] = $ID;
         $id_network = $ID;
         $snmpDevice->update($input, array('unicity_error_message' => false));
      }

      if ($id_network > 0 
            && $action == "add") {

         if (isset($ocsSnmp['POWERSUPPLIES']) 
               && $cfg_ocs['importsnmp_power'] 
                  && count($ocsSnmp['POWERSUPPLIES']) > 0) {

            $man_id = Dropdown::importExternal('Manufacturer', PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['POWERSUPPLIES'][0]['MANUFACTURER']));

            $pow['manufacturers_id'] = $man_id;
            $pow['designation']      = $ocsSnmp['POWERSUPPLIES'][0]['REFERENCE'];
            $pow['comment']          = $ocsSnmp['POWERSUPPLIES'][0]['DESCRIPTION'];
            $pow['entities_id']      = $_SESSION['glpidefault_entity'];

            $power    = new DevicePowerSupply();
            $power_id = $power->import($pow);
            if ($power_id) {
               $serial     = $ocsSnmp['POWERSUPPLIES'][0]['SERIALNUMBER'];
               $CompDevice = new Item_DevicePowerSupply();
               $CompDevice->add(array('items_id'               => $id_network,
                  'itemtype'               => $itemtype,
                  'entities_id'            => $_SESSION['glpidefault_entity'],
                  'serial'                 => $serial,
                  'devicepowersupplies_id' => $power_id,
                  'is_dynamic'             => 1,
                  '_no_history'            => !$dohistory));
            }
         }

         if (isset($ocsSnmp['FANS']) 
               && $cfg_ocs['importsnmp_fan'] 
                  && count($ocsSnmp['FANS']) > 0) {

            $man_id                  = Dropdown::importExternal('Manufacturer', PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['FANS'][0]['MANUFACTURER']));
            $dev['manufacturers_id'] = $man_id;

            $dev['designation'] = $ocsSnmp['FANS'][0]['REFERENCE'];
            $dev['comment']     = $ocsSnmp['FANS'][0]['DESCRIPTION'];
            $dev['entities_id'] = $_SESSION['glpidefault_entity'];

            $device    = new DevicePci();
            $device_id = $device->import($dev);
            if ($device_id) {
               $CompDevice = new Item_DevicePci();
               $CompDevice->add(array('items_id'      => $id_network,
                  'itemtype'      => $itemtype,
                  'entities_id'   => $_SESSION['glpidefault_entity'],
                  'devicepcis_id' => $device_id,
                  '_no_history'   => !$dohistory));
            }
         }
      }
      if ($id_network > 0 
            && $cfg_ocs['importsnmp_createport']) {
         //Add network port
         $ip  = $ocsSnmp['META']['IPADDR'];
         $mac = $ocsSnmp['META']['MACADDR'];
         
         $np    = new NetworkPort();
         $np->getFromDBByQuery("WHERE `mac` = '$mac' ");
         if(empty($np->fields)) {
         
            $newinput = array(
               "itemtype"                 => $itemtype,
               "items_id"                 => $id_network,
               //TODOSNMP entities_id
               "entities_id"              => $_SESSION["glpiactive_entity"],
               "name"                     => $ocsSnmp['META']['NAME'],
               "instantiation_type"       => "NetworkPortEthernet",
               "mac"                      => $mac,
               "NetworkName__ipaddresses" => array("-100" => $ip),
               "speed"                    => "0",
               "speed_other_value"        => "",
               "add"                      => __("Add"),
            );

            $np->splitInputForElements($newinput);
            $newID = $np->add($newinput);
            $np->updateDependencies(1);
         }
      }

      return $id_network;
   }

   static function addOrUpdateOther($plugin_ocsinventoryng_ocsservers_id, $itemtype, $ID = 0, $ocsSnmp, $loc_id, $dom_id, $action) {

      $snmpDevice = new $itemtype();
      
      $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
      
      $input = array(
         "is_dynamic"    => 1,
         "entities_id"   => $_SESSION['glpidefault_entity'],
         "have_ethernet" => 1,
      );
      
      if ($cfg_ocs['importsnmp_name']) {
         $input["name"] = $ocsSnmp['META']['NAME'];
      }
      if ($cfg_ocs['importsnmp_contact']) {
         $input["contact"] = $ocsSnmp['META']['CONTACT'];
      }
      if ($cfg_ocs['importsnmp_comment']) {
         $input["comment"] = $ocsSnmp['META']['DESCRIPTION'];
      }

      $input["locations_id"] = $loc_id;
      $input["domains_id"]   = $dom_id;

      $id_item = 0;

      if ($action == "add") {
         $id_item = $snmpDevice->add($input, array('unicity_error_message' => false));
      } else {
         $input["id"] = $ID;
         $id_item = $ID;
         $snmpDevice->update($input, array('unicity_error_message' => false));
      }

      if ($id_item > 0 
            && $cfg_ocs['importsnmp_createport']) {

         //Add network port
         $ip  = $ocsSnmp['META']['IPADDR'];
         $mac = $ocsSnmp['META']['MACADDR'];
         $np    = new NetworkPort();
         $np->getFromDBByQuery("WHERE `mac` = '$mac' ");
         if(empty($np->fields)) {
         
            $newinput = array(
               "itemtype"                 => $itemtype,
               "items_id"                 => $id_item,
               //TODOSNMP entities_id
               "entities_id"              => $_SESSION["glpiactive_entity"],
               "name"                     => $ocsSnmp['META']['NAME'],
               "instantiation_type"       => "NetworkPortEthernet",
               "mac"                      => $mac,
               "NetworkName__ipaddresses" => array("-100" => $ip),
               "speed"                    => "0",
               "speed_other_value"        => "",
               "add"                      => __("Add"),
            );

            $np    = new NetworkPort();
            $np->splitInputForElements($newinput);
            $newID = $np->add($newinput);
            $np->updateDependencies(1);
         }
      }

      return $id_item;
   }

   static function updateSnmp($ID, $plugin_ocsinventoryng_ocsservers_id) {
      global $DB;

      $query = "SELECT * FROM `glpi_plugin_ocsinventoryng_snmpocslinks` 
               WHERE `id` = " . $ID . " 
               AND `plugin_ocsinventoryng_ocsservers_id` = " . $plugin_ocsinventoryng_ocsservers_id;
      $rep   = $DB->query($query);
      while ($data  = $DB->fetch_array($rep)) {
         $ocsid       = $data['ocs_id'];
         $itemtype    = $data['itemtype'];
         $items_id    = $data['items_id'];
         $snmplink_id = $data['id'];
      }

      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $ocsSnmp   = $ocsClient->getSnmpDevice($ocsid);

      $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
      
      $loc_id = 0;
      $dom_id = 0;
      if ($cfg_ocs['importsnmp_location']) {
         $loc_id = Dropdown::importExternal('Location', PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['META']['LOCATION']));
      }
      if ($cfg_ocs['importsnmp_domain']) {
         $dom_id = Dropdown::importExternal('Domain', PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['META']['DOMAIN']));
      }
      if ($itemtype == "Printer") {
         PluginOcsinventoryngOcsServer::addOrUpdatePrinter($plugin_ocsinventoryng_ocsservers_id, $itemtype, $items_id, $ocsSnmp, $loc_id, $dom_id, "update");

         $now = date("Y-m-d H:i:s");
         $sql = "UPDATE `glpi_plugin_ocsinventoryng_snmpocslinks` SET `last_update` = '" . $now . "' WHERE `id` = " . $ID . ";";
         $DB->query($sql);

         return array('status' => PluginOcsinventoryngOcsServer::SNMP_SYNCHRONIZED,
            //'entities_id'  => $data['entities_id'],
         );
      } else if ($itemtype == "NetworkEquipment") {

         self::addOrUpdateNetworkEquipment($plugin_ocsinventoryng_ocsservers_id, $itemtype, $items_id, $ocsSnmp, $loc_id, $dom_id, "update");

         $now = date("Y-m-d H:i:s");
         $sql = "UPDATE `glpi_plugin_ocsinventoryng_snmpocslinks` SET `last_update` = '" . $now . "' WHERE `id` = " . $ID . ";";
         $DB->query($sql);

         return array('status' => PluginOcsinventoryngOcsServer::SNMP_SYNCHRONIZED,
            //'entities_id'  => $data['entities_id'],
         );
      } else if ($itemtype == "Computer" || $itemtype == "Peripheral") {

         self::addOrUpdateOther($plugin_ocsinventoryng_ocsservers_id, $itemtype, $items_id, $ocsSnmp, $loc_id, $dom_id, "update");

         $now = date("Y-m-d H:i:s");
         $sql = "UPDATE `glpi_plugin_ocsinventoryng_snmpocslinks` SET `last_update` = '" . $now . "' WHERE `id` = " . $ID . ";";
         $DB->query($sql);

         return array('status' => PluginOcsinventoryngOcsServer::SNMP_SYNCHRONIZED,
            //'entities_id'  => $data['entities_id'],
         );
      }


      return array('status' => PluginOcsinventoryngOcsServer::SNMP_NOTUPDATED,
         //'entities_id'  => $data['entities_id'],
      );
   }

   // Show snmp devices to add :)
   static function showSnmpDeviceToAdd($serverId, $advanced, $check, $start, $entity = 0, $tolinked = false) {
      global $DB, $CFG_GLPI;

      if (!Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
         return false;
      }

      $title = __('Import new SNMP devices', 'ocsinventoryng');
      if ($tolinked) {
         $title = __('Import new SNMP devices into glpi', 'ocsinventoryng');
      }
      $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsngsnmp.import.php';
      if ($tolinked) {
         $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsngsnmp.link.php';
      }

      // Get all links between glpi and OCS
      $query_glpi     = "SELECT ocs_id
                     FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
                     WHERE `plugin_ocsinventoryng_ocsservers_id` = '$serverId'";
      $result_glpi    = $DB->query($query_glpi);
      $already_linked = array();
      if ($DB->numrows($result_glpi) > 0) {
         while ($data = $DB->fetch_array($result_glpi)) {
            $already_linked [] = $data["ocs_id"];
         }
      }

      $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($serverId);

      $snmpOptions = array(
         'OFFSET'      => $start,
         'MAX_RECORDS' => $_SESSION['glpilist_limit'],
         'ORDER'       => 'LASTDATE',
         'FILTER'      => array(
            'EXCLUDE_IDS' => $already_linked
         ),
         'DISPLAY'     => array(
            'CHECKSUM' => PluginOcsinventoryngOcsClient::CHECKSUM_BIOS
         ),
         'ORDER'       => 'NAME'
      );

      if ($cfg_ocs["tag_limit"] and $tag_limit = explode("$", trim($cfg_ocs["tag_limit"]))) {
         $snmpOptions['FILTER']['TAGS'] = $tag_limit;
      }

      if ($cfg_ocs["tag_exclude"] and $tag_exclude = explode("$", trim($cfg_ocs["tag_exclude"]))) {
         $snmpOptions['FILTER']['EXCLUDE_TAGS'] = $tag_exclude;
      }
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($serverId);
      $ocsResult = $ocsClient->getSnmp($snmpOptions);

      if (isset($ocsResult['SNMP'])) {
         $snmp = $ocsResult['SNMP'];
         if (count($snmp)) {
            // Get all hardware from OCS DB
            $hardware = array();
            foreach ($snmp as $data) {
               $data                          = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));
               $id                            = $data['META']['ID'];
               $hardware[$id]["id"]           = $data['META']["ID"];
               $hardware[$id]["date"]         = $data['META']["LASTDATE"];
               $hardware[$id]["name"]         = $data['META']["NAME"];
               $hardware[$id]["ipaddr"]       = $data['META']["IPADDR"];
               $hardware[$id]["snmpdeviceid"] = $data['META']["SNMPDEVICEID"];
               $hardware[$id]["description"]  = $data['META']["DESCRIPTION"];
               $hardware[$id]["type"]         = $data['META']["TYPE"];
               $hardware[$id]["contact"]      = $data['META']["CONTACT"];
               $hardware[$id]["location"]     = $data['META']["LOCATION"];
            }

            if ($tolinked && count($hardware)) {
               echo "<div class='center b'>" .
               __('Caution! The imported data (see your configuration) will overwrite the existing one', 'ocsinventoryng') . "</div>";
            }
            echo "<div class='center'>";

            if ($numrows = $ocsResult['TOTAL_COUNT']) {
               $parameters = "check=$check";
               Html::printPager($start, $numrows, $target, $parameters);

               //Show preview form only in import even in multi-entity mode because computer import
               //can be refused by a rule
               if (!$tolinked) {
                  echo "<div class='firstbloc'>";
                  echo "<form method='post' name='ocsng_import_mode' id='ocsng_import_mode'
                         action='$target'>\n";
                  echo "<table class='tab_cadre_fixe'>";
                  echo "<tr><th>" . __('Manual import mode', 'ocsinventoryng') . "</th></tr>\n";
                  echo "<tr class='tab_bg_1'><td class='center'>";
                  echo "</td></tr>";
                  echo "</table>";
                  Html::closeForm();
                  echo "</div>";
               }

               echo "<form method='post' name='ocsng_form' id='ocsng_form' action='$target'>";
               if (!$tolinked) {
                  PluginOcsinventoryngOcsServer::checkBox($target);
               }
               echo "<table class='tab_cadrehov'>";

               echo "<tr class='tab_bg_1'><td colspan='10' class='center'>";
               if (!$tolinked) {
                  echo "<input class='submit' type='submit' name='import_ok' value=\"" .
                  _sx('button', 'Import', 'ocsinventoryng') . "\">";
               } else {
                  echo "<input class='submit' type='submit' name='import_ok' value=\"" .
                  _sx('button', 'Link', 'ocsinventoryng') . "\">";
               }
               echo "</td></tr>\n";

               echo "<tr><th>" . __('Name') . "</th>\n";
               echo "<th>" . __('Description') . "</th>\n";
               echo "<th>" . __('IP address') . "</th>\n";
               echo "<th>" . __('Date') . "</th>\n";
               echo "<th>" . __('Contact SNMP') . "</th>\n";
               echo "<th>" . __('Location SNMP') . "</th>\n";
               echo "<th>" . __('Type SNMP', 'ocsinventoryng') . "</th>\n";
               if (!$tolinked) {
                  echo "<th width='15%'>" . __('Item type to create', 'ocsinventoryng') . "</th>\n";
                  echo "<th width='10'>&nbsp;</th></tr>\n";
               } else {
                  echo "<th width='15%'>" . __('Item type to link', 'ocsinventoryng') . "</th>\n";
               }

               foreach ($hardware as $ID => $tab) {

                  echo "<tr class='tab_bg_2'><td>" . $tab["name"] . "</td>\n";
                  echo "<td>" . $tab["description"] . "</td><td>" . $tab["ipaddr"] . "</td>";
                  echo "<td>" . Html::convDateTime($tab["date"]) . "</td>\n";
                  echo "<td>" . $tab["contact"] . "</td>\n";
                  echo "<td>" . $tab["location"] . "</td>\n";
                  echo "<td>" . $tab["type"] . "</td>\n";

                  if (!$tolinked) {
                     echo "<td width='15%'>";
                     $value = false;
                     if ($tab["type"] == "Network") {
                        $tab["type"] = "NetworkEquipment";
                     }
                     if (getItemForItemtype($tab["type"])) {
                        $value = $tab["type"];
                     }
                     $type = "toimport_itemtype[" . $tab["id"] . "]";
                     Dropdown::showItemTypes($type, $CFG_GLPI["asset_types"], array('value' => $value));
                     echo "</td>\n";
                  }
                  /* if ($advanced && !$tolinked){
                    if (!isset ($data['entities_id']) || $data['entities_id'] == -1){
                    echo "<td class='center'><img src=\"".$CFG_GLPI['root_doc']. "/pics/redbutton.png\"></td>\n";
                    $data['entities_id'] = -1;
                    } else{
                    echo "<td class='center'>";
                    $tmprule = new RuleImportEntity();
                    if ($tmprule->can($data['_ruleid'],READ)){
                    echo "<a href='". $tmprule->getLinkURL()."'>".$tmprule->getName()."</a>";
                    }  else{
                    echo $tmprule->getName();
                    }
                    echo "</td>\n";
                    }
                    echo "<td width='30%'>";
                    $ent = "toimport_entities[".$tab["id"]."]";
                    Entity::dropdown(array('name'     => $ent,
                    'value'    => $data['entities_id'],
                    'comments' => 0));
                    echo "</td>\n";
                    } */
                  echo "<td width='10'>";
                  if (!$tolinked) {
                     echo "<input type='checkbox' name='toimport[" . $tab["id"] . "]' " .
                     ($check == "all" ? "checked" : "") . ">";
                  } else {

                     /* $tab['entities_id'] = $entity;
                       $rulelink         = new RuleImportComputerCollection();
                       $rulelink_results = array();
                       $params           = array('entities_id' => $entity,
                       'plugin_ocsinventoryng_ocsservers_id'
                       => $serverId);
                       $rulelink_results = $rulelink->processAllRules(Toolbox::stripslashes_deep($tab),
                       array(), $params);

                       //Look for the computer using automatic link criterias as defined in OCSNG configuration
                       $options       = array('name' => "tolink[".$tab["id"]."]");
                       $show_dropdown = true;
                       //If the computer is not explicitly refused by a rule
                       if (!isset($rulelink_results['action'])
                       || $rulelink_results['action'] != PluginOcsinventoryngOcsServer::LINK_RESULT_NO_IMPORT){

                       if (!empty($rulelink_results['found_computers'])){
                       $options['value']  = $rulelink_results['found_computers'][0];
                       $options['entity'] = $entity;
                       } */

                     /* } else{
                       echo "<img src='".$CFG_GLPI['root_doc']. "/pics/redbutton.png'>";
                       } */

                     $value = false;
                     if ($tab["type"] == "Network") {
                        $tab["type"] = "NetworkEquipment";
                     }
                     if (getItemForItemtype($tab["type"])) {
                        $type            = $tab["type"];
                        $options['name'] = "tolink_items[" . $tab["id"] . "]";

                        $self = new self;
                        if ($item = $self->getFromDBbyName($tab["type"], $tab["name"])) {
                           $options['value'] = (isset($item->fields['id'])) ? $item->fields['id'] : false;
                        }
                        $type::dropdown($options);
                        echo "<input type='hidden' name='tolink_itemtype[" . $tab["id"] . "]' value='" . $tab["type"] . "'>";
                     } else {

                        $rand = mt_rand();

                        $mynamei = "itemtype";
                        $myname  = "tolink_items[" . $tab["id"] . "]";

                        Dropdown::showItemTypes($mynamei, $CFG_GLPI["asset_types"], array('rand' => $rand));


                        $p = array('itemtype' => '__VALUE__',
                           //'entity_restrict' => $entity_restrict,
                           'id'       => $tab["id"],
                           'rand'     => $rand,
                           'myname'   => $myname);

                        Ajax::updateItemOnSelectEvent("dropdown_$mynamei$rand", "results_$mynamei$rand", $CFG_GLPI["root_doc"] .
                           "/plugins/ocsinventoryng/ajax/dropdownitems.php", $p);
                        echo "<span id='results_$mynamei$rand'>\n";
                        echo "</span>\n";
                     }
                  }
                  echo "</td></tr>\n";
               }

               echo "<tr class='tab_bg_1'><td colspan='10' class='center'>";
               if (!$tolinked) {
                  echo "<input class='submit' type='submit' name='import_ok' value=\"" .
                  _sx('button', 'Import', 'ocsinventoryng') . "\">";
               } else {
                  echo "<input class='submit' type='submit' name='import_ok' value=\"" .
                  _sx('button', 'Link', 'ocsinventoryng') . "\">";
               }
               echo "<input type=hidden name='plugin_ocsinventoryng_ocsservers_id' " .
               "value='$serverId'>";
               echo "</td></tr>";
               echo "</table>\n";
               Html::closeForm();

               if (!$tolinked) {
                  PluginOcsinventoryngOcsServer::checkBox($target);
               }

               Html::printPager($start, $numrows, $target, $parameters);
            } else {
               echo "<table class='tab_cadre_fixe'>";
               echo "<tr><th>" . $title . "</th></tr>\n";
               echo "<tr class='tab_bg_1'>";
               echo "<td class='center b'>" . __('No new SNMP device to be imported', 'ocsinventoryng') .
               "</td></tr>\n";
               echo "</table>";
            }
            echo "</div>";
         } else {
            echo "<div class='center'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . $title . "</th></tr>\n";
            echo "<tr class='tab_bg_1'>";
            echo "<td class='center b'>" . __('No new SNMP device to be imported', 'ocsinventoryng') .
            "</td></tr>\n";
            echo "</table></div>";
         }
      } else {
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>" . $title . "</th></tr>\n";
         echo "<tr class='tab_bg_1'>";
         echo "<td class='center b'>" . __('No new SNMP device to be imported', 'ocsinventoryng') .
         "</td></tr>\n";
         echo "</table></div>";
      }
   }

   function getFromDBbyName($itemtype, $name) {
      $item = getItemForItemtype($itemtype);
      $item->getFromDBByQuery("WHERE `" . getTableForItemType($itemtype) . "`.`name` = '$name' ");
      return $item;
   }

   static function showSnmpDeviceToUpdate($plugin_ocsinventoryng_ocsservers_id, $check, $start) {
      global $DB, $CFG_GLPI;

      PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      if (!Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
         return false;
      }

      $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);

      // Get linked computer ids in GLPI
      $already_linked_query  = "SELECT `glpi_plugin_ocsinventoryng_snmpocslinks`.`ocs_id` AS ocsid
                               FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
                               WHERE `glpi_plugin_ocsinventoryng_snmpocslinks`.`plugin_ocsinventoryng_ocsservers_id`
                                            = '$plugin_ocsinventoryng_ocsservers_id'";
      $already_linked_result = $DB->query($already_linked_query);

      if ($DB->numrows($already_linked_result) == 0) {
         echo "<div class='center b'>" . __('No new SNMP device to be updated', 'ocsinventoryng') . "</div>";
         return;
      }

      $already_linked_ids = array();
      while ($data               = $DB->fetch_assoc($already_linked_result)) {
         $already_linked_ids [] = $data['ocsid'];
      }

      // Fetch linked items from ocs
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $ocsResult = $ocsClient->getSnmp(array(
         'OFFSET'      => $start,
         'MAX_RECORDS' => $_SESSION['glpilist_limit'],
         'ORDER'       => 'LASTDATE',
         'FILTER'      => array(
            'IDS' => $already_linked_ids,
         )
      ));

      if (isset($ocsResult['SNMP'])) {
         if (count($ocsResult['SNMP']) > 0) {
            // Get all ids of the returned items
            $ocs_snmp_ids = array();
            $hardware     = array();

            foreach ($ocsResult['SNMP'] as $snmp) {
               $LASTDATE                            = $snmp['META']['LASTDATE'];
               $ocs_snmp_inv [$snmp['META']['ID']]  = $LASTDATE;
               $NAME                                = $snmp['META']['NAME'];
               $ocs_snmp_name [$snmp['META']['ID']] = $NAME;
               $ID                                  = $snmp['META']['ID'];
               $ocs_snmp_ids[]                      = $ID;

               if (isset($snmp['PRINTER'])) {
                  $TYPE = "printer";
               } else {
                  $TYPE = "";
               }
               $ocs_snmp_type [$snmp['META']['ID']] = $TYPE;
            }

            // query snmp links
            $query  = "SELECT * FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
                WHERE `glpi_plugin_ocsinventoryng_snmpocslinks`.`ocs_id` IN (" . implode(',', $ocs_snmp_ids) . ")";
            $result = $DB->query($query);

            // Get all links between glpi and OCS
            $already_linked = array();
            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetch_assoc($result)) {
                  $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));

                  $format             = 'Y-m-d H:i:s';
                  $last_glpi_update   = DateTime::createFromFormat($format, $data['last_update']);
                  $last_ocs_inventory = DateTime::createFromFormat($format, $ocs_snmp_inv[$data['ocs_id']]);
                  //TODOSNMP comment for test
                  //if ($last_ocs_inventory > $last_glpi_update) {
                     $already_linked[$data['id']] = $data;
                  //}
               }
            }
            echo "<div class='center'>";
            echo "<h2>" . __('Snmp device updated in OCSNG', 'ocsinventoryng') . "</h2>";

            $target  = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsngsnmp.sync.php';
            if (($numrows = $ocsResult['TOTAL_COUNT']) > 0) {
               $parameters = "check=$check";
               Html::printPager($start, $numrows, $target, $parameters);

               echo "<form method='post' id='ocsng_form' name='ocsng_form' action='" . $target . "'>";
               PluginOcsinventoryngOcsServer::checkBox($target);

               echo "<table class='tab_cadre_fixe'>";
               echo "<tr class='tab_bg_1'><td colspan='6' class='center'>";
               echo "<input class='submit' type='submit' name='update_ok' value=\"" .
               _sx('button', 'Synchronize', 'ocsinventoryng') . "\">";
               echo "</td></tr>\n";

               echo "<tr>";
               echo "<th>" . __('GLPI Object', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('Item type') . "</th>";
               echo "<th>" . __('OCS SNMP device', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('Import date in GLPI', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('Last OCSNG SNMP inventory date', 'ocsinventoryng') . "</th>";
               echo "<th>&nbsp;</th></tr>\n";

               foreach ($already_linked as $ID => $tab) {
                  echo "<tr class='tab_bg_2 center'>";
                  $item = new $tab["itemtype"]();
                  $item->getFromDB($tab["items_id"]);
                  echo "<td>" . $item->getLink() . "</td>\n";
                  echo "<td>" . $item->getTypeName() . "</td>\n";
                  echo "<td>" . $ocs_snmp_name[$tab["ocs_id"]] . "</td>\n";
                  echo "<td>" . Html::convDateTime($tab["last_update"]) . "</td>\n";
                  echo "<td>" . Html::convDateTime($ocs_snmp_inv[$tab["ocs_id"]]) . "</td>\n";
                  echo "<td><input type='checkbox' name='toupdate[" . $tab["id"] . "]' " .
                  (($check == "all") ? "checked" : "") . ">";
                  echo "</td></tr>\n";
               }

               echo "<tr class='tab_bg_1'><td colspan='6' class='center'>";
               echo "<input class='submit' type='submit' name='update_ok' value=\"" .
               _sx('button', 'Synchronize', 'ocsinventoryng') . "\">";
               echo "<input type=hidden name='plugin_ocsinventoryng_ocsservers_id' " .
               "value='$plugin_ocsinventoryng_ocsservers_id'>";
               echo "</td></tr>";

               echo "<tr class='tab_bg_1'><td colspan='5' class='center'>";
               PluginOcsinventoryngOcsServer::checkBox($target);
               echo "</table>\n";
               Html::closeForm();
               Html::printPager($start, $numrows, $target, $parameters);
            } else {
               echo "<br><span class='b'>" . __('Update SNMP device', 'ocsinventoryng') . "</span>";
            }
            echo "</div>";
         } else {
            echo "<div class='center b'>" . __('No new SNMP device to be updated', 'ocsinventoryng') . "</div>";
         }
      } else {
         echo "<div class='center b'>" . __('No new SNMP device to be updated', 'ocsinventoryng') . "</div>";
      }
   }

   /**
    * Make the item link between glpi and ocs.
    *
    * This make the database link between ocs and glpi databases
    *
    * @param $ocsid integer : ocs item unique id.
    * @param $plugin_ocsinventoryng_ocsservers_id integer : ocs server id
    * @param $glpi_computers_id integer : glpi computer id
    *
    * @return integer : link id.
    * */
   static function ocsSnmpLink($ocsid, $plugin_ocsinventoryng_ocsservers_id, $items_id, $itemtype) {
      global $DB;

      PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);

      $ocsSnmp = $ocsClient->getSnmpDevice($ocsid);

      if (is_null($ocsSnmp)) {
         return false;
      }

      $query  = "INSERT INTO `glpi_plugin_ocsinventoryng_snmpocslinks`
                       (`items_id`, `ocs_id`, `itemtype`,
                        `last_update`, `plugin_ocsinventoryng_ocsservers_id`)
                VALUES ('$items_id', '$ocsid', '" . $itemtype . "',
                        '" . $_SESSION["glpi_currenttime"] . "', '$plugin_ocsinventoryng_ocsservers_id')";
      $result = $DB->query($query);

      if ($result) {
         return ($DB->insert_id());
      }

      return false;
   }

   /**
    * @param $ocsid
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param $computers_id
    * */
   static function linkSnmpDevice($ocsid, $plugin_ocsinventoryng_ocsservers_id, $params) {
      global $DB, $CFG_GLPI;

      PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $cfg_ocs   = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
      //TODOSNMP entites_id ?

      $ocsSnmp       = $ocsClient->getSnmpDevice($ocsid);
      $p['itemtype'] = -1;
      $p['items_id'] = -1;
      foreach ($params as $key => $val) {
         $p[$key] = $val;
      }

      $ocs_id_change = true;
      /* $query = "SELECT *
        FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
        WHERE `ocs_id` = '$ocs_id'";

        $result           = $DB->query($query);
        $ocs_id_change    = false;
        $ocs_link_exists  = false;
        $numrows          = $DB->numrows($result);

        // Already link - check if the OCS computer already exists
        if ($numrows > 0) {
        $ocs_link_exists = true;
        $data            = $DB->fetch_assoc($result);

        $ocsComputer = $ocsClient->getComputer($data['ocsid']);

        // Not found
        if (is_null($ocsComputer)) {
        $idlink = $data["id"];
        $query  = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
        SET `ocsid` = '$ocsid'
        WHERE `id` = '" . $data["id"] . "'";

        if ($DB->query($query)) {
        $ocs_id_change = true;
        //Add history to indicates that the ocsid changed
        $changes[0] = '0';
        //Old ocsid
        $changes[1] = $data["ocsid"];
        //New ocsid
        $changes[2] = $ocsid;
        PluginOcsinventoryngOcslink::history($computers_id, $changes,
        PluginOcsinventoryngOcslink::HISTORY_OCS_IDCHANGED);
        }
        }
        }

        // No ocs_link or ocs id change does not exists so can link
        if ($ocs_id_change || !$ocs_link_exists) {
        $ocsConfig = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
        // Set OCS checksum to max value
        $ocsClient->setChecksum(PluginOcsinventoryngOcsClient::CHECKSUM_ALL, $ocsid);
       */
      if (
      //$ocs_id_change
      //|| 
         $p['itemtype'] != -1 && $p['items_id'] > 0 &&
         ($idlink        = self::ocsSnmpLink($ocsid, $plugin_ocsinventoryng_ocsservers_id, $p['items_id'], $p['itemtype']))) {
         /*
           // automatic transfer computer
           if (($CFG_GLPI['transfers_id_auto'] > 0)
           && Session::isMultiEntitiesMode()) {

           // Retrieve data from glpi_plugin_ocsinventoryng_ocslinks
           $ocsLink = new PluginOcsinventoryngOcslink();
           $ocsLink->getFromDB($idlink);

           if (count($ocsLink->fields)) {
           // Retrieve datas from OCS database
           $ocsComputer = $ocsClient->getComputer($ocsLink->fields['ocsid']);

           if (!is_null($ocsComputer)) {
           $ocsComputer = Toolbox::addslashes_deep($ocsComputer);
           PluginOcsinventoryngOcsServer::transferComputer($ocsLink->fields, $ocsComputer);
           }
           }
           }
           $comp = new Computer();
           $comp->getFromDB($computers_id);
           $input["id"]            = $computers_id;
           $input["entities_id"]   = $comp->fields['entities_id'];
           $input["is_dynamic"]    = 1;
           $input["_nolock"]       = true;

           // Not already import from OCS / mark default state
           if ((!$ocs_id_change && ($ocsConfig["states_id_default"] > 0))
           || (!$comp->fields['is_dynamic']
           && ($ocsConfig["states_id_default"] > 0))) {
           $input["states_id"] = $ocsConfig["states_id_default"];
           }
           $comp->update($input);
           // Auto restore if deleted
           if ($comp->fields['is_deleted']) {
           $comp->restore(array('id' => $computers_id));
           }

           // Reset only if not in ocs id change case
           if (!$ocs_id_change) {
           if ($ocsConfig["import_general_os"]) {
           PluginOcsinventoryngOcsServer::resetDropdown($computers_id, "operatingsystems_id", "glpi_operatingsystems");
           }
           if ($ocsConfig["import_device_processor"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'DeviceProcessor');
           }
           if ($ocsConfig["import_device_iface"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'DeviceNetworkCard');
           }
           if ($ocsConfig["import_device_memory"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'DeviceMemory');
           }
           if ($ocsConfig["import_device_hdd"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'DeviceHardDrive');
           }
           if ($ocsConfig["import_device_sound"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'DeviceSoundCard');
           }
           if ($ocsConfig["import_device_gfxcard"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'DeviceGraphicCard');
           }
           if ($ocsConfig["import_device_drive"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'DeviceDrive');
           }
           if ($ocsConfig["import_device_modem"] || $ocsConfig["import_device_port"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'DevicePci');
           }
           if ($ocsConfig["import_device_bios"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'PluginOcsinventoryngDeviceBiosdata');
           }
           if ($ocsConfig["import_device_motherboard"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'DeviceMotherboard');
           }
           if ($ocsConfig["import_software"]) {
           PluginOcsinventoryngOcsServer::resetSoftwares($computers_id);
           }
           if ($ocsConfig["import_disk"]) {
           PluginOcsinventoryngOcsServer::resetDisks($computers_id);
           }
           if ($ocsConfig["import_periph"]) {
           PluginOcsinventoryngOcsServer::resetPeripherals($computers_id);
           }
           if ($ocsConfig["import_monitor"]==1) { // Only reset monitor as global in unit management
           PluginOcsinventoryngOcsServer::resetMonitors($computers_id);    // try to link monitor with existing
           }
           if ($ocsConfig["import_printer"]) {
           PluginOcsinventoryngOcsServer::resetPrinters($computers_id);
           }
           if ($ocsConfig["import_registry"]) {
           PluginOcsinventoryngOcsServer::resetRegistry($computers_id);
           }
           $changes[0] = '0';
           $changes[1] = "";
           $changes[2] = $ocsid;
           PluginOcsinventoryngOcslink::history($computers_id, $changes,
           PluginOcsinventoryngOcslink::HISTORY_OCS_LINK);
           }
          */
         self::updateSnmp($idlink, $plugin_ocsinventoryng_ocsservers_id);
         return array('status' => PluginOcsinventoryngOcsServer::SNMP_LINKED,
            //'entities_id'  => $data['entities_id'],
         );
      }
      /*
        } else {
        //TRANS: %s is the OCS id
        Session::addMessageAfterRedirect(sprintf(__('Unable to import, GLPI computer is already related to an element of OCSNG (%d)',
        'ocsinventoryng'), $ocsid),
        false, ERROR);
        } */
      return false;
   }
}
?>