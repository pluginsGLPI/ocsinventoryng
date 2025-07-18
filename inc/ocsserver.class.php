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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/// OCS config class

/**
 * Class PluginOcsinventoryngOcsServer
 */
class PluginOcsinventoryngOcsServer extends CommonDBTM
{
    public static $types     = ['Computer'];
    public static $rightname = "plugin_ocsinventoryng";
    // From CommonDBTM
    public $dohistory = true;

    // Connection types
    public const CONN_TYPE_DB           = 0;
    public const CONN_TYPE_SOAP         = 1;
    public const OCS_VERSION_LIMIT      = 4020;
    public const OCS1_3_VERSION_LIMIT   = 5004;
    public const OCS2_VERSION_LIMIT     = 6000;
    public const OCS2_1_VERSION_LIMIT   = 7006;
    public const OCS2_2_VERSION_LIMIT   = 7009;
    public const OCS2_7_VERSION_LIMIT   = 7030;//https://github.com/OCSInventory-NG/OCSInventory-ocsreports/tree/2.6/files/update
    public const OCS2_8_VERSION_LIMIT   = 7034;//https://github.com/OCSInventory-NG/OCSInventory-ocsreports/tree/2.7/files/update
    public const ACTION_PURGE_COMPUTER  = 0; // Action cronCleanOldAgents : Purge computer
    public const ACTION_DELETE_COMPUTER = 1; // Action cronCleanOldAgents : delete computer

    /**
     * @param int $nb
     *
     * @return translated
     */
    public static function getTypeName($nb = 0)
    {
        return _n('OCSNG server', 'OCSNG servers', $nb, 'ocsinventoryng');
    }

    /**
     * @param CommonGLPI $item
     * @param int        $withtemplate
     *
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate) {
            switch ($item->getType()) {
                case __CLASS__:
                    //If connection to the OCS DB  is ok, and all rights are ok too
                    $ong[1] = self::createTabEntry(__('Test'));
                    if (self::checkOCSconnection($item->getID())
                        && self::checkVersion($item->getID())
                        && self::checkTraceDeleted($item->getID())
                    ) {
                        $ong[2] = self::createTabEntry(__('Datas to import', 'ocsinventoryng'));
                        $ong[3] = self::createTabEntry(__('Import options', 'ocsinventoryng'));
                        $ong[4] = self::createTabEntry(__('General history', 'ocsinventoryng'));
                    }
                    if ($item->getField('ocs_url')) {
                        $ong[5] = self::createTabEntry(__('OCSNG console', 'ocsinventoryng'));
                    }

                    return $ong;
            }
        }
        return '';
    }

    static function getIcon()
    {
        return "ti ti-server";
    }

    /**
     * @param CommonGLPI $item
     * @param int        $tabnum
     * @param int        $withtemplate
     *
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == __CLASS__) {
            switch ($tabnum) {
                case 1:
                    $item->showDBConnectionStatus($item->getID());
                    break;

                case 2:
                    $item->ocsFormConfig($item->getID());
                    break;

                case 3:
                    $item->ocsFormImportOptions($item->getID());
                    break;

                case 4:
                    $item->ocsHistoryConfig($item->getID());
                    break;

                case 5:
                    self::showOcsReportsConsole($item->getID());
                    break;
            }
        }
        return true;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(__CLASS__, $ong, $options);
        $this->addStandardTab('PluginOcsinventoryngSnmpOcslink', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);
        return $ong;
    }

    /**
     * @return array
     */
    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'   => 'common',
            'name' => self::getTypeName(2),
        ];

        $tab[] = [
            'id'            => '1',
            'table'         => $this->getTable(),
            'field'         => 'name',
            'name'          => __('Name'),
            'datatype'      => 'itemlink',
            'massiveaction' => false,
            'itemlink_type' => $this->getType(),
        ];

        $tab[] = [
            'id'    => '3',
            'table' => $this->getTable(),
            'field' => 'ocs_db_host',
            'name'  => __('Server'),
        ];

        $tab[] = [
            'id'       => '6',
            'table'    => $this->getTable(),
            'field'    => 'is_active',
            'name'     => __('Active'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id'            => '19',
            'table'         => $this->getTable(),
            'field'         => 'date_mod',
            'name'          => __('Last update'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'       => '16',
            'table'    => $this->getTable(),
            'field'    => 'comment',
            'name'     => __('Comments'),
            'datatype' => 'text',
        ];

        $tab[] = [
            'id'       => '17',
            'table'    => $this->getTable(),
            'field'    => 'use_massimport',
            'name'     => __('Expert sync mode', 'ocsinventoryng'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id'       => '18',
            'table'    => $this->getTable(),
            'field'    => 'ocs_db_utf8',
            'name'     => __('Database in UTF8', 'ocsinventoryng'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id'       => '30',
            'table'    => $this->getTable(),
            'field'    => 'id',
            'name'     => __('ID'),
            'datatype' => 'number',
        ];

        return $tab;
    }

    /**
     * Print ocs menu
     *
     * @param $plugin_ocsinventoryng_ocsservers_id Integer : Id of the ocs config
     *
     * @return void (display)
     * @throws \GlpitestSQLError
     */
    public static function setupMenu($plugin_ocsinventoryng_ocsservers_id)
    {
        global $CFG_GLPI, $DB;
        $name       = "";
        $ocsservers = [];

        $dbu                 = new DbUtils();
        $numberActiveServers = $dbu->countElementsInTable('glpi_plugin_ocsinventoryng_ocsservers', ["is_active" => 1]);
        if ($numberActiveServers > 0) {
            echo "<form action=\"" . PLUGIN_OCS_WEBDIR . "/front/ocsng.php\"
                method='post'>";
            echo "<div class='center'><table class='tab_cadre_fixe' width='40%'>";
            echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Choice of an OCSNG server', 'ocsinventoryng') .
                 "</th></tr>\n";

            echo "<tr class='tab_bg_2'><td class='center'>" . __('Name') . "</td>";
            echo "<td class='center'>";
            //            $query = "SELECT `glpi_plugin_ocsinventoryng_ocsservers`.`id`
            //                   FROM `glpi_plugin_ocsinventoryng_ocsservers_profiles`
            //                   LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers`
            //                      ON `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id`
            //                        = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
            //                   WHERE `profiles_id`= " . $_SESSION["glpiactiveprofile"]['id'] . "
            //                   AND `glpi_plugin_ocsinventoryng_ocsservers`.`is_active`= 1
            //                   ORDER BY `name` ASC";

            $iterator = $DB->request([
                'SELECT'    => [
                    'glpi_plugin_ocsinventoryng_ocsservers.id',
                ],
                'FROM'      => 'glpi_plugin_ocsinventoryng_ocsservers_profiles',
                'LEFT JOIN'       => [
                    'glpi_plugin_ocsinventoryng_ocsservers' => [
                        'ON' => [
                            'glpi_plugin_ocsinventoryng_ocsservers_profiles' => 'plugin_ocsinventoryng_ocsservers_id',
                            'glpi_plugin_ocsinventoryng_ocsservers'          => 'id',
                        ],
                    ],
                ],
                'WHERE'     => [
                    'profiles_id'  => $_SESSION["glpiactiveprofile"]['id'],
                    'is_active' => 1,
                ],
                'ORDERBY'   => 'name ASC',
            ]);

            if (count($iterator) > 0) {
                foreach ($iterator as $data) {
                    $ocsservers[] = $data['id'];
                }
            }

            Dropdown::show('PluginOcsinventoryngOcsServer',
                ["condition"           => ["id" => $ocsservers],
                "value"               => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                "on_change"           => "this.form.submit()",
                "display_emptychoice" => false]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_2'><td colspan='2'>";
            echo "<div class='alert alert-important alert-warning d-flex'>";
            echo __('If you not find your OCSNG server in this dropdown, please check if your profile can access it !', 'ocsinventoryng');
            echo "</div>";
            echo "</td></tr>";
            echo "</table></div>";
            Html::closeForm();
        }
        //        $sql      = "SELECT `name`, `is_active`
        //              FROM `glpi_plugin_ocsinventoryng_ocsservers`
        //              LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers_profiles`
        //                  ON `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id` = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
        //              WHERE `glpi_plugin_ocsinventoryng_ocsservers`.`id` = '" . $plugin_ocsinventoryng_ocsservers_id . "'
        //              AND `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`profiles_id`= '" . $_SESSION["glpiactiveprofile"]['id'] . "'";
        //        $result   = $DB->doQuery($sql);

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_plugin_ocsinventoryng_ocsservers.name',
                'glpi_plugin_ocsinventoryng_ocsservers.is_active',
            ],
            'FROM'      => 'glpi_plugin_ocsinventoryng_ocsservers',
            'LEFT JOIN'       => [
                'glpi_plugin_ocsinventoryng_ocsservers_profiles' => [
                    'ON' => [
                        'glpi_plugin_ocsinventoryng_ocsservers_profiles' => 'plugin_ocsinventoryng_ocsservers_id',
                        'glpi_plugin_ocsinventoryng_ocsservers'          => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_plugin_ocsinventoryng_ocsservers.id'  => $plugin_ocsinventoryng_ocsservers_id,
                'glpi_plugin_ocsinventoryng_ocsservers_profiles.profiles_id' => $_SESSION["glpiactiveprofile"]['id'],
            ],
            'ORDERBY'   => 'name ASC',
        ]);

        $isactive = 0;
        if (count($iterator) > 0) {
            foreach ($iterator as $datas) {
                $name = " : " . $datas["name"];
                $isactive = $datas["is_active"];
            }
        }

        $usemassimport = self::useMassImport();

        echo "<div class='center'><table class='tab_cadre_fixe' width='40%' cellpadding='10'>";
        if (Session::haveRight("plugin_ocsinventoryng", READ)) {
            echo "<tr><th colspan='4'>";
            printf(__('%1$s %2$s'), __('OCSNG server', 'ocsinventoryng'), $name);
            echo "</th></tr>";
            //config server
            if ($isactive) {
                echo "<tr class='tab_bg_1'><td class='center b' colspan='2'>
                  <a href='" . PLUGIN_OCS_WEBDIR . "/front/ocsserver.form.php?id=$plugin_ocsinventoryng_ocsservers_id'>
                    <i style='color:steelblue' class='fas fa-cogs fa-2x' 
                        title=\"" . __s('Configuration of OCSNG server', 'ocsinventoryng') . "\"></i>
                   <br><br>" . sprintf(__('Configuration of OCSNG server %s', 'ocsinventoryng'), $name) . "
                  </a></td>";

                if ($usemassimport && Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
                    //config massimport
                    echo "<td class='center b' colspan='2'>
                     <a href='" . PLUGIN_OCS_WEBDIR . "/front/config.form.php'>
                      <i style='color:steelblue' class='fas fa-sync-alt fa-2x' 
                        title=\"" . __s('Automatic synchronization\'s configuration', 'ocsinventoryng') . "\"></i>
                        <br><br>" . __("Automatic synchronization's configuration", 'ocsinventoryng') . "
                     </a></td>";
                } else {
                    echo "<td colspan='2'></td>";
                }
                echo "</tr>\n";
            }
        }

        echo "<tr><th colspan='4'>";
        echo __('Setup rules engine', 'ocsinventoryng');
        echo "</th></tr>";

        echo "<tr class='tab_bg_1'><td class='center b' colspan='2'>
            <a href='" . $CFG_GLPI["root_doc"] . "/front/ruleimportentity.php'>
            <i style='color:firebrick' class='fas fa-book fa-2x' 
                        title=\"" . __s('Rules for assigning an item to an entity') . "\"></i><br><br>" . __('Rules for assigning an item to an entity') . "</a>";
        echo "<br><span style='color:firebrick'>";
        echo __('Setup rules for choose entity on items import', 'ocsinventoryng');
        echo "</span></td>";

        echo "<td class='center b' colspan='2'>
            <a href='" . $CFG_GLPI["root_doc"] . "/front/ruleimportasset.php'>
         <i style='color:firebrick' class='fas fa-book fa-2x' 
                        title=\"" . __s('Rules for import and link equipments') . "\"></i><br><br>" . __('Rules for import and link equipments') . "</a>";
        echo "<br><span style='color:firebrick'>";
        echo __('Setup rules for select criteria for items link', 'ocsinventoryng');
        echo "</span></td>";

        echo "</tr>\n";

        echo "</table></div>";
    }


    /**
     * Print ocs menu
     *
     * @param $plugin_ocsinventoryng_ocsservers_id Integer : Id of the ocs config
     *
     * @return void (display)
     * @throws \GlpitestSQLError
     */
    public static function importMenu($plugin_ocsinventoryng_ocsservers_id)
    {
        global $DB;
        $name                = "";
        $ocsservers          = [];
        $dbu                 = new DbUtils();
        $numberActiveServers = $dbu->countElementsInTable(
            'glpi_plugin_ocsinventoryng_ocsservers',
            ["is_active" => 1]
        );
        if ($numberActiveServers > 0) {
            echo "<form action=\"" . PLUGIN_OCS_WEBDIR . "/front/ocsng.php\"
                method='post'>";
            echo "<div class='center'><table class='tab_cadre_fixe' width='40%' cellpadding='10'>";
            echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Choice of an OCSNG server', 'ocsinventoryng') .
                 "</th></tr>\n";

            echo "<tr class='tab_bg_2'><td class='center'>" . __('Name') . "</td>";
            echo "<td class='center'>";
            $iterator = $DB->request([
                'SELECT'    => [
                    'glpi_plugin_ocsinventoryng_ocsservers.id',
                ],
                'FROM'      => 'glpi_plugin_ocsinventoryng_ocsservers_profiles',
                'LEFT JOIN'       => [
                    'glpi_plugin_ocsinventoryng_ocsservers' => [
                        'ON' => [
                            'glpi_plugin_ocsinventoryng_ocsservers_profiles' => 'plugin_ocsinventoryng_ocsservers_id',
                            'glpi_plugin_ocsinventoryng_ocsservers'          => 'id',
                        ],
                    ],
                ],
                'WHERE'     => [
                    'profiles_id'  => $_SESSION["glpiactiveprofile"]['id'],
                    'is_active' => 1,
                ],
                'ORDERBY'   => 'name ASC',
            ]);

            if (count($iterator) > 0) {
                foreach ($iterator as $data) {
                    $ocsservers[] = $data['id'];
                }
            }
            Dropdown::show('PluginOcsinventoryngOcsServer', ["condition"           => ["id" => $ocsservers],
                "value"               => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                "on_change"           => "this.form.submit()",
                "display_emptychoice" => false]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_2'><td colspan='2'>";
            echo "<div class='alert alert-important alert-warning d-flex'>";
            echo __('If you not find your OCSNG server in this dropdown, please check if your profile can access it !', 'ocsinventoryng');
            echo "</div>";
            echo "</td></tr>";
            echo "</table></div>";
            Html::closeForm();
        }
        //        $sql      = "SELECT `name`, `is_active`
        //              FROM `glpi_plugin_ocsinventoryng_ocsservers`
        //              LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers_profiles`
        //                  ON `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id` = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
        //              WHERE `glpi_plugin_ocsinventoryng_ocsservers`.`id` = " . $plugin_ocsinventoryng_ocsservers_id . "
        //              AND `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`profiles_id`= '" . $_SESSION["glpiactiveprofile"]['id'] . "'";
        //        $result   = $DB->doQuery($sql);
        $isactive = 0;
        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_plugin_ocsinventoryng_ocsservers.name',
                'glpi_plugin_ocsinventoryng_ocsservers.is_active',
            ],
            'FROM'      => 'glpi_plugin_ocsinventoryng_ocsservers',
            'LEFT JOIN'       => [
                'glpi_plugin_ocsinventoryng_ocsservers_profiles' => [
                    'ON' => [
                        'glpi_plugin_ocsinventoryng_ocsservers_profiles' => 'plugin_ocsinventoryng_ocsservers_id',
                        'glpi_plugin_ocsinventoryng_ocsservers'          => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_plugin_ocsinventoryng_ocsservers.id'  => $plugin_ocsinventoryng_ocsservers_id,
                'glpi_plugin_ocsinventoryng_ocsservers_profiles.profiles_id' => $_SESSION["glpiactiveprofile"]['id'],
            ],
            'ORDERBY'   => 'name ASC',
        ]);

        $isactive = 0;
        if (count($iterator) > 0) {
            foreach ($iterator as $datas) {
                $name = " : " . $datas["name"];
                $isactive = $datas["is_active"];
            }
        }

        $usemassimport = self::useMassImport();

        echo "<div class='center'><table class='tab_cadre_fixe' width='40%' cellpadding='10'>";
        echo "<tr><th colspan='4'>";
        printf(__('%1$s %2$s'), __('OCSNG server', 'ocsinventoryng'), $name);
        echo "<br>";
        if (Session::haveRight("plugin_ocsinventoryng", READ)
            && $isactive) {
            echo "<a href='" . PLUGIN_OCS_WEBDIR . "/front/ocsserver.form.php?id=" . $plugin_ocsinventoryng_ocsservers_id . "&forcetab=PluginOcsinventoryngOcsServer\$2'>";
            echo __('See Setup : Datas to import before', 'ocsinventoryng');
            echo "</a>";
            echo "</th></tr>";
        }

        if (Session::haveRight("plugin_ocsinventoryng", READ)) {
            //config server
            if ($isactive) {
                if (Session::haveRight("plugin_ocsinventoryng_import", READ)) {
                    //manual import
                    echo "<tr class='tab_bg_1'>";
                    if (Session::haveRight("plugin_ocsinventoryng_import", READ)) {
                        echo "<td class='center b' colspan='2'>
                  <a href='" . PLUGIN_OCS_WEBDIR . "/front/ocsng.import.php'>";
                        echo "<i style='color:steelblue' class='fas fa-plus fa-2x' 
                           title=\"" . __s('Import or link new computers', 'ocsinventoryng') . "\"></i>";
                        echo "<br>" . __('Import or link new computers', 'ocsinventoryng') . "
                  </a></td>";
                    } else {
                        echo "<td class='center b' colspan='2'></td>";
                    }
                    if ($usemassimport && Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
                        //threads
                        echo "<td class='center b' colspan='2'>
                     <a href='" . PLUGIN_OCS_WEBDIR . "/front/thread.php?plugin_ocsinventoryng_ocsservers_id=" . $plugin_ocsinventoryng_ocsservers_id . "'>
                      <i style='color:steelblue' class='fas fa-play fa-2x' 
                           title=\"" . __s('Scripts execution of automatic actions', 'ocsinventoryng') . "\"></i>
                        <br>" . __('Scripts execution of automatic actions', 'ocsinventoryng') . "
                     </a></td>";
                    } else {
                        echo "<td colspan='2'></td>";
                    }
                    echo "</tr>\n";
                }
                //manual synchro
                if (Session::haveRight("plugin_ocsinventoryng_sync", READ)) {
                    echo "<tr class='tab_bg_1'>";
                    if (Session::haveRight("plugin_ocsinventoryng_sync", READ) && $isactive) {
                        echo "<td class='center b' colspan='2'>
                  <a href='" . PLUGIN_OCS_WEBDIR . "/front/ocsng.sync.php'>
                  <i style='color:steelblue' class='fas fa-sync-alt fa-2x' 
                           title=\"" . __s('Synchronize computers already imported', 'ocsinventoryng') . "\"></i>
                     <br>" . __('Synchronize computers already imported', 'ocsinventoryng') . "
                  </a></td>";
                    } else {
                        echo "<td class='center b' colspan='2'></td>";
                    }
                    $log = PluginOcsinventoryngConfig::logProcessedComputers();
                    if ($log && Session::haveRight("plugin_ocsinventoryng_import", READ) && $usemassimport) {
                        //host imported by thread
                        echo "<td class='center b' colspan='2'>
                     <a href='" . PLUGIN_OCS_WEBDIR . "/front/detail.php'>
                     <i style='color:green' class='fas fa-check fa-2x' 
                           title=\"" . __s('Computers imported by automatic actions', 'ocsinventoryng') . "\"></i>
                        <br>" . __('Computers imported by automatic actions', 'ocsinventoryng') . "
                     </a></td>";
                    } else {
                        echo "<td class='center b' colspan='2'></td>";
                    }
                    echo "</tr>\n";
                }
                //link
                if (Session::haveRight("plugin_ocsinventoryng", READ)) {
                    echo "<tr class='tab_bg_1'>";

                    echo "<td class='center b' colspan='2'></td>";

                    if ($usemassimport && Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
                        //host not imported by thread
                        echo "<td class='center b' colspan='2'>
                     <a href='" . PLUGIN_OCS_WEBDIR . "/front/notimportedcomputer.php'>
                     <i style='color:firebrick' class='fas fa-times fa-2x' 
                           title=\"" . __s('Computers not imported by automatic actions', 'ocsinventoryng') . "\"></i>
                        <br>" . __('Computers not imported by automatic actions', 'ocsinventoryng') . "
                     </a></td>";
                    } else {
                        echo "<td colspan='2'></td>";
                    }
                    echo "</tr>\n";
                }
            } else {
                echo "<tr class='tab_bg_2'><td class='center red' colspan='2'>";
                echo __('The selected server is not active. Import and synchronisation is not available', 'ocsinventoryng');
                echo "</td></tr>\n";
            }
        }

        if ((Session::haveRight("plugin_ocsinventoryng_clean", UPDATE)
             || Session::haveRight(static::$rightname, UPDATE))
            && $isactive) {
            echo "<tr class='tab_bg_1'>";
            if (Session::haveRight(static::$rightname, UPDATE)) {
                echo "<td class='center b' colspan='2'>";

                echo "<a href='" . PLUGIN_OCS_WEBDIR . "/front/deleted_equiv.php'>
            <i style='color:steelblue' class='fas fa-trash fa-2x' 
                           title=\"" . __s('Clean OCSNG deleted computers', 'ocsinventoryng') . "\"></i>
                  <br>" . __('Clean OCSNG deleted computers', 'ocsinventoryng') . "
               </a>";
                $ocsClient   = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
                $deleted_pcs = $ocsClient->getTotalDeletedComputers();
                if ($deleted_pcs > 0) {
                    echo "<br><span style='color:firebrick'>" . $deleted_pcs . " " . __('Pc deleted', 'ocsinventoryng');
                    echo "</span>";
                }
                echo "<br><span style='color:grey'>" . __('Update ID of deleted computers of OCSNG', 'ocsinventoryng');
                echo "</span>";
                echo "</td>";
            } else {
                echo "<td colspan='2'></td>";
            }
            echo "<td class='center b' colspan='2'>
               <a href='" . PLUGIN_OCS_WEBDIR . "/front/ocsng.clean.php'>
               <i style='color:steelblue' class='fas fa-broom fa-2x' 
                           title=\"" . __s('Clean OCSNG deleted computers', 'ocsinventoryng') . "\"></i>
                  <br>" . __('Clean links between GLPI and OCSNG', 'ocsinventoryng') . "
               </a>";
            echo "<br><span style='color:grey'>" . __('Drop links for not present computers into OCSNG', 'ocsinventoryng');
            echo "</span>";
            echo "</td><tr>";
        }
        echo "</table></div>";
    }

    /**
     * Print ocs config form
     *
     * @param $ID Integer : Id of the ocs config
     *
     * @return bool
     * @internal param form $target target
     */
    public function ocsFormConfig($ID)
    {
        if (!Session::haveRight("plugin_ocsinventoryng", READ)) {
            return false;
        }
        $this->getFromDB($ID);
        echo "<div class='center'>";
        echo "<form name='formconfig' id='formconfig' action='" . Toolbox::getItemTypeFormURL("PluginOcsinventoryngOcsServer") . "' method='post'>";
        echo "<table class='tab_cadre_fixe'>\n";
        echo "<tr><th>";
        echo __('All');

        echo $JS = <<<JAVASCRIPT
         <script type='text/javascript'>
            function form_init_all(value) {
                if(value != -1) {
                  var selects = $("form[id='formconfig'] select");

                  $.each(selects, function(index, select){
                     if (select.name != "init_all"
                         && select.name != "import_otherserial"
                           && select.name != "import_location"
                              && select.name != "import_group"
                                 && select.name != "import_contact_num"
                                    && select.name != "import_use_date"
                                       && select.name != "import_network") {
                       $(select).select2('val', value);
                     }
                  });
               }
            }
         </script>
JAVASCRIPT;

        $values = [-1 => Dropdown::EMPTY_VALUE,
            0  => __('No'),
            1  => __('Yes')];

        Dropdown::showFromArray('init_all', $values, [
            'width'     => '10%',
            'on_change' => "form_init_all(this.value);",
        ]);
        echo "</th></tr>";

        echo "<tr class='tab_bg_2'>\n";
        echo "<td class='top'>\n";
        echo Html::css(PLUGIN_OCS_NOTFULL_DIR . "/lib/jquery-ui/jquery-ui.min.css");
        echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/jquery-ui/jquery-ui.min.js");
        echo $JS = <<<JAVASCRIPT
         <script type='text/javascript'>
         function accordion(id, openall) {
             if(id == undefined){
                 id  = 'accordion';
             }
             jQuery(document).ready(function () {
                 $("#"+id).accordion({
                     collapsible: true,
                     //active:[0, 1, 2, 3],
                     heightStyle: "content"
                 });
                 //if (openall) {
                     //$('#'+id +' .ui-accordion-content').show();
                 //}
             });
         };
         </script>
JAVASCRIPT;

        echo "<div id='accordion'>";

        echo "<h2><a href='#'>" . __('General information', 'ocsinventoryng') . "</a></h2>";

        echo "<div>";
        echo "<table class='tab_cadre' width='100%'>";
        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='4'>";
        echo Html::hidden('id', ['value' => $ID]);
        echo "<div class='alert alert-important alert-warning d-flex'>";
        echo __('Warning : the import entity rules depends on selected fields', 'ocsinventoryng');
        echo "</div>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Name') . "</td>\n<td>";
        Dropdown::showYesNo("import_general_name", $this->fields["import_general_name"]);
        echo "</td>\n";
        echo "<td class='center'>" . __('Operating system') . "</td>\n<td>";
        Dropdown::showYesNo("import_general_os", $this->fields["import_general_os"]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . __('Serial of the operating system') . "</td>\n<td>";
        Dropdown::showYesNo("import_os_serial", $this->fields["import_os_serial"]);
        echo "</td>\n";
        echo "<td class='center'>" . __('Serial number') . "</td>\n<td>";
        Dropdown::showYesNo("import_general_serial", $this->fields["import_general_serial"]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('Depends on Bios import', 'ocsinventoryng')));
        echo "&nbsp;</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Model') . "</td>\n<td>";
        Dropdown::showYesNo("import_general_model", $this->fields["import_general_model"]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('Depends on Bios import', 'ocsinventoryng')));
        echo "&nbsp;</td>\n";
        echo "<td class='center'>" . _n('Manufacturer', 'Manufacturers', 1) . "</td>\n<td>";
        Dropdown::showYesNo("import_general_manufacturer", $this->fields["import_general_manufacturer"]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('Depends on Bios import', 'ocsinventoryng')));
        echo "&nbsp;</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Type') . "</td>\n<td>";
        Dropdown::showYesNo("import_general_type", $this->fields["import_general_type"]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('Depends on Bios import', 'ocsinventoryng')));
        echo "&nbsp;</td>\n";
        echo "<td class='center'>" . __('Domain') . "</td>\n<td>";
        Dropdown::showYesNo("import_general_domain", $this->fields["import_general_domain"]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center'>" . __('Comments') . "</td>\n<td>";
        Dropdown::showYesNo("import_general_comment", $this->fields["import_general_comment"]);
        echo "</td>\n";

        if (self::checkVersion($ID)) {
            echo "<td class='center'>" . __('UUID') . "</td>\n<td>";
            Dropdown::showYesNo("import_general_uuid", $this->fields["import_general_uuid"]);
        } else {
            echo "<td class='center'>";
            echo Html::hidden('import_general_uuid', ['value' => 0]);
        }
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('IP') . "</td>\n<td>";
        Dropdown::showYesNo("import_ip", $this->fields["import_ip"]);
        echo "</td>\n";

        echo "<td colspan='2'></td></tr>\n";

        echo "<tr class='tab_bg_2'>";
        echo "<th colspan='4'>" . __('User informations', 'ocsinventoryng');
        echo "</th></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Alternate username') . "</td>\n<td>";
        Dropdown::showYesNo("import_general_contact", $this->fields["import_general_contact"]);
        echo "</td>";
        echo "<td class='center'>" . __('Link with user', 'ocsinventoryng') . "</td>\n<td>";
        $import_array = ["0" => __('No import'),
            "1" => __('From user login / contact', 'ocsinventoryng')];
        Dropdown::showFromArray("link_with_user", $import_array, ['value' => $this->fields["link_with_user"]]);
        echo "</td>";
        echo "</tr>\n";

        if ($this->fields["link_with_user"] > 0) {
            echo "<td colspan='2'></td>";
            echo "<td class='center'>" . __('Affect default group of user by default', 'ocsinventoryng') . "</td>\n<td>";
            Dropdown::showYesNo("import_user_group_default", $this->fields["import_user_group_default"]);
            echo "</td>";
            echo "</tr>\n";
        }


        if ($this->fields["link_with_user"] > 0) {
            echo "<tr class='tab_bg_2'><td class='center'>" . __('Affect user location by default', 'ocsinventoryng') . "</td>\n<td>";
            Dropdown::showYesNo("import_user_location", $this->fields["import_user_location"]);
            echo "</td>\n";
            echo "<td class='center'>" . __('Affect first group of user by default', 'ocsinventoryng') . "</td>\n<td>";
            Dropdown::showYesNo("import_user_group", $this->fields["import_user_group"]);
            echo "</td></tr>\n";
        }
        echo "</table>";
        echo "</div>";

        //Components

        echo "<h2><a href='#'>" . _n('Component', 'Components', 2) . "</a></h2>";

        echo "<div>";
        echo "<table class='tab_cadre' width='100%'>";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Processor') . "</td>\n<td>";
        Dropdown::showYesNo("import_device_processor", $this->fields["import_device_processor"]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('After 7006 version of OCS Inventory NG', 'ocsinventoryng')));
        echo "&nbsp;</td>\n";
        echo "<td class='center'>" . __('Memory') . "</td>\n<td>";
        Dropdown::showYesNo("import_device_memory", $this->fields["import_device_memory"]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Hard drive') . "</td>\n<td>";
        Dropdown::showYesNo("import_device_hdd", $this->fields["import_device_hdd"]);
        echo "</td>\n";
        echo "<td class='center'>" . __('Network card') . "</td>\n<td>";
        Dropdown::showYesNo("import_device_iface", $this->fields["import_device_iface"]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Graphics card') . "</td>\n<td>";
        Dropdown::showYesNo("import_device_gfxcard", $this->fields["import_device_gfxcard"]);
        echo "&nbsp;&nbsp;</td>";
        echo "<td class='center'>" . __('Soundcard') . "</td>\n<td>";
        Dropdown::showYesNo("import_device_sound", $this->fields["import_device_sound"]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . _n('Drive', 'Drives', 2) . "</td>\n<td>";
        Dropdown::showYesNo("import_device_drive", $this->fields["import_device_drive"]);
        echo "</td>\n";
        echo "<td class='center'>" . __('Modems') . "</td>\n<td>";
        Dropdown::showYesNo("import_device_modem", $this->fields["import_device_modem"]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . _n('Port', 'Ports', 2) . "</td>\n<td>";
        Dropdown::showYesNo("import_device_port", $this->fields["import_device_port"]);
        echo "</td>\n";
        echo "<td class='center'>" . __('Bios') . "</td>\n<td>";
        Dropdown::showYesNo("import_device_bios", $this->fields["import_device_bios"]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('System board') . "</td>\n<td>";
        Dropdown::showYesNo("import_device_motherboard", $this->fields["import_device_motherboard"]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('After 7009 version of OCS Inventory NG && Depends on Bios import', 'ocsinventoryng')));
        echo "&nbsp;</td>\n";
        echo "<td class='center'>" . _n('Controller', 'Controllers', 2) . "</td>\n<td>";
        Dropdown::showYesNo("import_device_controller", $this->fields["import_device_controller"]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Other component', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showYesNo("import_device_slot", $this->fields["import_device_slot"]);
        echo "</td><td colspan='2'></td></tr>\n";

        echo "</table>";
        echo "</div>";

        //Linked objects

        echo "<h2><a href='#'>" . __('Linked objects', 'ocsinventoryng') . "</a></h2>";

        echo "<div>";
        echo "<table class='tab_cadre' width='100%'>";

        $import_array = ["0" => __('No import'),
            "1" => __('Global import', 'ocsinventoryng'),
            "2" => __('Unit import', 'ocsinventoryng')];

        $import_array2 = ["0" => __('No import'),
            "1" => __('Global import', 'ocsinventoryng'),
            "2" => __('Unit import', 'ocsinventoryng'),
            "3" => __('Unit import on serial number', 'ocsinventoryng'),
            "4" => __('Unit import serial number only', 'ocsinventoryng')];

        $periph   = $this->fields["import_periph"];
        $monitor  = $this->fields["import_monitor"];
        $printer  = $this->fields["import_printer"];
        $software = $this->fields["import_software"];

        echo "<tr class='tab_bg_2'><td class='center'>" . _n('Device', 'Devices', 2) . " </td>\n<td>";
        Dropdown::showFromArray("import_periph", $import_array, ['value' => $periph]);
        echo "</td>\n";
        echo "<td class='center'>" . _n('Monitor', 'Monitors', 2) . "</td>\n<td>";
        Dropdown::showFromArray("import_monitor", $import_array2, ['value' => $monitor]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Comments') . " " . _n('Monitor', 'Monitors', 2) . " </td>\n<td>";
        Dropdown::showYesNo("import_monitor_comment", $this->fields["import_monitor_comment"]);
        echo "</td>\n";
        echo "<td class='center'>" . _n('Printer', 'Printers', 2) . "</td>\n<td>";
        Dropdown::showFromArray("import_printer", $import_array, ['value' => $printer]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . _n('Software', 'Software', 2) . "</td>\n<td>";
        $import_array = ["0" => __('No import'),
            "1" => __('Unit import', 'ocsinventoryng')];
        Dropdown::showFromArray("import_software", $import_array, ['value' => $software]);
        echo "</td>\n";
        echo "<td class='center'>" . _n('Volume', 'Volumes', 2) . "</td>\n<td>";
        Dropdown::showYesNo("import_disk", $this->fields["import_disk"]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Registry', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showYesNo("import_registry", $this->fields["import_registry"]);
        echo "</td>\n";
        //check version
        if ($this->fields['ocs_version'] > self::OCS1_3_VERSION_LIMIT) {
            echo "<td class='center'>" .
                 _n('Virtual machine', 'Virtual machines', 2) . "</td>\n<td>";
            Dropdown::showYesNo("import_vms", $this->fields["import_vms"]);
            echo "</td>\n";
        } else {
            echo "<td class='center'>";
            echo Html::hidden('import_vms', ['value' => 0]);
            echo "</td>\n";
        }
        echo "</tr>\n";

        echo "<tr class='tab_bg_2'><td colspan='4'>";
        echo "<div class='alert alert-important alert-warning d-flex'>";
        echo __('No import: the plugin will not import these elements', 'ocsinventoryng');
        echo "<br>" . __('Global import: everything is imported but the material is globally managed (without duplicate)', 'ocsinventoryng');
        echo "<br>" . __("Unit import: everything is imported as it is", 'ocsinventoryng');
        echo "</div>";
        echo "</td></tr>";

        echo "</table>";
        echo "</div>";

        //Linked objects

        echo "<h2><a href='#'>" . __('OCS Inventory NG plugins', 'ocsinventoryng') . "</a></h2>";

        echo "<div>";
        echo "<table class='tab_cadre' width='100%'>";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Microsoft Office licenses', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showYesNo("import_officepack", $this->fields["import_officepack"]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('Depends on software import and OfficePack Plugin for (https://github.com/PluginsOCSInventory-NG/officepack) OCSNG must be installed', 'ocsinventoryng')));
        echo "</td><td class='center'>" . __('Antivirus', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showYesNo("import_antivirus", $this->fields["import_antivirus"]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('Security Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/security) must be installed', 'ocsinventoryng')));
        echo "&nbsp;</td>\n";
        echo "</tr>";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Uptime', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showYesNo("import_uptime", $this->fields["import_uptime"]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('Uptime Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/uptime) must be installed', 'ocsinventoryng')));
        echo "&nbsp;</td><td class='center'>" . __('Windows Update State', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showYesNo("import_winupdatestate", $this->fields["import_winupdatestate"]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('Winupdate Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/winupdate) must be installed', 'ocsinventoryng')));
        echo "&nbsp;</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Teamviewer', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showYesNo("import_teamviewer", $this->fields["import_teamviewer"]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('Teamviewer Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/teamviewer) must be installed', 'ocsinventoryng')));
        echo "&nbsp;</td><td class='center'>" . __('Proxy Settings', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showYesNo("import_proxysetting", $this->fields["import_proxysetting"]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('Navigator Proxy Setting Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/navigatorproxysetting) must be installed', 'ocsinventoryng')));
        echo "&nbsp;</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Windows Users', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showYesNo("import_winusers", $this->fields["import_winusers"]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('Winusers Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/winusers) must be installed', 'ocsinventoryng')));
        echo "&nbsp;</td>\n";
        echo "<td class='center'>" . __('OS Informations', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showYesNo("import_osinstall", $this->fields["import_osinstall"]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('OSInstall Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/osinstall) must be installed', 'ocsinventoryng')));
        echo "&nbsp;</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Network shares', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showYesNo("import_networkshare", $this->fields["import_networkshare"]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('Networkshare Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/networkshare) must be installed', 'ocsinventoryng')));
        echo "&nbsp;</td>\n";
        echo "<td class='center'>" . __('Service', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showYesNo("import_service", $this->fields["import_service"]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('Service Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/services) must be installed', 'ocsinventoryng')));
        echo "&nbsp;</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Running Process', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showYesNo("import_runningprocess", $this->fields["import_runningprocess"]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('Running Process Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/runningProcess) must be installed', 'ocsinventoryng')));
        echo "&nbsp;</td>\n";
        echo "<td class='center'>" . __('Customapp', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showYesNo("import_customapp", $this->fields["import_customapp"]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('Customapp Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/customapp) must be installed', 'ocsinventoryng')));
        echo "&nbsp;</td></tr>\n";
        echo "<tr class='tab_bg_2'><td class='center'>" . __('Bitlocker', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showYesNo("import_bitlocker", $this->fields["import_bitlocker"]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('Bitlocker Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/bitlocker) must be installed', 'ocsinventoryng')));
        echo "&nbsp;</td></tr>\n";

        echo "</table>";
        echo "</div>";

        //Administrative information

        echo "<h2><a href='#'>" . __('OCSNG administrative information', 'ocsinventoryng') . "</a></h2>";

        echo "<div>";
        echo "<table class='tab_cadre' width='100%'>";

        $opt                 = PluginOcsinventoryngOcsAdminInfosLink::getColumnListFromAccountInfoTable($ID, 'accountinfo');
        $oserial             = $opt;
        $oserial['ASSETTAG'] = "ASSETTAG";
        echo "<table class='tab_cadre' width='100%'>";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Inventory number') . " </td>\n";
        echo "<td>";
        $link = new PluginOcsinventoryngOcsAdminInfosLink();
        $link->getFromDBbyOcsServerIDAndGlpiColumn($ID, "otherserial");

        $value = ($link->fields["ocs_column"] ?? "");
        Dropdown::showFromArray("import_otherserial", $oserial, ['value' => $value,
            'width' => '100%']);
        echo "</td>\n";
        echo "<td class='center'>" . __('Location') . " </td>\n";
        echo "<td>";
        $link = new PluginOcsinventoryngOcsAdminInfosLink();
        $link->getFromDBbyOcsServerIDAndGlpiColumn($ID, "locations_id");

        $value = ($link->fields["ocs_column"] ?? "");
        Dropdown::showFromArray("import_location", $opt, ['value' => $value,
            'width' => '100%']);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Group') . " </td>\n";
        echo "<td>";
        $link = new PluginOcsinventoryngOcsAdminInfosLink();
        $link->getFromDBbyOcsServerIDAndGlpiColumn($ID, "groups_id");

        $value = ($link->fields["ocs_column"] ?? "");
        Dropdown::showFromArray("import_group", $opt, ['value' => $value,
            'width' => '100%']);

        echo "</td>\n";
        echo "<td class='center'>" . __('Alternate username number') . " </td>\n";
        echo "<td>";
        $link = new PluginOcsinventoryngOcsAdminInfosLink();
        $link->getFromDBbyOcsServerIDAndGlpiColumn($ID, "contact_num");

        $value = ($link->fields["ocs_column"] ?? "");
        Dropdown::showFromArray("import_contact_num", $opt, ['value' => $value,
            'width' => '100%']);

        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Network') . " </td>\n";
        echo "<td>";
        $link = new PluginOcsinventoryngOcsAdminInfosLink();
        $link->getFromDBbyOcsServerIDAndGlpiColumn($ID, "networks_id");

        $value = ($link->fields["ocs_column"] ?? "");
        Dropdown::showFromArray("import_network", $opt, ['value' => $value,
            'width' => '100%']);

        echo "</td>\n";
        $opt_date = PluginOcsinventoryngOcsAdminInfosLink::getColumnListFromAccountInfoTable($ID, 'hardware');
        echo "<td class='center'>" . __('Startup date') . " </td>\n";
        echo "<td>";
        $link = new PluginOcsinventoryngOcsAdminInfosLink();
        $link->getFromDBbyOcsServerIDAndGlpiColumn($ID, "use_date");

        $value = ($link->fields["ocs_column"] ?? "");
        Dropdown::showFromArray("import_use_date", $opt_date, ['value' => $value,
            'width' => '100%']);
        echo "</td></tr>\n";

        echo "</table>";
        echo "</div>";

        echo "</div>";

        echo "<script>accordion();</script>";

        echo "</td></tr>\n";

        if (Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
            echo "<tr class='tab_bg_2 center'><td colspan='4'>";
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
            echo "</td></tr>\n";
        }
        echo "</table>\n";
        Html::closeForm();
        echo "</div>\n";
    }

    public static function getHistoryValues()
    {
        $values = [__('None'),
            __('Installation / Update / Uninstallation', 'ocsinventoryng'),
            __('Installation / Update', 'ocsinventoryng'),
            __('Uninstallation', 'ocsinventoryng')];

        return $values;
    }

    public function showHistoryDropdown($name)
    {
        $value  = $this->fields[$name];
        $values = self::getHistoryValues();
        return Dropdown::showFromArray($name, $values, ['value' => $value]);
    }


    /**
     * Print ocs history form
     *
     * @param $ID Integer : Id of the ocs config
     *
     *
     * @return bool
     */
    public function ocsHistoryConfig($ID)
    {
        if (!Session::haveRight("plugin_ocsinventoryng", READ)) {
            return false;
        }
        $this->getFromDB($ID);
        echo "<div class='center'>";
        echo "<form name='historyconfig' id='historyconfig' action='" . Toolbox::getItemTypeFormURL("PluginOcsinventoryngOcsServer") . "' method='post'>";
        echo "<table class='tab_cadre_fixe'>\n";
        echo "<tr><th colspan ='4'>";
        echo __('All');

        echo $JS = <<<JAVASCRIPT
         <script type='text/javascript'>
            function form_init_all(value) {
                if(value != -1) {
                  var selects = $("form[id='historyconfig'] select");

                  $.each(selects, function(index, select){
                     if (select.name != "init_all") {
                       $(select).select2('val', value);
                     }
                  });
               }
            }
         </script>
JAVASCRIPT;
        $values = [-1 => Dropdown::EMPTY_VALUE,
            0  => __('No'),
            1  => __('Yes')];

        Dropdown::showFromArray('init_all', $values, [
            'width'     => '10%',
            'on_change' => "form_init_all(this.value);",
        ]);

        echo "</th></tr>";
        echo "<tr><th colspan='4'>";
        echo Html::hidden('id', ['value' => $ID]) . __('General history', 'ocsinventoryng') .
             "</th>\n";
        echo "</tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Do history', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showYesNo("dohistory", $this->fields["dohistory"]);
        echo "</td>\n";

        echo "<td class='center'>" . __('System history', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showYesNo("history_hardware", $this->fields["history_hardware"]);
        echo "&nbsp;";
        $fields = __('Operating system');
        $fields .= "<br>";
        $fields .= __('Serial of the operating system');
        $fields .= "<br>";
        $fields .= __('Domain');
        $fields .= "<br>";
        $fields .= __('Alternate username');
        $fields .= "<br>";
        $fields .= __('Comments');
        Html::showToolTip(nl2br($fields));
        echo "&nbsp;</td></tr>\n";

        //history_bios
        echo "<tr class='tab_bg_2'><td class='center'>" . __('Bios history', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showYesNo("history_bios", $this->fields["history_bios"]);
        echo "&nbsp;";
        $fields = __('Serial number');
        $fields .= "<br>";
        $fields .= __('Model');
        $fields .= "<br>";
        $fields .= _n('Manufacturer', 'Manufacturers', 1);
        $fields .= "<br>";
        $fields .= __('Type');
        $fields .= "<br>";
        if (self::checkVersion($ID)) {
            $fields .= __('UUID');
        }
        Html::showToolTip(nl2br($fields));
        echo "&nbsp;</td>\n";

        echo "<td class='center'>" . __('Devices history', 'ocsinventoryng') . "</td>\n<td>";
        $this->showHistoryDropdown("history_devices");
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Volumes history', 'ocsinventoryng') . "</td>\n<td>";
        $this->showHistoryDropdown("history_drives");
        echo "</td>\n";

        echo "<td class='center'>" . __('Network history', 'ocsinventoryng') . "</td>\n<td>";
        $this->showHistoryDropdown("history_network");
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Monitor connection history', 'ocsinventoryng') . "</td>\n<td>";
        $this->showHistoryDropdown("history_monitor");
        echo "</td>\n";

        echo "<td class='center'>" . __('Printer connection history', 'ocsinventoryng') . "</td>\n<td>";
        $this->showHistoryDropdown("history_printer");
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Peripheral connection history', 'ocsinventoryng') . "</td>\n<td>";
        $this->showHistoryDropdown("history_peripheral");
        echo "</td>\n";

        echo "<td class='center'>" . __('Software connection history', 'ocsinventoryng') . "</td>\n<td>";
        $this->showHistoryDropdown("history_software");

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Virtual machines history', 'ocsinventoryng') . "</td>\n<td>";
        $this->showHistoryDropdown("history_vm");

        echo "<td class='center'>" . __('Administrative infos history', 'ocsinventoryng') . "</td>\n<td>";
        $this->showHistoryDropdown("history_admininfos");

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Plugins history', 'ocsinventoryng') . "</td><td>";
        $this->showHistoryDropdown("history_plugins");
        echo "</td>\n";

        echo "<td class='center'>" . __('OS history', 'ocsinventoryng') . "</td>\n<td>";
        $this->showHistoryDropdown("history_os");
        echo "</td>\n";

        echo "</tr>";

        if (Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
            echo "<tr class='tab_bg_2 center'><td colspan='4'>";
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
            echo "</td></tr>\n";
        }
        echo "</table>\n";
        Html::closeForm();
        echo "</div>\n";
    }

    /**
     * @param $ID
     *
     * @internal param $withtemplate (default '')
     * @internal param $templateid (default '')
     */
    public function ocsFormImportOptions($ID)
    {
        $this->getFromDB($ID);
        echo "<div class='center'>";
        echo "<form name='formconfig' action='" . Toolbox::getItemTypeFormURL("PluginOcsinventoryngOcsServer") . "' method='post'>";
        echo "<table class='tab_cadre_fixe'>\n";
        echo "<tr class='tab_bg_2'><td class='center'>" . __('Web address of the OCSNG console', 'ocsinventoryng');
        echo Html::hidden('id', ['value' => $ID]);
        echo "<td>";
        echo Html::input('ocs_url', ['type'  => 'text',
            'value' => $this->fields["ocs_url"],
            'size'  => 30]);
        echo "</td></tr>\n";

        echo "<tr><th colspan='2'>" . __('Import options', 'ocsinventoryng') . "</th></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" .
             __('Limit the import to the following tags (separator $, nothing for all)', 'ocsinventoryng') . "</td>\n";
        echo "<td>";
        echo Html::input('tag_limit', ['type'  => 'text',
            'value' => $this->fields["tag_limit"],
            'size'  => 30]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'><td class='center'>" .
             __('Exclude the following tags (separator $, nothing for all)', 'ocsinventoryng') .
             "</td>\n";
        echo "<td>";
        echo Html::input('tag_exclude', ['type'  => 'text',
            'value' => $this->fields["tag_exclude"],
            'size'  => 30]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='4'>";
        echo "<div class='alert alert-important alert-warning d-flex'>";
        echo __("Define the action to do on link with other objects when computer is disconnecting from them", 'ocsinventoryng');
        echo "</div>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Behavior when disconnecting', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showFromArray("deconnection_behavior", [''       => __('Preserve link', 'ocsinventoryng'),
            "trash"  => __('Put the link in dustbin and add a lock', 'ocsinventoryng'),
            "delete" => __('Delete  the link permanently', 'ocsinventoryng')], ['value' => $this->fields["deconnection_behavior"]]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='4'>";
        echo "<div class='alert alert-important alert-warning d-flex'>";
        echo __("If Use the OCSNG software dictionary parameter is checked, no software will be imported before you setup them into OCS", 'ocsinventoryng');
        echo "</div>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'><td class='center'>" . __('Use the OCSNG software dictionary', 'ocsinventoryng') . "</td>\n<td>";
        Dropdown::showYesNo("use_soft_dict", $this->fields["use_soft_dict"]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='4'>";
        echo "<div class='alert alert-important alert-warning d-flex'>";
        echo __("The automatic task ocsng is launched only if server is not in expert mode", 'ocsinventoryng');
        echo "<br>" . __("The automatic task ocsng only synchronize existant computers, it doesn't import new computers", 'ocsinventoryng');
        echo "<br>" . __("If you want to import new computers, disable this parameter, change to expert mode and use script from system", 'ocsinventoryng');
        echo "</div>";
        echo "</td></tr>";


        echo "<tr class='tab_bg_2'><td class='center'>" .
             __('Number of items to synchronize via the automatic OCSNG action', 'ocsinventoryng') .
             "</td>\n<td>";
        Dropdown::showNumber('cron_sync_number', ['value' => $this->fields['cron_sync_number'],
            'min'   => 1,
            'toadd' => [0 => __('None')]]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'><td class='center'>" .
             __('Behavior to the deletion of a computer in OCSNG', 'ocsinventoryng') . "</td>";
        echo "<td>";
        $actions[0] = Dropdown::EMPTY_VALUE;
        $actions[1] = __('Put in trashbin');
        $dbu        = new DbUtils();
        foreach ($dbu->getAllDataFromTable('glpi_states') as $state) {
            $actions['STATE_' . $state['id']] = sprintf(__('Change to state %s', 'ocsinventoryng'), $state['name']);
        }
        Dropdown::showFromArray('deleted_behavior', $actions, ['value' => $this->fields['deleted_behavior']]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
        if (Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
            echo "</td></tr>";
        }
        echo "</table>\n";
        Html::closeForm();
        echo "</div>";
    }

    /**
     *
     */
    public function post_getEmpty()
    {
        $this->fields['ocs_db_host'] = "localhost";
        $this->fields['ocs_db_name'] = "ocsweb";
        $this->fields['ocs_db_user'] = "ocsuser";
        $this->fields['ocs_db_utf8'] = 1;
        $this->fields['is_active']   = 1;
        $this->fields['use_locks']   = 1;
    }

    /**
     * Print simple ocs config form (database part)
     *
     * @param $ID        integer : Id of the ocs config
     * @param $options   array
     *     - target form target
     *
     * @return void (display)
     */
    public function showForm($ID, $options = [])
    {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        $conn_type_values = [
            0 => __('Database', 'ocsinventoryng'),
            //1 => __('Webservice (SOAP)', 'ocsinventoryng'),
        ];

        $sync_method_values = [
            0 => __("Standard (allow manual actions)", "ocsinventoryng"),
            1 => __("Expert (Fully automatic, for large configuration)", "ocsinventoryng"),
        ];

        echo "<tr class='tab_bg_1'>";
        echo "<td class='center'>" . __('Connection type', 'ocsinventoryng') . "</td>";
        echo "<td id='conn_type_container'>";
        Dropdown::showFromArray(
            'conn_type',
            $conn_type_values,
            ['value' => $this->fields['conn_type']]
        );
        echo "</td>";
        echo "<td class='center'>" . __("Active") . "</td>";
        echo "<td>";
        Dropdown::showYesNo("is_active", $this->isActive());
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='center'>" . __("Name") . "</td>";
        echo "<td>";
        echo Html::input('name', ['type'  => 'text',
            'value' => $this->fields["name"]]);
        echo "</td>";
        echo "<td class='center'>";
        if ($ID) {
            printf(__('%1$s : %2$s'), _n("Version", "Versions", 1), $this->fields["ocs_version"]);
        }
        echo "</td>";
        echo "<td>";
        if ($ID) {
            printf(__('%1$s : %2$s'), "Checksum", $this->fields["checksum"]);
            echo "&nbsp;";
            Html::showSimpleForm(
                Toolbox::getItemTypeFormURL("PluginOcsinventoryngOcsServer"),
                'force_checksum',
                _sx('button', 'Reload Checksum', 'ocsinventoryng'),
                ['id' => $ID]
            );
        }
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='center'>" . __("Host", "ocsinventoryng") . "</td>";
        echo "<td>";
        echo Html::input('ocs_db_host', ['type'  => 'text',
            'value' => $this->fields["ocs_db_host"]]);
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('Like http://127.0.0.1 for SOAP method', 'ocsinventoryng')));
        echo "</td>";
        echo "<td class='center'>" . __("Synchronisation method", "ocsinventoryng") . "</td>";
        echo "<td>";
        Dropdown::showFromArray('use_massimport', $sync_method_values, ['value' => $this->fields['use_massimport']]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1 hide_if_soap' ";
        if ($this->fields["conn_type"] == self::CONN_TYPE_SOAP) {
            echo "style='display:none'";
        }
        echo "><td class='center'>" . __("Database") . "</td>";
        echo "<td>";
        echo Html::input('ocs_db_name', ['type'  => 'text',
            'value' => $this->fields["ocs_db_name"]]);
        echo "</td>";
        echo "<td class='center'>" . __("Database in UTF8", "ocsinventoryng") . "</td>";
        echo "<td>";
        Dropdown::showYesNo("ocs_db_utf8", $this->fields["ocs_db_utf8"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='center'>" . _n("User", "Users", 1) . "</td>";
        echo "<td>";
        echo Html::input('ocs_db_user', ['type'  => 'text',
            'value' => $this->fields["ocs_db_user"]]);
        echo "</td>";
        echo "<td class='center' rowspan='2'>" . __("Comments") . "</td>";
        echo "<td rowspan='2'>";
        Html::textarea(['name'            => 'comment',
            'value'           => $this->fields["comment"],
            'cols'            => 45,
            'rows'            => 6,
            'enable_richtext' => false]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='center'>" . __("Password") . "</td>";
        echo "<td>";
        echo Html::input('ocs_db_passwd', ['type'         => 'password',
            'autocomplete' => 'off']);
        if ($ID) {
            Html::showCheckbox([
                'name' => '_blank_passwd',
            ]);
            echo "&nbsp;" . __("Clear");
        }
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='center'>" . __('Use automatic action for clean old agents & drop from OCSNG software', 'ocsinventoryng') . "</td>";
        echo "<td>";
        Dropdown::showYesNo("use_cleancron", $this->fields["use_cleancron"], -1, ['on_change' => 'hide_show_cleancron(this.value);']);
        echo "</td>";
        echo Html::scriptBlock("
         function hide_show_cleancron(val) {
            var display = (val == 0) ? 'none' : '';
            var notdisplay = (val == 0) ? '' : 'none';
            document.getElementById('show_cleancron_td1').style.display = display;
            document.getElementById('show_cleancron_td2').style.display = display;
            document.getElementById('show_cleancron_td3').style.display = notdisplay;
            document.getElementById('show_cleancron_tr1').style.display = display;
         }");
        $style    = ($this->fields["use_cleancron"]) ? "" : "style='display: none '";
        $notstyle = ($this->fields["use_cleancron"]) ? "style='display: none '" : "";
        echo "<td colspan='2'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1' id='show_cleancron_td2' $style>";

        echo "<td class='center' id='show_cleancron_td1' $style>" . __("Action") . "</td>";
        $actions = self::getValuesActionCron();
        echo "<td>";
        Dropdown::showFromArray("action_cleancron", $actions, ['value' => $this->fields["action_cleancron"]]);
        echo "</td>";

        echo "<td>" . __('Number of days without inventory for cleaning', 'ocsinventoryng') . "</td>";
        echo "<td>";
        Dropdown::showNumber('cleancron_nb_days', ['value' => $this->fields["cleancron_nb_days"],
            'min'   => 1,
            'max'   => 365]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1' id='show_cleancron_tr1' $style>";
        echo "<td class='center'>" . __('Use automatic action restore deleted computer', 'ocsinventoryng') . "</td>";
        echo "<td>";
        Dropdown::showYesNo("use_restorationcron", $this->fields["use_restorationcron"], -1, ['on_change' => 'hide_show_restorecron(this.value);']);
        if ($ID == 0) {
            echo Html::hidden('delay_restorationcron', ['value' => 0]);
        }
        echo "</td>";

        echo Html::scriptBlock("
         function hide_show_restorecron(val) {
            var display = (val == 0) ? 'none' : '';
            var notdisplay = (val == 0) ? '' : 'none';
            document.getElementById('show_restorecron_td1').style.display = display;
            document.getElementById('show_restorecron_td2').style.display = display;
            document.getElementById('show_restorecron_td3').style.display = notdisplay;
         }");
        $style    = ($this->fields["use_restorationcron"]) ? "" : "style='display: none '";
        $notstyle = ($this->fields["use_restorationcron"]) ? "style='display: none '" : "";

        echo "<td class='center' id='show_restorecron_td1' $style>" . __("Number of days for the restoration of computers from the date of last inventory", "ocsinventoryng") . "</td>";
        echo "<td id='show_restorecron_td2' $style>";
        Dropdown::showNumber("delay_restorationcron", ['value' => $this->fields["delay_restorationcron"]]);
        echo "</td>";

        echo "<td colspan ='2' id='show_restorecron_td3' $notstyle></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='center'>" . __('Use automatic action to check entity assignment rules', 'ocsinventoryng');
        echo "&nbsp;";
        Html::showToolTip(nl2br(__('Use automatic action to check and send a notification for machines that no longer respond the entity and location assignment rules', 'ocsinventoryng')));
        echo "</td>";
        echo "<td colspan='3'>";
        Dropdown::showYesNo("use_checkruleimportentity", $this->fields["use_checkruleimportentity"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='center'>" . __('Use automatic locks', 'ocsinventoryng') . "</td>";
        echo "<td colspan='3'>";
        Dropdown::showYesNo("use_locks", $this->fields["use_locks"]);
        echo "</td>";
        echo "</tr>";

        $this->showFormButtons($options);
    }

    /**
     * check is one of the servers use_mass_import sync mode
     *
     * @return boolean
     * */
    public static function useMassImport()
    {
        $dbu = new DbUtils();
        return $dbu->countElementsInTable('glpi_plugin_ocsinventoryng_ocsservers', ["use_massimport" => 1]);
    }

    /**
     * @param $ID
     * */
    public function showDBConnectionStatus($ID)
    {
        $out   = "<br><div class='center'>\n";
        $out   .= "<table class='tab_cadre_fixe'>";
        $out   .= "<tr><th>" . __('Connecting to the database', 'ocsinventoryng') . "</th></tr>\n";
        $out   .= "</table></div>";
        $msg   = "";
        $style = "alert-info";
        if ($ID != -1) {
            if (!self::checkOCSconnection($ID)) {
                $style = "alert-warning";
                $msg   = __('Connection to the database failed', 'ocsinventoryng');
            } elseif (!self::checkVersion($ID)) {
                $style = "alert-warning";
                $msg   = __('Invalid OCSNG Version: RC3 is required', 'ocsinventoryng');
            } elseif (!self::checkTraceDeleted($ID)) {
                $style = "alert-warning";
                $msg   = __('Invalid OCSNG configuration (TRACE_DELETED must be active)', 'ocsinventoryng');
                // TODO
                /* } else if (!self::checkConfig(4)) {
                  $out .= __('Access denied on database (Need write rights on hardware.CHECKSUM necessary)',
                  'ocsinventoryng');
                  } else if (!self::checkConfig(8)) {
                  $out .= __('Access denied on database (Delete rights in deleted_equiv table necessary)',
                  'ocsinventoryng'); */
            } else {
                $msg = __('Connection to database successful', 'ocsinventoryng');
                $msg .= "<br>" . __('Valid OCSNG configuration and version', 'ocsinventoryng');
            }
        }
        $out .= "<br><div class='alert alert-important " . $style . " d-flex'>\n";
        $out .= $msg;
        $out .= "</div>";
        echo $out;
    }

    /**
     * @param datas $input
     *
     * @return datas
     */
    public function prepareInputForUpdate($input)
    {
        $adm = new PluginOcsinventoryngOcsAdminInfosLink();
        $adm->updateAdminInfo($input);
        if (isset($input["ocs_db_passwd"]) && !empty($input["ocs_db_passwd"])) {
            $input["ocs_db_passwd"] = rawurlencode(stripslashes($input["ocs_db_passwd"]));
            $input["ocs_db_passwd"] = (new GLPIKey())->encrypt(stripslashes($input["ocs_db_passwd"]));
        } else {
            unset($input["ocs_db_passwd"]);
        }

        if (isset($input["_blank_passwd"]) && $input["_blank_passwd"]) {
            $input['ocs_db_passwd'] = '';
        }

        return $input;
    }

    /**
     *
     */
    public function pre_updateInDB()
    {
        // Update checksum
        $checksum = 0;
        if (//$this->fields["import_device_processor"] ||
            $this->fields["import_general_contact"]
            || $this->fields["link_with_user"]
            || $this->fields["import_general_comment"]
            || $this->fields["import_general_domain"]
            || $this->fields["import_general_os"]
            || $this->fields["import_general_name"]) {
            $checksum |= pow(2, PluginOcsinventoryngOcsProcess::HARDWARE_FL);
        }
        if ($this->fields["import_device_bios"]) {
            $checksum |= $checksum + pow(2, PluginOcsinventoryngOcsProcess::BIOS_FL);
        }
        if ($this->fields["import_device_memory"]) {
            $checksum |= $checksum + pow(2, PluginOcsinventoryngOcsProcess::MEMORIES_FL);
        }
        if ($this->fields["import_registry"]) {
            $checksum |= pow(2, PluginOcsinventoryngOcsProcess::REGISTRY_FL);
        }
        if ($this->fields["import_device_controller"]) {
            $checksum |= pow(2, PluginOcsinventoryngOcsProcess::CONTROLLERS_FL);
        }
        if ($this->fields["import_device_slot"]) {
            $checksum |= pow(2, PluginOcsinventoryngOcsProcess::SLOTS_FL);
        }
        if ($this->fields["import_monitor"]) {
            $checksum |= pow(2, PluginOcsinventoryngOcsProcess::MONITORS_FL);
        }
        if ($this->fields["import_device_port"]) {
            $checksum |= pow(2, PluginOcsinventoryngOcsProcess::PORTS_FL);
        }
        if ($this->fields["import_device_hdd"] || $this->fields["import_device_drive"]) {
            $checksum |= pow(2, PluginOcsinventoryngOcsProcess::STORAGES_FL);
        }
        if ($this->fields["import_disk"]) {
            $checksum |= pow(2, PluginOcsinventoryngOcsProcess::DRIVES_FL);
        }
        if ($this->fields["import_periph"]) {
            $checksum |= pow(2, PluginOcsinventoryngOcsProcess::INPUTS_FL);
        }
        if ($this->fields["import_device_modem"]) {
            $checksum |= pow(2, PluginOcsinventoryngOcsProcess::MODEMS_FL);
        }
        if ($this->fields["import_ip"]
            || $this->fields["import_device_iface"]) {
            $checksum |= pow(2, PluginOcsinventoryngOcsProcess::NETWORKS_FL);
        }
        if ($this->fields["import_printer"]) {
            $checksum |= pow(2, PluginOcsinventoryngOcsProcess::PRINTERS_FL);
        }
        if ($this->fields["import_device_sound"]) {
            $checksum |= pow(2, PluginOcsinventoryngOcsProcess::SOUNDS_FL);
        }
        if ($this->fields["import_device_gfxcard"]) {
            $checksum |= pow(2, PluginOcsinventoryngOcsProcess::VIDEOS_FL);
        }
        if ($this->fields["import_software"]) {
            $checksum |= pow(2, PluginOcsinventoryngOcsProcess::SOFTWARES_FL);
        }
        if ($this->fields["import_vms"]) {
            $checksum |= pow(2, PluginOcsinventoryngOcsProcess::VIRTUALMACHINES_FL);
        }
        if ($this->fields["import_device_processor"]) {
            $checksum |= pow(2, PluginOcsinventoryngOcsProcess::CPUS_FL);
        }

        $this->updates[]          = "checksum";
        $this->fields["checksum"] = $checksum;
    }

    /**
     * @param datas $input
     *
     * @return bool|datas
     */
    public function prepareInputForAdd($input)
    {
        global $DB;

        // Check if server config does not exists
        $query  = "SELECT *
                FROM `" . $this->getTable() . "`
                WHERE `name` = '" . $input['name'] . "';";
        $result = $DB->doQuery($query);
        if ($DB->numrows($result) > 0) {
            Session::addMessageAfterRedirect(__('Unable to add. The OCSNG server already exists.', 'ocsinventoryng'), false, ERROR);
            return false;
        }

        if (isset($input["ocs_db_passwd"]) && !empty($input["ocs_db_passwd"])) {
            $input["ocs_db_passwd"] = rawurlencode(stripslashes($input["ocs_db_passwd"]));
            $input["ocs_db_passwd"] = (new GLPIKey())->encrypt(stripslashes($input["ocs_db_passwd"]));
        } else {
            unset($input["ocs_db_passwd"]);
        }

        if (isset($input["delay_restorationcron"]) && empty($input["delay_restorationcron"])) {
            $input["delay_restorationcron"] = 0;
        }
        return $input;
    }

    /**
     *
     */
    public function post_addItem()
    {
        global $DB;

        $query = "INSERT INTO  `glpi_plugin_ocsinventoryng_ocsservers_profiles` (`id` ,`plugin_ocsinventoryng_ocsservers_id` , `profiles_id`)
                 VALUES (NULL ,  '" . $this->fields['id'] . "',  '" . $_SESSION["glpiactiveprofile"]['id'] . "');";
        $DB->doQuery($query);
    }

    /**
     *
     */
    public function cleanDBonPurge()
    {
        $link = new PluginOcsinventoryngOcslink();
        $link->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

        $admin = new PluginOcsinventoryngOcsAdminInfosLink();
        $admin->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

        $server = new PluginOcsinventoryngServer();
        $server->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

        $detail = new PluginOcsinventoryngDetail();
        $detail->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

        $notimported = new PluginOcsinventoryngNotimportedcomputer();
        $notimported->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

        $iplink = new PluginOcsinventoryngIpdiscoverOcslink();
        $iplink->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

        $snmplink = new PluginOcsinventoryngSnmpOcslink();
        $snmplink->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

        $prof = new PluginOcsinventoryngOcsserver_Profile();
        $prof->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

        $thread = new PluginOcsinventoryngThread();
        $thread->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

        unset($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);

        // ocsservers_id for RuleImportAsset, OCS_SERVER for RuleImportEntity
        Rule::cleanForItemCriteria($this);
        Rule::cleanForItemCriteria($this, 'OCS_SERVER');
    }


    /**
     * Get the ocs server id of a machine, by giving the machine id
     *
     * @param $ID the machine ID
     *
     * return the ocs server id of the machine
     *
     * @return int
     */
    public static function getServerByComputerID($ID)
    {
        global $DB;

        $sql    = "SELECT `plugin_ocsinventoryng_ocsservers_id`
              FROM `glpi_plugin_ocsinventoryng_ocslinks`
              WHERE `computers_id` = $ID";
        $result = $DB->doQuery($sql);
        if ($DB->numrows($result) > 0) {
            $datas = $DB->fetchArray($result);
            return $datas["plugin_ocsinventoryng_ocsservers_id"];
        }
        return -1;
    }

    /**
     * Get the ocs id of a machine, by giving the machine id
     *
     * @param $ID the machine ID
     *
     * return the ocs server id of the machine
     *
     * @param $ocsservers_id
     *
     * @return int
     * @throws \GlpitestSQLError
     */
    public static function getOCSIDByComputerID($ID, $ocsservers_id)
    {
        global $DB;

        $sql    = "SELECT `ocsid`
              FROM `glpi_plugin_ocsinventoryng_ocslinks`
              WHERE `computers_id` = '" . $ID . "' AND `plugin_ocsinventoryng_ocsservers_id` = $ocsservers_id";
        $result = $DB->doQuery($sql);
        if ($DB->numrows($result) > 0) {
            $datas = $DB->fetchArray($result);
            return $datas["ocsid"];
        }
        return -1;
    }

    /**
     * Get a random plugin_ocsinventoryng_ocsservers_id
     * use for standard sync server selection
     *
     * return an ocs server id
     * */
    public static function getRandomServerID()
    {
        global $DB;

        $sql    = "SELECT `id`
              FROM `glpi_plugin_ocsinventoryng_ocsservers`
              WHERE `is_active` = 1
                AND `use_massimport` = 0
              ORDER BY RAND()
              LIMIT 1";
        $result = $DB->doQuery($sql);

        if ($DB->numrows($result) > 0) {
            $datas = $DB->fetchArray($result);
            return $datas["id"];
        }
        return -1;
    }

    /**
     * @return int|Value
     */
    public static function getFirstServer()
    {
        global $DB;

        $query   = "SELECT `glpi_plugin_ocsinventoryng_ocsservers`.`id`
                FROM `glpi_plugin_ocsinventoryng_ocsservers_profiles`
                LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers`
                ON `glpi_plugin_ocsinventoryng_ocsservers`.`id` = `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id`
                WHERE `glpi_plugin_ocsinventoryng_ocsservers`.`is_active`= 1
                ORDER BY `glpi_plugin_ocsinventoryng_ocsservers`.`id` ASC LIMIT 1 ";
        $results = $DB->doQuery($query);
        if ($DB->numrows($results) > 0) {
            return $DB->result($results, 0, 'id');
        }
        return -1;
    }

    /**
     * Get OCSNG mode configuration
     *
     * Get all config of the OCSNG mode
     *
     * @param $id int : ID of the OCS config (default value 1)
     *
     * @return int|null|string[] of $confVar fields or false if unfound.
     * @throws \GlpitestSQLError
     */
    public static function getConfig($id)
    {
        global $DB;

        $data = 0;

        if (!empty($id)) {
            $query  = "SELECT *
                FROM `glpi_plugin_ocsinventoryng_ocsservers`
                WHERE `id` = $id";
            $result = $DB->doQuery($query);

            if ($result) {
                $data = $DB->fetchAssoc($result);
            }
        }

        return $data;
    }


    /**
     * @param $ocs_config
     * @param $itemtype
     *
     * @return mixed
     */
    public static function getDevicesManagementMode($ocs_config, $itemtype)
    {
        switch ($itemtype) {
            case 'Monitor':
                return $ocs_config["import_monitor"];

            case 'Printer':
                return $ocs_config["import_printer"];

            case 'Peripheral':
                return $ocs_config["import_periph"];
        }
    }


    /**
     * Check if OCS connection is still valid
     * If not, then establish a new connection on the good server
     *
     * @param $serverId
     *
     * @return bool
     * @internal param the $plugin_ocsinventoryng_ocsservers_id ocs server id
     *
     */
    public static function checkOCSconnection($serverId)
    {
        return self::getDBocs($serverId)->checkConnection();
    }

    /**
     * Get a connection to the OCS server
     *
     * @param $serverId the ocs server id
     *
     * @return PluginOcsinventoryngOcsClient the ocs client (database or soap)
     */
    public static function getDBocs($serverId)
    {
        if ($serverId) {
            $config = self::getConfig($serverId);
            if (isset($config['conn_type'])) {
                if ($config['conn_type'] == self::CONN_TYPE_DB) {
                    return new PluginOcsinventoryngOcsDbClient(
                        $serverId,
                        $config['ocs_db_host'],
                        $config['ocs_db_user'],
                        rawurldecode(stripslashes((new GLPIKey())->decrypt($config['ocs_db_passwd']))),
                        $config['ocs_db_name']
                    );
                } else {
                    return new PluginOcsinventoryngOcsSoapClient(
                        $serverId,
                        $config['ocs_db_host'],
                        $config['ocs_db_user'],
                        rawurldecode(stripslashes((new GLPIKey())->decrypt($config['ocs_db_passwd'])))
                    );
                }
            }
        }
    }

    /**
     * get true if the server is active
     *
     * @param $serverID
     *
     * @return bool
     */
    public static function serverIsActive($serverID)
    {
        $ocsserver = new self();
        if ($ocsserver->getFromDB($serverID)) {
            return $ocsserver->isActive();
        }
        return false;
    }


    /**
     * @param $cfg_ocs
     *
     * @return string
     */
    public static function getTagLimit($cfg_ocs)
    {
        $WHERE = "";
        if (!empty($cfg_ocs["tag_limit"])) {
            $splitter = explode("$", trim($cfg_ocs["tag_limit"]));
            if (count($splitter)) {
                $WHERE = " `accountinfo`.`TAG` = '" . $splitter[0] . "' ";
                for ($i = 1; $i < count($splitter); $i++) {
                    $WHERE .= " OR `accountinfo`.`TAG` = '" . $splitter[$i] . "' ";
                }
            }
        }

        if (!empty($cfg_ocs["tag_exclude"])) {
            $splitter = explode("$", $cfg_ocs["tag_exclude"]);
            if (count($splitter)) {
                if (!empty($WHERE)) {
                    $WHERE .= " AND ";
                }
                $WHERE .= " `accountinfo`.`TAG` <> '" . $splitter[0] . "' ";
                for ($i = 1; $i < count($splitter); $i++) {
                    $WHERE .= " AND `accountinfo`.`TAG` <> '" . $splitter[$i] . "' ";
                }
            }
        }

        return $WHERE;
    }


    /**
     * @param $ID
     *
     * @return bool
     */
    public static function checkVersion($ID)
    {
        $client  = self::getDBocs($ID);
        $version = $client->getTextConfig('GUI_VERSION');

        if ($version) {
            $server = new self();
            $server->update([
                'id'          => $ID,
                'ocs_version' => $version,
            ]);
        }
        return true;
        if (!$version || ($version < self::OCS_VERSION_LIMIT && strpos($version, '2.0') !== 0)) { // hack for 2.0 RC
            return false;
        } else {
            return true;
        }
    }


    /**
     * @param $ID
     *
     * @return int
     */
    public static function checkTraceDeleted($ID)
    {
        $client = self::getDBocs($ID);
        return $client->getIntConfig('TRACE_DELETED');
    }


    /**
     * Displays a list of computers that can be cleaned.
     *
     * @param $plugin_ocsinventoryng_ocsservers_id int : id of ocs server in GLPI
     * @param $check string : parameter for HTML input checkbox
     * @param $start int : parameter for Html::printPager method
     *
     *
     * @return bool
     */
    public static function showComputersToClean($show_params)
    {
        global $DB, $CFG_GLPI;

        $plugin_ocsinventoryng_ocsservers_id = $show_params["plugin_ocsinventoryng_ocsservers_id"];
        $check                               = $show_params["check"];
        $start                               = $show_params["start"];

        if (!self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)) {
            return false;
        }

        if (!Session::haveRight("plugin_ocsinventoryng_clean", READ)) {
            return false;
        }
        $canedit = Session::haveRight("plugin_ocsinventoryng_clean", UPDATE);

        // ocslinks ocsid
        $query  =
            "SELECT `ocsid` " .
            "FROM `glpi_plugin_ocsinventoryng_ocslinks` " .
            "WHERE `plugin_ocsinventoryng_ocsservers_id` = $plugin_ocsinventoryng_ocsservers_id" .
            (new DbUtils())->getEntitiesRestrictRequest(" AND", "glpi_plugin_ocsinventoryng_ocslinks");
        $result = $DB->doQuery($query);
        $ocsids = [];
        if ($DB->numrows($result) > 0) {
            while ($data = $DB->fetchAssoc($result)) {
                $ocsids[] = $data['ocsid'];
            }
        }

        // Select unexisting OCS hardware
        $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);

        $computerOptions = [
            'COMPLETE' => '0',
            'FILTER'   => [
                'IDS' => $ocsids,
            ],
        ];

        $computers = $ocsClient->getComputers($computerOptions);

        if (isset($computers['COMPUTERS'])
            && count($computers['COMPUTERS']) > 0) {
            $hardware = $computers['COMPUTERS'];
            unset($computers);
        } else {
            $hardware = [];
        }

        //      $query  = "SELECT `ocsid`
        //                FROM `glpi_plugin_ocsinventoryng_ocslinks`
        //                WHERE `plugin_ocsinventoryng_ocsservers_id`
        //                           = $plugin_ocsinventoryng_ocsservers_id";
        //      $result = $DB->doQuery($query);

        $ocs_missing = [];
        //      if ($DB->numrows($result) > 0) {
        //         while ($data = $DB->fetchArray($result)) {
        //            if (!isset($hardware[$data["ocsid"]])) {
        //               $ocs_missing[$data["ocsid"]] = $data["ocsid"];
        //            }
        //         }
        //      }

        foreach ($ocsids as $id) {
            if (!isset($hardware[$id])) {
                $ocs_missing[$id] = $id;
            }
        }


        //Select unexisting computers
        $query_glpi = "SELECT `glpi_plugin_ocsinventoryng_ocslinks`.`entities_id` AS entities_id,
                            `glpi_plugin_ocsinventoryng_ocslinks`.`ocs_deviceid` AS ocs_deviceid,
                            `glpi_plugin_ocsinventoryng_ocslinks`.`last_update` AS last_update,
                            `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid` AS ocsid,
                            `glpi_plugin_ocsinventoryng_ocslinks`.`id`,
                            `glpi_computers`.`name` AS name
                     FROM `glpi_plugin_ocsinventoryng_ocslinks`
                     LEFT JOIN `glpi_computers`
                           ON `glpi_computers`.`id` = `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id`
                     WHERE ((`glpi_computers`.`id` IS NULL
                             AND `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id`
                                    = $plugin_ocsinventoryng_ocsservers_id)";

        if (count($ocs_missing)) {
            $query_glpi .= " OR `ocsid` IN ('" . implode("','", $ocs_missing) . "')";
        }
        $dbu        = new DbUtils();
        $query_glpi .= ") " . $dbu->getEntitiesRestrictRequest(" AND", "glpi_plugin_ocsinventoryng_ocslinks");

        $result_glpi = $DB->doQuery($query_glpi);

        // fetch all links missing between glpi and OCS
        $already_linked = [];
        if ($DB->numrows($result_glpi) > 0) {
            while ($data = $DB->fetchAssoc($result_glpi)) {

                $already_linked[$data["ocsid"]]["entities_id"]  = $data["entities_id"];
                $already_linked[$data["ocsid"]]["ocs_deviceid"] = $data["ocs_deviceid"];
                $already_linked[$data["ocsid"]]["date"]         = $data["last_update"];
                $already_linked[$data["ocsid"]]["id"]           = $data["id"];
                $already_linked[$data["ocsid"]]["in_ocs"]       = isset($hardware[$data["ocsid"]]);

                if ($data["name"] == null) {
                    $already_linked[$data["ocsid"]]["in_glpi"] = 0;
                } else {
                    $already_linked[$data["ocsid"]]["in_glpi"] = 1;
                }
            }
        }

        echo "<div class='center'>";
        echo "<h2>" . __('Clean links between GLPI and OCSNG', 'ocsinventoryng') . "</h2>";

        $target = PLUGIN_OCS_WEBDIR . '/front/ocsng.clean.php';
        if (($numrows = count($already_linked)) > 0) {
            $parameters = "check=$check";
            Html::printPager($start, $numrows, $target, $parameters);

            // delete end
            array_splice($already_linked, $start + $_SESSION['glpilist_limit']);

            // delete begin
            if ($start > 0) {
                array_splice($already_linked, 0, $start);
            }

            echo "<form method='post' id='ocsng_form' name='ocsng_form' action='" . $target . "'>";
            if ($canedit) {
                self::checkBox($target);
            }

            echo "<table class='tab_cadre'>";

            echo "<tr class='tab_bg_1'><td colspan='6' class='center'>";
            if ($canedit) {
                echo Html::submit(_sx('button', 'Clean'), ['name' => 'clean_ok', 'class' => 'btn btn-primary']);
            }
            echo "</td></tr>\n";

            echo "<tr class='tab_bg_1 center'>";
            echo "<th>" . __('Item') . "</th>";
            echo "<th>" . __('Import date in GLPI', 'ocsinventoryng') . "</th>";
            echo "<th>" . __('Existing in GLPI', 'ocsinventoryng') . "</th>";
            echo "<th>" . __('Existing in OCSNG', 'ocsinventoryng') . "</th>";
            if (Session::isMultiEntitiesMode()) {
                echo "<th>" . __('Entity') . "</th>";
            }
            if ($canedit) {
                echo "<th>&nbsp;</th>";
            }
            echo "</tr>\n";

            foreach ($already_linked as $ID => $tab) {
                echo "<tr class='tab_bg_2 center'>";
                echo "<td>" . $tab["ocs_deviceid"] . "</td>\n";
                echo "<td>" . Html::convDateTime($tab["date"]) . "</td>\n";
                echo "<td>" . Dropdown::getYesNo($tab["in_glpi"]) . "</td>\n";
                echo "<td>" . Dropdown::getYesNo($tab["in_ocs"]) . "</td>\n";
                if (Session::isMultiEntitiesMode()) {
                    echo "<td>" . Dropdown::getDropdownName('glpi_entities', $tab['entities_id']) . "</td>\n";
                }
                if ($canedit) {
                    echo "<td><input type='checkbox' name='toclean[" . $tab["id"] . "]' " .
                         (($check == "all") ? "checked" : "") . "></td>";
                }
                echo "</tr>\n";
            }

            echo "<tr class='tab_bg_1'><td colspan='6' class='center'>";
            if ($canedit) {
                echo Html::submit(_sx('button', 'Clean'), ['name' => 'clean_ok', 'class' => 'btn btn-primary']);
            }
            echo "</td></tr>";
            echo "</table>\n";
            Html::closeForm();
            Html::printPager($start, $numrows, $target, $parameters);
        } else {
            echo "<div class='center b '>" . __('No item to clean', 'ocsinventoryng') . "</div>";
            echo "<a href='" . PLUGIN_OCS_WEBDIR . "/front/ocsng.php'>";
            echo __('Back');
            echo "</a>";
        }
        echo "</div>";
    }


    /**
     * @param $plugin_ocsinventoryng_ocsservers_id
     * @param $check
     * @param $start
     *
     * @return bool|void
     */
    public static function showComputersToSynchronize($show_params)
    {
        echo Html::css(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/datatables.min.css");
        //      echo Html::css(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/Buttons-1.6.1/css/buttons.dataTables.min.css");
        echo Html::css(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/ColReorder-1.5.2/css/colReorder.dataTables.min.css");
        echo Html::css(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/Responsive-2.2.3/css/responsive.dataTables.min.css");
        echo Html::css(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/Select-1.3.1/css/select.dataTables.min.css");

        echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/datatables.min.js");
        echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/Responsive-2.2.3/js/dataTables.responsive.min.js");
        echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/Select-1.3.1/js/dataTables.select.min.js");
        //      echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/Buttons-1.6.1/js/dataTables.buttons.min.js");
        //      echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/Buttons-1.6.1/js/buttons.html5.min.js");
        //      echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/Buttons-1.6.1/js/buttons.print.min.js");
        //      echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/Buttons-1.6.1/js/buttons.colVis.min.js");
        echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/ColReorder-1.5.2/js/dataTables.colReorder.min.js");
        //      echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/JSZip-2.5.0/jszip.min.js");
        //      echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/pdfmake-0.1.36/pdfmake.min.js");
        //      echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/pdfmake-0.1.36/vfs_fonts.js");


        $plugin_ocsinventoryng_ocsservers_id = $show_params["plugin_ocsinventoryng_ocsservers_id"];

        if (!self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)) {
            return false;
        }
        if (!Session::haveRight("plugin_ocsinventoryng", UPDATE)
            && !Session::haveRight("plugin_ocsinventoryng_sync", UPDATE)) {
            return false;
        }

        $title      = __('Computers updated in OCSNG', 'ocsinventoryng');
        $target     = PLUGIN_OCS_WEBDIR . '/front/ocsng.sync.php';
        $languages  = json_encode(self::getJsLanguages());
        $columnDefs = "[ {
               orderable: false,
               className: 'select-checkbox',
               targets:0,
               },{
               targets: [1],
                visible: false
               }]";

        $columns = "[
                     { 'data': 'checked' },
                      { 'data': 'ocsid' },
                      { 'data': 'name' },
                      { 'data': 'serial' },
                      { 'data': 'last_update' },
                      { 'data': 'date' },
                      { 'data': 'TAG' },
                      { 'data': 'checksum_debug' },
                       ]";

        echo "<div id='ajax_loader' class=\"ajax_loader hidden\">";
        echo "</div>";
        //      $entities = json_encode($entities_id);
        echo "<script>
            $('#ajax_loader').show();
            $(document).ready(function () {
                   var table = $('#OcsTable').DataTable({
                      ajax: {
                          url: '../ajax/loadcomputerstosynchronize.php',
                          type:'POST',
                          data: {plugin_ocsinventoryng_ocsservers_id: $plugin_ocsinventoryng_ocsservers_id
                                },
                          datatype: 'json'
                      },
                      paging: true,
                      pageLength: 25,
                      stateSave: true,
                      columnDefs: $columnDefs,
                     'select': {
                        'style': 'multi',
                        'selector': 'td:first-child'
                     },
                     'order': [[1, 'asc']],
                       columns: $columns,
                       processing: true,
                       responsive: true,
                       initComplete: function () {
                            $('#ajax_loader').hide();
                      },
                     'language': $languages,
                   });
                   table.on('click', 'th.select-checkbox', function() {
                   if ($('th.select-checkbox').hasClass('selected')) {
                       table.rows().deselect();
                       $('th.select-checkbox').removeClass('selected');
                   } else {
                       table.rows().select();
                       $('th.select-checkbox').addClass('selected');
                   }
                  }).on('select deselect', function() {
                   ('Some selection or deselection going on')
                   if (table.rows({
                           selected: true
                       }).count() !== table.rows().count()) {
                       $('th.select-checkbox').removeClass('selected');
                   } else {
                       $('th.select-checkbox').addClass('selected');
                   }
                  });
                  // Handle form submission event 
                  $('#ocsng_form').on('submit', function(e){
                     var form = this;
                     var rows_selected = table.rows('.selected').data();
                     // Iterate over all selected checkboxes
                     rows_selected.each( function ( value, index ) {
                        // Create a hidden element 
                        $(form).append(
                            $('<input>')
                               .attr('type', 'hidden')
                               .attr('name', 'toupdate[]')
                               .val(value.id)
                        );
                     });
                  });
               });
            </script>";

        echo "<form method='post' name='ocsng_form' id='ocsng_form' action='$target'>";
        echo "<div class='center'>";
        echo "<table id='OcsTable' class='display' cellspacing='0' style='width: 100%!important;'>";
        echo "<thead>";
        $nbcols = 8;
        // Set display type for export if define
        $output_type = Search::HTML_OUTPUT;
        if (isset($values["display_type"])) {
            $output_type = $values["display_type"];
        }

        $header_num = 1;
        echo "<tr><th colspan='" . $nbcols . "'>" . $title . "</th></tr>";
        //Column title
        echo Search::showNewLine($output_type);
        echo Search::showHeaderItem($output_type, __('All', 'ocsinventoryng'), $header_num);
        echo Search::showHeaderItem($output_type, __('ID'), $header_num);
        echo Search::showHeaderItem($output_type, __('Name'), $header_num);
        echo Search::showHeaderItem($output_type, __('Serial number'), $header_num);
        echo Search::showHeaderItem($output_type, __('Import date in GLPI', 'ocsinventoryng'), $header_num);
        echo Search::showHeaderItem($output_type, __('Last OCSNG inventory date', 'ocsinventoryng'), $header_num);
        echo Search::showHeaderItem($output_type, __('OCSNG TAG', 'ocsinventoryng'), $header_num);
        echo Search::showHeaderItem($output_type, __('Checksum', 'ocsinventoryng'), $header_num);
        echo "</thead>";
        echo Search::showFooter($output_type, $title);

        echo Html::submit(_sx('button', 'Synchronize'), ['name' => 'update_ok', 'class' => 'btn btn-primary']);
        Html::closeForm();

        if (isset($_SESSION["ocs_update"]['statistics'])) {
            echo "<br>";
            PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_update"]['statistics'], true);
        }
    }

    /**
     * Display a list of computers to add or to link
     *
     * @param $show_params
     *
     * @return bool
     * @throws \GlpitestSQLError
     * @internal param the $plugin_ocsinventoryng_ocsservers_id ID of the ocs server
     * @internal param display $advanced detail about the computer import or not (target entity, matched rules, etc.)
     * @internal param indicates $check if checkboxes are checked or not
     * @internal param display $start a list of computers starting at rowX
     * @internal param a $entities_id list of entities in which computers can be added or linked
     *
     */
    public static function showComputersToAdd($show_params)
    {
        echo Html::css(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/datatables.min.css");
        //      echo Html::css(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/Buttons-1.6.1/css/buttons.dataTables.min.css");
        echo Html::css(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/ColReorder-1.5.2/css/colReorder.dataTables.min.css");
        echo Html::css(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/Responsive-2.2.3/css/responsive.dataTables.min.css");
        echo Html::css(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/Select-1.3.1/css/select.dataTables.min.css");

        echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/datatables.min.js");
        echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/Responsive-2.2.3/js/dataTables.responsive.min.js");
        echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/Select-1.3.1/js/dataTables.select.min.js");
        //      echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/Buttons-1.6.1/js/dataTables.buttons.min.js");
        //      echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/Buttons-1.6.1/js/buttons.html5.min.js");
        //      echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/Buttons-1.6.1/js/buttons.print.min.js");
        //      echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/Buttons-1.6.1/js/buttons.colVis.min.js");
        echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/ColReorder-1.5.2/js/dataTables.colReorder.min.js");
        //      echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/JSZip-2.5.0/jszip.min.js");
        //      echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/pdfmake-0.1.36/pdfmake.min.js");
        //      echo Html::script(PLUGIN_OCS_NOTFULL_DIR . "/lib/DataTables/pdfmake-0.1.36/vfs_fonts.js");

        $plugin_ocsinventoryng_ocsservers_id = $show_params["plugin_ocsinventoryng_ocsservers_id"];
        $advancedimport                      = $show_params["import_mode"] ?? 0;
        $advancedlink                        = $show_params["link_mode"] ?? 0;
        $entities_id                         = $show_params["entities_id"];


        if (!self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)) {
            return false;
        }

        if (!Session::haveRight("plugin_ocsinventoryng", READ)
            && !Session::haveRight("plugin_ocsinventoryng_import", READ)
            && !Session::haveRight("plugin_ocsinventoryng_link", READ)) {
            return false;
        }

        $caneditimport = Session::haveRight('plugin_ocsinventoryng_import', UPDATE);
        //      $caneditlink   = Session::haveRight('plugin_ocsinventoryng_link', UPDATE);
        $usecheckbox  = $caneditimport;
        $title        = __('Import or link new computers', 'ocsinventoryng');
        $target       = PLUGIN_OCS_WEBDIR . '/front/ocsng.import.php';
        $languages    = json_encode(self::getJsLanguages());
        $nbcols       = 9;
        $colsadvanced = "false";
        if ($advancedimport || $advancedlink) {
            $colsadvanced = "true";
            $nbcols       = 14;
        }

        $columnDefs = "[ {
               orderable: false,
               className: 'select-checkbox',
               targets: [0],
               },{
               targets: [1],
                visible: false
               }]";

        $columns = "[
                      { 'data': 'checked' },
                      { 'data': 'id'},
                      { 'data': 'name' },
                      { 'data': 'serial' },
                      { 'data': 'manufacturer' },
                      { 'data': 'model' },
                      { 'data': 'infos' },
                      { 'data': 'date' },
                      { 'data': 'TAG' },
                      { 'data': 'toimport_disable_unicity_check' },
                      { 'data': 'rule_matched'},
                      { 'data': 'toimport_entities'},
                      { 'data': 'toimport_recursive'},
                      { 'data': 'computers_id_founded'},
                       ]";

        echo "<div id='ajax_loader' class=\"ajax_loader hidden\">";
        echo "</div>";
        $entities = json_encode($entities_id);
        echo "<script>
            $('#ajax_loader').show();
            $(document).ready(function () {
                   var table = $('#OcsTable').DataTable({
                      ajax: {
                          url: '../ajax/loadcomputerstoimport.php',
                          type:'POST',
                          data: {plugin_ocsinventoryng_ocsservers_id: $plugin_ocsinventoryng_ocsservers_id,
                                 entities_id: $entities,
                                 importmode: $advancedimport,
                                 linkmode: $advancedlink
                                },
                          datatype: 'json'
                      },
                      paging: true,
                      pageLength: 25,
                      stateSave: true,
                      columnDefs: $columnDefs,
                     'select': {
                        'style': 'multi',
                        'selector': 'td:first-child'
                     },
                     'order': [[1, 'asc']],
                       columns: $columns,
                       processing: true,
                       responsive: true,
                       initComplete: function () {
                            $('#ajax_loader').hide();
                      },
                     'language': $languages,
                   });
                   table.columns( [1] ).visible( false );
                   table.columns( [9, 10, 11, 12, 13] ).visible( $colsadvanced );
                   table.on('click', 'th.select-checkbox', function() {
                   if ($('th.select-checkbox').hasClass('selected')) {
                       table.rows().deselect();
                       $('th.select-checkbox').removeClass('selected');
                   } else {
                       table.rows().select();
                       $('th.select-checkbox').addClass('selected');
                   }
                  }).on('select deselect', function() {
                   ('Some selection or deselection going on')
                   if (table.rows({
                           selected: true
                       }).count() !== table.rows().count()) {
                       $('th.select-checkbox').removeClass('selected');
                   } else {
                       $('th.select-checkbox').addClass('selected');
                   }
                  });
                  // Handle form submission event 
                  $('#ocsng_form').on('submit', function(e){
                     var form = this;
                     var rows_selected = table.rows('.selected').data();
                     // Iterate over all selected checkboxes
                     rows_selected.each( function ( value, index ) {
                        // Create a hidden element 
                        $(form).append(
                            $('<input>')
                               .attr('type', 'hidden')
                               .attr('name', 'toadd[]')
                               .val(value.id)
                        );
                     });
                  });
               });
            </script>";

        //Show preview form only in import even in multi-entity mode because computer import
        //can be refused by a rule
        echo "<div class='firstbloc'>";
        echo "<form method='post' name='ocsng_import_mode' id='ocsng_import_mode'
                               action='$target'>\n";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th class='center'>" . __('Manual import mode', 'ocsinventoryng') . "</th></tr>\n";
        echo "<tr class='tab_bg_1'><td class='center'>";

        if ($advancedimport || $advancedlink) {
            Html::showSimpleForm($target, 'simple_mode', __('Disable preview', 'ocsinventoryng'));
        } else {
            Html::showSimpleForm($target, 'change_import_mode', __('Enable preview for import', 'ocsinventoryng'));
            echo "&nbsp;";
            Html::showSimpleForm($target, 'change_link_mode', __('Enable preview for linking', 'ocsinventoryng'));
        }

        echo "</td></tr>";
        echo "<tr class='tab_bg_1'><td class='center b'>" .
             __('Check first that duplicates have been correctly managed in OCSNG', 'ocsinventoryng') . "</td>";
        echo "</tr></table>";
        Html::closeForm();
        echo "</div>";

        echo "<form method='post' name='ocsng_form' id='ocsng_form' action='$target'>";
        echo "<div class='center'>";
        echo "<table id='OcsTable' class='display' cellspacing='0' style='width: 100%!important;'>";
        echo "<thead>";

        // Set display type for export if define
        $output_type = Search::HTML_OUTPUT;
        if (isset($values["display_type"])) {
            $output_type = $values["display_type"];
        }

        $header_num = 1;
        echo "<tr><th colspan='" . $nbcols . "'>" . $title . "</th></tr>";
        //Column title
        echo Search::showNewLine($output_type);
        echo Search::showHeaderItem($output_type, __('All', 'ocsinventoryng'), $header_num);
        echo Search::showHeaderItem($output_type, __('ID'), $header_num);
        echo Search::showHeaderItem($output_type, __('Name'), $header_num);
        echo Search::showHeaderItem($output_type, __('Serial number'), $header_num);
        echo Search::showHeaderItem($output_type, __('Manufacturer'), $header_num);
        echo Search::showHeaderItem($output_type, __('Model'), $header_num);
        echo Search::showHeaderItem($output_type, _n('Information', 'Informations', 2), $header_num);
        echo Search::showHeaderItem($output_type, __('Last OCSNG inventory date', 'ocsinventoryng'), $header_num);
        echo Search::showHeaderItem($output_type, __('OCSNG TAG', 'ocsinventoryng'), $header_num);
        //        if ($advancedimport) {
        echo Search::showHeaderItem($output_type, __('Override unicity check ?', 'ocsinventoryng'), $header_num);
        echo Search::showHeaderItem($output_type, __('Match the rule ?', 'ocsinventoryng'), $header_num);
        echo Search::showHeaderItem($output_type, __('Destination entity', 'ocsinventoryng'), $header_num);
        echo Search::showHeaderItem($output_type, __('Child entities'), $header_num);
        //        }
        //        if ($advancedlink) {
        echo Search::showHeaderItem($output_type, __('Item to link', 'ocsinventoryng'), $header_num);
        //        }
        echo "</thead>";
        echo Search::showFooter($output_type, $title);

        echo Html::submit($title, ['name' => 'import_ok', 'class' => 'btn btn-primary']);
        Html::closeForm();

        if (isset($_SESSION["ocs_import"]['statistics'])) {
            echo "<br>";
            PluginOcsinventoryngOcsProcess::showStatistics($_SESSION["ocs_import"]['statistics'], true);
        }
    }


    /**
     * Get a direct link to the computer in ocs console
     *
     * @param $plugin_ocsinventoryng_ocsservers_id the ID of the OCS server
     * @param $ocsid ID of the computer in OCS hardware table
     * @param $todisplay the link's label to display
     * @param $only_url
     *
     *
     * return the html link to the computer in ocs console
     *
     * @return string
     */
    public static function getComputerLinkToOcsConsole($plugin_ocsinventoryng_ocsservers_id, $ocsid, $todisplay, $only_url = false)
    {
        $ocs_config = self::getConfig($plugin_ocsinventoryng_ocsservers_id);
        $url        = '';

        if ($ocs_config["ocs_url"] != '') {
            //Display direct link to the computer in ocsreports
            $url = $ocs_config["ocs_url"];
            if (!preg_match("/\/$/i", $ocs_config["ocs_url"])) {
                $url .= '/';
            }
            if ($ocs_config['ocs_version'] > self::OCS2_VERSION_LIMIT) {
                $url = $url . "index.php?function=computer&amp;head=1&amp;systemid=$ocsid";
            } else {
                $url = $url . "machine.php?systemid=$ocsid";
            }

            if ($only_url) {
                return $url;
            }
            return "<a class='submit btn btn-primary' target='_blank' href='$url'>" . $todisplay . "</a>";
        }
        return $url;
    }

    /**
     *
     * @return array
     */
    public static function getValuesActionCron()
    {
        $values                               = [];
        $values[self::ACTION_PURGE_COMPUTER]  = self::getActionCronName(self::ACTION_PURGE_COMPUTER);
        $values[self::ACTION_DELETE_COMPUTER] = self::getActionCronName(self::ACTION_DELETE_COMPUTER);

        return $values;
    }

    /**
     *
     * @param type $value
     *
     * @return type
     */
    public static function getActionCronName($value)
    {
        switch ($value) {
            case self::ACTION_PURGE_COMPUTER:
                return __('Purge computers in OCSNG', 'ocsinventoryng');

            case self::ACTION_DELETE_COMPUTER:
                return __('Delete computers', 'ocsinventoryng');
            default:
                // Return $value if not define
                return $value;
        }
    }

    /**
     * @param $name
     *
     * @return array
     */
    public static function cronInfo($name)
    {
        // no translation for the name of the project
        switch ($name) {
            case 'ocsng':
                return ['description' => __('OCSNG', 'ocsinventoryng') . " - " .
                                         __('Launch OCSNG synchronization script', 'ocsinventoryng')];
                break;
            case 'CleanOldAgents':
                return ['description' => __('OCSNG', 'ocsinventoryng') . " - " .
                                         __('Clean old agents & drop from OCSNG software', 'ocsinventoryng')];
                break;
            case 'RestoreOldAgents':
                return ['description' => __('OCSNG', 'ocsinventoryng') . " - " .
                                         __('Restore computers from the date of last inventory', 'ocsinventoryng')];
                break;
        }
    }

    /**
     * cron Clean Old Agents
     *
     * @param $task
     *
     * @return int
     */
    public static function cronCleanOldAgents($task)
    {
        global $DB;

        $CronTask = new CronTask();
        if ($CronTask->getFromDBbyName("PluginOcsinventoryngOcsServer", "CleanOldAgents")) {
            if ($CronTask->fields["state"] == CronTask::STATE_DISABLE) {
                return 0;
            }
        } else {
            return 0;
        }

        $cron_status                         = 0;
        $plugin_ocsinventoryng_ocsservers_id = 0;
        foreach ($DB->request([
            'FROM' => 'glpi_plugin_ocsinventoryng_ocsservers',
            'WHERE' => ['is_active' => 1, 'use_cleancron' => 1]]) as $config) {
            $plugin_ocsinventoryng_ocsservers_id = $config["id"];
            if ($plugin_ocsinventoryng_ocsservers_id > 0) {
                $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);
                $agents    = $ocsClient->getOldAgents($config["cleancron_nb_days"]);

                if ($config['action_cleancron'] == self::ACTION_PURGE_COMPUTER) {
                    //action purge agents OCSNG

                    $computers  = [];
                    $can_update = PluginOcsinventoryngConfig::canUpdateOCS();
                    if (count($agents) > 0 && $can_update) {
                        $nb = $ocsClient->deleteOldAgents($agents);
                        if ($nb) {
                            $cron_status = 1;
                            if ($task) {
                                $task->addVolume($nb);
                                $task->log(__('Clean old agents OK', 'ocsinventoryng'));
                            }
                        } else {
                            $task->log(__('Clean old agents failed', 'ocsinventoryng'));
                        }
                    }
                } else {
                    //action delete computers

                    if (count($agents) > 0) {
                        $nb = PluginOcsinventoryngOcslink::deleteComputers($plugin_ocsinventoryng_ocsservers_id, $agents);
                        if ($nb) {
                            $cron_status = 1;
                            if ($task) {
                                $task->addVolume($nb);
                                $task->log(__('Delete computers OK', 'ocsinventoryng'));
                            }
                        } else {
                            $task->log(__('Delete computers failed', 'ocsinventoryng'));
                        }
                    }
                }
            }
        }

        return $cron_status;
    }

    /**
     * cron Restore Old Agents
     *
     * @param type  $task
     *
     * @return int
     * @global type $DB
     * @global type $CFG_GLPI
     *
     */
    public static function cronRestoreOldAgents($task)
    {
        global $DB;

        $CronTask = new CronTask();
        if ($CronTask->getFromDBbyName("PluginOcsinventoryngOcsServer", "RestoreOldAgents")) {
            if ($CronTask->fields["state"] == CronTask::STATE_DISABLE) {
                return 0;
            }
        } else {
            return 0;
        }

        $cron_status                         = 0;
        $plugin_ocsinventoryng_ocsservers_id = 0;
        foreach ($DB->request([
            'FROM' => 'glpi_plugin_ocsinventoryng_ocsservers',
            'WHERE' => ['is_active' => 1, 'use_cleancron' => 1, 'use_restorationcron' => 1]]) as $config) {
            $plugin_ocsinventoryng_ocsservers_id = $config["id"];
            if ($plugin_ocsinventoryng_ocsservers_id > 0) {
                $delay = $config['delay_restorationcron'];

                $query = "SELECT `glpi_computers`.`id`
                     FROM `glpi_computers` 
                     INNER JOIN `glpi_plugin_ocsinventoryng_ocslinks` AS ocslink 
                        ON ocslink.`computers_id` = `glpi_computers`.`id`
                     WHERE `glpi_computers`.`is_deleted` = 1 
                     AND ( unix_timestamp(ocslink.`last_ocs_update`) >= UNIX_TIMESTAMP(NOW() - INTERVAL $delay DAY))";

                $result = $DB->doQuery($query);
                if ($DB->numrows($result) > 0) {
                    $computer = new Computer();
                    while ($data = $DB->fetchAssoc($result)) {
                        $computer->fields['id']         = $data['id'];
                        $computer->fields['is_deleted'] = 0;
                        $computer->fields['date_mod']   = $_SESSION['glpi_currenttime'];
                        $computer->updateInDB(['is_deleted', 'date_mod']);
                        //add history
                        $changes[0] = 0;
                        $changes[2] = "";
                        $changes[1] = "";
                        Log::history(
                            $data['id'],
                            'Computer',
                            $changes,
                            0,
                            Log::HISTORY_RESTORE_ITEM
                        );
                        //                  $computer->update(array('id' => $data['id'], 'is_deleted' => 0));
                    }
                    $cron_status = 1;
                    if ($task) {
                        $task->addVolume($DB->numrows($result));
                        $task->log(__('Restore computers OK', 'ocsinventoryng'));
                    }
                } else {
                    $task->log(__('Restore computers failed', 'ocsinventoryng'));
                }
            }
        }

        return $cron_status;
    }


    /**
     * @param $task
     *
     * @return int
     */
    public static function cronocsng($task)
    {
        global $DB;

        $original_glpiname    = $_SESSION['glpiname'] ?? null;
        $_SESSION["glpiname"] = 'ocsinventoryng';

        try {
            //Get a randon server id
            $plugin_ocsinventoryng_ocsservers_id = self::getRandomServerID();

            if ($plugin_ocsinventoryng_ocsservers_id > 0) {
                //Initialize the server connection
                $PluginOcsinventoryngDBocs = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);

                $cfg_ocs = self::getConfig($plugin_ocsinventoryng_ocsservers_id);
                $task->log(__('Launch OCSNG synchronization script from server', 'ocsinventoryng') . " " . $cfg_ocs['name'] . "\n");

                if (!$cfg_ocs["cron_sync_number"]) {
                    return 0;
                }
                PluginOcsinventoryngOcsProcess::manageDeleted($plugin_ocsinventoryng_ocsservers_id, false);

                //         $query    = "SELECT MAX(`last_ocs_update`)
                //                   FROM `glpi_plugin_ocsinventoryng_ocslinks`
                //                   WHERE `plugin_ocsinventoryng_ocsservers_id`= $plugin_ocsinventoryng_ocsservers_id";
                //         $max_date = "0000-00-00 00:00:00";
                //         if ($result = $DB->doQuery($query)) {
                //            if ($DB->numrows($result) > 0) {
                //               $max_date = $DB->result($result, 0, 0);

                // Get linked computer ids in GLPI
                $already_linked_query  =
                    "SELECT `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid` AS ocsid " .
                    "FROM `glpi_plugin_ocsinventoryng_ocslinks` " .
                    "INNER JOIN `glpi_computers` on `glpi_computers`.`id` = `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id` " .
                    "WHERE `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id` = $plugin_ocsinventoryng_ocsservers_id";
                $already_linked_result = $DB->doQuery($already_linked_query);

                $already_linked_ids = [];
                if ($DB->numrows($already_linked_result) > 0) {
                    while ($data = $DB->fetchAssoc($already_linked_result)) {
                        $already_linked_ids [] = $data['ocsid'];
                    }
                }

                // Fetch linked computers from ocs
                $res = [];
                if (count($already_linked_ids) > 0) {
                    $ocsResult = $PluginOcsinventoryngDBocs->getComputers([
                        'MAX_RECORDS' => $cfg_ocs["cron_sync_number"],
                        'ORDER'       => 'LASTDATE',
                        'COMPLETE'    => '0',
                        'FILTER'      => [
                            'IDS'      => $already_linked_ids,
                            'CHECKSUM' => $cfg_ocs["checksum"],
                        ],
                    ]);

                    if (isset($ocsResult['COMPUTERS']) && count($ocsResult['COMPUTERS']) > 0) {
                        foreach ($ocsResult['COMPUTERS'] as $computer) {
                            $res[$computer['META']['ID']] = $computer['META'];
                        }
                    }
                }

                $task->setVolume(0);
                if (count($res) > 0) {
                    foreach ($res as $k => $data) {
                        if (count($data) > 0) {
                            // Fetch all linked computers from GLPI that were returned from OCS
                            $query_glpi  = "SELECT `id`, `ocs_deviceid`
                                    FROM `glpi_plugin_ocsinventoryng_ocslinks`
                                    WHERE `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id`
                                                = $plugin_ocsinventoryng_ocsservers_id
                                          AND `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid` = '" . $data["ID"] . "'";
                            $result_glpi = $DB->doQuery($query_glpi);
                            if ($DB->numrows($result_glpi) > 0) {
                                while ($values = $DB->fetchAssoc($result_glpi)) {
                                    $task->addVolume(1);
                                    $task->log(sprintf(
                                        __('%1$s: %2$s'),
                                        _n('Computer', 'Computer', 1),
                                        sprintf(__('%1$s (%2$s)'), $values["ocs_deviceid"], $values["id"])
                                    ));

                                    $sync_params = ['ID'                                  => $values["id"],
                                        'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                                        'cfg_ocs'                             => $cfg_ocs,
                                        'force'                               => 0];
                                    PluginOcsinventoryngOcsProcess::synchronizeComputer($sync_params);
                                }
                            }
                        }
                    }
                } else {
                    return 0;
                }
            }
            return 1;
        } catch (Exception $e) {
        } finally {
            $_SESSION['glpiname'] = $original_glpiname;
        }
    }

    /**
     * For other plugins, add a type to the linkable types
     *
     *
     * @param $type string class name
     * */
    public static function registerType($type)
    {
        if (!in_array($type, self::$types)) {
            self::$types[] = $type;
        }
    }

    /**
     * Type than could be linked to a Store
     *
     * @param $all boolean, all type, or only allowed ones
     *
     * @return array of types
     * */
    public static function getTypes($all = false)
    {
        if ($all) {
            return self::$types;
        }

        // Only allowed types
        $types = self::$types;

        foreach ($types as $key => $type) {
            $dbu = new DbUtils();
            if (!($item = $dbu->getItemForItemtype($type))) {
                continue;
            }

            if (!$item->canView()) {
                unset($types[$key]);
            }
        }
        return $types;
    }


    /**
     * @param $id
     */
    public static function showOcsReportsConsole($id)
    {
        $ocsconfig = self::getConfig($id);

        echo "<div class='center'>";
        if ($ocsconfig["ocs_url"] != '') {
            echo "<iframe src='" . $ocsconfig["ocs_url"] . "/index.php?multi=4' width='95%' height='650'>";
        }
        echo "</div>";
    }

    /**
     * @param $target
     * *@since version 0.84
     *
     */
    public static function checkBox($target)
    {
        echo "<a href='" . $target . "?check=all' " .
             "onclick= \"if (markCheckboxes('ocsng_form')) return false;\">" . __('Check all') .
             "</a>&nbsp;/&nbsp;\n";
        echo "<a href='" . $target . "?check=none' " .
             "onclick= \"if ( unMarkCheckboxes('ocsng_form') ) return false;\">" .
             __('Uncheck all') . "</a>\n";
    }



    /**
     * @param null $checkitem
     *
     * @return an
     * @since version 0.85
     *
     * @see CommonDBTM::getSpecificMassiveActions()
     *
     */
    //   function getSpecificMassiveActions($checkitem = null) {
    //
    //      $actions = parent::getSpecificMassiveActions($checkitem);
    //
    //      return $actions;
    //   }
    //

    /**
     * @internal param $width
     */
    public function showSystemInformations()
    {
        $dbu        = new DbUtils();
        $ocsServers = $dbu->getAllDataFromTable('glpi_plugin_ocsinventoryng_ocsservers', ["is_active" => 1]);
        if (!empty($ocsServers)) {
            echo "\n<tr class='tab_bg_2'><th>OCS Inventory NG</th></tr>\n";

            $msg = '';
            foreach ($ocsServers as $ocsServer) {
                echo "<tr class='tab_bg_1'><td>";
                $msg = __('Host', 'ocsinventoryng') . ": " . $ocsServer['ocs_db_host'] . "";
                $msg .= "<br>" . __('Connection') . ": " . (self::checkOCSconnection($ocsServer['id']) ? "Ok" : "KO");
                $msg .= "<br>" . __('Use the OCSNG software dictionary', 'ocsinventoryng') . ": " .
                        ($ocsServer['use_soft_dict'] ? 'Yes' : 'No');
                echo $msg;
                echo "</td></tr>";
            }
        }
    }

    /**
     * Get all languages for a specific library
     *
     * @return array $languages
     * @internal param string $name name of the library :
     *    Currently available :
     *        sDashboard (for Datatable),
     *        mydashboard (for our own)
     */
    public static function getJsLanguages()
    {
        $languages                    = [];
        $languages['sEmptyTable']     = __('No data available in table', 'ocsinventoryng');
        $languages['sInfo']           = __('Showing _START_ to _END_ of _TOTAL_ entries', 'ocsinventoryng');
        $languages['sInfoEmpty']      = __('Showing 0 to 0 of 0 entries', 'ocsinventoryng');
        $languages['sInfoFiltered']   = __('(filtered from _MAX_ total entries)', 'ocsinventoryng');
        $languages['sInfoPostFix']    = __('');
        $languages['sInfoThousands']  = __(',');
        $languages['sLengthMenu']     = __('Show _MENU_ entries', 'ocsinventoryng');
        $languages['sLoadingRecords'] = __('Loading') . "...";
        $languages['sProcessing']     = __('Processing') . "...";
        $languages['sSearch']         = __('Search') . ":";
        $languages['sZeroRecords']    = __('No matching records found', 'ocsinventoryng');
        $languages['oPaginate']       = [
            'sFirst'    => __('First'),
            'sLast'     => __('Last'),
            'sNext'     => " " . __('Next'),
            'sPrevious' => __('Previous'),
        ];
        $languages['oAria']           = [
            'sSortAscending'  => __(': activate to sort column ascending', 'ocsinventoryng'),
            'sSortDescending' => __(': activate to sort column descending', 'ocsinventoryng'),
        ];
        $languages['close']           = __("Close", "ocsinventoryng");
        $languages['maximize']        = __("Maximize", "ocsinventoryng");
        $languages['minimize']        = __("Minimize", "ocsinventoryng");
        $languages['refresh']         = __("Refresh", "ocsinventoryng");
        $languages['buttons']         = [
            'colvis' => __('Column visibility', 'ocsinventoryng'),
        ];

        $languages['select'] = [
            'rows' => __('%d rows selected', 'ocsinventoryng'),
        ];

        return $languages;
    }
}
