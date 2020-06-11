<?php
/*
  -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2016 by the ocsinventoryng Development Team.

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

/**
 * Class PluginOcsinventoryngOcsAlert
 */
class PluginOcsinventoryngOcsAlert extends CommonDBTM {

   static $rightname = "plugin_ocsinventoryng";

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {

      return __('OCS-NG Synchronization alerts', 'ocsinventoryng');
   }

   /**
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return string|translated
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == 'CronTask' && $item->getField('name') == "SynchroAlert") {
         return __('Plugin setup', 'ocsinventoryng');
      } else if ($item->getType() == 'Entity') {
         return self::getTypeName(2);
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
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == 'CronTask') {
         self::configCron();
      } else if ($item->getType() == 'Entity') {

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
   static function cronInfo($name) {

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
   static function queryNew($delay_ocs, $config, $entity) {

      $delay_stamp_ocs = mktime(0, 0, 0, date("m"), date("d") - $delay_ocs, date("y"));
      $date_ocs        = date("Y-m-d", $delay_stamp_ocs);
      $date_ocs        = $date_ocs . " 00:00:00";

      $query = "SELECT `glpi_plugin_ocsinventoryng_ocslinks`.`last_ocs_update`,
                      `glpi_plugin_ocsinventoryng_ocslinks`.`last_update`,
                      `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id`, 
                      `glpi_computers`.*,
                      `glpi_items_operatingsystems`.`operatingsystems_id`
            FROM `glpi_plugin_ocsinventoryng_ocslinks`
            LEFT JOIN `glpi_computers` ON `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id` = `glpi_computers`.`id`
            LEFT JOIN `glpi_items_operatingsystems` ON (`glpi_computers`.`id` = `glpi_items_operatingsystems`.`items_id` 
                AND `glpi_items_operatingsystems`.`itemtype` = 'Computer')
            WHERE `glpi_computers`.`is_deleted` = 0
            AND `glpi_computers`.`is_template` = 0
            AND `last_ocs_update` >= '" . $date_ocs . "' 
            AND `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id` = '" . $config["id"] . "' ";
      $query .= "AND `glpi_computers`.`entities_id` = '" . $entity . "' ";
      $query .= " ORDER BY `glpi_plugin_ocsinventoryng_ocslinks`.`last_ocs_update` ASC";

      return $query;

   }

   /**
    * @param $delay_ocs
    * @param $config
    * @param $entity
    *
    * @return string
    */
   static function query($delay_ocs, $config, $entity) {
      global $DB;

      $delay_stamp_ocs = mktime(0, 0, 0, date("m"), date("d") - $delay_ocs, date("y"));
      $date_ocs        = date("Y-m-d", $delay_stamp_ocs);
      $date_ocs        = $date_ocs . " 00:00:00";

      $query = "SELECT `glpi_plugin_ocsinventoryng_ocslinks`.`last_ocs_update`,
                        `glpi_plugin_ocsinventoryng_ocslinks`.`last_update`,
                        `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id`, 
                        `glpi_computers`.*,
                        `glpi_items_operatingsystems`.`operatingsystems_id`
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                  LEFT JOIN `glpi_computers` 
                    ON `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id` = `glpi_computers`.`id`
                  LEFT JOIN `glpi_items_operatingsystems` 
                    ON (`glpi_computers`.`id` = `glpi_items_operatingsystems`.`items_id` 
                      AND `glpi_items_operatingsystems`.`itemtype` = 'Computer')
                WHERE `glpi_computers`.`is_deleted` = 0
                AND `glpi_computers`.`is_template` = 0
                AND `last_ocs_update` <= '" . $date_ocs . "' 
                AND `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id` = " . $config["id"];

      $query_state = "SELECT `states_id`
            FROM `glpi_plugin_ocsinventoryng_notificationstates` ";
      $result_state = $DB->query($query_state);
      if ($DB->numrows($result_state) > 0) {
         $query .= " AND (`glpi_computers`.`states_id` = 999999 ";
         while ($data_state = $DB->fetchArray($result_state)) {
            $type_where = "OR `glpi_computers`.`states_id` = " . $data_state["states_id"] . " ";
            $query      .= " $type_where ";
         }
         $query .= ") ";
      }

      $query .= " AND `glpi_computers`.`entities_id` = $entity";
      $query .= " ORDER BY `glpi_plugin_ocsinventoryng_ocslinks`.`last_ocs_update` DESC";

      return $query;

   }

   /**
    * @param $data
    *
    * @return string
    */
   static function displayBody($data) {
      global $CFG_GLPI;

      $computer = new computer();
      $computer->getFromDB($data["id"]);

      $body = "<tr class='tab_bg_2'><td><a href=\"" . $CFG_GLPI["root_doc"] . "/front/computer.form.php?id=" . $computer->fields["id"] . "\">" . $computer->fields["name"];

      if ($_SESSION["glpiis_ids_visible"] == 1 || empty($computer->fields["name"])) {
         $body .= " (";
         $body .= $computer->fields["id"] . ")";
      }
      $body .= "</a></td>";
      if (Session::isMultiEntitiesMode()) {
         $body .= "<td class='center'>" . Dropdown::getDropdownName("glpi_entities", $data["entities_id"]) . "</td>";
      }
      $item_operatingsystems = new Item_OperatingSystem();
      $item_operatingsystems->getFromDBByCrit(['itemtype' => 'Computer',
                                               'items_id' => $computer->getID()]);
      $body .= "<td>" . Dropdown::getDropdownName("glpi_operatingsystems",
                                                  $item_operatingsystems->getField("operatingsystems_id")) . "</td>";
      $body .= "<td>" . Dropdown::getDropdownName("glpi_states", $computer->fields["states_id"]) . "</td>";
      $body .= "<td>" . Dropdown::getDropdownName("glpi_locations", $computer->fields["locations_id"]) . "</td>";
      $body .= "<td>";
      if (!empty($computer->fields["users_id"])) {
         $dbu  = new DbUtils();
         $body .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/user.form.php?id=" . $computer->fields["users_id"] . "\">" .
                  $dbu->getUserName($computer->fields["users_id"]) . "</a>";
      }

      if (!empty($computer->fields["groups_id"])) {
         $body .= " - <a href=\"" . $CFG_GLPI["root_doc"] . "/front/group.form.php?id=" . $computer->fields["groups_id"] . "\">";
      }

      $body .= Dropdown::getDropdownName("glpi_groups", $computer->fields["groups_id"]);
      if ($_SESSION["glpiis_ids_visible"] == 1) {
         $body .= " (";
         $body .= $computer->fields["groups_id"] . ")";
      }
      $body .= "</a>";

      if (!empty($computer->fields["contact"])) {
         $body .= " - " . $computer->fields["contact"];
      }

      $body .= " - </td>";
      $body .= "<td>" . Html::convdatetime($data["last_ocs_update"]) . "</td>";
      $body .= "<td>" . Html::convdatetime($data["last_update"]) . "</td>";
      $body .= "<td>" . Dropdown::getDropdownName("glpi_plugin_ocsinventoryng_ocsservers",
                                                  $data["plugin_ocsinventoryng_ocsservers_id"]) . "</td>";

      $body .= "</tr>";

      return $body;
   }

   /**
    * @param      $field
    * @param bool $with_value
    *
    * @return array
    */
   static function getEntitiesToNotify($field, $with_value = false) {
      global $DB;

      $query = "SELECT `entities_id` as `entity`,`$field`
               FROM `glpi_plugin_ocsinventoryng_ocsalerts`";
      $query .= " ORDER BY `entities_id` ASC";

      $entities = [];
      $result   = $DB->query($query);

      if ($DB->numrows($result) > 0) {
         foreach ($DB->request($query) as $entitydatas) {
            self::getDefaultValueForNotification($field, $entities, $entitydatas);
         }
      } else {
         $config = new PluginOcsinventoryngConfig();
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
   static function getDefaultValueForNotification($field, &$entities, $entitydatas) {

      $config = new PluginOcsinventoryngConfig();
      $config->getFromDB(1);
      //If there's a configuration for this entity & the value is not the one of the global config
      if (isset($entitydatas[$field]) && $entitydatas[$field] > 0) {
         $entities[$entitydatas['entity']] = $entitydatas[$field];
      } //No configuration for this entity : if global config allows notification then add the entity
      //to the array of entities to be notified
      else if ((!isset($entitydatas[$field])
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
   static function cronSynchroAlert($task = null) {
      global $DB, $CFG_GLPI;

      if (!$CFG_GLPI["notifications_mailing"]) {
         return 0;
      }

      $CronTask = new CronTask();
      if ($CronTask->getFromDBbyName("PluginOcsinventoryngOcsAlert", "SynchroAlert")) {
         if ($CronTask->fields["state"] == CronTask::STATE_DISABLE) {
            return 0;
         }
      } else {
         return 0;
      }

      $message     = [];
      $cron_status = 0;

      foreach (self::getEntitiesToNotify('delay_ocs') as $entity => $delay_ocs) {

         foreach ($DB->request("glpi_plugin_ocsinventoryng_ocsservers", "`is_active` = 1") as $config) {
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
                  $ocs_messages[$type][$entity] = PluginOcsinventoryngOcsAlert::getTypeName(2) . "<br />";
               }
               $ocs_messages[$type][$entity] .= $message;
            }

            foreach ($ocs_infos[$type] as $entity => $ocsmachines) {
               Plugin::loadLang('ocsinventoryng');

               if (NotificationEvent::raiseEvent("ocs",
                                                 new PluginOcsinventoryngOcsAlert(),
                                                 ['entities_id' => $entity,
                                                  'ocsmachines' => $ocsmachines,
                                                  'delay_ocs'   => $delay_ocs])) {
                  $message     = $ocs_messages[$type][$entity];
                  $cron_status = 1;
                  if ($task) {
                     $task->log(Dropdown::getDropdownName("glpi_entities",
                                                          $entity) . ":  $message\n");
                     $task->addVolume(1);
                  } else {
                     Session::addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities",
                                                                                $entity) . ":  $message");
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
   static function cronAlertNewComputers($task = null) {
      global $DB, $CFG_GLPI;

      if (!$CFG_GLPI["notifications_mailing"]) {
         return 0;
      }

      $CronTask = new CronTask();
      if ($CronTask->getFromDBbyName("PluginOcsinventoryngOcsAlert", "AlertNewComputers")) {
         if ($CronTask->fields["state"] == CronTask::STATE_DISABLE) {
            return 0;
         }
      } else {
         return 0;
      }

      $message     = [];
      $cron_status = 0;

      foreach (self::getEntitiesToNotify('use_newocs_alert') as $entity => $delay_ocs) {

         foreach ($DB->request("glpi_plugin_ocsinventoryng_ocsservers", "`is_active` = 1") as $config) {
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

               if (NotificationEvent::raiseEvent("newocs",
                                                 new PluginOcsinventoryngOcsAlert(),
                                                 ['entities_id' => $entity,
                                                  'ocsmachines' => $newocsmachines,
                                                  'delay_ocs'   => $delay_ocs])) {
                  $message     = $newocsmachine_messages[$type][$entity];
                  $cron_status = 1;
                  if ($task) {
                     $task->log(Dropdown::getDropdownName("glpi_entities",
                                                          $entity) . ":  $message\n");
                     $task->addVolume(1);
                  } else {
                     Session::addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities",
                                                                                $entity) . ":  $message");
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
   static function configCron() {

      $state  = new PluginOcsinventoryngNotificationState();
      $states = $state->find();
      $used   = [];
      foreach ($states as $data) {
         $used[] = $data['states_id'];
      }

      echo "<div align='center'>";
      echo "<form method='post' action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
      echo "<table class='tab_cadre_fixe' cellpadding='5'>";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='2'>" . __('Statuses used for alert', 'ocsinventoryng') . "</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Status');
      echo "</td>";
      echo "<td>";
      Dropdown::show('State', ['name' => "states_id",
                               'used' => $used]);
      echo Html::submit(_sx('button', 'Add'), ['name' => 'add_state']);
      echo "</td>";
      echo "</tr>";
      echo "</table>";
      Html::closeForm();

      echo "</div>";

      $state->configState();

   }

   /**
    * @param $entities_id
    *
    * @return bool
    */
   function getFromDBbyEntity($entities_id) {
      global $DB;

      $query = "SELECT *
                FROM `" . $this->getTable() . "`
                WHERE `entities_id` = '$entities_id'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 1) {
            return false;
         }
         $this->fields = $DB->fetchAssoc($result);
         if (is_array($this->fields) && count($this->fields)) {
            return true;
         }
         return false;
      }
      return false;
   }

   /**
    * @param Entity $entity
    *
    * @return bool
    */
   static function showNotificationOptions(Entity $entity) {

      $con_spotted = false;

      $ID = $entity->getField('id');
      if (!$entity->can($ID, READ)) {
         return false;
      }

      // Notification right applied
      $canedit = Session::haveRight('notification', UPDATE) && Session::haveAccessToEntity($ID);

      // Get data
      $entitynotification = new PluginOcsinventoryngOcsAlert();
      if (!$entitynotification->getFromDBbyEntity($ID)) {
         $entitynotification->getEmpty();
      }

      if ($canedit) {
         echo "<form method='post' name=form action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
      }
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'>";
      echo "<th colspan='3'>" . __('OCS-NG Synchronization alerts', 'ocsinventoryng');
      echo "</th></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('New imported computers from OCS-NG', 'ocsinventoryng') . "</td><td>";
      Alert::dropdownIntegerNever('use_newocs_alert', $entitynotification->fields["use_newocs_alert"],
                                  ['max'            => 99,
                                   'inherit_global' => 1]);
      echo "&nbsp;" . _n('Day', 'Days', 2) . "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td >" . __('Computers not synchronized with OCS-NG since more', 'ocsinventoryng') . "</td><td>";
      Alert::dropdownIntegerNever('delay_ocs', $entitynotification->fields["delay_ocs"],
                                  ['max'            => 99,
                                   'inherit_global' => 1]);
      echo "&nbsp;" . _n('Day', 'Days', 2) . "</td>";
      echo "</tr>";

      if ($canedit) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='4'>";
         echo Html::hidden('entities_id', ['value' => $ID]);
         if ($entitynotification->fields["id"]) {
            echo Html::hidden('id', ['value' => $entitynotification->fields["id"]]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         } else {
            echo Html::submit(_sx('button', 'Save'), ['name' => 'add']);
         }
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
      } else {
         echo "</table>";
      }
   }
}

