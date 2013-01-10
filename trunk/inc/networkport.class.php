<?php
/*
 * @version $Id: HEADER 15930 2012-12-15 11:10:55Z tsmr $
-------------------------------------------------------------------------
Ocsinventoryng plugin for GLPI
Copyright (C) 2012-2013 by the ocsinventoryng plugin Development Team.

https://forge.indepnet.net/projects/ocsinventoryng
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
--------------------------------------------------------------------------*/

// ----------------------------------------------------------------------
// Original Author of file: Damien Touraine
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// NetworkPortLocal class : local instantiation of NetworkPort. Among others, loopback
/// (ie.: 127.0.0.1)

class PluginOcsinventoryngNetworkPort extends NetworkPortInstantiation {

   static function importNetwork($PluginOcsinventoryngDBocs, $cfg_ocs, $ocsid,
                                   $CompDevice, $computers_id, $prevalue,
                                   $dohistory) {
      global $DB;

      $query2 = "SELECT *
                 FROM `networks`
                 WHERE `HARDWARE_ID` = '$ocsid'
                 ORDER BY `ID`";

      $result2                 = $PluginOcsinventoryngDBocs->query($query2);
      $i                       = 0;
      $manually_link           = false;
      $already_processed_iface = array();
      $already_processed_port  = array();
      $mac_id                 = false;
      
      if (isset($devID)) {
         unset($devID);
      }

      // Add network device
      if ($PluginOcsinventoryngDBocs->numrows($result2) > 0) {
         $mac_already_imported = array();
         while ($line2 = $PluginOcsinventoryngDBocs->fetch_array($result2)) {
            $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
            if ($cfg_ocs["import_device_iface"]) {
               $network['designation']
                  = PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs["ocs_db_utf8"],
                                                                       $line2['DESCRIPTION']);

               // MAC must be unique, except for wmware (internal/external use same MAC)
               if (preg_match('/^vm(k|nic)([0-9]+)$/', $line2['DESCRIPTION'])
                   || !in_array($line2['MACADDR'], $mac_already_imported)) {
                  $mac_already_imported[] = $line2["MACADDR"];

                  //Try to find if the network card still exists in GLPI
                  $query   = "SELECT `inc`.`id`
                              FROM `glpi_items_devicenetworkcards` as `inc`,
                                   `glpi_devicenetworkcards` as `nc`
                              WHERE `inc`.`itemtype`='Computer'
                                 AND `inc`.`items_id`='$computers_id'
                                 AND `nc`.`designation`='".$network['designation']."'
                                 AND `inc`.`devicenetworkcards_id`=`nc`.`id`";
                  $results = $DB->query($query);
                  $id  = false;
                  if ($DB->numrows($results) > 0) {
                     $id = $DB->result($results, 0, 'id');
                  }
                  
                  if (!$id) {
                     if (!empty ($line2["SPEED"])) {
                        $network["bandwidth"] = $line2["SPEED"];
                     }
                     //Import the card
                     $DeviceNetworkCard = new DeviceNetworkCard();
                     $net_id = $DeviceNetworkCard->import($network);
                     if ($net_id) {
                        //link the network card to the computer
                        $id = $CompDevice->add(array('items_id'              => $computers_id,
                                                      'itemtype'               => 'Computer',
                                                      'devicenetworkcards_id'  => $net_id,
                                                      'mac'                    => $line2["MACADDR"],
                                                      '_no_history'            => !$dohistory,
                                                      'is_dynamic'             => 1,
                                                      'is_deleted'             => 0));
                     }
                  } else {
                     $CompDevice->update(array('id'        => $id,
                                               'mac'        => $line2["MACADDR"],
                                               'is_dynamic' => 1));
                  }
               }
               $already_processed_iface[] = $id;
            }
            
            if (!empty ($line2["IPADDRESS"]) && $cfg_ocs["import_ip"]) {
               $ocs_ips = explode(",", $line2["IPADDRESS"]);
               $ocs_ips = array_unique($ocs_ips);
               sort($ocs_ips);

               $netport = array();
               $netport["name"]
                  = PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs["ocs_db_utf8"],
                                                                       $line2["DESCRIPTION"]);
               $netport["mac"]                   = $line2["MACADDR"];
               $netport["items_id"]              = $computers_id;
               $netport["itemtype"]              = 'Computer';
               $netport['networkinterface_name'] = $line2["TYPE"];
               $netport['MIB']                   = $line2["TYPEMIB"];
               $netport['netmask']               = $line2['IPMASK'];
               $netport['gateway']               = $line2['IPGATEWAY'];
               $netport['subnet']                = $line2['IPSUBNET'];
               $netport['is_dynamic']            = 1; //it is a dynamic port, created by OCS
               $netport['is_deleted']            = 0;
               
               if (isset($devID)) {
                  $netport['items_devicenetworkcards_id'] = $id;
               }
               $netport['speed'] = NetworkPortEthernet::transformPortSpeed($line2['SPEED'], false);
               if ($netport['speed'] === false) {
                  $netport['speed'] = 10;
               }
               $netport['invalid_network_interface'] = 0;
               switch ($line2["TYPE"]) {
                  case 'Ethernet':
                     $instantiation_type = 'NetworkPortEthernet';
                     break;

                  case 'Wifi':
                     $instantiation_type = 'NetworkPortWifi';
                     break;

                  default:
                     $netport['invalid_network_interface'] = 1;
                     $instantiation_type = 'PluginOcsinventoryngNetworkPort';
                     break;
               };

               $np = new NetworkPort();
               for ($j = 0 ; $j < count($ocs_ips) ; $j++) {
                  // First, we normalize the IP address to test with common values
                  $ip_object = new IPAddress($ocs_ips[$j]);
                  if ($ip_object->is_valid()) {
                     $ip_address = $ip_object->getTextual();
                  } else { // For instance 192.168.1. or ?? or toto
                     $ip_address = $ocs_ips[$j];
                  }
                  
                  $is_deleted         = false;
                  
                  //First search : look for the same port (same IP and same MAC)
                  $querySelectIDandIP = "SELECT PORT.`id` as id, ADDR.`name` as ip,
                                                PORT.`is_deleted`
                                          FROM `glpi_networkports` AS PORT
                                             LEFT JOIN `glpi_networknames` AS NAME
                                                ON (NAME.`itemtype` = 'NetworkPort'
                                                   AND NAME.`items_id` = PORT.`id`)
                                             LEFT JOIN `glpi_ipaddresses` AS ADDR
                                                ON (ADDR.`itemtype` = 'NetworkName'
                                                   AND ADDR.`items_id` = NAME.`id`)
                                          WHERE PORT.`itemtype` = 'Computer'
                                             AND PORT.`items_id` = '$computers_id'
                                             AND PORT.`mac` = '" . $line2["MACADDR"] . "'
                                             AND ADDR.`name` = '".$ip_address."'";
                  $results = $DB->query($querySelectIDandIP);
                  if ($DB->numrows($results) > 0) {
                     $mac_id     = $DB->result($results, 0, 'id');
                     $is_deleted = $DB->result($results, 0, 'is_deleted');
                  } else {
                     //Second search : IP may have change, so look only for mac address
                     $querySelectIDByMac = "SELECT PORT.`id` as id, PORT.`is_deleted`
                                            FROM `glpi_networkports` AS PORT
                                            WHERE PORT.`itemtype` = 'Computer'
                                               AND PORT.`items_id` = '$computers_id'
                                               AND PORT.`mac` = '" . $line2["MACADDR"] . "'";
                     $results = $DB->query($querySelectIDByMac);
                     if ($DB->numrows($results) > 0) {
                        $mac_id     = $DB->result($results, 0, 'id');
                        $is_deleted = $DB->result($results, 0, 'is_deleted');
                     }
                  }
                  
                  //If port is locked, do not try to go further
                  if ($mac_id && $is_deleted) {
                     $already_processed_port[] = $mac_id;
                     continue;
                  }
                  
                  $netport['_no_history'] =! $dohistory;

                  // Process for NetworkName
                  if ($ip_object->is_valid()) {
                     $netport['instantiation_type'] = $instantiation_type;
                     $netport['NetworkName_name']   = 'OCS-INVENTORY-NG-'.str_replace('.', '-',
                                                                                      $ip_address);
                     // Warning : index must be negative, otherwise, the address will be
                     //           considered to be updated, not added !
                  } else {
                     // In case of invalid IP we force the NetworkPort to be a
                     // PluginOcsinventoryngNetworkPort, thus we will keep the IP !
                     $netport['instantiation_type'] = 'PluginOcsinventoryngNetworkPort';

                    unset($netport['NetworkName_name']);
                  }

                   //Update already in DB
                  if ($mac_id > 0) {
                     $already_processed_port[]  = $mac_id;
                     $netport["logical_number"] = $j;
                     $netport["id"]             = $mac_id;

                     if ($ip_object->is_valid()) {
                        $queryForIP = "SELECT `glpi_networknames`.`id` as names_id,
                                              `glpi_ipaddresses`.`id` as ip_id,
                                              `glpi_ipaddresses`.`name`
                                       FROM `glpi_networknames`
                                       LEFT JOIN `glpi_ipaddresses` ON (
                                            `glpi_ipaddresses`.`itemtype` = 'NetworkName'
                                        AND `glpi_ipaddresses`.`items_id` = `glpi_networknames`.`id`)
                                       WHERE `glpi_networknames`.`itemtype` = 'NetworkPort'
                                        AND `glpi_networknames`.`items_id` = '$mac_id'";
                        $netport['NetworkName__ipaddresses'] = array();
                        $ip_id   = -1;
                        $name_id = -1;
                        foreach ($DB->request($queryForIP) as $ip_entry) {
                           // Clear all otherIPs
                           $netport['NetworkName__ipaddresses'][$ip_entry['ip_id']] = '';
                           if (($ip_entry['name'] == $ip_address) && ($ip_id < 0)) {
                              // If we find the IP, then we stock its ID
                              $ip_id   = $ip_entry['ip_id'];
                              $name_id = $ip_entry['names_id'];
                           }
                        }
                        // If the current IP is not found, then we change the last founded IP
                        if ($ip_id < 0 && isset($ip_entry['ip_id'])) {
                           $ip_id = $ip_entry['ip_id'];
                        }
                        if ($name_id < 0 && isset($ip_entry['names_id'])) {
                           $name_id = $ip_entry['names_id'];
                        }
                        $netport['NetworkName__ipaddresses'][$ip_id] = $ip_address;
                        $netport['NetworkName_id'] = $name_id;
                     }

                     $np->splitInputForElements($netport);
                     $np->update($netport);
                     $np->updateDependencies(1);
                  } else { //If new IP found
                     unset ($np->fields["netpoints_id"]);
                     unset ($netport["id"]);
                     unset ($np->fields["id"]);
                     $netport["logical_number"] = $j;

                     if ($ip_object->is_valid()) {
                        $netport['NetworkName__ipaddresses'] = array('-1' => $ip_address);
                     }

                     $np->splitInputForElements($netport);
                     $newID = $np->add($netport);
                     $np->updateDependencies(1);
                     $already_processed_port[] = $newID;
                  }
               }
            }
         }
      }
      
      //Remove unused network devices
      if ($cfg_ocs["import_device_iface"]) {
         $query = "SELECT `id`
                   FROM `glpi_items_devicenetworkcards`
                   WHERE `is_dynamic`='1'
                      AND `is_deleted`='0'
                      AND `itemtype`='Computer'
                      AND `items_id`='$computers_id'";
         if (!empty($already_processed_iface)) {
            $query.= "AND `id` NOT IN (".implode(',',$already_processed_iface).")";
         }
         foreach ($DB->request($query) as $data) {
            $CompDevice->delete($data, true);
         }
      }
      
      $port  = new NetworkPort();
      //Remove unused network devices
      $query = "SELECT `id`
                FROM `glpi_networkports`
                WHERE `is_dynamic`='1'
                   AND `is_deleted`='0'
                   AND `itemtype`='Computer'
                   AND `items_id`='$computers_id'";
      if (!empty($already_processed_port)) {
         $query.= "AND `id` NOT IN (".implode(',',$already_processed_port).")";
      }
      foreach ($DB->request($query) as $data) {
         $port->delete($data, true);
       }
   }
}

?>