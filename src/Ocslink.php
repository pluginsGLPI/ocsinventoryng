<?php

/*
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2018 by the ocsinventoryng Development Team.

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

use CommonDBTM;
use CommonGLPI;
use Computer;
use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\Asset_PeripheralAsset;
use GlpiPlugin\Ocsinventoryng\Components\Bios;
use GlpiPlugin\Ocsinventoryng\Components\Bitlockerstatus;
use GlpiPlugin\Ocsinventoryng\Components\Customapp;
use GlpiPlugin\Ocsinventoryng\Components\Hardware;
use GlpiPlugin\Ocsinventoryng\Components\OS;
use GlpiPlugin\Ocsinventoryng\Components\Osinstall;
use GlpiPlugin\Ocsinventoryng\Components\Proxysetting;
use GlpiPlugin\Ocsinventoryng\Components\RegistryKey;
use GlpiPlugin\Ocsinventoryng\Components\Runningprocess;
use GlpiPlugin\Ocsinventoryng\Components\Service;
use GlpiPlugin\Ocsinventoryng\Components\Teamviewer;
use GlpiPlugin\Ocsinventoryng\Components\Winupdate;
use GlpiPlugin\Ocsinventoryng\Components\Winuser;
use Html;
use Log;
use RuleImportEntityCollection;
use Session;
use Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Ocslink
 */
class Ocslink extends CommonDBTM
{
    public const HISTORY_OCS_IMPORT     = 8;
    public const HISTORY_OCS_DELETE     = 9;
    public const HISTORY_OCS_IDCHANGED  = 10;
    public const HISTORY_OCS_LINK       = 11;
    public const HISTORY_OCS_TAGCHANGED = 12;

    public static string $rightname = "plugin_ocsinventoryng";

    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return _n('OCSNG link', 'OCSNG links', $nb, 'ocsinventoryng');
    }

    /**
     * Show simple inventory information of an computer child item
     *
     * @param $item                   CommonDBTM object
     *
     * @return void
     */
    public static function showSimpleForChild(CommonDBTM $item)
    {
        // The children of a computer: Item_Disk / ItemVirtualMachine (itemtype + items_id)
        $dbu = new DbUtils();
        if ($item->isDynamic()
            && ($item->fields['itemtype'] ?? null) === Computer::class
            && $dbu->countElementsInTable('glpi_plugin_ocsinventoryng_ocslinks', ["computers_id" => (int) $item->fields['items_id']]) > 0) {
            echo htmlescape(Menu::getTypeName());
        }
    }

    /**
     * Show simple inventory information of an item
     *
     * @param $item                   CommonDBTM object
     *
     * @return void
     */
    public static function showSimpleForItem(CommonDBTM $item)
    {
        global $DB;

        if (!in_array($item->getType(), [Computer::class])) {
            return;
        }

        $plugin_ocsinventoryng_ocsservers_id = Ocslink::getOCSServerForItem($item);
        if ($plugin_ocsinventoryng_ocsservers_id <= 0) {
            return;
        }

        $blocks = [];
        if (!OcsServer::serverIsActive($plugin_ocsinventoryng_ocsservers_id)) {
            // A server not active: its name only
            $ocs_config = OcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
            $blocks[] = [
                'title' => __('OCS Inventory NG Import informations', 'ocsinventoryng'),
                'infos' => [
                    ['label' => __('Server'), 'html' => self::getServerLink($ocs_config)],
                    ['label' => __('Active'), 'value' => Dropdown::getYesNo(0)],
                ],
            ];
            self::displayInventoryInfo($blocks);
            return;
        }

        $items_id = $item->getField('id');
        if (empty($items_id)
            || !$item->fields["is_dynamic"]
            || !Session::haveRight(Profile::RIGHT_LINK, READ)) {
            return;
        }

        $target = Toolbox::getItemTypeFormURL(__CLASS__);
        $dbu    = new DbUtils();
        $data   = $DB->request([
            'FROM'  => 'glpi_plugin_ocsinventoryng_ocslinks',
            'WHERE' => ['computers_id' => (int) $items_id]
                + $dbu->getEntitiesRestrictCriteria('glpi_plugin_ocsinventoryng_ocslinks'),
            'LIMIT' => 1,
        ])->current();
        if ($data) {
            // Manage locks pictures
            self::showLockIcon($item->getField('id'), $data);

            $ocs_config = OcsServer::getConfig($data['plugin_ocsinventoryng_ocsservers_id']);
            $infos = [
                ['label' => __('Last OCSNG inventory date', 'ocsinventoryng'), 'value' => Html::convDateTime($data["last_ocs_update"])],
                // Reported by the OCS agent: escaped by the template
                ['label' => __('Inventory agent', 'ocsinventoryng'),
                    'value' => trim($data["ocs_agent_version"] . " " . $data['ocs_deviceid'])],
                ['label' => __('GLPI import date', 'ocsinventoryng'), 'value' => Html::convDateTime($data["last_update"])],
                ['label' => __('Server'), 'html' => self::getServerLink($ocs_config)],
            ];
            if (isset($data["last_ocs_conn"])) {
                $infos[] = ['label' => __('Last OCSNG connection date', 'ocsinventoryng'), 'value' => Html::convDateTime($data["last_ocs_conn"])];
            }
            if (isset($data["ip_src"])) {
                $infos[] = ['label' => __('IP Source', 'ocsinventoryng'), 'value' => (string) $data["ip_src"]];
            }
            $infos[] = ['label' => __('OCSNG TAG', 'ocsinventoryng'), 'value' => (string) $data["tag"]];
            if (Session::haveRight(Profile::RIGHT_SYNC, UPDATE)) {
                $infos[] = ['label' => __('Automatic update OCSNG', 'ocsinventoryng'), 'value' => Dropdown::getYesNo($data["use_auto_update"])];
            }
            if ($data['uptime'] != null) {
                $infos[] = ['label' => __('Uptime', 'ocsinventoryng'), 'value' => (string) $data['uptime']];
            }

            $actions = [];
            // If have write right on OCS and ocsreports url is not empty in OCS config
            if (Session::haveRight(OcsServer::$rightname, UPDATE) && ($ocs_config["ocs_url"] != '')) {
                $url = OcsServer::getComputerLinkToOcsConsole($ocs_config['id'], $data["ocsid"], '', true);
                if ($url != '') {
                    $actions[] = ['title' => __('OCS NG Interface', 'ocsinventoryng'), 'url' => $url];
                }
            }
            $resynch = ['id' => $items_id, 'resynch_id' => $data["id"]];
            if (Session::haveRight(Profile::RIGHT_SYNC, UPDATE)) {
                if (strtotime($data["last_ocs_update"]) > strtotime($data["last_update"])) {
                    $actions[] = ['title' => _sx('button', 'Launch synchronization', 'ocsinventoryng'),
                        'target' => $target, 'name' => 'launch_ocs_resynch', 'params' => $resynch];
                }
                $actions[] = ['title' => _sx('button', 'Force full import', 'ocsinventoryng'), 'icon' => 'ti-download',
                    'target' => $target, 'name' => 'force_ocs_resynch', 'params' => $resynch];
            }

            $blocks[] = [
                'title'   => __('OCS Inventory NG Import informations', 'ocsinventoryng'),
                'infos'   => $infos,
                'links'   => Teamviewer::getDirectAccessLinks($item),
                'actions' => $actions,
            ];
        }

        // SNMP link
        $data = $DB->request([
            'FROM'  => 'glpi_plugin_ocsinventoryng_snmpocslinks',
            'WHERE' => ['items_id' => (int) $items_id, 'itemtype' => $item->getType()],
            'LIMIT' => 1,
        ])->current();
        if ($data) {
            $blocks[] = SnmpOcslink::getInventoryInfoBlock($item, $data, true);
        }

        // IPDiscover link
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

        self::displayInventoryInfo($blocks);
    }

    /**
     * Link to an OCS server, its name only without the right of the plugin
     *
     * @param array $ocs_config
     *
     * @return string HTML
     */
    public static function getServerLink(array $ocs_config)
    {
        $name = htmlescape((string) ($ocs_config['name'] ?? ''));
        if (!Session::haveRight(OcsServer::$rightname, READ)) {
            return $name;
        }

        return "<a href='" . htmlescape(OcsServer::getFormURLWithID((int) $ocs_config['id'])) . "'>" . $name . "</a>";
    }

    /**
     * Display the inventory information blocks of an item (templates/inventory_info.html.twig)
     *
     * @param array $blocks
     *
     * @return void
     */
    public static function displayInventoryInfo(array $blocks)
    {
        if (count($blocks) == 0) {
            return;
        }
        TemplateRenderer::getInstance()->display('@ocsinventoryng/inventory_info.html.twig', [
            'blocks' => $blocks,
        ]);
    }

    /**
     * Display the rows of an OCS component of a computer (Components/*::showForComputer()) with
     * templates/table.html.twig, and add them to the navigation of the items
     *
     * @param string   $itemtype    the component class
     * @param Computer $comp
     * @param array    $columns     column => label
     * @param array    $rows        the rows (with an 'id' key), values escaped by the template
     * @param array    $raw_columns columns of HTML built and escaped in PHP
     *
     * @return void
     */
    public static function showComponentRows(string $itemtype, Computer $comp, array $columns, array $rows, array $raw_columns = [])
    {
        Session::initNavigateListItems(
            $itemtype,
            //TRANS : %1$s is the itemtype name,
            //        %2$s is the name of the item (used for headings of a list)
            sprintf(__('%1$s = %2$s'), Computer::getTypeName(1), $comp->getName()),
        );
        foreach ($rows as $row) {
            Session::addToNavigateListItems($itemtype, $row['id']);
        }

        TemplateRenderer::getInstance()->display('@ocsinventoryng/table.html.twig', [
            'title'         => $itemtype::getTypeName(count($rows)),
            'columns'       => $columns,
            'raw_columns'   => $raw_columns,
            'rows'          => $rows,
            'empty_message' => __('No results found'),
        ]);
    }


    /**
     * Read ocslink for a given computer
     *
     * @param $ID   Integer   ID of the computer
     *
     * @return boolean
     **/
    public function getFromDBforComputer($ID)
    {
        $field = "`" . $this->getTable() . "`.`computers_id`";
        if ($this->getFromDBByCrit([$field => $ID])) {
            return true;
        }
        return false;
    }


    /**
     * Show OcsLink of an item
     *
     * @param $item                   CommonDBTM object
     *
     * @return void
     * @internal param int|string $withtemplate integer  withtemplate param (default '')
     */
    public static function showForItem(CommonDBTM $item)
    {
        global $DB;

        if (!in_array($item->getType(), [Computer::class])) {
            return;
        }

        $items_id = $item->getField('id');
        $data     = null;
        if (!empty($items_id)
            && $item->fields["is_dynamic"]
            && Session::haveRight(Profile::RIGHT_LINK, READ)) {
            $dbu  = new DbUtils();
            $data = $DB->request([
                'FROM'  => 'glpi_plugin_ocsinventoryng_ocslinks',
                'WHERE' => ['computers_id' => (int) $items_id]
                    + $dbu->getEntitiesRestrictCriteria('glpi_plugin_ocsinventoryng_ocslinks'),
                'LIMIT' => 1,
            ])->current() ?: null;
        }

        $checksum = '';
        $debug    = null;
        if ($data !== null
            && OcsServer::checkOCSconnection($data["plugin_ocsinventoryng_ocsservers_id"])) {
            $ocsClient = OcsServer::getDBocs($data["plugin_ocsinventoryng_ocsservers_id"]);
            $checksum  = $ocsClient->getChecksum($data['ocsid']);

            if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                $debug = self::getOcsDebugData($ocsClient, $data);
            }
        }

        // Values of the link reported by the OCS agent: escaped by the template (|e, htmlField prints raw)
        TemplateRenderer::getInstance()->display('@ocsinventoryng/ocslink.html.twig', [
            'form_url'   => Toolbox::getItemTypeFormURL(__CLASS__),
            'items_id'   => (int) $items_id,
            'link'       => $data,
            'checksum'   => (string) $checksum,
            'canedit'    => Session::haveRight(Profile::RIGHT_LINK, UPDATE),
            'can_delete' => Session::haveRight(Profile::RIGHT_CLEAN, UPDATE),
            'debug'      => $debug,
        ]);
    }

    /**
     * The OCS data of a linked computer, for the debug mode
     *
     * @param OcsClient $ocsClient
     * @param array     $data      the OCS link
     *
     * @return array sections of the data (section => [name => value or [key => value]]) and the
     *               checksum test
     */
    private static function getOcsDebugData(OcsClient $ocsClient, array $data)
    {
        $computer = $ocsClient->getComputer($data["ocsid"], [
            'DISPLAY'  => [
                'CHECKSUM' => OcsClient::CHECKSUM_HARDWARE,
                'PLUGINS'  => OcsClient::PLUGINS_ALL,
            ],
            'COMPLETE' => 1,
        ]);

        $sections        = [];
        $checksum_client = 0;
        foreach (is_array($computer) ? $computer : [] as $key => $val) {
            foreach ((array) $val as $name => $value) {
                $sections[$key][$name] = $value;
                if ($name == "CHECKSUM") {
                    $checksum_client = intval($value);
                }
            }
        }

        $checksum_test = null;
        $server        = new OcsServer();
        if ($checksum_client > 0 && $server->getFromDB($data["plugin_ocsinventoryng_ocsservers_id"])) {
            $checksum_server = intval($server->fields["checksum"]);
            $checksum_test   = sprintf(
                '(%1$2d = %1$04b) = (%2$2d = %2$04b) %3$s (%4$2d = %4$04b)',
                $checksum_server & $checksum_client,
                $checksum_server,
                '&',
                $checksum_client,
            );
        }

        return ['sections' => $sections, 'checksum_test' => $checksum_test];
    }


    /**
     * Update TAG information in glpi_plugin_ocsinventoryng_ocslinks table
     *
     * @param $line
     * @param $meta
     *
     * @return string : current tag of computer on update
     * @internal param array $line_ocs : data from ocs tables
     */
    public static function updateTag($line, $meta)
    {
        global $DB;

        if ($meta) {
            $data_ocs = $meta;

            if (isset($data_ocs["TAG"])
                && isset($line["tag"])
                && $data_ocs["TAG"] != $line["tag"]
            ) {
                // The tag is set by the agent: bound, never concatenated
                if ($DB->update(
                    'glpi_plugin_ocsinventoryng_ocslinks',
                    ['tag' => (string) $data_ocs["TAG"]],
                    ['computers_id' => (int) $line["computers_id"]],
                )) {
                    $changes[0] = '0';
                    $changes[1] = $line["tag"];
                    $changes[2] = $data_ocs["TAG"];

                    self::history(
                        $line["computers_id"],
                        $changes,
                        self::HISTORY_OCS_TAGCHANGED,
                    );
                }
            }
        }
    }

    /**
     * Update linked items of an item
     *
     * @param $item                     Asset_PeripheralAsset object
     *
     * @internal param int|string $withtemplate integer  withtemplate param (default '')
     * @return bool
     */
    public static function addComputer_Item(Asset_PeripheralAsset $item)
    {
        global $DB;

        $link = new $item->input['itemtype']();
        if (!$link->getFromDB($item->input['items_id'])) {
            return false;
        }
        if (!$link->getField('is_global')) {
            // Handle case where already used, should never happen (except from OCS sync)
            $result = $DB->request([
                'SELECT' => ['id', 'items_id_asset'],
                'FROM'   => 'glpi_assets_assets_peripheralassets',
                'WHERE'  => [
                    'items_id_peripheral' => (int) $item->input['items_id'],
                    'itemtype_peripheral' => $link->getType(),
                    'itemtype_asset'      => Computer::class,
                ],
            ]);

            foreach ($result as $data) {
                $temp = clone $item;
                $temp->delete($data, true);
            }
        }
    }


    /**
     * Delete computers
     *
     * @param      $plugin_ocsinventoryng_ocsservers_id
     * @param type $agents
     *
     * @return int
     */
    public static function deleteComputers($plugin_ocsinventoryng_ocsservers_id, $agents)
    {
        $ocslink  = new self();
        $computer = new Computer();

        $nb = 0;
        foreach ($agents as $key => $val) {
            foreach ($val as $k => $agent) {
                //search for OCS links
                if ($ocslink->getFromDBByCrit(['ocsid'                               => $agent,
                    'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id])) {
                    //search computer
                    if ($computer->getFromDB($ocslink->fields['computers_id'])) {
                        if (!$computer->fields['is_deleted']) {
                            $computer->fields['is_deleted'] = 1;
                            $computer->fields['date_mod']   = $_SESSION['glpi_currenttime'];
                            $computer->updateInDB(['is_deleted', 'date_mod']);
                            //add history
                            $changes[0] = 0;
                            $changes[2] = "";
                            $changes[1] = "";
                            Log::history(
                                $ocslink->fields['computers_id'],
                                'Computer',
                                $changes,
                                0,
                                Log::HISTORY_DELETE_ITEM,
                            );

                            $nb += 1;
                        }
                    }
                }
            }
        }

        return $nb;
    }

    /**
     * if Computer deleted
     *
     * @param $comp   Computer object
     **/
    public static function purgeComputer(Computer $comp)
    {
        $can_update = Config::canUpdateOCS();
        if ($can_update) {
            $ocsservers_id = OcsServer::getServerByComputerID($comp->getField("id"));
            if ($ocsservers_id > 0) {
                $ocs_id = OcsServer::getOCSIDByComputerID($comp->getField("id"), $ocsservers_id);
                if ($ocs_id > 0) {
                    Notimportedcomputer::deleteComputerInOCS($ocs_id, $ocsservers_id);
                }
            }
        }

        $link = new self();
        $link->deleteByCriteria(['computers_id' => $comp->getField("id")]);

        $reg = new RegistryKey();
        $reg->deleteByCriteria(['computers_id' => $comp->getField("id")]);

        $winupdate = new Winupdate();
        $winupdate->deleteByCriteria(['computers_id' => $comp->getField("id")]);

        $osinstall = new Osinstall();
        $osinstall->deleteByCriteria(['computers_id' => $comp->getField("id")]);

        $proxy = new Proxysetting();
        $proxy->deleteByCriteria(['computers_id' => $comp->getField("id")]);

        $runningprocess = new Runningprocess();
        $runningprocess->deleteByCriteria(['computers_id' => $comp->getField("id")]);

        $service = new Service();
        $service->deleteByCriteria(['computers_id' => $comp->getField("id")]);

        $winuser = new Winuser();
        $winuser->deleteByCriteria(['computers_id' => $comp->getField("id")]);

        $teamviewer = new Teamviewer();
        $teamviewer->deleteByCriteria(['computers_id' => $comp->getField("id")]);

        $customapp = new Customapp();
        $customapp->deleteByCriteria(['computers_id' => $comp->getField("id")]);

        $bitlocker = new Bitlockerstatus();
        $bitlocker->deleteByCriteria(['computers_id' => $comp->getField("id")]);
    }


    /**
     * if Asset_PeripheralAsset deleted
     *
     * @param $comp   Asset_PeripheralAsset object
     **/
    public static function purgeComputer_Item(Asset_PeripheralAsset $comp)
    {


        global $DB;
        $dbu = new DbUtils();
        if ($device = $dbu->getItemForItemtype($comp->fields['itemtype'])) {
            if ($device->getFromDB($comp->fields['items_id'])) {
                if (isset($comp->input['_ocsservers_id'])) {
                    $ocsservers_id = $comp->input['_ocsservers_id'];
                } else {
                    $ocsservers_id = OcsServer::getServerByComputerID($comp->fields['computers_id']);
                }

                if ($ocsservers_id > 0) {
                    //Get OCS configuration
                    $ocs_config = OcsServer::getConfig($ocsservers_id);

                    //Get the management mode for this device
                    $mode     = OcsServer::getDevicesManagementMode(
                        $ocs_config,
                        $comp->fields['itemtype'],
                    );
                    $decoConf = $ocs_config["deconnection_behavior"];

                    //Change status if :
                    // 1 : the management mode IS NOT global
                    // 2 : a deconnection's status have been defined
                    // 3 : unique with serial

                    if (($mode >= 2)
                        && (strlen($decoConf) > 0)
                    ) {


                        if ($decoConf == "delete") {
                            $DB->delete('glpi_assets_assets_peripheralassets', ['id' => (int) $comp->getID()]);
                            //Put periph in dustbin
                        } elseif ($decoConf == "trash") {
                            $DB->update(
                                'glpi_assets_assets_peripheralassets',
                                ['is_deleted' => 1],
                                ['id' => (int) $comp->getID()],
                            );
                        }
                    }
                } // $ocsservers_id>0
            }
        }
    }

    /**
     * @see inc/CommonGLPI::getTabNameForItem()
     *
     * @param $item               CommonGLPI object
     * @param $withtemplate (default 0)
     *
     * @return array|string
     * @return array|string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $plugin_ocsinventoryng_ocsservers_id = Ocslink::getOCSServerForItem($item);
        if ($plugin_ocsinventoryng_ocsservers_id > 0
            && OcsServer::serverIsActive($plugin_ocsinventoryng_ocsservers_id)) {
            if (in_array($item->getType(), OcsServer::getTypes(true))
                && Session::haveRight(Profile::RIGHT_LINK, READ)) {
                switch ($item->getType()) {
                    case 'Computer':
                        if (!$withtemplate) {
                            return self::createTabEntry(_n('OCSNG link', 'OCSNG links', 1, 'ocsinventoryng'));
                        }
                }
            }
        }
        return '';
    }

    public static function getIcon()
    {
        return "ti ti-link";
    }


    /**
     * @param $item            CommonGLPI object
     * @param $tabnum (default 1)
     * @param $withtemplate (default 0)
     *
     * @return bool|true
     * @return bool|true
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (in_array($item->getType(), OcsServer::getTypes(true))) {
            switch ($item->getType()) {
                case 'Computer':
                    self::showForItem($item);
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
     * @param $action       Integer in Ocslink::HISTORY_OCS_*
     *
     * @return Integer id of the inserted entry
     **/
    public static function history($computers_id, $changes, $action)
    {
        return Log::history(
            $computers_id,
            'Computer',
            $changes,
            __CLASS__,
            Log::HISTORY_PLUGIN + $action,
        );
    }

    /**
     * Get an history entry message
     *
     * @param $data Array from glpi_logs table
     *
     * @return string
     **/
    public static function getHistoryEntry($data)
    {
        if (Session::haveRight(OcsServer::$rightname, READ)) {
            switch ($data['linked_action'] - Log::HISTORY_PLUGIN) {
                case self::HISTORY_OCS_IMPORT:
                    return sprintf(
                        __('%1$s: %2$s'),
                        __('Imported from OCSNG', 'ocsinventoryng'),
                        $data['new_value'],
                    );

                case self::HISTORY_OCS_DELETE:
                    return sprintf(
                        __('%1$s: %2$s'),
                        __('Deleted in OCSNG', 'ocsinventoryng'),
                        $data['old_value'],
                    );

                case self::HISTORY_OCS_LINK:
                    return sprintf(
                        __('%1$s: %2$s'),
                        __('Linked with an OCSNG computer', 'ocsinventoryng'),
                        $data['new_value'],
                    );

                case self::HISTORY_OCS_IDCHANGED:
                    return sprintf(
                        __(
                            'The OCSNG ID of the computer changed from %1$s to %2$s',
                            'ocsinventoryng',
                        ),
                        $data['old_value'],
                        $data['new_value'],
                    );

                case self::HISTORY_OCS_TAGCHANGED:
                    return sprintf(
                        __(
                            'The OCSNG TAG of the computer changed from %1$s to %2$s',
                            'ocsinventoryng',
                        ),
                        $data['old_value'],
                        $data['new_value'],
                    );
            }
        }
        return '';
    }

    /**
     * @param CommonGLPI $item
     *
     * @return int the OCS server id, 0 if the item is not linked
     */
    public static function getOCSServerForItem(CommonGLPI $item)
    {
        global $DB;

        // Items without database id, e.g. the virtual items of the PDF plugin
        if (!$item instanceof CommonDBTM) {
            return 0;
        }

        $dbu = new DbUtils();
        $result = $DB->request([
            'FROM'  => 'glpi_plugin_ocsinventoryng_ocslinks',
            'WHERE' => ['computers_id' => (int) $item->getID()]
                + $dbu->getEntitiesRestrictCriteria('glpi_plugin_ocsinventoryng_ocslinks'),
            'LIMIT' => 1,
        ]);
        if (count($result) > 0) {
            $data = $result->current();

            if (count($data)) {
                return $data['plugin_ocsinventoryng_ocsservers_id'];
            }

            return 0;
        }

        return 0;
    }

    /**
     * Make the item link between glpi and ocs.
     *
     * This make the database link between ocs and glpi databases
     *
     * @param $link_params
     * @return bool|item
     */
    public static function ocsLink($link_params)
    {
        global $DB;

        $ocsid                               = (int) $link_params["ocsid"];
        $plugin_ocsinventoryng_ocsservers_id = (int) $link_params["plugin_ocsinventoryng_ocsservers_id"];
        $computers_id                        = (int) $link_params["computers_id"];

        // Retrieve informations from computer
        $comp = new Computer();
        if (!$comp->getFromDB($computers_id)) {
            return false;
        }
        $comp->update([
            "id"         => $computers_id,
            "is_dynamic" => 1,
        ]);

        $ocsClient = OcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);

        $ocsComputer = $ocsClient->getComputer($ocsid);

        if (is_null($ocsComputer)) {
            return false;
        }
        $link = new self();
        $data = $link->find(["ocsid" => $ocsid,
            "plugin_ocsinventoryng_ocsservers_id" => $plugin_ocsinventoryng_ocsservers_id]);
        if (count($data) > 0) {
            return false;
        }
        $result = $DB->insert('glpi_plugin_ocsinventoryng_ocslinks', [
            'computers_id'                        => $computers_id,
            'ocsid'                               => $ocsid,
            'ocs_deviceid'                        => (string) $ocsComputer['META']['DEVICEID'],
            'last_update'                         => $_SESSION["glpi_currenttime"],
            'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
            'entities_id'                         => (int) $comp->fields['entities_id'],
            'tag'                                 => (string) $ocsComputer['META']['TAG'],
        ]);

        if ($result) {
            return ($DB->insertId());
        }

        return false;
    }

    /**
     * Clean links between GLPI and OCS from a list.
     *
     * @param $plugin_ocsinventoryng_ocsservers_id int : id of ocs server in GLPI
     * @param $ocslinks_id array : ids of ocslinks to clean
     * @param bool $check_rights ids posted by a user (front/ocsng.clean.php): only the computers
     *                           the user may delete or update, not those of other entities
     *
     * @return int the number of links skipped (computers the user may not clean)
     */
    public static function cleanLinksFromList($plugin_ocsinventoryng_ocsservers_id, $ocslinks_id, bool $check_rights = false)
    {
        global $DB;

        $plugin_ocsinventoryng_ocsservers_id = (int) $plugin_ocsinventoryng_ocsservers_id;
        $cfg_ocs = OcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
        $skipped = 0;

        foreach ($ocslinks_id as $key => $val) {
            $data = $DB->request([
                'FROM'  => 'glpi_plugin_ocsinventoryng_ocslinks',
                'WHERE' => [
                    'id'                                  => (int) $key,
                    'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                ],
                'LIMIT' => 1,
            ])->current();
            if (!$data) {
                continue;
            }

            $comp = new Computer();
            if ($check_rights
                && !$comp->can((int) $data['computers_id'], $cfg_ocs['deleted_behavior'] == 1 ? DELETE : UPDATE)) {
                $skipped++;
                continue;
            }
            if ($cfg_ocs['deleted_behavior']) {
                if ($cfg_ocs['deleted_behavior'] == 1) {
                    $comp->delete(["id" => $data["computers_id"]], 0);
                } else {
                    if (preg_match('/STATE_(.*)/', $cfg_ocs['deleted_behavior'], $results)) {
                        $tmp['id']          = $data["computers_id"];
                        $tmp['states_id']   = $results[1];
                        $tmp['entities_id'] = $data['entities_id'];
                        $tmp["_nolock"]     = true;

                        $comp->update($tmp);
                    }
                }
            }

            //Add history to indicates that the machine was deleted from OCS
            $changes[0] = '0';
            $changes[1] = $data["ocsid"];
            $changes[2] = "";
            self::history($data["computers_id"], $changes, self::HISTORY_OCS_DELETE);

            $DB->delete('glpi_plugin_ocsinventoryng_ocslinks', ['id' => (int) $data["id"]]);
        }

        return $skipped;
    }

    /**
     * Link of a computer, for the lock functions below
     *
     * @param int|string $computers_id
     *
     * @return array|null
     */
    private static function getLinkForComputer($computers_id): ?array
    {
        global $DB;

        return $DB->request([
            'FROM'  => 'glpi_plugin_ocsinventoryng_ocslinks',
            'WHERE' => ['computers_id' => (int) $computers_id],
            'LIMIT' => 1,
        ])->current();
    }

    /**
     * Save a list of locked fields of the link of a computer
     *
     * @param int|string $computers_id
     * @param string     $field computer_update or another list field of the link
     * @param array      $tab
     *
     * @return bool
     */
    private static function saveOcsArray($computers_id, string $field, array $tab): bool
    {
        global $DB;

        $dbu = new DbUtils();

        return (bool) $DB->update(
            'glpi_plugin_ocsinventoryng_ocslinks',
            [$field => $dbu->exportArrayToDB($tab)],
            ['computers_id' => (int) $computers_id],
        );
    }

    /**
     * @param $computers_id
     * @param $tomerge
     * @return bool
     */
    public static function mergeOcsArray($computers_id, $tomerge)
    {
        $link = self::getLinkForComputer($computers_id);
        if ($link === null) {
            return false;
        }

        $dbu = new DbUtils();
        $tab = $dbu->importArrayFromDB($link["computer_update"]);
        $lockable = self::getLockableFields($link["plugin_ocsinventoryng_ocsservers_id"], $link["ocsid"]);
        foreach ($tab as $k => $field) {
            if (!array_key_exists($field, $lockable)) {
                unset($tab[$k]);
            }
        }

        if (is_array($tab) && is_array($tomerge)) {
            return self::saveOcsArray($computers_id, 'computer_update', array_unique(array_merge($tomerge, $tab)));
        }
        return false;
    }

    /**
     * @param      $computers_id
     * @param      $todel
     * @param bool $is_value_to_del
     *
     * @return bool
     */
    public static function deleteInOcsArray($computers_id, $todel, $is_value_to_del = false)
    {
        $link = self::getLinkForComputer($computers_id);
        if ($link === null) {
            return false;
        }

        $cfg_ocs = OcsServer::getConfig($link["plugin_ocsinventoryng_ocsservers_id"]);
        if ($cfg_ocs["use_locks"]) {
            $dbu = new DbUtils();
            $tab = $dbu->importArrayFromDB($link['computer_update']);

            if ($is_value_to_del) {
                $todel = array_search($todel, $tab);
            }
            if ($todel !== false && isset($tab[$todel])) {
                unset($tab[$todel]);
                return self::saveOcsArray($computers_id, 'computer_update', $tab);
            }
        }
        return false;
    }

    /**
     * @param      $computers_id
     * @param      $newArray
     * @param bool $lock
     *
     * @return bool
     */
    public static function replaceOcsArray($computers_id, $newArray, $lock = true)
    {
        $link = self::getLinkForComputer($computers_id);
        if ($link === null) {
            return false;
        }

        $cfg_ocs = OcsServer::getConfig($link["plugin_ocsinventoryng_ocsservers_id"]);
        if ($lock && $cfg_ocs["use_locks"]) {
            self::saveOcsArray($computers_id, 'computer_update', (array) $newArray);

            return true;
        }
        return false;
    }


    /**
     * @param $computers_id
     * @param $toadd
     * @param $field
     *
     * @return bool
     */
    public static function addToOcsArray($computers_id, $toadd, $field)
    {
        global $DB;

        // A column of the link table only: it is used as an identifier
        if (!is_string($field) || !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocslinks', $field)) {
            return false;
        }

        $link = self::getLinkForComputer($computers_id);
        if ($link === null) {
            return false;
        }

        $cfg_ocs = OcsServer::getConfig($link["plugin_ocsinventoryng_ocsservers_id"]);
        if ($cfg_ocs["use_locks"]) {
            $dbu = new DbUtils();
            $tab = $dbu->importArrayFromDB($link[$field]);

            // Stripslashes because importArray get clean array
            foreach ($toadd as $key => $val) {
                $tab[] = stripslashes($val);
            }
            self::saveOcsArray($computers_id, $field, $tab);

            return true;
        }
        return false;
    }

    /**
     * @param     $ID
     *
     * @param int $withrule
     * @return array|null
     */
    public static function getLocksForComputer($ID, $withrule = 1)
    {
        global $DB;

        $locks  = [];
        $result = $DB->request([
            'FROM'  => 'glpi_plugin_ocsinventoryng_ocslinks',
            'WHERE' => ['computers_id' => (int) $ID],
        ]);
        if (count($result) == 1) {
            $data = $result->current();

            $cfg_ocs = OcsServer::getConfig($data["plugin_ocsinventoryng_ocsservers_id"]);
            if ($cfg_ocs["use_locks"]) {
                // Print lock fields for OCSNG
                if ($withrule) {
                    $lockable_fields = self::getLockableFields(
                        $data["plugin_ocsinventoryng_ocsservers_id"],
                        $data["ocsid"],
                    );
                } else {
                    $lockable_fields = self::getLockableFields(
                        $data["plugin_ocsinventoryng_ocsservers_id"],
                        0,
                    );
                }
                $dbu    = new DbUtils();
                $locked = $dbu->importArrayFromDB($data["computer_update"]);
                if (count($locked) > 0) {
                    foreach ($locked as $key => $val) {
                        if (!isset($lockable_fields[$val])) {
                            unset($locked[$key]);
                        }
                    }
                }

                if (count($locked)) {
                    foreach ($locked as $key => $val) {
                        $locks[$key] = $val;
                    }
                }
            } else {
                $locks = [];
            }

            return $locks;
        }
    }

    /**
     * @param int $plugin_ocsinventoryng_ocsservers_id
     * @param int $ocsid
     * @return array
     */
    public static function getLockableFields($plugin_ocsinventoryng_ocsservers_id = 0, $ocsid = 0)
    {
        $locks = array_merge(
            Hardware::getHardwareLockableFields($plugin_ocsinventoryng_ocsservers_id),
            Bios::getBiosLockableFields($plugin_ocsinventoryng_ocsservers_id),
            Hardware::getRuleLockableFields($plugin_ocsinventoryng_ocsservers_id, $ocsid),
            OS::getOSLockableFields($plugin_ocsinventoryng_ocsservers_id),
            OcsAdminInfosLink::getAdministrativeInfosLockableFields($plugin_ocsinventoryng_ocsservers_id),
        );

        return $locks;
    }


    /**
     * Display lock icon in main item form
     *
     * @param $computers_id
     * @param $data
     */
    public static function showLockIcon($computers_id, $data)
    {
        global $CFG_GLPI;

        $cfg_ocs = OcsServer::getConfig($data['plugin_ocsinventoryng_ocsservers_id']);
        if ($cfg_ocs["use_locks"]) {
            if (isset($computers_id)
                && $computers_id > 0) {
                $locks = self::getLocksForComputer($computers_id);

                $text = __('Unlock field and import OCSNG data', 'ocsinventoryng');
                foreach ($locks as $field) {
                    if ($field == "contact"
                        || $field == "contact_num"
                        || $field == "serial"
                        || $field == "name"
                        || $field == "otherserial"
                        || $field == "licenseid"
                        || $field == "contact_num"
                        || $field == "license_number"
                        || $field == "use_date"
                    ) {
                        $js = '$("input[name=' . $field . ']").after("&nbsp;<i class=\"btn btn-outline-secondary red lockfield' . $field . ' ti ti-lock pointer\"></i>");';
                    } elseif ($field == "comment") {
                        $js = '$("textarea[name=' . $field . ']").after("&nbsp;<i class=\"btn btn-outline-secondary red lockfield' . $field . ' ti ti-lock pointer\"></i>");';
                    } else {
                        $js = '$("select[name=' . $field . ']").after("&nbsp;<i class=\"btn btn-outline-secondary red lockfield' . $field . ' ti ti-lock pointer\"></i>");';
                    }
                    $root_ocs_doc                             = PLUGIN_OCS_WEBDIR;
                    $plugin_ocsinventoryng_ocsservers_id = $data['plugin_ocsinventoryng_ocsservers_id'];
                    $js .= '$(document).ready(function() {
                        $(".lockfield' . $field . '").click(function(e) {
                           lastClickedElement = e.target;
                           var check = confirm("' . $text . ' ?");
                           var lock_data = {
                           "field" : "' . $field . '",
                           "computers_id" : "' . $computers_id . '",
                           "plugin_ocsinventoryng_ocsservers_id" : "' . $plugin_ocsinventoryng_ocsservers_id . '",
                           "ocsid" : "' . $data['ocsid'] . '",
                           "ocs_linkid" : "' . $data['id'] . '",
                           "update_lock" : "update_lock",
                           };
                          if (check) {
                                 $.ajax({
                                 type: "POST",
                                 url: "' . $root_ocs_doc . '/ajax/updatelock.php",
                                 data:lock_data,
                                 success: function(){
                                    window.location.reload();
                                 },
                              });
                          } else {
                              return false;
                          }
                      });
                    });';
                    echo Html::scriptBlock($js);
                }
            }
        }
    }

    /**
     * @param $plugin_ocsinventoryng_ocsservers_id
     * @param $computers_id
     * @param $ocsid
     * @param $field
     *
     */
    public static function updateLock($plugin_ocsinventoryng_ocsservers_id, $computers_id, $ocsid, $field)
    {
        $comp = new Computer();
        if ($comp->getFromDB($computers_id)) {
            self::deleteInOcsArray($computers_id, $field, true);
            $ocsClient = OcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
            $cfg_ocs   = OcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);

            $options   = [
                "DISPLAY" => [
                    "CHECKSUM" => OcsClient::CHECKSUM_HARDWARE | OcsClient::CHECKSUM_BIOS  | OcsClient::CHECKSUM_NETWORK_ADAPTERS,
                ],
            ];

            $locks = self::getLocksForComputer($computers_id);

            $ocsComputer = $ocsClient->getComputer($ocsid, $options);
            $params      = ['computers_id'                        => $computers_id,
                'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                'cfg_ocs'                             => $cfg_ocs,
                'computers_updates'                   => $locks,
                'ocs_id'                              => $ocsid,
                'entities_id'                         => $comp->fields['entities_id'],
                'HARDWARE'                            => $ocsComputer['HARDWARE'] ?? [],
                'BIOS'                                => $ocsComputer['BIOS'],
                'force'                               => 0,
            ];

            if (array_key_exists(
                $field,
                Hardware::getHardwareLockableFields($plugin_ocsinventoryng_ocsservers_id),
            )) {
                Hardware::updateComputerHardware($params);
            }
            if (array_key_exists(
                $field,
                Bios::getBiosLockableFields($plugin_ocsinventoryng_ocsservers_id),
            )) {
                Bios::updateComputerBios($params);
            }
            if (array_key_exists(
                $field,
                OS::getOSLockableFields($plugin_ocsinventoryng_ocsservers_id),
            )) {
                $params['check_history'] = true;
                OS::updateComputerOS($params);
            }
            if (array_key_exists(
                $field,
                OcsAdminInfosLink::getAdministrativeInfosLockableFields($plugin_ocsinventoryng_ocsservers_id),
            )) {
                OcsAdminInfosLink::updateAdministrativeInfo($params);
            }
            if (array_key_exists(
                $field,
                Hardware::getRuleLockableFields($plugin_ocsinventoryng_ocsservers_id, $ocsid),
            )) {
                //            $values = [];
                //            Hardware::getFields($ocsComputer, $cfg_ocs, $values);
                $input = [
                    'itemtype'    => "Computer",
                    'name'        => $ocsComputer['META']["NAME"],
                    'serial'      => $ocsComputer['BIOS']["SSN"] ?? '',
                    'is_dynamic'  => 1,
                    'ocsid'       => $ocsComputer['META']['ID'],
                    //                'id'          => $id,
                ];
                $computerinput = $input;
                $computerinput['ip'] = $ocsComputer['NETWORKS'][0]['IPADDRESS'];
                $computerinput['subnet'] = $ocsComputer['NETWORKS'][0]['IPSUBNET'];
                if (isset($ocsComputer['HARDWARE']['WORKGROUP'])) {
                    $computerinput['domain'] = $ocsComputer['HARDWARE']['WORKGROUP'];
                }
                if (isset($ocsComputer['HARDWARE']['OSCOMMENTS'])) {
                    $computerinput['oscomment'] = $ocsComputer['HARDWARE']['OSCOMMENTS'];
                }
                $computerinput['_source'] = 'ocsinventoryng';

                $rule = new RuleImportEntityCollection();
                $data = $rule->processAllRules(
                    $computerinput +
                                               ['ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                                                   '_source'       => 'ocsinventoryng'],
                    [],
                    ['ocsid' => $ocsid],
                );

                Hardware::updateComputerFields($params, $data, $cfg_ocs);
            }
        }
    }
}
