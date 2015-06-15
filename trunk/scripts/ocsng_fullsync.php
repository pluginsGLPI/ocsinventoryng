<?php
/*
 * @version $Id: HEADER 15930 2012-12-15 11:10:55Z tsmr $
-------------------------------------------------------------------------
Ocsinventoryng plugin for GLPI
Copyright (C) 2012-2013 by the ocsinventoryng plugin Development Team.

https://forge.indepnet.net/projects/ocsinventoryng
-------------------------------------------------------------------------

LICENSE

This file is part of ocsinventoryng.

Ocsinventoryng plugin is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

Ocsinventoryng plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with ocsinventoryng. If not, see <http://www.gnu.org/licenses/>.
-------------------------------------------------------------------------- */

ini_set("memory_limit", "-1");
ini_set("max_execution_time", "0");

# Converts cli parameter to web parameter for compatibility
if (isset ($_SERVER["argv"]) && !isset ($argv)) {
   $argv = $_SERVER["argv"];
}
if ($argv) {
   for ($i = 1 ; $i < count($argv) ; $i++) {
      $it = explode("=", $argv[$i], 2);
      $it[0] = preg_replace('/^--/', '', $it[0]);
      $_GET[$it[0]] = $it[1];
   }
}

// Can't run on MySQL replicate
$USEDBREPLICATE        = 0;
$DBCONNECTION_REQUIRED = 1;

// MASS IMPORT for OCSNG
include ('../../../inc/includes.php');

$_SESSION["glpicronuserrunning"] = $_SESSION["glpiname"]= 'ocsinventoryng';
// Check PHP Version - sometime (debian) cli version != module version
if (phpversion() < "5") {
   die("PHP version:".phpversion()." - "."You must install at least PHP5.\n\n");
}
// Chech Memory_limit - sometine cli limit (php-cli.ini) != module limit (php.ini)
$mem = Toolbox::getMemoryLimit();
if (($mem > 0) && ($mem < (64 * 1024 * 1024))) {
   die("PHP memory_limit = ".$mem." - "."A minimum of 64Mio is commonly required for GLPI.'\n\n");
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

$thread_nbr    = '';
$threadid      = '';
$ocsservers_id = -1;
$fields        = array ();

//Get script configuration
$config     = new PluginOcsinventoryngConfig();
$notimport  = new PluginOcsinventoryngNotimportedcomputer();
$config->getFromDB(1);

if (!isset ($_GET["ocs_server_id"]) || ($_GET["ocs_server_id"] == '')) {
   $ocsservers_id = -1;
} else {
   $ocsservers_id = $_GET["ocs_server_id"];
}

if (isset ($_GET["managedeleted"]) && ($_GET["managedeleted"] == 1)) {
   //echo "=====================================================\n";
   //echo "\tClean old Not Imported machine list (" . $notimport->cleanNotImported($ocsservers_id) . ")\n";

   if ($ocsservers_id != -1) {
      FirstPass($ocsservers_id);
   } else {
      echo "\tManage delete items in all OCS server\n";
      echo "=====================================================\n";

      //Import from all the OCS servers
      $query = "SELECT `id`, `name`
                FROM `glpi_plugin_ocsinventoryng_ocsservers`
                WHERE `is_active`
                  AND `use_massimport`";
      $result = $DB->query($query);

      echo "=====================================================\n";
      while ($ocsservers = $DB->fetch_array($result)) {
         FirstPass($ocsservers["id"]);
      }
      echo "=====================================================\n";
   }
} else { // not managedeleted
   if (isset($_GET["thread_nbr"]) || isset ($_GET["thread_id"])) {
      if (!isset($_GET["thread_id"])
                 || ($_GET["thread_id"] > $_GET["thread_nbr"])
                 || ($_GET["thread_id"] <= 0)) {
         echo ("Threadid invalid: threadid must be between 1 and thread_nbr\n\n");
         exit (1);
      }

      $thread_nbr = $_GET["thread_nbr"];
      $threadid   = $_GET["thread_id"];

      echo "=====================================================\n";
      echo "\tThread #$threadid: starting ($threadid/$thread_nbr)\n";
   } else {
      $thread_nbr = -1;
      $threadid   = -1;
   }

   //Get the script's process identifier
   if (isset ($_GET["process_id"])) {
      $fields["processid"] = $_GET["process_id"];
   }
   $thread = new PluginOcsinventoryngThread();

   //Prepare datas to log in db
   $fields["start_time"]                           = date("Y-m-d H:i:s");
   $fields["threadid"]                             = $threadid;
   $fields["status"]                               = PLUGIN_OCSINVENTORYNG_STATE_STARTED;
   $fields["plugin_ocsinventoryng_ocsservers_id"]  = $ocsservers_id;
   $fields["imported_machines_number"]             = 0;
   $fields["synchronized_machines_number"]         = 0;
   $fields["not_unique_machines_number"]           = 0;
   $fields["failed_rules_machines_number"]         = 0;
   $fields["notupdated_machines_number"]           = 0;
   $fields["not_unique_machines_number"]           = 0;
   $fields["linked_machines_number"]               = 0;
   $fields["link_refused_machines_number"]         = 0;
   $fields["total_number_machines"]                = 0;
   $fields["error_msg"]                            = '';

   $tid           = $thread->add($fields);
   $fields["id"]  = $tid;

   if ($ocsservers_id != -1) {
      $result = SecondPass($tid,$ocsservers_id, $thread_nbr, $threadid, $fields, $config);
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

      while ($ocsservers = $DB->fetch_array($res)) {
         $result = SecondPass($tid,$ocsservers["id"], $thread_nbr, $threadid, $fields, $config);
         if ($result) {
            $fields = $result;
         }
      }
   }

   //Write in db all the informations about this thread
   $fields["total_number_machines"] = $fields["imported_machines_number"]
                                      + $fields["synchronized_machines_number"]
                                      + $fields["failed_rules_machines_number"]
                                      + $fields["linked_machines_number"]
                                      + $fields["notupdated_machines_number"]
                                      + $fields["not_unique_machines_number"]
                                      + $fields["link_refused_machines_number"];
   $fields["end_time"]  = date("Y-m-d H:i:s");
   $fields["status"]    = PLUGIN_OCSINVENTORYNG_STATE_FINISHED;
   $fields["error_msg"] = "";
   $thread->update($fields);

   echo "\tThread #" . $threadid . ": done!!\n";
   echo "=====================================================\n";
}

/**
 * @param $ocsservers_id   integer  the OCS server id
**/
function FirstPass($ocsservers_id) {
   global $DB;

   if (PluginOcsinventoryngOcsServer::checkOCSconnection($ocsservers_id)) {
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($ocsservers_id);
      
      // Compute lastest new computer
      $ocsResult = $ocsClient->getComputers(array(
            'MAX_RECORDS' => 1,
            'ORDER' => 'ID DESC',
      ));
      
      if (count($ocsResult['COMPUTERS'])) {
         $max_id = key($ocsResult['COMPUTERS']);
      } else {
         $max_id = 0;
      }

      // Compute lastest synchronization date
      $query = "SELECT MAX(`last_ocs_update`)
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `plugin_ocsinventoryng_ocsservers_id` = '$ocsservers_id'";
      $max_date = "0000-00-00 00:00:00";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            if ($DB->result($result, 0, 0) != '') {
               $max_date = $DB->result($result, 0, 0);
            }
         }
      }

      // Store result for second pass (multi-thread)
      $server                                         = new PluginOcsinventoryngServer();
      $fields["max_ocsid"]                            = $max_id;
      $fields["max_glpidate"]                         = $max_date;
      $fields["plugin_ocsinventoryng_ocsservers_id"]  = $ocsservers_id;

      if ($server->getFromDBbyOcsServer($ocsservers_id)) {
         $fields["id"] = $server->fields["id"];
         $server->update($fields);
      } else {
         $fields["id"] = $server->add($fields);
      }

      // Handle ID changed or PC deleted in OCS.
      $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($ocsservers_id);
      echo "\tManage delete items in OCS server #$ocsservers_id: \"" . $cfg_ocs["name"] . "\"\n";
      PluginOcsinventoryngOcsServer::manageDeleted($ocsservers_id);
   } else {
      echo "*** Can't connect to OCS server #$ocsservers_id ***";
   }
}


/**
 * @param $threads_id
 * @param $ocsservers_id
 * @param $thread_nbr
 * @param $threadid
 * @param $fields
 * @param $config
**/
function SecondPass($threads_id, $ocsservers_id, $thread_nbr, $threadid, $fields, $config) {

   $server  = new PluginOcsinventoryngServer();
   $ocsserver  = new PluginOcsinventoryngOcsServer();

   if (!PluginOcsinventoryngOcsServer::checkOCSconnection($ocsservers_id)) {
      echo "\tThread #" . $threadid . ": cannot contact server\n\n";
      return false;
   }

   if (!$ocsserver->getFromDB($ocsservers_id)) {
      echo "\tThread #" . $threadid .": cannot get OCS server information\n\n";
      return false;
   }

   if (!$server->getFromDBbyOcsServer($ocsservers_id)) {
      echo "\tThread #" . $threadid .": cannot get server information\n\n";
      return false;
   }

   $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($ocsservers_id);

   return plugin_ocsinventoryng_importFromOcsServer($threads_id, $cfg_ocs, $server, $thread_nbr,
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
**/
function plugin_ocsinventoryng_importFromOcsServer($threads_id, $cfg_ocs, $server, $thread_nbr,
                                                  $threadid, $fields, $config) {
   echo "\tThread #" . $threadid . ": import computers from server: '" . $cfg_ocs["name"] . "'\n";

   $multiThread = false;
   if ($threadid != -1 && $thread_nbr > 1) {
      $multiThread = true;
   }
   
   $ocsServerId = $cfg_ocs['id'];
   $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($ocsServerId);
   $ocsComputers = array();
   
   // Build common options
   //$inventoriedBefore = new DateTime('@'.(time() - 180));

   $computerOptions = array(
         'FILTER' => array(
         'INVENTORIED_BEFORE' => "NOW())-180",
         )
   );
   
   // Limit the number of imported records according to config
   if ($config->fields["import_limit"] > 0) {
      $computerOptions['MAX_RECORDS'] = $config->fields["import_limit"];
   }

   // Filter tags according to config
   if ($cfg_ocs["tag_limit"] and $tag_limit = explode("$", trim($cfg_ocs["tag_limit"]))) {
      $computerOptions['FILTER']['TAGS'] = $tag_limit;
   }
   
   if ($cfg_ocs["tag_limit"] and $tag_exclude = explode("$", trim($cfg_ocs["tag_exclude"]))) {
      $computerOptions['FILTER']['EXCLUDE_TAGS'] = $tag_exclude;
   }
   
   // Get newly inventoried computers
   $firstQueryOptions = $computerOptions;
   if ($server->fields["max_glpidate"] != '0000-00-00 00:00:00') {
      $firstQueryOptions['FILTER']['INVENTORIED_SINCE'] = $server->fields["max_glpidate"];
   }
   
   $ocsResult = $ocsClient->getComputers($firstQueryOptions);
	
	// Get computers for which checksum has changed
   $secondQueryOptions = $computerOptions;
   
   // Filter only useful computers
   // Some conditions can't be sent to OCS, so we have to do this in a loop
   // Maybe add this to SOAP ?
   if (isset($ocsResult['COMPUTERS'])) {
	   $excludeIds = array();
	   foreach ($ocsResult['COMPUTERS'] as $ID => $computer) {
		  if ($ID <= intval($server->fields["max_ocsid"]) and (!$multiThread or ($ID % $thread_nbr) == ($threadid - 1))) {
			 $ocsComputers[$ID] = $computer;
		  }
		  $excludeIds []= $ID;
	   }
	   
	   $secondQueryOptions['FILTER']['EXLUDE_IDS'] = $excludeIds;
   }
   
   
   $secondQueryOptions['FILTER']['CHECKSUM'] = intval($cfg_ocs["checksum"]);
   
   $ocsResult = $ocsClient->getComputers($secondQueryOptions);
   
   // Filter only useful computers
   // Some conditions can't be sent to OCS, so we have to do this in a loop
   // Maybe add this to SOAP ?
   if (isset($ocsResult['COMPUTERS'])) {
	   if (isset($ocsResult['COMPUTERS'])) {
		  foreach ($ocsResult['COMPUTERS'] as $ID => $computer) {
			 if ($ID <= intval($server->fields["max_ocsid"]) and (!$multiThread or ($ID % $thread_nbr) == ($threadid - 1))) {
				$ocsComputers[$ID] = $computer;
			 }
		  }
	   }
	   // Limit the number of imported records according to config
	   if ($config->fields["import_limit"] > 0 and count($ocsComputers) > $config->fields["import_limit"]) {
		  $ocsComputers = array_splice($ocsComputers, $config->fields["import_limit"]);
	   }
   }
   $nb = count($ocsComputers);
   echo "\tThread #$threadid: $nb computer(s)\n";

   $fields["total_number_machines"] += $nb;

   $thread     = new PluginOcsinventoryngThread();
   $notimport  = new PluginOcsinventoryngNotimportedcomputer();
   
   $i = 0;
   foreach ($ocsComputers as $ID => $ocsComputer) {
      if ($i == $config->fields["thread_log_frequency"]) {
         $fields["status"] = PLUGIN_OCSINVENTORYNG_STATE_RUNNING;
         $thread->update($fields);
         $i = 0;
      } else {
         $i++;
      }

      echo ".";
      $entities_id = 0;
      $action = PluginOcsinventoryngOcsServer::processComputer($ID, $ocsServerId, 1);
      PluginOcsinventoryngOcsServer::manageImportStatistics($fields, $action['status']);

      switch ($action['status']) {
         case PluginOcsinventoryngOcsServer::COMPUTER_NOT_UNIQUE :
         case PluginOcsinventoryngOcsServer::COMPUTER_FAILED_IMPORT:
         case PluginOcsinventoryngOcsServer::COMPUTER_LINK_REFUSED:
            $notimport->logNotImported($ocsServerId, $ID, $action);
            break;

         default:
            $notimport->cleanNotImported($ocsServerId, $ID);
            //Log detail
            $detail = new PluginOcsinventoryngDetail();
            $detail->logProcessedComputer($ID, $ocsServerId, $action, $threadid, $threads_id);
            break;
      }

   }
   return $fields;
}
?>