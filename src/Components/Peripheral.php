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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Peripheral
 */

use Dropdown;
use Entity;
use Glpi\Asset\Asset_PeripheralAsset;
use GlpiPlugin\Ocsinventoryng\OcsProcess;
use GlpiPlugin\Ocsinventoryng\OcsServer;

class Peripheral
{
    public static $rightname = "plugin_ocsinventoryng";

    /**
     *
     * Import peripherals from OCS
     * @since 1.0
     *
     * @param $periph_params
     *
     * @throws \GlpitestSQLError
     * @internal param computer $ocsid 's id in OCS
     */
    public static function importPeripheral($periph_params)
    {
        global $DB;

        $cfg_ocs       = $periph_params["cfg_ocs"];
        $computers_id  = $periph_params["computers_id"];
        $ocsservers_id = $periph_params["plugin_ocsinventoryng_ocsservers_id"];
        $ocsComputer   = $periph_params["datas"];
        $entity        = $periph_params["entities_id"];
        $force         = $periph_params["force"];

        $uninstall_history = 0;
        if ($cfg_ocs['dohistory'] == 1
            && ($cfg_ocs['history_peripheral'] == 1 || $cfg_ocs['history_peripheral'] == 3)) {
            $uninstall_history = 1;
        }
        $install_history = 0;
        if ($cfg_ocs['dohistory'] == 1
            && ($cfg_ocs['history_peripheral'] == 1 || $cfg_ocs['history_peripheral'] == 2)) {
            $install_history = 1;
        }

        if ($force) {
            self::resetPeripherals($computers_id, $uninstall_history);
        }

        $already_processed = [];
        $p                 = new \Peripheral();
        $conn              = new Asset_PeripheralAsset();

        foreach ($ocsComputer as $peripheral) {
            if ($peripheral["CAPTION"] !== '') {
                $periph         = [];
                $periph["name"] = OcsProcess::encodeOcsDataInUtf8($cfg_ocs["ocs_db_utf8"], $peripheral["CAPTION"]);
                //Look for a monitor with the same name (and serial if possible) already connected
                //to this computer
                $criteria = [
                    'SELECT' => [
                        'glpi_peripherals.id'
                    ],
                    'FROM' => 'glpi_peripherals',
                    'LEFT JOIN'       => [
                        'glpi_assets_assets_peripheralassets' => [
                            'ON' => [
                                'glpi_peripherals'   => 'id',
                                'glpi_assets_assets_peripheralassets'                  => 'items_id_peripheral', [
                                    'AND' => [
                                        'glpi_assets_assets_peripheralassets.itemtype_peripheral' => 'Peripheral',
                                    ],
                                ],
                            ]
                        ],
                    ],
                    'WHERE' => [
                        'glpi_peripherals.name' => $periph["name"],
                        'glpi_assets_assets_peripheralassets.is_dynamic' => 1,
                        'glpi_assets_assets_peripheralassets.items_id_asset' => $computers_id,
                        'glpi_assets_assets_peripheralassets.itemtype_asset' => 'Computer',
                    ]
                ];

                $iterator = $DB->request($criteria);

                $id      = false;
                if (count($iterator) > 0) {
                    foreach ($iterator as $values) {
                        $id = $values['id'];
                    }
                }

                if (!$id) {
                    // Clean peripheral object
                    $p->reset();
                    if ($peripheral["MANUFACTURER"] != "NULL") {
                        $periph["brand"] = OcsProcess::encodeOcsDataInUtf8(
                            $cfg_ocs["ocs_db_utf8"],
                            $peripheral["MANUFACTURER"]
                        );
                    }
                    if ($peripheral["INTERFACE"] != "NULL") {
                        $periph["comment"] = OcsProcess::encodeOcsDataInUtf8(
                            $cfg_ocs["ocs_db_utf8"],
                            $peripheral["INTERFACE"]
                        );
                    }
                    $periph["peripheraltypes_id"] = Dropdown::importExternal(
                        'PeripheralType',
                        $peripheral["TYPE"]
                    );
                    $id_periph                    = 0;
                    if ($cfg_ocs["import_periph"] == 1) {
                        //Config says : manage peripherals as global
                        //check if peripherals already exists in GLPI
                        $periph["is_global"] = MANAGEMENT_GLOBAL;

                        $criteria = [
                            'SELECT' => 'id',
                            'FROM' => 'glpi_peripherals',
                            'WHERE' => [
                                'name' => $periph["name"],
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
                                $id_periph = $data['id'];
                            }
                        } else {
                            $input = $periph;
                            //for rule asset
                            $input['_auto']       = 1;
                            $input["is_dynamic"]  = 1;
                            $input["entities_id"] = $entity;
                            $id_periph            = $p->add($input, [], $install_history);
                        }
                    } elseif ($cfg_ocs["import_periph"] == 2) {
                        //Config says : manage peripherals as single units
                        //Import all peripherals as non global.
                        $input              = $periph;
                        $input["is_global"] = MANAGEMENT_UNITARY;
                        //for rule asset
                        $input['_auto'] = 1;
                        $input["is_dynamic"]  = 1;
                        $input["entities_id"] = $entity;
                        $id_periph            = $p->add($input, [], $install_history);
                    }

                    if ($id_periph) {
                        $already_processed[] = $id_periph;
                        if ($conn->add(['items_id_asset' => $computers_id,
                            'itemtype_asset'     => 'Computer',
                            'itemtype_peripheral'     => 'Peripheral',
                            'items_id_peripheral'     => $id_periph,
                            'is_dynamic'   => 1], [], $install_history)) {
                            //Update column "is_deleted" set value to 0 and set status to default
                            $input                = [];
                            $input["id"]          = $id_periph;
                            $input["is_deleted"]  = 0;
                            $input["entities_id"] = $entity;
                            //for rule asset
                            $input['_auto']      = 1;
                            $input["is_dynamic"]  = 1;
                            $p->update($input, $install_history);
                        }
                    }
                } else {
                    $already_processed[] = $id;
                }
            }
        }

        //Look for all peripherals, not locked, not linked to the computer anymore
        $criteria = [
            'SELECT' => 'id',
            'FROM' => 'glpi_assets_assets_peripheralassets',
            'WHERE' => [
                'itemtype_peripheral' => 'Peripheral',
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
            $mode     = OcsServer::getDevicesManagementMode($ocs_config, 'Peripheral');
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

    /**
     * Delete all old periphs for a computer.
     *
     * @param $glpi_computers_id integer : glpi computer id.
     *
     * @param $uninstall_history
     * @return void .
     * @throws \GlpitestSQLError
     */
    public static function resetPeripherals($glpi_computers_id, $uninstall_history)
    {
        global $DB;

        $criteria = [
            'SELECT' => ['*'],
            'FROM' => 'glpi_assets_assets_peripheralassets',
            'WHERE' => [
                'itemtype_peripheral' => 'Peripheral',
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
                        'itemtype_peripheral' => 'Peripheral',
                        'items_id_peripheral' => $data['items_id_asset'],
                    ],
                ];
                $iterator = $DB->request($criteria);

                $periph = new \Peripheral();
                if (count($iterator) == 1) {
                    $periph->delete(['id' => $data['items_id_asset'], '_no_history' => !$uninstall_history], true, $uninstall_history);
                }
            }
        }
    }
}
