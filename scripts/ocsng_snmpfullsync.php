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

ini_set("memory_limit", "-1");
ini_set("max_execution_time", "0");

# Converts cli parameter to web parameter for compatibility
if (isset ($_SERVER["argv"]) && !isset ($argv)) {
   $argv = $_SERVER["argv"];
}
if ($argv) {
   for ($i = 1; $i < count($argv); $i++) {
      $it = explode("=", $argv[$i], 2);
      $it[0] = preg_replace('/^--/', '', $it[0]);
      if(isset($it[1])) {
         $_GET[$it[0]] = $it[1];
      } else {
         $_GET[$it[0]] = 1;
      }
   }
}

// Can't run on MySQL replicate
$USEDBREPLICATE = 0;
$DBCONNECTION_REQUIRED = 1;

// MASS IMPORT for OCSNG
include('../../../inc/includes.php');

$_SESSION["glpicronuserrunning"] = $_SESSION["glpiname"] = 'ocsinventoryng';
// Check PHP Version - sometime (debian) cli version != module version
if (phpversion() < "5") {
   die("PHP version:" . phpversion() . " - " . "You must install at least PHP5.\n\n");
}
// Chech Memory_limit - sometine cli limit (php-cli.ini) != module limit (php.ini)
$mem = Toolbox::getMemoryLimit();
if (($mem > 0) && ($mem < (64 * 1024 * 1024))) {
   die("PHP memory_limit = " . $mem . " - " . "A minimum of 64Mio is commonly required for GLPI.'\n\n");
}

//Check if plugin is installed
$plugin = new Plugin();
if (!$plugin->isInstalled("ocsinventoryng")) {
   echo "Disabled plugin\n";
   exit (1);
}

if (!$plugin->isActivated("ocsinventoryng")) {
   echo "Disabled plugin\n";
   exit (1);
}

$thread_nbr = '';
$threadid = '';
$ocsservers_id = -1;
$fields = array();

//Get script configuration
$config = new PluginOcsinventoryngConfig();
//$notimport = new PluginOcsinventoryngNotimportedcomputer();
$config->getFromDB(1);

if (!isset ($_GET["ocs_server_id"]) || ($_GET["ocs_server_id"] == '')) {
   $ocsservers_id = -1;
} else {
   $ocsservers_id = $_GET["ocs_server_id"];
}

if (isset($_GET["thread_nbr"]) || isset ($_GET["thread_id"])) {
   if (!isset($_GET["thread_id"])
      || ($_GET["thread_id"] > $_GET["thread_nbr"])
      || ($_GET["thread_id"] <= 0)
   ) {
      echo("Threadid invalid: threadid must be between 1 and thread_nbr\n\n");
      exit (1);
   }

   $thread_nbr = $_GET["thread_nbr"];
   $threadid = $_GET["thread_id"];

   echo "=====================================================\n";
   echo "\tThread #$threadid: starting ($threadid/$thread_nbr)\n";
} else {
   $thread_nbr = -1;
   $threadid = -1;
}

//Get the script's process identifier
if (isset ($_GET["process_id"])) {
   $fields["processid"] = $_GET["process_id"];
}
$thread = new PluginOcsinventoryngThread();

//Prepare datas to log in db
$fields["start_time"] = date("Y-m-d H:i:s");
$fields["threadid"] = $threadid;
$fields["status"] = PLUGIN_OCSINVENTORYNG_STATE_STARTED;
$fields["plugin_ocsinventoryng_ocsservers_id"] = $ocsservers_id;
$fields["synchronized_snmp_number"] = 0;
$fields["notupdated_snmp_number"] = 0;
$fields["total_number_machines"] = 0;
$fields["error_msg"] = '';
//TODO create thread & update it ?
//$tid = $thread->add($fields);
//$fields["id"] = $tid;
$tid = $threadid;

if ($ocsservers_id != -1) {
   $result = launchSync($tid, $ocsservers_id, $thread_nbr, $threadid, $fields, $config);
   if ($result) {
      $fields = $result;
   }
} else {
   //Import from all the OCS servers
   $query = "SELECT `id`, `name`
                FROM `glpi_plugin_ocsinventoryng_ocsservers`
                WHERE `is_active`
                  AND `use_massimport`";
   $res = $DB->query($query);

   while ($ocsservers = $DB->fetchArray($res)) {
      $result = launchSync($tid, $ocsservers["id"], $thread_nbr, $threadid, $fields, $config);
      if ($result) {
         $fields = $result;
      }
   }
}

//Write in db all the informations about this thread
// TODO create thread & update it ?
//$fields["total_number_machines"] = $fields["synchronized_snmp_number"]
//   + $fields["notupdated_snmp_number"];
//$fields["end_time"] = date("Y-m-d H:i:s");
//$fields["status"] = PLUGIN_OCSINVENTORYNG_STATE_FINISHED;
//$fields["error_msg"] = "";
//$thread->update($fields);

echo "\tThread #" . $threadid . ": done!!\n";
echo "=====================================================\n";
//}

/**
 * @param $threads_id
 * @param $ocsservers_id
 * @param $thread_nbr
 * @param $threadid
 * @param $fields
 * @param $config
 *
 * @return bool|mixed
 */
function launchSync($threads_id, $ocsservers_id, $thread_nbr, $threadid, $fields, $config)
{

   $server = new PluginOcsinventoryngServer();
   $ocsserver = new PluginOcsinventoryngOcsServer();

   if (!PluginOcsinventoryngOcsServer::checkOCSconnection($ocsservers_id)) {
      echo "\tThread #" . $threadid . ": cannot contact server\n\n";
      return false;
   }

   if (!$ocsserver->getFromDB($ocsservers_id)) {
      echo "\tThread #" . $threadid . ": cannot get OCS server information\n\n";
      return false;
   }

   if (!$server->getFromDBbyOcsServer($ocsservers_id)) {
      echo "\tThread #" . $threadid . ": cannot get server information\n\n";
      return false;
   }

   $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($ocsservers_id);

   return importSNMPFromOcsServer($threads_id, $cfg_ocs, $server, $thread_nbr,
      $threadid, $fields, $config);
}


/**
 * @param $threads_id
 * @param $cfg_ocs
 * @param $server
 * @param $thread_nbr
 * @param $threadid
 * @param $fields
 * @param $config
 *
 * @return mixed
 */
function importSNMPFromOcsServer($threads_id, $cfg_ocs, $server, $thread_nbr,
                                 $threadid, $fields, $config)
{
   global $DB;

   echo "\tThread #" . $threadid . ": synchronize SNMP objects from server: '" . $cfg_ocs["name"] . "'\n";

   $multiThread = false;
   if ($threadid != -1 && $thread_nbr > 1) {
      $multiThread = true;
   }

   $ocsServerId = $cfg_ocs['id'];
   $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($ocsServerId);

   $already_linked_query = "SELECT `glpi_plugin_ocsinventoryng_snmpocslinks`.`ocs_id`,`glpi_plugin_ocsinventoryng_snmpocslinks`.`id`
                               FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
                               WHERE `glpi_plugin_ocsinventoryng_snmpocslinks`.`plugin_ocsinventoryng_ocsservers_id`
                                            = '$ocsServerId'";
   $already_linked_result = $DB->query($already_linked_query);
   $already_linked_ocs_ids = array();

   if ($DB->numrows($already_linked_result) > 0) {
      while ($data = $DB->fetchAssoc($already_linked_result)) {
         $already_linked_ocs_ids [] = $data['ocs_id'];
//         $already_linked_ids [] = $data['id'];
      }
   }

   $ocsResult = $ocsClient->getSnmp(array(
      'ORDER'    => 'LASTDATE',
      'COMPLETE' => '0',
      'FILTER'   => array(
         'IDS' => $already_linked_ocs_ids,
      )
   ));

   //Unset SNMP objects not updated by OCS
   foreach ($ocsResult['SNMP'] as $ID => $snmpids) {
      
      
      $last_update = date('Y-m-d H:m:s');
      //Compute lastest synchronization date
      $query = "SELECT `last_update`
                FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
                WHERE `ocs_id` = '$ID'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            if ($DB->result($result, 0, 0) != '') {
               $last_update = $DB->result($result, 0, 0);
            }
         }
      }
   
      if ($snmpids['META']['LASTDATE'] < $last_update) {
         if (($key = array_search($ID, $already_linked_ocs_ids)) !== false) {
            unset($already_linked_ocs_ids[$key]);
         }
      }
   }

   $already_linked_ids = array();
   //List definitive SNMP objects to update
   if (count($already_linked_ocs_ids) > 0) {
      $query = "SELECT `glpi_plugin_ocsinventoryng_snmpocslinks`.`id`
                                  FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
                                  WHERE `ocs_id` IN (" . implode(",", $already_linked_ocs_ids) . ")";
      $result = $DB->query($query);


      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetchAssoc($result)) {
            $already_linked_ids [] = $data['id'];
         }
      }
   }
   $nb = count($already_linked_ids);

   echo "\tThread #$threadid: $nb object(s)\n";

   $fields["total_number_machines"] += $nb;

//   $thread = new PluginOcsinventoryngThread();
//   $notimport = new PluginOcsinventoryngNotimportedcomputer();

//   $i = 0;

   foreach ($already_linked_ids as $ID) {

      /* TODO create thread & update it ?
       * if ($i == $config->fields["thread_log_frequency"]) {
         $fields["status"] = PLUGIN_OCSINVENTORYNG_STATE_RUNNING;
         $thread->update($fields);
         $i = 0;
      } else {
         $i++;
      }*/

      echo ".";
      $action = PluginOcsinventoryngSnmpOcslink::updateSnmp($ID, $ocsServerId);
      PluginOcsinventoryngOcsProcess::manageImportStatistics($fields, $action['status']);

      /* TODO log it ?
      /*switch ($action['status']) {
         case PluginOcsinventoryngOcsProcess::SNMP_FAILED_IMPORT:
            $notimport->logNotImported($ocsServerId, $ID, $action);
            break;

         default:
            $notimport->cleanNotImported($ocsServerId, $ID);
            //Log detail
            $detail = new PluginOcsinventoryngDetail();
            $detail->logProcessedComputer($ID, $ocsServerId, $action, $threadid, $threads_id);
            break;
      }*/

   }
   return $fields;
}
