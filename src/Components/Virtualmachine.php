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
use ItemVirtualMachine;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Virtualmachine
 */
class Virtualmachine
{

    public static $rightname = "plugin_ocsinventoryng";

    /**
     *
     * Synchronize virtual machines
     *
     * @param         $ocsservers_id
     * @param         $history_vm
     * @param         $force
     *
     * @return void
     * @throws \GlpitestSQLError
     * @internal param unknown $ocsid
     * @internal param unknown $dohistory
     */
    public static function updateVirtualMachine($computers_id, $ocsComputer, $ocsservers_id, $cfg_ocs, $force)
    {
        global $DB;

        $uninstall_history = 0;
        if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_vm'] == 1 || $cfg_ocs['history_vm'] == 3)) {
            $uninstall_history = 1;
        }
        $install_history = 0;
        if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_vm'] == 1 || $cfg_ocs['history_vm'] == 2)) {
            $install_history = 1;
        }

        if ($force) {
            self::resetVirtualmachine($computers_id, $uninstall_history);
        }

        $already_processed = [];

        $virtualmachine = new ItemVirtualMachine();
        $ocsVirtualmachines = $ocsComputer;

        if (count($ocsVirtualmachines) > 0) {
            foreach ($ocsVirtualmachines as $ocsVirtualmachine) {
                $vm = [];
                $vm['name'] = $ocsVirtualmachine['NAME'];
                $vm['vcpu'] = $ocsVirtualmachine['VCPU'];
                $vm['ram'] = $ocsVirtualmachine['MEMORY'];
                $vm['uuid'] = $ocsVirtualmachine['UUID'];
                $vm['itemtype'] = 'Computer';
                $vm['items_id'] = $computers_id;
                $vm['is_dynamic'] = 1;

                $vm['virtualmachinestates_id'] = Dropdown::importExternal(
                    'VirtualMachineState',
                    $ocsVirtualmachine['STATUS']
                );
                $vm['virtualmachinetypes_id'] = Dropdown::importExternal(
                    'VirtualMachineType',
                    $ocsVirtualmachine['VMTYPE']
                );
                $vm['virtualmachinesystems_id'] = Dropdown::importExternal(
                    'VirtualMachineType',
                    $ocsVirtualmachine['SUBSYSTEM']
                );

                $query = "SELECT `id`
                         FROM `glpi_itemvirtualmachines`
                         WHERE `items_id`= $computers_id
                           AND `itemtype` = 'Computer'
                            AND `is_dynamic` = 1";
                if ($ocsVirtualmachine['UUID']) {
                    $query .= " AND `uuid`='" . $ocsVirtualmachine['UUID'] . "'";
                } else {
                    // Failback on name
                    $query .= " AND `name`='" . $ocsVirtualmachine['NAME'] . "'";
                }

                $results = $DB->doQuery($query);
                if ($DB->numrows($results) > 0) {
                    $id = $DB->result($results, 0, 'id');
                } else {
                    $id = 0;
                }
                if (!$id) {
                    $virtualmachine->reset();
                    $id_vm = $virtualmachine->add($vm, [], $install_history);
                    if ($id_vm) {
                        $already_processed[] = $id_vm;
                    }
                } else {
                    if ($virtualmachine->getFromDB($id)) {
                        $vm['id'] = $id;
                        $virtualmachine->update($vm, $install_history);
                    }
                    $already_processed[] = $id;
                }
            }
        }
        // Delete Unexisting Items not found in OCS
        //Look for all ununsed virtual machines
        $criteria = [
            'SELECT' => 'id',
            'FROM' => 'glpi_itemvirtualmachines',
            'WHERE' => [
                'items_id' => $computers_id,
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
            $virtualmachine->delete(
                [
                    'id' => $data['id'],
                    '_ocsservers_id' => $ocsservers_id,
                    '_no_history' => !$uninstall_history,
                ],
                true,
                $uninstall_history
            );
        }
    }

    /**
     * @param $glpi_computers_id
     * @param $uninstall_history
     */
    public static function resetVirtualmachine($glpi_computers_id, $uninstall_history)
    {
        $dd = new ItemVirtualMachine();
        $dd->deleteByCriteria([
            'items_id' => $glpi_computers_id,
            'itemtype' => 'Computer',
            'is_dynamic' => 1,
        ], 1, $uninstall_history);
    }
}
