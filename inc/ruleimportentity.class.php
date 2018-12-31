<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
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
 * Class PluginOcsinventoryngRuleImportEntity
 */
class PluginOcsinventoryngRuleImportEntity extends CommonDBTM {

   static $rightname = "plugin_ocsinventoryng";

   const NO_RULE = 0;

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {
      return __('Elements not match with the rule', 'ocsinventoryng');
   }

   /**
    * @param $name
    *
    * @return array
    */
   static function cronInfo($name) {

      switch ($name) {
         case "CheckRuleImportEntity" :
            return ['description' => __('OCSNG', 'ocsinventoryng') . " - " .
                                     __('Alerts on computers that no longer respond the rules for assigning an item to an entity', 'ocsinventoryng')];
      }
   }


   /**
    * Checking machines that no longer respond the assignment rules
    *
    * @param $task
    *
    * @return int
    */
   static function cronCheckRuleImportEntity($task) {
      global $DB, $CFG_GLPI;

      ini_set("memory_limit", "-1");
      ini_set("max_execution_time", "0");

      if (!$CFG_GLPI["notifications_mailing"]) {
         return 0;
      }

      $CronTask = new CronTask();
      if ($CronTask->getFromDBbyName("PluginOcsinventoryngRuleImportEntity", "CheckRuleImportEntity")) {
         if ($CronTask->fields["state"] == CronTask::STATE_DISABLE) {
            return 0;
         }
      } else {
         return 0;
      }

      $cron_status                         = 0;
      $plugin_ocsinventoryng_ocsservers_id = 0;
      foreach ($DB->request("glpi_plugin_ocsinventoryng_ocsservers", "`is_active` = 1 
                              AND `use_checkruleimportentity` = 1") as $config) {
         $plugin_ocsinventoryng_ocsservers_id   = $config["id"];
         $plugin_ocsinventoryng_ocsservers_name = $config["name"];

         if ($plugin_ocsinventoryng_ocsservers_id > 0) {

            $computers = self::checkRuleImportEntity($plugin_ocsinventoryng_ocsservers_id);

            foreach ($computers as $entities_id => $items) {
               $message = $plugin_ocsinventoryng_ocsservers_name . ": <br />" .
                          sprintf(__('Items that do not meet the allocation rules for the entity %s: %s', 'ocsinventoryng'),
                                  Dropdown::getDropdownName("glpi_entities", $entities_id),
                                  count($items)) . "<br />";

               if (NotificationEvent::raiseEvent("CheckRuleImportEntity",
                                                 new PluginOcsinventoryngRuleImportEntity(),
                                                 ['entities_id' => $entities_id,
                                                  'items'       => $items])) {

                  $cron_status = 1;
                  if ($task) {
                     $task->addVolume(1);
                     $task->log($message);
                  } else {
                     Session::addMessageAfterRedirect($message);
                  }

               } else {
                  if ($task) {
                     $task->addVolume(count($items));
                     $task->log($message . "\n");
                  } else {
                     Session::addMessageAfterRedirect($message);
                  }
               }
            }
         }
      }

      return $cron_status;
   }


   /**
    * Checks the assignment rules for the server computers
    *
    * @param $plugin_ocsinventoryng_ocsservers_id
    *
    * @return array
    */
   static function checkRuleImportEntity($plugin_ocsinventoryng_ocsservers_id) {

      $data = [];

      $computers = self::getComputerOcsLink($plugin_ocsinventoryng_ocsservers_id);

      $ruleCollection = new RuleImportEntityCollection();
      $ruleAsset = new RuleAssetCollection();

      foreach ($computers as $computer) {

         $fields = $ruleCollection->processAllRules($computer,
                                                    [],
                                                    ['ocsid' => $computer['ocsid']]);


         //case pc matched with a rule
         if (isset($fields['_ruleid'])) {
            $entities_id = $computer['entities_id'];

            //Verification of the entity and location
            if (isset($fields['entities_id']) && $fields['entities_id'] != $entities_id) {

               if (!isset($data[$entities_id])) {
                  $data[$entities_id] = [];
               }

               $data[$entities_id][$computer['id']]           = $computer;
               $data[$entities_id][$computer['id']]['ruleid'] = $fields['_ruleid'];
               $rule                                          = new Rule();
               $rule->getFromDB($fields['_ruleid']);
               $data[$entities_id][$computer['id']]['rule_name'] = $rule->fields['name'];

               if (isset($fields['entities_id']) && $fields['entities_id'] != $entities_id) {

                  if (!isset($data[$fields['entities_id']])) {
                     $data[$fields['entities_id']] = [];
                  }

                  $data[$entities_id][$computer['id']]['error'][]     = 'Entity';
                  $data[$entities_id][$computer['id']]['dataerror'][] = $fields['entities_id'];

                  $data[$fields['entities_id']][$computer['id']] = $data[$entities_id][$computer['id']];
               }
            }

            $output = ['locations_id' => $computer['locations_id']];
            $fields = $ruleAsset->processAllRules($computer, $output, ['ocsid' => $computer['ocsid']]);

            if (isset($fields['locations_id']) && $fields['locations_id'] != $computer['locations_id']
                 || !isset($fields['locations_id']) && $computer['locations_id'] != 0) {

               if(!isset($data[$entities_id][$computer['id']])) {
                  $data[$entities_id][$computer['id']]           = $computer;
               }

               $data[$entities_id][$computer['id']]['error'][]     = 'Location';
               $data[$entities_id][$computer['id']]['dataerror'][] = $fields['locations_id'];
            }

         } else {
            //No rules match
            $entities_id = $computer['entities_id'];

            if (!isset($data[$entities_id])) {
               $data[$entities_id] = [];
            }
            $data[$entities_id][$computer['id']]            = $computer;
            $data[$entities_id][$computer['id']]['error'][] = self::NO_RULE;
         }

      }

      return $data;

   }

   /**
    * @param $plugin_ocsinventoryng_ocsservers_id
    *
    * @return array
    */
   static function getComputerOcsLink($plugin_ocsinventoryng_ocsservers_id) {
      $ocslink = new PluginOcsinventoryngOcslink();

      $ocslinks = $ocslink->find(["plugin_ocsinventoryng_ocsservers_id" => $plugin_ocsinventoryng_ocsservers_id],
                                 ["entities_id"]);

      $computers = [];
      foreach ($ocslinks as $ocs) {
         $computer = new Computer();
         if ($computer->getFromDB($ocs['computers_id'])) {
            $computer_id                              = $computer->fields['id'];
            $computers[$computer_id]                  = $computer->fields;
            $computers[$computer_id]['ocsservers_id'] = $ocs['plugin_ocsinventoryng_ocsservers_id'];
            $computers[$computer_id]['ocsid']         = $ocs['ocsid'];
            $computers[$computer_id]['_source']       = 'ocsinventoryng';
            $computers[$computer_id]['_auto']         = true;

         }
      }

      return $computers;

   }

}