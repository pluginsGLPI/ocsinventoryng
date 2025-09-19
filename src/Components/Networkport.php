<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2025 by the ocsinventoryng Development Team.

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

namespace GlpiPlugin\Ocsinventoryng\Components;

use CommonDBTM;
use Computer;
use DbUtils;
use DeviceNetworkCard;
use Glpi\DBAL\QueryExpression;
use GlpiPlugin\Ocsinventoryng\NetworkPortType;
use GlpiPlugin\Ocsinventoryng\OcsProcess;
use HTMLTableCell;
use HTMLTableGroup;
use HTMLTableHeader;
use HTMLTableRow;
use HTMLTableSuperHeader;
use IPAddress;
use IPNetwork;
use Item_DeviceNetworkCard;
use MassiveAction;
use NetworkName;
use NetworkPortEthernet;
use NetworkPortInstantiation;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class NetworkPort
 */
class NetworkPort extends NetworkPortInstantiation
{
    public static $rightname = "plugin_ocsinventoryng";

    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Unknown imported network port type', 'Unknown imported network ports types', $nb, 'ocsinventoryng');
    }


    /**
     * @param     $mac
     * @param     $name
     * @param     $computers_id
     * @param     $instantiation_type
     * @param     $inst_input
     * @param     $ips
     * @param     $check_name
     * @param     $cfg_ocs
     * @param     $already_known_ports
     *
     * @param     $mask
     * @param     $gateway
     * @param     $subnet
     * @param     $entities_id
     * @param int $speed
     *
     * @return int
     */
    private static function updateNetworkPort(
        $mac,
        $name,
        $computers_id,
        $instantiation_type,
        $inst_input,
        $ips,
        $check_name,
        $cfg_ocs,
        $already_known_ports,
        $mask,
        $gateway,
        $subnet,
        $entities_id,
        $speed = 0
    ) {
        global $DB;

        $network_port = new \NetworkPort();

        $install_network_history = 0;
        if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_network'] == 1 || $cfg_ocs['history_network'] == 2)) {
            $install_network_history = 1;
        }
        $uninstall_network_history = 0;
        if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_network'] == 1 || $cfg_ocs['history_network'] == 3)) {
            $uninstall_network_history = 1;
        }

        // Then, find or create the base NetworkPort
        $criteria = [
            'SELECT' => ['id','is_dynamic'],
            'FROM' => 'glpi_networkports',
            'WHERE' => [
                'itemtype' => 'Computer',
                'items_id' => $computers_id,
                'mac' => $mac,
            ],
            'ORDERBY' => 'is_dynamic, id',
        ];
        if ($check_name) {
            $criteria['WHERE'] = $criteria['WHERE'] + ['name' => $name];
        }
        if (!empty($already_processed)) {
            $criteria['WHERE'] = $criteria['WHERE'] + ['id' => ['NOT IN', $already_processed]];
        }
        $iterator = $DB->request($criteria);

        if (count($iterator) == 0) {
            $port_input = ['name'               => $name,
                'mac'                => $mac,
                'items_id'           => $computers_id,
                'itemtype'           => 'Computer',
                '_no_history'        => !$install_network_history,
                'instantiation_type' => $instantiation_type,
                '_create_children'   => 1,
                'is_dynamic'         => 1,
                'is_deleted'         => 0];

            $networkports_id = $network_port->add($port_input, [], $install_network_history);
            if ($networkports_id === false) {
                return -1;
            }

            $inst_input['networkports_id'] = $networkports_id;
            $instantiation                 = $network_port->getInstantiation();
            $inst_input['speed']           = NetworkPortEthernet::transformPortSpeed($speed, false);
            $instantiation->update($inst_input, $install_network_history);
            unset($instantiation);
        } else {
            foreach ($iterator as $line) {
                $networkports_id = $line['id'] ?? 0;
                if ($network_port->getFromDB($networkports_id)) {
                    if ((!$check_name) && ($network_port->fields['name'] != $name)) {
                        $port_input = [
                            'id' => $network_port->getID(),
                            'name' => $name,
                            'is_dynamic' => 1,
                        ];
                        $network_port->update($port_input, $install_network_history);
                    }
                    if (($network_port->fields['instantiation_type'] != $instantiation_type)
                        && ($network_port->fields['is_dynamic'] == 1)) {
                        $network_port->switchInstantiationType($instantiation_type);
                        $inst_input['networkports_id'] = $network_port->getID();
                        $inst_input['speed'] = NetworkPortEthernet::transformPortSpeed($speed, false);
                        $instantiation = $network_port->getInstantiation();

                        $instantiation->add($inst_input, [], $install_network_history);
                        unset($instantiation);
                    }
                    if ($network_port->fields['instantiation_type'] == $instantiation_type) {
                        $instantiation = $network_port->getInstantiation();
                        $inst_input['id'] = $instantiation->getID();
                        $inst_input['speed'] = NetworkPortEthernet::transformPortSpeed($speed, false);
                        $inst_input['networkports_id'] = $network_port->getID();
                        if ($instantiation->getID() > 0) {
                            $instantiation->update($inst_input, $install_network_history);
                        } else {
                            $instantiation->add($inst_input, [], $install_network_history);
                        }

                        unset($instantiation);
                    }
                }
            }
        }

        if ($network_port->isNewItem()) {
            return -1;
        }

        $network_name = new NetworkName();

        $criteria = [
            'SELECT' => ['id', 'is_dynamic'],
            'FROM' => 'glpi_networknames',
            'WHERE' => [
                'itemtype' => 'NetworkPort',
                'items_id' => $networkports_id,
            ],
            'ORDERBY' => 'is_dynamic',
        ];

        $iterator = $DB->request($criteria);

        if ((!$ips) || (count($ips) == 0)) {
            foreach ($iterator as $line) {
                if ($line['is_dynamic']) {
                    $network_name->delete($line, true, $uninstall_network_history);
                }
            }
        } else {
            $networknames_id = 0;

            if (count($iterator) == 0) {
                $comp = new Computer();
                $comp->getFromDB($computers_id);
                $name_input      = ['itemtype'    => 'NetworkPort',
                    'items_id'    => $networkports_id,
                    'is_dynamic'  => 1,
                    'is_deleted'  => 0,
                    '_no_history' => !$install_network_history,
                    'name'        => $comp->getName()];
                $networknames_id = $network_name->add($name_input);
            } else {
                foreach ($iterator as $line) {
                    $networknames_id = $line['id'];
                    if ($networknames_id > 0) {
                        if (($line['is_dynamic'] == 1) && ($line['id'] != $networknames_id)) {
                            $network_port->delete($line, true, $uninstall_network_history);
                        }
                    }
                }
            }
            if ($networknames_id > 0) {
                $ip_address              = new IPAddress();
                $already_known_addresses = [];
                $criteria = [
                    'SELECT' => ['id','name','is_dynamic','mainitems_id'],
                    'FROM' => 'glpi_ipaddresses',
                    'WHERE' => [
                        'itemtype' => 'NetworkName',
                        'items_id' => $networknames_id,
                    ],
                    'ORDERBY' => 'is_dynamic',
                ];
                $iterator = $DB->request($criteria);
                foreach ($iterator as $line) {
                    if (in_array($line['name'], $ips)
                      && !empty($line['mainitems_id'])) {
                        $already_known_addresses[] = $line['id'];
                        $ips                       = array_diff($ips, [$line['name']]);
                    } elseif ($line['is_dynamic'] == 1) {
                        $ip_address->delete($line, true, $uninstall_network_history);
                    }
                }
            }
        }


        if ($mask != ''
          && $gateway != ''
          && $subnet != ''
          && $subnet != '0.0.0.0') {
            $IPNetwork = new IPNetwork();

            $condition = ["address"     => $subnet,
                "netmask"     => $mask,
                "entities_id" => $entities_id];

            //To avoid the "Invalid gateway address" error message when adding a gateway to 0.0.0.0
            if ($gateway != '0.0.0.0') {
                $condition["gateway"] = $gateway;
            }
            $dbu = new DbUtils();
            if ($dbu->countElementsInTable('glpi_ipnetworks', $condition) == 0) {
                $input = [
                    'name'        => $subnet . '/' .
                                    $mask . ' - ' .
                                    $gateway,
                    'network'     => $subnet . ' / ' .
                                    $mask,
                    //               'gateway'     => $gateway,
                    'addressable' => 1,
                    'entities_id' => $entities_id,
                ];

                if ($gateway != '0.0.0.0') {
                    $input['gateway'] = $gateway;
                }

//                $IPNetwork->networkUpdate = true;
                $IPNetwork->add($input, [], $install_network_history);
            }
        }

        if ($ips) {
            foreach ($ips as $ip) {
                $ip_address = new IPAddress();
                $ip_input = [
                    'name' => $ip,
                    'itemtype' => 'NetworkName',
                    'items_id' => $networknames_id,
                    '_no_history' => !$install_network_history,
                    'is_dynamic' => 1,
                    'is_deleted' => 0,
                ];
                $ip_address->add($ip_input);
            }
        }

        return $network_port->getID();
    }

    // importNetwork

    /**
     * @param $cfg_ocs
     * @param $ocsComputer
     * @param $computers_id
     * @param $entities_id
     */
    public static function importNetwork($cfg_ocs, $ocsComputer, $computers_id, $entities_id)
    {
        global $DB;

        $install_devices_history = 0;
        if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_devices'] == 1 || $cfg_ocs['history_devices'] == 2)) {
            $install_devices_history = 1;
        }

        $uninstall_network_history = 0;
        if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_network'] == 1 || $cfg_ocs['history_network'] == 3)) {
            $uninstall_network_history = 1;
        }

        // Group by DESCRIPTION, MACADDR, TYPE, TYPEMIB, SPEED, VIRTUALDEV
        // to get an array in IPADDRESS
        $ocsNetworks = [];
        foreach ($ocsComputer as $ocsNetwork) {
            $key = $ocsNetwork['DESCRIPTION'] . $ocsNetwork['MACADDR'] . $ocsNetwork['TYPE']
                . $ocsNetwork['TYPEMIB'] . $ocsNetwork['SPEED'] . $ocsNetwork['VIRTUALDEV'];

            if (!isset($ocsNetworks[$key])) {
                $ocsNetworks[$key]              = $ocsNetwork;
                $ocsNetworks[$key]['IPADDRESS'] = [$ocsNetwork['IPADDRESS']];
            } else {
                $ocsNetworks[$key]['IPADDRESS'] [] = $ocsNetwork['IPADDRESS'];
            }
        }

        $network_ports  = [];
        $network_ifaces = [];
        foreach ($ocsNetworks as $line) {
            $networkport_type = new NetworkPortType();
            $networkport_type->getFromTypeAndTypeMIB($line);
            $networkport_type->fields['speed'] = $line['SPEED'];
            //         $speed = NetworkPortEthernet::transformPortSpeed($line['SPEED'], false);
            //         if (!empty($speed)) {
            //            $networkport_type->fields['speed'] = $speed;
            //         }

            $typen = (array_push($network_ifaces, $networkport_type) - 1);

            if (!isset($network_ports[$typen])) {
                $network_ports[$typen] = ['virtual' => []];
            }
            $name = OcsProcess::encodeOcsDataInUtf8(
                $cfg_ocs["ocs_db_utf8"],
                $line['DESCRIPTION']
            );

            if (!empty($line['IPADDRESS'])) {
                $ip = $line['IPADDRESS'];
            } else {
                $ip = false;
            }

            $values = ['name'   => $name,
                'type'   => $typen,
                'ip'     => $ip,
                'result' => $line];

            // Virtual dev can be :
            //    1°) specifically defined from OCS
            //    2°) if the networkport contains Virtual
            //    3°) if the networkport is issued by VMWare
            if (((isset($line['VIRTUALDEV'])) && ($line['VIRTUALDEV'] == '1'))
             || (isset($network_ports[$typen]['main']))
             || (preg_match('/^vm(k|nic)([0-9]+)$/', $name))
             || (preg_match('/(V|v)irtual/', $name))) {
                $network_ports[$typen]['virtual'] [] = $values;
            } else {
                $network_ports[$typen]['main'] = $values;
            }
        }

        $already_known_ports  = [];
        $already_known_ifaces = [];

        foreach ($network_ports as $id => $ports) {
            if (isset($ports['main'])) {
                $main = $ports['main'];
                $type = $network_ifaces[$main['type']];
                // First search for the Network Card
                $item_device = new Item_DeviceNetworkCard();

                $criteria = [
                    'SELECT' => 'glpi_items_devicenetworkcards.id',
                    'FROM' => 'glpi_items_devicenetworkcards',
                    'INNER JOIN'       => [
                        'glpi_devicenetworkcards' => [
                            'ON' => [
                                'glpi_items_devicenetworkcards' => 'devicenetworkcards_id',
                                'glpi_devicenetworkcards'          => 'id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        'glpi_items_devicenetworkcards.itemtype' => 'Computer',
                        'glpi_items_devicenetworkcards.items_id' => $computers_id,
                        'glpi_devicenetworkcards.designation' => $main['name'],
                        'glpi_items_devicenetworkcards.mac' => $main['result']['MACADDR'],
                    ],
                ];

                $item_net = $DB->request($criteria);

                // If not found, then, create it
                if (count($item_net) == 0
                    //$item_device->isNewItem()
                ) {
                    $deviceNetworkCard = new DeviceNetworkCard();
                    $device_input      = ['designation' => $main['name'],
                        'bandwidth'   => $type->fields['speed'],
                        'entities_id' => $entities_id];

                    $net_id = $deviceNetworkCard->import($device_input);

                    if ($net_id) {
                        if ($item_device->getFromDBByCrit(['items_id'              => $computers_id,
                            'itemtype'              => 'Computer',
                            'entities_id'           => $entities_id,
                            'devicenetworkcards_id' => $net_id,
                            'mac'                   => $main['result']['MACADDR'],
                            'is_dynamic'            => 1,
                            'is_deleted'            => 0])) {
                            $item_device->update(['id'                    => $item_device->getID(),
                                'items_id'              => $computers_id,
                                'itemtype'              => 'Computer',
                                'entities_id'           => $entities_id,
                                'devicenetworkcards_id' => $net_id,
                                'mac'                   => $main['result']['MACADDR'],
                                '_no_history'           => !$install_devices_history,
                                'is_dynamic'            => 1,
                                'is_deleted'            => 0], $install_devices_history);
                        } else {
                            $item_device->add(['items_id'              => $computers_id,
                                'itemtype'              => 'Computer',
                                'entities_id'           => $entities_id,
                                'devicenetworkcards_id' => $net_id,
                                'mac'                   => $main['result']['MACADDR'],
                                '_no_history'           => !$install_devices_history,
                                'is_dynamic'            => 1,
                                'is_deleted'            => 0], [], $install_devices_history);
                        }
                    }
                }
                if (!$item_device->isNewItem()) {
                    foreach ($item_net as $net) {
                        $item_device->getFromDB($net['id']);
                    }
                    $already_known_ifaces[] = $item_device->getID();
                }
                $networkports_id = -1;
                if ($cfg_ocs["import_ip"] == 1) {
                    if ($type->fields['instantiation_type'] == __CLASS__) {
                        $result     = $main['result'];
                        $inst_input = ['TYPE'    => $result['TYPE'],
                            'TYPEMIB' => $result['TYPEMIB'],
                            'speed'   => $result['SPEED']];
                    } else {
                        $inst_input = $type->fields;
                        foreach (['id', 'name', 'OCS_TYPE', 'OCS_TYPEMIB',
                            'instantiation_type', 'comment'] as $field) {
                            unset($inst_input[$field]);
                        }
                    }
                    $inst_input['items_devicenetworkcards_id'] = $item_device->getID();

                    $mask    = $main['result']['IPMASK'];
                    $gateway = $main['result']['IPGATEWAY'];
                    $subnet  = $main['result']['IPSUBNET'];
                    $speed   = $main['result']['SPEED'];
                    $status  = $main['result']['STATUS'];
                    //               if ($status == "Up") {
                    $networkports_id = self::updateNetworkPort(
                        $main['result']['MACADDR'],
                        $main['name'],
                        $computers_id,
                        $type->fields['instantiation_type'],
                        $inst_input,
                        $main['ip'],
                        false,
                        $cfg_ocs,
                        $already_known_ports,
                        $mask,
                        $gateway,
                        $subnet,
                        $entities_id,
                        $speed
                    );
                    //               }
                }
                if ($networkports_id < 0) {
                    continue;
                }
                if ($networkports_id > 0) {
                    $already_known_ports[] = $networkports_id;
                }
            } else {
                $networkports_id = 0;
            }
            if ($cfg_ocs["import_ip"] == 1) {
                if (isset($ports['virtual'])) {
                    foreach ($ports['virtual'] as $port) {
                        $mask       = $port['result']['IPMASK'];
                        $gateway    = $port['result']['IPGATEWAY'];
                        $subnet     = $port['result']['IPSUBNET'];
                        $status     = $port['result']['STATUS'];
                        $speed      = $port['result']['SPEED'];
                        $inst_input = ['networkports_id_alias' => $networkports_id];
                        //                  if ($status == "Up") {
                        $id = self::updateNetworkPort(
                            $port['result']['MACADDR'],
                            $port['name'],
                            $computers_id,
                            'NetworkPortAlias',
                            $inst_input,
                            $port['ip'],
                            true,
                            $cfg_ocs,
                            $already_known_ports,
                            $mask,
                            $gateway,
                            $subnet,
                            $entities_id,
                            $speed
                        );
                        //                  }
                        if ($id > 0) {
                            $already_known_ports[] = $id;
                        }
                    }
                }
            }
        }
    }

    /**
     * @param HTMLTableGroup       $group
     * @param HTMLTableSuperHeader $super
     * @param HTMLTableSuperHeader $internet_super
     * @param HTMLTableHeader      $father
     * @param array                $options
     *
     * @return null|the
     * @see NetworkPortInstantiation::getInstantiationHTMLTableHeaders
     *
     */
    public function getInstantiationHTMLTableHeaders(
        HTMLTableGroup       $group,
        HTMLTableSuperHeader $super,
        ?HTMLTableSuperHeader $internet_super = null,
        ?HTMLTableHeader      $father = null,
        array                $options = []
    ) {

        DeviceNetworkCard::getHTMLTableHeader(
            'NetworkPortWifi',
            $group,
            $super,
            null,
            $options
        );

        $group->addHeader('TYPE', __('OCS TYPE', 'ocsinventoryng'), $super);
        $group->addHeader('TYPEMIB', __('OCS MIB TYPE', 'ocsinventoryng'), $super);
        $group->addHeader('Generate', __('Create a mapping', 'ocsinventoryng'), $super);

        parent::getInstantiationHTMLTableHeaders($group, $super, $internet_super, $father, $options);
        return null;
    }


    /**
     * @param NetworkPort   $netport
     * @param HTMLTableRow  $row
     * @param HTMLTableCell $father
     * @param array         $options
     *
     * @return null|the
     * @see NetworkPortInstantiation::getInstantiationHTMLTable()
     *
     */
    public function getInstantiationHTMLTable(
        \NetworkPort   $netport,
        HTMLTableRow $row,
        ?HTMLTableCell $father = null,
        array $options = []
    ) {

        DeviceNetworkCard::getHTMLTableCellsForItem($row, $this, null, $options);

        $row->addCell($row->getHeaderByName('Instantiation', 'TYPE'), $this->fields['TYPE']);
        $row->addCell($row->getHeaderByName('Instantiation', 'TYPEMIB'), $this->fields['TYPEMIB']);
        $link  = NetworkPortType::getFormURL(true) . '?' . $this->getForeignKeyField() . '=' . $this->getID();
        $value = NetworkPortType::getLinkToCreateFromTypeAndTypeMIB($this->fields);
        $row->addCell($row->getHeaderByName('Instantiation', 'Generate'), $value);

        parent::getInstantiationHTMLTable($netport, $row, $father, $options);
        return null;
    }


    /**
     * @return bool
     */
    public function transformAccordingTypes()
    {

        $networkport_type = new NetworkPortType();
        if ($networkport_type->getFromTypeAndTypeMIB($this->fields)) {
            if (isset($networkport_type->fields['instantiation_type'])
             && ($networkport_type->fields['instantiation_type'] != __CLASS__)) {
                $networkport = $this->getItem();
                if ($networkport->switchInstantiationType($networkport_type->fields
                                                      ['instantiation_type']) !== false) {
                    $instantiation = $networkport->getInstantiation();
                    $input2        = $networkport_type->fields;
                    unset($input2['id']);
                    foreach (['networkports_id', 'items_devicenetworkcards_id'] as $field) {
                        if (isset($this->fields[$field])) {
                            $input2[$field] = $this->fields[$field];
                        }
                    }
                    if (isset($this->fields['speed'])) {
                        $input2['speed'] = NetworkPortEthernet::transformPortSpeed($this->fields
                                                                             ['speed'], false);
                    }
                    if ($instantiation->add($input2)) {
                        $this->delete([static::getIndexName() => $this->getID()]);
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param $type
     *
     * @return string
     */
    private static function getTextualType($type)
    {
        if (empty($type)) {
            return '<i>' . __('empty', 'ocsinventoryng') . '</i>';
        }
        return $type;
    }

    public static function displayInvalidList()
    {
        global $DB;

        $criteria = [
            'SELECT' => 'TYPE',
            'DISTINCT' => true,
            'FROM' => 'glpi_plugin_ocsinventoryng_networkports',
        ];
        $iterator = $DB->request($criteria);
        echo "<br>\n<div class ='center'><table class='tab_cadre_fixe'>";
        if (count($iterator) > 0) {
            echo "<tr class='tab_bg_2'><th colspan='4'>" . self::getTypeName(2) . "</th></tr>";
            foreach ($iterator as $type) {

                //                $query           = "SELECT `TYPEMIB`, `TYPE`,
                //                             GROUP_CONCAT(DISTINCT `speed` SEPARATOR '#') AS speed
                //                        FROM `glpi_plugin_ocsinventoryng_networkports`
                //                       WHERE `TYPE` = '" . $type['TYPE'] . "'
                //                       GROUP BY `TYPEMIB`";

                $criteria = [
                    'SELECT' => ['TYPEMIB',
                        'TYPE',
                        new QueryExpression("GROUP_CONCAT(DISTINCT `speed` SEPARATOR '#') AS " . $DB->quoteName('speed'). ""),
                    ],
                    'FROM' => 'glpi_plugin_ocsinventoryng_networkports',
                    'WHERE' => [
                        'TYPE' => $type['TYPE'],
                    ],
                    'GROUPBY' => 'TYPEMIB',
                ];
                $typemib_results = $DB->request($criteria);

                echo "<tr class='tab_bg_1'>";
                echo "<td rowspan='" . $typemib_results->numrows() . "'>" .
                 self::getTextualType($type['TYPE']) . "</td>";
                $first = true;
                foreach ($typemib_results as $typemib) {
                    if (!$first) {
                        echo "<tr class='tab_bg_1'>";
                    } else {
                        $first = false;
                    }

                    echo "<td>" . self::getTextualType($typemib['TYPEMIB']) . "</td>";

                    // Normalize speeds ...
                    $speeds = [];
                    foreach (explode('#', $typemib['speed']) as $speed) {
                        $speed = NetworkPortEthernet::transformPortSpeed($speed, false);
                        if (($speed !== false) and (!in_array($speed, $speeds))) {
                            $speeds[] = $speed;
                        }
                    }
                    asort($speeds);
                    $printable_speeds = [];
                    foreach ($speeds as $speed) {
                        $printable_speeds[] = NetworkPortEthernet::transformPortSpeed($speed, true);
                    }
                    $typemib['speed'] = implode(', ', $printable_speeds);
                    echo "<td>" . $typemib['speed'] . "</td>";

                    echo "<td>" . NetworkPortType::getLinkToCreateFromTypeAndTypeMIB($typemib) . "</td>";

                    echo "</tr>";
                }
            }
        } else {
            echo "<tr class='tab_bg_2'><th>" . __(
                'No unknown network port type from OCS !',
                'ocsinventoryng'
            ) . "</th></tr>";
        }
        echo "</table></div>";
    }


    /**
     * @param null $checkitem
     *
     * @return array
     * @since version 0.85
     *
     * @see CommonDBTM::getSpecificMassiveActions()
     *
     */
    public function getSpecificMassiveActions($checkitem = null)
    {

        $actions = parent::getSpecificMassiveActions($checkitem);

        return $actions;
    }

    /**
     * @return an|array
     */
    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    /**
     * @param MassiveAction $ma
     *
     * @return bool|false
     * @since version 0.85
     *
     * @see CommonDBTM::showMassiveActionsSubForm()
     *
     */
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case 'plugin_ocsinventoryng_update_networkport_type':
                echo "&nbsp;" .
                 Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
        }
        return parent::showMassiveActionsSubForm($ma);
    }


    /**
     * @param MassiveAction $ma
     * @param CommonDBTM    $item
     * @param array         $ids
     *
     * @return nothing|void
     * @since version 0.85
     *
     * @see CommonDBTM::processMassiveActionsForOneItemtype()
     *
     */
    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array         $ids
    ) {

        switch ($ma->getAction()) {
            case "plugin_ocsinventoryng_update_networkport_type":
                $networkport = new NetworkPort();
                foreach ($ids as $id) {
                    if ($networkport->getFromDBByCrit(['networkports_id' => $id])) {
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

    public static function getNetworkPortInstantiationsWithNames()
    {

        $types = \NetworkPort::getNetworkPortInstantiations();
        $tab   = [];
        foreach ($types as $itemtype) {
            $tab[$itemtype] = call_user_func([$itemtype, 'getTypeName']);
        }
        return $tab;
    }
}
