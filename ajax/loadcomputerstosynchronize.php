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

include('../../../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

$plugin_ocsinventoryng_ocsservers_id = $_POST["plugin_ocsinventoryng_ocsservers_id"];

$hardware["data"] = [];

if ($plugin_ocsinventoryng_ocsservers_id > 0) {

   $server = new PluginOcsinventoryngServer();
   $server->getFromDBbyOcsServer($plugin_ocsinventoryng_ocsservers_id);

   $config    = new PluginOcsinventoryngConfig();
   $config->getFromDB(1);
   $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
   // Fetch linked computers from ocs
   $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);

   $computerOptions = array(
      'COMPLETE' => '0',
      'ORDER'    => 'LASTDATE',
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
//   if ($server->fields["max_glpidate"] != '0000-00-00 00:00:00') {
//      $firstQueryOptions['FILTER']['INVENTORIED_BEFORE'] = $server->fields["max_glpidate"];
//   }

   $firstQueryOptions['FILTER']['CHECKSUM'] = intval($cfg_ocs["checksum"]);


   $ocsResult = $ocsClient->getComputers($firstQueryOptions);

   $computers = (isset($ocsResult['COMPUTERS']) ? $ocsResult['COMPUTERS'] : []);

   if (isset($computers)) {
      if (count($computers)) {
         // Get all hardware from OCS DB

         foreach ($computers as $computer) {
            $ID          = $computer['META']['ID'];
            $query_glpi  = "SELECT `glpi_plugin_ocsinventoryng_ocslinks`.`last_update` AS last_update,
                                    `glpi_plugin_ocsinventoryng_ocslinks`.`last_ocs_update` AS last_ocs_update,
                                  `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id` AS computers_id,
                                  `glpi_computers`.`serial` AS serial,
                                  `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid` AS ocsid,
                                  `glpi_computers`.`name` AS name,
                                  `glpi_plugin_ocsinventoryng_ocslinks`.`use_auto_update`,
                                  `glpi_plugin_ocsinventoryng_ocslinks`.`id`
                           FROM `glpi_plugin_ocsinventoryng_ocslinks`
                           LEFT JOIN `glpi_computers` ON (`glpi_computers`.`id`= `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id`)
                           WHERE `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id`
                                       = $plugin_ocsinventoryng_ocsservers_id
                                  AND `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid` = $ID
                           ORDER BY `glpi_plugin_ocsinventoryng_ocslinks`.`use_auto_update` DESC,
                                    `last_update`,
                                    `name`";
            $result_glpi = $DB->query($query_glpi);
            if ($DB->numrows($result_glpi) > 0) {
               while ($data = $DB->fetchAssoc($result_glpi)) {
                  if (strtotime($computer['META']["LASTDATE"]) > strtotime($data["last_update"])) {
                     $checksum_debug = "";
                     if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                        $checksum_server = intval($cfg_ocs["checksum"]);
                        $checksum_client = intval($computer['META']["CHECKSUM"]);
                        if ($checksum_server > 0
                            && $checksum_client > 0
                        ) {
                           $result         = $checksum_server & $checksum_client;
                           $checksum_debug = intval($result);
                        }
                     } else {
                        $checksum_debug = $computer['META']["CHECKSUM"];
                     }

                     $hardware["data"][] = [
                        'checked'        => "",
                        'id'             => $data["id"],
                        'ocsid'          => $data["ocsid"],
                        'name'           => addslashes($computer['META']["NAME"]),
                        'date'           => $computer['META']["LASTDATE"],
                        'TAG'            => $computer['META']["TAG"],
                        'computers_id'   => $data["computers_id"],
                        'serial'         => $data["serial"],
                        'last_update'    => $data["last_update"],
                        'checksum_debug' => $checksum_debug,
                     ];
                  }

               }
            }
         }
      }
   }
}

$json = json_encode($hardware);

echo $json;


