<?php
/*
 * @version $Id$
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
 --------------------------------------------------------------------------
 */


ini_set("memory_limit","-1");
ini_set("max_execution_time", "0");

if (is_array($_SERVER['argv'])) {
   for ($i=1 ; $i<$_SERVER['argc'] ; $i++) {
      $it           = explode("=",$_SERVER['argv'][$i],2);
      $it[0]        = preg_replace('/^--/','',$it[0]);
      $_GET[$it[0]] = (isset($it[1]) ? $it[1] : true);
   }
}

include ('../../../inc/includes.php');

$CFG_GLPI["debug"] = 0;
restore_error_handler();
ini_set('display_errors','On');


if (isset($_GET["help"])) {
   echo "Usage : php remove_from_ocs.php ocs_server_id=<server_id> mode=[list|run] nb_month=xx \n";
   exit(0);
}

if (!isset($_GET["ocs_server_id"]) || intval($_GET["ocs_server_id"])<1) {
   echo "No or bad ocs_server_id option\n";
   exit(1);
}

if (!isset($_GET["mode"]) || !in_array($_GET["mode"], array('list','run'))) {
   echo "No or bad mode option\n";
   exit(1);
}

if (!isset($_GET["nb_month"]) || intval($_GET["nb_month"])<1) {
   echo "No or bad nb_month option\n";
   exit(1);
}

$_GET["ocs_server_id"] = intval($_GET["ocs_server_id"]);
$_GET["nb_month"]      = intval($_GET["nb_month"]);

$ocs_ids = getMachinesToRemove($_GET["ocs_server_id"], $_GET["nb_month"]);

if (empty($ocs_ids)) {
   echo "No machines to remove !\n";
   exit(2);
}

if ($_GET["mode"] == "list") {
   foreach($ocs_ids as $id=>$toremove) {
      echo "OCSID=".$id.", Name=".$toremove["name"].", Lastcome=".$toremove["lastcome"]."\n";
   }
   echo "Total: ".count($ocs_ids)."\n";

} else if ($_GET["mode"] == "run") {
   $cpt = removeMachinesFromOcs($_GET["ocs_server_id"],$ocs_ids);
}
exit (0);



//--------------Functions-----------------//
function getMachinesToRemove($ocs_server_id, $nb_month) {
   global $DB, $PluginOcsinventoryngDBocs;

   PluginOcsinventoryngOcsServer::checkOCSconnection($ocs_server_id) or die ("No OCS connection\n");

   $ocs_ids = array();

   $res_ocs = $PluginOcsinventoryngDBocs->query("SELECT `ID`, `NAME`, `WORKGROUP`, `LASTCOME`
                                                 FROM `hardware`
                                                 WHERE `LASTCOME` <= DATE_ADD(NOW(),
                                                          INTERVAL -" . $nb_month . " MONTH)");
   $sql_in = "`ocsservers_id` = '$ocs_server_id'
              AND `ocsid` IN (";

   if ($PluginOcsinventoryngDBocs->numrows($res_ocs) > 0) {
      $first = true;

      while ($ocs_machine = $DB->fetch_array($res_ocs)) {
         if ($first) {
            $first = false;
         } else {
            $sql_in .= ",";
         }

         $ocs_ids[$ocs_machine["ID"]] = array("name"     => $ocs_machine["NAME"].'.'.$ocs_machine["WORKGROUP"],
                                              "lastcome" => $ocs_machine["LASTCOME"]);
         $sql_in .= $ocs_machine["ID"];
      }

      $sql_in .= ")";
      if (!$first) {
         $res_glpi = $DB->query("SELECT `ocsid`
                                 FROM `glpi_plugin_ocsinventoryng_ocslinks`
                                 WHERE ".$sql_in);

         while ($glpi_machine = $DB->fetch_array($res_glpi)) {
            unset($ocs_ids[$glpi_machine["ocsid"]]);
         }
      }
   }

   return $ocs_ids;
}


/**
 * Remove a machine from OCS
 * This code comes from OCS Inventory ocsreports
 */
function removeMachinesFromOcs($ocs_server_id, $ocs_ids) {
   global $PluginOcsinventoryngDBocs;

   $cpt = 0;
   PluginOcsinventoryngOcsServer::checkOCSconnection($ocs_server_id) or die ("No OCS connection\n");

   $config = new PluginOcsinventoryngOcsServer();
   $config->getFromDB($ocs_server_id);

   if (count($ocs_ids) > 0) {
      $where = " IN (";
      $first = true;

      foreach ($ocs_ids as $id => $value) {
         $where .= (!$first?",":"").$id;
         $first  = false;
      }
      $where .= ")";

      $tables = array("accesslog", "accountinfo", "bios", "controllers", "drives", "inputs",
                      "memories", "modems", "monitors", "networks", "ports", "printers", "registry",
                      "slots", "softwares", "sounds", "storages", "videos", "devices",
                      "download_history");

      if ($config->fields['ocs_version'] > PluginOcsinventoryngOcsServer::OCS1_3_VERSION_LIMIT) {
         $tables[] = 'virtualmachines';
      }

      //First try to remove all the network ports
      $PluginOcsinventoryngDBocs->query("DELETE
                                         FROM `netmap`
                                         WHERE `MAC` IN (SELECT `MACADDR`
                                                         FROM `networks`
                                                         WHERE `networks`.`HARDWARE_ID` $where)");

      if ($cpt = $PluginOcsinventoryngDBocs->affected_rows()) {
         printf("Table %-16s: %10d rows\n", 'netmap', $cpt);
      }

      foreach ($tables as $table) {
         $PluginOcsinventoryngDBocs->query("DELETE
                                            FROM `".$table ."`
                                            WHERE `HARDWARE_ID` ".$where);

         if ($cpt = $PluginOcsinventoryngDBocs->affected_rows()) {
            printf("Table %-16s: %10d rows\n", $table, $cpt);
         }
      }

      $PluginOcsinventoryngDBocs->query("DELETE
                                         FROM `hardware`
                                         WHERE `ID` ".$where);
      $cpt = $PluginOcsinventoryngDBocs->affected_rows();
      printf("Table %-16s: %10d rows\n", 'hardware', $cpt);
   }
   return $cpt;
}
?>
