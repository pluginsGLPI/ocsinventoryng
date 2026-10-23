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

use CommonDBTM;
use CommonGLPI;
use Computer;
use CronTask;
use DBmysql;
use DbUtils;
use Dropdown;
use Entity;
use Exception;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use GLPIKey;
use Html;
use Log;
use Rule;
use RuleImportAsset;
use RuleImportEntity;
use Search;
use Session;
use Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/// OCS config class

/**
 * Class OcsServer
 */
class OcsServer extends CommonDBTM
{
    public static $types     = ['Computer'];
    public static string $rightname = "plugin_ocsinventoryng";
    // From CommonDBTM
    public bool $dohistory = true;
    // The password of the OCS database, even encrypted, never goes to the history
    public array $history_blacklist = ['ocs_db_passwd'];

    /**
     * The password of the OCS database is never sent by the API nor the exports
     */
    public static function unsetUndisclosedFields(&$fields)
    {
        parent::unsetUndisclosedFields($fields);
        unset($fields['ocs_db_passwd']);
    }

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
     * @return string
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

    public static function getIcon()
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
        $this->addStandardTab(SnmpOcslink::class, $ong, $options);
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
     * Display the choice of the OCS server used by the plugin pages
     * (the active servers allowed for the active profile)
     *
     * @return void (display)
     */
    public static function showServerChoice()
    {
        global $DB;

        $dbu = new DbUtils();
        if ($dbu->countElementsInTable('glpi_plugin_ocsinventoryng_ocsservers', ["is_active" => 1]) == 0) {
            return;
        }

        $ocsservers = [];
        $iterator   = $DB->request([
            'SELECT'    => [
                'glpi_plugin_ocsinventoryng_ocsservers.id',
            ],
            'FROM'      => 'glpi_plugin_ocsinventoryng_ocsservers_profiles',
            'LEFT JOIN' => [
                'glpi_plugin_ocsinventoryng_ocsservers' => [
                    'ON' => [
                        'glpi_plugin_ocsinventoryng_ocsservers_profiles' => 'plugin_ocsinventoryng_ocsservers_id',
                        'glpi_plugin_ocsinventoryng_ocsservers'          => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'profiles_id' => $_SESSION["glpiactiveprofile"]['id'],
                'is_active'   => 1,
            ],
            'ORDERBY'   => 'name ASC',
        ]);
        foreach ($iterator as $data) {
            $ocsservers[] = $data['id'];
        }

        TemplateRenderer::getInstance()->display('@ocsinventoryng/server_choice.html.twig', [
            'action'   => PLUGIN_OCS_WEBDIR . "/front/ocsng.php",
            'itemtype' => self::class,
            'value'    => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
            'servers'  => $ocsservers,
        ]);
    }

    /**
     * Name and status of an OCS server allowed for the active profile
     *
     * @param $plugin_ocsinventoryng_ocsservers_id
     *
     * @return array [name, is_active] ('' and 0 when the server is not allowed)
     */
    public static function getServerState($plugin_ocsinventoryng_ocsservers_id)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_plugin_ocsinventoryng_ocsservers.name',
                'glpi_plugin_ocsinventoryng_ocsservers.is_active',
            ],
            'FROM'      => 'glpi_plugin_ocsinventoryng_ocsservers',
            'LEFT JOIN' => [
                'glpi_plugin_ocsinventoryng_ocsservers_profiles' => [
                    'ON' => [
                        'glpi_plugin_ocsinventoryng_ocsservers_profiles' => 'plugin_ocsinventoryng_ocsservers_id',
                        'glpi_plugin_ocsinventoryng_ocsservers'          => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_plugin_ocsinventoryng_ocsservers.id'                   => (int) $plugin_ocsinventoryng_ocsservers_id,
                'glpi_plugin_ocsinventoryng_ocsservers_profiles.profiles_id' => $_SESSION["glpiactiveprofile"]['id'],
            ],
        ]);
        foreach ($iterator as $data) {
            return [$data["name"], $data["is_active"]];
        }

        return ['', 0];
    }

    /**
     * Print ocs setup menu
     *
     * @param $plugin_ocsinventoryng_ocsservers_id Integer : Id of the ocs config
     *
     * @return void (display)
     */
    public static function setupMenu($plugin_ocsinventoryng_ocsservers_id)
    {
        self::showServerChoice();

        [$name, $isactive] = self::getServerState($plugin_ocsinventoryng_ocsservers_id);

        $sections = [];
        if (Session::haveRight(OcsServer::$rightname, READ) && $isactive) {
            $items   = [];
            $items[] = [
                'title' => sprintf(__('Configuration of OCSNG server %s', 'ocsinventoryng'), $name),
                'url'   => PLUGIN_OCS_WEBDIR . "/front/ocsserver.form.php?id=" . (int) $plugin_ocsinventoryng_ocsservers_id,
                'icon'  => 'ti-settings',
            ];
            if (self::useMassImport() && Session::haveRight(OcsServer::$rightname, UPDATE)) {
                $items[] = [
                    'title' => __("Automatic synchronization's configuration", 'ocsinventoryng'),
                    'url'   => PLUGIN_OCS_WEBDIR . "/front/config.form.php",
                    'icon'  => 'ti-refresh',
                ];
            }
            $sections[] = [
                'title' => sprintf(__('%1$s %2$s'), __('OCSNG server', 'ocsinventoryng'), $name),
                'icon'  => self::getIcon(),
                'items' => $items,
            ];
        }

        $sections[] = [
            'title' => __('Setup rules engine', 'ocsinventoryng'),
            'icon'  => 'ti ti-book-2',
            'items' => [
                [
                    'title'       => __('Rules for assigning an item to an entity'),
                    'description' => __('Setup rules for choose entity on items import', 'ocsinventoryng'),
                    'url'         => RuleImportEntity::getSearchURL(),
                    'icon'        => 'ti-book-2',
                ],
                [
                    'title'       => __('Rules for import and link equipments'),
                    'description' => __('Setup rules for select criteria for items link', 'ocsinventoryng'),
                    'url'         => RuleImportAsset::getSearchURL(),
                    'icon'        => 'ti-book-2',
                ],
            ],
        ];

        Menu::displayMenuSections($sections);
    }


    /**
     * Print ocs import menu
     *
     * @param $plugin_ocsinventoryng_ocsservers_id Integer : Id of the ocs config
     *
     * @return void (display)
     */
    public static function importMenu($plugin_ocsinventoryng_ocsservers_id)
    {
        self::showServerChoice();

        [$name, $isactive] = self::getServerState($plugin_ocsinventoryng_ocsservers_id);
        $usemassimport     = self::useMassImport();

        $section = [
            'title' => sprintf(__('%1$s %2$s'), __('OCSNG server', 'ocsinventoryng'), $name),
            'icon'  => self::getIcon(),
            'items' => [],
        ];
        if (Session::haveRight(OcsServer::$rightname, READ) && $isactive) {
            $section['link'] = [
                'title' => __('See Setup : Datas to import before', 'ocsinventoryng'),
                'url'   => PLUGIN_OCS_WEBDIR . "/front/ocsserver.form.php?id=" . (int) $plugin_ocsinventoryng_ocsservers_id
                    . "&forcetab=" . urlencode(self::class . '$2'),
            ];
        }

        if (Session::haveRight(OcsServer::$rightname, READ)) {
            if ($isactive) {
                if (Session::haveRight(Profile::RIGHT_IMPORT, READ)) {
                    $section['items'][] = [
                        'title' => __('Import or link new computers', 'ocsinventoryng'),
                        'url'   => PLUGIN_OCS_WEBDIR . "/front/ocsng.import.php",
                        'icon'  => 'ti-plus',
                    ];
                }
                if (Session::haveRight(Profile::RIGHT_SYNC, READ)) {
                    $section['items'][] = [
                        'title' => __('Synchronize computers already imported', 'ocsinventoryng'),
                        'url'   => PLUGIN_OCS_WEBDIR . "/front/ocsng.sync.php",
                        'icon'  => 'ti-refresh',
                    ];
                }
                if ($usemassimport && Session::haveRight(OcsServer::$rightname, UPDATE)) {
                    $section['items'][] = [
                        'title' => __('Scripts execution of automatic actions', 'ocsinventoryng'),
                        'url'   => PLUGIN_OCS_WEBDIR . "/front/thread.php?plugin_ocsinventoryng_ocsservers_id="
                            . (int) $plugin_ocsinventoryng_ocsservers_id,
                        'icon'  => 'ti-player-play',
                    ];
                }
                if (Config::logProcessedComputers()
                    && Session::haveRight(Profile::RIGHT_IMPORT, READ)
                    && $usemassimport) {
                    $section['items'][] = [
                        'title' => __('Computers imported by automatic actions', 'ocsinventoryng'),
                        'url'   => PLUGIN_OCS_WEBDIR . "/front/detail.php",
                        'icon'  => 'ti-check',
                        'color' => 'text-green',
                    ];
                }
                if ($usemassimport && Session::haveRight(OcsServer::$rightname, UPDATE)) {
                    $section['items'][] = [
                        'title' => __('Computers not imported by automatic actions', 'ocsinventoryng'),
                        'url'   => PLUGIN_OCS_WEBDIR . "/front/notimportedcomputer.php",
                        'icon'  => 'ti-x',
                        'color' => 'text-red',
                    ];
                }
            } else {
                $section['alert'] = __('The selected server is not active. Import and synchronisation is not available', 'ocsinventoryng');
            }
        }

        if ((Session::haveRight(Profile::RIGHT_CLEAN, UPDATE)
             || Session::haveRight(static::$rightname, UPDATE))
            && $isactive) {
            if (Session::haveRight(static::$rightname, UPDATE)) {
                $deleted = [
                    'title'       => __('Clean OCSNG deleted computers', 'ocsinventoryng'),
                    'description' => __('Update ID of deleted computers of OCSNG', 'ocsinventoryng'),
                    'url'         => PLUGIN_OCS_WEBDIR . "/front/deleted_equiv.php",
                    'icon'        => 'ti-trash',
                ];
                $deleted_pcs = 0;
                if (self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)) {
                    $deleted_pcs = self::getDBocs($plugin_ocsinventoryng_ocsservers_id)->getTotalDeletedComputers();
                }
                if ($deleted_pcs > 0) {
                    $deleted['badge'] = $deleted_pcs . " " . __('Pc deleted', 'ocsinventoryng');
                }
                $section['items'][] = $deleted;
            }
            $section['items'][] = [
                'title'       => __('Clean links between GLPI and OCSNG', 'ocsinventoryng'),
                'description' => __('Drop links for not present computers into OCSNG', 'ocsinventoryng'),
                'url'         => PLUGIN_OCS_WEBDIR . "/front/ocsng.clean.php",
                'icon'        => 'ti-recycle',
            ];
        }

        Menu::displayMenuSections([$section]);
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
        if (!Session::haveRight(OcsServer::$rightname, READ)) {
            return false;
        }
        $this->getFromDB($ID);

        // A yes/no field; the helper is the former tooltip
        $yesno = fn(string $name, string $label, string $helper = '') => [
            'type'   => 'yesno',
            'name'   => $name,
            'value'  => $this->fields[$name],
            'label'  => $label,
            'helper' => $helper,
        ];
        // A list set by the "All" dropdown too (0: no import, 1: first kind of import)
        $import = fn(string $name, string $label, array $values) => [
            'type'    => 'array',
            'name'    => $name,
            'value'   => $this->fields[$name],
            'values'  => $values,
            'label'   => $label,
            'options' => ['class' => 'form-select ocs-yesno'],
        ];
        $depends_on_bios = __('Depends on Bios import', 'ocsinventoryng');

        // General information
        $general = [
            $yesno('import_general_name', __('Name')),
            $yesno('import_general_os', __('Operating system')),
            $yesno('import_os_serial', __('Serial of the operating system')),
            $yesno('import_general_serial', __('Serial number'), $depends_on_bios),
            $yesno('import_general_model', __('Model'), $depends_on_bios),
            $yesno('import_general_manufacturer', _n('Manufacturer', 'Manufacturers', 1), $depends_on_bios),
            $yesno('import_general_type', __('Type'), $depends_on_bios),
            $yesno('import_general_domain', __('Domain')),
            $yesno('import_general_comment', __('Comments')),
        ];
        $general[] = self::checkVersion($ID)
            ? $yesno('import_general_uuid', __('UUID'))
            : ['type' => 'hidden', 'name' => 'import_general_uuid', 'value' => 0];
        $general[] = $yesno('import_ip', __('IP'));
        $general[] = ['type' => 'title', 'label' => __('User informations', 'ocsinventoryng')];
        $general[] = $yesno('import_general_contact', __('Alternate username'));
        $general[] = $import('link_with_user', __('Link with user', 'ocsinventoryng'), [
            0 => __('No import'),
            1 => __('From user login / contact', 'ocsinventoryng'),
        ]);
        if ($this->fields["link_with_user"] > 0) {
            $general[] = $yesno('import_user_group_default', __('Affect default group of user by default', 'ocsinventoryng'));
            $general[] = $yesno('import_user_location', __('Affect user location by default', 'ocsinventoryng'));
            $general[] = $yesno('import_user_group', __('Affect first group of user by default', 'ocsinventoryng'));
        }

        // Linked objects
        $import_kinds = [
            0 => __('No import'),
            1 => __('Global import', 'ocsinventoryng'),
            2 => __('Unit import', 'ocsinventoryng'),
        ];
        $linked = [
            $import('import_periph', _n('Device', 'Devices', 2), $import_kinds),
            $import('import_monitor', _n('Monitor', 'Monitors', 2), $import_kinds + [
                3 => __('Unit import on serial number', 'ocsinventoryng'),
                4 => __('Unit import serial number only', 'ocsinventoryng'),
            ]),
            $yesno('import_monitor_comment', __('Comments') . " " . _n('Monitor', 'Monitors', 2)),
            $import('import_printer', _n('Printer', 'Printers', 2), $import_kinds),
            $import('import_software', _n('Software', 'Software', 2), [
                0 => __('No import'),
                1 => __('Unit import', 'ocsinventoryng'),
            ]),
            $yesno('import_disk', _n('Volume', 'Volumes', 2)),
            $yesno('import_registry', __('Registry', 'ocsinventoryng')),
        ];
        $linked[] = $this->fields['ocs_version'] > self::OCS1_3_VERSION_LIMIT
            ? $yesno('import_vms', _n('Virtual machine', 'Virtual machines', 2))
            : ['type' => 'hidden', 'name' => 'import_vms', 'value' => 0];

        // Administrative information: OCS columns, not set by the "All" dropdown
        $accountinfo             = OcsAdminInfosLink::getColumnListFromAccountInfoTable($ID, 'accountinfo');
        $otherserial             = $accountinfo;
        $otherserial['ASSETTAG'] = "ASSETTAG";
        $admin_column = function (string $name, string $glpi_column, string $label, array $values) use ($ID) {
            $link = new OcsAdminInfosLink();
            $link->getFromDBbyOcsServerIDAndGlpiColumn($ID, $glpi_column);
            return [
                'type'   => 'array',
                'name'   => $name,
                'value'  => $link->fields["ocs_column"] ?? "",
                'values' => $values,
                'label'  => $label,
            ];
        };

        self::showOptionsForm($ID, [
            [
                'title'  => __('General information', 'ocsinventoryng'),
                'icon'   => 'ti ti-device-desktop',
                'alerts' => [__('Warning : the import entity rules depends on selected fields', 'ocsinventoryng')],
                'fields' => $general,
            ],
            [
                'title'  => _n('Component', 'Components', 2),
                'icon'   => 'ti ti-cpu',
                'fields' => [
                    $yesno('import_device_processor', __('Processor'), __('After 7006 version of OCS Inventory NG', 'ocsinventoryng')),
                    $yesno('import_device_memory', __('Memory')),
                    $yesno('import_device_hdd', __('Hard drive')),
                    $yesno('import_device_iface', __('Network card')),
                    $yesno('import_device_gfxcard', __('Graphics card')),
                    $yesno('import_device_sound', __('Soundcard')),
                    $yesno('import_device_drive', _n('Drive', 'Drives', 2)),
                    $yesno('import_device_modem', __('Modems', 'ocsinventoryng')),
                    $yesno('import_device_port', _n('Port', 'Ports', 2)),
                    $yesno('import_device_bios', __('Bios', 'ocsinventoryng')),
                    $yesno('import_device_motherboard', __('System board'), __('After 7009 version of OCS Inventory NG && Depends on Bios import', 'ocsinventoryng')),
                    $yesno('import_device_controller', _n('Controller', 'Controllers', 2)),
                    $yesno('import_device_slot', __('Other component', 'ocsinventoryng')),
                ],
            ],
            [
                'title'  => __('Linked objects', 'ocsinventoryng'),
                'icon'   => 'ti ti-link',
                'alerts' => [
                    __('No import: the plugin will not import these elements', 'ocsinventoryng'),
                    __('Global import: everything is imported but the material is globally managed (without duplicate)', 'ocsinventoryng'),
                    __("Unit import: everything is imported as it is", 'ocsinventoryng'),
                ],
                'fields' => $linked,
            ],
            [
                'title'  => __('OCS Inventory NG plugins', 'ocsinventoryng'),
                'icon'   => 'ti ti-puzzle',
                'fields' => [
                    $yesno('import_officepack', __('Microsoft Office licenses', 'ocsinventoryng'), __('Depends on software import and OfficePack Plugin for (https://github.com/PluginsOCSInventory-NG/officepack) OCSNG must be installed', 'ocsinventoryng')),
                    $yesno('import_antivirus', __('Antivirus', 'ocsinventoryng'), __('Security Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/security) must be installed', 'ocsinventoryng')),
                    $yesno('import_uptime', __('Uptime', 'ocsinventoryng'), __('Uptime Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/uptime) must be installed', 'ocsinventoryng')),
                    $yesno('import_winupdatestate', __('Windows Update State', 'ocsinventoryng'), __('Winupdate Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/winupdate) must be installed', 'ocsinventoryng')),
                    $yesno('import_teamviewer', __('Teamviewer', 'ocsinventoryng'), __('Teamviewer Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/teamviewer) must be installed', 'ocsinventoryng')),
                    $yesno('import_proxysetting', __('Proxy Settings', 'ocsinventoryng'), __('Navigator Proxy Setting Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/navigatorproxysetting) must be installed', 'ocsinventoryng')),
                    $yesno('import_winusers', __('Windows Users', 'ocsinventoryng'), __('Winusers Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/winusers) must be installed', 'ocsinventoryng')),
                    $yesno('import_osinstall', __('OS Informations', 'ocsinventoryng'), __('OSInstall Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/osinstall) must be installed', 'ocsinventoryng')),
                    $yesno('import_networkshare', __('Network shares', 'ocsinventoryng'), __('Networkshare Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/networkshare) must be installed', 'ocsinventoryng')),
                    $yesno('import_service', __('Service', 'ocsinventoryng'), __('Service Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/services) must be installed', 'ocsinventoryng')),
                    $yesno('import_runningprocess', __('Running Process', 'ocsinventoryng'), __('Running Process Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/runningProcess) must be installed', 'ocsinventoryng')),
                    $yesno('import_customapp', __('Customapp', 'ocsinventoryng'), __('Customapp Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/customapp) must be installed', 'ocsinventoryng')),
                    $yesno('import_bitlocker', __('Bitlocker', 'ocsinventoryng'), __('Bitlocker Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/bitlocker) must be installed', 'ocsinventoryng')),
                ],
            ],
            [
                'title'  => __('OCSNG administrative information', 'ocsinventoryng'),
                'icon'   => 'ti ti-id',
                'fields' => [
                    $admin_column('import_otherserial', 'otherserial', __('Inventory number'), $otherserial),
                    $admin_column('import_location', 'locations_id', __('Location'), $accountinfo),
                    $admin_column('import_group', 'groups_id', __('Group'), $accountinfo),
                    $admin_column('import_contact_num', 'contact_num', __('Alternate username number'), $accountinfo),
                    $admin_column('import_network', 'networks_id', __('Network'), $accountinfo),
                    $admin_column(
                        'import_use_date',
                        'use_date',
                        __('Startup date'),
                        OcsAdminInfosLink::getColumnListFromAccountInfoTable($ID, 'hardware'),
                    ),
                ],
            ],
        ], true);

        return true;
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
        if (!Session::haveRight(OcsServer::$rightname, READ)) {
            return false;
        }
        $this->getFromDB($ID);

        $system_fields = [
            __('Operating system'),
            __('Serial of the operating system'),
            __('Domain'),
            __('Alternate username'),
            __('Comments'),
        ];
        $bios_fields = [__('Serial number'), __('Model'), _n('Manufacturer', 'Manufacturers', 1), __('Type')];
        if (self::checkVersion($ID)) {
            $bios_fields[] = __('UUID');
        }

        $fields = [
            ['type' => 'yesno', 'name' => 'dohistory', 'value' => $this->fields['dohistory'],
                'label' => __('Do history', 'ocsinventoryng')],
            ['type' => 'yesno', 'name' => 'history_hardware', 'value' => $this->fields['history_hardware'],
                'label' => __('System history', 'ocsinventoryng'), 'helper' => implode(', ', $system_fields)],
            ['type' => 'yesno', 'name' => 'history_bios', 'value' => $this->fields['history_bios'],
                'label' => __('Bios history', 'ocsinventoryng'), 'helper' => implode(', ', $bios_fields)],
        ];
        foreach ([
            'history_devices'    => __('Devices history', 'ocsinventoryng'),
            'history_drives'     => __('Volumes history', 'ocsinventoryng'),
            'history_network'    => __('Network history', 'ocsinventoryng'),
            'history_monitor'    => __('Monitor connection history', 'ocsinventoryng'),
            'history_printer'    => __('Printer connection history', 'ocsinventoryng'),
            'history_peripheral' => __('Peripheral connection history', 'ocsinventoryng'),
            'history_software'   => __('Software connection history', 'ocsinventoryng'),
            'history_vm'         => __('Virtual machines history', 'ocsinventoryng'),
            'history_admininfos' => __('Administrative infos history', 'ocsinventoryng'),
            'history_plugins'    => __('Plugins history', 'ocsinventoryng'),
            'history_os'         => __('OS history', 'ocsinventoryng'),
        ] as $name => $label) {
            // Set by the "All" dropdown too: 0 is none, 1 every change
            $fields[] = ['type' => 'array', 'name' => $name, 'value' => $this->fields[$name],
                'values' => self::getHistoryValues(), 'label' => $label,
                'options' => ['class' => 'form-select ocs-yesno']];
        }

        self::showOptionsForm($ID, [
            [
                'title'  => __('General history', 'ocsinventoryng'),
                'icon'   => 'ti ti-history',
                'fields' => $fields,
            ],
        ], true);

        return true;
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

        $deleted_actions = [
            0 => Dropdown::EMPTY_VALUE,
            1 => __('Put in trashbin'),
        ];
        $dbu = new DbUtils();
        foreach ($dbu->getAllDataFromTable('glpi_states') as $state) {
            $deleted_actions['STATE_' . $state['id']] = sprintf(__('Change to state %s', 'ocsinventoryng'), $state['name']);
        }

        // As Dropdown::showNumber(): none, 1 to 100, and the stored value
        $sync_numbers = [0 => __('None')];
        for ($i = 1; $i <= 100; $i++) {
            $sync_numbers[$i] = $i;
        }
        $sync_numbers[(int) $this->fields['cron_sync_number']] ??= (int) $this->fields['cron_sync_number'];

        self::showOptionsForm($ID, [
            [
                'title'  => __('Import options', 'ocsinventoryng'),
                'icon'   => 'ti ti-download',
                'fields' => [
                    ['type' => 'text', 'name' => 'ocs_url', 'value' => $this->fields['ocs_url'],
                        'label' => __('Web address of the OCSNG console', 'ocsinventoryng'), 'full_width' => true],
                    ['type' => 'text', 'name' => 'tag_limit', 'value' => $this->fields['tag_limit'],
                        'label' => __('Limit the import to the following tags (separator $, nothing for all)', 'ocsinventoryng')],
                    ['type' => 'text', 'name' => 'tag_exclude', 'value' => $this->fields['tag_exclude'],
                        'label' => __('Exclude the following tags (separator $, nothing for all)', 'ocsinventoryng')],
                ],
            ],
            [
                'title'  => __('Behavior when disconnecting', 'ocsinventoryng'),
                'icon'   => 'ti ti-plug-connected-x',
                'alerts' => [__("Define the action to do on link with other objects when computer is disconnecting from them", 'ocsinventoryng')],
                'fields' => [
                    ['type' => 'array', 'name' => 'deconnection_behavior', 'value' => $this->fields['deconnection_behavior'],
                        'values' => [
                            ''       => __('Preserve link', 'ocsinventoryng'),
                            'trash'  => __('Put the link in dustbin and add a lock', 'ocsinventoryng'),
                            'delete' => __('Delete  the link permanently', 'ocsinventoryng'),
                        ],
                        'label' => __('Behavior when disconnecting', 'ocsinventoryng')],
                    ['type' => 'array', 'name' => 'deleted_behavior', 'value' => $this->fields['deleted_behavior'],
                        'values' => $deleted_actions,
                        'label' => __('Behavior to the deletion of a computer in OCSNG', 'ocsinventoryng')],
                ],
            ],
            [
                'title'  => __('Use the OCSNG software dictionary', 'ocsinventoryng'),
                'icon'   => 'ti ti-book',
                'alerts' => [__("If Use the OCSNG software dictionary parameter is checked, no software will be imported before you setup them into OCS", 'ocsinventoryng')],
                'fields' => [
                    ['type' => 'yesno', 'name' => 'use_soft_dict', 'value' => $this->fields['use_soft_dict'],
                        'label' => __('Use the OCSNG software dictionary', 'ocsinventoryng')],
                ],
            ],
            [
                'title'  => __('Automatic actions'),
                'icon'   => 'ti ti-player-play',
                'alerts' => [
                    __("The automatic task ocsng is launched only if server is not in expert mode", 'ocsinventoryng'),
                    __("The automatic task ocsng only synchronize existant computers, it doesn't import new computers", 'ocsinventoryng'),
                    __("If you want to import new computers, disable this parameter, change to expert mode and use script from system", 'ocsinventoryng'),
                ],
                'fields' => [
                    ['type' => 'array', 'name' => 'cron_sync_number', 'value' => (int) $this->fields['cron_sync_number'],
                        'values' => $sync_numbers,
                        'label' => __('Number of items to synchronize via the automatic OCSNG action', 'ocsinventoryng')],
                ],
            ],
        ]);
    }

    /**
     * Display a form of options of an OCS server (templates/server_options.html.twig)
     *
     * @param int   $ID       id of the OCS server
     * @param array $sections sections of fields (see the template)
     * @param bool   $set_all     whether to display the "All" dropdown setting every yes/no field
     * @param string $submit_name name of the submit button (front/ocsserver.form.php)
     *
     * @return void
     */
    public static function showOptionsForm($ID, array $sections, bool $set_all = false, string $submit_name = 'update')
    {
        TemplateRenderer::getInstance()->display('@ocsinventoryng/server_options.html.twig', [
            'form_url'    => Toolbox::getItemTypeFormURL(self::class),
            'id'          => (int) $ID,
            'sections'    => $sections,
            'set_all'     => $set_all,
            'collapsible' => count($sections) > 3,
            'canedit'     => Session::haveRight(OcsServer::$rightname, UPDATE),
            'submit_name' => $submit_name,
        ]);
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

        TemplateRenderer::getInstance()->display('@ocsinventoryng/ocsserver_form.html.twig', [
            'item'         => $this,
            'params'       => $options,
            'conn_types'   => [
                self::CONN_TYPE_DB => __('Database', 'ocsinventoryng'),
            ],
            'sync_methods' => [
                0 => __("Standard (allow manual actions)", "ocsinventoryng"),
                1 => __("Expert (Fully automatic, for large configuration)", "ocsinventoryng"),
            ],
            'cron_actions' => self::getValuesActionCron(),
        ]);

        return true;
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
        if ($ID == -1) {
            return;
        }

        $message = [
            'title' => __('Connecting to the database', 'ocsinventoryng'),
            'icon'  => 'ti-alert-triangle',
            'color' => 'bg-orange-lt',
        ];
        if (!self::checkOCSconnection($ID)) {
            $message['lines'] = [__('Connection to the database failed', 'ocsinventoryng')];
            $message['icon']  = 'ti-plug-connected-x';
            $message['color'] = 'bg-red-lt';
        } elseif (!self::checkVersion($ID)) {
            $message['lines'] = [__('Invalid OCSNG Version: RC3 is required', 'ocsinventoryng')];
        } elseif (!self::checkTraceDeleted($ID)) {
            $message['lines'] = [__('Invalid OCSNG configuration (TRACE_DELETED must be active)', 'ocsinventoryng')];
        } else {
            $message['lines'] = [
                __('Connection to database successful', 'ocsinventoryng'),
                __('Valid OCSNG configuration and version', 'ocsinventoryng'),
            ];
            $message['icon']  = 'ti-plug-connected';
            $message['color'] = 'bg-green-lt';
        }
        self::showMessage($message);
    }

    /**
     * @param array $input
     *
     * @return datas
     */
    /**
     * Whether the URL of the OCS console is a plain http(s) URL: it ends in an iframe and in
     * links of GLPI, where a javascript: URL or a quote would run a script
     *
     * @param string $url
     *
     * @return bool
     */
    public static function isValidOcsUrl(string $url): bool
    {
        return preg_match('#^https?://[^\s\'"<>`]+$#i', $url) === 1
            && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * The URL of the OCS console posted to the form: empty, or a valid http(s) URL
     *
     * @param array $input
     *
     * @return bool
     */
    private static function checkOcsUrl(array $input): bool
    {
        if (!isset($input['ocs_url']) || trim((string) $input['ocs_url']) === '') {
            return true;
        }
        if (self::isValidOcsUrl(trim((string) $input['ocs_url']))) {
            return true;
        }
        Session::addMessageAfterRedirect(
            __('The OCS console URL must start with http:// or https://', 'ocsinventoryng'),
            false,
            ERROR,
        );

        return false;
    }

    public function prepareInputForUpdate($input)
    {
        // Read from the OCS server (checkVersion(), pre_updateInDB()): never from the form
        unset($input["ocs_version"]);

        if (!self::checkOcsUrl($input)) {
            return false;
        }
        $adm = new OcsAdminInfosLink();
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
            $checksum |= pow(2, OcsProcess::HARDWARE_FL);
        }
        if ($this->fields["import_device_bios"]) {
            $checksum |= $checksum + pow(2, OcsProcess::BIOS_FL);
        }
        if ($this->fields["import_device_memory"]) {
            $checksum |= $checksum + pow(2, OcsProcess::MEMORIES_FL);
        }
        if ($this->fields["import_registry"]) {
            $checksum |= pow(2, OcsProcess::REGISTRY_FL);
        }
        if ($this->fields["import_device_controller"]) {
            $checksum |= pow(2, OcsProcess::CONTROLLERS_FL);
        }
        if ($this->fields["import_device_slot"]) {
            $checksum |= pow(2, OcsProcess::SLOTS_FL);
        }
        if ($this->fields["import_monitor"]) {
            $checksum |= pow(2, OcsProcess::MONITORS_FL);
        }
        if ($this->fields["import_device_port"]) {
            $checksum |= pow(2, OcsProcess::PORTS_FL);
        }
        if ($this->fields["import_device_hdd"] || $this->fields["import_device_drive"]) {
            $checksum |= pow(2, OcsProcess::STORAGES_FL);
        }
        if ($this->fields["import_disk"]) {
            $checksum |= pow(2, OcsProcess::DRIVES_FL);
        }
        if ($this->fields["import_periph"]) {
            $checksum |= pow(2, OcsProcess::INPUTS_FL);
        }
        if ($this->fields["import_device_modem"]) {
            $checksum |= pow(2, OcsProcess::MODEMS_FL);
        }
        if ($this->fields["import_ip"]
            || $this->fields["import_device_iface"]) {
            $checksum |= pow(2, OcsProcess::NETWORKS_FL);
        }
        if ($this->fields["import_printer"]) {
            $checksum |= pow(2, OcsProcess::PRINTERS_FL);
        }
        if ($this->fields["import_device_sound"]) {
            $checksum |= pow(2, OcsProcess::SOUNDS_FL);
        }
        if ($this->fields["import_device_gfxcard"]) {
            $checksum |= pow(2, OcsProcess::VIDEOS_FL);
        }
        if ($this->fields["import_software"]) {
            $checksum |= pow(2, OcsProcess::SOFTWARES_FL);
        }
        if ($this->fields["import_vms"]) {
            $checksum |= pow(2, OcsProcess::VIRTUALMACHINES_FL);
        }
        if ($this->fields["import_device_processor"]) {
            $checksum |= pow(2, OcsProcess::CPUS_FL);
        }

        $this->updates[]          = "checksum";
        $this->fields["checksum"] = $checksum;
    }

    /**
     * @param array $input
     *
     * @return bool|datas
     */
    public function prepareInputForAdd($input)
    {
        global $DB;

        // Read from the OCS server (checkVersion(), pre_updateInDB()): never from the form
        unset($input["ocs_version"], $input["checksum"]);

        if (!self::checkOcsUrl($input)) {
            return false;
        }

        // Check if server config does not exists (the posted name bound by the query builder)
        if (countElementsInTable($this->getTable(), ['name' => (string) ($input['name'] ?? '')]) > 0) {
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

        $DB->insert('glpi_plugin_ocsinventoryng_ocsservers_profiles', [
            'plugin_ocsinventoryng_ocsservers_id' => (int) $this->fields['id'],
            'profiles_id'                         => (int) $_SESSION["glpiactiveprofile"]['id'],
        ]);
    }

    /**
     *
     */
    public function cleanDBonPurge()
    {
        $link = new Ocslink();
        $link->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

        $admin = new OcsAdminInfosLink();
        $admin->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

        $server = new Server();
        $server->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

        $detail = new Detail();
        $detail->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

        $notimported = new Notimportedcomputer();
        $notimported->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

        $iplink = new IpdiscoverOcslink();
        $iplink->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

        $snmplink = new SnmpOcslink();
        $snmplink->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

        $prof = new Ocsserver_Profile();
        $prof->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']]);

        $thread = new Thread();
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

        $datas = $DB->request([
            'SELECT' => 'plugin_ocsinventoryng_ocsservers_id',
            'FROM'   => 'glpi_plugin_ocsinventoryng_ocslinks',
            'WHERE'  => ['computers_id' => (int) $ID],
            'LIMIT'  => 1,
        ])->current();

        return $datas ? $datas["plugin_ocsinventoryng_ocsservers_id"] : -1;
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
     */
    public static function getOCSIDByComputerID($ID, $ocsservers_id)
    {
        global $DB;

        $datas = $DB->request([
            'SELECT' => 'ocsid',
            'FROM'   => 'glpi_plugin_ocsinventoryng_ocslinks',
            'WHERE'  => [
                'computers_id'                        => (int) $ID,
                'plugin_ocsinventoryng_ocsservers_id' => (int) $ocsservers_id,
            ],
            'LIMIT'  => 1,
        ])->current();

        return $datas ? $datas["ocsid"] : -1;
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

        $datas = $DB->request([
            'SELECT' => 'id',
            'FROM'   => 'glpi_plugin_ocsinventoryng_ocsservers',
            'WHERE'  => ['is_active' => 1, 'use_massimport' => 0],
            'ORDER'  => new QueryExpression('RAND()'),
            'LIMIT'  => 1,
        ])->current();

        return $datas ? $datas["id"] : -1;
    }

    /**
     * First active OCS server allowed for the active profile, -1 when there is none
     *
     * @return int
     */
    public static function getFirstServer()
    {
        global $DB;

        if (!isset($_SESSION['glpiactiveprofile']['id'])) {
            return -1;
        }

        $server = $DB->request([
            'SELECT'     => ['glpi_plugin_ocsinventoryng_ocsservers.id'],
            'FROM'       => 'glpi_plugin_ocsinventoryng_ocsservers',
            'INNER JOIN' => [
                'glpi_plugin_ocsinventoryng_ocsservers_profiles' => [
                    'ON' => [
                        'glpi_plugin_ocsinventoryng_ocsservers_profiles' => 'plugin_ocsinventoryng_ocsservers_id',
                        'glpi_plugin_ocsinventoryng_ocsservers'          => 'id',
                    ],
                ],
            ],
            'WHERE'      => [
                'glpi_plugin_ocsinventoryng_ocsservers.is_active'            => 1,
                'glpi_plugin_ocsinventoryng_ocsservers_profiles.profiles_id' => (int) $_SESSION['glpiactiveprofile']['id'],
            ],
            'ORDER'      => 'glpi_plugin_ocsinventoryng_ocsservers.id ASC',
            'LIMIT'      => 1,
        ])->current();

        return $server ? (int) $server['id'] : -1;
    }

    /**
     * Get OCSNG mode configuration
     *
     * Get all config of the OCSNG mode
     *
     * @param $id int : ID of the OCS config (default value 1)
     *
     * @return int|null|string[] of $confVar fields or false if unfound.
     */
    public static function getConfig($id)
    {
        global $DB;

        $data = 0;

        if (!empty($id)) {
            $data = $DB->request([
                'FROM'  => 'glpi_plugin_ocsinventoryng_ocsservers',
                'WHERE' => ['id' => (int) $id],
                'LIMIT' => 1,
            ])->current() ?? 0;
        }

        return $data;
    }

    /**
     * The active OCS servers allowed for the active profile
     * (glpi_plugin_ocsinventoryng_ocsservers_profiles)
     *
     * @return int[]
     */
    public static function getAllowedServers(): array
    {
        global $DB;

        if (!isset($_SESSION['glpiactiveprofile']['id'])) {
            return [];
        }

        $servers = [];
        foreach ($DB->request([
            'SELECT'     => ['glpi_plugin_ocsinventoryng_ocsservers.id'],
            'FROM'       => 'glpi_plugin_ocsinventoryng_ocsservers',
            'INNER JOIN' => [
                'glpi_plugin_ocsinventoryng_ocsservers_profiles' => [
                    'ON' => [
                        'glpi_plugin_ocsinventoryng_ocsservers_profiles' => 'plugin_ocsinventoryng_ocsservers_id',
                        'glpi_plugin_ocsinventoryng_ocsservers'          => 'id',
                    ],
                ],
            ],
            'WHERE'      => [
                'glpi_plugin_ocsinventoryng_ocsservers.is_active'            => 1,
                'glpi_plugin_ocsinventoryng_ocsservers_profiles.profiles_id' => (int) $_SESSION['glpiactiveprofile']['id'],
            ],
        ]) as $data) {
            $servers[] = (int) $data['id'];
        }

        return $servers;
    }

    /**
     * Whether the OCS server is active and allowed for the active profile
     * (glpi_plugin_ocsinventoryng_ocsservers_profiles)
     *
     * @param int $ocsservers_id
     *
     * @return bool
     */
    public static function isAllowedServer(int $ocsservers_id): bool
    {
        global $DB;

        if ($ocsservers_id <= 0 || !isset($_SESSION['glpiactiveprofile']['id'])) {
            return false;
        }

        return count($DB->request([
            'SELECT'     => ['glpi_plugin_ocsinventoryng_ocsservers.id'],
            'FROM'       => 'glpi_plugin_ocsinventoryng_ocsservers',
            'INNER JOIN' => [
                'glpi_plugin_ocsinventoryng_ocsservers_profiles' => [
                    'ON' => [
                        'glpi_plugin_ocsinventoryng_ocsservers_profiles' => 'plugin_ocsinventoryng_ocsservers_id',
                        'glpi_plugin_ocsinventoryng_ocsservers'          => 'id',
                    ],
                ],
            ],
            'WHERE'      => [
                'glpi_plugin_ocsinventoryng_ocsservers.id'                   => $ocsservers_id,
                'glpi_plugin_ocsinventoryng_ocsservers.is_active'            => 1,
                'glpi_plugin_ocsinventoryng_ocsservers_profiles.profiles_id' => (int) $_SESSION['glpiactiveprofile']['id'],
            ],
        ])) > 0;
    }

    /**
     * The OCS server posted to a page or an endpoint: an integer, allowed for the active profile
     *
     * @param mixed $ocsservers_id
     *
     * @return int
     */
    public static function checkAllowedServer($ocsservers_id): int
    {
        $ocsservers_id = (int) $ocsservers_id;
        if (!self::isAllowedServer($ocsservers_id)) {
            throw new \Glpi\Exception\Http\AccessDeniedHttpException();
        }

        return $ocsservers_id;
    }

    /**
     * The OCS server kept in session for the pages of the plugin, checked again for the active
     * profile (the profile may have changed since it was chosen): reset to -1, "no server", when
     * it is no longer allowed
     *
     * @return int
     */
    public static function checkSessionServer(): int
    {
        $ocsservers_id = (int) ($_SESSION["plugin_ocsinventoryng_ocsservers_id"] ?? -1);
        if ($ocsservers_id > 0 && !self::isAllowedServer($ocsservers_id)) {
            $ocsservers_id = -1;
        }
        $_SESSION["plugin_ocsinventoryng_ocsservers_id"] = $ocsservers_id;

        return $ocsservers_id;
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
        // An unreachable server is tested only once by request (each attempt waits for the connect timeout)
        static $failed = [];

        if (isset($failed[$serverId])) {
            return false;
        }
        try {
            return self::getDBocs($serverId)->checkConnection();
        } catch (\RuntimeException $e) {
            $failed[$serverId] = true;
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * Get a connection to the OCS server
     *
     * @param $serverId
     *
     * @return OcsClient the ocs client (database or soap)
     */
    public static function getDBocs($serverId)
    {
        if ($serverId) {
            $config = self::getConfig($serverId);

            $key = (new GLPIKey())->decrypt($config['ocs_db_passwd']);
            if ($key != null) {
                $key = rawurldecode(stripslashes($key));
            }
            if (isset($config['conn_type'])) {
                if ($config['conn_type'] == self::CONN_TYPE_DB) {
                    return new OcsDbClient(
                        $serverId,
                        $config['ocs_db_host'],
                        $config['ocs_db_user'],
                        $key,
                        $config['ocs_db_name'],
                    );
                } else {
                    return new OcsDbClient(
                        $serverId,
                        $config['ocs_db_host'],
                        $config['ocs_db_user'],
                        $key,
                        $config['ocs_db_name'],
                    );
                }
            }
        }
        return false;
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
                $WHERE = " `accountinfo`.`TAG` = " . DBmysql::quoteValue((string) $splitter[0]) . " ";
                for ($i = 1; $i < count($splitter); $i++) {
                    $WHERE .= " OR `accountinfo`.`TAG` = " . DBmysql::quoteValue((string) $splitter[$i]) . " ";
                }
            }
        }

        if (!empty($cfg_ocs["tag_exclude"])) {
            $splitter = explode("$", $cfg_ocs["tag_exclude"]);
            if (count($splitter)) {
                if (!empty($WHERE)) {
                    $WHERE .= " AND ";
                }
                $WHERE .= " `accountinfo`.`TAG` <> " . DBmysql::quoteValue((string) $splitter[0]) . " ";
                for ($i = 1; $i < count($splitter); $i++) {
                    $WHERE .= " AND `accountinfo`.`TAG` <> " . DBmysql::quoteValue((string) $splitter[$i]) . " ";
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
            global $DB;

            // The version of the OCS server (its GUI_VERSION, a number such as 7077): stored without
            // update(), which refuses ocs_version from a form
            $DB->update(self::getTable(), ['ocs_version' => (string) preg_replace('/[^0-9.]/', '', (string) $version)], ['id' => (int) $ID]);
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
     * Display a message page of the plugin
     *
     * @param array $params 'title', 'icon' (ti-*), optional 'color' (bg-*-lt), 'lines', 'progress' and
     *                      'links' (each with 'title', 'url', optional 'icon' and 'class')
     *
     * @return void (display)
     */
    public static function showMessage(array $params)
    {
        TemplateRenderer::getInstance()->display('@ocsinventoryng/message.html.twig', $params);
    }

    /**
     * Display the "no OCS server" message, with a link to add one
     *
     * @return void (display)
     */
    public static function showNoServer()
    {
        $links = [];
        if (self::canCreate()) {
            $links[] = [
                'title' => __('Add a OCSNG server', 'ocsinventoryng'),
                'url'   => self::getFormURL(),
                'icon'  => 'ti-plus',
                'class' => 'btn-primary',
            ];
        }
        self::showMessage([
            'title' => __('No OCSNG server defined', 'ocsinventoryng'),
            'lines' => [__('You must to configure a OCSNG server', 'ocsinventoryng')],
            'icon'  => 'ti-alert-triangle',
            'color' => 'bg-orange-lt',
            'links' => $links,
        ]);
    }

    /**
     * The link back to the plugin menu
     *
     * @return array
     */
    public static function getBackLink()
    {
        return [
            'title' => __('Back'),
            'url'   => PLUGIN_OCS_WEBDIR . "/front/ocsng.php",
            'icon'  => 'ti-arrow-left',
        ];
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

        if (!Session::haveRight(Profile::RIGHT_CLEAN, READ)) {
            return false;
        }
        $canedit = Session::haveRight(Profile::RIGHT_CLEAN, UPDATE);

        // ocslinks ocsid
        $ocsids = [];
        foreach ($DB->request([
            'SELECT' => 'ocsid',
            'FROM'   => 'glpi_plugin_ocsinventoryng_ocslinks',
            'WHERE'  => ['plugin_ocsinventoryng_ocsservers_id' => (int) $plugin_ocsinventoryng_ocsservers_id]
                + getEntitiesRestrictCriteria('glpi_plugin_ocsinventoryng_ocslinks'),
        ]) as $data) {
            $ocsids[] = $data['ocsid'];
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


        $ocs_missing = [];

        foreach ($ocsids as $id) {
            if (!isset($hardware[$id])) {
                $ocs_missing[$id] = $id;
            }
        }


        //Select unexisting computers
        // Links whose computer is gone, or whose OCS machine is missing
        $missing = [
            'glpi_computers.id'                                         => null,
            'glpi_plugin_ocsinventoryng_ocslinks.plugin_ocsinventoryng_ocsservers_id' => (int) $plugin_ocsinventoryng_ocsservers_id,
        ];
        if (count($ocs_missing)) {
            $missing = ['OR' => [
                $missing,
                ['glpi_plugin_ocsinventoryng_ocslinks.ocsid' => array_map('intval', $ocs_missing)],
            ]];
        }
        $result_glpi = $DB->request([
            'SELECT'    => [
                'glpi_plugin_ocsinventoryng_ocslinks.entities_id',
                'glpi_plugin_ocsinventoryng_ocslinks.ocs_deviceid',
                'glpi_plugin_ocsinventoryng_ocslinks.last_update',
                'glpi_plugin_ocsinventoryng_ocslinks.ocsid',
                'glpi_plugin_ocsinventoryng_ocslinks.id',
                'glpi_computers.name AS name',
            ],
            'FROM'      => 'glpi_plugin_ocsinventoryng_ocslinks',
            'LEFT JOIN' => [
                'glpi_computers' => [
                    'ON' => [
                        'glpi_computers'                      => 'id',
                        'glpi_plugin_ocsinventoryng_ocslinks' => 'computers_id',
                    ],
                ],
            ],
            'WHERE'     => [$missing] + getEntitiesRestrictCriteria('glpi_plugin_ocsinventoryng_ocslinks'),
        ]);

        // fetch all links missing between glpi and OCS
        $already_linked = [];
        if (count($result_glpi) > 0) {
            foreach ($result_glpi as $data) {

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

        $target  = PLUGIN_OCS_WEBDIR . '/front/ocsng.clean.php';
        $numrows = count($already_linked);
        $start   = max(0, (int) $start);
        $limit   = (int) $_SESSION['glpilist_limit'];

        $rows = [];
        foreach (array_slice($already_linked, $start, $limit, true) as $tab) {
            $row = [
                '_checkbox' => (int) $tab["id"],
                'item'      => (string) $tab["ocs_deviceid"],
                'date'      => Html::convDateTime($tab["date"]),
                'in_glpi'   => Dropdown::getYesNo($tab["in_glpi"]),
                'in_ocs'    => Dropdown::getYesNo($tab["in_ocs"]),
            ];
            if (Session::isMultiEntitiesMode()) {
                $row['entity'] = (string) Dropdown::getDropdownName('glpi_entities', $tab['entities_id']);
            }
            $rows[] = $row;
        }

        $columns = [
            'item'    => __('Item'),
            'date'    => __('Import date in GLPI', 'ocsinventoryng'),
            'in_glpi' => __('Existing in GLPI', 'ocsinventoryng'),
            'in_ocs'  => __('Existing in OCSNG', 'ocsinventoryng'),
        ];
        if (Session::isMultiEntitiesMode()) {
            $columns['entity'] = Entity::getTypeName(1);
        }

        TemplateRenderer::getInstance()->display('@ocsinventoryng/table.html.twig', [
            'title'         => __('Clean links between GLPI and OCSNG', 'ocsinventoryng'),
            'icon'          => 'ti ti-recycle',
            'columns'       => $columns,
            'rows'          => $rows,
            'empty_message' => __('No item to clean', 'ocsinventoryng'),
            'form_action'   => $canedit ? $target : null,
            'checkbox_name' => $canedit ? 'toclean' : null,
            'checked_all'   => $check == "all",
            'buttons'       => [
                [
                    'name'  => 'clean_ok',
                    'title' => _sx('button', 'Clean'),
                    'icon'  => 'ti-recycle',
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
     * @param $plugin_ocsinventoryng_ocsservers_id
     * @param $check
     * @param $start
     *
     * @return bool|void
     */
    public static function showComputersToSynchronize($show_params)
    {
        $plugin_ocsinventoryng_ocsservers_id = $show_params["plugin_ocsinventoryng_ocsservers_id"];

        if (!self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)) {
            return false;
        }
        if (!Session::haveRight(OcsServer::$rightname, UPDATE)
            && !Session::haveRight(Profile::RIGHT_SYNC, UPDATE)) {
            return false;
        }

        self::showOcsDataTable([
            'title'        => __('Computers updated in OCSNG', 'ocsinventoryng'),
            'icon'         => 'ti-refresh',
            'target'       => PLUGIN_OCS_WEBDIR . '/front/ocsng.sync.php',
            'load_url'     => PLUGIN_OCS_WEBDIR . '/ajax/loadcomputerstosynchronize.php',
            'load_data'    => ['plugin_ocsinventoryng_ocsservers_id' => (int) $plugin_ocsinventoryng_ocsservers_id],
            'field_name'   => 'toupdate',
            'columns'      => [
                'checked'        => __('All', 'ocsinventoryng'),
                'ocsid'          => __('ID'),
                'name'           => __('Name'),
                'serial'         => __('Serial number'),
                'last_update'    => __('Import date in GLPI', 'ocsinventoryng'),
                'date'           => __('Last OCSNG inventory date', 'ocsinventoryng'),
                'TAG'            => __('OCSNG TAG', 'ocsinventoryng'),
                'checksum_debug' => __('Checksum', 'ocsinventoryng'),
            ],
            'hidden'       => [1],
            'submit_name'  => 'update_ok',
            'submit_icon'  => 'ti-refresh',
            'submit_label' => _sx('button', 'Synchronize'),
        ]);

        if (isset($_SESSION["ocs_update"]['statistics'])) {
            echo "<br>";
            OcsProcess::showStatistics($_SESSION["ocs_update"]['statistics'], true);
        }
    }

    /**
     * Display a list of computers to add or to link
     *
     * @param $show_params
     *
     * @return bool
     * @internal param the $plugin_ocsinventoryng_ocsservers_id ID of the ocs server
     * @internal param display $advanced detail about the computer import or not (target entity, matched rules, etc.)
     * @internal param indicates $check if checkboxes are checked or not
     * @internal param display $start a list of computers starting at rowX
     * @internal param a $entities_id list of entities in which computers can be added or linked
     *
     */
    public static function showComputersToAdd($show_params)
    {

        $plugin_ocsinventoryng_ocsservers_id = $show_params["plugin_ocsinventoryng_ocsservers_id"];
        $advancedimport                      = $show_params["import_mode"] ?? 0;
        $advancedlink                        = $show_params["link_mode"] ?? 0;
        $entities_id                         = $show_params["entities_id"];


        if (!self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)) {
            return false;
        }

        if (!Session::haveRight(OcsServer::$rightname, READ)
            && !Session::haveRight(Profile::RIGHT_IMPORT, READ)
            && !Session::haveRight(Profile::RIGHT_LINK, READ)) {
            return false;
        }

        $title  = __('Import or link new computers', 'ocsinventoryng');
        $target = PLUGIN_OCS_WEBDIR . '/front/ocsng.import.php';

        //Show preview form only in import even in multi-entity mode because computer import
        //can be refused by a rule
        echo "<div class='card my-3'>";
        echo "<div class='card-header'><h3 class='card-title mb-0'><i class='ti ti-eye me-2'></i>"
            . htmlescape(__('Manual import mode', 'ocsinventoryng')) . "</h3></div>";
        echo "<div class='card-body d-flex flex-wrap align-items-center gap-2'>";
        if ($advancedimport || $advancedlink) {
            Html::showSimpleForm($target, 'simple_mode', __('Disable preview', 'ocsinventoryng'));
        } else {
            Html::showSimpleForm($target, 'change_import_mode', __('Enable preview for import', 'ocsinventoryng'));
            Html::showSimpleForm($target, 'change_link_mode', __('Enable preview for linking', 'ocsinventoryng'));
        }
        echo "<span class='ms-auto alert alert-warning mb-0 py-1'><i class='ti ti-alert-triangle me-1'></i>"
            . htmlescape(__('Check first that duplicates have been correctly managed in OCSNG', 'ocsinventoryng'))
            . "</span>";
        echo "</div>";
        echo "</div>";

        // The preview columns (unicity, rule, entity, recursivity, item to link) are only shown in
        // the preview modes
        $advanced = $advancedimport || $advancedlink;
        self::showOcsDataTable([
            'title'        => $title,
            'icon'         => 'ti-download',
            'target'       => $target,
            'load_url'     => PLUGIN_OCS_WEBDIR . '/ajax/loadcomputerstoimport.php',
            'load_data'    => [
                'plugin_ocsinventoryng_ocsservers_id' => (int) $plugin_ocsinventoryng_ocsservers_id,
                'entities_id'                         => $entities_id,
                'importmode'                          => (int) $advancedimport,
                'linkmode'                            => (int) $advancedlink,
            ],
            'field_name'   => 'toadd',
            'columns'      => [
                'checked'                        => __('All', 'ocsinventoryng'),
                'id'                             => __('ID'),
                'name'                           => __('Name'),
                'serial'                         => __('Serial number'),
                'manufacturer'                   => __('Manufacturer'),
                'model'                          => __('Model'),
                'infos'                          => _n('Information', 'Informations', 2),
                'date'                           => __('Last OCSNG inventory date', 'ocsinventoryng'),
                'TAG'                            => __('OCSNG TAG', 'ocsinventoryng'),
                'toimport_disable_unicity_check' => __('Override unicity check ?', 'ocsinventoryng'),
                'rule_matched'                   => __('Match the rule ?', 'ocsinventoryng'),
                'toimport_entities'              => __('Destination entity', 'ocsinventoryng'),
                'toimport_recursive'             => __('Child entities'),
                'computers_id_founded'           => __('Item to link', 'ocsinventoryng'),
            ],
            'hidden'       => $advanced ? [1] : [1, 9, 10, 11, 12, 13],
            'submit_name'  => 'import_ok',
            'submit_icon'  => 'ti-download',
            'submit_label' => $title,
        ]);

        if (isset($_SESSION["ocs_import"]['statistics'])) {
            echo "<br>";
            OcsProcess::showStatistics($_SESSION["ocs_import"]['statistics'], true);
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

        // A http(s) URL only (checkOcsUrl()): never a javascript: or broken attribute
        if ($ocs_config["ocs_url"] != '' && self::isValidOcsUrl((string) $ocs_config["ocs_url"])) {
            //Display direct link to the computer in ocsreports
            $url = rtrim((string) $ocs_config["ocs_url"], '/') . '/';
            if ($ocs_config['ocs_version'] > self::OCS2_VERSION_LIMIT) {
                $url = $url . "index.php?" . http_build_query(['function' => 'computer', 'head' => 1, 'systemid' => (int) $ocsid]);
            } else {
                $url = $url . "machine.php?" . http_build_query(['systemid' => (int) $ocsid]);
            }

            if ($only_url) {
                return $url;
            }
            return "<a class='submit btn btn-primary' target='_blank' rel='noopener noreferrer' href='" . htmlescape($url) . "'>"
                . htmlescape((string) $todisplay) . "</a>";
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
        if ($CronTask->getFromDBbyName(OcsServer::class, "CleanOldAgents")) {
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
                    $can_update = Config::canUpdateOCS();
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
                        $nb = Ocslink::deleteComputers($plugin_ocsinventoryng_ocsservers_id, $agents);
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
        if ($CronTask->getFromDBbyName(OcsServer::class, "RestoreOldAgents")) {
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

                $result = $DB->request([
                    'SELECT'     => 'glpi_computers.id',
                    'FROM'       => 'glpi_computers',
                    'INNER JOIN' => [
                        'glpi_plugin_ocsinventoryng_ocslinks AS ocslink' => [
                            'ON' => ['ocslink' => 'computers_id', 'glpi_computers' => 'id'],
                        ],
                    ],
                    'WHERE'      => [
                        'glpi_computers.is_deleted' => 1,
                        'ocslink.last_ocs_update'   => ['>=', new QueryExpression(
                            'NOW() - INTERVAL ' . (int) $delay . ' DAY',
                        )],
                    ],
                ]);
                if (count($result) > 0) {
                    $computer = new Computer();
                    foreach ($result as $data) {
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
                            Log::HISTORY_RESTORE_ITEM,
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
            //Get a random server id
            $plugin_ocsinventoryng_ocsservers_id = self::getRandomServerID();

            if ($plugin_ocsinventoryng_ocsservers_id > 0) {
                //Initialize the server connection
                $DBocs = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);

                $cfg_ocs = self::getConfig($plugin_ocsinventoryng_ocsservers_id);
                $task->log(__('Launch OCSNG synchronization script from server', 'ocsinventoryng') . " " . $cfg_ocs['name'] . "\n");

                if (!$cfg_ocs["cron_sync_number"]) {
                    return 0;
                }
                OcsProcess::manageDeleted($plugin_ocsinventoryng_ocsservers_id, false);


                // Get linked computer ids in GLPI

                $already_linked_query = $DB->request([
                    'SELECT'    => [
                        'glpi_plugin_ocsinventoryng_ocslinks.ocsid AS ocsid',
                    ],
                    'FROM'      => 'glpi_plugin_ocsinventoryng_ocslinks',
                    'INNER JOIN'       => [
                        'glpi_computers' => [
                            'ON' => [
                                'glpi_computers' => 'id',
                                'glpi_plugin_ocsinventoryng_ocslinks'          => 'computers_id',
                            ],
                        ],
                    ],
                    'WHERE'     => [
                        'glpi_plugin_ocsinventoryng_ocslinks.plugin_ocsinventoryng_ocsservers_id'  => $plugin_ocsinventoryng_ocsservers_id,
                    ],
                ]);

                $already_linked_ids = [];
                if (count($already_linked_query) > 0) {
                    foreach ($already_linked_query as $data) {
                        $already_linked_ids [] = $data['ocsid'];
                    }
                }

                // Fetch linked computers from ocs
                $res = [];
                if (count($already_linked_ids) > 0) {
                    $ocsResult = $DBocs->getComputers([
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

                            $criteria = [
                                'SELECT'    => [
                                    'id', 'ocs_deviceid',
                                ],
                                'FROM'      => 'glpi_plugin_ocsinventoryng_ocslinks',
                                'WHERE'     => [
                                    'plugin_ocsinventoryng_ocsservers_id'  => $plugin_ocsinventoryng_ocsservers_id,
                                    'ocsid'  => $data["ID"],
                                ],
                            ];

                            $iterator = $DB->request($criteria);

                            if (count($iterator) > 0) {
                                foreach ($iterator as $values) {

                                    $task->addVolume(1);
                                    $task->log(sprintf(
                                        __('%1$s: %2$s'),
                                        _n('Computer', 'Computer', 1),
                                        sprintf(__('%1$s (%2$s)'), $values["ocs_deviceid"], $values["id"]),
                                    ));

                                    $sync_params = ['ID'                                  => $values["id"],
                                        'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                                        'cfg_ocs'                             => $cfg_ocs,
                                        'force'                               => 0];

                                    OcsProcess::synchronizeComputer($sync_params);

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

        // A http(s) URL only (checkOcsUrl()), escaped by the template
        $url = (string) $ocsconfig["ocs_url"];
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% if url is not empty %}
                <div class="card mb-3">
                    <iframe src="{{ url }}" class="w-100 border-0" style="height: 650px"></iframe>
                </div>
            {% endif %}
TWIG, ['url' => $url != '' && self::isValidOcsUrl($url) ? rtrim($url, '/') . "/index.php?multi=4" : '']);
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
    public function getSystemInformation()
    {
        $dbu        = new DbUtils();
        $ocsServers = $dbu->getAllDataFromTable('glpi_plugin_ocsinventoryng_ocsservers', ["is_active" => 1]);
        if (!empty($ocsServers)) {

            $content = '';
            foreach ($ocsServers as $ocsServer) {
                echo "<tr class='tab_bg_1'><td>";
                $content = __('Host', 'ocsinventoryng') . ": " . $ocsServer['ocs_db_host'] . "";
                $content .= "<br>" . __('Connection') . ": " . (self::checkOCSconnection($ocsServer['id']) ? "Ok" : "KO");
                $content .= "<br>" . __('Use the OCSNG software dictionary', 'ocsinventoryng') . ": " .
                        ($ocsServer['use_soft_dict'] ? 'Yes' : 'No');
            }
        }
        return [
            'label' => __('OCS Inventory NG', 'ocsinventoryng'),
            'content' => $content,
        ];
    }

    /**
     * List of OCS computers loaded by AJAX (templates/computers_datatable.html.twig and
     * public/js/ocs-datatable.js): a card with the DataTables table, the ids of the selected rows
     * posted with the form as <field_name>[]
     *
     * @param array{title: string, icon: string, target: string, load_url: string, load_data: array,
     *              field_name: string, columns: array<string, string>, hidden: int[],
     *              submit_name: string, submit_icon: string, submit_label: string} $params
     *
     * @return void
     */
    private static function showOcsDataTable(array $params): void
    {
        $lib    = PLUGIN_OCS_WEBDIR . '/lib/datatables/';
        $assets = '';
        foreach (['jquery.dataTables', 'select.dataTables', 'responsive.dataTables', 'colReorder.dataTables'] as $css) {
            $assets .= Html::css($lib . 'css/' . $css . '.min.css');
        }
        foreach (['jquery.dataTables', 'dataTables.select', 'dataTables.responsive', 'dataTables.colReorder'] as $js) {
            $assets .= Html::script($lib . 'js/' . $js . '.min.js');
        }
        $assets .= Html::script(PLUGIN_OCS_WEBDIR . '/js/ocs-datatable.js');

        TemplateRenderer::getInstance()->display('@ocsinventoryng/computers_datatable.html.twig', [
            'assets'       => $assets,
            'title'        => $params['title'],
            'icon'         => $params['icon'],
            'target'       => $params['target'],
            'headers'      => array_values($params['columns']),
            'submit_name'  => $params['submit_name'],
            'submit_icon'  => $params['submit_icon'],
            'submit_label' => $params['submit_label'],
            'table_config' => [
                'load_url'       => $params['load_url'],
                'load_data'      => $params['load_data'],
                'field_name'     => $params['field_name'],
                'columns'        => array_keys($params['columns']),
                'hidden_columns' => $params['hidden'],
                'language'       => self::getJsLanguages(),
            ],
        ]);
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
        $languages['sInfoPostFix']    = '';
        $languages['sInfoThousands']  = ',';
        $languages['sLengthMenu']     = __('Show _MENU_ entries', 'ocsinventoryng');
        $languages['sLoadingRecords'] = __('Loading...');
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
