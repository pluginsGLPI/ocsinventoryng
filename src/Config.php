<?php

/*
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

use CommonGLPI;
use CronTask;
use Glpi\Application\View\TemplateRenderer;
use Session;
use Toolbox;

/**
 * Class Config
 */
class Config extends \CommonDBTM
{
    /**
     * @var string
     */
    public static $rightname = "plugin_ocsinventoryng";

    /**
     * @param int $nb
     *
     * @return string|translated
     */
    public static function getTypeName($nb = 0)
    {
        return __("Plugin setup", 'ocsinventoryng');
    }


    /**
     * @param CommonGLPI $item
     * @param int        $withtemplate
     *
     * @return array|string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            switch ($item->getType()) {
                case __CLASS__:
                    $tab['1'] = self::createTabEntry(__('Alerts', 'ocsinventoryng'));
                    if (OcsServer::useMassImport()) {
                        //If connection to the OCS DB  is ok, and all rights are ok too
                        $tab['2'] = self::createTabEntry(__("Automatic synchronization's configuration", 'ocsinventoryng'));
                        $tab['3'] = self::createTabEntry(__('Check OCSNG import script', 'ocsinventoryng'));
                    }
                    return $tab;
            }
        }
        return '';
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

        switch ($item->getType()) {
            case __CLASS__:
                switch ($tabnum) {
                    case 1:
                        $item->displayAlerts();
                        break;
                    case 2:
                        $item->showFormAutomaticSynchronization();
                        break;
                    case 3:
                        $item->showScriptLock();
                        break;
                }

                break;
        }
        return true;
    }

    public static function getIcon()
    {
        return "ti ti-settings";
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
        return $ong;
    }

    public static function getConfig()
    {
        static $config = null;

        if (is_null($config)) {
            $config = new self();
        }
        $config->getFromDB(1);

        return $config;
    }

    /**
     *
     */
    public static function showMenu()
    {
        Menu::displayMenuSections([
            [
                'title' => __('Configuration'),
                'icon'  => 'ti ti-tool',
                'items' => [
                    [
                        'title' => _n('OCSNG server', 'OCSNG servers', 2, 'ocsinventoryng'),
                        'url'   => PLUGIN_OCS_WEBDIR . "/front/ocsserver.php",
                        'icon'  => 'ti-server',
                    ],
                    [
                        'title' => __('Add a OCSNG server', 'ocsinventoryng'),
                        'url'   => OcsServer::getFormURL(),
                        'icon'  => 'ti-plus',
                    ],
                    [
                        'title' => self::getTypeName(),
                        'url'   => PLUGIN_OCS_WEBDIR . "/front/config.form.php",
                        'icon'  => 'ti-settings',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    /**
     * Values of the alert delays: never, then 1 to 99 days (as Alert::dropdownIntegerNever())
     *
     * @return array
     */
    public static function getAlertDelays()
    {
        $days = [0 => __('Never')];
        for ($i = 1; $i <= 99; $i++) {
            $days[$i] = sprintf(_n('%d day', '%d days', $i), $i);
        }

        return $days;
    }

    public function showForm($ID, $options = [])
    {

        $this->getFromDB(1);
        $days = self::getAlertDelays();

        TemplateRenderer::getInstance()->display('@ocsinventoryng/config_form.html.twig', [
            'title'       => __('OCS-NG Synchronization alerts', 'ocsinventoryng'),
            'icon'        => 'ti ti-bell',
            'form_url'    => $this->getFormURL(),
            'canedit'     => $this->canUpdateItem(),
            'form_fields' => [
                [
                    'type'   => 'alert',
                    'name'   => 'use_newocs_alert',
                    'value'  => (int) $this->fields['use_newocs_alert'],
                    'values' => $days,
                    'label'  => __('New imported computers from OCS-NG', 'ocsinventoryng'),
                ],
                [
                    'type'   => 'alert',
                    'name'   => 'delay_ocs',
                    'value'  => (int) $this->fields['delay_ocs'],
                    'values' => $days,
                    'label'  => __('Computers not synchronized with OCS-NG since more', 'ocsinventoryng'),
                ],
            ],
        ]);

        return true;
    }

    public static function displayAlerts()
    {
        global $DB;

        $CronTask = new CronTask();

        $config = self::getConfig();

        $ocsalert = new OcsAlert();
        $ocsalert->getFromDBByCrit(['entities_id' => $_SESSION["glpiactive_entity"]]);
        if (isset($ocsalert->fields["use_newocs_alert"])
          && $ocsalert->fields["use_newocs_alert"] > 0) {
            $use_newocs_alert = $ocsalert->fields["use_newocs_alert"];
        } else {
            $use_newocs_alert = $config->useNewocsAlert();
        }

        if (isset($ocsalert->fields["delay_ocs"])
          && $ocsalert->fields["delay_ocs"] > 0) {
            $delay_ocs = $ocsalert->fields["delay_ocs"];
        } else {
            $delay_ocs = $config->getDelayOcs();
        }
        $synchro_ocs = 0;
        if ($CronTask->getFromDBbyName(OcsAlert::class, "SynchroAlert")) {
            if ($CronTask->fields["state"] != CronTask::STATE_DISABLE && $delay_ocs > 0) {
                $synchro_ocs = 1;
            }
        }
        $new_ocs = 0;
        if ($CronTask->getFromDBbyName(OcsAlert::class, "AlertNewComputers")) {
            if ($CronTask->fields["state"] != CronTask::STATE_DISABLE && $use_newocs_alert > 0) {
                $new_ocs = 1;
            }
        }

        if ($synchro_ocs == 0
          && $new_ocs == 0) {
            OcsServer::showMessage([
                'title' => __('No used alerts, please activate the automatic actions', 'ocsinventoryng'),
                'icon'  => 'ti-bell-off',
            ]);
            return;
        }

        $lists = [];
        foreach ($DB->request([
            'FROM'  => 'glpi_plugin_ocsinventoryng_ocsservers',
            'WHERE' => ['is_active' => 1],
        ]) as $ocsserver) {
            if ($new_ocs != 0) {
                $lists[] = [
                    'title'    => __('New imported computers from OCS-NG', 'ocsinventoryng') . " - " . $delay_ocs . " " . _n('Day', 'Days', 2),
                    'icon'     => 'ti ti-plus',
                    'criteria' => OcsAlert::queryNew($delay_ocs, $ocsserver, $_SESSION["glpiactive_entity"]),
                    'empty'    => __('No new imported computer from OCS-NG', 'ocsinventoryng'),
                ];
            }
            if ($synchro_ocs != 0) {
                $lists[] = [
                    'title'    => __('Computers not synchronized with OCS-NG since more', 'ocsinventoryng') . " " . $delay_ocs . " " . _n('Day', 'Days', 2),
                    'icon'     => 'ti ti-clock-exclamation',
                    'criteria' => OcsAlert::query($delay_ocs, $ocsserver, $_SESSION["glpiactive_entity"]),
                    'empty'    => __('No computer not synchronized since more', 'ocsinventoryng') . " " . $delay_ocs . " " . _n('Day', 'Days', 2),
                ];
            }
        }

        foreach ($lists as $list) {
            $rows = [];
            // OcsAlert::queryNew() and OcsAlert::query() return criteria
            foreach ($DB->request($list['criteria']) as $data) {
                $rows[] = OcsAlert::getAlertRow($data);
            }
            $columns = OcsAlert::getAlertColumns();
            TemplateRenderer::getInstance()->display('@ocsinventoryng/table.html.twig', [
                'title'         => $list['title'],
                'icon'          => $list['icon'],
                'columns'       => $columns,
                // Built and escaped by OcsAlert::getAlertRow()
                'raw_columns'   => array_keys($columns),
                'rows'          => $rows,
                'empty_message' => $list['empty'],
            ]);
        }
    }


    /**
     * @return bool
     * @internal param $target
     */
    public function showFormAutomaticSynchronization()
    {

        if (!Session::haveRight("plugin_ocsinventoryng_sync", READ)) {
            return false;
        }
        $this->getFromDB(1);

        TemplateRenderer::getInstance()->display('@ocsinventoryng/config_form.html.twig', [
            'title'       => __("Automatic synchronization's configuration", 'ocsinventoryng'),
            'icon'        => 'ti ti-refresh',
            'form_url'    => $this->getFormURL(),
            'canedit'     => Session::haveRight("plugin_ocsinventoryng_sync", UPDATE),
            'form_fields' => [
                [
                    'type'  => 'yesno',
                    'name'  => 'is_displayempty',
                    'value' => $this->fields['is_displayempty'],
                    'label' => __('Show processes where nothing was changed', 'ocsinventoryng'),
                ],
                [
                    'type'  => 'yesno',
                    'name'  => 'allow_ocs_update',
                    'value' => $this->fields['allow_ocs_update'],
                    'label' => __('Authorize the OCSNG update (purge agents when purge GLPI computers or from Automatic actions)', 'ocsinventoryng'),
                ],
                [
                    'type'  => 'yesno',
                    'name'  => 'log_imported_computers',
                    'value' => $this->fields['log_imported_computers'],
                    'label' => __('Log imported computers', 'ocsinventoryng'),
                ],
                [
                    'type'    => 'number',
                    'name'    => 'delay_refresh',
                    'value'   => (int) $this->fields['delay_refresh'],
                    'label'   => __('Refresh information of a process every', 'ocsinventoryng')
                        . ' (' . _n('second', 'seconds', 2, 'ocsinventoryng') . ')',
                    'options' => ['min' => 0],
                ],
                [
                    'type'    => 'number',
                    'name'    => 'import_limit',
                    'value'   => (int) $this->fields['import_limit'],
                    'label'   => __('Limit each massive import to this number of objects', 'ocsinventoryng'),
                    'options' => ['min' => 0],
                ],
                [
                    'type'  => 'textarea',
                    'name'  => 'comment',
                    'value' => $this->fields['comment'],
                    'label' => __('Comments'),
                ],
            ],
        ]);

        return true;
    }


    /**
     *
     */
    public function showScriptLock()
    {
        // A fixed target: the Referer is chosen by the previous page
        TemplateRenderer::getInstance()->display('@ocsinventoryng/script_lock.html.twig', [
            'form_url' => Toolbox::getItemTypeFormURL(self::class),
            'locked'   => self::isScriptLocked(),
            'canedit'  => Session::haveRight(static::$rightname, UPDATE),
        ]);
    }


    /**
     * @return bool
     */
    public static function isScriptLocked()
    {
        return file_exists(PLUGIN_OCSINVENTORYNG_LOCKFILE);
    }


    /**
     *
     */
    public function setScriptLock()
    {

        $fp = fopen(PLUGIN_OCSINVENTORYNG_LOCKFILE, "w+");
        fclose($fp);
    }


    /**
     *
     */
    public function removeScriptLock()
    {

        if (file_exists(PLUGIN_OCSINVENTORYNG_LOCKFILE)) {
            unlink(PLUGIN_OCSINVENTORYNG_LOCKFILE);
        }
    }


    /**
     * @return mixed
     */
    public function getAllOcsServers()
    {
        global $DB;

        $servers[-1] = __('All servers', 'ocsinventoryng');

        foreach ($DB->request([
            'SELECT' => ['id', 'name'],
            'FROM'   => 'glpi_plugin_ocsinventoryng_ocsservers',
        ]) as $conf) {
            $servers[$conf["id"]] = $conf["name"];
        }

        return $servers;
    }


    /**
     * @return mixed
     */
    public static function canUpdateOCS()
    {

        $config = new Config();
        $config->getFromDB(1);
        return $config->fields['allow_ocs_update'];
    }

    /**
     * @return mixed
     */
    public static function logProcessedComputers()
    {

        $config = new Config();
        $config->getFromDB(1);
        return $config->fields['log_imported_computers'];
    }


    //----------------- Getters and setters -------------------//

    public function useNewocsAlert()
    {
        return $this->fields['use_newocs_alert'];
    }

    public function getDelayOcs()
    {
        return $this->fields['delay_ocs'];
    }
}
