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

use Dropdown;
use Entity;
use Glpi\Asset\Asset_PeripheralAsset;
use GlpiPlugin\Ocsinventoryng\OcsProcess;
use GlpiPlugin\Ocsinventoryng\OcsServer;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Monitor
 */
class Monitor
{
    public static $rightname = "plugin_ocsinventoryng";

    /**
     *
     * Import monitors from OCS
     * @since 1.0
     *
     * @param $monitor_params
     *
     * @throws \GlpitestSQLError
     * @internal param computer $ocsid 's id in OCS
     * @internal param the $entity entity in which the monitor will be created
     */
    public static function importMonitor($monitor_params)
    {
        global $DB;

        $cfg_ocs       = $monitor_params["cfg_ocs"];
        $computers_id  = $monitor_params["computers_id"];
        $ocsservers_id = $monitor_params["plugin_ocsinventoryng_ocsservers_id"];
        $ocsComputer   = $monitor_params["datas"];
        $entity        = $monitor_params["entities_id"];
        $force         = $monitor_params["force"];

        $uninstall_history = 0;
        if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_monitor'] == 1 || $cfg_ocs['history_monitor'] == 3)) {
            $uninstall_history = 1;
        }
        $install_history = 0;
        if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_monitor'] == 1 || $cfg_ocs['history_monitor'] == 2)) {
            $install_history = 1;
        }

        if ($force || $cfg_ocs["import_monitor"] == 1) { // Only reset monitor as global in unit management
            self::resetMonitors($computers_id, $uninstall_history);    // try to link monitor with existing
        }

        $already_processed = [];
        $m                 = new \Monitor();
        $conn              = new Asset_PeripheralAsset();

        $monitors = [];

        // First pass - check if all serial present

        foreach ($ocsComputer as $monitor) {
            // Config says import monitor with serial number only
            // Restrict SQL query ony for monitors with serial present
            if ($cfg_ocs["import_monitor"] > 2 && empty($monitor["SERIAL"])) {
                unset($monitor);
            } else {
                $monitors[] = $monitor;
            }
        }

        if (count($monitors) > 0 && $cfg_ocs["import_monitor"] > 0) {
            foreach ($monitors as $monitor) {
                $mon = [];
                if (!empty($monitor["CAPTION"])) {
                    $mon["name"]             = OcsProcess::encodeOcsDataInUtf8(
                        $cfg_ocs["ocs_db_utf8"],
                        $monitor["CAPTION"]
                    );
                    $mon["monitormodels_id"] = Dropdown::importExternal('MonitorModel', $monitor["CAPTION"]);
                }
                if (empty($monitor["CAPTION"]) && !empty($monitor["MANUFACTURER"])) {
                    $mon["name"] = $monitor["MANUFACTURER"];
                }
                if (empty($monitor["CAPTION"]) && !empty($monitor["TYPE"])) {
                    if (!empty($monitor["MANUFACTURER"])) {
                        $mon["name"] .= " ";
                    }
                    $mon["name"] .= $monitor["TYPE"];
                }
                if (!empty($monitor["TYPE"])) {
                    $mon["monitortypes_id"] = Dropdown::importExternal('MonitorType', $monitor["TYPE"]);
                }
                $mon["serial"]     = $monitor["SERIAL"];
                $mon["is_dynamic"] = 1;
                //Look for a monitor with the same name (and serial if possible) already connected
                //to this computer
                //15012021 : Unactivated because block link for good computer
                //            $query = "SELECT `m`.`id`, `gci`.`is_deleted`
                //                      FROM `glpi_monitors` as `m`, `glpi_assets_assets_peripheralassets` as `gci`
                //                      WHERE `m`.`id` = `gci`.`items_id`
                //                         AND `gci`.`is_dynamic` = 1
                //                         AND `computers_id`= $computers_id
                //                         AND `itemtype`= 'Monitor'
                //                         AND `m`.`name`='" . $mon["name"] . "'";
                //            if ($cfg_ocs["import_monitor"] > 2 && !empty($mon["serial"])) {
                //               $query .= " AND `m`.`serial`='" . $mon["serial"] . "'";
                //            }
                //            $results = $DB->doQuery($query);
                $id      = false;
                //            if ($DB->numrows($results) == 1) {
                //               $id = $DB->result($results, 0, 'id');
                //            }

                if ($id == false) {
                    // Clean monitor object
                    $m->reset();
                    $mon["manufacturers_id"] = Dropdown::importExternal('Manufacturer', $monitor["MANUFACTURER"]);
                    if ($cfg_ocs["import_monitor_comment"]) {
                        $mon["comment"] = $monitor["DESCRIPTION"];
                    }
                    $id_monitor = 0;

                    if ($cfg_ocs["import_monitor"] == 1) {
                        //Config says : manage monitors as global
                        //check if monitors already exists in GLPI
                        $mon["is_global"] = MANAGEMENT_GLOBAL;

                        $criteria = [
                            'SELECT' => 'id',
                            'FROM' => 'glpi_monitors',
                            'WHERE' => [
                                'name' => $mon["name"],
                                'is_global' => MANAGEMENT_GLOBAL,
                            ]
                        ];

                        if (Entity::getUsedConfig('transfers_strategy', $entity, 'transfers_id', 0) < 1) {
                            $criteria['WHERE'] = $criteria['WHERE'] + ['entities_id' => $entity];
                        }
                        $iterator = $DB->request($criteria);

                        if (count($iterator) > 0) {
                            //Periph is already in GLPI
                            //Do not import anything just get periph ID for link
                            foreach ($iterator as $data) {
                                $id_monitor = $data['id'];
                            }
                        } else {
                            $input = $mon;
                            //for rule asset
                            $input['_auto']      = 1;
                            $input["is_dynamic"]  = 1;
                            $input["entities_id"] = $entity;
                            $id_monitor           = $m->add($input, [], $install_history);
                        }
                    } elseif ($cfg_ocs["import_monitor"] >= 2) {
                        //Config says : manage monitors as single units
                        //Import all monitors as non global.
                        $mon["is_global"] = MANAGEMENT_UNITARY;

                        // Try to find a monitor with the same serial.
                        if (!empty($mon["serial"])) {

                            $criteria = [
                                'SELECT' => 'id',
                                'FROM' => 'glpi_monitors',
                                'WHERE' => [
                                    'serial'   => ['LIKE', '%' . $mon["serial"] . '%'],
                                    'is_global' => MANAGEMENT_UNITARY,
                                ]
                            ];

                            if (Entity::getUsedConfig('transfers_strategy', $entity, 'transfers_id', 0) < 1) {
                                $criteria['WHERE'] = $criteria['WHERE'] + ['entities_id' => $entity];
                            }

                            $iterator = $DB->request($criteria);

                            if (count($iterator) == 1) {
                                //Periph is already in GLPI
                                //Do not import anything just get periph ID for link
                                foreach ($iterator as $data) {
                                    //Monitor founded
                                    $id_monitor = $data['id'];
                                }
                            }
                        }

                        //Search by serial failed, search by name
                        if ($cfg_ocs["import_monitor"] == 2
                        && !$id_monitor) {
                            //Try to find a monitor with no serial, the same name and not already connected.
                            if (!empty($mon["name"])) {

                                $criteria = [
                                    'SELECT' => [
                                        'glpi_monitors.id'
                                    ],
                                    'FROM' => 'glpi_monitors',
                                    'LEFT JOIN'       => [
                                        'glpi_assets_assets_peripheralassets' => [
                                            'ON' => [
                                                'glpi_peripherals'   => 'id',
                                                'glpi_assets_assets_peripheralassets'                  => 'items_id_peripheral', [
                                                    'AND' => [
                                                        'glpi_assets_assets_peripheralassets.itemtype_peripheral' => 'Monitor',
                                                    ],
                                                ],
                                            ]
                                        ],
                                    ],
                                    'WHERE' => [
                                        'glpi_peripherals.name' => $mon["name"],
                                        'glpi_assets_assets_peripheralassets.is_global' => 0,
                                        'glpi_assets_assets_peripheralassets.items_id_asset' => null,
                                        'glpi_assets_assets_peripheralassets.itemtype_asset' => 'Computer',
                                    ]
                                ];

                                if (Entity::getUsedConfig('transfers_strategy', $entity, 'transfers_id', 0) < 1) {
                                    $criteria['WHERE'] = $criteria['WHERE'] + ['entities_id' => $entity];
                                }

                                $iterator = $DB->request($criteria);
                                if (count($iterator) == 1) {
                                    foreach ($iterator as $values) {
                                        $id_monitor = $values['id'];
                                    }
                                }
                            }
                        }

                        if (!$id_monitor) {
                            $input = $mon;
                            //for rule asset
                            $input['_auto']       = 1;
                            $input["entities_id"] = $entity;
                            $input["is_dynamic"]  = 1;
                            $id_monitor           = $m->add($input, [], $install_history);
                        }
                    } // ($cfg_ocs["import_monitor"] >= 2)

                    if ($id_monitor) {
                        //Import unique : Disconnect monitor on other computer done in Connect function
                        $conn->add(['items_id_asset' => $computers_id,
                            'itemtype_asset'     => 'Computer',
                            'itemtype_peripheral'      => 'Monitor',
                            'items_id'     => $id_monitor,
                            'is_dynamic'   => 1,
                            'is_deleted'   => 0], [], $install_history);
                        $already_processed[] = $id_monitor;

                        //Update column "is_deleted" set value to 0 and set status to default
                        $input = [];
                        $old   = new \Monitor();
                        if ($old->getFromDB($id_monitor)) {
                            //for rule asset
                            $input['_auto']      = 1;
                            if ($old->fields["is_deleted"]) {
                                $input["is_deleted"] = 0;
                            }

                            if (empty($old->fields["name"])
                             && !empty($mon["name"])) {
                                $input["name"] = $mon["name"];
                            }
                            if (empty($old->fields["serial"])
                            && !empty($mon["serial"])) {
                                $input["serial"] = $mon["serial"];
                            }
                            $input["id"] = $id_monitor;
                            if (count($input)) {
                                $input['entities_id'] = $entity;
                                $m->update($input, $install_history);
                            }
                        }
                    }
                } else {
                    $already_processed[] = $id;
                }
                //Look for all monitors, not locked, not linked to the computer anymore
                $criteria = [
                    'SELECT' => 'id',
                    'FROM' => 'glpi_assets_assets_peripheralassets',
                    'WHERE' => [
                        'itemtype_peripheral' => 'Monitor',
                        'items_id_asset' => $computers_id,
                        'itemtype_asset' => 'Computer',
                        'is_dynamic' => 1,
                        'is_deleted' => 0,
                    ],
                ];
                if (!empty($already_processed)) {
                    $criteria['WHERE'] = $criteria['WHERE'] + ['items_id_asset' => ['NOT IN', $already_processed]];
                }
                $iterator = $DB->request($criteria);
                foreach ($iterator as $data) {
                    // Delete all connexions
                    //Get OCS configuration
                    $ocs_config = OcsServer::getConfig($ocsservers_id);

                    //Get the management mode for this device
                    $mode     = OcsServer::getDevicesManagementMode($ocs_config, 'Monitor');
                    $decoConf = $ocs_config["deconnection_behavior"];

                    //Change status if :
                    // 1 : the management mode IS NOT global
                    // 2 : a deconnection's status have been defined
                    // 3 : unique with serial
                    if ($mode >= 2 && $decoConf != null && (strlen($decoConf) > 0)) {
                        //Delete periph from glpi
                        if ($decoConf == "delete") {

                            $query = $DB->buildDelete(
                                'glpi_assets_assets_peripheralassets',
                                [
                                    'id' =>  $data['id'],
                                ]
                            );
                            $DB->doQuery($query);

                            //Put periph in dustbin
                        } elseif ($decoConf == "trash") {

                            $query = $DB->buildUpdate(
                                'glpi_assets_assets_peripheralassets',
                                [
                                    'is_deleted' => 1,
                                ],
                                [
                                    'id' =>  $data['id'],
                                ]
                            );
                            $DB->doQuery($query);

                        }
                    }
                }
            }
        }
    }

    /**
     * Delete all old monitors of a computer.
     *
     * @param $glpi_computers_id integer : glpi computer id.
     *
     * @param $uninstall_history
     * @return void .
     * @throws \GlpitestSQLError
     */
    public static function resetMonitors($glpi_computers_id, $uninstall_history)
    {
        global $DB;

        $criteria = [
            'SELECT' => ['*'],
            'FROM' => 'glpi_assets_assets_peripheralassets',
            'WHERE' => [
                'itemtype_peripheral' => 'Monitor',
                'items_id_asset' => $glpi_computers_id,
                'itemtype_asset' => 'Computer',
                'is_dynamic' => 1,
            ],
        ];
        $iterator = $DB->request($criteria);

        if (count($iterator) > 0) {
            $conn = new Asset_PeripheralAsset();

            foreach ($iterator as $data) {
                $conn->delete(['id' => $data['id'], '_no_history' => !$uninstall_history], true, $uninstall_history);

                $criteria = [
                    'COUNT' => 'cpt',
                    'FROM' => 'glpi_assets_assets_peripheralassets',
                    'WHERE' => [
                        'itemtype_peripheral' => 'Monitor',
                        'items_id_peripheral' => $data['items_id_asset'],
                    ],
                ];
                $iterator = $DB->request($criteria);

                $mon = new \Monitor();
                if (count($iterator) == 1) {
                    $mon->delete(['id' => $data['items_id_asset'], '_no_history' => !$uninstall_history], true, $uninstall_history);
                }
            }
        }
    }
}
