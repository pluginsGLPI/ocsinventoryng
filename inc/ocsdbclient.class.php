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

/**
 * Class PluginOcsinventoryngOcsDbClient
 */
class PluginOcsinventoryngOcsDbClient extends PluginOcsinventoryngOcsClient
{

   /**
    * @var DBmysql
    */
   private $db;

   /**
    * PluginOcsinventoryngOcsDbClient constructor.
    * @param $id
    * @param $dbhost
    * @param $dbuser
    * @param $dbpassword
    * @param $dbdefault
    */
   public function __construct($id, $dbhost, $dbuser, $dbpassword, $dbdefault)
   {
      parent::__construct($id);
      $this->db = new PluginOcsinventoryngDBocs($dbhost, $dbuser, $dbpassword, $dbdefault);
   }

   /**
    * @return DBmysql|PluginOcsinventoryngDBocs
    */
   public function getDB()
   {
      return $this->db;
   }

   public function getComputerRule($id, $tables = array())
   {
      $computers = array();

      $query = "SELECT `hardware`.*,`accountinfo`.`TAG` FROM `hardware`
            INNER JOIN `accountinfo` ON (`hardware`.`id` = `accountinfo`.`HARDWARE_ID`)
            WHERE `hardware`.`ID` = $id";

      $request = $this->db->query($query);
      while ($meta = $this->db->fetch_assoc($request)) {
         $computers["META"]["ID"]       = $meta["ID"];
         $computers["META"]["CHECKSUM"] = $meta["CHECKSUM"];
         $computers["META"]["DEVICEID"] = $meta["DEVICEID"];
         $computers["META"]["LASTCOME"] = $meta["LASTCOME"];
         $computers["META"]["LASTDATE"] = $meta["LASTDATE"];
         $computers["META"]["NAME"]     = $meta["NAME"];
         $computers["META"]["TAG"]      = $meta["TAG"];
         $computers["META"]["USERID"]   = $meta["USERID"];
         $computers["META"]["UUID"]     = $meta["UUID"];
      }

      $query   = "SELECT * FROM `hardware` WHERE `ID` = $id";
      $request = $this->db->query($query);
      while ($hardware = $this->db->fetch_assoc($request)) {

         $computers[strtoupper('hardware')] = $hardware;

      }


      foreach ($tables as $table) {

         if ($table == "accountinfo") {
            $query   = "SELECT * FROM `" . $table . "` WHERE `HARDWARE_ID` = $id";
            $request = $this->db->query($query);
            while ($accountinfo = $this->db->fetch_assoc($request)) {
               foreach ($accountinfo as $column => $value) {
                  if (preg_match('/fields_\d+/', $column, $matches)) {
                     $colnumb = explode("fields_", $matches['0']);

                     if (self::OcsTableExists("accountinfo_config")) {
                        $col            = $colnumb['1'];
                        $query          = "SELECT ID,NAME FROM accountinfo_config WHERE ID = '" . $col . "'";
                        $requestcolname = $this->db->query($query);
                        $colname        = $this->db->fetch_assoc($requestcolname);
                        if ($colname['NAME'] != "") {
                           if (!is_null($value)) {
                              $name         = "ACCOUNT_VALUE_" . $colname['NAME'] . "_" . $value;
                              $query        = "SELECT TVALUE,NAME FROM config WHERE NAME = '" . $name . "'";
                              $requestvalue = $this->db->query($query);
                              $custom_value = $this->db->fetch_assoc($requestvalue);
                              if (isset($custom_value['TVALUE'])) {
                                 $accountinfo[$column] = $custom_value['TVALUE'];
                              }
                           }
                        }
                     }
                  }
               }
               $accountinfomap = $this->getAccountInfoColumns();
               foreach ($accountinfo as $key => $value) {
                  unset($accountinfo[$key]);
                  $accountinfo[$accountinfomap[$key]] = $value;
               }
               $computers[strtoupper($table)][] = $accountinfo;

            }
         } else if ($table != "hardware") {
            if (self::OcsTableExists($table) && $table != "glpi_plugin_ocsinventoryng_ocsservers") {
               $query   = "SELECT * FROM `" . $table . "` WHERE `HARDWARE_ID` = $id";
               $request = $this->db->query($query);
               while ($computer = $this->db->fetch_assoc($request)) {
                  if ($table == 'networks') {
                     $computers[strtoupper($table)][] = $computer;
                  } else {
                     $computers[strtoupper($table)] = $computer;
                  }
               }
            }
         }
      }
      return $computers;
   }

   /**
    * Verify if a DB table exists
    *
    * @param $tablename string : Name of the table we want to verify.
    *
    * @return bool : true if exists, false elseway.
    **/
   function OcsTableExists($tablename)
   {


      // Get a list of tables contained within the database.
      $result = $this->db->list_tables("%" . $tablename . "%");

      if ($rcount = $this->db->numrows($result)) {
         while ($data = $this->db->fetch_row($result)) {
            if ($data[0] === $tablename) {
               return true;
            }
         }
      }

      $this->db->free_result($result);
      return false;
   }

   /*    * ******************* */
   /* PRIVATE  FUNCTIONS */
   /*    * ******************* */

   /**
    * @param $ids
    * @param $checksum
    * @param $wanted
    * @param $plugins
    * @param int $complete
    * @return mixed
    */
   private function getComputerSections($ids, $checksum, $wanted, $plugins, $complete = 0)
   {

      $OCS_MAP = self::getOcsMap();
      $DATA_MAP = array();
      foreach ($OCS_MAP as $table => $value) {

         if ($table == "dico_soft") {
            continue;
         }
         if ($table == "hardware") {
            $DATA_MAP[$table] = $value;
         }
         if (isset($value['checksum'])) {
            $check = $value['checksum'];
            if ($check & $checksum) {
               $DATA_MAP[$table] = $value;
            }
         } elseif (isset($value['wanted'])) {
            $check = $value['wanted'];
            if ($wanted & self::WANTED_ACCOUNTINFO) {
               $DATA_MAP[$table] = $value;
            }
         } elseif (isset($value['plugins'])) {
            $check = $value['plugins'];
            if (self::OcsTableExists($table) && (($check & $plugins) || $plugins == self::PLUGINS_ALL)) {
               $DATA_MAP[$table] = $value;
            }
         }
      }

      if ($complete > 0) {
         $DATA_MAP = $OCS_MAP;
      }

      $version = $this->getConfig("GUI_VERSION");

      foreach ($DATA_MAP as $table => $value) {
         if ($table == "dico_soft") {
            continue;
         }
         if (isset($value['checksum'])) {
            $check = $value['checksum'];
         } elseif (isset($value['wanted'])) {
            $check = $value['wanted'];
         } elseif (isset($value['plugins'])) {
            $check = $value['plugins'];
         }
         $multi = $value['multi'];
         if ($table == "accountinfo") {
            if (($wanted & self::WANTED_ACCOUNTINFO) || $complete > 0) {
               $query = "SELECT * FROM `" . $table . "` WHERE `HARDWARE_ID` IN (" . implode(',', $ids) . ")";
               $request = $this->db->query($query);
               while ($accountinfo = $this->db->fetch_assoc($request)) {
                  foreach ($accountinfo as $column => $value) {
                     if (preg_match('/fields_\d+/', $column, $matches)) {
                        $colnumb = explode("fields_", $matches['0']);

                        if (self::OcsTableExists("accountinfo_config")) {
                           $col = $colnumb['1'];
                           $query = "SELECT ID,NAME FROM accountinfo_config WHERE ID = '" . $col . "'";
                           $requestcolname = $this->db->query($query);
                           $colname = $this->db->fetch_assoc($requestcolname);
                           if ($colname['NAME'] != "") {
                              if (!is_null($value)) {
                                 $name = "ACCOUNT_VALUE_" . $colname['NAME'] . "_" . $value;
                                 $query = "SELECT TVALUE,NAME FROM config WHERE NAME = '" . $name . "'";
                                 $requestvalue = $this->db->query($query);
                                 $custom_value = $this->db->fetch_assoc($requestvalue);
                                 if (isset($custom_value['TVALUE'])) {
                                    $accountinfo[$column] = $custom_value['TVALUE'];
                                 }
                              }
                           }
                        }
                     }
                  }
                  $accountinfomap = $this->getAccountInfoColumns();
                  foreach ($accountinfo as $key => $value) {
                     unset($accountinfo[$key]);
                     $accountinfo[$accountinfomap[$key]] = $value;
                  }
                  if ($multi) {
                     $computers[$accountinfo['HARDWARE_ID']][strtoupper($table)][] = $accountinfo;
                  } else {
                     $computers[$accountinfo['HARDWARE_ID']][strtoupper($table)] = $accountinfo;
                  }
               }
            }
         } elseif ($table == "softwares") {
            if (($check & $checksum) || $complete > 0) {

               if (self::WANTED_DICO_SOFT & $wanted) {
                  $query = "SELECT
                                        IFNULL(`dico_soft`.`FORMATTED`, `softwares`.`NAME`) AS NAME,
                                        `softwares`.`VERSION`,
                                        `softwares`.`PUBLISHER`,
                                        `softwares`.`COMMENTS`,
                                        `softwares`.`FOLDER`,
                                        `softwares`.`FILENAME`,
                                        `softwares`.`FILESIZE`,
                                        `softwares`.`SOURCE`,
                                        `softwares`.`HARDWARE_ID`";
                  if ($version['TVALUE'] > PluginOcsinventoryngOcsServer::OCS2_VERSION_LIMIT) {
                     $query .= ",`softwares`.`GUID`,
                                        `softwares`.`LANGUAGE`,
                                        `softwares`.`INSTALLDATE`,
                                        `softwares`.`BITSWIDTH`";
                  }
                  $query .= "FROM `softwares`
                                        INNER JOIN `dico_soft` ON (`softwares`.`NAME` = `dico_soft`.`EXTRACTED`)
                                        WHERE `softwares`.`HARDWARE_ID` IN (" . implode(',', $ids) . ")";
               } else {
                  $query = "SELECT
                                        `softwares`.`NAME`,
                                        `softwares`.`VERSION`,
                                        `softwares`.`PUBLISHER`,
                                        `softwares`.`COMMENTS`,
                                        `softwares`.`FOLDER`,
                                        `softwares`.`FILENAME`,
                                        `softwares`.`FILESIZE`,
                                        `softwares`.`SOURCE`,
                                        `softwares`.`HARDWARE_ID`";
                  if ($version['TVALUE'] > PluginOcsinventoryngOcsServer::OCS2_VERSION_LIMIT) {
                     $query .= ",`softwares`.`GUID`,
                                  `softwares`.`LANGUAGE`,
                                  `softwares`.`INSTALLDATE`,
                                  `softwares`.`BITSWIDTH`";
                  }
                  $query .= "FROM `softwares`
                                        WHERE `softwares`.`HARDWARE_ID` IN (" . implode(',', $ids) . ")";
               }

               $request = $this->db->query($query);
               while ($software = $this->db->fetch_assoc($request)) {
                  $computers[$software['HARDWARE_ID']]["SOFTWARES"][] = $software;
               }
            }
         } elseif ($table == "registry") {

            if (($check & $checksum) || $complete > 0) {
               $query = "SELECT `registry`.`NAME` AS name,
                          `registry`.`REGVALUE` AS regvalue,
                          `registry`.`HARDWARE_ID` AS HARDWARE_ID,
                          `regconfig`.`REGTREE` AS regtree,
                          `regconfig`.`REGKEY` AS regkey
                   FROM `registry`
                   LEFT JOIN `regconfig` ON (`registry`.`NAME` = `regconfig`.`NAME`)
                   WHERE `HARDWARE_ID` IN (" . implode(',', $ids) . ")";
               $request = $this->db->query($query);
               while ($reg = $this->db->fetch_assoc($request)) {
                  if ($multi) {
                     $computers[$reg['HARDWARE_ID']][strtoupper($table)][] = $reg;
                  } else {
                     $computers[$reg['HARDWARE_ID']][strtoupper($table)] = $reg;
                  }
               }
            }
         } elseif (self::OcsTableExists("securitycenter") && $table == "securitycenter") {

            if (($check & $plugins) || $plugins == self::PLUGINS_ALL || $complete > 0) {
               $query = "SELECT `securitycenter`.`SCV` AS scv,
                          `securitycenter`.`CATEGORY` AS category,
                          `securitycenter`.`HARDWARE_ID` AS HARDWARE_ID,
                          `securitycenter`.`COMPANY` AS company,
                          `securitycenter`.`PRODUCT` AS product,
                          `securitycenter`.`VERSION` AS version,
                          `securitycenter`.`ENABLED` AS enabled,
                          `securitycenter`.`UPTODATE` AS uptodate
                   FROM `securitycenter`
                   WHERE `HARDWARE_ID` IN (" . implode(',', $ids) . ")";
               $request = $this->db->query($query);
               while ($av = $this->db->fetch_assoc($request)) {
                  if ($multi) {
                     $computers[$av['HARDWARE_ID']][strtoupper($table)][] = $av;
                  } else {
                     $computers[$av['HARDWARE_ID']][strtoupper($table)] = $av;
                  }
               }
            }
         } elseif (self::OcsTableExists("uptime") && $table == "uptime") {

            if (($check & $plugins) || $plugins == self::PLUGINS_ALL || $complete > 0) {
               $query = "SELECT `uptime`.`TIME` AS time,
                          `uptime`.`HARDWARE_ID` AS HARDWARE_ID
                   FROM `uptime`
                   WHERE `HARDWARE_ID` IN (" . implode(',', $ids) . ")";
               $request = $this->db->query($query);
               while ($up = $this->db->fetch_assoc($request)) {
                  $computers[$up['HARDWARE_ID']][strtoupper($table)] = $up;
               }
            }
         } elseif (self::OcsTableExists("officepack") && $table == "officepack") {

            $query = "SELECT `officepack`.* FROM `hardware`
            INNER JOIN `officepack` ON (`hardware`.`id` = `officepack`.`HARDWARE_ID`)
            WHERE `hardware`.`ID` IN (" . implode(',', $ids) . ")
            AND `INSTALL`" ;
            $request = $this->db->query($query);
            while ($meta = $this->db->fetch_assoc($request)) {
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["OFFICEVERSION"] = $meta["OFFICEVERSION"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["PRODUCT"] = $meta["PRODUCT"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["OFFICEKEY"] = $meta["OFFICEKEY"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["NOTE"] = $meta["NOTE"];

            }

         } elseif (self::OcsTableExists("winupdatestate") && $table == "winupdatestate") {
            
            $query = "SELECT `winupdatestate`.* FROM `hardware`
            INNER JOIN `winupdatestate` ON (`hardware`.`id` = `winupdatestate`.`HARDWARE_ID`)
            WHERE `hardware`.`ID` IN (" . implode(',', $ids) . ") " ;
            $request = $this->db->query($query);
            while ($meta = $this->db->fetch_assoc($request)) {
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["AUOPTIONS"] = $meta["AUOPTIONS"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["SCHEDULEDINSTALLDATE"] = $meta["SCHEDULEDINSTALLDATE"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["LASTSUCCESSTIME"] = $meta["LASTSUCCESSTIME"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["DETECTSUCCESSTIME"] = $meta["DETECTSUCCESSTIME"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["DOWNLOADSUCCESSTIME"] = $meta["DOWNLOADSUCCESSTIME"];

            }

         } elseif (self::OcsTableExists("runningprocess") && $table == "runningprocess") {
            $query   = "SELECT `runningprocess`.* FROM `hardware`
            INNER JOIN `runningprocess` ON (`hardware`.`id` = `runningprocess`.`HARDWARE_ID`)
            WHERE `hardware`.`ID` IN (" . implode(',', $ids) . ") ";
            $request = $this->db->query($query);
            while ($meta = $this->db->fetch_assoc($request)) {
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["CPUUSAGE"]      = $meta["CPUUSAGE"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["TTY"]           = $meta["TTY"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["STARTED"]       = $meta["STARTED"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["VIRTUALMEMORY"] = $meta["VIRTUALMEMORY"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["PROCESSNAME"]   = $meta["PROCESSNAME"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["PROCESSID"]     = $meta["PROCESSID"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["USERNAME"]      = $meta["USERNAME"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["PROCESSMEMORY"] = $meta["PROCESSMEMORY"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["COMMANDLINE"]   = $meta["COMMANDLINE"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["DESCRIPTION"]   = $meta["DESCRIPTION"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["COMPANY"]       = $meta["COMPANY"];
            }

         } elseif (self::OcsTableExists("service") && $table == "service") {

            $query   = "SELECT `service`.* FROM `hardware`
            INNER JOIN `service` ON (`hardware`.`id` = `service`.`HARDWARE_ID`)
            WHERE `hardware`.`ID` IN (" . implode(',', $ids) . ") ";
            $request = $this->db->query($query);
            while ($meta = $this->db->fetch_assoc($request)) {
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["SVCNAME"]         = $meta["SVCNAME"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["SVCDN"]           = $meta["SVCDN"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["SVCSTATE"]        = $meta["SVCSTATE"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["SVCDESC"]         = $meta["SVCDESC"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["SVCSTARTMODE"]    = $meta["SVCSTARTMODE"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["SVCPATH"]         = $meta["SVCPATH"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["SVCSTARTNAME"]    = $meta["SVCSTARTNAME"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["SVCEXITCODE"]     = $meta["SVCEXITCODE"];
               $computers[$meta['HARDWARE_ID']][strtoupper($table)][$meta['ID']]["SVCSPECEXITCODE"] = $meta["SVCSPECEXITCODE"];
            }

         } elseif ($table == "hardware") {

            $query = "SELECT `hardware`.*,`accountinfo`.`TAG` FROM `hardware`
            INNER JOIN `accountinfo` ON (`hardware`.`id` = `accountinfo`.`HARDWARE_ID`)
            WHERE `ID` IN (" . implode(',', $ids) . ")";
            $request = $this->db->query($query);
            while ($meta = $this->db->fetch_assoc($request)) {
               $computers[$meta['ID']]["META"]["ID"] = $meta["ID"];
               $computers[$meta['ID']]["META"]["CHECKSUM"] = $meta["CHECKSUM"];
               $computers[$meta['ID']]["META"]["DEVICEID"] = $meta["DEVICEID"];
               $computers[$meta['ID']]["META"]["LASTCOME"] = $meta["LASTCOME"];
               $computers[$meta['ID']]["META"]["LASTDATE"] = $meta["LASTDATE"];
               $computers[$meta['ID']]["META"]["NAME"] = $meta["NAME"];
               $computers[$meta['ID']]["META"]["TAG"] = $meta["TAG"];
               $computers[$meta['ID']]["META"]["USERID"] = $meta["USERID"];
               $computers[$meta['ID']]["META"]["UUID"] = $meta["UUID"];
            }

            if (($check & $checksum) || $complete > 0) {
               $query = "SELECT * FROM `" . $table . "` WHERE `ID` IN (" . implode(',', $ids) . ")";
               $request = $this->db->query($query);
               while ($hardware = $this->db->fetch_assoc($request)) {
                  if ($multi) {
                     $computers[$hardware['ID']][strtoupper($table)][] = $hardware;
                  } else {
                     $computers[$hardware['ID']][strtoupper($table)] = $hardware;
                  }
               }
            }
         } else {
            if (self::OcsTableExists($table)) {
               if (($check & $checksum) || $complete > 0) {
                  $query = "SELECT * FROM `" . $table . "` WHERE `HARDWARE_ID` IN (" . implode(',', $ids) . ")";
                  $request = $this->db->query($query);
                  while ($computer = $this->db->fetch_assoc($request)) {
                     if ($multi) {
                        $computers[$computer['HARDWARE_ID']][strtoupper($table)][] = $computer;
                     } else {
                        $computers[$computer['HARDWARE_ID']][strtoupper($table)] = $computer;
                     }
                  }
               }
            }
         }
      }

      return $computers;
   }

   /**
    * @param $ids
    * @param int $complete
    * @return array
    */
   private function getSnmpSections($ids, $complete = 1)
   {

      $snmp = array();

      // Check for basics snmp infos
      $query = "SELECT * FROM `snmp` WHERE `ID` IN (" . implode(',', $ids) . ")";
      $request = $this->db->query($query);
      while ($snmp_request = $this->db->fetch_assoc($request)) {
         $snmp[$snmp_request['ID']]['META'] = $snmp_request;
      }
      if ($complete) {
         // Printers infos
         $query = "SELECT * FROM `snmp_printers` WHERE `SNMP_ID` IN (" . implode(',', $ids) . ")";
         $request = $this->db->query($query);
         while ($snmp_request = $this->db->fetch_assoc($request)) {
            $snmp[$snmp_request['SNMP_ID']]['PRINTER'][] = $snmp_request;
         }

         // Cartridges
         $query = "SELECT * FROM `snmp_cartridges` WHERE `SNMP_ID` IN (" . implode(',', $ids) . ")";
         $request = $this->db->query($query);
         while ($snmp_request = $this->db->fetch_assoc($request)) {
            $snmp[$snmp_request['SNMP_ID']]['CARTRIDGES'][] = $snmp_request;
         }

         // Switches
         $query = "SELECT * FROM `snmp_switchs` WHERE `SNMP_ID` IN (" . implode(',', $ids) . ")";
         $request = $this->db->query($query);
         while ($snmp_request = $this->db->fetch_assoc($request)) {
            $snmp[$snmp_request['SNMP_ID']]['SWITCH'][] = $snmp_request;
         }

         // cards
         $query = "SELECT * FROM `snmp_cards` WHERE `SNMP_ID` IN (" . implode(',', $ids) . ")";
         $request = $this->db->query($query);
         while ($snmp_request = $this->db->fetch_assoc($request)) {
            $snmp[$snmp_request['SNMP_ID']]['CARDS'][] = $snmp_request;
         }

         // Powersupplies
         $query = "SELECT * FROM `snmp_powersupplies` WHERE `SNMP_ID` IN (" . implode(',', $ids) . ")";
         $request = $this->db->query($query);
         while ($snmp_request = $this->db->fetch_assoc($request)) {
            $snmp[$snmp_request['SNMP_ID']]['POWERSUPPLIES'][] = $snmp_request;
         }

         // Firewall
         $query = "SELECT * FROM `snmp_firewalls` WHERE `SNMP_ID` IN (" . implode(',', $ids) . ")";
         $request = $this->db->query($query);
         while ($snmp_request = $this->db->fetch_assoc($request)) {
            $snmp[$snmp_request['SNMP_ID']]['FIREWALLS'][] = $snmp_request;
         }

         // Fans
         $query = "SELECT * FROM `snmp_fans` WHERE `SNMP_ID` IN (" . implode(',', $ids) . ")";
         $request = $this->db->query($query);
         while ($snmp_request = $this->db->fetch_assoc($request)) {
            $snmp[$snmp_request['SNMP_ID']]['FANS'][] = $snmp_request;
         }

         // Trays
         $query = "SELECT * FROM `snmp_trays` WHERE `SNMP_ID` IN (" . implode(',', $ids) . ")";
         $request = $this->db->query($query);
         while ($snmp_request = $this->db->fetch_assoc($request)) {
            $snmp[$snmp_request['SNMP_ID']]['TRAYS'][] = $snmp_request;
         }

         //memories
         $query = "SELECT * FROM `snmp_memories` WHERE `SNMP_ID` IN (" . implode(',', $ids) . ")";
         $request = $this->db->query($query);
         while ($snmp_request = $this->db->fetch_assoc($request)) {
            $snmp[$snmp_request['SNMP_ID']]['MEMORIES'][] = $snmp_request;
         }

         //cpus
         $query = "SELECT * FROM `snmp_cpus` WHERE `SNMP_ID` IN (" . implode(',', $ids) . ")";
         $request = $this->db->query($query);
         while ($snmp_request = $this->db->fetch_assoc($request)) {
            $snmp[$snmp_request['SNMP_ID']]['CPU'][] = $snmp_request;
         }

         //networks
         $query = "SELECT * FROM `snmp_networks` WHERE `SNMP_ID` IN (" . implode(',', $ids) . ")";
         $request = $this->db->query($query);
         while ($snmp_request = $this->db->fetch_assoc($request)) {
            $snmp[$snmp_request['SNMP_ID']]['NETWORKS'][] = $snmp_request;
         }

         //virtualmachines
         $query = "SELECT * FROM `snmp_virtualmachines` WHERE `SNMP_ID` IN (" . implode(',', $ids) . ")";
         $request = $this->db->query($query);
         while ($snmp_request = $this->db->fetch_assoc($request)) {
            $snmp[$snmp_request['SNMP_ID']]['VIRTUALMACHINES'][] = $snmp_request;
         }

         //softwares
         $query = "SELECT * FROM `snmp_softwares` WHERE `SNMP_ID` IN (" . implode(',', $ids) . ")";
         $request = $this->db->query($query);
         while ($snmp_request = $this->db->fetch_assoc($request)) {
            $snmp[$snmp_request['SNMP_ID']]['SOFTWARES'][] = $snmp_request;
         }
      }
      return $snmp;

   }

   /*    * ******************* */
   /* PUBLIC  FUNCTIONS  */
   /*    * ******************* */

   /**
    * @see PluginOcsinventoryngOcsClient::checkConnection()
    */
   public function checkConnection()
   {
      return $this->db->connected;
   }

   /**
    * @see PluginOcsinventoryngOcsClient::searchComputers()
    * @param string $field
    * @param mixed $value
    * @return array
    */
   public function searchComputers($field, $value)
   {

      if ($field == "id" || $field == "ID") {
         $options = array(
            "FILTER" => array(
               'IDS' => array(
                  $value
               )
            )
         );
      } elseif ($field == "tag" || $field == "TAG") {
         $options = array(
            "FILTER" => array(
               'TAGS' => array(
                  $value
               )
            )
         );
      } elseif ($field == "deviceid" || $field == "DEVICEID") {
         $options = array(
            "FILTER" => array(
               'DEVICEIDS' => array(
                  $value
               )
            )
         );
      }


      $res = $this->getComputers($options);
      return $res;
   }

   /**
    * @see PluginOcsinventoryngOcsClient::updateBios()
    * @param int $ssn
    * @param int $id
    */
   public function updateBios($ssn, $id)
   {
      $query = "UPDATE `bios` SET `SSN` = '" . $ssn . "'" . " WHERE `HARDWARE_ID` = '" . $id . "'";
      $this->db->query($query);
   }

   /**
    * @see PluginOcsinventoryngOcsClient::updateTag()
    * @param int $tag
    * @param int $id
    */
   public function updateTag($tag, $id)
   {
      $query = "UPDATE `accountinfo` SET `TAG` = '" . $tag . "' WHERE `HARDWARE_ID` = '" . $id . "'";
      $this->db->query($query);
   }
   
      /**
    * @see PluginOcsinventoryngOcsClient::getComputers()
    * @param array $options
    * @return array
    */
   public function countComputers($options, $id = 0)
   {


      if (isset($options['OFFSET'])) {
         $offset = "OFFSET  " . $options['OFFSET'];
      } else {
         $offset = "";
      }
      if (isset($options['MAX_RECORDS'])) {
         $max_records = "LIMIT  " . $options['MAX_RECORDS'];
      } else {
         $max_records = "";
      }
      if (isset($options['ORDER'])) {
         $order = $options['ORDER'];
      } else {
         $order = " LASTDATE ";
      }

      if (isset($options['FILTER'])) {
         $filters = $options['FILTER'];
         if (isset($filters['IDS']) and $filters['IDS']) {
            $ids = $filters['IDS'];
            $where_ids = " AND hardware.ID IN (";
            $where_ids .= join(',', $ids);
            $where_ids .= ") ";
         } else {
            $where_ids = "";
         }

         if (isset($filters['EXCLUDE_IDS']) and $filters['EXCLUDE_IDS']) {
            $exclude_ids = $filters['EXCLUDE_IDS'];
            $where_exclude_ids = " AND hardware.ID NOT IN (";
            $where_exclude_ids .= join(',', $exclude_ids);
            $where_exclude_ids .= ") ";
         } else {
            $where_exclude_ids = "";
         }
         if (isset($filters['DEVICEIDS']) and $filters['DEVICEIDS']) {
            $deviceids = $filters['DEVICEIDS'];
            $where_deviceids = " AND hardware.DEVICEID IN ('";
            $where_deviceids .= join('\',\'', $deviceids);
            $where_deviceids .= "') ";
         } else {
            $where_deviceids = "";
         }

         if (isset($filters['EXCLUDE_DEVICEIDS']) and $filters['EXCLUDE_DEVICEIDS']) {
            $exclude_deviceids = $filters['EXCLUDE_DEVICEIDS'];
            $where_exclude_deviceids = " AND hardware.DEVICEID NOT IN (";
            $where_exclude_deviceids .= join(',', $exclude_deviceids);
            $where_exclude_deviceids .= ") ";
         } else {
            $where_exclude_deviceids = "";
         }

         if (isset($filters['TAGS']) and $filters['TAGS']) {
            $tags = $filters['TAGS'];
            $where_tags = " AND accountinfo.TAG IN (";
            $where_tags .= "'" . join('\',\'', $tags) . "'";
            $where_tags .= ") ";
         } else {
            $where_tags = "";
         }

         if (isset($filters['EXCLUDE_TAGS']) and $filters['EXCLUDE_TAGS']) {

            $exclude_tags = $filters['EXCLUDE_TAGS'];
            $where_exclude_tags = " AND accountinfo.TAG NOT IN (";
            $where_exclude_tags .= "'" . join('\',\'', $exclude_tags) . "'";
            $where_exclude_tags .= ") ";
         } else {
            $where_exclude_tags = "";
         }

         if (isset($filters['INVENTORIED_SINCE']) and $filters['INVENTORIED_SINCE']) {

            if (!isset($filters['CHECKSUM'])) {
               $since = $filters['INVENTORIED_SINCE'];
               $where_since = " AND (`hardware`.`LASTDATE` > ";
               $where_since .= "'" . $since . "'";
               $where_since .= ") ";
            } else {
               $where_since = "";
            }
         } else {
            $where_since = "";
         }

         if (isset($filters['INVENTORIED_BEFORE']) and $filters['INVENTORIED_BEFORE']) {

            $before = $filters['INVENTORIED_BEFORE'];
            $where_before = " AND (UNIX_TIMESTAMP(`hardware`.`LASTDATE`) < (UNIX_TIMESTAMP(" . $before . ")-180";
            // $where_before .= "'" .$before. "'";
            $where_before .= ")) ";
         } else {
            $where_before = "";
         }


         if (isset($filters['CHECKSUM']) and $filters['CHECKSUM']) {
            $checksum = $filters['CHECKSUM'];

            $where_checksum = " AND (('" . $checksum . "' & `hardware`.`CHECKSUM`) > '0'";
            if (isset($filters['INVENTORIED_SINCE']) and $filters['INVENTORIED_SINCE']) {
               $since = $filters['INVENTORIED_SINCE'];
               $where_checksum .= " OR `hardware`.`LASTDATE` > '$since'";
            }
            $where_checksum .= ")";
         } else {
            $where_checksum = "";
         }
         $where_condition = $where_ids . $where_exclude_ids . $where_deviceids . $where_exclude_deviceids . $where_tags . $where_exclude_tags . $where_checksum . $where_since . $where_before;
      } else {
         $where_condition = "";
      }
      $join = "";
      if ((isset($filters['EXCLUDE_TAGS']) and $filters['EXCLUDE_TAGS']) ||
         (isset($filters['TAGS']) and $filters['TAGS'])) {
         $join = "LEFT JOIN `accountinfo` ON (`hardware`.`ID` = `accountinfo`.`HARDWARE_ID`) ";
      }

      if ($id > 0) {
         $query = "SELECT count(DISTINCT `hardware`.`ID`) FROM `hardware` $join
                           WHERE `hardware`.`ID` = $id
                           $where_condition";
      } else {
         $query = "SELECT DISTINCT `hardware`.`ID` FROM `hardware` $join
                           WHERE `hardware`.`DEVICEID` NOT LIKE '\\_%'
                           $where_condition
                           ORDER BY $order
                           $max_records $offset";
      }
      $request = $this->db->query($query);

      if ($this->db->numrows($request)) {

         $count = $this->db->numrows($request);
         while ($hardwareid = $this->db->fetch_assoc($request)) {
            $hardwareids[] = $hardwareid['ID'];
         }
         return $hardwareids;
      } else {

         return array();
      }

      return $res;
   }

   /**
    * @see PluginOcsinventoryngOcsClient::getComputers()
    * @param array $options
    * @return array
    */
   public function getComputers($options, $id = 0)
   {


      if (isset($options['OFFSET'])) {
         $offset = "OFFSET  " . $options['OFFSET'];
      } else {
         $offset = "";
      }
      if (isset($options['MAX_RECORDS'])) {
         $max_records = "LIMIT  " . $options['MAX_RECORDS'];
      } else {
         $max_records = "";
      }
      if (isset($options['ORDER'])) {
         $order = $options['ORDER'];
      } else {
         $order = " LASTDATE ";
      }

      if (isset($options['FILTER'])) {
         $filters = $options['FILTER'];
         if (isset($filters['IDS']) and $filters['IDS']) {
            $ids = $filters['IDS'];
            $where_ids = " AND hardware.ID IN (";
            $where_ids .= join(',', $ids);
            $where_ids .= ") ";
         } else {
            $where_ids = "";
         }

         if (isset($filters['EXCLUDE_IDS']) and $filters['EXCLUDE_IDS']) {
            $exclude_ids = $filters['EXCLUDE_IDS'];
            $where_exclude_ids = " AND hardware.ID NOT IN (";
            $where_exclude_ids .= join(',', $exclude_ids);
            $where_exclude_ids .= ") ";
         } else {
            $where_exclude_ids = "";
         }
         if (isset($filters['DEVICEIDS']) and $filters['DEVICEIDS']) {
            $deviceids = $filters['DEVICEIDS'];
            $where_deviceids = " AND hardware.DEVICEID IN ('";
            $where_deviceids .= join('\',\'', $deviceids);
            $where_deviceids .= "') ";
         } else {
            $where_deviceids = "";
         }

         if (isset($filters['EXCLUDE_DEVICEIDS']) and $filters['EXCLUDE_DEVICEIDS']) {
            $exclude_deviceids = $filters['EXCLUDE_DEVICEIDS'];
            $where_exclude_deviceids = " AND hardware.DEVICEID NOT IN (";
            $where_exclude_deviceids .= join(',', $exclude_deviceids);
            $where_exclude_deviceids .= ") ";
         } else {
            $where_exclude_deviceids = "";
         }

         if (isset($filters['TAGS']) and $filters['TAGS']) {
            $tags = $filters['TAGS'];
            $where_tags = " AND accountinfo.TAG IN (";
            $where_tags .= "'" . join('\',\'', $tags) . "'";
            $where_tags .= ") ";
         } else {
            $where_tags = "";
         }

         if (isset($filters['EXCLUDE_TAGS']) and $filters['EXCLUDE_TAGS']) {

            $exclude_tags = $filters['EXCLUDE_TAGS'];
            $where_exclude_tags = " AND accountinfo.TAG NOT IN (";
            $where_exclude_tags .= "'" . join('\',\'', $exclude_tags) . "'";
            $where_exclude_tags .= ") ";
         } else {
            $where_exclude_tags = "";
         }

         if (isset($filters['INVENTORIED_SINCE']) and $filters['INVENTORIED_SINCE']) {

            if (!isset($filters['CHECKSUM'])) {
               $since = $filters['INVENTORIED_SINCE'];
               $where_since = " AND (`hardware`.`LASTDATE` > ";
               $where_since .= "'" . $since . "'";
               $where_since .= ") ";
            } else {
               $where_since = "";
            }
         } else {
            $where_since = "";
         }

         if (isset($filters['INVENTORIED_BEFORE']) and $filters['INVENTORIED_BEFORE']) {

            $before = $filters['INVENTORIED_BEFORE'];
            $where_before = " AND (UNIX_TIMESTAMP(`hardware`.`LASTDATE`) < (UNIX_TIMESTAMP(" . $before . ")-180";
            // $where_before .= "'" .$before. "'";
            $where_before .= ")) ";
         } else {
            $where_before = "";
         }


         if (isset($filters['CHECKSUM']) and $filters['CHECKSUM']) {
            $checksum = $filters['CHECKSUM'];

            $where_checksum = " AND (('" . $checksum . "' & `hardware`.`CHECKSUM`) > '0'";
            if (isset($filters['INVENTORIED_SINCE']) and $filters['INVENTORIED_SINCE']) {
               $since = $filters['INVENTORIED_SINCE'];
               $where_checksum .= " OR `hardware`.`LASTDATE` > '$since'";
            }
            $where_checksum .= ")";
         } else {
            $where_checksum = "";
         }
         $where_condition = $where_ids . $where_exclude_ids . $where_deviceids . $where_exclude_deviceids . $where_tags . $where_exclude_tags . $where_checksum . $where_since . $where_before;
      } else {
         $where_condition = "";
      }


      /*$query = "SELECT * FROM `hardware`, `accountinfo`
                        WHERE `hardware`.`DEVICEID` NOT LIKE '\\_%'
                        AND `hardware`.`ID` = `accountinfo`.`HARDWARE_ID`
                        $where_condition";*/
      if ($id > 0) {
         $query = "SELECT DISTINCT `hardware`.`ID`,`hardware`.`LASTDATE`,`hardware`.`NAME` FROM `hardware`
                           WHERE `hardware`.`ID` = $id
                           $where_condition";
      } else {
         $query = "SELECT DISTINCT `hardware`.`ID`,`hardware`.`LASTDATE`,`hardware`.`NAME` FROM `hardware`, `accountinfo`
                           WHERE `hardware`.`DEVICEID` NOT LIKE '\\_%'
                           AND `hardware`.`ID` = `accountinfo`.`HARDWARE_ID`
                           $where_condition
                           ORDER BY $order
                           $max_records $offset";
      }
      $request = $this->db->query($query);

      if ($this->db->numrows($request)) {

         $count = $this->db->numrows($request);
         /*$query = "SELECT DISTINCT hardware.ID FROM hardware, accountinfo
                           WHERE hardware.DEVICEID NOT LIKE '\\_%'
                           AND hardware.ID = accountinfo.HARDWARE_ID
                           $where_condition
                           ORDER BY $order
                           $max_records  $offset";
         
         $request = $this->db->query($query);*/
         $this->getAccountInfoColumns();
         while ($hardwareid = $this->db->fetch_assoc($request)) {
            $hardwareids[] = $hardwareid['ID'];
         }
         $res["TOTAL_COUNT"] = $count;
         if (isset($options['DISPLAY']['CHECKSUM'])) {
            $checksum = $options['DISPLAY']['CHECKSUM'];
         } else {
            $checksum = self::CHECKSUM_NONE;
         }
         if (isset($options['DISPLAY']['WANTED'])) {
            $wanted = $options['DISPLAY']['WANTED'];
         } else {
            $wanted = self::WANTED_NONE;
         }
         
         if (isset($options['DISPLAY']['PLUGINS'])) {
            $plugins = $options['DISPLAY']['PLUGINS'];
         } else {
            $plugins = self::PLUGINS_NONE;
         }

         $complete = 0;
         if (isset($options['COMPLETE'])) {
            $complete = $options['COMPLETE'];
         }

         $res["COMPUTERS"] = $this->getComputerSections($hardwareids, $checksum, $wanted, $plugins, $complete);

      } else {

         $res = array();
      }

      return $res;
   }

   /**
    * @see PluginOcsinventoryngOcsClient::getConfig()
    * @param string $key
    * @return bool|mixed|result
    */
   public function getConfig($key)
   {
      $res = false;
      $query = "SELECT `IVALUE`, `TVALUE` FROM `config` WHERE `NAME` = '" . $this->db->escape($key) . "'";
      $config = $this->db->query($query);
      if ($config->num_rows > 0) {
         while ($conf = $this->db->fetch_assoc($config)) {
            $res = $conf;
         }
      }
      return $res;
   }

   /**
    * @see PluginOcsinventoryngOcsClient::setConfig()
    * @param string $key
    * @param int $ivalue
    * @param string $tvalue
    */
   public function setConfig($key, $ivalue, $tvalue)
   {
      $query = "UPDATE `config` SET `IVALUE` = '" . $ivalue . "', `TVALUE` = '" . $this->db->escape($tvalue) . "' WHERE `NAME` = '" . $this->db->escape($key) . "'";
      $this->db->query($query);
   }

   /**
    * @see PluginOcsinventoryngOcsClient::setChecksum()
    * @param int $checksum
    * @param int $id
    */
   public function setChecksum($checksum, $id)
   {
      $query = "UPDATE `hardware` SET `CHECKSUM` = '" . $checksum . "' WHERE `ID` = '" . $id . "'";
      $this->db->query($query);
   }

   /**
    * @see PluginOcsinventoryngOcsClient::getChecksum()
    * @param int $id
    * @return int
    */
   public function getChecksum($id)
   {
      $query = "SELECT `CHECKSUM` FROM `hardware` WHERE `ID` = '" . $id . "'";
      $checksum = $this->db->query($query);
      $res = $this->db->fetch_assoc($checksum);
      return $res["CHECKSUM"];
   }

   /**
    * @see PluginOcsinventoryngOcsClient::getComputersToUpdate()
    * @param array $cfg_ocs
    * @param date $max_date
    * @return array
    */
   public function getComputersToUpdate($cfg_ocs, $max_date)
   {
      $query = "SELECT *
                       FROM `hardware`
                       INNER JOIN `accountinfo` ON (`hardware`.`ID` = `accountinfo`.`HARDWARE_ID`)
                       WHERE ((`hardware`.`CHECKSUM` & " . $cfg_ocs["checksum"] . ") > '0'
                              OR `hardware`.`LASTDATE` > '$max_date') ";

      // workaround to avoid duplicate when synchro occurs during an inventory
      // "after" insert in ocsweb.hardware  and "before" insert in ocsweb.deleted_equiv
      $query .= " AND UNIX_TIMESTAMP(`LASTDATE`) < (UNIX_TIMESTAMP(NOW())-180) ";

      $tag_limit = PluginOcsinventoryngOcsServer::getTagLimit($cfg_ocs);
      if (!empty($tag_limit)) {
         $query .= "AND " . $tag_limit;
      }

      $query .= " ORDER BY `hardware`.`LASTDATE` ASC
                        LIMIT " . intval($cfg_ocs["cron_sync_number"]);

      $res = $this->db->query($query);
      $data = array();

      if ($res->num_rows > 0) {
         while ($num = $this->db->fetch_assoc($res)) {
            $data[] = $num;
         }
      }
      return $data;
   }

   /**
    * @see PluginOcsinventoryngOcsClient::getOCSComputers()
    */
   public function getOCSComputers()
   {
      $query = "SELECT `ID`, `DEVICEID`
                    FROM `hardware`";
      $res = $this->db->query($query);
      $data = array();

      if ($res->num_rows > 0) {
         while ($num = $this->db->fetch_assoc($res)) {
            $data = $num;
         }
      }
      return $data;
   }

   public function getIfOCSComputersExists($id)
   {
      $query = "SELECT `ID`
                    FROM `hardware`
                    WHERE `ID` = $id";
      $res = $this->db->query($query);

      if ($res->num_rows > 0) {
         return true;
      }
      return false;
   }

   /**
    * @see PluginOcsinventoryngOcsClient::getOldAgents()
    */
   public function getOldAgents()
   {

      $config = $this->getConfig("GUI_REPORT_AGIN_MACH");
      $delay = $config['IVALUE'];
      $query = "SELECT id from hardware 
                     WHERE ( unix_timestamp(LASTCOME) <= UNIX_TIMESTAMP(NOW() - INTERVAL $delay DAY)) 
                     AND ( unix_timestamp(LASTCOME) <= UNIX_TIMESTAMP(NOW() - INTERVAL $delay DAY)) 
                     AND deviceid <> '_SYSTEMGROUP_' AND deviceid <> '_DOWNLOADGROUP_'";
      $res = $this->db->query($query);
      $data = array();

      if ($res->num_rows > 0) {
         while ($num = $this->db->fetch_assoc($res)) {
            $data[] = $num;
         }
      }
      return $data;
   }

   /**
    * @param $agents
    * @return int
    */
   public function deleteOldAgents($agents)
   {

      $i = 0;

      foreach ($agents as $key => $val) {
         foreach ($val as $k => $agent) {

            $query = "SELECT deviceid,name,IPADDR,OSNAME FROM hardware WHERE id='" . $agent . "' ";

            $res = $this->db->query($query);

            if ($res->num_rows > 0) {
               while ($num = $this->db->fetch_assoc($res)) {

                  $did = $num["deviceid"];
                  if ($did) {

                     $tables = array("accesslog",
                        "accountinfo",
                        "bios",
                        "controllers",
                        "devices",
                        "download_history",
                        "download_servers",
                        "drives",
                        "groups",
                        "groups_cache",
                        "inputs",
                        "itmgmt_comments",
                        "javainfo",
                        "journallog",
                        "locks",
                        "memories",
                        "modems",
                        "monitors",
                        "networks",
                        "ports",
                        "printers",
                        "registry",
                        "securitycenter",
                        "uptime",
                        "officepack",
                        "winupdatestate",
                        "slots",
                        "softwares",
                        "sounds",
                        "storages",
                        "videos",
                        "virtualmachines",
                        "cpus",
                        "sim"
                     );
                     if (isset($tables) and is_array($tables)) {
                        foreach ($tables as $table) {
                           if(self::OcsTableExists($table)){
                              $sql = "DELETE FROM $table WHERE HARDWARE_ID='" . $agent . "'";
                              $this->db->query($sql);
                           }
                        }
                     }
                     $sql = "DELETE FROM download_enable WHERE SERVER_ID='" . $agent . "'";
                     $this->db->query($sql);

                     $sql = "DELETE FROM hardware WHERE id='" . $agent . "'";
                     $this->db->query($sql);

                     //Deleted computers tracking
                     $sql = "INSERT INTO deleted_equiv(DELETED,EQUIVALENT) VALUES('$did','NULL')";
                     $this->db->query($sql);
                     $i++;
                  }
               }
            }
         }
      }
      return $i;
   }

   /**
    * @see PluginOcsinventoryngOcsClient::getDeletedComputers()
    */
   public function getDeletedComputers()
   {

      if (empty($_SESSION["ocs_deleted_equiv"]["total"])) {
         $query = "SELECT COUNT( * ) FROM `deleted_equiv`";
         $total_count = $this->db->query($query);
         $total = $this->db->fetch_row($total_count);
         $_SESSION["ocs_deleted_equiv"]["total"] = intval($total['0']);
      }
      $count = 0;
      $query = "SELECT `DATE`,`DELETED`,`EQUIVALENT` 
                     FROM `deleted_equiv` ORDER BY `DATE`,`EQUIVALENT` 
                     LIMIT 300";
      $deleted = $this->db->query($query);
      while ($del = $this->db->fetch_assoc($deleted)) {
         $computers[] = $del;
      }
      if (isset($computers)) {
         foreach ($computers as $computer) {
            $res[$computer['DELETED']] = $computer['EQUIVALENT'];
            $count++;
         }
      } else {
         $res = array();
      }
      $_SESSION["ocs_deleted_equiv"]["deleted"] = 0;
      if (empty($_SESSION["ocs_deleted_equiv"]["total"])) {
         $_SESSION["ocs_deleted_equiv"]["deleted"] = $count;
      } else {
         $_SESSION["ocs_deleted_equiv"]["deleted"] += $count;
      }
      $_SESSION["ocs_deleted_equiv"]["last_req"] = $count;
      return $res;
   }

   /**
    * @param $deleted
    * @param null $equivclean
    * @return Query
    */
   public function removeDeletedComputers($deleted, $equivclean = null)
   {
      if (is_array($deleted)) {
         $del = "('";
         $del .= join("','", $deleted);
         $del .= "')";
         $query = "DELETE FROM `deleted_equiv` WHERE `DELETED` IN " . $del . " ";
      } else {
         $query = "DELETE FROM `deleted_equiv` WHERE `DELETED` = '" . $this->db->escape($deleted) . "' ";
      }
      if (empty($equivclean)) {
         $equiv_clean = " AND (`EQUIVALENT` = '' OR `EQUIVALENT` IS NULL ) ";
      } else {
         $equiv_clean = "AND `EQUIVALENT` = '" . $this->db->escape($equivclean) . "'";
      }
      $query .= $equiv_clean;
      $delete = $this->db->query($query);
      $res = $delete;
      return $res;
   }

   /*    * eee
    * @see PluginOcsinventoryngOcsClient::getAccountInfoColumns()
    */

   /**
    * @param string $table
    * @return mixed
    */
   public function getAccountInfoColumns($table = 'accountinfo')
   {
      if ($table == 'accountinfo') {
         $query = "SHOW COLUMNS FROM `$table`";
         $columns = $this->db->query($query);
         while ($column = $this->db->fetch_assoc($columns)) {
            $res[$column['Field']] = $column['Field'];
         }
      } elseif ($table == 'hardware') {
         $res['DEVICEID'] = 'DEVICEID';
      }
      return $res;

   }

   /**
    * @see PluginOcsinventoryngOcsClient::getSnmp()
    * @param array $options
    * @return array
    */
   public function getSnmp($options)
   {


      if (isset($options['OFFSET'])) {
         $offset = "OFFSET  " . $options['OFFSET'];
      } else {
         $offset = "";
      }
      if (isset($options['MAX_RECORDS'])) {
         $max_records = "LIMIT  " . $options['MAX_RECORDS'];
      } else {
         $max_records = "";
      }
      if (isset($options['ORDER'])) {
         $order = $options['ORDER'];
      } else {
         $order = " LASTDATE ";
      }

      if (isset($options['FILTER'])) {
         $filters = $options['FILTER'];
         if (isset($filters['IDS']) and $filters['IDS']) {
            $ids = $filters['IDS'];
            $where_ids = " AND snmp.ID IN (";
            $where_ids .= join(',', $ids);
            $where_ids .= ") ";
         } else {
            $where_ids = "";
         }

         if (isset($filters['EXCLUDE_IDS']) and $filters['EXCLUDE_IDS']) {
            $exclude_ids = $filters['EXCLUDE_IDS'];
            $where_exclude_ids = " AND snmp.ID NOT IN (";
            $where_exclude_ids .= join(',', $exclude_ids);
            $where_exclude_ids .= ") ";
         } else {
            $where_exclude_ids = "";
         }
         if (isset($filters['DEVICEIDS']) and $filters['DEVICEIDS']) {
            $deviceids = $filters['DEVICEIDS'];
            $where_deviceids = " AND snmp.SNMPDEVICEID IN ('";
            $where_deviceids .= join('\',\'', $deviceids);
            $where_deviceids .= "') ";
         } else {
            $where_deviceids = "";
         }

         if (isset($filters['EXCLUDE_DEVICEIDS']) and $filters['EXCLUDE_DEVICEIDS']) {
            $exclude_deviceids = $filters['EXCLUDE_DEVICEIDS'];
            $where_exclude_deviceids = " AND snmp.SNMPDEVICEID NOT IN (";
            $where_exclude_deviceids .= join(',', $exclude_deviceids);
            $where_exclude_deviceids .= ") ";
         } else {
            $where_exclude_deviceids = "";
         }

         if (isset($filters['INVENTORIED_SINCE']) and $filters['INVENTORIED_SINCE']) {

            $since = $filters['INVENTORIED_SINCE'];
            $where_since = " AND (`snmp`.`LASTDATE` > ";
            $where_since .= "'" . $since . "'";
            $where_since .= ") ";
         } else {
            $where_since = "";
         }

         if (isset($filters['INVENTORIED_BEFORE']) and $filters['INVENTORIED_BEFORE']) {

            $before = $filters['INVENTORIED_BEFORE'];
            $where_before = " AND (UNIX_TIMESTAMP(`snmp`.`LASTDATE`) < (UNIX_TIMESTAMP(" . $before . ")-180";
            // $where_before .= "'" .$before. "'";
            $where_before .= ")) ";
         } else {
            $where_before = "";
         }


         if (isset($filters['CHECKSUM']) and $filters['CHECKSUM']) {
            $checksum = $filters['CHECKSUM'];
            $where_checksum = " AND ('" . $checksum . "' & snmp.CHECKSUM) ";
         } else {
            $where_checksum = "";
         }
         $where_condition = $where_ids . $where_exclude_ids . $where_deviceids . $where_exclude_deviceids . $where_checksum . $where_since . $where_before;
      } else {
         $where_condition = "";
      }


      $query = "SELECT DISTINCT snmp.ID FROM snmp, snmp_accountinfo
                        WHERE snmp.SNMPDEVICEID NOT LIKE '\\_%'
                        AND snmp.ID = snmp_accountinfo.SNMP_ID
                        $where_condition";
      $request = $this->db->query($query);

      if ($this->db->numrows($request)) {


         $count = $this->db->numrows($request);
         $query = "SELECT DISTINCT snmp.ID, snmp.NAME FROM snmp, snmp_accountinfo
                           WHERE snmp.SNMPDEVICEID NOT LIKE '\\_%'
                           AND snmp.ID = snmp_accountinfo.SNMP_ID
                           $where_condition
                           ORDER BY $order
                           $max_records  $offset";
         $request = $this->db->query($query);
         $this->getAccountInfoColumns();
         while ($snmpid = $this->db->fetch_assoc($request)) {
            $snmpids[] = $snmpid['ID'];
         }
         $res["TOTAL_COUNT"] = $count;
//         if (isset($options['DISPLAY']['CHECKSUM'])) {
//            $checksum = $options['DISPLAY']['CHECKSUM'];
//         } else {
//            $checksum = self::CHECKSUM_NONE;
//         }
//         if (isset($options['DISPLAY']['WANTED'])) {
//            $wanted = $options['DISPLAY']['WANTED'];
//         } else {
//            $wanted = self::WANTED_NONE;
//         }
         $complete = 1;
         if (isset($options['COMPLETE'])) {
            $complete = $options['COMPLETE'];
         }
         $res["SNMP"] = $this->getSnmpSections($snmpids, $complete);
      } else {


         $res = array();
      }

      return $res;
   }

}
