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

/// OCS config class

/**
 * Class PluginOcsinventoryngOcsProcess
 */
class PluginOcsinventoryngOcsProcess extends CommonDBTM {

   static $rightname = "plugin_ocsinventoryng";
   // From CommonDBTM
   public $dohistory = true;

   // Class constants - OCSNG Flags on Checksum
   // See Apache/Ocsinventory/Map.pm
   const HARDWARE_FL        = 0;//1
   const BIOS_FL            = 1;//2
   const MEMORIES_FL        = 2;//4
   const SLOTS_FL           = 3;//8
   const REGISTRY_FL        = 4;//16
   const CONTROLLERS_FL     = 5;//32
   const MONITORS_FL        = 6;//64
   const PORTS_FL           = 7;//128
   const STORAGES_FL        = 8;//256
   const DRIVES_FL          = 9;//512
   const INPUTS_FL          = 10;//1024
   const MODEMS_FL          = 11;//2048
   const NETWORKS_FL        = 12;//4096
   const PRINTERS_FL        = 13;//8192
   const SOUNDS_FL          = 14;//16384
   const VIDEOS_FL          = 15;//32768
   const SOFTWARES_FL       = 16;//65536
   const VIRTUALMACHINES_FL = 17; //131072
   const CPUS_FL            = 18; //added into OCS2_1_VERSION_LIMIT - 262144

   const SIMS_FL      = 19; //not used added into OCS2_1_VERSION_LIMIT - 524288
   const BATTERIES_FL = 20; //not used  added into OCS2_2_VERSION_LIMIT - 1048576

   const MAX_CHECKSUM = 524287;//262143;//With < 19 (with 20 : 2097151)

   const FIELD_SEPARATOR = '$$$$$';
   const IMPORT_TAG_078  = '_version_078_';

   // Class constants - Update result
   const COMPUTER_IMPORTED      = 0; //Computer is imported in GLPI
   const COMPUTER_SYNCHRONIZED  = 1; //Computer is synchronized
   const COMPUTER_LINKED        = 2; //Computer is linked to another computer already in GLPI
   const COMPUTER_FAILED_IMPORT = 3; //Computer cannot be imported because it matches none of the rules
   const COMPUTER_NOTUPDATED    = 4; //Computer should not be updated, nothing to do
   const COMPUTER_NOT_UNIQUE    = 5; //Computer import is refused because it's not unique
   const COMPUTER_LINK_REFUSED  = 6; //Computer cannot be imported because a rule denies its import
   const LINK_RESULT_IMPORT     = 0;
   const LINK_RESULT_NO_IMPORT  = 1;
   const LINK_RESULT_LINK       = 2;

   // Class constants - Update result
   const SNMP_IMPORTED            = 10; //SNMP Object is imported in GLPI
   const SNMP_SYNCHRONIZED        = 11; //SNMP is synchronized
   const SNMP_LINKED              = 12; //SNMP is linked to another object already in GLPI
   const SNMP_FAILED_IMPORT       = 13; //SNMP cannot be imported - no itemtype
   const SNMP_NOTUPDATED          = 14; //SNMP should not be updated, nothing to do
   const IPDISCOVER_IMPORTED      = 15; //IPDISCOVER Object is imported in GLPI
   const IPDISCOVER_NOTUPDATED    = 16; //IPDISCOVER should not be updated, nothing to do
   const IPDISCOVER_FAILED_IMPORT = 17; //IPDISCOVER cannot be imported - no itemtype
   const IPDISCOVER_SYNCHRONIZED  = 18; //IPDISCOVER is synchronized

   /**
    *
    * Encode data coming from OCS DB in utf8 is needed
    *
    * @param boolean $is_ocsdb_utf8 is OCS database declared as utf8 in GLPI configuration
    * @param string  $value value to encode in utf8
    *
    * @return string value encoded in utf8
    * @since 1.0
    *
    */
   static function encodeOcsDataInUtf8($is_ocsdb_utf8, $value) {
      if (!$is_ocsdb_utf8 && !Toolbox::seems_utf8($value)) {
         return Toolbox::encodeInUtf8($value);
      } else {
         return $value;
      }
   }


   /**
    * @param      $plugin_ocsinventoryng_ocsservers_id
    * @param bool $redirect
    *
    * @return bool
    * @throws \GlpitestSQLError
    */
   static function manageDeleted($plugin_ocsinventoryng_ocsservers_id, $redirect = true) {
      global $DB;

      if (!(PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)
            && PluginOcsinventoryngOcsServer::checkVersion($plugin_ocsinventoryng_ocsservers_id))) {
         return false;
      }

      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $deleted   = $ocsClient->getDeletedComputers();

      //if (strpos($_SERVER['PHP_SELF'], "deleted_equiv.php") == true){
      if (count($deleted)) {

         foreach ($deleted as $del => $equiv) {
            if (!empty($equiv) && $equiv != "NULL") { // New name ($equiv = VARCHAR)
               // Get hardware due to bug of duplicates management of OCS
               if (strpos($equiv, "-") !== false) {
                  $res = $ocsClient->searchComputers('DEVICEID', $equiv);
                  if (isset($res['COMPUTERS']) && count($res['COMPUTERS'])) {
                     if (isset($res['COMPUTERS']['META']) && is_array(isset($res['COMPUTERS']['META']))) {
                        $data  = end($res['COMPUTERS']['META']);
                        $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                                        SET `ocsid` = '" . $data["ID"] . "',
                                            `ocs_deviceid` = '" . $data["DEVICEID"] . "'
                                        WHERE `ocs_deviceid` = '$del'
                                              AND `plugin_ocsinventoryng_ocsservers_id`
                                                      = $plugin_ocsinventoryng_ocsservers_id";
                        $DB->query($query);
                        $ocsClient->setChecksum($data['CHECKSUM'] | PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE, $data['ID']);
                     }
                     // } else {
                     // We're damned ! no way to find new ID
                     // TODO : delete ocslinks ?
                  }
               } else {
                  $res = $ocsClient->searchComputers('ID', $equiv);
                  if (isset($res['COMPUTERS']) && count($res['COMPUTERS'])) {

                     $data  = $res['COMPUTERS'][$equiv]['META'];
                     $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                                     SET `ocsid` = '" . $data["ID"] . "',
                                         `ocs_deviceid` = '" . $data["DEVICEID"] . "'
                                     WHERE `ocsid` = '$del'
                                           AND `plugin_ocsinventoryng_ocsservers_id`
                                                   = $plugin_ocsinventoryng_ocsservers_id";
                     $DB->query($query);
                     $ocsClient->setChecksum($data['CHECKSUM'] | PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE, $data['ID']);
                  } else {
                     // Not found, probably because ID change twice since previous sync
                     // No way to found new DEVICEID
                     $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                                     SET `ocsid` = '$equiv'
                                     WHERE `ocsid` = '$del'
                                           AND `plugin_ocsinventoryng_ocsservers_id` = $plugin_ocsinventoryng_ocsservers_id";
                     $DB->query($query);
                     // for history, see below
                     $data = ['ID' => $equiv];
                  }
               }
               //foreach($ocs_deviceid as $deviceid => $equiv){
               //$query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
               //SET `ocsid` = '$equiv'
               //WHERE `ocs_deviceid` = '$del'
               //AND `plugin_ocsinventoryng_ocsservers_id` = '$plugin_ocsinventoryng_ocsservers_id'";
               //}
               //TODO
               //http://www.karlrixon.co.uk/writing/update-multiple-rows-with-different-values-and-a-single-sql-query/
               if (isset($data)) {
                  $sql_id = "SELECT `computers_id`
                             FROM `glpi_plugin_ocsinventoryng_ocslinks`
                             WHERE `ocsid` = " . $data["ID"] . "
                                   AND `plugin_ocsinventoryng_ocsservers_id`
                                          = $plugin_ocsinventoryng_ocsservers_id";
                  if ($res_id = $DB->query($sql_id)) {
                     if ($DB->numrows($res_id) > 0) {
                        //Add history to indicates that the ocsid changed
                        $changes[0] = '0';
                        //Old ocsid
                        $changes[1] = $del;
                        //New ocsid
                        $changes[2] = $data["ID"];
                        PluginOcsinventoryngOcslink::history($DB->result($res_id, 0, "computers_id"),
                                                             $changes,
                                                             PluginOcsinventoryngOcslink::HISTORY_OCS_IDCHANGED);
                     }
                  }
               }
            } else { // Deleted
               $ocslinks_toclean = [];
               if (strstr($del, "-")) {
                  $link = "ocs_deviceid";
               } else {
                  $link = "ocsid";
               }
               $query = "SELECT *
                               FROM `glpi_plugin_ocsinventoryng_ocslinks`
                               WHERE `" . $link . "` = '$del'
                                     AND `plugin_ocsinventoryng_ocsservers_id`
                                                = $plugin_ocsinventoryng_ocsservers_id";

               if ($result = $DB->query($query)) {
                  if ($DB->numrows($result) > 0) {
                     $data                          = $DB->fetchArray($result);
                     $ocslinks_toclean[$data['id']] = $data['id'];
                  }
               }
               PluginOcsinventoryngOcslink::cleanLinksFromList($plugin_ocsinventoryng_ocsservers_id, $ocslinks_toclean);
            }
            //Delete from deleted_equiv
            if (!empty($equiv)) {
               $ocsClient->removeDeletedComputers($del, $equiv);
            } else {
               $to_del[] = $del;
            }
         }
         //Delete from deleted_equiv
         if (!empty($to_del)) {
            $ocsClient->removeDeletedComputers($to_del);
         }
         //If cron, no redirect
         if ($redirect) {
            if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != "") {
               $redirection = $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING'];
            } else {
               $redirection = $_SERVER['PHP_SELF'];
            }
            Html::redirect($redirection);
         }
      } else {
         $_SESSION['ocs_deleted_equiv']['computers_to_del']  = false;
         $_SESSION['ocs_deleted_equiv']['computers_deleted'] = 0;
      }
      // New way to delete entry from deleted_equiv table
      //} else if (count($deleted)) {
      //   $message = sprintf(__('Please consider cleaning the deleted computers in OCSNG <a href="%s">Clean OCSNG datatabase </a>', 'ocsinventoryng'), $CFG_GLPI['root_doc']."/plugins/ocsinventoryng/front/deleted_equiv.php");
      //   echo "<tr><th colspan='2'>";
      //   Html::displayTitle($CFG_GLPI['root_doc']."/pics/warning.png", $message, $message);
      //   echo "</th></tr>";
      //}
   }


   /**
    * Return field matching between OCS and GLPI
    *
    * @return array of glpifield => ocsfield
    * */
   static function getOcsFieldsMatching() {

      // Manufacturer and Model both as text (for rules) and as id (for import)
      return ['manufacturer'                    => ['BIOS', 'SMANUFACTURER'],
              'manufacturers_id'                => ['BIOS', 'SMANUFACTURER'],
              'license_number'                  => ['HARDWARE', 'WINPRODKEY'],
              'licenseid'                       => ['HARDWARE', 'WINPRODID'],
              'operatingsystems_id'             => ['HARDWARE', 'OSNAME'],
              'operatingsystemversions_id'      => ['HARDWARE', 'OSVERSION'],
              'operatingsystemarchitectures_id' => ['HARDWARE', 'ARCH'],
              'operatingsystemservicepacks_id'  => ['HARDWARE', 'OSCOMMENTS'],
              'domains_id'                      => ['HARDWARE', 'WORKGROUP'],
              'contact'                         => ['HARDWARE', 'USERID'],
              'name'                            => ['META', 'NAME'],
              'comment'                         => ['HARDWARE', 'DESCRIPTION'],
              'serial'                          => ['BIOS', 'SSN'],
              'model'                           => ['BIOS', 'SMODEL'],
              'computermodels_id'               => ['BIOS', 'SMODEL'],
              'TAG'                             => ['ACCOUNTINFO', 'TAG']
      ];
   }

   /**
    * @param array $ocs_fields
    * @param       $cfg_ocs
    * @param       $entities_id
    * @param int   $is_recursive
    *
    * @param int   $groups_id
    *
    * @return array
    */
   static function getComputerInformations($ocs_fields, $cfg_ocs, $entities_id,
                                           $groups_id = 0, $is_recursive = 0) {
      $input               = [];
      $input["is_dynamic"] = 1;
      //for rule asset
      $input['_auto'] = 1;

      $input["entities_id"] = $entities_id;

      if ($groups_id) {
         $input["groups_id"] = $groups_id;
      }

      if ($is_recursive) {
         $input["is_recursive"] = $is_recursive;
      }

      $input['ocsid']      = $ocs_fields['META']['ID'];
      $ocs_fields_matching = self::getOcsFieldsMatching();
      foreach ($ocs_fields_matching as $glpi_field => $ocs_field) {
         $ocs_section = $ocs_field[0];
         $ocs_field   = $ocs_field[1];
         $dbu         = new DbUtils();
         $table       = $dbu->getTableNameForForeignKeyField($glpi_field);

         $ocs_val = null;
         if (isset($ocs_fields[$ocs_section]) && is_array($ocs_fields[$ocs_section])) {
            if (array_key_exists($ocs_field, $ocs_fields[$ocs_section])) {
               $ocs_val = $ocs_fields[$ocs_section][$ocs_field];
            } else if (isset($ocs_fields[$ocs_section][0])
                       && array_key_exists($ocs_field, $ocs_fields[$ocs_section][0])) {
               $ocs_val = $ocs_fields[$ocs_section][0][$ocs_field];
            }
         }
         if (!is_null($ocs_val)) {
            //            $ocs_field = Toolbox::encodeInUtf8($ocs_field);

            //Field is a foreing key
            if ($table != '') {
               if (!($item = $dbu->getItemForItemtype($table))) {
                  continue;
               }
               $itemtype        = $dbu->getItemTypeForTable($table);
               $external_params = [];

               foreach ($item->additional_fields_for_dictionnary as $field) {
                  $additional_ocs_section = $ocs_fields_matching[$field][0];
                  $additional_ocs_field   = $ocs_fields_matching[$field][1];

                  if (isset($ocs_fields[$additional_ocs_section][$additional_ocs_field])) {
                     $external_params[$field] = $ocs_fields[$additional_ocs_section][$additional_ocs_field];
                  } else if (isset($ocs_fields[$additional_ocs_section][0][$additional_ocs_field])) {
                     $external_params[$field] = $ocs_fields[$additional_ocs_section][0][$additional_ocs_field];
                  } else {
                     $external_params[$field] = "";
                  }
               }

               $input[$glpi_field] = Dropdown::importExternal($itemtype, $ocs_val, $entities_id, $external_params);
            } else {
               switch ($glpi_field) {
                  case 'contact' :
                     if ($users_id = User::getIDByField('name', $ocs_val)) {
                        $input[$glpi_field] = $users_id;
                     }
                     break;

                  case 'comment' :
                     $input[$glpi_field] = '';
                     if ($ocs_val && $ocs_val != NOT_AVAILABLE) {
                        $input[$glpi_field] .= $ocs_val . "\r\n";
                     }
                     $input[$glpi_field] .= addslashes(sprintf(__('%1$s %2$s'), $input[$glpi_field], sprintf(__('%1$s: %2$s'), __('Swap', 'ocsinventoryng'), $ocs_fields['HARDWARE']['SWAP'])));
                     break;

                  default :
                     $input[$glpi_field] = $ocs_val;
                     break;
               }
            }
         }
      }
      if (intval($cfg_ocs["import_general_name"]) == 0) {
         unset($input["name"]);
      }

      if (intval($cfg_ocs["import_general_os"]) == 0) {
         unset($input["operatingsystems_id"]);
         unset($input["operatingsystemversions_id"]);
         unset($input["operatingsystemservicepacks_id"]);
         unset($input["operatingsystemarchitectures_id"]);
      }

      if (intval($cfg_ocs["import_os_serial"]) == 0) {
         unset($input["license_number"]);
         unset($input["licenseid"]);
      }

      if (intval($cfg_ocs["import_general_serial"]) == 0) {
         unset($input["serial"]);
      }

      if (intval($cfg_ocs["import_general_model"]) == 0) {
         unset($input["model"]);
         unset($input["computermodels_id"]);
      }

      if (intval($cfg_ocs["import_general_manufacturer"]) == 0) {
         unset($input["manufacturer"]);
         unset($input["manufacturers_id"]);
      }

      if (intval($cfg_ocs["import_general_type"]) == 0) {
         unset($input["computertypes_id"]);
      }

      if (intval($cfg_ocs["import_general_comment"]) == 0) {
         unset($input["comment"]);
      }

      if (intval($cfg_ocs["import_general_contact"]) == 0) {
         unset($input["contact"]);
         unset($input["users_id"]);
      }

      if (intval($cfg_ocs["import_general_domain"]) == 0) {
         unset($input["domains_id"]);
      }

      return $input;
   }

   /**
    * @param $process_params
    *
    * @return array
    * @throws \GlpitestSQLError
    */
   static function processComputer($process_params) {
      global $DB;

      $ocsid                               = $process_params["ocsid"];
      $plugin_ocsinventoryng_ocsservers_id = $process_params["plugin_ocsinventoryng_ocsservers_id"];
      $lock                                = $process_params["lock"];
      $defaultentity                       = (isset($process_params["defaultentity"])) ? $process_params["defaultentity"] : -1;
      $defaultrecursive                    = (isset($process_params["defaultrecursive"])) ? $process_params["defaultrecursive"] : 0;
      $disable_unicity_check               = (isset($process_params["disable_unicity_check"])) ? $process_params["disable_unicity_check"] : false;

      $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);


      //Check it machine is already present AND was imported by OCS AND still present in GLPI
      $query  = "SELECT `glpi_plugin_ocsinventoryng_ocslinks`.`id`, `computers_id`, `ocsid`
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                LEFT JOIN `glpi_computers`
                     ON `glpi_computers`.`id`=`glpi_plugin_ocsinventoryng_ocslinks`.`computers_id`
                WHERE `glpi_computers`.`id` IS NOT NULL
                      AND `ocsid` = '$ocsid'
                      AND `plugin_ocsinventoryng_ocsservers_id` = $plugin_ocsinventoryng_ocsservers_id";
      $result = $DB->query($query);

      if ($DB->numrows($result)) {
         $datas = $DB->fetchArray($result);
         //Return code to indicates that the machine was synchronized
         //or only last inventory date changed
         $sync_params = ['ID'                                  => $datas["id"],
                         'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                         'cfg_ocs'                             => $cfg_ocs,
                         'force'                               => 0];
         return self::synchronizeComputer($sync_params);
      } else {
         $import_params = ['ocsid'                               => $ocsid,
                           'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                           'lock'                                => $lock,
                           'defaultentity'                       => $defaultentity,
                           'defaultrecursive'                    => $defaultrecursive,
                           'cfg_ocs'                             => $cfg_ocs,
                           'disable_unicity_check'               => $disable_unicity_check];


         return self::importComputer($import_params);
      }

   }


   /**
    * @param $import_params
    *
    * @return array
    * @throws \GlpitestSQLError
    */
   static function importComputer($import_params) {

      $ocsid                               = $import_params["ocsid"];
      $plugin_ocsinventoryng_ocsservers_id = $import_params["plugin_ocsinventoryng_ocsservers_id"];
      $lock                                = $import_params["lock"];
      $defaultentity                       = $import_params["defaultentity"];
      $defaultrecursive                    = $import_params["defaultrecursive"];
      $cfg_ocs                             = $import_params["cfg_ocs"];
      $disable_unicity_check               = $import_params["disable_unicity_check"];

      //      PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
      $comp    = new Computer();

      $rules_matched = [];
      $ocsClient     = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);

      $ocsComputer = $ocsClient->getComputer($ocsid, [
         'DISPLAY' => [
            'CHECKSUM' => PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE | PluginOcsinventoryngOcsClient::CHECKSUM_BIOS,
            'WANTED'   => PluginOcsinventoryngOcsClient::WANTED_ACCOUNTINFO
         ]
      ]);

      //No entity or location predefined, check rules
      if ($defaultentity == -1) {
         //Try to affect computer to an entity
         $rule = new RuleImportEntityCollection();
         $data = $rule->processAllRules(['ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                                         '_source'       => 'ocsinventoryng'], [], ['ocsid' => $ocsid]);

         if (isset($data['_ignore_import']) && $data['_ignore_import'] == 1) {
            //ELSE Return code to indicates that the machine was not imported because it doesn't matched rules
            return ['status'       => self::COMPUTER_LINK_REFUSED,
                    'rule_matched' => $data['_ruleid']];
         }
      } else {
         //An entity or a location has already been defined via the web interface
         $data['entities_id']  = $defaultentity;
         $data['is_recursive'] = $defaultrecursive;
      }
      //Try to match all the rules, return the first good one, or null if not rules matched
      if (isset($data['entities_id']) && $data['entities_id'] >= 0) {
         if ($lock) {
            while (!$fp = self::setEntityLock($data['entities_id'])) {
               sleep(1);
            }
         }

         //Store rule that matched
         if (isset($data['_ruleid'])) {
            $rules_matched['RuleImportEntity'] = $data['_ruleid'];
         }

         if (!is_null($ocsComputer)) {

            $computer = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($ocsComputer));

            $is_recursive = (isset($data['is_recursive']) ? $data['is_recursive'] : 0);
            $input        = self::getComputerInformations($computer, PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id),
                                                          $data['entities_id'], $is_recursive);

            PluginOcsinventoryngHardware::getFields($ocsComputer, $cfg_ocs, $input);

            //Check if machine could be linked with another one already in DB
            $rulelink = new RuleImportComputerCollection();

            $params           = ['entities_id' => $data['entities_id'],
                                 'plugin_ocsinventoryng_ocsservers_id'
                                               => $plugin_ocsinventoryng_ocsservers_id,
                                 'ocsid'       => $ocsid];
            $rulelink_results = $rulelink->processAllRules(Toolbox::stripslashes_deep($input), [], $params);

            //If at least one rule matched
            //else do import as usual
            if (isset($rulelink_results['action'])) {
               $rules_matched['RuleImportComputer'] = $rulelink_results['_ruleid'];

               switch ($rulelink_results['action']) {
                  case self::LINK_RESULT_NO_IMPORT :
                     return ['status'       => self::COMPUTER_LINK_REFUSED,
                             'entities_id'  => $data['entities_id'],
                             'rule_matched' => $rules_matched];

                  case self::LINK_RESULT_LINK :
                     if (is_array($rulelink_results['found_computers']) && count($rulelink_results['found_computers']) > 0) {

                        foreach ($rulelink_results['found_computers'] as $tmp => $computers_id) {
                           $link_params = ['ocsid'                               => $ocsid,
                                           'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                                           'computers_id'                        => $computers_id];
                           if (self::linkComputer($link_params)) {
                              return ['status'       => self::COMPUTER_LINKED,
                                      'entities_id'  => $data['entities_id'],
                                      'rule_matched' => $rules_matched,
                                      'computers_id' => $computers_id];
                           }
                        }
                        break;
                     }
               }
            }

            $ocsClient->setChecksum(PluginOcsinventoryngOcsClient::CHECKSUM_ALL, $ocsid);

            //ADD IF NOT LINKED
            //disable_unicity_check
            $opt['unicity_error_message'] = false;
            $opt['disable_unicity_check'] = $disable_unicity_check;
            $computers_id                 = $comp->add($input, $opt);
            if ($computers_id) {
               if ($cfg_ocs['dohistory'] == 1) {
                  $ocsid      = $computer['META']['ID'];
                  $changes[0] = '0';
                  $changes[1] = "";
                  $changes[2] = $ocsid;
                  PluginOcsinventoryngOcslink::history($computers_id, $changes, PluginOcsinventoryngOcslink::HISTORY_OCS_IMPORT);
               }

               $link_params = ['ocsid'                               => $computer['META']['ID'],
                               'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                               'computers_id'                        => $computers_id];

               if ($idlink = PluginOcsinventoryngOcslink::ocsLink($link_params)) {
                  $sync_params = ['ID'                                  => $idlink,
                                  'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                                  'cfg_ocs'                             => $cfg_ocs,
                                  'force'                               => 0];
                  self::synchronizeComputer($sync_params);
               }

               //Return code to indicates that the machine was imported
               // var_dump("post",$_POST,"session",$_SESSION,"server",$_SERVER,"get",$_GET,"cookie",$_COOKIE,"request",$_REQUEST);
               //die("lets see the things in post get session, cookie and reques");
               return ['status'       => self::COMPUTER_IMPORTED,
                       'entities_id'  => $data['entities_id'],
                       'rule_matched' => $rules_matched,
                       'computers_id' => $computers_id];
            }
            return ['status'       => self::COMPUTER_NOT_UNIQUE,
                    'entities_id'  => $data['entities_id'],
                    'rule_matched' => $rules_matched];
         }

         if ($lock) {
            self::removeEntityLock($data['entities_id'], $fp);
         }
      }
      //ELSE Return code to indicates that the machine was not imported because it doesn't matched rules
      return ['status'       => self::COMPUTER_FAILED_IMPORT,
              'rule_matched' => $rules_matched];
   }

   /**
    * @param $link_params
    *
    * @return bool
    * @throws \GlpitestSQLError
    */
   static function linkComputer($link_params) {
      global $DB, $CFG_GLPI;

      $ocsid                               = $link_params["ocsid"];
      $plugin_ocsinventoryng_ocsservers_id = $link_params["plugin_ocsinventoryng_ocsservers_id"];
      $computers_id                        = $link_params["computers_id"];

      //      PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $cfg_ocs   = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);

      $query = "SELECT *
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `computers_id` = $computers_id";

      $result          = $DB->query($query);
      $ocs_id_change   = false;
      $ocs_link_exists = false;
      $numrows         = $DB->numrows($result);

      // Already link - check if the OCS computer already exists
      if ($numrows > 0) {
         $ocs_link_exists = true;
         $data            = $DB->fetchAssoc($result);

         // Not found
         if (!$ocsClient->getIfOCSComputersExists($data['ocsid'])) {
            $idlink = $data["id"];
            $query  = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                       SET `ocsid` = $ocsid
                       WHERE `id` = " . $data["id"];

            if ($DB->query($query)) {
               if ($cfg_ocs['dohistory'] == 1) {
                  $ocs_id_change = true;
                  //Add history to indicates that the ocsid changed
                  $changes[0] = '0';
                  //Old ocsid
                  $changes[1] = $data["ocsid"];
                  //New ocsid
                  $changes[2] = $ocsid;
                  PluginOcsinventoryngOcslink::history($computers_id, $changes, PluginOcsinventoryngOcslink::HISTORY_OCS_IDCHANGED);
               }
            }
         }
      }
      $options     = [
         "DISPLAY" => [
            "CHECKSUM" => PluginOcsinventoryngOcsClient::CHECKSUM_BIOS | PluginOcsinventoryngOcsClient::CHECKSUM_NETWORK_ADAPTERS,
         ]
      ];
      $ocsComputer = $ocsClient->getComputer($ocsid, $options);

      $serial       = (isset($ocsComputer['BIOS']["SSN"])) ? $ocsComputer['BIOS']["SSN"] : "";
      $ssnblacklist = Blacklist::getSerialNumbers();
      if (in_array($serial, $ssnblacklist)) {
         Session::addMessageAfterRedirect(sprintf(__('Unable to link this computer, Serial number is blacklisted (%d)', 'ocsinventoryng'), $ocsid), false, ERROR);
         return false;
      }

      $uuid          = (isset($ocsComputer['META']["UUID"])) ? $ocsComputer['META']["UUID"] : "";
      $uuidblacklist = Blacklist::getUUIDs();
      if (in_array($uuid, $uuidblacklist)) {
         Session::addMessageAfterRedirect(sprintf(__('Unable to link this computer, UUID is blacklisted (%d)', 'ocsinventoryng'), $ocsid), false, ERROR);
         return false;
      }

      // No ocs_link or ocs id change does not exists so can link
      if ($ocs_id_change || !$ocs_link_exists) {
         // Set OCS checksum to max value
         $ocsClient->setChecksum(PluginOcsinventoryngOcsClient::CHECKSUM_ALL, $ocsid);

         if ($ocs_id_change
             || ($idlink = PluginOcsinventoryngOcslink::ocsLink($link_params))) {

            // automatic transfer computer
            if (($CFG_GLPI['transfers_id_auto'] > 0) && Session::isMultiEntitiesMode()) {

               // Retrieve data from glpi_plugin_ocsinventoryng_ocslinks
               $ocsLink = new PluginOcsinventoryngOcslink();
               $ocsLink->getFromDB($idlink);

               if (count($ocsLink->fields)) {
                  // Retrieve datas from OCS database
                  $ocsComputer = $ocsClient->getComputer($ocsLink->fields['ocsid']);

                  if (!is_null($ocsComputer)) {
                     //                     $ocsComputer = Toolbox::addslashes_deep($ocsComputer);
                     self::transferComputer($ocsLink->fields);
                  }
               }
            }

            $comp = new Computer();
            $comp->getFromDB($computers_id);
            $input["id"]          = $computers_id;
            $input["entities_id"] = $comp->fields['entities_id'];
            $input["is_dynamic"]  = 1;
            $input["_nolock"]     = true;
            //for rule asset
            $input['_auto'] = 1;

            $update_history       = 0;
            $input["_no_history"] = 1;
            if ($cfg_ocs['dohistory'] == 1 && $cfg_ocs['history_hardware'] == 1) {
               $update_history       = 1;
               $input["_no_history"] = 0;
            }

            $comp->update($input, $update_history);

            // Auto restore if deleted
            if ($comp->fields['is_deleted']) {
               $comp->restore(['id' => $computers_id]);
            }

            if ($ocs_id_change && $cfg_ocs['dohistory'] == 1) {
               $changes[0] = '0';
               $changes[1] = "";
               $changes[2] = $ocsid;
               PluginOcsinventoryngOcslink::history($computers_id, $changes, PluginOcsinventoryngOcslink::HISTORY_OCS_LINK);
            }
            $sync_params = ['ID'                                  => $idlink,
                            'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                            'cfg_ocs'                             => $cfg_ocs,
                            'force'                               => 1];
            //            self::synchronizeComputer($sync_params, false);
            return true;
         }
      } else {
         //TRANS: %s is the OCS id
         Session::addMessageAfterRedirect(sprintf(__('Unable to import, GLPI computer is already related to an element of OCSNG (%d)', 'ocsinventoryng'), $ocsid), false, ERROR);
      }
      return false;
   }


   /**
    * Do automatic transfer if option is enable
    *
    * @param $line_links array : data from glpi_plugin_ocsinventoryng_ocslinks table
    *
    * @return void
    * @internal param array $line_ocs : data from ocs tables
    */
   static function transferComputer($line_links) {
      global $CFG_GLPI;

      $ocsClient   = PluginOcsinventoryngOcsServer::getDBocs($line_links["plugin_ocsinventoryng_ocsservers_id"]);
      $cfg_ocs     = PluginOcsinventoryngOcsServer::getConfig($line_links["plugin_ocsinventoryng_ocsservers_id"]);
      $ocsComputer = $ocsClient->getComputer($line_links["ocsid"]);

      $values = [];
      PluginOcsinventoryngHardware::getFields($ocsComputer, $cfg_ocs, $values, $line_links['computers_id']);

      // Get all rules for the current plugin_ocsinventoryng_ocsservers_id
      $rule = new RuleImportEntityCollection();

      $data = $rule->processAllRules($values +
                                     ['ocsservers_id' => $line_links["plugin_ocsinventoryng_ocsservers_id"],
                                      '_source'       => 'ocsinventoryng'],
                                     $values,
                                     ['ocsid' => $line_links["ocsid"]]);

      // If entity is changing move items to the new entities_id
      if (isset($data['entities_id']) && $data['entities_id'] > -1 && $data['entities_id'] != $line_links['entities_id']) {

         if (!isCommandLine() && !Session::haveAccessToEntity($data['entities_id'])) {
            Html::displayRightError();
         }

         $transfer = new Transfer();
         $transfer->getFromDB($CFG_GLPI['transfers_id_auto']);

         $item_to_transfer = ["Computer" => [$line_links['computers_id']
                                             => $line_links['computers_id']]];

         $transfer->moveItems($item_to_transfer, $data['entities_id'], $transfer->fields);
      }

      //If location is update by a rule
      PluginOcsinventoryngHardware::updateComputerFields($line_links, $data, $cfg_ocs);
   }

   /** Update a ocs computer
    *
    * @param $sync_params
    *
    * @return array
    * @throws \GlpitestSQLError
    */
   static function synchronizeComputer($sync_params, $transfer = true) {
      global $DB, $CFG_GLPI;

      $ID                                  = $sync_params["ID"];
      $plugin_ocsinventoryng_ocsservers_id = $sync_params["plugin_ocsinventoryng_ocsservers_id"];
      $force                               = $sync_params["force"];
      $cfg_ocs                             = $sync_params["cfg_ocs"];

      $query  = "SELECT `ocsid`, `computers_id`, `plugin_ocsinventoryng_ocsservers_id`, `entities_id`, `computer_update`, `tag`
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `id` = $ID
                AND `plugin_ocsinventoryng_ocsservers_id` = $plugin_ocsinventoryng_ocsservers_id";
      $result = $DB->query($query);

      if ($DB->numrows($result) == 1) {
         $line = $DB->fetchAssoc($result);

         $comp = new Computer();
         $comp->getFromDB($line["computers_id"]);

         $ocsClient    = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
         $options      = [
            "DISPLAY" => [
               "CHECKSUM" => PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE,
               'PLUGINS'  => PluginOcsinventoryngOcsClient::PLUGINS_ALL
            ]
         ];
         $computer_ocs = $ocsClient->getComputer($line['ocsid'], $options);

         if (is_array($computer_ocs) && count($computer_ocs) > 0) {
            // automatic transfer computer
            if ($CFG_GLPI['transfers_id_auto'] > 0
                && Session::isMultiEntitiesMode()
                && $transfer == true) {
               self::transferComputer($line);

            } else {

               $values = [];
               PluginOcsinventoryngHardware::getFields($computer_ocs, $cfg_ocs, $values, $line['computers_id']);

               if (isset($comp->fields["is_recursive"])) {
                  $values['is_recursive'] = $comp->fields["is_recursive"];
               }

               $rule = new RuleImportEntityCollection();

               $data = $rule->processAllRules($values +
                                              ['ocsservers_id' => $line["plugin_ocsinventoryng_ocsservers_id"],
                                               '_source'       => 'ocsinventoryng'],
                                              $values,
                                              ['ocsid' => $line["ocsid"]]);

               PluginOcsinventoryngHardware::updateComputerFields($line, $data, $cfg_ocs);
            }

            // update last_update and and last_ocs_update
            $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                             SET `last_update` = '" . $_SESSION["glpi_currenttime"] . "',
                             `last_ocs_update` = '" . $computer_ocs["META"]["LASTDATE"] . "',
                             `ocs_agent_version` = '" . $computer_ocs["HARDWARE"]["USERAGENT"] . " ',
                             `last_ocs_conn` = '" . $computer_ocs["HARDWARE"]["LASTCOME"] . " ',
                             `ip_src` = '" . $computer_ocs["HARDWARE"]["IPSRC"] . " ',
                             `ocs_deviceid` = '" . $computer_ocs["HARDWARE"]["DEVICEID"] . "'
                             WHERE `id` = $ID";
            $DB->query($query);
            //Add  || $data_ocs["META"]["CHECKSUM"] > self::MAX_CHECKSUM for bug of checksum 18446744073689088230
            //            Toolbox::logWarning(intval($cfg_ocs["checksum"]));

            if ($force || $computer_ocs["META"]["CHECKSUM"] > self::MAX_CHECKSUM) {
               $ocs_checksum = self::MAX_CHECKSUM;
               PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id)->setChecksum($ocs_checksum, $line['ocsid']);
            } else {
               if (isset($computer_ocs['OFFICEPACK'])) {
                  $ocsClient->setChecksum($computer_ocs["META"]["CHECKSUM"] | PluginOcsinventoryngOcsClient::CHECKSUM_SOFTWARE, $line['ocsid']);
               }
               $ocs_checksum = $computer_ocs["META"]["CHECKSUM"];
            }
            $mixed_checksum = intval($ocs_checksum) & intval($cfg_ocs["checksum"]);

            $updates = ['bios'            => false,
                        'memories'        => false,
                        'storages'        => [],
                        'cpus'            => false,
                        'hardware'        => false,
                        'videos'          => false,
                        'sounds'          => false,
                        'networks'        => false,
                        'modems'          => false,
                        'ports'           => false,
                        'monitors'        => false,
                        'printers'        => false,
                        'inputs'          => false,
                        'softwares'       => false,
                        'drives'          => false,
                        'registry'        => false,
                        'antivirus'       => false,
                        'uptime'          => false,
                        'officepack'      => false,
                        'winupdatestate'  => false,
                        'osinstall'       => false,
                        'bitlocker'       => false,
                        'proxysetting'    => false,
                        'networkshare'    => false,
                        'service'         => false,
                        'runningprocess'  => false,
                        'winuser'         => false,
                        'teamviewer'      => false,
                        'customapp'       => false,
                        'virtualmachines' => false,
                        'mb'              => false,
                        'controllers'     => false,
                        'slots'           => false,
            ];

            if ($mixed_checksum) {

               $ocsCheck   = [];
               $ocsPlugins = [];
               if ($mixed_checksum & pow(2, self::HARDWARE_FL)) {
                  $updates['hardware'] = true;
                  $ocsCheck[]          = PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE;
               }
               if ($mixed_checksum & pow(2, self::BIOS_FL)) {
                  $updates['bios'] = true;
                  $ocsCheck[]      = PluginOcsinventoryngOcsClient::CHECKSUM_BIOS;
                  if ($cfg_ocs["import_device_motherboard"]
                      && $cfg_ocs['ocs_version'] >= PluginOcsinventoryngOcsServer::OCS2_2_VERSION_LIMIT) {
                     $updates['mb'] = true;
                  }
               }
               if ($mixed_checksum & pow(2, self::MEMORIES_FL)) {
                  if ($cfg_ocs["import_device_memory"]) {
                     $updates['memories'] = true;
                     $ocsCheck[]          = PluginOcsinventoryngOcsClient::CHECKSUM_MEMORY_SLOTS;
                  }
               }
               if ($mixed_checksum & pow(2, self::CONTROLLERS_FL)) {
                  if ($cfg_ocs["import_device_controller"]) {
                     $updates['controllers'] = true;
                     $ocsCheck[]             = PluginOcsinventoryngOcsClient::CHECKSUM_SYSTEM_CONTROLLERS;
                  }
               }
               if ($mixed_checksum & pow(2, self::SLOTS_FL)) {
                  if ($cfg_ocs["import_device_slot"]) {
                     $updates['slots'] = true;
                     $ocsCheck[]       = PluginOcsinventoryngOcsClient::CHECKSUM_SYSTEM_SLOTS;
                  }
               }
               if ($mixed_checksum & pow(2, self::STORAGES_FL)) {
                  if ($cfg_ocs["import_device_hdd"]) {
                     $updates['storages']["hdd"] = true;
                     $ocsCheck[]                 = PluginOcsinventoryngOcsClient::CHECKSUM_STORAGE_PERIPHERALS;
                  }
                  if ($cfg_ocs["import_device_drive"]) {
                     $updates['storages']["drive"] = true;
                     $ocsCheck[]                   = PluginOcsinventoryngOcsClient::CHECKSUM_STORAGE_PERIPHERALS;
                  }
               }
               if ($mixed_checksum & pow(2, self::CPUS_FL)) {
                  if ($cfg_ocs["import_device_processor"]
                      && !($cfg_ocs['ocs_version'] < PluginOcsinventoryngOcsServer::OCS2_1_VERSION_LIMIT)) {
                     $updates['cpus'] = true;
                     $ocsCheck[]      = PluginOcsinventoryngOcsClient::CHECKSUM_CPUS;
                  }
               }
               if ($mixed_checksum & pow(2, self::VIDEOS_FL)) {
                  if ($cfg_ocs["import_device_gfxcard"]) {
                     $updates['videos'] = true;
                     $ocsCheck[]        = PluginOcsinventoryngOcsClient::CHECKSUM_VIDEO_ADAPTERS;
                  }
               }
               if ($mixed_checksum & pow(2, self::SOUNDS_FL)) {
                  if ($cfg_ocs["import_device_sound"]) {
                     $updates['sounds'] = true;
                     $ocsCheck[]        = PluginOcsinventoryngOcsClient::CHECKSUM_SOUND_ADAPTERS;
                  }
               }
               if ($mixed_checksum & pow(2, self::NETWORKS_FL)) {
                  if ($cfg_ocs["import_device_iface"]
                      || $cfg_ocs["import_ip"]) {
                     $updates['networks'] = true;
                     $ocsCheck[]          = PluginOcsinventoryngOcsClient::CHECKSUM_NETWORK_ADAPTERS;
                  }
               }
               if ($mixed_checksum & pow(2, self::MODEMS_FL)
                   || $mixed_checksum & pow(2, self::PORTS_FL)
               ) {
                  if ($cfg_ocs["import_device_modem"]) {
                     $updates['modems'] = true;
                     $ocsCheck[]        = PluginOcsinventoryngOcsClient::CHECKSUM_MODEMS;
                  }
               }
               if ($mixed_checksum & pow(2, self::PORTS_FL)) {
                  if ($cfg_ocs["import_device_modem"]) {
                     $updates['ports'] = true;
                     $ocsCheck[]       = PluginOcsinventoryngOcsClient::CHECKSUM_MODEMS;
                  }
               }
               if ($mixed_checksum & pow(2, self::MONITORS_FL)) {
                  if ($cfg_ocs["import_monitor"]) {
                     $updates['monitors'] = true;
                     $ocsCheck[]          = PluginOcsinventoryngOcsClient::CHECKSUM_MONITORS;
                  }
               }
               if ($mixed_checksum & pow(2, self::PRINTERS_FL)) {
                  if ($cfg_ocs["import_printer"]) {
                     $updates['printers'] = true;
                     $ocsCheck[]          = PluginOcsinventoryngOcsClient::CHECKSUM_PRINTERS;
                  }
               }
               if ($mixed_checksum & pow(2, self::INPUTS_FL)) {
                  if ($cfg_ocs["import_periph"]) {
                     $updates['inputs'] = true;
                     $ocsCheck[]        = PluginOcsinventoryngOcsClient::CHECKSUM_INPUT_DEVICES;
                  }
               }
               if ($mixed_checksum & pow(2, self::SOFTWARES_FL)) {
                  if ($cfg_ocs["import_software"]) {
                     $updates['softwares'] = true;
                     $ocsCheck[]           = PluginOcsinventoryngOcsClient::CHECKSUM_SOFTWARE;
                     if ($cfg_ocs["use_soft_dict"]) {
                        $ocsWanted = PluginOcsinventoryngOcsClient::WANTED_DICO_SOFT;
                     }
                  }
               }
               if ($mixed_checksum & pow(2, self::DRIVES_FL)) {
                  if ($cfg_ocs["import_disk"]) {
                     $updates['drives'] = true;
                     $ocsCheck[]        = PluginOcsinventoryngOcsClient::CHECKSUM_LOGICAL_DRIVES;
                  }
               }
               if ($mixed_checksum & pow(2, self::REGISTRY_FL)) {
                  if ($cfg_ocs["import_registry"]) {
                     $updates['registry'] = true;
                     $ocsCheck[]          = PluginOcsinventoryngOcsClient::CHECKSUM_REGISTRY;
                  }
               }
               if ($mixed_checksum & pow(2, self::VIRTUALMACHINES_FL)) {
                  //no vm in ocs before 1.3
                  if (!($cfg_ocs['ocs_version'] < PluginOcsinventoryngOcsServer::OCS1_3_VERSION_LIMIT) && $cfg_ocs["import_vms"]) {
                     $updates['virtualmachines'] = true;
                     $ocsCheck[]                 = PluginOcsinventoryngOcsClient::CHECKSUM_VIRTUAL_MACHINES;
                  }
               }
               /********************* PLUGINS *********************/
               if ($cfg_ocs["import_antivirus"]) {
                  $updates['antivirus'] = true;
                  $ocsPlugins[]         = PluginOcsinventoryngOcsClient::PLUGINS_SECURITY;
               }
               if ($cfg_ocs["import_uptime"]) {
                  $updates['uptime'] = true;
                  $ocsPlugins[]      = PluginOcsinventoryngOcsClient::PLUGINS_UPTIME;
               }
               if ($cfg_ocs["import_software"] && $cfg_ocs["import_officepack"]) {
                  $updates['officepack'] = true;
                  $ocsPlugins[]          = PluginOcsinventoryngOcsClient::PLUGINS_OFFICE;
               }
               if ($cfg_ocs["import_winupdatestate"]) {
                  $updates['winupdatestate'] = true;
                  $ocsPlugins[]              = PluginOcsinventoryngOcsClient::PLUGINS_WUPDATE;
               }
               if ($cfg_ocs["import_teamviewer"]) {
                  $updates['teamviewer'] = true;
                  $ocsPlugins[]          = PluginOcsinventoryngOcsClient::PLUGINS_TEAMVIEWER;
               }
               if ($cfg_ocs["import_proxysetting"]) {
                  $updates['proxysetting'] = true;
                  $ocsPlugins[]            = PluginOcsinventoryngOcsClient::PLUGINS_PROXYSETTING;
               }
               if ($cfg_ocs["import_winusers"]) {
                  $updates['winuser'] = true;
                  $ocsPlugins[]       = PluginOcsinventoryngOcsClient::PLUGINS_WINUSERS;
               }
               if ($cfg_ocs["import_osinstall"]) {
                  $updates['osinstall'] = true;
                  $ocsPlugins[]         = PluginOcsinventoryngOcsClient::PLUGINS_OSINSTALL;
               }
               if ($cfg_ocs["import_bitlocker"]) {
                  $updates['bitlocker'] = true;
                  $ocsPlugins[]               = PluginOcsinventoryngOcsClient::PLUGINS_BITLOCKER;
               }
               if ($cfg_ocs["import_networkshare"]) {
                  $updates['networkshare'] = true;
                  $ocsPlugins[]            = PluginOcsinventoryngOcsClient::PLUGINS_NETWORKSHARE;
               }
               if ($cfg_ocs["import_service"]) {
                  $updates['service'] = true;
                  $ocsPlugins[]       = PluginOcsinventoryngOcsClient::PLUGINS_SERVICE;
               }
               if ($cfg_ocs["import_runningprocess"]) {
                  $updates['runningprocess'] = true;
                  $ocsPlugins[]              = PluginOcsinventoryngOcsClient::PLUGINS_RUNNINGPROCESS;
               }
               if ($cfg_ocs["import_customapp"]) {
                  $updates['customapp'] = true;
                  $ocsPlugins[]         = PluginOcsinventoryngOcsClient::PLUGINS_CUSTOMAPP;
               }
               /********************* PLUGINS *********************/

               if (count($ocsCheck) > 0) {
                  $ocsCheckResult = $ocsCheck[0];
                  foreach ($ocsCheck as $k => $ocsChecksum) {
                     $ocsCheckResult = $ocsCheckResult | $ocsChecksum;
                  }
               } else {
                  $ocsCheckResult = 0;
               }

               if (!isset($ocsWanted)) {
                  $ocsWanted = PluginOcsinventoryngOcsClient::WANTED_ACCOUNTINFO;
               }

               if (count($ocsPlugins) > 0) {
                  $ocsPluginsResult = $ocsPlugins[0];
                  foreach ($ocsPlugins as $plug) {
                     $ocsPluginsResult = $ocsPluginsResult | $plug;
                  }
               } else {
                  $ocsPluginsResult = 0;
               }

               $import_options = [
                  'DISPLAY' => [
                     'CHECKSUM' => $ocsCheckResult,
                     'WANTED'   => $ocsWanted,
                     'PLUGINS'  => $ocsPluginsResult
                  ],
               ];
               //launch process
               $ocsComputer = $ocsClient->getComputer($line['ocsid'], $import_options);

               // Get updates on computers
               $dbu              = new DbUtils();
               $computer_updates = $dbu->importArrayFromDB($line["computer_update"]);
               // Update Administrative informations
               $params = ['computers_id'                        => $line['computers_id'],
                          'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                          'cfg_ocs'                             => $cfg_ocs,
                          'computers_updates'                   => $computer_updates,
                          'ocs_id'                              => $line['ocsid'],
                          'entities_id'                         => $comp->fields['entities_id'],
                          'check_history'                       => true];

               if (isset($ocsComputer['ACCOUNTINFO'])) {
                  $params['ACCOUNTINFO'] = $ocsComputer['ACCOUNTINFO'];
                  PluginOcsinventoryngOcsAdminInfosLink::updateAdministrativeInfo($params);
               }
               if (isset($ocsComputer['HARDWARE'])) {
                  PluginOcsinventoryngOcsAdminInfosLink::updateAdministrativeInfoUseDate($line['computers_id'],
                                                                                         $plugin_ocsinventoryng_ocsservers_id,
                                                                                         $computer_updates,
                                                                                         $ocsComputer['HARDWARE'],
                                                                                         $cfg_ocs);
               }

               //Update TAG
               if (isset($ocsComputer['META'])) {
                  PluginOcsinventoryngOcslink::updateTag($line, $ocsComputer['META']);
               }

               $params['force'] = $force;

               if ($updates['hardware']) {
                  $params['HARDWARE'] = $ocsComputer['HARDWARE'];

                  $params['check_history'] = true;
                  if ($force) {
                     $params['check_history'] = false;
                  }

                  PluginOcsinventoryngHardware::updateComputerHardware($params);

                  PluginOcsinventoryngOS::updateComputerOS($params);
               }

               if ($updates['bios'] && isset($ocsComputer['BIOS'])) {
                  $params['BIOS'] = $ocsComputer['BIOS'];
                  PluginOcsinventoryngBios::updateComputerBios($params);
               }

               $params_devices = ['computers_id' => $line['computers_id'],
                                  'cfg_ocs'      => $cfg_ocs,
                                  'entities_id'  => $comp->fields['entities_id'],
                                  'force'        => $force];

               if ($updates['bios'] && isset($ocsComputer['BIOS'])) {
                  PluginOcsinventoryngDevice::updateDevices("Item_DeviceFirmware", $ocsComputer['BIOS'], $params_devices);
               }
               if ($updates['memories'] && isset($ocsComputer['MEMORIES'])) {
                  PluginOcsinventoryngDevice::updateDevices("Item_DeviceMemory", $ocsComputer['MEMORIES'], $params_devices);
               }

               if ($updates['storages'] && isset($ocsComputer['STORAGES'])) {
                  if (isset($updates['storages']["hdd"]) && $updates['storages']["hdd"]) {
                     PluginOcsinventoryngDevice::updateDevices("Item_DeviceHardDrive", $ocsComputer['STORAGES'], $params_devices);
                  }
                  if (isset($updates['storages']["drive"]) && $updates['storages']["drive"]) {
                     PluginOcsinventoryngDevice::updateDevices("Item_DeviceDrive", $ocsComputer['STORAGES'], $params_devices);
                  }
               }

               if ($updates['cpus'] && isset($ocsComputer['CPUS'])) {
                  PluginOcsinventoryngDevice::updateDevices("Item_DeviceProcessor", $ocsComputer['CPUS'], $params_devices);
               }
               if ($updates['videos'] && isset($ocsComputer['VIDEOS'])) {
                  PluginOcsinventoryngDevice::updateDevices("Item_DeviceGraphicCard", $ocsComputer['VIDEOS'], $params_devices);
               }

               if ($updates['mb'] && isset($ocsComputer['BIOS'])) {
                  PluginOcsinventoryngDevice::updateDevices("Item_DeviceMotherboard", $ocsComputer['BIOS'], $params_devices);
               }
               if ($updates['controllers'] && isset($ocsComputer['CONTROLLERS'])) {
                  PluginOcsinventoryngDevice::updateDevices("Item_DeviceControl", $ocsComputer['CONTROLLERS'], $params_devices);
               }
               if ($updates['sounds'] && isset($ocsComputer['SOUNDS'])) {
                  PluginOcsinventoryngDevice::updateDevices("Item_DeviceSoundCard", $ocsComputer['SOUNDS'], $params_devices);
               }
               if ($updates['networks'] && isset($ocsComputer['NETWORKS'])) {
                  PluginOcsinventoryngDevice::updateDevices("Item_DeviceNetworkCard", $ocsComputer['NETWORKS'], $params_devices);
               }
               if ($updates['modems'] && isset($ocsComputer['MODEMS'])) {
                  PluginOcsinventoryngDevice::updateDevices("Item_DevicePci", $ocsComputer['MODEMS'], $params_devices);
               }
               if ($updates['slots'] && isset($ocsComputer['SLOTS'])) {
                  PluginOcsinventoryngDevice::updateDevices("Item_DevicePci", $ocsComputer['SLOTS'], $params_devices);
               }
               if ($updates['ports'] && isset($ocsComputer['PORTS'])) {
                  PluginOcsinventoryngDevice::updateDevices("Item_DevicePci", $ocsComputer['PORTS'], $params_devices);
               }
               if ($updates['monitors'] && isset($ocsComputer["MONITORS"])) {
                  $monitor_params = ['entities_id'                         => $comp->fields["entities_id"],
                                     'cfg_ocs'                             => $cfg_ocs,
                                     'computers_id'                        => $line['computers_id'],
                                     'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                                     'datas'                               => $ocsComputer["MONITORS"],
                                     'force'                               => $force];
                  PluginOcsinventoryngMonitor::importMonitor($monitor_params);
               }
               if ($updates['printers'] && isset($ocsComputer["PRINTERS"])) {
                  $printer_params = ['entities_id'                         => $comp->fields["entities_id"],
                                     'cfg_ocs'                             => $cfg_ocs,
                                     'computers_id'                        => $line['computers_id'],
                                     'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                                     'datas'                               => $ocsComputer["PRINTERS"],
                                     'force'                               => $force];
                  PluginOcsinventoryngPrinter::importPrinter($printer_params);
               }
               if ($updates['inputs'] && isset($ocsComputer["INPUTS"])) {
                  $periph_params = ['entities_id'                         => $comp->fields["entities_id"],
                                    'cfg_ocs'                             => $cfg_ocs,
                                    'computers_id'                        => $line['computers_id'],
                                    'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                                    'datas'                               => $ocsComputer["INPUTS"],
                                    'force'                               => $force];
                  PluginOcsinventoryngPeripheral::importPeripheral($periph_params);
               }
               if ($updates['softwares'] && isset($ocsComputer["SOFTWARES"])) {
                  //import softwares
                  PluginOcsinventoryngSoftware::updateSoftware($cfg_ocs, $line['computers_id'], $ocsComputer["SOFTWARES"],
                                                               $comp->fields["entities_id"],
                                                               $updates['officepack'],
                     (isset($ocsComputer['OFFICEPACK']) ? $ocsComputer['OFFICEPACK'] : []), $force);
               }
               if ($updates['drives'] && isset($ocsComputer["DRIVES"])) {
                  //import drives
                  PluginOcsinventoryngDisk::updateDisk($line['computers_id'], $ocsComputer["DRIVES"],
                                                       $plugin_ocsinventoryng_ocsservers_id,
                                                       $updates['bitlocker'],
                     (isset($ocsComputer['BITLOCKERSTATUS']) ? $ocsComputer['BITLOCKERSTATUS'] : []),
                                                       $cfg_ocs, $force);
               }
               if ($updates['virtualmachines'] && isset($ocsComputer["VIRTUALMACHINES"])) {
                  //import vm
                  PluginOcsinventoryngVirtualmachine::updateVirtualMachine($line['computers_id'], $ocsComputer["VIRTUALMACHINES"],
                                                                           $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs, $force);
               }
               if ($updates['registry'] && isset($ocsComputer["REGISTRY"])) {
                  //import registry entries
                  PluginOcsinventoryngRegistryKey::updateRegistry($line['computers_id'], $ocsComputer["REGISTRY"],
                                                                  $cfg_ocs, 1);
               }
               /********************* PLUGINS *********************/
               if ($updates['antivirus'] && isset($ocsComputer["SECURITYCENTER"])) {
                  //import antivirus entries
                  PluginOcsinventoryngAntivirus::updateAntivirus($line['computers_id'], $ocsComputer["SECURITYCENTER"],
                                                                 $cfg_ocs, 1);
               }
               if ($updates['winupdatestate'] && isset($ocsComputer["WINUPDATESTATE"])) {
                  //import winupdatestate entries
                  PluginOcsinventoryngWinupdate::updateWinupdatestate($line['computers_id'], $ocsComputer["WINUPDATESTATE"],
                                                                      $cfg_ocs, 1);
               }
               if ($updates['osinstall'] && isset($ocsComputer["OSINSTALL"])) {
                  //import osinstall entries
                  PluginOcsinventoryngOsinstall::updateOSInstall($line['computers_id'], $ocsComputer["OSINSTALL"],
                                                                 $cfg_ocs, 1);
               }
               if ($updates['proxysetting'] && isset($ocsComputer["NAVIGATORPROXYSETTING"])) {
                  //import proxysetting entries
                  PluginOcsinventoryngProxysetting::updateProxysetting($line['computers_id'], $ocsComputer["NAVIGATORPROXYSETTING"],
                                                                       $cfg_ocs, 1);
               }
               if ($updates['networkshare'] && isset($ocsComputer["NETWORKSHARE"])) {
                  //import networkshare entries
                  PluginOcsinventoryngNetworkshare::updateNetworkshare($line['computers_id'], $ocsComputer["NETWORKSHARE"],
                                                                       $cfg_ocs, 1);
               }
               if ($updates['runningprocess'] && isset($ocsComputer["RUNNINGPROCESS"])) {
                  //import runningprocess entries
                  PluginOcsinventoryngRunningprocess::updateRunningprocess($line['computers_id'], $ocsComputer["RUNNINGPROCESS"],
                                                                           $cfg_ocs, 1);
               }
               if ($updates['service'] && isset($ocsComputer["SERVICE"])) {
                  //import service entries
                  PluginOcsinventoryngService::updateService($line['computers_id'], $ocsComputer["SERVICE"],
                                                             $cfg_ocs, 1);
               }
               if ($updates['winuser'] && isset($ocsComputer["WINUSERS"])) {
                  //import winusers entries
                  PluginOcsinventoryngWinuser::updateWinuser($line['computers_id'], $ocsComputer["WINUSERS"],
                                                             $cfg_ocs, 1);
               }
               if ($updates['teamviewer'] && isset($ocsComputer["TEAMVIEWER"])) {
                  //import teamviewer entries
                  PluginOcsinventoryngTeamviewer::updateTeamviewer($line['computers_id'], $ocsComputer["TEAMVIEWER"],
                                                                   $cfg_ocs, 1);
               }
               if ($updates['uptime'] && isset($ocsComputer["UPTIME"])) {
                  //import uptime
                  PluginOcsinventoryngUptime::updateUptime($ID, $ocsComputer["UPTIME"]);
               }
               if ($updates['customapp'] && isset($ocsComputer["CUSTOMAPP"])) {
                  //import teamviewer entries
                  PluginOcsinventoryngCustomapp::updateCustomapp($line['computers_id'], $ocsComputer["CUSTOMAPP"],
                                                                 $cfg_ocs, 1);
               }

               /********************* PLUGINS *********************/
            }
            unset($updates);

            //force drop old locks
            $locks = PluginOcsinventoryngOcslink::getLocksForComputer($line['computers_id']);
            PluginOcsinventoryngOcslink::mergeOcsArray($line['computers_id'], $locks);

            // Update OCS Cheksum
            $oldChecksum = $ocsClient->getChecksum($line['ocsid']);
            $newchecksum = $oldChecksum - $mixed_checksum;
            if (isset($computer_ocs['OFFICEPACK'])) {
               $ocsClient->setChecksum($newchecksum | PluginOcsinventoryngOcsClient::CHECKSUM_SOFTWARE, $line['ocsid']);
            } else {
               $ocsClient->setChecksum($newchecksum, $line['ocsid']);
            }

            //Return code to indicate that computer was synchronized
            return ['status'       => self::COMPUTER_SYNCHRONIZED,
                    'entities_id'  => $comp->fields["entities_id"],
                    'rule_matched' => [],
                    'computers_id' => $line['computers_id']];

         }
         // ELSE Return code to indicate only last inventory date changed
         return ['status'       => self::COMPUTER_NOTUPDATED,
                 'entities_id'  => $comp->fields["entities_id"],
                 'rule_matched' => [],
                 'computers_id' => $line['computers_id']];
      }
   }


   /**
    * Get IP address from OCS hardware table
    *
    * @param the $plugin_ocsinventoryng_ocsservers_id
    * @param ID  $computers_id
    * return the ip address or ''
    *
    * @return string
    * @internal param ID $computers_id of the computer in OCS hardware table
    *
    * @internal param the $plugin_ocsinventoryng_ocsservers_id ID of the OCS server
    */
   static function getGeneralIpAddress($plugin_ocsinventoryng_ocsservers_id, $computers_id) {
      if (!PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)) {
         return '';
      }
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $options   = [
         'DISPLAY' => [
            'CHECKSUM' => PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE
         ]
      ];
      $computer  = $ocsClient->getComputer($computers_id, $options);
      $ipaddress = $computer["HARDWARE"]["IPADDR"];
      if ($ipaddress) {
         return $ipaddress;
      }

      return '';
   }

   /**
    * @param $entity
    *
    * @return bool|resource
    */
   static function setEntityLock($entity) {

      $fp = fopen(GLPI_LOCK_DIR . "/lock_entity_" . $entity, "w+");
      if (flock($fp, LOCK_EX)) {
         return $fp;
      }
      fclose($fp);
      return false;
   }

   /**
    * @param $entity
    * @param $fp
    */
   static function removeEntityLock($entity, $fp) {

      flock($fp, LOCK_UN);
      fclose($fp);

      //Test if the lock file still exists before removing it
      // (sometimes another thread already removed the file)
      clearstatcache();
      if (file_exists(GLPI_LOCK_DIR . "/lock_entity_" . $entity)) {
         @unlink(GLPI_LOCK_DIR . "/lock_entity_" . $entity);
      }
   }




   /**
    * Delete old dropdown value
    *
    * Delete all old dropdown value of a computer.
    *
    * @param $glpi_computers_id integer : glpi computer id.
    * @param $field string : string of the computer table
    * @param $table string : dropdown table name
    *
    * @return nothing.
    *
    * static function resetDropdown($glpi_computers_id, $field, $table) {
    * global $DB;
    *
    * $query  = "SELECT `$field` AS val
    * FROM `glpi_computers`
    * WHERE `id` = $glpi_computers_id";
    * $result = $DB->query($query);
    *
    * if ($DB->numrows($result) == 1) {
    * $value  = $DB->result($result, 0, "val");
    * $query  = "SELECT COUNT(*) AS cpt
    * FROM `glpi_computers`
    * WHERE `$field` = '$value'";
    * $result = $DB->query($query);
    *
    * if ($DB->result($result, 0, "cpt") == 1) {
    * $query2 = "DELETE
    * FROM `$table`
    * WHERE `id` = $value";
    * $DB->query($query2);
    * }
    * }
    * }
    */


   /**
    * @param bool $snmp
    * @param bool $ipdiscover
    *
    * @return array
    */
   static function getAvailableStatistics($snmp = false, $ipdiscover = false) {

      $stats = ['imported_machines_number'     => __('Computers imported', 'ocsinventoryng'),
                'synchronized_machines_number' => __('Computers synchronized', 'ocsinventoryng'),
                'linked_machines_number'       => __('Computers linked', 'ocsinventoryng'),
                'notupdated_machines_number'   => __('Computers not updated', 'ocsinventoryng'),
                'failed_rules_machines_number' => __("Computers don't check any rule", 'ocsinventoryng'),
                'not_unique_machines_number'   => __('Duplicate computers', 'ocsinventoryng'),
                'link_refused_machines_number' => __('Computers whose import is refused by a rule', 'ocsinventoryng')];
      if ($snmp) {
         $stats = ['imported_snmp_number'        => __('SNMP objects imported', 'ocsinventoryng'),
                   'synchronized_snmp_number'    => __('SNMP objects synchronized', 'ocsinventoryng'),
                   'linked_snmp_number'          => __('SNMP objects linked', 'ocsinventoryng'),
                   'notupdated_snmp_number'      => __('SNMP objects not updated', 'ocsinventoryng'),
                   'failed_imported_snmp_number' => __("SNMP objects not imported", 'ocsinventoryng')];
      }
      if ($ipdiscover) {
         $stats = ['imported_ipdiscover_number'        => __('IPDISCOVER objects imported', 'ocsinventoryng'),
                   'synchronized_ipdiscover_number'    => __('IPDISCOVER objects synchronized', 'ocsinventoryng'),
                   'notupdated_ipdiscover_number'      => __('IPDISCOVER objects not updated', 'ocsinventoryng'),
                   'failed_imported_ipdiscover_number' => __("IPDISCOVER objects not imported", 'ocsinventoryng')];
      }

      return $stats;
   }

   /**
    * @param array $statistics
    * @param bool  $action
    * @param bool  $snmp
    * @param bool  $ipdiscover
    */
   static function manageImportStatistics(&$statistics = [], $action = false, $snmp = false, $ipdiscover = false) {

      if (empty($statistics)) {
         foreach (self::getAvailableStatistics($snmp, $ipdiscover) as $field => $label) {
            $statistics[$field] = 0;
         }
      }

      switch ($action) {
         case self::COMPUTER_SYNCHRONIZED:
            $statistics["synchronized_machines_number"]++;
            break;

         case self::COMPUTER_IMPORTED:
            $statistics["imported_machines_number"]++;
            break;

         case self::COMPUTER_FAILED_IMPORT:
            $statistics["failed_rules_machines_number"]++;
            break;

         case self::COMPUTER_LINKED:
            $statistics["linked_machines_number"]++;
            break;

         case self::COMPUTER_NOT_UNIQUE:
            $statistics["not_unique_machines_number"]++;
            break;

         case self::COMPUTER_NOTUPDATED:
            $statistics["notupdated_machines_number"]++;
            break;

         case self::COMPUTER_LINK_REFUSED:
            $statistics["link_refused_machines_number"]++;
            break;

         case self::SNMP_SYNCHRONIZED:
            $statistics["synchronized_snmp_number"]++;
            break;

         case self::SNMP_IMPORTED:
            $statistics["imported_snmp_number"]++;
            break;

         case self::SNMP_FAILED_IMPORT:
            $statistics["failed_imported_snmp_number"]++;
            break;

         case self::SNMP_LINKED:
            $statistics["linked_snmp_number"]++;
            break;

         case self::SNMP_NOTUPDATED:
            $statistics["notupdated_snmp_number"]++;
            break;

         case self::IPDISCOVER_IMPORTED:
            $statistics["imported_ipdiscover_number"]++;
            break;

         case self::IPDISCOVER_FAILED_IMPORT:
            $statistics["failed_imported_ipdiscover_number"]++;
            break;

         case self::IPDISCOVER_NOTUPDATED:
            $statistics["notupdated_ipdiscover_number"]++;
            break;

         case self::IPDISCOVER_SYNCHRONIZED:
            $statistics["synchronized_ipdiscover_number"]++;
            break;
      }
   }

   /**
    * @param array $statistics
    * @param bool  $finished
    * @param bool  $snmp
    * @param bool  $ipdiscover
    */
   static function showStatistics($statistics = [], $finished = false, $snmp = false, $ipdiscover = false) {

      echo "<div class='center b'>";
      echo "<table class='tab_cadre_fixe'>";
      if ($snmp) {
         echo "<th colspan='2'>" . __('Statistics of the OCSNG SNMP import', 'ocsinventoryng');
      } else if ($ipdiscover) {
         echo "<th colspan='2'>" . __('Statistics of the OCSNG IPDISCOVER import', 'ocsinventoryng');
      } else {
         echo "<th colspan='2'>" . __('Statistics of the OCSNG link', 'ocsinventoryng');
      }

      if ($finished) {
         echo "&nbsp;-&nbsp;";
         echo __('Task completed.');
      }
      echo "</th>";

      foreach (self::getAvailableStatistics($snmp, $ipdiscover) as $field => $label) {
         echo "<tr class='tab_bg_1'><td>" . $label . "</td><td>" . $statistics[$field] . "</td></tr>";
      }
      echo "</table></div>";
   }


   /**
    * @param null $checkitem
    *
    * @return array
    * @since version 0.85
    *
    * @see CommonDBTM::getSpecificMassiveActions()
    *
    */
   function getSpecificMassiveActions($checkitem = null) {

      $actions = parent::getSpecificMassiveActions($checkitem);

      return $actions;
   }

   /**
    * @param MassiveAction $ma
    *
    * @return bool|false
    * @since version 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
    *
    */
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'plugin_ocsinventoryng_launch_ocsng_update':
         case 'plugin_ocsinventoryng_force_ocsng_update':
            echo "&nbsp;" .
                 Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
            return true;
         case 'plugin_ocsinventoryng_lock_ocsng_field':
            $fields['all'] = __('All');
            $fields        += PluginOcsinventoryngOcslink::getLockableFields();
            Dropdown::showFromArray("field", $fields);
            echo "&nbsp;" .
                 Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
            return true;
         case 'plugin_ocsinventoryng_unlock_ocsng_field':
            $fields['all'] = __('All');
            $fields        += PluginOcsinventoryngOcslink::getLockableFields();
            Dropdown::showFromArray("field", $fields);
            echo "&nbsp;" .
                 Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
            return true;
      }
      return parent::showMassiveActionsSubForm($ma);
   }

   /**
    * @param MassiveAction $ma
    * @param CommonDBTM    $item
    * @param array         $ids
    *
    * @return nothing|void
    * @throws \GlpitestSQLError
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
    *
    * @since version 0.85
    *
    */
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids) {
      global $DB;

      switch ($ma->getAction()) {

         case "plugin_ocsinventoryng_launch_ocsng_update":
            $input = $ma->getInput();

            foreach ($ids as $id) {

               $query  = "SELECT `plugin_ocsinventoryng_ocsservers_id`, `id`
                               FROM `glpi_plugin_ocsinventoryng_ocslinks`
                               WHERE `computers_id` = $id";
               $result = $DB->query($query);
               if ($DB->numrows($result) == 1) {
                  $data        = $DB->fetchAssoc($result);
                  $cfg_ocs     = PluginOcsinventoryngOcsServer::getConfig($data['plugin_ocsinventoryng_ocsservers_id']);
                  $sync_params = ['ID'                                  => $data['id'],
                                  'plugin_ocsinventoryng_ocsservers_id' => $data['plugin_ocsinventoryng_ocsservers_id'],
                                  'cfg_ocs'                             => $cfg_ocs,
                                  'force'                               => 0];
                  if (self::synchronizeComputer($sync_params)) {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                  }
               } else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
               }
            }

            return;

         case "plugin_ocsinventoryng_force_ocsng_update":
            $input = $ma->getInput();

            foreach ($ids as $id) {

               $query  = "SELECT `plugin_ocsinventoryng_ocsservers_id`, `id`
                               FROM `glpi_plugin_ocsinventoryng_ocslinks`
                               WHERE `computers_id` = $id";
               $result = $DB->query($query);
               if ($DB->numrows($result) == 1) {
                  $data        = $DB->fetchAssoc($result);
                  $cfg_ocs     = PluginOcsinventoryngOcsServer::getConfig($data['plugin_ocsinventoryng_ocsservers_id']);
                  $sync_params = ['ID'                                  => $data['id'],
                                  'plugin_ocsinventoryng_ocsservers_id' => $data['plugin_ocsinventoryng_ocsservers_id'],
                                  'cfg_ocs'                             => $cfg_ocs,
                                  'force'                               => 1];
                  if (self::synchronizeComputer($sync_params)) {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                  }
               } else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
               }
            }

            return;

         case "plugin_ocsinventoryng_lock_ocsng_field" :
            $input  = $ma->getInput();
            $fields = PluginOcsinventoryngOcslink::getLockableFields();

            if ($input['field'] == 'all' || isset($fields[$input['field']])) {
               foreach ($ids as $id) {

                  if ($input['field'] == 'all') {
                     if (PluginOcsinventoryngOcslink::addToOcsArray($id, array_flip($fields), "computer_update")) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     }
                  } else {
                     if (PluginOcsinventoryngOcslink::addToOcsArray($id, [$input['field']], "computer_update")) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     }
                  }
               }
            }

            return;

         case "plugin_ocsinventoryng_unlock_ocsng_field" :
            $input  = $ma->getInput();
            $fields = PluginOcsinventoryngOcslink::getLockableFields();
            if ($input['field'] == 'all' || isset($fields[$input['field']])) {
               foreach ($ids as $id) {

                  if ($input['field'] == 'all') {
                     if (PluginOcsinventoryngOcslink::replaceOcsArray($id, [])) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     }
                  } else {
                     if (PluginOcsinventoryngOcslink::deleteInOcsArray($id, $input['field'], true)) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     }
                  }
               }
            }

            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }
}
