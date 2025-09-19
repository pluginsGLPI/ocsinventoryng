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

use Alert;
use CommonDBTM;
use CommonDropdown;
use DbUtils;
use Dropdown;
use Entity;
use Html;
use Log;
use MassiveAction;
use NotificationEvent;
use Session;
use Toolbox;

/**
 * Class Notimportedcomputer
 */
class Notimportedcomputer extends CommonDBTM
{
    // From CommonDBTM
    public $dohistory         = true;
    public static $rightname         = "plugin_ocsinventoryng";
    public $first_level_menu  = "plugins";
    public $second_level_menu = "ocsinventoryng";


    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Computer not imported', 'Computers not imported', $nb, 'ocsinventoryng');
    }


    /**
     * @return array
     */
//    public function getAdditionalFields()
//    {
//        return [['name'  => 'reason',
//            'label' => __('Reason of rejection'),
//            'type'  => 'reason'],
//            ['name'  => 'rules_id',
//                'label' => __('Verified rules', 'ocsinventoryng'),
//                'type'  => 'echo_rule'],
//            ['name'  => 'ocsid',
//                'label' => __('OCSNG ID', 'ocsinventoryng'),
//                'type'  => 'text'],
//            ['name'  => 'plugin_ocsinventoryng_ocsservers_id',
//                'label' => __('Server'),
//                'type'  => 'echo_dropdown',
//                'table' => 'glpi_plugin_ocsinventoryng_ocsservers'],
//            ['name'  => 'ocs_deviceid',
//                'label' => __('Device ID', 'ocsinventoryng'),
//                'type'  => 'text'],
//            ['name'  => 'serial',
//                'label' => __('Serial number'),
//                'type'  => 'text'],
//            ['name'  => 'tag',
//                'label' => __('OCSNG TAG', 'ocsinventoryng'),
//                'type'  => 'text'],
//            ['name'  => 'useragent',
//                'label' => __('Inventory agent', 'ocsinventoryng'),
//                'type'  => 'text'],
//            ['name'  => 'ipaddr',
//                'label' => __('IP'),
//                'type'  => 'text'],
//            ['name'  => 'domain',
//                'label' => __('Domain'),
//                'type'  => 'text'],
//            ['name'  => 'last_inventory',
//                'label' => __('Last OCSNG inventory date', 'ocsinventoryng'),
//                'type'  => 'echo_datetime']];
//    }


    /**
     * Add more tabs to display
     * @return array
     * @internal param array $options
     *
     */
    public function defineMoreTabs()
    {
        $ong = [];
        if (OcsServer::getComputerLinkToOcsConsole(
            $this->fields['plugin_ocsinventoryng_ocsservers_id'],
            $this->fields['ocsid'],
            '',
            true
        ) != '') {
            $ong[3] = __('OCSNG console');
        }
        $ong[12] = _n('Log', 'Logs', 2);
        return $ong;
    }


    /**
     * Display fields that are specific to this itemtype
     *
     * @param the   $ID
     * @param array $field
     *
     * @return void
     * @internal param the $ID item's ID
     * @internal param array $field the item's fields
     */
    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {
        switch ($field['type']) {
            case 'echo':
                echo $this->fields[$field['name']];
                break;

            case 'reason':
                echo self::getReason($this->fields[$field['name']]);
                break;

            case 'echo_datetime':
                echo Html::convDateTime($this->fields[$field['name']]);
                break;

            case 'echo_dropdown':
                $result = Dropdown::getDropdownName($field['table'], $this->fields[$field['name']]);
                if ($result == '') {
                    echo Dropdown::EMPTY_VALUE;
                } else {
                    echo $result;
                }
                break;

            case 'echo_rule':
                echo self::getRuleMatchedMessage($this->fields[$field['name']]);
                break;
        }
    }


    /**
     * @param $rule_list
     *
     * @return string
     * @return string
     */
    public static function getRuleMatchedMessage($rule_list)
    {
        $message = [];
        if ($rule_list != '') {
            foreach (json_decode($rule_list, true) as $key => $value) {
                $dbu = new DbUtils();
                if ($rule = $dbu->getItemForItemtype($key)) {
                    $rule = new $key();
                    if ($rule->can($value, READ)) {
                        $url       = $rule->getLinkURL();
                        $message[] = "<a href='$url'>" . $rule->getName() . "</a>";
                    }
                }
            }
            return implode(' => ', $message);
        }
        return '';
    }


    public function displayOcsConsole()
    {
        $url = OcsServer::getComputerLinkToOcsConsole(
            $this->fields['plugin_ocsinventoryng_ocsservers_id'],
            $this->fields['ocsid'],
            '',
            true
        );
        echo "<div class='center'>";
        if ($url != '') {
            echo "<iframe src='$url' width='80%' height='60%'>";
        }
        echo "</div>";
    }


    /**
     * Display more tabs
     *
     * @param $tab
     **/
    public function displayMoreTabs($tab)
    {
        switch ($tab) {
            case 1:
                self::showActions($this);
                break;

            case 3:
                $this->displayOcsConsole();
                break;

            case 12:
                Log::showForItem($this);
                break;

            case -1:
                self::showActions($this);
                Log::showForItem($this);
                break;
        }
    }


    /**
     * @return array
     */
    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'   => 'common',
            'name' => _n(
                'Computer not imported',
                'Computers not imported',
                2,
                'ocsinventoryng'
            ),
        ];

        $tab[] = [
            'id'       => '1',
            'table'    => $this->getTable(),
            'field'    => 'ocsid',
            'name'     => __('OCSNG ID', 'ocsinventoryng'),
//            'datatype' => 'integer',
        ];
        //
        //      $tab[] = [
        //         'id'            => '2',
        //         'table'         => $this->getTable(),
        //         'field'         => 'name',
        //         'name'          => __('OCSNG name', 'ocsinventoryng'),
        //         'datatype'      => 'itemlink',
        //         'massiveaction' => false,
        //         'itemlink_type' => $this->getType()
        //      ];

        $tab[] = [
            'id'       => '3',
            'table'    => $this->getTable(),
            'field'    => 'useragent',
            'name'     => __('Inventory agent', 'ocsinventoryng'),
            'datatype' => 'text',
        ];

        $tab[] = [
            'id'       => '4',
            'table'    => $this->getTable(),
            'field'    => 'ocs_deviceid',
            'name'     => __('Device ID', 'ocsinventoryng'),
            'datatype' => 'text',
        ];

        $tab[] = [
            'id'        => '5',
            'table'     => 'glpi_plugin_ocsinventoryng_ocsservers',
            'field'     => 'name',
            'name'      => __('Server'),
            'linkfield' => 'plugin_ocsinventoryng_ocsservers_id',
            'datatype'  => 'dropdown',
        ];

        $tab[] = [
            'id'    => '6',
            'table' => $this->getTable(),
            'field' => 'tag',
            'name'  => __('OCSNG TAG', 'ocsinventoryng'),
        ];

        $tab[] = [
            'id'    => '7',
            'table' => $this->getTable(),
            'field' => 'ipaddr',
            'name'  => __('IP'),
        ];

        $tab[] = [
            'id'    => '8',
            'table' => $this->getTable(),
            'field' => 'domain',
            'name'  => __('Domain'),
        ];

        $tab[] = [
            'id'       => '9',
            'table'    => $this->getTable(),
            'field'    => 'last_inventory',
            'name'     => __('Last OCSNG inventory date', 'ocsinventoryng'),
            'datatype' => 'datetime',
        ];

        $tab[] = [
            'id'    => '10',
            'table' => $this->getTable(),
            'field' => 'reason',
            'name'  => __('Reason of rejection'),
        ];

        $tab[] = [
            'id'    => '11',
            'table' => $this->getTable(),
            'field' => 'serial',
            'name'  => __('Serial number'),
        ];

        $tab[] = [
            'id'       => '80',
            'table'    => 'glpi_entities',
            'field'    => 'completename',
            'name'     => __('Entity'),
            'datatype' => 'dropdown',
        ];

        return $tab;
    }

    /**
     * @param $ocsservers_id
     * @param $ocsid
     * @param $reason
     **/
    public function logNotImported($ocsservers_id, $ocsid, $reason)
    {
        global $DB;

        //      OcsServer::checkOCSconnection($ocsservers_id);
        $ocsClient = OcsServer::getDBocs($ocsservers_id);
        $options   = [
            "DISPLAY" => [
                "CHECKSUM" => OcsClient::CHECKSUM_HARDWARE
                              | OcsClient::CHECKSUM_BIOS,
            ],
        ];
        $computer  = $ocsClient->getComputer($ocsid, $options);

        if ((isset($computer["HARDWARE"]) && $computer["HARDWARE"])
            && (isset($computer["BIOS"]) && $computer["BIOS"])) {
            $input["_ocs"]                                = true;
            $input["name"]                                = $computer["META"]["NAME"] ?? 'null';
            $input["domain"]                              = $computer["HARDWARE"]["WORKGROUP"] ?? 'null';
            $input["tag"]                                 = $computer["META"]["TAG"];
            $input["ocs_deviceid"]                        = $computer["HARDWARE"]["DEVICEID"];
            $input["ipaddr"]                              = $computer["HARDWARE"]["IPSRC"] ?? 'null';
            $input["plugin_ocsinventoryng_ocsservers_id"] = $ocsservers_id;
            $input["ocsid"]                               = $ocsid;
            $input["last_inventory"]                      = $computer["HARDWARE"]["LASTCOME"];
            $input["useragent"]                           = $computer["HARDWARE"]["USERAGENT"] ?? 'null';
            $input["serial"]                              = $computer["BIOS"]["SSN"] ?? '';
            $input["reason"]                              = $reason['status'];
            $input["comment"]                             = "";
            if (isset($reason['entities_id'])) {
                $input["entities_id"] = $reason['entities_id'];
            } else {
                $input['entities_id'] = 0;
            }
            $input["rules_id"] = json_encode($reason['rule_matched']);

            $query  = "SELECT `id` FROM `glpi_plugin_ocsinventoryng_notimportedcomputers`
                    WHERE `ocsid`='" . $ocsid . "'
                    AND `plugin_ocsinventoryng_ocsservers_id`= '" . $ocsservers_id . "'";
            $result = $DB->doQuery($query);
            if ($DB->numrows($result) > 0) {
                $input['id'] = $DB->result($result, 0, 'id');
                $this->update($input);
            } else {
                $this->add($input);
            }
        }
    }


    /**
     * @param $ocsservers_id (default -1)
     * @param $ocsid (default -1)
     *
     * @return int|number
     * @return int|number
     */
    public function cleanNotImported($ocsservers_id = -1, $ocsid = -1)
    {
        global $DB;

        $first = true;

        $sql = "DELETE
              FROM `" . $this->getTable() . "`";

        if ($ocsservers_id != -1) {
            $sql   .= " WHERE `plugin_ocsinventoryng_ocsservers_id` = $ocsservers_id";
            $first = false;
        }
        if ($ocsid != -1) {
            $sql   .= ($first ? " WHERE" : " AND") . " `ocsid` = '$ocsid'";
            $first = false;
        }
        if ($first) {
            // Use truncate to reset id
            $sql = "TRUNCATE `" . $this->getTable() . "`";
        }
        $result = $DB->doQuery($sql);

        return ($result ? $DB->affectedRows() : -1);
    }


    /**
     * Delete a row in the notimported table
     *
     * @param if $not_imported_id
     *
     * @return void
     * @internal param if $not_imported_id of the computer that is not imported in GLPI
     */
    public function deleteNotImportedComputer($not_imported_id)
    {
        $can_update = Config::canUpdateOCS();
        if ($this->getFromDB($not_imported_id) && $can_update) {
            self::deleteComputerInOCS(
                $this->fields["ocsid"],
                $this->fields["plugin_ocsinventoryng_ocsservers_id"]
            );
            $fields["id"] = $not_imported_id;
            $this->delete($fields);
        }
    }

    /**
     * @param $ocs_id
     * @param $ocs_server_id
     *
     * @throws \GlpitestSQLError
     */
    public static function deleteComputerInOCS($ocs_id, $ocs_server_id)
    {
        $DBocs = OcsServer::getDBocs($ocs_server_id)->getDB();

        //First try to remove all the network ports
        $query = "DELETE
                FROM `netmap`
                WHERE `MAC` IN (SELECT `MACADDR`
                                FROM `networks`
                                WHERE `networks`.`HARDWARE_ID` = '" . $ocs_id . "')";
        $DBocs->doQuery($query);

        $tables = ["accesslog", "accountinfo", "batteries", "bios", "controllers", "cpus", "devices",
            "download_history", "download_servers", "drives", "groups",
            "groups_cache", "inputs", "itmgmt_comments", "javainfo", "jounallog",
            "locks", "memories", "modems", "monitors", "networks", "ports", "printers",
            "registry", "saas", "sim", "slots", "softwares", "sounds", "storages", "usbdevices", "videos", "virtualmachines"];

        foreach ($tables as $table) {
            if (self::OcsTableExists($ocs_server_id, $table)) {
                $query = "DELETE
                      FROM `" . $table . "`
                      WHERE `hardware_id` = '" . $ocs_id . "'";
                $DBocs->doQuery($query);
            }
        }

        $query = "DELETE
                FROM `hardware`
                WHERE `ID` = '" . $ocs_id . "'";
        $DBocs->doQuery($query);
    }

    public static function OcsTableExists($ocs_server_id, $tablename)
    {
        $dbClient = OcsServer::getDBocs($ocs_server_id);

        if (!($dbClient instanceof OcsDbClient)) {
            return false;
        }

        $DBocs = $dbClient->getDB();
        return $DBocs->tableExists($tablename);
    }


    /**
     * @param $reason
     *-
     *
     * @return string|translated
     */
    public static function getReason($reason)
    {
        switch ($reason) {
            case OcsProcess::COMPUTER_FAILED_IMPORT:
                return __("Can't affect an entity", 'ocsinventoryng');

            case OcsProcess::COMPUTER_NOT_UNIQUE:
                return __('Unicity criteria not verified', 'ocsinventoryng');

            case OcsProcess::COMPUTER_LINK_REFUSED:
                return __('Import refused by rule', 'ocsinventoryng');

            default:
                return "";
        }
    }


    /**
     * @param $notimported  Notimportedcomputer object
     **/
    public static function showActions(Notimportedcomputer $notimported)
    {
        echo "<div class='spaced'>";
        echo "<form name='actions' id='actions' method='post' value='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_2'>";
        echo "<th class='center'>" . __('Actions to be made on the computer', 'ocsinventoryng') .
             "</th></tr>";

        echo "<tr class='tab_bg_2'><td class='center'>";
        echo Html::hidden('id', ['value' => $notimported->fields['id']]);
        echo Html::hidden('action', ['value' => 'massive']);
        Dropdown::showForMassiveAction(
            Notimportedcomputer::class,
            0,
            ['action' => 'massive']
        );
        echo "</td></tr>";
        echo "</table>";
        Html::closeForm();
        echo "</div>";
    }


    /**
     * @param $params array
     *
     * @return bool
     * @return bool
     */
    public static function computerImport($params = [])
    {
        if (isset($params['id'])) {
            $notimported = new Notimportedcomputer();
            $notimported->getFromDB($params['id']);

            if (!OcsServer::checkOCSconnection($_SESSION["plugin_ocsinventoryng_ocsservers_id"])) {
                Session::addMessageAfterRedirect(
                    __("Error to contact ocs server", 'ocsinventoryng'),
                    false,
                    ERROR
                );
                return false;
            }
            $changes = self::getOcsComputerInfos($notimported->fields);
            if (isset($params['force'])) {
                $process_params = ['ocsid'                               => $notimported->fields['ocsid'],
                    'plugin_ocsinventoryng_ocsservers_id' => $notimported->fields['plugin_ocsinventoryng_ocsservers_id'],
                    'lock'                                => 0,
                    'force'                               => $params['force'],
                    'defaultentity'                       => $params['entity'],
                    'defaultrecursive'                    => 0];
            } else {
                $process_params = ['ocsid'                               => $notimported->fields['ocsid'],
                    'plugin_ocsinventoryng_ocsservers_id' => $notimported->fields['plugin_ocsinventoryng_ocsservers_id'],
                    'lock'                                => 0];
            }
            $result         = OcsProcess::processComputer($process_params);

            if (in_array(
                $result['status'],
                [OcsProcess::COMPUTER_IMPORTED,
                    OcsProcess::COMPUTER_LINKED,
                    OcsProcess::COMPUTER_SYNCHRONIZED]
            )) {
                $notimported->delete(['id' => $params['id']]);

                //If serial has been changed in order to import computer
                if (in_array('serial', $changes)) {
                    Ocslink::mergeOcsArray(
                        $result['computers_id'],
                        ['serial']
                    );
                }

                return true;
            } else {
                Session::addMessageAfterRedirect(
                    self::getReason($result['status']),
                    false,
                    ERROR
                );
                return false;
            }

            $tmp           = $notimported->fields;
            $tmp['reason'] = $result['status'];
            if (isset($result['entities_id'])) {
                $tmp["entities_id"] = $result['entities_id'];
            } else {
                $tmp['entities_id'] = 0;
            }
            $tmp["rules_id"] = json_encode($result['rule_matched']);
            $notimported->update($tmp);
            return false;
        }
    }


    /**
     * @param $params array
     **/
    public static function linkComputer($params = [])
    {
        if (isset($params['id'])) {
            $notimported = new Notimportedcomputer();
            $notimported->getFromDB($params['id']);

            $link_params = ['ocsid'                               => $notimported->fields['ocsid'],
                'plugin_ocsinventoryng_ocsservers_id' => $notimported->fields['plugin_ocsinventoryng_ocsservers_id'],
                'computers_id'                        => $params['computers_id']];
            if (!OcsServer::checkOCSconnection($notimported->fields['plugin_ocsinventoryng_ocsservers_id'])) {
                Session::addMessageAfterRedirect(
                    __("Error to contact ocs server", 'ocsinventoryng'),
                    false,
                    ERROR
                );
                return false;
            }
            $changes = self::getOcsComputerInfos($notimported->fields);
            if (OcsProcess::linkComputer($link_params)
            ) {
                $notimported->delete(['id' => $params['id']]);
                //If serial has been changed in order to import computer
                if (in_array('serial', $changes)) {
                    Ocslink::mergeOcsArray($params['id'], ['serial']);
                }
            }
        }
    }


    /**
     * @param $params array
     *
     * @return array
     * @return array
     */
    public static function getOcsComputerInfos($params = [])
    {
        $ocsClient = OcsServer::getDBocs($params['plugin_ocsinventoryng_ocsservers_id']);
        $options   = [
            "DISPLAY" => [
                "CHECKSUM" => OcsClient::CHECKSUM_BIOS,
            ],
        ];
        $computer  = $ocsClient->getComputer($params['ocsid'], $options);
        $changes   = [];

        if ($computer) {
            $ocs_serial = $computer["BIOS"]["SSN"];
            if ($ocs_serial != $params['serial']) {
                $ocsClient->updateBios($params['serial'], $params['ocsid']);
                $changes[] = 'serial';
            }
            $ocs_tag = $computer["META"]["TAG"];
            if ($ocs_tag != $params['tag']) {
                $ocsClient->updateBios($params['tag'], $params['ocsid']);
                $changes[] = 'tag';
            }
        }

        return $changes;
    }


    /**
     * @return int
     */
    public static function sendAlert()
    {
        global $DB, $CFG_GLPI;

        if (!$CFG_GLPI["notifications_mailing"]) {
            return 0;
        }

        $items_infos = [];

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_plugin_ocsinventoryng_notimportedcomputers.*',
            ],
            'FROM'      => 'glpi_plugin_ocsinventoryng_notimportedcomputers',
            'LEFT JOIN'       => [
                'glpi_alerts' => [
                    'ON' => [
                        'glpi_plugin_ocsinventoryng_notimportedcomputers'   => 'id',
                        'glpi_alerts'                  => 'items_id', [
                            'AND' => [
                                'glpi_alerts.itemtype' => 'GlpiPlugin\\Ocsinventoryng\\Notimportedcomputer',
                                'glpi_alerts.type' => Alert::END,
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_alerts.date'  => null,
            ],
        ]);

        foreach ($iterator as $notimported) {
            $items_infos[$notimported['entities_id']][$notimported['id']] = $notimported;
        }

        foreach ($items_infos as $entity => $items) {
            if (NotificationEvent::raiseEvent(
                'not_imported',
                new Notimportedcomputer(),
                ['entities_id' => $entity,
                    'notimported' => $items]
            )
            ) {
                $alert             = new Alert();
                $input["itemtype"] = Notimportedcomputer::class;
                $input["type"]     = Alert::END;
                foreach ($items as $id => $item) {
                    $input["items_id"] = $id;
                    $alert->add($input);
                    unset($alert->fields['id']);
                }
            } else {
                Toolbox::logDebug(
                    __('%1$s: %2$s') . "\n",
                    Dropdown::getDropdownName("glpi_entities", $entity),
                    __('Send OCSNG not imported computers alert failed', 'ocsinventoryng')
                );
            }
        }
    }


    /**
     *
     */
    public function cleanDBonPurge()
    {
        global $DB;

        $query = "DELETE
                FROM `glpi_alerts`
                WHERE `itemtype` = 'GlpiPlugin\\Ocsinventoryng\\Notimportedcomputer'
                      AND `items_id` = " . $this->fields['id'];
        $DB->doQuery($query);
    }


    /**
     * @param $name
     *
     * @return array
     */
    public static function cronInfo($name)
    {
        switch ($name) {
            case "SendAlerts":
                return ['description' => __('OCSNG', 'ocsinventoryng')
                                            . " - " . __('Not imported computers alert', 'ocsinventoryng')];
        }
    }

    /**
     * @param $task
     */
    public static function cronSendAlerts($task)
    {
        self::sendAlert();
        $task->setVolume(1);
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
            case 'plugin_ocsinventoryng_import':
                Entity::dropdown(['name' => 'entity']);
                echo "&nbsp;" .
                     Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
                /*case 'plugin_ocsinventoryng_link':
                   Computer::dropdown(array('name' => 'computers_id'));
                   echo "&nbsp;".
                        Html::submit(_x('button','Post'), array('name' => 'massiveaction'));
                   return true;*/
            case 'plugin_ocsinventoryng_replayrules':
            case 'plugin_ocsinventoryng_delete':
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
        array $ids
    ) {
        $notimport = new Notimportedcomputer();

        switch ($ma->getAction()) {
            case "plugin_ocsinventoryng_import":
                $input = $ma->getInput();

                foreach ($ids as $id) {
                    if (Notimportedcomputer::computerImport(['id'     => $id,
                        'force'  => true,
                        'entity' => $input['entity']])) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                    } else {
                        $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                    }
                }

                return;

            case "plugin_ocsinventoryng_replayrules":
                foreach ($ids as $id) {
                    if (Notimportedcomputer::computerImport(['id' => $id])) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                    } else {
                        $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                    }
                }
                return;

            case "plugin_ocsinventoryng_delete":
                foreach ($ids as $id) {
                    if ($notimport->deleteNotImportedComputer($id)) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                    } else {
                        $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                    }
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }
}
