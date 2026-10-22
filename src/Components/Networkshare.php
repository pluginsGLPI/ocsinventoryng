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

use CommonGLPI;
use Computer;
use DbUtils;
use GlpiPlugin\Ocsinventoryng\Ocslink;
use GlpiPlugin\Ocsinventoryng\OcsServer;
use Html;
use Session;
use Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Networkshare
 */
class Networkshare extends \CommonDBTM
{

    public static $rightname = "plugin_ocsinventoryng";

    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('Network shares', 'ocsinventoryng');
    }

    /**
     * Update config of the Networkshare
     *
     * This function erase old data and import the new ones about Networkshare
     *
     * @param $computers_id integer : glpi computer id.
     * @param $ocsComputer
     * @param $history_plugins boolean
     * @param $force
     */
    public static function updateNetworkshare($computers_id, $ocsComputer, $cfg_ocs, $force)
    {

        $uninstall_history = 0;
        if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_plugins'] == 1 || $cfg_ocs['history_plugins'] == 3)) {
            $uninstall_history = 1;
        }
        $install_history = 0;
        if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_plugins'] == 1 || $cfg_ocs['history_plugins'] == 2)) {
            $install_history = 1;
        }

        if ($force) {
            self::resetNetworkshare($computers_id, $uninstall_history);
        }

        $ocsShares = new self();

        //update data
        foreach ($ocsComputer as $sha) {

            $input = [];

            $input["computers_id"] = $computers_id;
            $input["drive"]        = $sha["DRIVE"];
            $input["path"]         = $sha["PATH"];
            $input["size"]         = $sha["SIZE"];
            $input["freespace"]    = $sha["FREESPACE"];
            $input["quota"]        = $sha["QUOTA"];
            $ocsShares->add($input, ['disable_unicity_check' => true], $install_history);
        }

    }


    public static function getTable($classname = null)
    {
        return "glpi_plugin_ocsinventoryng_networkshares";
    }
    /**
     * Delete old Networkshare entries
     *
     * @param $glpi_computers_id integer : glpi computer id.
     * @param $uninstall_history boolean
     *
     */
    public static function resetNetworkshare($glpi_computers_id, $uninstall_history)
    {

        $share = new self();
        $share->deleteByCriteria(['computers_id' => $glpi_computers_id], 1, $uninstall_history);

    }

    /**
     * @see CommonGLPI::getTabNameForItem()
     *
     * @param \CommonGLPI $item
     * @param int         $withtemplate
     *
     * @return array|string
     * @throws \GlpitestSQLError
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        $plugin_ocsinventoryng_ocsservers_id = Ocslink::getOCSServerForItem($item);
        if ($plugin_ocsinventoryng_ocsservers_id > 0
            && OcsServer::serverIsActive($plugin_ocsinventoryng_ocsservers_id)) {


            $cfg_ocs = OcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
            // can exists for template
            if (($item->getType() == 'Computer')
                && Computer::canView()
                && $cfg_ocs["import_networkshare"]) {
                $nb = 0;
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $dbu = new DbUtils();
                    $nb = $dbu->countElementsInTable(
                        'glpi_plugin_ocsinventoryng_networkshares',
                        ["computers_id" => $item->getID()]
                    );
                }
                return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
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
        // Rows stored in GLPI by the synchronization: no connection to the OCS server needed
        self::showForComputer($item, $withtemplate);
        return true;
    }

    /**
     * Print the computers windows update states
     *
     * @param             $comp                  Computer object
     * @param bool|string $withtemplate boolean  Template or basic item (default '')
     *
     * @return bool
*/
    public static function showForComputer(Computer $comp, $withtemplate = '')
    {
        global $DB;

        $ID = $comp->fields['id'];

        if (!$comp->getFromDB($ID)
            || !$comp->can($ID, READ)) {
            return false;
        }

        $rows = [];
        foreach ($DB->request([
            'FROM'  => 'glpi_plugin_ocsinventoryng_networkshares',
            'WHERE' => ['computers_id' => $ID],
            'ORDER' => 'drive',
        ]) as $data) {
            // Sizes reported in TiO by the agent, with a decimal comma
            $total   = (float) str_replace(",", ".", (string) $data['size']);
            $free    = (float) str_replace(",", ".", (string) $data['freespace']);
            $percent = $total > 0 ? round(100 * $free / $total, 0, PHP_ROUND_HALF_UP) : 0;
            // Values reported by the OCS agent: escaped by the template
            $rows[] = [
                'id'        => $data['id'],
                'drive'     => (string) $data['drive'],
                'path'      => (string) $data['path'],
                'size'      => Toolbox::getSize($total * 1024 * 1024 * 1024 * 1024),
                'freespace' => Toolbox::getSize($free * 1024 * 1024 * 1024 * 1024),
                'percent'   => Html::getProgressBar($percent),
                'quota'     => (string) $data['quota'],
            ];
        }

        Ocslink::showComponentRows(self::class, $comp, [
            'drive'     => _n('Drive', 'Drives', 1),
            'path'      => __('Path', 'ocsinventoryng'),
            'size'      => __('Global size'),
            'freespace' => __('Free size'),
            'percent'   => __('Free percentage'),
            'quota'     => __('Quota', 'ocsinventoryng'),
        ], $rows, ['percent']);

        return true;
    }
}
