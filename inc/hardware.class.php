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
 * Class PluginOcsinventoryngHardware
 */
class PluginOcsinventoryngHardware extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Computer';
   static public $items_id = 'computers_id';

   static $rightname = "plugin_ocsinventoryng";


   /**
    * Update lockable fields of an item
    *
    * @param $item                     CommonDBTM object
    *
    * @return void
    * @internal param int|string $withtemplate integer  withtemplate param (default '')
    */
   static function updateLockforComputer(CommonDBTM $item) {

      $ocslink = new PluginOcsinventoryngOcslink();
      if ($item->fields["is_dynamic"]
          && $ocslink->getFromDBforComputer($item->getID())
          && (count($item->updates) > 1)
          && (!isset($item->input["_nolock"]))) {

         $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($ocslink->fields["plugin_ocsinventoryng_ocsservers_id"]);
         if ($cfg_ocs["use_locks"]) {
            $updates = [];
            foreach ($item->updates as $k => $field) {
               if (array_key_exists($field,
                                    PluginOcsinventoryngOcslink::getLockableFields($ocslink->fields["plugin_ocsinventoryng_ocsservers_id"],
                                                                                   $ocslink->fields["ocsid"]))) {
                  $updates[] = $field;
               }
            }
            PluginOcsinventoryngOcslink::mergeOcsArray($item->fields["id"], $updates);
         }
      }
   }

   /**
    * @param int $plugin_ocsinventoryng_ocsservers_id
    *
    * @return array
    */
   static function getHardwareLockableFields($plugin_ocsinventoryng_ocsservers_id = 0) {

      if ($plugin_ocsinventoryng_ocsservers_id > 0) {

         $locks   = [];
         $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);

         if (intval($cfg_ocs["import_general_name"]) > 0) {
            $locks["name"] = __('Name');
         }

         if (intval($cfg_ocs["import_general_comment"]) > 0) {
            $locks["comment"] = __('Comments');
         }

         if (intval($cfg_ocs["import_general_contact"]) > 0) {
            $locks["contact"] = __('Alternate username');
            $locks["users_id"] = __('User');
         }

         if (intval($cfg_ocs["import_general_type"]) > 0
             && intval($cfg_ocs["import_device_bios"]) > 0) {
            $locks["computertypes_id"] = __('Type');
         }

         if (intval($cfg_ocs["import_general_domain"]) > 0) {
            $locks["domains_id"] = __('Domain');
         }

         if (intval($cfg_ocs["import_general_uuid"]) > 0) {
            $locks["uuid"] = __('UUID');
         }

      } else {
         $locks = ["name"       => __('Name'),
                   "comment"    => __('Comments'),
                   "contact"    => __('Alternate username'),
                   "domains_id" => __('Domain'),
                   "uuid"       => __('UUID'),
                   "users_id"   => __('User')];
      }

      return $locks;
   }


   /**
    * @param int $plugin_ocsinventoryng_ocsservers_id
    * @param int $ocsid
    *
    * @return array
    */
   static function getRuleLockableFields($plugin_ocsinventoryng_ocsservers_id = 0, $ocsid = 0) {

      if ($plugin_ocsinventoryng_ocsservers_id > 0 && $ocsid > 0) {

         $locks   = [];
         $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);

         $rule         = new RuleImportEntityCollection();
         $locations_id = 0;
         $groups_id    = 0;
         $data         = $rule->processAllRules(['ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                                                 '_source'       => 'ocsinventoryng',
                                                 'locations_id'  => $locations_id,
                                                 'groups_id'     => $groups_id],
                                                ['locations_id' => $locations_id,
                                                 'groups_id'    => $groups_id],
                                                ['ocsid' => $ocsid]);

         if (intval($cfg_ocs["import_user_group"]) > 0 || intval($cfg_ocs["import_user_group_default"])) {
            $locks["groups_id"] = __('Group');
         } else if (isset($data['groups_id']) && $data['groups_id'] > 0) {
            $locks["groups_id"] = __('Group');
         }

         if (intval($cfg_ocs["import_user_location"]) > 0) {
            $locks["locations_id"] = __('Location');
         } else if (isset($data['locations_id']) && $data['locations_id'] > 0) {
            $locks["locations_id"] = __('Location');
         }

         if (isset($data['groups_id_tech']) && $data['groups_id_tech'] > 0) {
            $locks["groups_id_tech"] = __('Group in charge of the hardware');
         }
      } else {
         $locks = ["locations_id"   => __('Location'),
                   "groups_id"      => __('Group'),
                   "groups_id_tech" => __('Group in charge of the hardware')];
      }

      return $locks;
   }


   /**
    * @param array $options
    *
    * @return void
    * @throws \GlpitestSQLError
    */
   static function updateComputerHardware($options = []) {
      global $DB;

      $is_utf8 = $options['cfg_ocs']["ocs_db_utf8"];
      $force   = $options["force"];
      $cfg_ocs = $options['cfg_ocs'];

      $update_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && $cfg_ocs['history_hardware'] == 1) {
         $update_history = 1;
      }

      if (isset($options['HARDWARE'])) {
         $hardware = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($options['HARDWARE']));

         $updates = [];

         if (intval($options['cfg_ocs']["import_general_domain"]) > 0
             && !in_array("domains_id", $options['computers_updates'])) {
            $updates["domains_id"] = Dropdown::importExternal('Domain', PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($is_utf8, $hardware["WORKGROUP"]), $options['entities_id']);
         }

         if (intval($options['cfg_ocs']["import_general_contact"]) > 0
             && !in_array("contact", $options['computers_updates'])) {

            $updates["contact"] = PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($is_utf8, $hardware["USERID"]);
         }

         if (intval($options['cfg_ocs']["import_general_name"]) > 0
             && !in_array("name", $options['computers_updates'])) {
            $updates["name"] = PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($is_utf8, $hardware["NAME"]);
         }

         if (intval($options['cfg_ocs']["import_general_comment"]) > 0
             && !in_array("comment", $options['computers_updates'])) {

            $updates["comment"] = "";
            if (!empty($hardware["DESCRIPTION"])
                && $hardware["DESCRIPTION"] != NOT_AVAILABLE) {
               $updates["comment"] .= PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($is_utf8, $hardware["DESCRIPTION"])
                                      . "\r\n";
            }
            $updates["comment"] .= sprintf(__('%1$s: %2$s'), __('Swap', 'ocsinventoryng'), PluginOcsinventoryngOcsProcess::encodeOcsDataInUtf8($is_utf8, $hardware["SWAP"]));
         }

         if ($options['cfg_ocs']['ocs_version'] >= PluginOcsinventoryngOcsServer::OCS1_3_VERSION_LIMIT
             && intval($options['cfg_ocs']["import_general_uuid"]) > 0
             && !in_array("uuid", $options['computers_updates'])) {
            $updates["uuid"] = $hardware["UUID"];
         }

         if (count($updates) || $force) {
            $updates["id"]          = $options['computers_id'];
            $updates["entities_id"] = $options['entities_id'];
            $updates["_nolock"]     = true;
            $updates["_no_history"] = !$update_history;
            $updates['_auto']       = true;

            $comp = new Computer();
            $comp->update($updates, $update_history);
         }
      }
   }


   /**
    * Update fields : location / group for a computer if needed after rule processing
    *
    * @param $line_links
    * @param $data
    * @param $history_hardware
    *
    * @return void
    * @internal param $line_links
    * @internal param $data
    */
   static function updateComputerFields($line_links, $data, $cfg_ocs) {

      $update_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && $cfg_ocs['history_hardware'] == 1) {
         $update_history = 1;
      }
      $tmp = [];

      $computer = new Computer();
      if ($computer->getFromDB($line_links['computers_id'])) {

         $dbu       = new DbUtils();
         $ancestors = $dbu->getAncestorsOf('glpi_entities', $computer->fields['entities_id']);

         //If there's a location to update
         if (isset($data['locations_id'])) {

            $location = new Location();
            if ($location->getFromDB($data['locations_id'])) {
               //If location is in the same entity as the computer, or if the location is
               //defined in a parent entity, but recursive
               if ($location->fields['entities_id'] == $computer->fields['entities_id']
                   || (in_array($location->fields['entities_id'], $ancestors)
                       && $location->fields['is_recursive'])) {
                  $ko    = 0;
                  $locks = PluginOcsinventoryngOcslink::getLocksForComputer($line_links['computers_id']);
                  if (is_array($locks) && count($locks)) {
                     if (in_array("locations_id", $locks)) {
                        $ko = 1;
                     }
                  }
                  if ($ko == 0) {
                     $tmp['locations_id'] = $data['locations_id'];
                  }
               }
            }
         }

         //If there's a recursive to update
         if (isset($data['is_recursive'])) {
            $tmp['is_recursive'] = $data['is_recursive'];
         }

         //If there's a Group to update
         if (isset($data['groups_id'])) {

            $group = new Group();
            $ko    = 1;
            $locks = PluginOcsinventoryngOcslink::getLocksForComputer($line_links['computers_id']);
            if ($group->getFromDB($data['groups_id'])) {
               //If group is in the same entity as the computer, or if the group is
               //defined in a parent entity, but recursive
               if ($group->fields['entities_id'] == $computer->fields['entities_id']
                   || (in_array($group->fields['entities_id'], $ancestors)
                       && $group->fields['is_recursive'])) {
                  $ko = 0;

                  if (is_array($locks) && count($locks)) {
                     if (in_array("groups_id", $locks)) {
                        $ko = 1;
                     }
                  }

               }
            } else if ($data['groups_id'] == 0) {
               $ko = 0;
               if (is_array($locks) && count($locks)) {
                  if (in_array("groups_id", $locks)) {
                     $ko = 1;
                  }
               }
            }
            if ($ko == 0) {
               $tmp['groups_id'] = $data['groups_id'];
            }
         }
         if (count($tmp) > 0) {
            $tmp["_nolock"]     = true;
            $tmp['id']          = $line_links['computers_id'];
            $tmp["_no_history"] = !$update_history;
            $computer->update($tmp, $update_history);
         }
      }
   }

   /**
    * @param        $userid
    *
    * @return array|int
    */
   static function getUserDefaultGroup($userid) {

      $user = new User();
      if ($user->getFromDB($userid)) {
         return $user->getField('groups_id');
      }
      return 0;
   }

   /**
    * @param        $entity
    * @param        $userid
    * @param string $filter
    * @param bool   $first
    *
    * @return array|int
    */
   static function getUserGroup($entity, $userid, $filter = '', $first = true) {
      global $DB;

      $dbu   = new DbUtils();
      $query = "SELECT `glpi_groups`.`id`
                FROM `glpi_groups_users`
                INNER JOIN `glpi_groups` ON (`glpi_groups`.`id` = `glpi_groups_users`.`groups_id`)
                WHERE `glpi_groups_users`.`users_id` = " . $userid .
               $dbu->getEntitiesRestrictRequest(' AND ', 'glpi_groups', '', $entity, true);

      if ($filter) {
         $query .= "AND (" . $filter . ")";
      }
      $rep = [];
      foreach ($DB->request($query) as $data) {
         if ($first) {
            return $data['id'];
         }
         $rep[] = $data['id'];
      }
      return ($first ? 0 : $rep);
   }

   /**
    * @param     $ocsComputer
    * @param     $cfg_ocs
    * @param     $values
    * @param int $computers_id
    *
    * @throws \GlpitestSQLError
    */
   static function getFields($ocsComputer, $cfg_ocs, &$values, $computers_id = 0) {
      global $DB;

      $contact = (isset($ocsComputer['META']["USERID"])) ? $ocsComputer['META']["USERID"] : "";
      if (!empty($contact)) {
         $query  = "SELECT `id`
                   FROM `glpi_users`
                   WHERE `name` = '" . $contact . "';";
         $result = $DB->query($query);

         if ($DB->numrows($result) == 1) {
            $user_id = $DB->result($result, 0, 0);
            $user    = new User();
            $user->getFromDB($user_id);
            if ($cfg_ocs["import_user_location"] > 0) {
               $values['locations_id'] = $user->fields["locations_id"];
            }
            if ($cfg_ocs["import_user_group_default"]) {
               $values['groups_id'] = self::getUserDefaultGroup($user_id);
            }
            if ($cfg_ocs["import_user_group"] > 0 &&
                (isset($values['groups_id']) && $values['groups_id'] == 0
                 || !isset($values['groups_id']))) {
               $comp        = new Computer();
               $entities_id = 0;
               if ($computers_id > 0 && $comp->getFromDB($computers_id)) {
                  $entities_id = $comp->fields["entities_id"];
               }
               $values['groups_id'] = self::getUserGroup($entities_id, $user_id, '`is_itemgroup`', true);
            }
         }
      }
   }
}
