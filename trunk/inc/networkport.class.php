<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
*/

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

   public $canHaveVLAN = true;
   public $haveMAC     = true;


   static function getTypeName($nb=0) {
      return _n('Network port import', 'Network ports import', $nb, 'ocsinventoryng');
   }


   static function getMotives() {

      return array('invalid_ip'                => __('Invalid IP address'),
                   'invalid_network_interface' => __('Invalid network interface') );
       }


   function prepareInputForUpdate($input) {

      if (isset($input['transform_to'])) {
         $networkport = new NetworkPort();
         if ($networkport->getFromDB($this->fields['networkports_id'])) {
            if ($networkport->switchInstantiationType($input['transform_to']) !== false) {
               $instantiation = $networkport->getInstantiation();
               $input         = $this->fields;
                unset($input['id']);
                var_dump($input);
                $instantiation->add($input);
                exit();
                return false;
            }
         }
      }
      exit();

      // Return false because the current element does not exist any more ...
       return $input;
   }


   function showInstantiationForm(NetworkPort $netport, $options=array(), $recursiveItems) {

      $options['canedit'] = false;

      echo "<tr class='tab_bg_1'><td>" .__('Transform this network port to');
      echo "</td><td>";
      Dropdown::showItemTypes('transform_to', NetworkPort::getNetworkPortInstantiations(),
                              array('value' => "NetworkPortEthernet"));
      echo "</td>";
      echo "<td>" . __('Motive of not standard network port instantiation') ."</td>";
      $motives = array();
      foreach (self::getMotives() as $field => $name) {
         if ($this->fields[$field] == 1) {
            $motives[] = $name;
         }
      }
      echo "<td>".implode('<br>', $motives)."&nbsp;</td>\n";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('MAC') ."</td><td>".$netport->fields['mac']."</td>\n";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Network interface name') . "</td>";
      if ($this->fields['invalid_network_interface']) {
         $cell_delimiter = 'th';
      } else {
        $cell_delimiter = 'td';
      }
      echo "<$cell_delimiter>" . $this->fields['networkinterface_name'] . "</$cell_delimiter>";
      echo "<td>" . __('Management Information Base (MIB)') . "</td>";
      echo "<td>" .$this->fields['MIB']."</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . IPAddress::getTypeName(1) . "</td>";
      if ($this->fields['invalid_ip']) {
         $cell_delimiter = 'th';
      } else {
         $cell_delimiter = 'td';
      }
      echo "<$cell_delimiter>" . $this->fields['ip']."</$cell_delimiter>";
      echo "<td>" . IPNetmask::getTypeName(1) . "</td><td>" . $this->fields['netmask']."</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Gateway') . "</td><td>" . $this->fields['gateway']."</td>";
      echo "<td>" . IPNetwork::getTypeName(1) . "</td><td>" . $this->fields['subnet']."</td>";
      echo "</tr>\n";
   }


   /**
    * @param $group              HTMLTable_Group object
    * @param $super              HTMLTable_SuperHeader object
    * @param $options   array
   **/
   function getInstantiationHTMLTable_Headers(HTMLTable_Group $group, HTMLTable_SuperHeader $super,
                                              HTMLTable_SuperHeader $internet_super = NULL,
                                              HTMLTable_Header $father=NULL,
                                              array $options=array()) {


      $group->addHeader('Motive', __('Motive'), $super);

      $group->addHeader('InterfaceName', __('Network interface name'), $super);
      $group->addHeader('MIB', __('Management Information Base (MIB)'), $super);

      $group->addHeader('IPAddress', IPAddress::getTypeName(1), $super);
      $group->addHeader('Netmask', IPNetmask::getTypeName(1), $super);
      $group->addHeader('Gateway', __('Gateway'), $super);
      $group->addHeader('Subnet', IPNetwork::getTypeName(1), $super);

      parent::getInstantiationHTMLTable_Headers($group, $super, $internet_super, $father, $options);

      return NULL;
   }


   /**
    * @see inc/NetworkPortInstantiation::getInstantiationHTMLTable_()
   **/
   function getInstantiationHTMLTable_(NetworkPort $netport, HTMLTable_Row $row,
                                       HTMLTable_Cell $father=NULL, array $options=array()) {


      foreach (self::getMotives() as $field => $name) {
         if ($this->fields[$field] == 1) {
            $row->addCell($row->getHeaderByName('Instantiation', 'Motive'), $name);
         }
      }
      $row->addCell($row->getHeaderByName('Instantiation', 'InterfaceName'),
                                          $this->fields['networkinterface_name']);
      $row->addCell($row->getHeaderByName('Instantiation', 'MIB'), $this->fields['MIB']);
      $row->addCell($row->getHeaderByName('Instantiation', 'IPAddress'), $this->fields['ip']);
      $row->addCell($row->getHeaderByName('Instantiation', 'Netmask'), $this->fields['netmask']);
      $row->addCell($row->getHeaderByName('Instantiation', 'Gateway'), $this->fields['gateway']);
      $row->addCell($row->getHeaderByName('Instantiation', 'Subnet'), $this->fields['subnet']);

      parent::getInstantiationHTMLTable_($netport, $row, $father, $options);

      return NULL;
   }


   function getSpecificMassiveActions($checkitem=NULL) {

      $isadmin = $this->canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin) {
         $actions['transform_to'] = __('Transform this network port to');
      }
      return $actions;
   }


   function showSpecificMassiveActionsParameters($input = array()) {

      switch ($input['action']) {
         case "transform_to" :
            Dropdown::showItemTypes('transform_to', NetworkPort::getNetworkPortInstantiations(),
                                    array('value' => 'NetworkPortEthernet'));
            echo "<br><br><input type='submit' name='massiveaction' class='submit' value='".
                           _sx('button', 'Save')."'>";
            return true;

         default :
            return parent::showSpecificMassiveActionsParameters($input);
      }

      return false;
   }


   function doSpecificMassiveActions($input = array()) {

      $res = array('ok'      => 0,
                   'ko'      => 0,
                   'noright' => 0);

      switch ($input['action']) {
         case "transform_to" :
            if (isset($input["transform_to"]) && !empty($input["transform_to"])) {
               $networkport = new NetworkPort();
               foreach ($input["item"] as $key => $val) {
                  if ($val == 1) {
                     if ($networkport->can($key,'w') && $this->can($key,'d')) {
                        if ($networkport->switchInstantiationType($input['transform_to']) !== false) {
                           $instantiation             = $networkport->getInstantiation();
                           $input2                    = $item->fields;
                           $input2['networkports_id'] = $input2['id'];
                           unset($input2['id']);
                           if ($instantiation->add($input2)) {
                              $this->delete(array('id' => $key));
                              $res['ok']++;
                           } else {
                              $res['ko']++;
                           }
                        } else {
                           $res['ko']++;
                        }
                     } else {
                        $res['noright']++;
                     }
                  }
               }
            } else {
               $res['ko']++;
            }
            break;

         default :
            return parent::doSpecificMassiveActions($input);
      }

      return $res;
   }


   static function importNetwork($import_ip, $PluginOcsinventoryngDBocs, $cfg_ocs, $ocsid,
                                 $CompDevice, $computers_id, $prevalue, $import_device,
                                 $dohistory) {
      global $DB;

      $do_clean = true;
      //If import_ip doesn't contain _VERSION_072_, then migrate it to the new architecture
      if (!in_array(PluginOcsinventoryngOcsServer::IMPORT_TAG_072,$import_ip)) {
         $import_ip = PluginOcsinventoryngOcsServer::migrateImportIP($computers_id, $import_ip);
      }
      $query2 = "SELECT *
                 FROM `networks`
                 WHERE `HARDWARE_ID` = '$ocsid'
                 ORDER BY `ID`";

      $result2       = $PluginOcsinventoryngDBocs->query($query2);
      $i             = 0;
      $manually_link = false;

      //Count old ip in GLPI
      $count_ip = count($import_ip);

      // Add network device
      if ($PluginOcsinventoryngDBocs->numrows($result2) > 0) {
         $mac_already_imported = array();
         while ($line2 = $PluginOcsinventoryngDBocs->fetch_array($result2)) {
            $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
            if ($cfg_ocs["import_device_iface"]) {
               if (!Toolbox::seems_utf8($line2["DESCRIPTION"])) {
                  $network["designation"] = Toolbox::encodeInUtf8($line2["DESCRIPTION"]);
               } else {
                  $network["designation"] = $line2["DESCRIPTION"];
               }

               // MAC must be unique, except for wmware (internal/external use same MAC)
               if (preg_match('/^vm(k|nic)([0-9]+)$/', $line2['DESCRIPTION'])
                   || !in_array($line2['MACADDR'], $mac_already_imported)) {
                  $mac_already_imported[] = $line2["MACADDR"];

                  if (!in_array(stripslashes($prevalue.$network["designation"]),
                                             $import_device)) {
                     if (!empty ($line2["SPEED"])) {
                        $network["bandwidth"] = $line2["SPEED"];
                     }
                     $DeviceNetworkCard = new DeviceNetworkCard();
                     $net_id = $DeviceNetworkCard->import($network);
                     if ($net_id) {
                        $devID = $CompDevice->add(array('computers_id'          => $computers_id,
                                                        '_itemtype'             => 'DeviceNetworkCard',
                                                        'devicenetworkcards_id' => $net_id,
                                                        'specificity'           => $line2["MACADDR"],
                                                        '_no_history'           => !$dohistory));
                        PluginOcsinventoryngOcsServer::addToOcsArray($computers_id,
                                                                     array($prevalue.$devID
                                                                           => $prevalue.$network["designation"]),
                                                                     "import_device");
                     }
                  } else {
                     $tmp = array_search(stripslashes($prevalue.$network["designation"]),
                                         $import_device);
                     list($type, $id) = explode(PluginOcsinventoryngOcsServer::FIELD_SEPARATOR, $tmp);
                     $CompDevice->update(array('id'          => $id,
                                               'specificity' => $line2["MACADDR"],
                                               '_itemtype'   => 'DeviceNetworkCard'));
                     unset ($import_device[$tmp]);
                  }
               }
            }
            if (!empty ($line2["IPADDRESS"]) && $cfg_ocs["import_ip"]) {
               $ocs_ips = explode(",", $line2["IPADDRESS"]);
               $ocs_ips = array_unique($ocs_ips);
               sort($ocs_ips);

               //if never imported (only 0.72 tag in array), check if existing ones match
               if ($count_ip == 1) {
                  //get old IP in DB
                  $querySelectIDandIP = "SELECT PORT.`id` as id, ADDR.`name` as ip
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
                                              AND PORT.`name` = '".$line2["DESCRIPTION"]."'";
                  $result = $DB->query($querySelectIDandIP);
                  if ($DB->numrows($result) > 0) {
                     while ($data = $DB->fetch_array($result)) {
                        //Upate import_ip column and import_ip array
                        PluginOcsinventoryngOcsServer::addToOcsArray($computers_id,
                                                                     array($data["id"]
                                                                            => $data["ip"].
                                                                               PluginOcsinventoryngOcsServer::FIELD_SEPARATOR.
                                                                               $line2["MACADDR"]),
                                                                     "import_ip");
                        $import_ip[$data["id"]] = $data["ip"].
                                                  PluginOcsinventoryngOcsServer::FIELD_SEPARATOR.
                                                  $line2["MACADDR"];
                     }
                  }
               }
               $netport = array();
               $netport["mac"]                   = $line2["MACADDR"];
               $netport["name"]                  = $line2["DESCRIPTION"];
               $netport["items_id"]              = $computers_id;
               $netport["itemtype"]              = 'Computer';
               $netport['networkinterface_name'] = $line2["TYPE"];
               $netport['MIB']                   = $line2["TYPEMIB"];
               $netport['netmask'] = $line2['IPMASK'];
               $netport['gateway'] = $line2['IPGATEWAY'];
               $netport['subnet']  = $line2['IPSUBNET'];

               switch ($line2['SPEED']) {
                  case "1 Gb/s"   :
                     $netport['speed'] = 1000;
                     break;

                  case "100 Mb/s" :
                     $netport['speed'] =  100;
                     break;

                  default:
                     $netport['speed'] =   10;
                     break;
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
               for ($j = 0 ; $j<count($ocs_ips) ; $j++) {
                  // First, we normalize the IP address to test with common values
                  $ip_object = new IPAddress($ocs_ips[$j]);
                  if ($ip_object->is_valid()) {
                     $netport['invalid_ip'] = 0;;
                     $ip_address = $ip_object->getTextual();
                  } else { // For instance 192.168.1. or ?? or toto
                     $netport['invalid_ip'] = 1;;
                     $ip_address = $ocs_ips[$j];
                  }

                  //First search : look for the same port (same IP and same MAC)
                  $id_ip = array_search($ip_address.PluginOcsinventoryngOcsServer::FIELD_SEPARATOR.
                                        $line2["MACADDR"], $import_ip);

                  //Second search : IP may have change, so look only for mac address
                  if (!$id_ip) {
                     //Browse the whole import_ip array
                     foreach ($import_ip as $ID => $ip) {
                        if ($ID > 0) {
                           $tmp = explode(PluginOcsinventoryngOcsServer::FIELD_SEPARATOR,$ip);
                           //Port was found by looking at the mac address
                           if (isset($tmp[1]) && $tmp[1] == $line2["MACADDR"]) {
                              //Remove port in import_ip
                              PluginOcsinventoryngOcsServer::deleteInOcsArray($computers_id, $ID,
                                                                              "import_ip");
                              PluginOcsinventoryngOcsServer::addToOcsArray($computers_id,
                                                                           array($ID
                                                                                 => $ip_address.
                                                                                   PluginOcsinventoryngOcsServer::FIELD_SEPARATOR.
                                                                                   $line2["MACADDR"]),
                                                                          "import_ip");
                              $import_ip[$ID] = $ip_address . PluginOcsinventoryngOcsServer::FIELD_SEPARATOR .
                              $line2["MACADDR"];
                              $id_ip = $ID;
                              break;
                           }
                        }
                     }
                  }
                  $netport['_no_history'] =! $dohistory;
                  $netport['ip']          = $ip_address;

                  // Process for NetworkName
                  if ($ip_object->is_valid()) {
                     $netport['instantiation_type'] = $instantiation_type;
                     $netport['NetworkName_name']   = 'OCS-INVENTORY-NG-'.str_replace('.', '-',
                                                                                      $ip_address);
                     $netport['NetworkName__ipaddresses'] = array($ip_address);
                  } else {
                     // In case of invalid IP we force the NetworkPort to be a
                     // PluginOcsinventoryngNetworkPort, thus we will keep the IP !
                     $netport['instantiation_type'] = 'PluginOcsinventoryngNetworkPort';

                     unset($netport['NetworkName__ipaddresses']);
                     unset($netport['NetworkName_name']);
                  }


                   //Update already in DB
                  if ($id_ip>0) {
                     $netport["logical_number"] = $j;
                     $netport["id"]             = $id_ip;

                     $np->splitInputForElements($netport);
                     $np->update($netport);
                     $np->updateDependencies(1);

                     unset ($import_ip[$id_ip]);
                     $count_ip++;

                  } else { //If new IP found
                     unset ($np->fields["netpoints_id"]);
                     unset ($netport["id"]);
                     unset ($np->fields["id"]);
                     $netport["ip"]             = $ip_address;
                     $netport["logical_number"] = $j;

                     $np->splitInputForElements($netport);
                     $newID = $np->add($netport);
                     $np->updateDependencies(1);

                     //ADD to array
                     PluginOcsinventoryngOcsServer::addToOcsArray($computers_id,
                                                                  array($newID
                                                                        => $ip_address.PluginOcsinventoryngOcsServer::FIELD_SEPARATOR.
                                               $line2["MACADDR"]),
                                         "import_ip");
                     $count_ip++;
                  }
               }
            }
         }
      }
   }

}
?>
