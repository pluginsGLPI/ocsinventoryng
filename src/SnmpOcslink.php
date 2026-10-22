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

namespace GlpiPlugin\Ocsinventoryng;

use Ajax;
use CommonDBTM;
use CommonGLPI;
use Computer;
use ComputerVirtualMachine;
use DbUtils;
use DeviceMemory;
use DeviceNetworkCard;
use DevicePci;
use DevicePowerSupply;
use DeviceProcessor;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Ocsinventoryng\Components\Software;
use Html;
use IPAddress;
use Item_DeviceMemory;
use Item_DeviceNetworkCard;
use Item_DevicePci;
use Item_DevicePowerSupply;
use Item_DeviceProcessor;
use Item_Disk;
use NetworkEquipment;
use NetworkName;
use NetworkPort;
use NetworkPort_NetworkPort;
use Phone;
use Session;
use Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class SnmpOcslink
 */
class SnmpOcslink extends CommonDBTM
{
    public static $snmptypes = ['Computer', 'NetworkEquipment', 'Peripheral', 'Phone', 'Printer'];
    public static $rightname = "plugin_ocsinventoryng";
    public const OTHER_DATA = 'other';

    /**
     * @see inc/CommonGLPI::getTabNameForItem()
     *
     * @param $item               CommonGLPI object
     * @param $withtemplate (default 0)
     *
     * @return string|translated
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (in_array($item->getType(), self::$snmptypes)
          && $this->canView()) {
            if ($this->getFromDBByCrit(['items_id' => $item->getID(), 'itemtype' => $item->getType()])) {
                return self::createTabEntry(__('OCSNG SNMP', 'ocsinventoryng'));
            }
        } elseif ($item->getType() == OcsServer::class) {
            if (OcsServer::checkOCSconnection($item->getID())
             && OcsServer::checkVersion($item->getID())
             && OcsServer::checkTraceDeleted($item->getID())) {
                $client  = OcsServer::getDBocs($item->getID());
                $version = $client->getTextConfig('GUI_VERSION');
                $snmp    = ($client->getIntConfig('SNMP') > 0) ? true : false;

                if ($version > OcsServer::OCS2_1_VERSION_LIMIT && $snmp) {
                    return self::createTabEntry(__('SNMP Import', 'ocsinventoryng'));
                }
            }
        }
        return '';
    }


    /**
     * @param $item            CommonGLPI object
     * @param $tabnum (default 1)
     * @param $withtemplate (default 0)
     *
     * @return bool|true
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if (in_array($item->getType(), self::$snmptypes)) {
            self::showForItem($item);
        } elseif ($item->getType() == OcsServer::class) {
            $conf = new self();
            $conf->ocsFormSNMPImportOptions($item->getID());
        }
        return true;
    }

    /**
     * @param $ID
     *
     * @internal param $withtemplate (default '')
     * @internal param $templateid (default '')
     */
    public function ocsFormSNMPImportOptions($ID)
    {
        $conf = new OcsServer();
        $conf->getFromDB($ID);

        // The yes/no fields of a group: import (importsnmp_*) or link (linksnmp_*) options
        $group = function (string $prefix, string $title, array $labels) use ($conf) {
            $fields = [['type' => 'title', 'label' => $title]];
            foreach ($labels as $name => $label) {
                $fields[] = [
                    'type'  => 'yesno',
                    'name'  => $prefix . $name,
                    'value' => $conf->fields[$prefix . $name],
                    'label' => $label,
                ];
            }
            return $fields;
        };

        $sections = [];
        foreach ([
            'importsnmp_' => [
                'title'    => __('General SNMP import options', 'ocsinventoryng'),
                'icon'     => 'ti ti-download',
                'general'  => [
                    'name'         => __('Import SNMP name', 'ocsinventoryng'),
                    'serial'       => __('Import SNMP serial', 'ocsinventoryng'),
                    'comment'      => __('Import SNMP comment', 'ocsinventoryng'),
                    'contact'      => __('Import SNMP contact', 'ocsinventoryng'),
                    'location'     => __('Import SNMP location', 'ocsinventoryng'),
                    'domain'       => __('Import SNMP domain', 'ocsinventoryng'),
                    'manufacturer' => __('Import SNMP manufacturer', 'ocsinventoryng'),
                    'createport'   => __('Create network port', 'ocsinventoryng'),
                ],
                'computer' => [__('Computer SNMP import options', 'ocsinventoryng'), [
                    'computernetworkcards' => __('Import SNMP network cards', 'ocsinventoryng'),
                    'computermemory'       => __('Import SNMP memory', 'ocsinventoryng'),
                    'computerprocessors'   => __('Import SNMP processors', 'ocsinventoryng'),
                    'computersoftwares'    => __('Import SNMP softwares', 'ocsinventoryng'),
                    'computervm'           => __('Import SNMP virtual machines', 'ocsinventoryng'),
                    'computerdisks'        => __('Import SNMP volumes', 'ocsinventoryng'),
                ]],
                'printer'  => [__('Printer SNMP import options', 'ocsinventoryng'), [
                    'last_pages_counter' => __('Import SNMP last pages counter', 'ocsinventoryng'),
                    'printermemory'      => __('Import SNMP printer memory', 'ocsinventoryng'),
                ]],
                'network'  => [__('Networking SNMP import options', 'ocsinventoryng'), [
                    'firmware' => __('Import SNMP firmware', 'ocsinventoryng'),
                    'power'    => __('Import SNMP Power supplies', 'ocsinventoryng'),
                    'fan'      => __('Import SNMP Fans', 'ocsinventoryng'),
                ]],
            ],
            'linksnmp_' => [
                'title'    => __('General SNMP link options', 'ocsinventoryng'),
                'icon'     => 'ti ti-link',
                'general'  => [
                    'name'         => __('Link SNMP name', 'ocsinventoryng'),
                    'serial'       => __('Link SNMP serial', 'ocsinventoryng'),
                    'comment'      => __('Link SNMP comment', 'ocsinventoryng'),
                    'contact'      => __('Link SNMP contact', 'ocsinventoryng'),
                    'location'     => __('Link SNMP location', 'ocsinventoryng'),
                    'domain'       => __('Link SNMP domain', 'ocsinventoryng'),
                    'manufacturer' => __('Link SNMP manufacturer', 'ocsinventoryng'),
                    'createport'   => __('Create network port', 'ocsinventoryng'),
                ],
                'computer' => [__('Computer SNMP link options', 'ocsinventoryng'), [
                    'computernetworkcards' => __('Link SNMP network cards', 'ocsinventoryng'),
                    'computermemory'       => __('Link SNMP memory', 'ocsinventoryng'),
                    'computerprocessors'   => __('Link SNMP processors', 'ocsinventoryng'),
                    'computersoftwares'    => __('Link SNMP softwares', 'ocsinventoryng'),
                    'computervm'           => __('Link SNMP virtual machines', 'ocsinventoryng'),
                    'computerdisks'        => __('Link SNMP volumes', 'ocsinventoryng'),
                ]],
                'printer'  => [__('Printer SNMP link options', 'ocsinventoryng'), [
                    'last_pages_counter' => __('Link SNMP last pages counter', 'ocsinventoryng'),
                    'printermemory'      => __('Link SNMP printer memory', 'ocsinventoryng'),
                ]],
                'network'  => [__('Networking SNMP link options', 'ocsinventoryng'), [
                    'firmware' => __('Link SNMP firmware', 'ocsinventoryng'),
                    'power'    => __('Link SNMP Power supplies', 'ocsinventoryng'),
                    'fan'      => __('Link SNMP Fans', 'ocsinventoryng'),
                ]],
            ],
        ] as $prefix => $options) {
            $fields = array_slice($group($prefix, '', $options['general']), 1);
            foreach (['computer', 'printer', 'network'] as $kind) {
                array_push($fields, ...$group($prefix, $options[$kind][0], $options[$kind][1]));
            }
            $sections[] = [
                'title'  => $options['title'],
                'icon'   => $options['icon'],
                'fields' => $fields,
            ];
        }

        OcsServer::showOptionsForm($ID, $sections, true, 'updateSNMP');
    }

    /**
     * @param $plugin_ocsinventoryng_ocsservers_id
     */
    public static function snmpMenu($plugin_ocsinventoryng_ocsservers_id)
    {
        OcsServer::showServerChoice();

        [, $isactive] = OcsServer::getServerState($plugin_ocsinventoryng_ocsservers_id);
        if (!$isactive) {
            return;
        }

        Menu::displayMenuSections([
            [
                'title' => __('OCSNG SNMP import', 'ocsinventoryng'),
                'icon'  => 'ti ti-router',
                'link'  => [
                    'title' => __('See Setup : SNMP Import before', 'ocsinventoryng'),
                    'url'   => PLUGIN_OCS_WEBDIR . "/front/ocsserver.form.php?id=" . (int) $plugin_ocsinventoryng_ocsservers_id
                        . "&forcetab=" . urlencode(self::class . '$1'),
                ],
                'items' => [
                    [
                        'title' => __('Import new SNMP devices', 'ocsinventoryng'),
                        'url'   => PLUGIN_OCS_WEBDIR . "/front/ocsngsnmp.import.php",
                        'icon'  => 'ti-plus',
                    ],
                    [
                        'title' => __('Link SNMP devices to existing GLPI objects', 'ocsinventoryng'),
                        'url'   => PLUGIN_OCS_WEBDIR . "/front/ocsngsnmp.link.php",
                        'icon'  => 'ti-link',
                    ],
                    [
                        'title' => __('Synchronize snmp devices already imported', 'ocsinventoryng'),
                        'url'   => PLUGIN_OCS_WEBDIR . "/front/ocsngsnmp.sync.php",
                        'icon'  => 'ti-refresh',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Show OcsLink of an item
     *
     * @param $item                   CommonDBTM object
     *
     * @return void
     * @throws \GlpitestSQLError
     * @internal param int|string $withtemplate integer  withtemplate param (default '')
     */
    public static function showForItem(CommonDBTM $item)
    {
        global $DB;

        $items_id = $item->getField('id');
        if (!in_array($item->getType(), self::$snmptypes)
            || empty($items_id)
            || !$item->fields["is_dynamic"]
            || !Session::haveRight("plugin_ocsinventoryng_view", READ)) {
            return;
        }

        $data = $DB->request([
            'FROM'  => 'glpi_plugin_ocsinventoryng_snmpocslinks',
            'WHERE' => ['items_id' => (int) $items_id, 'itemtype' => $item->getType()],
            'LIMIT' => 1,
        ])->current();
        if ($data) {
            Ocslink::displayInventoryInfo([self::getInventoryInfoBlock($item, $data, true, true)]);
        }
    }

    /**
     * if Printer purged
     *
     * @param $print   \Printer object
     **/
    public static function purgePrinter(\Printer $print)
    {
        $snmp = new self();
        $snmp->deleteByCriteria(['items_id' => $print->getField("id"),
            'itemtype' => $print->getType()]);

        $ipdiscover = new IpdiscoverOcslink();
        $ipdiscover->deleteByCriteria(['items_id' => $print->getField("id"),
            'itemtype' => $print->getType()]);
    }

    /**
     * if Printer purged
     *
     * @param $per   \Peripheral object
     **/
    public static function purgePeripheral(\Peripheral $per)
    {
        $snmp = new self();
        $snmp->deleteByCriteria(['items_id' => $per->getField("id"),
            'itemtype' => $per->getType()]);
        $ipdiscover = new IpdiscoverOcslink();
        $ipdiscover->deleteByCriteria(['items_id' => $per->getField("id"),
            'itemtype' => $per->getType()]);
    }

    /**
     * if NetworkEquipment purged
     *
     * @param NetworkEquipment $net
     *
     * @internal param NetworkEquipment $comp object
     */
    public static function purgeNetworkEquipment(NetworkEquipment $net)
    {
        $snmp = new self();
        $snmp->deleteByCriteria(['items_id' => $net->getField("id"),
            'itemtype' => $net->getType()]);
        $ipdiscover = new IpdiscoverOcslink();
        $ipdiscover->deleteByCriteria(['items_id' => $net->getField("id"),
            'itemtype' => $net->getType()]);
    }

    /**
     * if Computer purged
     *
     * @param $comp   Computer object
     **/
    public static function purgeComputer(Computer $comp)
    {
        $snmp = new self();
        $snmp->deleteByCriteria(['items_id' => $comp->getField("id"),
            'itemtype' => $comp->getType()]);
        $ipdiscover = new IpdiscoverOcslink();
        $ipdiscover->deleteByCriteria(['items_id' => $comp->getField("id"),
            'itemtype' => $comp->getType()]);
    }

    /**
     * if Phone purged
     *
     * @param $pho   Phone object
     **/
    public static function purgePhone(Phone $pho)
    {
        $snmp = new self();
        $snmp->deleteByCriteria(['items_id' => $pho->getField("id"),
            'itemtype' => $pho->getType()]);
        $ipdiscover = new IpdiscoverOcslink();
        $ipdiscover->deleteByCriteria(['items_id' => $pho->getField("id"),
            'itemtype' => $pho->getType()]);
    }

    /**
     * Show simple inventory information of an item
     *
     * @param $item                   CommonDBTM object
     *
     * @return void
     * @throws \GlpitestSQLError
     */
    public static function showSimpleForItem(CommonDBTM $item)
    {
        global $DB;

        $items_id = $item->getField('id');
        if (empty($items_id) || !Session::haveRight("plugin_ocsinventoryng_view", READ)) {
            return;
        }

        $blocks = [];
        if (in_array($item->getType(), self::$snmptypes) && $item->fields["is_dynamic"]) {
            $data = $DB->request([
                'FROM'  => 'glpi_plugin_ocsinventoryng_snmpocslinks',
                'WHERE' => ['items_id' => (int) $items_id, 'itemtype' => $item->getType()],
                'LIMIT' => 1,
            ])->current();
            if ($data) {
                $blocks[] = self::getInventoryInfoBlock($item, $data);
            }
        }
        // IPDiscover links
        if (in_array($item->getType(), IpdiscoverOcslink::$hardwareItemTypes)) {
            $data = $DB->request([
                'FROM'  => 'glpi_plugin_ocsinventoryng_ipdiscoverocslinks',
                'WHERE' => ['items_id' => (int) $items_id, 'itemtype' => $item->getType()],
                'LIMIT' => 1,
            ])->current();
            if ($data) {
                $blocks[] = [
                    'title' => __('OCS Inventory NG IPDiscover Import informations', 'ocsinventoryng'),
                    'icon'  => 'ti ti-network',
                    'infos' => [
                        ['label' => __('Import date in GLPI', 'ocsinventoryng'), 'value' => Html::convDateTime($data["last_update"])],
                    ],
                ];
            }
        }

        Ocslink::displayInventoryInfo($blocks);
    }

    /**
     * The SNMP inventory information block of an item (Ocslink::displayInventoryInfo())
     *
     * @param CommonDBTM $item
     * @param array      $data          the SNMP link of the item
     * @param bool       $with_ocs_data whether to read the last inventory date and the uptime in OCS
     * @param bool       $tab           the OCSNG SNMP tab of the item: the "Delete link" action and,
     *                                  in debug mode, the SNMP data, instead of the synchronization
     *
     * @return array
     */
    public static function getInventoryInfoBlock(CommonDBTM $item, array $data, bool $with_ocs_data = false, bool $tab = false)
    {
        $linked = $data["linked"] ? __('Linked object', 'ocsinventoryng') : __('Imported object', 'ocsinventoryng');
        $infos  = [
            ['label' => __('Import date in GLPI', 'ocsinventoryng'),
                'value' => Html::convDateTime($data["last_update"]) . " (" . $linked . ")"],
        ];
        $debug = null;
        if ($with_ocs_data && OcsServer::checkOCSconnection($data['plugin_ocsinventoryng_ocsservers_id'])) {
            $ocsResult = OcsServer::getDBocs($data['plugin_ocsinventoryng_ocsservers_id'])->getSnmp([
                'MAX_RECORDS' => 1,
                'FILTER'      => ['IDS' => [$data['ocs_id']]],
            ]);
            foreach ($ocsResult['SNMP'] ?? [] as $snmp) {
                $infos[] = ['label' => __('Last OCSNG SNMP inventory date', 'ocsinventoryng'),
                    'value' => Html::convDateTime($snmp['META']['LASTDATE'])];
                // Reported by the SNMP device: escaped by the template
                $infos[] = ['label' => __('Uptime', 'ocsinventoryng'), 'value' => (string) $snmp['META']['UPTIME']];
            }
            if ($tab && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE && !empty($ocsResult['SNMP'])) {
                // Escaped by the template (pre)
                $debug = print_r($ocsResult['SNMP'], true);
            }
        }

        $params = [
            'items_id'                            => $item->getID(),
            'itemtype'                            => $item->getType(),
            'id'                                  => $data["id"],
            'plugin_ocsinventoryng_ocsservers_id' => $data["plugin_ocsinventoryng_ocsservers_id"],
        ];
        $actions = [];
        if ($tab) {
            // front/snmpocslink.form.php: the right of the plugin and of the item
            if (Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
                $actions[] = [
                    'title'  => _sx('button', 'Delete link', 'ocsinventoryng'),
                    'icon'   => 'ti-unlink',
                    'target' => Toolbox::getItemTypeFormURL(self::class),
                    'name'   => 'delete_link',
                    'params' => $params,
                ];
            }
        } elseif (Session::haveRight("plugin_ocsinventoryng_sync", UPDATE)) {
            $actions[] = [
                'title'  => _sx('button', 'Force SNMP synchronization', 'ocsinventoryng'),
                'target' => Toolbox::getItemTypeFormURL(self::class),
                'name'   => 'force_ocssnmp_resynch',
                'params' => $params,
            ];
        }

        return [
            'title'   => __('OCS Inventory NG SNMP Import informations', 'ocsinventoryng'),
            'icon'    => 'ti ti-router',
            'infos'   => $infos,
            'actions' => $actions,
            'debug'   => $debug,
        ];
    }

    // SNMP PART HERE

    /**
     * @param     $ocsid
     * @param     $plugin_ocsinventoryng_ocsservers_id
     * @param int $lock
     * @param     $params
     *
     * @return array
     */
    public static function processSnmp($ocsid, $plugin_ocsinventoryng_ocsservers_id, $params)
    {
        global $DB;



        $ocsid                               = (int) $ocsid;
        $plugin_ocsinventoryng_ocsservers_id = (int) $plugin_ocsinventoryng_ocsservers_id;

        //Check it machine is already present AND was imported by OCS AND still present in GLPI
        $datas = $DB->request([
            'SELECT' => 'id',
            'FROM'   => 'glpi_plugin_ocsinventoryng_snmpocslinks',
            'WHERE'  => [
                'ocs_id'                              => $ocsid,
                'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
            ],
            'LIMIT'  => 1,
        ])->current();

        if ($datas) {
            //Return code to indicates that the machine was synchronized
            //or only last inventory date changed
            return self::updateSnmp($datas["id"], $plugin_ocsinventoryng_ocsservers_id);
        }
        return self::importSnmp($ocsid, $plugin_ocsinventoryng_ocsservers_id, $params);
    }

    /**
     * @param $ocsid
     * @param $plugin_ocsinventoryng_ocsservers_id
     * @param $params
     *
     * @return array
     */
    public static function importSnmp($ocsid, $plugin_ocsinventoryng_ocsservers_id, $params)
    {
        global $DB;

        $p['entity']   = -1;
        $p['itemtype'] = -1;
        foreach ($params as $key => $val) {
            $p[$key] = $val;
        }
        $ocsid                               = (int) $ocsid;
        $plugin_ocsinventoryng_ocsservers_id = (int) $plugin_ocsinventoryng_ocsservers_id;
        // The type is queued from the request: one of the SNMP types only
        if (!in_array($p['itemtype'], self::$snmptypes, true)) {
            $p['itemtype'] = -1;
        }


        $ocsClient = OcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
        $cfg_ocs   = OcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
        //TODOSNMP entites_id ?

        $ocsSnmp = $ocsClient->getSnmpDevice($ocsid);

        if ($ocsSnmp['META']['ID'] == $ocsid && $p['itemtype'] != -1) {
            $itemtype = $p['itemtype'];

            $loc_id = 0;
            if ($cfg_ocs['importsnmp_location']) {
                $loc_id = Dropdown::importExternal('Location', OcsProcess::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['META']['LOCATION']));
            }

            if ($itemtype == "NetworkEquipment") {
                $id = self::addOrUpdateNetworkEquipment($itemtype, 0, $ocsSnmp, $loc_id, "add", false, $cfg_ocs);
            } elseif ($itemtype == "Printer") {
                $id = self::addOrUpdatePrinter($itemtype, 0, $ocsSnmp, $loc_id, "add", false, $cfg_ocs);
            } elseif ($itemtype == "Computer") {
                $id = self::addOrUpdateComputer($plugin_ocsinventoryng_ocsservers_id, $itemtype, 0, $ocsSnmp, $loc_id, "add", false, $cfg_ocs);
            } elseif ($itemtype == "Peripheral" || $itemtype == "Phone") {
                $id = self::addOrUpdateOther($itemtype, 0, $ocsSnmp, $loc_id, "add", false, $cfg_ocs);
            }
            //TODOSNMP
            //Monitor & Phone ???
            if (isset($id)) {
                $date = date("Y-m-d H:i:s");
                //Add to snmp link

                $DB->insert('glpi_plugin_ocsinventoryng_snmpocslinks', [
                    'items_id'                            => (int) $id,
                    'ocs_id'                              => $ocsid,
                    'itemtype'                            => $itemtype,
                    'last_update'                         => $date,
                    'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                ]);

                return ['status' => OcsProcess::SNMP_IMPORTED,
                    //'entities_id'  => $data['entities_id'],
                ];
            } else {
                return ['status' => OcsProcess::SNMP_FAILED_IMPORT,
                    //'entities_id'  => $data['entities_id'],
                ];
            }
        } else {
            return ['status' => OcsProcess::SNMP_FAILED_IMPORT,
                //'entities_id'  => $data['entities_id'],
            ];
        }
    }

    /**
     * @param      $plugin_ocsinventoryng_ocsservers_id
     * @param      $itemtype
     * @param int  $ID
     * @param      $ocsSnmp
     * @param      $loc_id
     * @param      $action
     * @param bool $linked
     *
     * @param      $cfg_ocs
     *
     * @return int
     * @throws \GlpitestSQLError
     */
    public static function addOrUpdatePrinter($itemtype, $ID, $ocsSnmp, $loc_id, $action, $linked, $cfg_ocs)
    {
        global $DB;

        $snmpDevice = new $itemtype();

        $input = [
            "is_dynamic"    => 1,
            "entities_id"   => ($_SESSION['glpiactive_entity'] ?? 0),
            "have_ethernet" => 1,
        ];


        if (($cfg_ocs['importsnmp_name'] && $action == "add")
          || ($cfg_ocs['linksnmp_name'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)) {
            $input["name"] = $ocsSnmp['META']['NAME'];
        }
        if (($cfg_ocs['importsnmp_contact'] && $action == "add")
          || ($cfg_ocs['linksnmp_contact'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_contact'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_contact'] && $linked)) {
            $input["contact"] = $ocsSnmp['META']['CONTACT'];
        }
        if (($cfg_ocs['importsnmp_comment'] && $action == "add")
          || ($cfg_ocs['linksnmp_comment'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_comment'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_contact'] && $linked)) {
            $input["comment"] = $ocsSnmp['META']['DESCRIPTION'];
        }
        if (($cfg_ocs['importsnmp_serial'] && $action == "add")
          || ($cfg_ocs['linksnmp_serial'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_serial'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_serial'] && $linked)) {
            $input["serial"] = $ocsSnmp['PRINTER'][0]['SERIALNUMBER'];
        }
        if (($cfg_ocs['importsnmp_last_pages_counter'] && $action == "add")
          || ($cfg_ocs['linksnmp_last_pages_counter'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_last_pages_counter'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_last_pages_counter'] && $linked)) {
            $input["last_pages_counter"] = $ocsSnmp['PRINTER'][0]['COUNTER'];
        }

        if ($loc_id > 0) {
            $input["locations_id"] = $loc_id;
        }

        $id_printer = 0;

        if ($action == "add") {
            $id_printer = $snmpDevice->add($input, ['unicity_error_message' => true], $cfg_ocs['history_hardware']);
        } else {
            $id_printer  = $ID;
            $input["id"] = $ID;
            if ($snmpDevice->getFromDB($id_printer)) {
                $input["entities_id"] = $snmpDevice->fields['entities_id'];
            }

            $snmpDevice->update($input, $cfg_ocs['history_hardware'], ['unicity_error_message' => false,
                '_no_history'           => !$cfg_ocs['history_hardware']]);
        }

        if ($id_printer > 0
          && isset($ocsSnmp['MEMORIES'])
          && (($cfg_ocs['importsnmp_printermemory'] && $action == "add")
              || ($cfg_ocs['linksnmp_printermemory'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_printermemory'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_printermemory'] && $linked))
          && count($ocsSnmp['MEMORIES']) > 0
          && $ocsSnmp['MEMORIES'][0]['CAPACITY'] > 0) {
            $dev['designation'] = __("Printer Memory", 'ocsinventoryng');

            $item   = new $itemtype();
            $entity = ($_SESSION['glpiactive_entity'] ?? 0);
            if ($item->getFromDB($id_printer)) {
                $entity = $item->fields['entities_id'];
            }

            $dev['entities_id'] = $entity;

            $device    = new DeviceMemory();
            $device_id = $device->import($dev);
            if ($device_id) {
                $CompDevice = new Item_DeviceMemory();

                if ($cfg_ocs['history_devices']) {
                    $dbu = new DbUtils();
                    $table = $dbu->getTableForItemType("Item_DeviceMemory");
                    $DB->delete($table, ['items_id' => (int) $id_printer, 'itemtype' => $itemtype]);
                }
                //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
                //            $CompDevice->deleteByCriteria(array('items_id' => $id_printer,
                //               'itemtype' => $itemtype), 1);
                $CompDevice->add(['items_id'          => $id_printer,
                    'itemtype'          => $itemtype,
                    'size'              => $ocsSnmp['MEMORIES'][0]['CAPACITY'],
                    'entities_id'       => $entity,
                    'devicememories_id' => $device_id,
                    'is_dynamic'        => 1], [], $cfg_ocs['history_devices']);
            }
        }

        if ($id_printer > 0
          && (($cfg_ocs['importsnmp_createport'] && $action == "add")
              || ($cfg_ocs['linksnmp_createport'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_createport'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_createport'] && $linked))
        ) {
            //Delete Existing network config
            foreach ($DB->request([
                'SELECT' => 'id',
                'FROM' => 'glpi_networkports',
                'WHERE' => ['items_id' => $id_printer,
                    'itemtype' => $itemtype]]) as $networkPortID) {

                foreach ($DB->request([
                    'SELECT' => 'id',
                    'FROM' => 'glpi_networknames',
                    'WHERE' => ['items_id' => $networkPortID['id'],
                        'itemtype' => 'NetworkPort']]) as $networkNameID) {
                    $ipAddress = new IPAddress();
                    $ipAddress->deleteByCriteria(['items_id' => $networkNameID['id'],
                        'itemtype' => 'NetworkName'], 1);
                }

                $nn = new NetworkName();
                $nn->deleteByCriteria(['items_id' => $networkPortID['id'],
                    'itemtype' => 'NetworkPort'], 1);
            }
            $np = new NetworkPort();
            $np->deleteByCriteria(['items_id' => $id_printer,
                'itemtype' => $itemtype], 1);

            //Add network port
            $ip  = $ocsSnmp['META']['IPADDR'];
            $mac = $ocsSnmp['META']['MACADDR'];

            $np = new NetworkPort();
            $data = $np->find(['mac' => $mac, 'items_id' => $id_printer, 'itemtype' => $itemtype]);

            if (count($data) < 1) {
                $item   = new $itemtype();
                $entity = ($_SESSION['glpiactive_entity'] ?? 0);
                if ($item->getFromDB($id_printer)) {
                    $entity = $item->fields['entities_id'];
                }

                $port_input = ['name'                     => $ocsSnmp['PRINTER'][0]['NAME'],
                    'mac'                      => $mac,
                    'items_id'                 => $id_printer,
                    'itemtype'                 => $itemtype,
                    'instantiation_type'       => "NetworkPortEthernet",
                    "entities_id"              => $entity,
                    "NetworkName__ipaddresses" => ["-100" => $ip],
                    '_create_children'         => 1,
                    //'is_dynamic'                => 1,
                    'is_deleted'               => 0];

                $np->add($port_input, [], $cfg_ocs['history_network']);
            }
        }

        return $id_printer;
    }

    /**
     * @param      $itemtype
     * @param int  $ID
     * @param      $ocsSnmp
     * @param      $loc_id
     * @param      $action
     * @param bool $linked
     *
     * @param      $cfg_ocs
     * @return int
     * @throws \GlpitestSQLError
     */
    public static function addOrUpdateNetworkEquipment($itemtype, $ID, $ocsSnmp, $loc_id, $action, $linked, $cfg_ocs)
    {
        global $DB;

        $snmpDevice = new $itemtype();
        $dbu = new DbUtils();
        $input = [
            "is_dynamic"   => 1,
            "entities_id"  => ($_SESSION['glpiactive_entity'] ?? 0),
            "is_recursive" => 0,
        ];

        if (($cfg_ocs['importsnmp_name'] && $action == "add")
          || ($cfg_ocs['linksnmp_name'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)) {
            if ($ocsSnmp['META']['NAME'] != "N/A") {
                $input["name"] = $ocsSnmp['META']['NAME'];
            } else {
                $input["name"] = $ocsSnmp['META']['DESCRIPTION'];
            }
        }
        if (($cfg_ocs['importsnmp_contact'] && $action == "add")
          || ($cfg_ocs['linksnmp_contact'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_contact'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_contact'] && $linked)) {
            $input["contact"] = $ocsSnmp['META']['CONTACT'];
        }
        if (($cfg_ocs['importsnmp_comment'] && $action == "add")
          || ($cfg_ocs['linksnmp_comment'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_comment'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_comment'] && $linked)) {
            $input["comment"] = $ocsSnmp['META']['DESCRIPTION'];
        }

        if ($loc_id > 0) {
            $input["locations_id"] = $loc_id;
        }


        if (!empty($ocsSnmp['SWITCH'])) {
            if (($cfg_ocs['importsnmp_manufacturer'] && $action == "add")
             || ($cfg_ocs['linksnmp_manufacturer'] && $linked)
             || ($action == "update" && $cfg_ocs['importsnmp_manufacturer'] && !$linked)
             || ($action == "update" && $cfg_ocs['linksnmp_manufacturer'] && $linked)) {
                $man_id                    = Dropdown::importExternal(
                    'Manufacturer',
                    OcsProcess::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['SWITCH'][0]['MANUFACTURER'])
                );
                $input['manufacturers_id'] = $man_id;
            }

            if (($cfg_ocs['importsnmp_firmware'] && $action == "add")
             || ($cfg_ocs['linksnmp_firmware'] && $linked)
             || ($action == "update" && $cfg_ocs['importsnmp_firmware'] && !$linked)
             || ($action == "update" && $cfg_ocs['linksnmp_firmware'] && $linked)) {
                $firm_id                               = Dropdown::importExternal(
                    'NetworkEquipmentFirmware',
                    OcsProcess::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['SWITCH'][0]['FIRMVERSION'])
                );
                $input['networkequipmentfirmwares_id'] = $firm_id;
            }

            if (($cfg_ocs['importsnmp_serial'] && $action == "add")
             || ($cfg_ocs['linksnmp_serial'] && $linked)
             || ($action == "update" && $cfg_ocs['importsnmp_serial'] && !$linked)
             || ($action == "update" && $cfg_ocs['linksnmp_serial'] && $linked)) {
                $input['serial'] = $ocsSnmp['SWITCH'][0]['SERIALNUMBER'];
            }
        }
        if (!empty($ocsSnmp['FIREWALLS'])) {
            if (($cfg_ocs['importsnmp_serial'] && $action == "add")
             || ($cfg_ocs['linksnmp_serial'] && $action == $linked)
             || ($action == "update" && $cfg_ocs['importsnmp_serial'] && !$linked)
             || ($action == "update" && $cfg_ocs['linksnmp_serial'] && $linked)) {
                $input['serial'] = $ocsSnmp['FIREWALLS']['SERIALNUMBER'];
            }
            // TODOSNMP ?
            //$input['networkequipmenttypes_id'] = self::checkIfExist("NetworkEquipmentType", "Firewall");
        }
        $id_network = 0;
        if ($action == "add") {
            $id_network = $snmpDevice->add($input, ['unicity_error_message' => true], $cfg_ocs['history_hardware']);
        } else {
            $input["id"] = $ID;
            $id_network  = $ID;
            if ($snmpDevice->getFromDB($id_network)) {
                $input["entities_id"] = $snmpDevice->fields['entities_id'];
            }
            $snmpDevice->update($input, $cfg_ocs['history_hardware'], ['unicity_error_message' => false,
                '_no_history'           => !$cfg_ocs['history_hardware']]);
        }

        if ($id_network > 0
            //&& $action == "add"
        ) {
            if (isset($ocsSnmp['POWERSUPPLIES'])
             && (($cfg_ocs['importsnmp_power'] && $action == "add")
                 || ($cfg_ocs['linksnmp_power'] && $linked)
                 || ($action == "update" && $cfg_ocs['importsnmp_power'] && !$linked)
                 || ($action == "update" && $cfg_ocs['linksnmp_power'] && $linked))
             && count($ocsSnmp['POWERSUPPLIES']) > 0) {
                $man_id = Dropdown::importExternal(
                    'Manufacturer',
                    OcsProcess::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['POWERSUPPLIES'][0]['MANUFACTURER'])
                );

                $pow['manufacturers_id'] = $man_id;
                $pow['designation']      = $ocsSnmp['POWERSUPPLIES'][0]['REFERENCE'];
                $pow['comment']          = $ocsSnmp['POWERSUPPLIES'][0]['DESCRIPTION'];

                $item   = new $itemtype();
                $entity = ($_SESSION['glpiactive_entity'] ?? 0);
                if ($item->getFromDB($id_network)) {
                    $entity = $item->fields['entities_id'];
                }

                $pow['entities_id'] = $entity;

                $power    = new DevicePowerSupply();
                $power_id = $power->import($pow);
                if ($power_id) {
                    $serial     = $ocsSnmp['POWERSUPPLIES'][0]['SERIALNUMBER'];
                    $CompDevice = new Item_DevicePowerSupply();

                    if ($cfg_ocs['history_devices']) {
                        $table = $dbu->getTableForItemType("Item_DevicePowerSupply");
                        $DB->delete($table, ['items_id' => (int) $id_network, 'itemtype' => $itemtype]);
                    }
                    //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
                    //               $CompDevice->deleteByCriteria(array('items_id' => $id_network,
                    //                  'itemtype' => $itemtype), 1);
                    $CompDevice->add(['items_id'               => $id_network,
                        'itemtype'               => $itemtype,
                        'entities_id'            => $entity,
                        'serial'                 => $serial,
                        'devicepowersupplies_id' => $power_id,
                        'is_dynamic'             => 1], [], $cfg_ocs['history_devices']);
                }
            }

            if (isset($ocsSnmp['FANS'])
             && (($cfg_ocs['importsnmp_fan'] && $action == "add")
                 || ($cfg_ocs['linksnmp_fan'] && $linked)
                 || ($action == "update" && $cfg_ocs['importsnmp_fan'] && !$linked)
                 || ($action == "update" && $cfg_ocs['linksnmp_fan'] && $linked))
             && count($ocsSnmp['FANS']) > 0) {
                $man_id                  = Dropdown::importExternal('Manufacturer', OcsProcess::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['FANS'][0]['MANUFACTURER']));
                $dev['manufacturers_id'] = $man_id;

                $dev['designation'] = $ocsSnmp['FANS'][0]['REFERENCE'];
                $dev['comment']     = $ocsSnmp['FANS'][0]['DESCRIPTION'];

                $item   = new $itemtype();
                $entity = ($_SESSION['glpiactive_entity'] ?? 0);
                if ($item->getFromDB($id_network)) {
                    $entity = $item->fields['entities_id'];
                }

                $dev['entities_id'] = $entity;

                $device    = new DevicePci();
                $device_id = $device->import($dev);
                if ($device_id) {
                    $CompDevice = new Item_DevicePci();
                    if ($cfg_ocs['history_devices']) {
                        $dbu = new DbUtils();
                        $table = $dbu->getTableForItemType("Item_DevicePci");
                        $DB->delete($table, ['items_id' => (int) $id_network, 'itemtype' => $itemtype]);
                    }
                    //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
                    //               $CompDevice->deleteByCriteria(array('items_id' => $id_network,
                    //                  'itemtype' => $itemtype), 1);
                    $CompDevice->add(['items_id'      => $id_network,
                        'itemtype'      => $itemtype,
                        'entities_id'   => $entity,
                        'devicepcis_id' => $device_id,
                        'is_dynamic'    => 1], [], $cfg_ocs['history_devices']);
                }
            }
        }
        if ($id_network > 0
          && (($cfg_ocs['importsnmp_createport'] && $action == "add")
              || ($cfg_ocs['linksnmp_createport'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_createport'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_createport'] && $linked))) {
            //Add local port
            $ip  = $ocsSnmp['META']['IPADDR'];
            $mac = $ocsSnmp['META']['MACADDR'];

            foreach ($DB->request([
                'SELECT' => 'id',
                'FROM' => 'glpi_networkports',
                'WHERE' => ['items_id' => $id_network,
                    'itemtype' => $itemtype]]) as $networkPortID) {

                foreach ($DB->request([
                    'SELECT' => 'id',
                    'FROM' => 'glpi_networknames',
                    'WHERE' => ['items_id' => $networkPortID['id'],
                        'itemtype' => 'NetworkPort']]) as $networkNameID) {
                    $ipAddress = new IPAddress();
                    $ipAddress->deleteByCriteria(['items_id' => $networkNameID['id'],
                        'itemtype' => 'NetworkName'], 1);
                }

                $nn = new NetworkName();
                $nn->deleteByCriteria(['items_id' => $networkPortID['id'],
                    'itemtype' => 'NetworkPort'], 1);
            }
            $np = new NetworkPort();
            $np->deleteByCriteria(['items_id' => $id_network,
                'itemtype' => $itemtype], 1);

            $np = new NetworkPort();
            $data = $np->find(['mac' => $mac, 'items_id' => $id_network, 'itemtype' => $itemtype]);
            if (count($data) < 1) {
                $item   = new $itemtype();
                $entity = ($_SESSION['glpiactive_entity'] ?? 0);
                if ($item->getFromDB($id_network)) {
                    $entity = $item->fields['entities_id'];
                }

                $port_input = ['name'                     => $ocsSnmp['META']['NAME'],
                    'mac'                      => $mac,
                    'items_id'                 => $id_network,
                    'itemtype'                 => $itemtype,
                    'instantiation_type'       => "NetworkPortLocal",
                    "entities_id"              => $entity,
                    "NetworkName__ipaddresses" => ["-100" => $ip],
                    '_create_children'         => 1,
                    'is_dynamic'               => 1,
                    'is_deleted'               => 0];

                $np->add($port_input, [], $cfg_ocs['history_network']);
            }

            //All PORTS
            if ($id_network > 0
             && isset($ocsSnmp['NETWORKS'])
             && count($ocsSnmp['NETWORKS']) > 0) {
                foreach ($ocsSnmp['NETWORKS'] as $k => $net) {
                    //$dev["designation"] = $net['SLOT'];
                    //$dev["comment"] = $net['TYPE'];
                    $mac = $net['MACADDR'];

                    $net['DEVICENAME'] = str_replace("SEP", "", $net['DEVICENAME']);
                    $mac_dest          = self::addMacSeparator($net['DEVICENAME']);

                    $np = new NetworkPort();
                    $data = $np->find(['mac' => $mac, 'items_id' => $id_network, 'itemtype' => $itemtype, 'instantiation_type' => 'NetworkPortEthernet']);
                    if (count($data) < 1) {
                        $item   = new $itemtype();
                        $entity = ($_SESSION['glpiactive_entity'] ?? 0);
                        if ($item->getFromDB($id_network)) {
                            $entity = $item->fields['entities_id'];
                        }

                        $port_input = ['name'               => $net['SLOT'],
                            'mac'                => $mac,
                            'items_id'           => $id_network,
                            'itemtype'           => $itemtype,
                            'instantiation_type' => "NetworkPortEthernet",
                            "entities_id"        => $entity,
                            //"NetworkName__ipaddresses" => array("-100" => $ip),
                            '_create_children'   => 1,
                            'is_dynamic'         => 1,
                            'is_deleted'         => 0];

                        $np_src = $np->add($port_input, [], $cfg_ocs['history_network']);
                    }
                    if ($np_src) {
                        $link    = new NetworkPort_NetworkPort();
                        $np_dest = new NetworkPort();
                        $datas = $np_dest->find(['mac' => $mac_dest]);
                        if (count($datas) > 0) {
                            foreach ($datas as $data) {
                                $link->getFromDBByCrit(['networkports_id_1' => $np_src,
                                    'networkports_id_2' => $data['id']]);
                                if (count($link->fields) < 1) {
                                    $link_input = ['networkports_id_1' => $np_src,
                                        'networkports_id_2' => $data['id']];
                                    $link->add($link_input, [], $cfg_ocs['history_network']);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $id_network;
    }


    /**
     * @param        $mac
     * @param string $separator
     *
     * @return string
     */
    public static function addMacSeparator($mac, $separator = ':')
    {
        return join($separator, str_split($mac, 2));
    }

    /**
     * @param      $plugin_ocsinventoryng_ocsservers_id
     * @param      $itemtype
     * @param int  $ID
     * @param      $ocsSnmp
     * @param      $loc_id
     * @param      $action
     * @param bool $linked
     *
     * @param      $cfg_ocs
     * @return int
     * @throws \GlpitestSQLError
     */
    public static function addOrUpdateComputer($plugin_ocsinventoryng_ocsservers_id, $itemtype, $ID, $ocsSnmp, $loc_id, $action, $linked, $cfg_ocs)
    {
        global $DB;

        $snmpDevice = new $itemtype();
        $dbu = new DbUtils();
        $input = [
            "is_dynamic"  => 1,
            "entities_id" => ($_SESSION['glpiactive_entity'] ?? 0),
        ];

        if (($cfg_ocs['importsnmp_name'] && $action == "add")
          || ($cfg_ocs['linksnmp_name'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)) {
            $input["name"] = $ocsSnmp['META']['NAME'];
        }
        if (($cfg_ocs['importsnmp_contact'] && $action == "add")
          || ($cfg_ocs['linksnmp_contact'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)) {
            $input["contact"] = $ocsSnmp['META']['CONTACT'];
        }
        if (($cfg_ocs['importsnmp_comment'] && $action == "add")
          || ($cfg_ocs['linksnmp_comment'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)) {
            $input["comment"] = $ocsSnmp['META']['DESCRIPTION'];
        }

        if ($loc_id > 0) {
            $input["locations_id"] = $loc_id;
        }

        $id_item = 0;

        if ($action == "add") {
            $id_item = $snmpDevice->add($input, ['unicity_error_message' => true], $cfg_ocs['history_hardware']);
        } else {
            $input["id"] = $ID;
            $id_item     = $ID;
            if ($snmpDevice->getFromDB($id_item)) {
                $input["entities_id"] = $snmpDevice->fields['entities_id'];
            }
            $snmpDevice->update(
                $input,
                $cfg_ocs['history_hardware'],
                ['unicity_error_message' => false,
                    '_no_history'           => !$cfg_ocs['history_hardware']]
            );
        }

        if ($id_item > 0
          && isset($ocsSnmp['MEMORIES'])
          && (($cfg_ocs['importsnmp_computermemory'] && $action == "add")
              || ($cfg_ocs['linksnmp_computermemory'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_computermemory'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_computermemory'] && $linked))
          && count($ocsSnmp['MEMORIES']) > 0
          && $ocsSnmp['MEMORIES'][0]['CAPACITY'] > 0) {
            $dev['designation'] = __("Computer Memory", 'ocsinventoryng');

            $item   = new $itemtype();
            $entity = ($_SESSION['glpiactive_entity'] ?? 0);
            if ($item->getFromDB($id_item)) {
                $entity = $item->fields['entities_id'];
            }

            $dev['entities_id'] = $entity;

            $device    = new DeviceMemory();
            $device_id = $device->import($dev);
            if ($device_id) {
                $CompDevice = new Item_DeviceMemory();
                if ($cfg_ocs['history_devices']) {
                    $table = $dbu->getTableForItemType("Item_DeviceMemory");
                    $DB->delete($table, ['items_id' => (int) $id_item, 'itemtype' => $itemtype]);
                }
                //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
                //            $CompDevice->deleteByCriteria(array('items_id' => $id_item,
                //               'itemtype' => $itemtype), 1);
                $CompDevice->add(['items_id'          => $id_item,
                    'itemtype'          => $itemtype,
                    'size'              => $ocsSnmp['MEMORIES'][0]['CAPACITY'],
                    'entities_id'       => $entity,
                    'devicememories_id' => $device_id,
                    'is_dynamic'        => 1], [], $cfg_ocs['history_devices']);
            }
        }

        if ($id_item > 0
          && isset($ocsSnmp['NETWORKS'])
          && (($cfg_ocs['importsnmp_computernetworkcards'] && $action == "add")
              || ($cfg_ocs['linksnmp_computernetworkcards'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_computernetworkcards'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_computernetworkcards'] && $linked))
          && count($ocsSnmp['NETWORKS']) > 0) {
            $CompDevice = new Item_DeviceNetworkCard();
            if ($cfg_ocs['history_devices']) {
                $table = $dbu->getTableForItemType("Item_DeviceNetworkCard");
                $DB->delete($table, ['items_id' => (int) $id_item, 'itemtype' => $itemtype]);
            }
            //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
            //         $CompDevice->deleteByCriteria(array('items_id' => $id_item,
            //                                             'itemtype' => $itemtype), 1);

            foreach ($ocsSnmp['NETWORKS'] as $k => $net) {
                $dev["designation"] = $net['SLOT'];
                $dev["comment"]     = $net['TYPE'];
                $mac                = $net['MACADDR'];
                /*$speed = 0;
              if (strstr($processor['SPEED'], "GHz")) {
                 $speed = str_replace("GHz", "", $processor['SPEED']);
                 $speed = $speed * 1000;
              }
              if (strstr($processor['SPEED'], "MHz")) {
                 $speed = str_replace("MHz", "", $processor['SPEED']);
                }*/

                $item   = new $itemtype();
                $entity = ($_SESSION['glpiactive_entity'] ?? 0);
                if ($item->getFromDB($id_item)) {
                    $entity = $item->fields['entities_id'];
                }

                $dev['entities_id'] = $entity;

                $device    = new DeviceNetworkCard();
                $device_id = $device->import($dev);

                if ($device_id) {
                    $CompDevice->add(['items_id'              => $id_item,
                        'itemtype'              => $itemtype,
                        'mac'                   => $mac,
                        'entities_id'           => $entity,
                        'devicenetworkcards_id' => $device_id,
                        'is_dynamic'            => 1], [], $cfg_ocs['history_devices']);
                }
            }
        }

        if ($id_item > 0
          && isset($ocsSnmp['SOFTWARES'])
          && (($cfg_ocs['importsnmp_computersoftwares'] && $action == "add")
              || ($cfg_ocs['linksnmp_computersoftwares'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_computersoftwares'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_computersoftwares'] && $linked))
          && count($ocsSnmp['SOFTWARES']) > 0) {
            $entity = ($_SESSION['glpiactive_entity'] ?? 0);
            if ($item->getFromDB($id_item)) {
                $entity = $item->fields['entities_id'];
            }
            Software::updateSoftware($cfg_ocs, $id_item, $ocsSnmp, $entity, false, false);
        }
        if ($id_item > 0
          && isset($ocsSnmp['CPU'])
          && (($cfg_ocs['importsnmp_computerprocessors'] && $action == "add")
              || ($cfg_ocs['linksnmp_computerprocessors'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_computerprocessors'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_computerprocessors'] && $linked))
          && count($ocsSnmp['CPU']) > 0) {
            $CompDevice = new Item_DeviceProcessor();
            if ($cfg_ocs['history_devices']) {
                $table = $dbu->getTableForItemType("Item_DeviceProcessor");
                $DB->delete($table, ['items_id' => (int) $id_item, 'itemtype' => $itemtype]);
            }
            //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
            //         $CompDevice->deleteByCriteria(array('items_id' => $id_item,
            //                                             'itemtype' => $itemtype), 1);

            foreach ($ocsSnmp['CPU'] as $k => $processor) {
                $dev["designation"]      = $processor['TYPE'];
                $dev["manufacturers_id"] = Dropdown::importExternal(
                    'Manufacturer',
                    OcsProcess::encodeOcsDataInUtf8(
                        $cfg_ocs['ocs_db_utf8'],
                        $processor['MANUFACTURER']
                    )
                );
                $speed                   = 0;
                if (strstr($processor['SPEED'], "GHz")) {
                    $speed = str_replace("GHz", "", $processor['SPEED']);
                    $speed = $speed * 1000;
                }
                if (strstr($processor['SPEED'], "MHz")) {
                    $speed = str_replace("MHz", "", $processor['SPEED']);
                }

                $item   = new $itemtype();
                $entity = ($_SESSION['glpiactive_entity'] ?? 0);
                if ($item->getFromDB($id_item)) {
                    $entity = $item->fields['entities_id'];
                }

                $dev['entities_id'] = $entity;

                $device    = new DeviceProcessor();
                $device_id = $device->import($dev);

                if ($device_id) {
                    $CompDevice->add(['items_id'            => $id_item,
                        'itemtype'            => $itemtype,
                        'frequency'           => $speed,
                        'entities_id'         => $entity,
                        'deviceprocessors_id' => $device_id,
                        'is_dynamic'          => 1], [], $cfg_ocs['history_devices']);
                }
            }
        }

        if ($id_item > 0
          && isset($ocsSnmp['VIRTUALMACHINES'])
          && (($cfg_ocs['importsnmp_computervm'] && $action == "add")
              || ($cfg_ocs['linksnmp_computervm'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_computervm'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_computervm'] && $linked))
          && count($ocsSnmp['VIRTUALMACHINES']) > 0) {
            $already_processed = [];

            $virtualmachine = new ComputerVirtualMachine();
            foreach ($ocsSnmp['VIRTUALMACHINES'] as $k => $ocsVirtualmachine) {
                $vm                 = [];
                $vm['name']         = $ocsVirtualmachine['NAME'];
                $vm['vcpu']         = $ocsVirtualmachine['CPU'];
                $vm['ram']          = $ocsVirtualmachine['MEMORY'];
                $vm['uuid']         = $ocsVirtualmachine['UUID'];
                $vm['itemtype']     = Computer::class;
                $vm['items_id']     = $id_item;
                $vm['is_dynamic']   = 1;

                $vm['virtualmachinestates_id'] = Dropdown::importExternal('VirtualMachineState', $ocsVirtualmachine['POWER']);
                //$vm['virtualmachinetypes_id'] = Dropdown::importExternal('VirtualMachineType', $ocsVirtualmachine['VMTYPE']);
                //$vm['virtualmachinesystems_id'] = Dropdown::importExternal('VirtualMachineType', $ocsVirtualmachine['SUBSYSTEM']);

                // UUID and name are sent by the OCS agent: bound, never concatenated
                $existing = $DB->request([
                    'SELECT' => 'id',
                    'FROM'   => 'glpi_itemvirtualmachines',
                    'WHERE'  => [
                        'items_id'   => (int) $id_item,
                        'itemtype'   => Computer::class,
                        'is_dynamic' => 1,
                    ] + ($ocsVirtualmachine['UUID']
                        ? ['uuid' => (string) $ocsVirtualmachine['UUID']]
                        // Failback on name
                        : ['name' => (string) $ocsVirtualmachine['NAME']]),
                    'LIMIT'  => 1,
                ])->current();
                $id = $existing ? (int) $existing['id'] : 0;
                if (!$id) {
                    $virtualmachine->reset();
                    $id_vm = $virtualmachine->add($vm, [], $cfg_ocs['history_vm']);
                    if ($id_vm) {
                        $already_processed[] = $id_vm;
                    }
                } else {
                    if ($virtualmachine->getFromDB($id)) {
                        $vm['id'] = $id;
                        $virtualmachine->update($vm, $cfg_ocs['history_vm']);
                    }
                    $already_processed[] = $id;
                }
                // Delete Unexisting Items not found in OCS
                //Look for all ununsed virtual machines
                $criteria = [
                    'SELECT' => 'id',
                    'FROM' => 'glpi_itemvirtualmachines',
                    'WHERE' => [
                        'items_id'   => (int) $id_item,
                        'itemtype'   => Computer::class,
                        'is_dynamic' => 1,
                    ],
                ];
                if (!empty($already_processed)) {
                    $criteria['WHERE'] = $criteria['WHERE'] + ['id' => ['NOT IN', $already_processed]];
                }
                $iterator = $DB->request($criteria);
                foreach ($iterator as $data) {
                    //Delete all connexions
                    $virtualmachine->delete(['id'             => $data['id'],
                        '_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                        '_no_history'    => !$cfg_ocs['history_vm']], true, $cfg_ocs['history_vm']);
                }
            }
        }

        if ($id_item > 0
          && (($cfg_ocs['importsnmp_createport'] && $action == "add")
              || ($cfg_ocs['linksnmp_createport'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_createport'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_createport'] && $linked))) {
            //Delete Existing network config
            foreach ($DB->request([
                'SELECT' => 'id',
                'FROM' => 'glpi_networkports',
                'WHERE' => ['items_id' => $id_item,
                    'itemtype' => $itemtype]]) as $networkPortID) {

                foreach ($DB->request([
                    'SELECT' => 'id',
                    'FROM' => 'glpi_networkports',
                    'WHERE' => ['items_id' => $networkPortID['id'],
                        'itemtype' => 'NetworkPort']]) as $networkNameID) {
                    $ipAddress = new IPAddress();
                    $ipAddress->deleteByCriteria(['items_id' => $networkNameID['id'],
                        'itemtype' => 'NetworkName'], 1);
                }

                $nn = new NetworkName();
                $nn->deleteByCriteria(['items_id' => $networkPortID['id'],
                    'itemtype' => 'NetworkPort'], 1);
            }
            $np = new NetworkPort();
            $np->deleteByCriteria(['items_id' => $id_item,
                'itemtype' => $itemtype], 1);

            //Add network port
            $ip  = $ocsSnmp['META']['IPADDR'];
            $mac = $ocsSnmp['META']['MACADDR'];

            $np = new NetworkPort();
            $data = $np->find(['mac' => $mac, 'items_id' => $id_item, 'itemtype' => $itemtype]);
            if (count($data) < 1) {
                $item   = new $itemtype();
                $entity = ($_SESSION['glpiactive_entity'] ?? 0);
                if ($item->getFromDB($id_item)) {
                    $entity = $item->fields['entities_id'];
                }
                $port_input = ['name'                     => $ocsSnmp['META']['NAME'],
                    'mac'                      => $mac,
                    'items_id'                 => $id_item,
                    'itemtype'                 => $itemtype,
                    'instantiation_type'       => "NetworkPortEthernet",
                    "entities_id"              => $entity,
                    "NetworkName__ipaddresses" => ["-100" => $ip],
                    '_create_children'         => 1,
                    //'is_dynamic'         => 1,
                    'is_deleted'               => 0];

                $np->add($port_input, [], $cfg_ocs['history_network']);
            }
        }

        $computerDisk = new Item_Disk();
        if ($id_item > 0
          && isset($ocsSnmp['COMPUTERDISKS'])
          && (($cfg_ocs['importsnmp_computerdisks'] && $action == "add")
              || ($cfg_ocs['linksnmp_computerdisks'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_computerdisks'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_computerdisks'] && $linked))
          && count($ocsSnmp['COMPUTERDISKS']) > 0) {
            $already_processed = [];

            foreach ($ocsSnmp['COMPUTERDISKS'] as $k => $ocsComputerDisks) {
                $disk                   = [];
                $disk['itemtype']       = Computer::class;
                $disk['items_id']       = $id_item;
                $disk['name']           = $ocsComputerDisks['FILESYSTEM'];
                $disk['mountpoint']     = $ocsComputerDisks['VOLUMN'];
                $disk['device']         = $ocsComputerDisks['TYPE'];
                $disk['totalsize']      = $ocsComputerDisks['TOTAL'];
                $disk['freesize']       = $ocsComputerDisks['FREE'];
                $disk['filesystems_id'] = Dropdown::importExternal('Filesystem', $ocsComputerDisks["FILESYSTEM"]);
                $disk['is_dynamic']     = 1;

                // Ok import disk
                if (isset($disk['name']) && !empty($disk["name"])) {
                    $disk['totalsize'] = $ocsComputerDisks['TOTAL'];
                    $disk['freesize']  = $ocsComputerDisks['FREE'];

                    // The name of the file system is sent by the OCS agent: bound
                    $existing = $DB->request([
                        'SELECT' => 'id',
                        'FROM'   => 'glpi_items_disks',
                        'WHERE'  => [
                            'items_id'   => (int) $id_item,
                            'itemtype'   => Computer::class,
                            'name'       => (string) $disk['name'],
                            'is_dynamic' => 1,
                        ],
                    ]);
                    $id = count($existing) == 1 ? (int) $existing->current()['id'] : false;

                    if (!$id) {
                        $computerDisk->reset();
                        $disk['is_dynamic']  = 1;
                        $id_disk             = $computerDisk->add($disk, [], $cfg_ocs['history_drives']);
                        $already_processed[] = $id_disk;
                    } else {
                        // Only update if needed
                        if ($computerDisk->getFromDB($id)) {
                            // Update on type, total size change or variation of 5%
                            if ($computerDisk->fields['totalsize'] != $disk['totalsize']
                            || ($computerDisk->fields['filesystems_id'] != $disk['filesystems_id'])
                            || ((abs($disk['freesize'] - $computerDisk->fields['freesize']) / $disk['totalsize']) > 0.05)) {
                                $toupdate['id']             = $id;
                                $toupdate['totalsize']      = $disk['totalsize'];
                                $toupdate['freesize']       = $disk['freesize'];
                                $toupdate['filesystems_id'] = $disk['filesystems_id'];
                                $computerDisk->update($toupdate, $cfg_ocs['history_drives']);
                            }
                            $already_processed[] = $id;
                        }
                    }
                }
            }
        }

        // Delete Unexisting Items not found in OCS
        //Look for all ununsed disks
        $criteria = [
            'SELECT' => 'id',
            'FROM' => 'glpi_items_disks',
            'WHERE' => [
                'items_id' => $id_item,
                'itemtype' => 'Computer',
                'is_dynamic' => 1,
            ],
        ];
        if (!empty($already_processed)) {
            $criteria['WHERE'] = $criteria['WHERE'] + ['id' => ['NOT IN', $already_processed]];
        }
        $iterator = $DB->request($criteria);
        foreach ($iterator as $data) {
            //Delete all connexions
            $computerDisk->delete(
                ['id'             => $data['id'],
                    '_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                    '_no_history'    => !$cfg_ocs['history_drives']],
                true,
                $cfg_ocs['history_drives']
            );
        }

        return $id_item;
    }

    /**
     * @param      $itemtype
     * @param int  $ID
     * @param      $ocsSnmp
     * @param      $loc_id
     * @param      $action
     * @param bool $linked
     *
     * @param      $cfg_ocs
     * @return int
     */
    public static function addOrUpdateOther($itemtype, $ID, $ocsSnmp, $loc_id, $action, $linked, $cfg_ocs)
    {
        global $DB;

        $snmpDevice = new $itemtype();

        $input = [
            "is_dynamic"  => 1,
            "entities_id" => ($_SESSION['glpiactive_entity'] ?? 0),
        ];

        if (($cfg_ocs['importsnmp_name'] && $action == "add")
          || ($cfg_ocs['linksnmp_name'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)) {
            $input["name"] = $ocsSnmp['META']['NAME'];
        }
        if (($cfg_ocs['importsnmp_contact'] && $action == "add")
          || ($cfg_ocs['linksnmp_contact'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)) {
            $input["contact"] = $ocsSnmp['META']['CONTACT'];
        }
        if (($cfg_ocs['importsnmp_comment'] && $action == "add")
          || ($cfg_ocs['linksnmp_comment'] && $linked)
          || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
          || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)) {
            $input["comment"] = $ocsSnmp['META']['DESCRIPTION'];
        }

        if ($loc_id > 0) {
            $input["locations_id"] = $loc_id;
        }

        $id_item = 0;

        if ($action == "add") {
            $id_item = $snmpDevice->add($input, ['unicity_error_message' => true], $cfg_ocs['history_hardware']);
        } else {
            $input["id"] = $ID;
            $id_item     = $ID;
            if ($snmpDevice->getFromDB($id_item)) {
                $input["entities_id"] = $snmpDevice->fields['entities_id'];
            }

            $snmpDevice->update($input, $cfg_ocs['history_hardware'], ['unicity_error_message' => false,
                '_no_history'           => !$cfg_ocs['history_hardware']]);
        }

        if ($id_item > 0
          && (($cfg_ocs['importsnmp_createport'] && $action == "add")
              || ($cfg_ocs['linksnmp_createport'] && $linked)
              || ($action == "update" && $cfg_ocs['importsnmp_createport'] && !$linked)
              || ($action == "update" && $cfg_ocs['linksnmp_createport'] && $linked))) {
            //Delete Existing network config
            foreach ($DB->request([
                'SELECT' => 'id',
                'FROM' => 'glpi_networkports',
                'WHERE' => ['items_id' => $id_item,
                    'itemtype' => $itemtype]]) as $networkPortID) {

                foreach ($DB->request([
                    'SELECT' => 'id',
                    'FROM' => 'glpi_networkports',
                    'WHERE' => ['items_id' => $networkPortID['id'],
                        'itemtype' => 'NetworkPort']]) as $networkNameID) {
                    $ipAddress = new IPAddress();
                    $ipAddress->deleteByCriteria(['items_id' => $networkNameID['id'],
                        'itemtype' => 'NetworkName'], 1);
                }

                $nn = new NetworkName();
                $nn->deleteByCriteria(['items_id' => $networkPortID['id'],
                    'itemtype' => 'NetworkPort'], 1);
            }
            $np = new NetworkPort();
            $np->deleteByCriteria(['items_id' => $id_item,
                'itemtype' => $itemtype], 1);

            //Add network port
            $ip  = $ocsSnmp['META']['IPADDR'];
            $mac = $ocsSnmp['META']['MACADDR'];

            $np = new NetworkPort();
            $data = $np->find(['mac' => $mac, 'items_id' => $id_item, 'itemtype' => $itemtype]);
            if (count($data) < 1) {
                $item   = new $itemtype();
                $entity = ($_SESSION['glpiactive_entity'] ?? 0);
                if ($item->getFromDB($id_item)) {
                    $entity = $item->fields['entities_id'];
                }
                $port_input = ['name'                     => $ocsSnmp['META']['NAME'],
                    'mac'                      => $mac,
                    'items_id'                 => $id_item,
                    'itemtype'                 => $itemtype,
                    'instantiation_type'       => "NetworkPortEthernet",
                    "entities_id"              => $entity,
                    "NetworkName__ipaddresses" => ["-100" => $ip],
                    '_create_children'         => 1,
                    //'is_dynamic'         => 1,
                    'is_deleted'               => 0];

                $np->add($port_input, [], $cfg_ocs['history_network']);
            }
        }

        return $id_item;
    }

    /**
     * @param $ID
     * @param $plugin_ocsinventoryng_ocsservers_id
     *
     * @return array
     */
    public static function updateSnmp($ID, $plugin_ocsinventoryng_ocsservers_id)
    {
        global $DB;

        $plugin_ocsinventoryng_ocsservers_id = (int) $plugin_ocsinventoryng_ocsservers_id;
        $data = $DB->request([
            'FROM'  => 'glpi_plugin_ocsinventoryng_snmpocslinks',
            'WHERE' => [
                'id'                                  => (int) $ID,
                'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
            ],
            'LIMIT' => 1,
        ])->current();
        if (!$data) {
            return false;
        }
        $ocsid    = $data['ocs_id'];
        $itemtype = $data['itemtype'];
        $items_id = $data['items_id'];
        $linked   = $data['linked'];

        $ocsClient = OcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
        $ocsSnmp   = $ocsClient->getSnmpDevice($ocsid);

        $cfg_ocs = OcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);

        $loc_id = 0;

        if (($cfg_ocs['importsnmp_location'] && $linked == 0)
          || ($cfg_ocs['linksnmp_location'] && $linked)) {
            $loc_id = Dropdown::importExternal(
                'Location',
                OcsProcess::encodeOcsDataInUtf8(
                    $cfg_ocs['ocs_db_utf8'],
                    $ocsSnmp['META']['LOCATION']
                )
            );
        }

        if ($itemtype == "Printer") {
            self::addOrUpdatePrinter(
                $itemtype,
                $items_id,
                $ocsSnmp,
                $loc_id,
                "update",
                $linked,
                $cfg_ocs
            );

            $now = date("Y-m-d H:i:s");
            $DB->update('glpi_plugin_ocsinventoryng_snmpocslinks', ['last_update' => $now], ['id' => (int) $ID]);

            return ['status' => OcsProcess::SNMP_SYNCHRONIZED,
                //'entities_id'  => $data['entities_id'],
            ];
        } elseif ($itemtype == "NetworkEquipment") {
            self::addOrUpdateNetworkEquipment(
                $itemtype,
                $items_id,
                $ocsSnmp,
                $loc_id,
                "update",
                $linked,
                $cfg_ocs
            );

            $now = date("Y-m-d H:i:s");
            $DB->update('glpi_plugin_ocsinventoryng_snmpocslinks', ['last_update' => $now], ['id' => (int) $ID]);

            return ['status' => OcsProcess::SNMP_SYNCHRONIZED,
                //'entities_id'  => $data['entities_id'],
            ];
        } elseif ($itemtype == "Computer") {
            self::addOrUpdateComputer(
                $plugin_ocsinventoryng_ocsservers_id,
                $itemtype,
                $items_id,
                $ocsSnmp,
                $loc_id,
                "update",
                $linked,
                $cfg_ocs
            );

            $now = date("Y-m-d H:i:s");
            $DB->update('glpi_plugin_ocsinventoryng_snmpocslinks', ['last_update' => $now], ['id' => (int) $ID]);

            return ['status' => OcsProcess::SNMP_SYNCHRONIZED,
                //'entities_id'  => $data['entities_id'],
            ];
        } elseif ($itemtype == "Peripheral"
                 || $itemtype == "Phone"
        ) {
            self::addOrUpdateOther($itemtype, $items_id, $ocsSnmp, $loc_id, "update", $linked, $cfg_ocs);

            $now = date("Y-m-d H:i:s");
            $DB->update('glpi_plugin_ocsinventoryng_snmpocslinks', ['last_update' => $now], ['id' => (int) $ID]);

            return ['status' => OcsProcess::SNMP_SYNCHRONIZED,
                //'entities_id'  => $data['entities_id'],
            ];
        }

        return ['status' => OcsProcess::SNMP_NOTUPDATED,
            //'entities_id'  => $data['entities_id'],
        ];
    }

    /**
     * Prints search form
     *
     * @param $params
     *
     * @return void
     * @internal param the $manufacturer supplier choice
     * @internal param the $type device type
     */
    public static function searchForm($params)
    {
        $itemtypes = [];
        foreach (self::$snmptypes as $itemtype) {
            $itemtypes[$itemtype] = $itemtype::getTypeName(1);
        }

        TemplateRenderer::getInstance()->display('@ocsinventoryng/snmp_filter.html.twig', [
            'form_url'  => PLUGIN_OCS_WEBDIR . (($params['tolinked'] ?? 0) > 0 ? '/front/ocsngsnmp.link.php' : '/front/ocsngsnmp.import.php'),
            'itemtypes' => $itemtypes,
            // Values of the request: escaped by the template
            'itemtype'  => (string) ($params['itemtype'] ?? ''),
            'ip'        => (string) ($params['ip'] ?? ''),
        ]);
    }

    // Show snmp devices to add :)

    /**
     * @param $params
     */
    public static function showSnmpDeviceToAdd($params)
    {
        global $DB;

        $p = array_merge([
            'start'                               => 0,
            'itemtype'                            => '',
            'ip'                                  => '',
            'tolinked'                            => 0,
            'check'                               => 'all',
            'plugin_ocsinventoryng_ocsservers_id' => 0,
        ], $params);

        $tolinked                            = $p['tolinked'];
        $start                               = max(0, (int) $p['start']);
        $plugin_ocsinventoryng_ocsservers_id = (int) $p['plugin_ocsinventoryng_ocsservers_id'];

        $title  = $tolinked
            ? __('Import new SNMP devices into glpi', 'ocsinventoryng')
            : __('Import new SNMP devices', 'ocsinventoryng');
        $target = PLUGIN_OCS_WEBDIR . ($tolinked ? '/front/ocsngsnmp.link.php' : '/front/ocsngsnmp.import.php');

        // Get all links between glpi and OCS
        $already_linked = [];
        foreach ($DB->request([
            'SELECT' => 'ocs_id',
            'FROM'   => 'glpi_plugin_ocsinventoryng_snmpocslinks',
            'WHERE'  => ['plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id],
        ]) as $data) {
            $already_linked[] = $data["ocs_id"];
        }

        $ocsResult = OcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id)->getSnmp([
            'FILTER'  => [
                'EXCLUDE_IDS' => $already_linked,
            ],
            'DISPLAY' => [
                'CHECKSUM' => OcsClient::CHECKSUM_BIOS,
            ],
            'ORDER'   => 'NAME',
        ]);
        $numrows = (int) ($ocsResult['TOTAL_COUNT'] ?? 0);
        $limit   = (int) $_SESSION['glpilist_limit'];

        $rows = [];
        foreach (array_slice($ocsResult['SNMP'] ?? [], $start, $limit) as $data) {
            $meta = $data['META'];
            $type = $meta["TYPE"] == "Network" ? "NetworkEquipment" : $meta["TYPE"];
            if ((!empty($p['itemtype']) && $type != $p['itemtype'])
                // A text searched in the address, never a regular expression from the request
                || (!empty($p['ip']) && !str_contains((string) $meta['IPADDR'], (string) $p['ip']))) {
                continue;
            }

            // Values reported by the SNMP device: escaped by the template
            $row = [
                '_checkbox'   => (int) $meta["ID"],
                'name'        => (string) $meta["NAME"],
                'description' => (string) $meta["DESCRIPTION"],
                'ipaddr'      => (string) $meta["IPADDR"],
                'mac'         => (string) $meta["MACADDR"],
                'date'        => Html::convDateTime($meta["LASTDATE"]),
                'contact'     => (string) $meta["CONTACT"],
                'location'    => (string) $meta["LOCATION"],
                'type'        => (string) $type,
            ];
            // The selection of the line: HTML of the dropdowns of GLPI
            ob_start();
            if (!$tolinked) {
                Dropdown::showItemTypes(
                    "toimport_itemtype[" . (int) $meta["ID"] . "]",
                    self::$snmptypes,
                    ['value' => getItemForItemtype($type) ? $type : false]
                );
            } else {
                self::showLinkDropdown((int) $meta["ID"], (string) $type, (string) $meta["NAME"]);
            }
            $row['select'] = ob_get_clean();
            $rows[] = $row;
        }

        TemplateRenderer::getInstance()->display('@ocsinventoryng/table.html.twig', [
            'title'         => $title,
            'icon'          => 'ti ti-router',
            'alert'         => $tolinked && count($rows)
                ? [__('Caution! The imported data (see your configuration) will overwrite the existing one', 'ocsinventoryng')]
                : null,
            'columns'       => [
                'name'        => __('Name'),
                'description' => __('Description'),
                'ipaddr'      => __('IP address'),
                'mac'         => __('MAC address'),
                'date'        => __('Date'),
                'contact'     => __('Contact SNMP', 'ocsinventoryng'),
                'location'    => __('Location SNMP', 'ocsinventoryng'),
                'type'        => __('Type SNMP', 'ocsinventoryng'),
                'select'      => $tolinked ? __('Item to link', 'ocsinventoryng') : __('Item type to create', 'ocsinventoryng'),
            ],
            'raw_columns'   => ['select'],
            'rows'          => $rows,
            'empty_message' => __('No new SNMP device to be imported', 'ocsinventoryng'),
            'form_action'   => $target,
            // Import: the lines to import are checked; link: the item chosen on each line
            'checkbox_name' => $tolinked ? null : 'toimport',
            'checked_all'   => $p['check'] == "all",
            'hidden'        => ['plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id],
            'buttons'       => [
                [
                    'name'  => 'import_ok',
                    'title' => $tolinked ? _sx('button', 'Link', 'ocsinventoryng') : _sx('button', 'Import'),
                    'icon'  => $tolinked ? 'ti-link' : 'ti-download',
                ],
            ],
            'pager'         => $numrows > $limit ? [
                'start' => $start,
                'count' => $numrows,
                'limit' => $limit,
                'href'  => $target,
            ] : null,
        ]);
    }

    /**
     * The dropdown of the GLPI item to link to an SNMP device (link list)
     *
     * @param int    $ocs_id the SNMP device
     * @param string $type   its type reported by OCS
     * @param string $name   its name reported by OCS
     *
     * @return void
     */
    private static function showLinkDropdown(int $ocs_id, string $type, string $name)
    {
        global $DB;

        if (in_array($type, self::$snmptypes, true) && getItemForItemtype($type)) {
            $options = ['name' => "tolink_items[" . $ocs_id . "]", 'used' => []];

            // The item of the same name, unless it is already linked
            $self = new self();
            $item = $self->getFromDBbyName($type, $name);
            $ido  = (int) ($item->fields['id'] ?? 0);
            if ($ido > 0) {
                $linked = $DB->request([
                    'FROM'  => 'glpi_plugin_ocsinventoryng_snmpocslinks',
                    'WHERE' => ['itemtype' => $type, 'items_id' => $ido],
                    'LIMIT' => 1,
                ]);
                if (count($linked) > 0) {
                    $options['used'][] = $ido;
                } else {
                    $options['value'] = $ido;
                }
            }

            $type::dropdown($options);
            echo Html::hidden('tolink_itemtype[' . $ocs_id . ']', ['value' => $type]);
            return;
        }

        // An unknown type: chosen by the user, then its items (ajax/dropdownitems.php)
        $mynamei = "itemtype";
        $rand    = Dropdown::showItemTypes($mynamei, self::$snmptypes, ['rand' => mt_rand()]);
        Ajax::updateItemOnSelectEvent(
            "dropdown_$mynamei$rand",
            "results_$mynamei$rand",
            PLUGIN_OCS_WEBDIR . "/ajax/dropdownitems.php",
            ['itemtype' => '__VALUE__', 'id' => $ocs_id, 'rand' => $rand, 'myname' => "tolink_items[" . $ocs_id . "]"]
        );
        echo "<span id='results_$mynamei$rand'></span>";
    }

    /**
     * @param $itemtype
     * @param $name
     *
     * @return itemtype
     */
    public function getFromDBbyName($itemtype, $name)
    {
        $dbu = new DbUtils();
        $item  = $dbu->getItemForItemtype($itemtype);
        $field = "`" . $dbu->getTableForItemType($itemtype) . "`.`name`";
        $item->getFromDBByCrit([$field => $name]);
        return $item;
    }

    /**
     * @param $plugin_ocsinventoryng_ocsservers_id
     * @param $check
     * @param $start
     *
     * @return bool|void
     */
    public static function showSnmpDeviceToUpdate($plugin_ocsinventoryng_ocsservers_id, $check, $start)
    {
        global $DB;

        if (!OcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)) {
            return false;
        }
        if (!Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
            return false;
        }

        $start  = max(0, (int) $start);
        $limit  = (int) $_SESSION['glpilist_limit'];
        $target = PLUGIN_OCS_WEBDIR . '/front/ocsngsnmp.sync.php';

        // Get linked SNMP device ids in GLPI
        $already_linked_ids = [];
        foreach ($DB->request([
            'SELECT' => 'ocs_id AS ocsid',
            'FROM'   => 'glpi_plugin_ocsinventoryng_snmpocslinks',
            'WHERE'  => ['plugin_ocsinventoryng_ocsservers_id' => (int) $plugin_ocsinventoryng_ocsservers_id],
        ]) as $data) {
            $already_linked_ids[] = $data['ocsid'];
        }

        // Fetch the linked devices from OCS
        $ocsResult = [];
        if (count($already_linked_ids) > 0) {
            $ocsResult = OcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id)->getSnmp([
                'ORDER'  => 'LASTDATE',
                'FILTER' => [
                    'IDS' => $already_linked_ids,
                ],
            ]);
        }
        $numrows = (int) ($ocsResult['TOTAL_COUNT'] ?? 0);

        // Name and last inventory date in OCS of the devices of the page
        $ocs_snmp = [];
        foreach (array_slice($ocsResult['SNMP'] ?? [], $start, $limit) as $snmp) {
            $ocs_snmp[$snmp['META']['ID']] = $snmp['META'];
        }

        $rows = [];
        if (count($ocs_snmp) > 0) {
            foreach ($DB->request([
                'FROM'  => 'glpi_plugin_ocsinventoryng_snmpocslinks',
                'WHERE' => ['ocs_id' => array_map('intval', array_keys($ocs_snmp))],
                'ORDER' => 'last_update DESC',
            ]) as $tab) {
                if (!($item = getItemForItemtype($tab["itemtype"])) || !$item->getFromDB($tab["items_id"])) {
                    continue;
                }
                $rows[] = [
                    '_checkbox'   => (int) $tab["id"],
                    // Link escaped by the core
                    'item'        => $item->getLink(),
                    'itemtype'    => $item->getTypeName(1),
                    // Reported by the SNMP device: escaped by the template
                    'ocs_name'    => (string) ($ocs_snmp[$tab["ocs_id"]]['NAME'] ?? ''),
                    'last_update' => Html::convDateTime($tab["last_update"]),
                    'ocs_date'    => Html::convDateTime($ocs_snmp[$tab["ocs_id"]]['LASTDATE'] ?? null),
                ];
            }
        }

        TemplateRenderer::getInstance()->display('@ocsinventoryng/table.html.twig', [
            'title'         => __('Snmp device updated in OCSNG', 'ocsinventoryng'),
            'icon'          => 'ti ti-refresh',
            'columns'       => [
                'item'        => __('GLPI Object', 'ocsinventoryng'),
                'itemtype'    => __('Item type'),
                'ocs_name'    => __('OCS SNMP device', 'ocsinventoryng'),
                'last_update' => __('Import date in GLPI', 'ocsinventoryng'),
                'ocs_date'    => __('Last OCSNG SNMP inventory date', 'ocsinventoryng'),
            ],
            'raw_columns'   => ['item'],
            'rows'          => $rows,
            'empty_message' => __('No new SNMP device to be updated', 'ocsinventoryng'),
            'form_action'   => $target,
            'checkbox_name' => 'toupdate',
            'checked_all'   => $check == "all",
            'hidden'        => ['plugin_ocsinventoryng_ocsservers_id' => (int) $plugin_ocsinventoryng_ocsservers_id],
            'buttons'       => [
                [
                    'name'  => 'update_ok',
                    'title' => _sx('button', 'Synchronize'),
                    'icon'  => 'ti-refresh',
                ],
                [
                    'name'    => 'delete',
                    'title'   => _sx('button', 'Delete link', 'ocsinventoryng'),
                    'icon'    => 'ti-unlink',
                    'class'   => 'btn-outline-danger',
                    'confirm' => __('Confirm the final deletion?'),
                ],
            ],
            'pager'         => $numrows > $limit ? [
                'start'             => $start,
                'count'             => $numrows,
                'limit'             => $limit,
                'href'              => $target,
                'additional_params' => 'check=' . urlencode((string) $check),
            ] : null,
        ]);
    }

    /**
     * Make the item link between glpi and ocs.
     *
     * This make the database link between ocs and glpi databases
     *
     * @param $ocsid integer : ocs item unique id.
     * @param $plugin_ocsinventoryng_ocsservers_id integer : ocs server id
     * @param $items_id
     * @param $itemtype
     * return int : link id.
     *
     * @internal param int $glpi_computers_id : glpi computer id
     * @return bool|item
     */
    public static function ocsSnmpLink($ocsid, $plugin_ocsinventoryng_ocsservers_id, $items_id, $itemtype)
    {
        global $DB;

        $ocsid                               = (int) $ocsid;
        $plugin_ocsinventoryng_ocsservers_id = (int) $plugin_ocsinventoryng_ocsservers_id;
        $items_id                            = (int) $items_id;

        // One of the SNMP types, an item the user may update: the synchronisation overwrites it
        if (!in_array($itemtype, self::$snmptypes, true)
            || !($item = getItemForItemtype($itemtype))
            || !$item->can($items_id, UPDATE)) {
            return false;
        }

        $ocsClient = OcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);

        $ocsSnmp = $ocsClient->getSnmpDevice($ocsid);

        if (is_null($ocsSnmp)) {
            return false;
        }

        $result = $DB->insert('glpi_plugin_ocsinventoryng_snmpocslinks', [
            'items_id'                            => $items_id,
            'ocs_id'                              => $ocsid,
            'itemtype'                            => $itemtype,
            'last_update'                         => $_SESSION["glpi_currenttime"],
            'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
            'linked'                              => 1,
        ]);

        if ($result) {
            return ($DB->insertId());
        }

        return false;
    }

    /**
     * @param $ocsid
     * @param $plugin_ocsinventoryng_ocsservers_id
     * @param $params
     *
     * @return array|bool
     * @internal param $computers_id
     *
     */
    public static function linkSnmpDevice($ocsid, $plugin_ocsinventoryng_ocsservers_id, $params)
    {
        //TODO enlever pour en conserver 1 seul
        if (!OcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)) {
            return false;
        }
        $ocsClient = OcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
        //TODOSNMP entites_id ?

        $p['itemtype'] = -1;
        $p['items_id'] = -1;
        foreach ($params as $key => $val) {
            $p[$key] = $val;
        }

        $ocs_id_change = true;

        if (//$ocs_id_change
            //||
            $p['itemtype'] != -1 && $p['items_id'] > 0 &&
            ($idlink = self::ocsSnmpLink(
                $ocsid,
                $plugin_ocsinventoryng_ocsservers_id,
                $p['items_id'],
                $p['itemtype']
            ))) {
            /*
             // automatic transfer computer
             if (($CFG_GLPI['transfers_id_auto'] > 0)
             && Session::isMultiEntitiesMode()) {

             // Retrieve data from glpi_plugin_ocsinventoryng_ocslinks
             $ocsLink = new Ocslink();
             $ocsLink->getFromDB($idlink);

             if (count($ocsLink->fields)) {
             // Retrieve datas from OCS database
             $ocsComputer = $ocsClient->getComputer($ocsLink->fields['ocsid']);

             if (!is_null($ocsComputer)) {
             OcsServer::transferComputer($ocsLink->fields, $ocsComputer);
             }
             }
             }
             $comp = new Computer();
             $comp->getFromDB($computers_id);
             $input["id"]            = $computers_id;
             $input["entities_id"]   = $comp->fields['entities_id'];
             $input["is_dynamic"]    = 1;
             $input["_nolock"]       = true;

             //for rule asset
             $input['_auto']      = 1;
             $comp->update($input);
             // Auto restore if deleted
             if ($comp->fields['is_deleted']) {
             $comp->restore(array('id' => $computers_id));
             }

             // Reset only if not in ocs id change case
             if (!$ocs_id_change) {
             if ($ocsConfig["import_general_os"]) {
             OcsServer::resetDropdown($computers_id, "operatingsystems_id", "glpi_operatingsystems");
             }
             if ($ocsConfig["import_device_processor"]) {
             OcsServer::resetDevices($computers_id, 'DeviceProcessor');
             }
             if ($ocsConfig["import_device_iface"]) {
             OcsServer::resetDevices($computers_id, 'DeviceNetworkCard');
             }
             if ($ocsConfig["import_device_memory"]) {
             OcsServer::resetDevices($computers_id, 'DeviceMemory');
             }
             if ($ocsConfig["import_device_hdd"]) {
             OcsServer::resetDevices($computers_id, 'DeviceHardDrive');
             }
             if ($ocsConfig["import_device_sound"]) {
             OcsServer::resetDevices($computers_id, 'DeviceSoundCard');
             }
             if ($ocsConfig["import_device_gfxcard"]) {
             OcsServer::resetDevices($computers_id, 'DeviceGraphicCard');
             }
             if ($ocsConfig["import_device_drive"]) {
             OcsServer::resetDevices($computers_id, 'DeviceDrive');
             }
             if ($ocsConfig["import_device_modem"] || $ocsConfig["import_device_port"]) {
             OcsServer::resetDevices($computers_id, 'DevicePci');
             }
             if ($ocsConfig["import_device_bios"]) {
             OcsServer::resetDevices($computers_id, 'DeviceBiosdata');
             }
             if ($ocsConfig["import_device_motherboard"]) {
             OcsServer::resetDevices($computers_id, 'DeviceMotherboard');
             }
             if ($ocsConfig["import_software"]) {
             OcsServer::resetSoftwares($computers_id);
             }
             if ($ocsConfig["import_disk"]) {
             OcsServer::resetDisks($computers_id);
             }
             if ($ocsConfig["import_periph"]) {
             OcsServer::resetPeripherals($computers_id);
             }
             if ($ocsConfig["import_monitor"]==1) { // Only reset monitor as global in unit management
             OcsServer::resetMonitors($computers_id);    // try to link monitor with existing
             }
             if ($ocsConfig["import_printer"]) {
             OcsServer::resetPrinters($computers_id);
             }
             if ($ocsConfig["import_registry"]) {
             OcsServer::resetRegistry($computers_id);
             }
             $changes[0] = '0';
             $changes[1] = "";
             $changes[2] = $ocsid;
             Ocslink::history($computers_id, $changes,
             Ocslink::HISTORY_OCS_LINK);
             }
             */
            self::updateSnmp($idlink, $plugin_ocsinventoryng_ocsservers_id);
            return ['status' => OcsProcess::SNMP_LINKED,
                //'entities_id'  => $data['entities_id'],
            ];
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
