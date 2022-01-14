<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2022 by the ocsinventoryng Development Team.

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
 *  OCS Administration Information management class
 */
class PluginOcsinventoryngOcsAdminInfosLink extends CommonDBTM {

   /**
    * @param $ID
    */
   function cleanForOcsServer($ID) {

      $temp = new self();
      $temp->deleteByCriteria(['plugin_ocsinventoryng_ocsservers_id' => $ID]);

   }

   /**
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param $glpi_column
    *
    * @return true
    */
   function getFromDBbyOcsServerIDAndGlpiColumn($plugin_ocsinventoryng_ocsservers_id, $glpi_column) {
      $table        = $this->getTable();
      $field_server = "`$table`.`plugin_ocsinventoryng_ocsservers_id`";
      $field_column = "`$table`.`glpi_column`";
      return $this->getFromDBByCrit([$field_server => $plugin_ocsinventoryng_ocsservers_id, $field_column => $glpi_column]);

   }

   /**
    * @param int $plugin_ocsinventoryng_ocsservers_id
    *
    * @return array
    */
   static function getAdministrativeInfosLockableFields($plugin_ocsinventoryng_ocsservers_id = 0) {

      if ($plugin_ocsinventoryng_ocsservers_id > 0) {

         $locks = [];
         $link  = new self();

         $link->getFromDBbyOcsServerIDAndGlpiColumn($plugin_ocsinventoryng_ocsservers_id, "networks_id");
         if (!empty($link->fields["ocs_column"])) {
            $locks["networks_id"] = __('Network');
         }
         $link->getFromDBbyOcsServerIDAndGlpiColumn($plugin_ocsinventoryng_ocsservers_id, "use_date");
         if (!empty($link->fields["ocs_column"])) {
            $locks["use_date"] = __('Startup date');
         }
         $link->getFromDBbyOcsServerIDAndGlpiColumn($plugin_ocsinventoryng_ocsservers_id, "otherserial");
         if (!empty($link->fields["ocs_column"])) {
            $locks["otherserial"] = __('Inventory number');
         }
         $link->getFromDBbyOcsServerIDAndGlpiColumn($plugin_ocsinventoryng_ocsservers_id, "contact_num");
         if (!empty($link->fields["ocs_column"])) {
            $locks["contact_num"] = __('Alternate username number');
         }
         $link->getFromDBbyOcsServerIDAndGlpiColumn($plugin_ocsinventoryng_ocsservers_id, "locations_id");
         if (!empty($link->fields["ocs_column"])) {
            $locks["locations_id"] = __('Location');
         }
         $link->getFromDBbyOcsServerIDAndGlpiColumn($plugin_ocsinventoryng_ocsservers_id, "groups_id");
         if (!empty($link->fields["ocs_column"])) {
            $locks["groups_id"] = __('Group');
         }
      } else {
         $locks = ["networks_id"  => __('Network'),
                   "use_date"     => __('Startup date'),
                   "otherserial"  => __('Inventory number'),
                   "contact_num"  => __('Alternate username number'),
                   "locations_id" => __('Location'),
                   "groups_id"    => __('Group')];
      }

      return $locks;

   }

   /**
    * @param $computers_id
    * @param $date
    * @param $computer_updates
    *
    * @return array
    */
   static function addInfocomsForComputer($computers_id, $date, $computer_updates, $history) {
      global $DB;

      $infocom  = new Infocom();
      $use_date = substr($date, 0, 10);

      if ($infocom->getFromDBByCrit(['items_id' => $computers_id, 'itemtype' => 'Computer'])) {
         if (empty($infocom->fields['use_date'])
             || $infocom->fields['use_date'] == 'NULL') {
            //add use_date
            $infocom->update(['id'       => $infocom->fields['id'],
                              'use_date' => $use_date], $history);
         }
      } else {
         //add infocom
         $infocom->add(['items_id' => $computers_id,
                        'itemtype' => 'Computer',
                        'use_date' => $use_date], [], $history);

      }

      //Add lock
      $ocslink = new PluginOcsinventoryngOcslink();
      if ($ocslink->getFromDBforComputer($computers_id)) {
         $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($ocslink->fields["plugin_ocsinventoryng_ocsservers_id"]);
         if ($cfg_ocs["use_locks"]) {
            $computer_updates[] = "use_date";
            $dbu                = new DbUtils();
            $query              = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                         SET `computer_update` = '" . addslashes($dbu->exportArrayToDB($computer_updates)) . "'
                         WHERE `computers_id` = $computers_id";
            $DB->query($query);
         }
      }
      return $computer_updates;

   }

   /**
    * @param $ID
    * @param $table
    *
    * @return array
    */
   static function getColumnListFromAccountInfoTable($ID, $table) {

      $listColumn = ["0" => __('No import')];
      if ($ID != -1) {
         if (PluginOcsinventoryngOcsServer::checkOCSconnection($ID)) {
            $ocsClient          = PluginOcsinventoryngOcsServer::getDBocs($ID);
            $AccountInfoColumns = $ocsClient->getAccountInfoColumns($table);
            if (count($AccountInfoColumns) > 0) {
               foreach ($AccountInfoColumns as $id => $name) {
                  $listColumn[$id] = $name;
               }
            }
         }
      }
      return $listColumn;
   }

   /**
    * Update Admin Info retrieve config
    *
    * @param $tab data array
    * */
   function updateAdminInfo($tab) {
      if (isset($tab["import_location"])
          || isset($tab["import_otherserial"])
          || isset($tab["import_group"])
          || isset($tab["import_network"])
          || isset($tab["import_contact_num"])
          || isset($tab["import_use_date"])) {

         $this->cleanForOcsServer($tab["id"]);

         if (isset($tab["import_location"])) {
            if ($tab["import_location"] != "") {
               $input["plugin_ocsinventoryng_ocsservers_id"] = $tab["id"];
               $input["glpi_column"]                         = "locations_id";
               $input["ocs_column"]                          = $tab["import_location"];
               $this->add($input);
            }
         }

         if (isset($tab["import_otherserial"])) {
            if ($tab["import_otherserial"] != "") {
               $input["plugin_ocsinventoryng_ocsservers_id"] = $tab["id"];
               $input["glpi_column"]                         = "otherserial";
               $input["ocs_column"]                          = $tab["import_otherserial"];
               $this->add($input);
            }
         }

         if (isset($tab["import_group"])) {
            if ($tab["import_group"] != "") {
               $input["plugin_ocsinventoryng_ocsservers_id"] = $tab["id"];
               $input["glpi_column"]                         = "groups_id";
               $input["ocs_column"]                          = $tab["import_group"];
               $this->add($input);
            }
         }

         if (isset($tab["import_network"])) {
            if ($tab["import_network"] != "") {
               $input["plugin_ocsinventoryng_ocsservers_id"] = $tab["id"];
               $input["glpi_column"]                         = "networks_id";
               $input["ocs_column"]                          = $tab["import_network"];
               $this->add($input);
            }
         }

         if (isset($tab["import_contact_num"])) {
            if ($tab["import_contact_num"] != "") {
               $input["plugin_ocsinventoryng_ocsservers_id"] = $tab["id"];
               $input["glpi_column"]                         = "contact_num";
               $input["ocs_column"]                          = $tab["import_contact_num"];
               $this->add($input);
            }
         }

         if (isset($tab["import_use_date"])) {
            if ($tab["import_use_date"] != "") {
               $input["plugin_ocsinventoryng_ocsservers_id"] = $tab["id"];
               $input["glpi_column"]                         = "use_date";
               $input["ocs_column"]                          = $tab["import_use_date"];
               $this->add($input);
            }
         }
      }
   }

   /**
    * Update the administrative informations
    *
    * This function erase old data and import the new ones about administrative informations
    *
    * @param $params
    *
    * @throws \GlpitestSQLError
    */
   static function updateAdministrativeInfo($params) {
      global $DB;

      $plugin_ocsinventoryng_ocsservers_id = $params['plugin_ocsinventoryng_ocsservers_id'];
      $ocsid                               = $params['ocs_id'];
      $cfg_ocs                             = $params['cfg_ocs'];
      $computer_updates                    = $params['computers_updates'];
      $entities_id                         = $params['entities_id'];
      $computers_id                        = $params['computers_id'];

      $update_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && $cfg_ocs['history_admininfos'] == 1) {
         $update_history = 1;
      }

      //      PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      //check link between ocs and glpi column
      $queryListUpdate = "SELECT `ocs_column`, `glpi_column`
                          FROM `glpi_plugin_ocsinventoryng_ocsadmininfoslinks`
                          WHERE `plugin_ocsinventoryng_ocsservers_id` = $plugin_ocsinventoryng_ocsservers_id";
      $result          = $DB->query($queryListUpdate);

      if ($DB->numrows($result) > 0) {

         if (isset($params["ACCOUNTINFO"])) {

            $accountinfos = $params["ACCOUNTINFO"];

            foreach ($accountinfos as $key => $accountinfo) {
               $comp = new Computer();
               //update data
               while ($links_glpi_ocs = $DB->fetchArray($result)) {
                  //get info from ocs
                  $ocs_column  = $links_glpi_ocs['ocs_column'];
                  $glpi_column = $links_glpi_ocs['glpi_column'];

                  if ($computer_updates
                      && array_key_exists($ocs_column, $accountinfo)
                      && !in_array($glpi_column, $computer_updates)) {

                     if (isset($accountinfo[$ocs_column])) {
                        $var = addslashes($accountinfo[$ocs_column]);
                     } else {
                        $var = "";
                     }
                     switch ($glpi_column) {
                        case "groups_id":
                           $var = self::importGroup($var, $entities_id);
                           break;

                        case "locations_id":
                           $var = Dropdown::importExternal("Location", $var, $entities_id);
                           break;

                        case "networks_id":
                           $var = Dropdown::importExternal("Network", $var);
                           break;
                     }

                     $input                = [];
                     $input[$glpi_column]  = $var;
                     $input["id"]          = $computers_id;
                     $input["entities_id"] = $entities_id;
                     $input["_nolock"]     = true;

                     $comp->update($input, $update_history);
                  }

                  if ($computer_updates
                      && $ocs_column == 'ASSETTAG'
                      && !in_array($glpi_column, $computer_updates)) {

                     $options  = [
                        "DISPLAY" => [
                           "CHECKSUM" => PluginOcsinventoryngOcsClient::CHECKSUM_BIOS,
                           "WANTED"   => PluginOcsinventoryngOcsClient::WANTED_ACCOUNTINFO,
                        ]
                     ];
                     $computer = $ocsClient->getComputer($ocsid, $options);

                     $var = $computer["BIOS"]["ASSETTAG"];
                     if (isset($computer["BIOS"]["ASSETTAG"])
                         && !empty($computer["BIOS"]["ASSETTAG"])) {
                        $input                = [];
                        $input[$glpi_column]  = $var;
                        $input["id"]          = $computers_id;
                        $input["entities_id"] = $entities_id;
                        $input["_nolock"]     = true;

                        $comp->update($input, $update_history);
                     }
                  }
               }
            }
         }
      }
   }

   /**
    * Import a group from OCS table.
    *
    * @param $value string : Value of the new dropdown.
    * @param $entities_id int : entity in case of specific dropdown
    *
    * @return integer : dropdown id.
    * */
   static function importGroup($value, $entities_id) {
      global $DB;

      if (empty($value)) {
         return 0;
      }

      $query = "SELECT `id`
                 FROM `glpi_groups`
                 WHERE `name` = '$value' ";
      $dbu   = new DbUtils();
      $query .= $dbu->getEntitiesRestrictRequest(' AND ', 'glpi_groups', '',
                                                 $entities_id, true);

      $result = $DB->query($query);

      if ($DB->numrows($result) == 0) {
         $group                = new Group();
         $input["name"]        = $value;
         $input["entities_id"] = $entities_id;
         return $group->add($input);
      }
      $line = $DB->fetchArray($result);
      return $line["id"];
   }

   /**
    * Update the administrative informations
    *
    * This function erase old data and import the new ones about administrative informations
    *
    * @param $computers_id integer : glpi computer id.
    * @param $plugin_ocsinventoryng_ocsservers_id integer : ocs server id
    * @param $computer_updates array : already updated fields of the computer
    * @param $ocsComputer
    *
    * @return void .
    * @internal param int $ocsid : ocs computer id (ID).
    * @internal param array $cfg_ocs : configuration ocs of the server
    * @internal param int $entity : entity of the computer
    * @internal param bool $dohistory : log changes?
    */
   static function updateAdministrativeInfoUseDate($computers_id, $plugin_ocsinventoryng_ocsservers_id,
                                                   &$computer_updates, $ocsComputer, $cfg_ocs) {

      $update_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && $cfg_ocs['history_admininfos'] == 1) {
         $update_history = 1;
      }

      $ocsAdminInfosLink = new PluginOcsinventoryngOcsAdminInfosLink();
      if ($ocsAdminInfosLink->getFromDBByCrit(['plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                                               'glpi_column'                         => 'use_date'])) {

         $ocs_column = $ocsAdminInfosLink->getField('ocs_column');
         if ($computer_updates
             && array_key_exists($ocs_column, $ocsComputer)
             && !in_array('use_date', $computer_updates)) {
            if (isset($ocsComputer[$ocs_column])) {
               $var = addslashes($ocsComputer[$ocs_column]);
            } else {
               $var = "";
            }
            $date             = str_replace($ocsComputer['NAME'] . "-", "", $var);
            $computer_updates = self::addInfocomsForComputer($computers_id,
                                                             $date,
                                                             $computer_updates,
                                                             $update_history);
         }
      }
   }
}
