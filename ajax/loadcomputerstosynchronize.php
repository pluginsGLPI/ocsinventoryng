<?php
/*
 -------------------------------------------------------------------------
 Accesscontrols plugin for GLPI
 Copyright (C) 2009-2022 by the accesscontrols Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of accesscontrols.

 Accesscontrols is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Accesscontrols is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with accesscontrols. If not, see <http://www.gnu.org/licenses/>.
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
   $max_date = "0000-00-00 00:00:00";
   if (isset($server->fields["max_glpidate"])) {
      $max_date = $server->fields["max_glpidate"];
   }
   $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
   // Fetch linked computers from ocs
   $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);

   $ocsResult = $ocsClient->getComputers([
                                            //                                            'OFFSET'      => $start,
                                            //                                            'MAX_RECORDS' => $_SESSION['glpilist_limit'],
                                            'ORDER'    => 'LASTDATE',
                                            'COMPLETE' => '0',
                                            'FILTER'   => [
                                               //                                                  'IDS'               => $already_linked_ids,
                                               'CHECKSUM'          => $cfg_ocs["checksum"],
//                                               'INVENTORIED_BEFORE' => 'NOW()',
//                                               'INVENTORIED_BEFORE' => $max_date,
                                            ]
                                         ]);

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


