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
-------------------------------------------------------------------------- */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


/// OCS NetworkPort class
class PluginOcsinventoryngNetworkPort extends NetworkPortInstantiation {
   
   static $rightname = "plugin_ocsinventoryng";
   
   static function getTypeName($nb=0) {
      return _n('Unknown type from OCS', 'Unknown types from OCS', $nb, 'ocsinventoryng');
   }

   static private function getInvalidIPString($ip) {
      return __('Invalid', 'ocsinventoryng').': '.$ip;
   }


   static private function updateNetworkPort($mac, $name, $computers_id, $instantiation_type,
                                             $inst_input, $ips, $check_name, $dohistory,
                                             $already_known_ports) {
      global $DB;

      $network_port = new NetworkPort();

      // Then, find or create the base NetworkPort
      $query = "SELECT `id`, `is_dynamic`
                FROM `glpi_networkports`
                WHERE `itemtype` = 'Computer'
                   AND `items_id` = '$computers_id'
                   AND `mac` = '$mac'";

      // If there is virtual ports, then, filter by port's name
      if ($check_name) {
         $query .=  " AND `name` = '$name'";
      }

      if (count($already_known_ports) > 0) {
         $query .= " AND `id` NOT IN (".implode(',', $already_known_ports).")";
      }

      // We order by is_dynamic to be sure to get the static ones first !
      $query .= " ORDER BY `is_dynamic`, `id`";

      $ports = $DB->request($query);
      if ($ports->numrows() == 0) {
         $port_input = array('name'               => $name,
                             'mac'                => $mac,
                             'items_id'           => $computers_id,
                             'itemtype'           => 'Computer',
                             '_no_history'        => !$dohistory,
                             'instantiation_type' => $instantiation_type,
                             'is_dynamic'         => 1,
                             'is_deleted'         => 0);

         $networkports_id = $network_port->add($port_input);
         if ($networkports_id === false) {
            return -1;
         }
         $inst_input['networkports_id'] = $networkports_id;
         $instantiation                 = $network_port->getInstantiation();
         $instantiation->add($inst_input);
         unset($instantiation);

      } else {
         $line = $ports->next();
         $networkports_id = $line['id'];
         $network_port->getFromDB($networkports_id);
         if ((!$check_name) && ($network_port->fields['name'] != $name)) {
            $port_input = array('id'         => $network_port->getID(),
                                'name'       => $name,
                                'is_dynamic' => 1);
            $network_port->update($port_input);
         }
         if (($network_port->fields['instantiation_type'] != $instantiation_type)
             && ($network_port->fields['is_dynamic'] == 1)) {
            $network_port->switchInstantiationType($instantiation_type);
            $inst_input['networkports_id'] = $network_port->getID();
            $instantiation                 = $network_port->getInstantiation();
            $instantiation->add($inst_input);
            unset($instantiation);
         }
         if ($network_port->fields['instantiation_type'] == $instantiation_type) {
            $instantiation = $network_port->getInstantiation();
            $inst_input['id'] = $instantiation->getID();
            $inst_input['networkports_id'] = $network_port->getID();
            $instantiation->update($inst_input);
            unset($instantiation);
         }
      }

      if ($network_port->isNewItem()) {
         return -1;
      }

      $network_name = new NetworkName();
      $query = "SELECT `id`, `is_dynamic`
                FROM `glpi_networknames`
                WHERE `itemtype` = 'NetworkPort'
                   AND `items_id` = '$networkports_id'
                ORDER BY `is_dynamic`";
      if ((!$ips) || (count($ips) == 0)) {
         foreach ($DB->request($query) as $line) {
            if ($line['is_dynamic']) {
               $network_name->delete($line, true);
            }
         }
      } else {
         $names = $DB->request($query);
         if ($names->numrows() == 0) {
            $name_input = array('itemtype'    => 'NetworkPort',
                                'items_id'    => $networkports_id,
                                'is_dynamic'  => 1,
                                'is_deleted'  => 0,
                                '_no_history' => !$dohistory,
                                'name'        => 'OCS-INVENTORY-NG');
            $networknames_id = $network_name->add($name_input);
         } else {
            $line = $names->next();
            $networknames_id = $line['id'];
            foreach ($names as $line) {
               if (($line['is_dynamic'] == 1) && ($line['id'] != $networknames_id)){
                  $network_port->delete($line, true);
               }
            }
         }

         $ip_address = new IPAddress();
         $already_known_addresses = array();
         $query = "SELECT `id`, `name`, `is_dynamic`, `mainitems_id`
                   FROM `glpi_ipaddresses`
                   WHERE `itemtype` = 'NetworkName'
                     AND `items_id` = '$networknames_id'
                   ORDER BY `is_dynamic`";
         foreach ($DB->request($query) as $line) {
            if (in_array($line['name'], $ips) 
                  && !empty($line['mainitems_id'])) {
               $already_known_addresses[] = $line['id'];
               $ips = array_diff($ips, array($line['name']));
            } elseif ($line['is_dynamic'] == 1) {
               $ip_address->delete($line, true);
            }
         }
      }
      if ($ips) {
         foreach ($ips as $ip) {
            $ip_input = array('name'        => $ip,
                              'itemtype'    => 'NetworkName',
                              'items_id'    => $networknames_id,
                              '_no_history' => !$dohistory,
                              'is_dynamic'  => 1,
                              'is_deleted'  => 0);
            $ip_address->add($ip_input);
         }
      }

      return $network_port->getID();
   }

   // importNetwork
   static function importNetwork($ocsServerId, $cfg_ocs, $ocsComputer, $computers_id, $dohistory, $entities_id) {
      global $DB;
      
      // Group by DESCRIPTION, MACADDR, TYPE, TYPEMIB, SPEED, VIRTUALDEV
      // to get an array in IPADDRESS
      $ocsNetworks = array();
      if(isset($ocsComputer['NETWORKS'])) {
         foreach ($ocsComputer['NETWORKS'] as $ocsNetwork) {
            $key = $ocsNetwork['DESCRIPTION'].$ocsNetwork['MACADDR'].$ocsNetwork['TYPE']
                  .$ocsNetwork['TYPEMIB'].$ocsNetwork['SPEED'].$ocsNetwork['VIRTUALDEV'];
            
            if (!isset($ocsNetworks[$key])) {
               $ocsNetworks[$key] = $ocsNetwork;
               $ocsNetworks[$key]['IPADDRESS'] = array($ocsNetwork['IPADDRESS']);
            } else {
               $ocsNetworks[$key]['IPADDRESS'] []= $ocsNetwork['IPADDRESS'];
            }
         }
      }
      $network_ports  = array();
      $network_ifaces = array();
      foreach ($ocsNetworks as $line) {
         $line = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line));
         $mac  = $line['MACADDR'];
         if (!isset($network_ports[$mac])) {
            $network_ports[$mac] = array('virtual' => array());
         }
         $name = PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs["ocs_db_utf8"],
                                                                    $line['DESCRIPTION']);

         if (!empty($line['IPADDRESS'])) {
            $ip = $line['IPADDRESS'];
         } else {
            $ip = false;
         }
         $networkport_type = new PluginOcsinventoryngNetworkPortType();
         $networkport_type->getFromTypeAndTypeMIB($line);
         $speed = NetworkPortEthernet::transformPortSpeed($line['SPEED'], false);
         if (!empty($speed)) {
            $networkport_type->fields['speed'] = $speed;
         }

         $values = array('name'   => $name,
                         'type'   => (array_push($network_ifaces, $networkport_type) - 1),
                         'ip'     => $ip,
                         'result' => $line);

         // Virtual dev can be :
         //    1°) specifically defined from OCS
         //    2°) if there is already one main device
         //    3°) if the networkport is issued by VMWare
         if (((isset($line['VIRTUALDEV'])) && ($line['VIRTUALDEV'] == '1'))
             || (isset($network_ports[$mac]['main']))
             || (preg_match('/^vm(k|nic)([0-9]+)$/', $name))) {
            $network_ports[$mac]['virtual'] []= $values;
         } else {
            $network_ports[$mac]['main'] = $values;
         }
      }

      $already_known_ports  = array();
      $already_known_ifaces = array();
      foreach ($network_ports as $mac => $ports) {
         if (isset($ports['main'])) {
            $main = $ports['main'];
            $type = $network_ifaces[$main['type']];
            
            // First search for the Network Card
            $item_device = new Item_DeviceNetworkCard();
            $item_device->getFromDBByQuery(
                       "INNER JOIN `glpi_devicenetworkcards`
                               ON (`glpi_devicenetworkcards`.`designation`='".$main['name']."')
                        WHERE `glpi_items_devicenetworkcards`.`itemtype`='Computer'
                           AND `glpi_items_devicenetworkcards`.`items_id`='$computers_id'
                           AND `glpi_items_devicenetworkcards`.`mac`='$mac'
                           AND `glpi_items_devicenetworkcards`.`devicenetworkcards_id`=
                               `glpi_devicenetworkcards`.`id`");
            // If not found, then, create it
            if ($item_device->isNewItem()) {
               $deviceNetworkCard = new DeviceNetworkCard();
               $device_input      = array('designation' => $main['name'],
                                          'bandwidth'   => $type->fields['speed'],
                                          'entities_id'           => $entities_id);

               $net_id = $deviceNetworkCard->import($device_input);

               if ($net_id) {
                  $item_device->add(array('items_id'              => $computers_id,
                                          'itemtype'              => 'Computer',
                                          'entities_id'           => $entities_id,
                                          'devicenetworkcards_id' => $net_id,
                                          'mac'                   => $mac,
                                          '_no_history'           => !$dohistory,
                                          'is_dynamic'            => 1,
                                          'is_deleted'            => 0));
               }
            }
            if (!$item_device->isNewItem()) {
               $already_known_ifaces[] = $item_device->getID();
            }

            if ($type->fields['instantiation_type'] == __CLASS__) {
               $result = $main['result'];
               $inst_input = array('TYPE'    => $result['TYPE'],
                                   'TYPEMIB' => $result['TYPEMIB'],
                                   'speed'   => $result['SPEED']);
            } else {
               $inst_input = $type->fields;
               foreach (array('id', 'name', 'OCS_TYPE', 'OCS_TYPEMIB',
                              'instantiation_type', 'comment') as $field) {
                  unset($inst_input[$field]);
               }
            }
            $inst_input['items_devicenetworkcards_id'] = $item_device->getID();

            $networkports_id = self::updateNetworkPort($mac, $main['name'], $computers_id,
                                                       $type->fields['instantiation_type'],
                                                       $inst_input, $main['ip'], false,
                                                       $dohistory, $already_known_ports);

            if ($networkports_id < 0) {
               continue;
            }

            $already_known_ports[] = $networkports_id;
         } else {
            $networkports_id = 0;
         }

         foreach ($ports['virtual'] as $port) {
            $inst_input = array('networkports_id_alias' => $networkports_id);
            $id = self::updateNetworkPort($mac, $port['name'], $computers_id,
                                          'NetworkPortAlias', $inst_input, $port['ip'],
                                          true, $dohistory, $already_known_ports);
            if ($id > 0) {
               $already_known_ports[] = $id;
            }
         }
      }

      $query = "SELECT `id`
                FROM `glpi_networkports`
                WHERE `itemtype` = 'Computer'
                   AND `items_id` = '$computers_id'
                   AND `is_dynamic` = '1'";
      if (count($already_known_ports) > 0) {
         $query .= " AND `id` NOT IN ('".implode("', '", $already_known_ports)."')";
      }
      $network_ports = new NetworkPort();
      foreach ($DB->request($query) as $line) {
         $network_ports->delete($line, true);
      }

      $query = "SELECT `id`
                FROM `glpi_items_devicenetworkcards`
                WHERE `itemtype` = 'Computer'
                   AND `items_id` = '$computers_id'
                   AND `is_dynamic` = '1'";
      if (count($already_known_ifaces) > 0) {
         $query .= " AND `id` NOT IN ('".implode("', '", $already_known_ifaces)."')";
      }
      $item_device = new Item_DeviceNetworkCard();
      foreach ($DB->request($query) as $line) {
         $item_device->delete($line, true);
      }
   }


   function showInstantiationForm(NetworkPort $netport, $options=array(), $recursiveItems) {

      if (!$options['several']) {
         echo "<tr class='tab_bg_1'>\n";
         $this->showNetworkCardField($netport, $options, $recursiveItems);
         $this->showMacField($netport, $options);
         echo "</tr>\n";

         echo "<tr class='tab_bg_1'>\n";
         echo "<td>" . __('OCS TYPE', 'ocsinventoryng') . "</td>";
         echo "<td>".$this->fields['TYPE']."</td>\n";
         echo "<td>" . __('OCS MIB TYPE', 'ocsinventoryng') . "</td>";
         echo "<td>".$this->fields['TYPEMIB']."</td>\n";
         echo "</tr>\n";

         echo "<tr class='tab_bg_1'>\n";
         echo "<td>" . __('Create an entry for defining this type', 'ocsinventoryng') . "</td><td>";
         echo PluginOcsinventoryngNetworkPortType::getLinkToCreateFromTypeAndTypeMIB($this->fields);
         echo "</td>";
         echo "</tr>\n";
      }
   }


   /**
    * @see NetworkPortInstantiation::getInstantiationHTMLTableHeaders
   **/
   function getInstantiationHTMLTableHeaders(HTMLTableGroup $group, HTMLTableSuperHeader $super,
                                             HTMLTableSuperHeader $internet_super=NULL,
                                             HTMLTableHeader $father=NULL,
                                             array $options=array()) {

      DeviceNetworkCard::getHTMLTableHeader('NetworkPortWifi', $group, $super, NULL,
                                            $options);

      $group->addHeader('TYPE', __('OCS TYPE', 'ocsinventoryng'), $super);
      $group->addHeader('TYPEMIB', __('OCS MIB TYPE', 'ocsinventoryng'), $super);
      $group->addHeader('Generate', __('Create a mapping', 'ocsinventoryng'), $super);

      parent::getInstantiationHTMLTableHeaders($group, $super, $internet_super, $father, $options);
      return NULL;
   }


   /**
    * @see NetworkPortInstantiation::getInstantiationHTMLTable()
   **/
   function getInstantiationHTMLTable(NetworkPort $netport, HTMLTableRow $row,
                                      HTMLTableCell $father=NULL, array $options=array()) {

      DeviceNetworkCard::getHTMLTableCellsForItem($row, $this, NULL, $options);

      $row->addCell($row->getHeaderByName('Instantiation', 'TYPE'), $this->fields['TYPE']);
      $row->addCell($row->getHeaderByName('Instantiation', 'TYPEMIB'), $this->fields['TYPEMIB']);
      $link = PluginOcsinventoryngNetworkPortType::getFormURL(true).'?'.$this->getForeignKeyField().'='.$this->getID();
      $value = PluginOcsinventoryngNetworkPortType::getLinkToCreateFromTypeAndTypeMIB($this->fields);
      $row->addCell($row->getHeaderByName('Instantiation', 'Generate'), $value);

      parent::getInstantiationHTMLTable($netport, $row, $father, $options);
      return NULL;
   }


   function transformAccordingTypes() {
      global $DB;

      $networkport_type = new PluginOcsinventoryngNetworkPortType();
      if ($networkport_type->getFromTypeAndTypeMIB($this->fields)) {
         if (isset($networkport_type->fields['instantiation_type'])
             && ($networkport_type->fields['instantiation_type'] != __CLASS__)) {
            $networkport = $this->getItem();
            if ($networkport->switchInstantiationType($networkport_type->fields
                                                      ['instantiation_type']) !== false) {
               $instantiation = $networkport->getInstantiation();
               $input2        = $networkport_type->fields;
               unset($input2['id']);
               foreach (array('networkports_id', 'items_devicenetworkcards_id') as $field) {
                  if (isset($this->fields[$field])) {
                     $input2[$field] = $this->fields[$field];
                  }
               }
               if (isset($this->fields['speed'])) {
                  $input2['speed'] = NetworkPortEthernet::transformPortSpeed($this->fields
                                                                             ['speed'], false);
               }
               if ($instantiation->add($input2)) {
                  $this->delete(array(static::getIndexName() => $this->getID()));
                  return true;
               }
            }
         }
      }
      return false;
   }
   
   static private function getTextualType($type) {
      if (empty($type)) {
         return '<i>'.__('empty', 'ocsinventoryng').'</i>';
      }
      return $type;
   }

   static function displayInvalidList() {
      global $DB;

      $query = "SELECT DISTINCT `TYPE`
                  FROM `glpi_plugin_ocsinventoryng_networkports`";
      $type_results = $DB->request($query);
      echo "<br>\n<div class ='center'><table class='tab_cadrehov'>";
      if ($type_results->numrows() > 0) {
         echo "<tr class='tab_bg_2'><th colspan='4'>".__('Unknown network port type from OCS',
                                                         'ocsinventoryng')."</th></tr>";
         foreach ($type_results as $type) {
            $query = "SELECT `TYPEMIB`, `TYPE`,
                             GROUP_CONCAT(DISTINCT `speed` SEPARATOR '#') AS speed
                        FROM `glpi_plugin_ocsinventoryng_networkports`
                       WHERE `TYPE` = '".$type['TYPE']."'
                       GROUP BY `TYPEMIB`";
            $typemib_results = $DB->request($query);
            echo "<tr class='tab_bg_1'>";
            echo "<td rowspan='".$typemib_results->numrows()."'>".
                 self::getTextualType($type['TYPE'])."</td>";
            $first = True;
            foreach ($typemib_results as $typemib) {
               if (!$first) {
                  echo "<tr class='tab_bg_1'>";
               } else {
                  $first = False;
               }

               echo "<td>".self::getTextualType($typemib['TYPEMIB'])."</td>";

               // Normalize speeds ...
               $speeds = array();
               foreach (explode('#', $typemib['speed']) as $speed) {
                  $speed = NetworkPortEthernet::transformPortSpeed($speed, false);
                  if (($speed !== false) and (!in_array($speed, $speeds))) {
                     $speeds[] = $speed;
                  }
               }
               asort($speeds);
               $printable_speeds = array();
               foreach ($speeds as $speed) {
                  $printable_speeds[] = NetworkPortEthernet::transformPortSpeed($speed, true);
               }
               $typemib['speed'] = implode(', ', $printable_speeds);
               echo "<td>".$typemib['speed']."</td>";

               echo "<td>" . PluginOcsinventoryngNetworkPortType::
                             getLinkToCreateFromTypeAndTypeMIB($typemib)."</td>";

               echo "</tr>";
            }
         }
      } else {
         echo "<tr class='tab_bg_2'><th>".__('No unknown network port type from OCS !',
                                             'ocsinventoryng')."</th></tr>";
      }
      echo "</table></div>";
   }
   
   
   /**
    * @since version 0.85
    *
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $actions = parent::getSpecificMassiveActions($checkitem);

      return $actions;
   }
   
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'plugin_ocsinventoryng_update_networkport_type':
            echo "&nbsp;".
                 Html::submit(_x('button','Post'), array('name' => 'massiveaction'));
            return true;
    }
      return parent::showMassiveActionsSubForm($ma);
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {
      global $DB;
      
      switch ($ma->getAction()) {
         case "plugin_ocsinventoryng_update_networkport_type":
            $networkport = new PluginOcsinventoryngNetworkPort();
            $input = $ma->getInput();
            foreach ($ids as $id) {
               if ($networkport->getFromDBByQuery("WHERE `networkports_id` = '$id'")) {
                  if ($networkport->transformAccordingTypes()) {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                  }
               } else {
                  $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
               }
            }

            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }
}
?>