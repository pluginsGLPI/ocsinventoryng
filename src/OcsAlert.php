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

use Alert;
use CommonDBTM;
use CommonGLPI;
use Computer;
use CronTask;
use DbUtils;
use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Group;
use Group_Item;
use Html;
use Item_OperatingSystem;
use Location;
use NotificationEvent;
use OperatingSystem;
use Plugin;
use Session;
use Toolbox;
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class OcsAlert
 */
class OcsAlert extends CommonDBTM
{
    public static $rightname = "plugin_ocsinventoryng";

    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {

        return __('OCS-NG Synchronization alerts', 'ocsinventoryng');
    }

    public static function getIcon()
    {
        return "ti ti-devices";
    }

    /**
     * @param CommonGLPI $item
     * @param int        $withtemplate
     *
     * @return string|translated
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if ($item->getType() == 'CronTask' && $item->getField('name') == "SynchroAlert") {
            return self::createTabEntry(__('Plugin setup', 'ocsinventoryng'));
        } elseif ($item->getType() == 'Entity') {
            return self::createTabEntry(self::getTypeName(2));
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

        if ($item->getType() == 'CronTask') {
            self::configCron();
        } elseif ($item->getType() == 'Entity') {
            self::showNotificationOptions($item);
        }
        return true;
    }

    // Cron action

    /**
     * @param $name
     *
     * @return array
     */
    public static function cronInfo($name)
    {

        switch ($name) {
            case 'SynchroAlert':
                return [
                    'description' => __('Computers not synchronized with OCS-NG since X days', 'ocsinventoryng')];   // Optional
                break;
            case 'AlertNewComputers':
                return [
                    'description' => __('Alert for the new imported computers', 'ocsinventoryng')];
                break;
        }
        return [];
    }

    /**
     * @param $config
     * @param $entity
     *
     * @return string
     */
    public static function queryNew($delay_ocs, $config, $entity)
    {

        $delay_stamp_ocs = mktime(0, 0, 0, date("m"), date("d") - $delay_ocs, date("y"));
        $date_ocs        = date("Y-m-d", $delay_stamp_ocs);
        $date_ocs        = $date_ocs . " 00:00:00";



        $criteria = [
            'SELECT' => ['glpi_plugin_ocsinventoryng_ocslinks.last_ocs_update',
                'glpi_plugin_ocsinventoryng_ocslinks.last_update',
                'glpi_plugin_ocsinventoryng_ocslinks.plugin_ocsinventoryng_ocsservers_id',
                'glpi_computers.*',
                'glpi_items_operatingsystems.operatingsystems_id'],
            'FROM' => 'glpi_plugin_ocsinventoryng_ocslinks',
            'LEFT JOIN'       => [
                'glpi_computers' => [
                    'ON' => [
                        'glpi_plugin_ocsinventoryng_ocslinks' => 'computers_id',
                        'glpi_computers'          => 'id',
                    ],
                ],
                'glpi_items_operatingsystems' => [
                    'ON' => [
                        'glpi_computers'   => 'id',
                        'glpi_items_operatingsystems'                  => 'items_id', [
                            'AND' => [
                                'glpi_items_operatingsystems.itemtype' => 'Computer',
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE' => [
                'glpi_computers.is_deleted' => 0,
                'glpi_computers.is_template' => 0,
                'glpi_plugin_ocsinventoryng_ocslinks.plugin_ocsinventoryng_ocsservers_id' => $config["id"],
                'last_ocs_update' => ['>=', $date_ocs],
            ],
            'ORDERBY' => 'glpi_plugin_ocsinventoryng_ocslinks.last_ocs_update ASC',
        ];


        return $criteria;
    }

    /**
     * @param $delay_ocs
     * @param $config
     * @param $entity
     *
     * @return array
     */
    public static function query($delay_ocs, $config, $entity)
    {
        global $DB;

        $delay_stamp_ocs = mktime(0, 0, 0, date("m"), date("d") - $delay_ocs, date("y"));
        $date_ocs        = date("Y-m-d", $delay_stamp_ocs);
        $date_ocs        = $date_ocs . " 00:00:00";


        $states = [];

        foreach ($DB->request([
            'SELECT' => 'states_id',
            'FROM' => 'glpi_plugin_ocsinventoryng_notificationstates']) as $data) {
            $states[] = $data["states_id"];
        }


        $criteria = [
            'SELECT' => ['glpi_plugin_ocsinventoryng_ocslinks.last_ocs_update',
                'glpi_plugin_ocsinventoryng_ocslinks.last_update',
                'glpi_plugin_ocsinventoryng_ocslinks.plugin_ocsinventoryng_ocsservers_id',
                'glpi_computers.*',
                'glpi_items_operatingsystems.operatingsystems_id'],
            'FROM' => 'glpi_plugin_ocsinventoryng_ocslinks',
            'LEFT JOIN'       => [
                'glpi_computers' => [
                    'ON' => [
                        'glpi_plugin_ocsinventoryng_ocslinks' => 'computers_id',
                        'glpi_computers'          => 'id',
                    ],
                ],
                'glpi_items_operatingsystems' => [
                    'ON' => [
                        'glpi_computers'   => 'id',
                        'glpi_items_operatingsystems'                  => 'items_id', [
                            'AND' => [
                                'glpi_items_operatingsystems.itemtype' => 'Computer',
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE' => [
                'glpi_computers.is_deleted' => 0,
                'glpi_computers.is_template' => 0,
                'glpi_computers.entities_id' => $entity,
                'glpi_plugin_ocsinventoryng_ocslinks.plugin_ocsinventoryng_ocsservers_id' => $config["id"],
                'last_ocs_update' => ['<=', $date_ocs],
            ],
            'ORDERBY' => 'glpi_plugin_ocsinventoryng_ocslinks.last_ocs_update DESC',
        ];
        $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_computers.states_id' => $states];

        return $criteria;
    }

    /**
     * A row of the alert lists (Config::displayAlerts()): HTML cells, every value escaped
     *
     * @param $data
     *
     * @return array
     */
    public static function getAlertRow($data)
    {
        // The name and the contact of a computer come from the OCS agent,
        // the names of the dropdowns from any user who may create them
        $computer = new Computer();
        $computer->getFromDB($data["id"]);
        $computers_id = (int) $computer->fields["id"];

        $name = (string) $computer->fields["name"];
        if ($_SESSION["glpiis_ids_visible"] == 1 || empty($name)) {
            $name .= " (" . $computers_id . ")";
        }
        $row = [
            'name' => "<a href=\"" . htmlescape(Computer::getFormURLWithID($computers_id)) . "\">" . htmlescape($name) . "</a>",
        ];
        if (Session::isMultiEntitiesMode()) {
            $row['entity'] = htmlescape((string) Dropdown::getDropdownName("glpi_entities", $data["entities_id"]));
        }
        // A computer may have several operating systems
        $operatingsystems      = [];
        $item_operatingsystems = new Item_OperatingSystem();
        foreach ($item_operatingsystems->find(['itemtype' => Computer::class, 'items_id' => $computers_id]) as $item_os) {
            $operatingsystems[] = (string) Dropdown::getDropdownName("glpi_operatingsystems", $item_os["operatingsystems_id"]);
        }
        $row['os']       = htmlescape(implode(", ", $operatingsystems));
        $row['state']    = htmlescape((string) Dropdown::getDropdownName("glpi_states", $computer->fields["states_id"]));
        $row['location'] = htmlescape((string) Dropdown::getDropdownName("glpi_locations", $computer->fields["locations_id"]));

        $users = [];
        if (!empty($computer->fields["users_id"])) {
            $dbu     = new DbUtils();
            $users[] = "<a href=\"" . htmlescape(User::getFormURLWithID((int) $computer->fields["users_id"])) . "\">"
                . htmlescape((string) $dbu->getUserName($computer->fields["users_id"])) . "</a>";
        }
        $groupsitem = new Group_Item();
        foreach ($groupsitem->find(['itemtype' => Computer::class, 'items_id' => $computers_id, 'type' => Group_Item::GROUP_TYPE_NORMAL]) as $group) {
            $users[] = htmlescape((string) Group::getFriendlyNameById($group['groups_id']));
        }
        if (!empty($computer->fields["contact"])) {
            $users[] = htmlescape((string) $computer->fields["contact"]);
        }
        $row['user']        = implode(" - ", $users);
        $row['last_ocs']    = htmlescape((string) Html::convDateTime($data["last_ocs_update"]));
        $row['last_update'] = htmlescape((string) Html::convDateTime($data["last_update"]));
        $row['server']      = htmlescape((string) Dropdown::getDropdownName(
            "glpi_plugin_ocsinventoryng_ocsservers",
            $data["plugin_ocsinventoryng_ocsservers_id"],
        ));

        return $row;
    }

    /**
     * Columns of the alert lists (Config::displayAlerts())
     *
     * @return array
     */
    public static function getAlertColumns()
    {
        $columns = ['name' => __('Name')];
        if (Session::isMultiEntitiesMode()) {
            $columns['entity'] = Entity::getTypeName(1);
        }

        return $columns + [
            'os'          => OperatingSystem::getTypeName(1),
            'state'       => __('Status'),
            'location'    => Location::getTypeName(1),
            'user'        => __('User') . " / " . __('Group') . " / " . __('Alternate username'),
            'last_ocs'    => __('Last OCSNG inventory date', 'ocsinventoryng'),
            'last_update' => __('Import date in GLPI', 'ocsinventoryng'),
            'server'      => __('OCSNG server', 'ocsinventoryng'),
        ];
    }

    /**
     * @param      $field
     * @param bool $with_value
     *
     * @return array
     */
    public static function getEntitiesToNotify($field, $with_value = false)
    {
        global $DB;

        // A column of the alerts: use_newocs_alert or delay_ocs only
        if (!in_array($field, ['use_newocs_alert', 'delay_ocs'], true)) {
            return [];
        }

        $entities = [];
        $result   = $DB->request([
            'SELECT' => ['entities_id AS entity', $field],
            'FROM'   => 'glpi_plugin_ocsinventoryng_ocsalerts',
            'ORDER'  => 'entities_id ASC',
        ]);

        if (count($result) > 0) {
            foreach ($result as $entitydatas) {
                self::getDefaultValueForNotification($field, $entities, $entitydatas);
            }
        } else {
            $config = new Config();
            $config->getFromDB(1);
            $dbu = new DbUtils();
            foreach ($dbu->getAllDataFromTable('glpi_entities') as $entity) {
                $entities[$entity['id']] = $config->fields[$field];
            }
        }

        return $entities;
    }

    /**
     * @param $field
     * @param $entities
     * @param $entitydatas
     */
    public static function getDefaultValueForNotification($field, &$entities, $entitydatas)
    {

        $config = new Config();
        $config->getFromDB(1);
        //If there's a configuration for this entity & the value is not the one of the global config
        if (isset($entitydatas[$field]) && $entitydatas[$field] > 0) {
            $entities[$entitydatas['entity']] = $entitydatas[$field];
        } //No configuration for this entity : if global config allows notification then add the entity
        //to the array of entities to be notified
        elseif ((!isset($entitydatas[$field])
                || (isset($entitydatas[$field]) && $entitydatas[$field] == -1))
               && $config->fields[$field]) {
            $dbu = new DbUtils();
            foreach ($dbu->getAllDataFromTable('glpi_entities') as $entity) {
                $entities[$entity['id']] = $config->fields[$field];
            }
        }
    }

    /**
     * @param null $task
     *
     * @return int
     */
    public static function cronSynchroAlert($task = null)
    {
        global $DB, $CFG_GLPI;

        if (!$CFG_GLPI["notifications_mailing"]) {
            return 0;
        }

        $CronTask = new CronTask();
        if ($CronTask->getFromDBbyName(OcsAlert::class, "SynchroAlert")) {
            if ($CronTask->fields["state"] == CronTask::STATE_DISABLE) {
                return 0;
            }
        } else {
            return 0;
        }

        $message     = [];
        $cron_status = 0;

        foreach (self::getEntitiesToNotify('delay_ocs') as $entity => $delay_ocs) {
            foreach ($DB->request([
                'FROM' => 'glpi_plugin_ocsinventoryng_ocsservers',
                'WHERE' => ['is_active' => 1]]) as $config) {
                $query_ocs = self::query($delay_ocs, $config, $entity);

                $ocs_infos    = [];
                $ocs_messages = [];

                $type             = Alert::END;
                $ocs_infos[$type] = [];
                foreach ($DB->request($query_ocs) as $data) {
                    $entity                      = $data['entities_id'];
                    $message                     = $data["name"];
                    $ocs_infos[$type][$entity][] = $data;

                    if (!isset($ocs_messages[$type][$entity])) {
                        $ocs_messages[$type][$entity] = OcsAlert::getTypeName(2) . "<br />";
                    }
                    $ocs_messages[$type][$entity] .= $message;
                }

                foreach ($ocs_infos[$type] as $entity => $ocsmachines) {
                    Plugin::loadLang('ocsinventoryng');

                    if (NotificationEvent::raiseEvent(
                        "ocs",
                        new OcsAlert(),
                        ['entities_id' => $entity,
                            'ocsmachines' => $ocsmachines,
                            'delay_ocs'   => $delay_ocs],
                    )) {
                        $message     = $ocs_messages[$type][$entity];
                        $cron_status = 1;
                        if ($task) {
                            $task->log(Dropdown::getDropdownName(
                                "glpi_entities",
                                $entity,
                            ) . ":  $message\n");
                            $task->addVolume(1);
                        } else {
                            Session::addMessageAfterRedirect(Dropdown::getDropdownName(
                                "glpi_entities",
                                $entity,
                            ) . ":  $message");
                        }
                    } else {
                        if ($task) {
                            $task->log(Dropdown::getDropdownName("glpi_entities", $entity) .
                                ":  Send ocsmachines alert failed\n");
                        } else {
                            Session::addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities", $entity) .
                                                      ":  Send ocsmachines alert failed", false, ERROR);
                        }
                    }
                }
            }
        }
        return $cron_status;
    }

    /**
     * @param null $task
     *
     * @return int
     */
    public static function cronAlertNewComputers($task = null)
    {
        global $DB, $CFG_GLPI;

        if (!$CFG_GLPI["notifications_mailing"]) {
            return 0;
        }

        $CronTask = new CronTask();
        if ($CronTask->getFromDBbyName(OcsAlert::class, "AlertNewComputers")) {
            if ($CronTask->fields["state"] == CronTask::STATE_DISABLE) {
                return 0;
            }
        } else {
            return 0;
        }

        $message     = [];
        $cron_status = 0;

        foreach (self::getEntitiesToNotify('use_newocs_alert') as $entity => $delay_ocs) {
            foreach ($DB->request([
                'FROM' => 'glpi_plugin_ocsinventoryng_ocsservers',
                'WHERE' => ['is_active' => 1]]) as $config) {
                $query_newocsmachine = self::queryNew($delay_ocs, $config, $entity);

                $newocsmachine_infos    = [];
                $newocsmachine_messages = [];

                $type                       = Alert::END;
                $newocsmachine_infos[$type] = [];
                foreach ($DB->request($query_newocsmachine) as $data) {
                    $entity                                = $data['entities_id'];
                    $message                               = $data["name"];
                    $newocsmachine_infos[$type][$entity][] = $data;

                    if (!isset($newocsmachine_messages[$type][$entity])) {
                        $newocsmachine_messages[$type][$entity] = __('New imported computers from OCS-NG', 'ocsinventoryng') . "<br />";
                    }
                    $newocsmachine_messages[$type][$entity] .= $message;
                }

                $delay_ocs = 0;
                foreach ($newocsmachine_infos[$type] as $entity => $newocsmachines) {
                    Plugin::loadLang('ocsinventoryng');

                    if (NotificationEvent::raiseEvent(
                        "newocs",
                        new OcsAlert(),
                        ['entities_id' => $entity,
                            'ocsmachines' => $newocsmachines,
                            'delay_ocs'   => $delay_ocs],
                    )) {
                        $message     = $newocsmachine_messages[$type][$entity];
                        $cron_status = 1;
                        if ($task) {
                            $task->log(Dropdown::getDropdownName(
                                "glpi_entities",
                                $entity,
                            ) . ":  $message\n");
                            $task->addVolume(1);
                        } else {
                            Session::addMessageAfterRedirect(Dropdown::getDropdownName(
                                "glpi_entities",
                                $entity,
                            ) . ":  $message");
                        }
                    } else {
                        if ($task) {
                            $task->log(Dropdown::getDropdownName("glpi_entities", $entity) .
                                ":  Send newocsmachines alert failed\n");
                        } else {
                            Session::addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities", $entity) .
                                                      ":  Send newocsmachines alert failed", false, ERROR);
                        }
                    }
                }
            }
        }
        return $cron_status;
    }

    /**
     * @param $target
     * @param $ID
     */
    public static function configCron()
    {

        $state = new NotificationState();
        $used  = [];
        foreach ($state->find() as $data) {
            $used[] = $data['states_id'];
        }

        TemplateRenderer::getInstance()->display('@ocsinventoryng/alert_states.html.twig', [
            'form_url' => Toolbox::getItemTypeFormURL(__CLASS__),
            'used'     => $used,
        ]);

        $state->configState();
    }


    /**
     * @param Entity $entity
     *
     * @return bool
     */
    public static function showNotificationOptions(Entity $entity)
    {
        $ID = $entity->getField('id');
        if (!$entity->can($ID, READ)) {
            return false;
        }

        // Get data
        $entitynotification = new OcsAlert();
        if (!$entitynotification->getFromDBByCrit(['entities_id' => $ID])) {
            $entitynotification->getEmpty();
        }

        TemplateRenderer::getInstance()->display('@ocsinventoryng/entity_alerts.html.twig', [
            'form_url'         => Toolbox::getItemTypeFormURL(__CLASS__),
            // Notification right applied
            'canedit'          => Session::haveRight('notification', UPDATE) && Session::haveAccessToEntity($ID),
            'entities_id'      => (int) $ID,
            'id'               => (int) ($entitynotification->fields["id"] ?? 0),
            'use_newocs_alert' => (int) $entitynotification->fields["use_newocs_alert"],
            'delay_ocs'        => (int) $entitynotification->fields["delay_ocs"],
            'days'             => Config::getAlertDelays(),
        ]);

        return true;
    }
}
