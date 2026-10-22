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
use CommonGLPI;
use Computer;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Ocsinventoryng\Ocslink;
use GlpiPlugin\Ocsinventoryng\OcsServer;
use Link;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Teamviewer
 */
class Teamviewer extends \CommonDBTM
{
    public static $rightname = "plugin_ocsinventoryng";

    public static $tags = '[TWID]';

    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('Teamviewer access', 'ocsinventoryng');
    }

    /**
     * Update config of the Teamviewer
     *
     * This function erase old data and import the new ones about Teamviewer
     *
     * @param $computers_id integer : glpi computer id.
     * @param $ocsComputer
     * @param $history_plugins boolean
     * @param $force
     */
    public static function updateTeamviewer($computers_id, $ocsComputer, $cfg_ocs, $force)
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
            self::resetTeamviewer($computers_id, $uninstall_history);
        }

        $CompTeam              = new self();
        $input                 = [];
        $input["computers_id"] = $computers_id;
        $input["version"]      = $ocsComputer["VERSION"];
        $input["twid"]         = $ocsComputer["TWID"];

        $CompTeam->add($input, ['disable_unicity_check' => true], $install_history);
    }

    public static function getTable($classname = null)
    {
        return "glpi_plugin_ocsinventoryng_teamviewers";
    }
    /**
     * Delete old Teamviewer entries
     *
     * @param $glpi_computers_id integer : glpi computer id.
     * @param $uninstall_history boolean
     *
     */
    public static function resetTeamviewer($glpi_computers_id, $uninstall_history)
    {

        $team = new self();
        $team->deleteByCriteria(['computers_id' => $glpi_computers_id], 1, $uninstall_history);
    }

    /**
     * @see CommonGLPI::getTabNameForItem()
     *
     * @param \CommonGLPI $item
     * @param int         $withtemplate
     *
     * @return array|string
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
             && $cfg_ocs["import_teamviewer"]) {
                $nb = 0;
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $dbu = new DbUtils();
                    $nb  = $dbu->countElementsInTable(
                        'glpi_plugin_ocsinventoryng_teamviewers',
                        ["computers_id" => $item->getID()],
                    );
                }
                return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
            }
            return '';
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
     * @throws \GlpitestSQLError
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
            'FROM'  => 'glpi_plugin_ocsinventoryng_teamviewers',
            'WHERE' => ['computers_id' => $ID],
        ]) as $data) {
            // Values reported by the OCS agent: escaped by the template
            $rows[] = [
                'id'      => $data['id'],
                'twid'    => (string) $data['twid'],
                'version' => (string) $data['version'],
            ];
        }

        Ocslink::showComponentRows(self::class, $comp, [
            'twid'    => __('Teamviewer ID', 'ocsinventoryng'),
            'version' => __('Version'),
        ], $rows);

        $links = self::getDirectAccessLinks($comp);
        if (count($links) > 0) {
            TemplateRenderer::getInstance()->display('@ocsinventoryng/table.html.twig', [
                'title'         => __('Teamviewer direct access', 'ocsinventoryng'),
                'icon'          => 'ti ti-link',
                'columns'       => ['link' => ''],
                // Links escaped by Link::getAllLinksFor()
                'raw_columns'   => ['link'],
                'rows'          => array_map(fn($link) => ['link' => $link], $links),
                'empty_message' => '',
            ]);
        }

        return true;
    }

    /**
     * The Teamviewer direct access links of a computer (inventory information block)
     *
     * @param \Computer $comp
     *
     * @return string[] HTML links, escaped by Link::getAllLinksFor()
     */
    public static function getDirectAccessLinks(Computer $comp)
    {
        global $DB;

        $ID = $comp->fields['id'];

        if (!$comp->getFromDB($ID)
          || !$comp->can($ID, READ)) {
            return [];
        }

        $iterator = $DB->request([
            'FROM' => 'glpi_plugin_ocsinventoryng_teamviewers',
            'WHERE' => ['computers_id' => $ID]]);
        if (!count($iterator)) {
            return [];
        }

        $self = new self();
        $self->getFromDB($iterator->current()['id']);

        $links = [];
        foreach (self::getLinks($comp->getEntityID()) as $data) {
            array_push($links, ...Link::getAllLinksFor($self, $data));
        }

        return $links;
    }

    /**
     * External links of the Teamviewer items, visible in an entity
     *
     * @param int $entities_id
     *
     * @return \DBmysqlIterator
     */
    private static function getLinks($entities_id)
    {
        global $DB;

        return $DB->request([
            'SELECT'     => [
                'glpi_links.id',
                'glpi_links.link AS link',
                'glpi_links.name AS name',
                'glpi_links.data AS data',
                'glpi_links.open_window AS open_window',
            ],
            'FROM'       => 'glpi_links',
            'INNER JOIN' => [
                'glpi_links_itemtypes' => [
                    'ON' => [
                        'glpi_links'           => 'id',
                        'glpi_links_itemtypes' => 'links_id',
                    ],
                ],
            ],
            'WHERE'      => ['glpi_links_itemtypes.itemtype' => self::class]
                + getEntitiesRestrictCriteria('glpi_links', 'entities_id', $entities_id, true),
            'ORDER'      => 'name',
        ]);
    }

    /**
     * @param string     $link
     * @param CommonDBTM $item
     *
     * @return array
     */
    public static function generateLinkContents($link, CommonDBTM $item, bool $safe_url = true)
    {

        if (strstr($link, "[TWID]")) {
            $link = str_replace("[TWID]", $item->fields['twid'], $link);
            return [$link];
        }

        //        return parent::generateLinkContents($link, $item);
    }
}
