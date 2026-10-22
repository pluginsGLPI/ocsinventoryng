<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2025 by the ocsinventoryng Development Team.

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


use GlpiPlugin\Ocsinventoryng\OcsServer;
use GlpiPlugin\Ocsinventoryng\Config;
use GlpiPlugin\Ocsinventoryng\Server;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

// Same right as the page of the list (front/ocsng.sync.php)
Session::checkLoginUser();
Session::checkRight("plugin_ocsinventoryng_sync", READ);

// A server allowed for the active profile
$plugin_ocsinventoryng_ocsservers_id = OcsServer::checkAllowedServer($_POST["plugin_ocsinventoryng_ocsservers_id"] ?? 0);

$hardware["data"] = [];

global $DB;

if ($plugin_ocsinventoryng_ocsservers_id > 0) {

   $server = new Server();
   $server->getFromDBbyOcsServer($plugin_ocsinventoryng_ocsservers_id);

   $config    = new Config();
   $config->getFromDB(1);
   $cfg_ocs = OcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
   // Fetch linked computers from ocs
   $ocsClient = OcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);

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

   $firstQueryOptions['FILTER']['CHECKSUM'] = intval($cfg_ocs["checksum"]);


   $ocsResult = $ocsClient->getComputers($firstQueryOptions);

   $computers = (isset($ocsResult['COMPUTERS']) ? $ocsResult['COMPUTERS'] : []);

   if (count($computers)) {
      foreach ($computers as $computer) {
         // Links of the computers of the active entities only
         $iterator = $DB->request([
            'SELECT'    => [
               'glpi_plugin_ocsinventoryng_ocslinks.last_update AS last_update',
               'glpi_plugin_ocsinventoryng_ocslinks.last_ocs_update AS last_ocs_update',
               'glpi_plugin_ocsinventoryng_ocslinks.computers_id AS computers_id',
               'glpi_computers.serial AS serial',
               'glpi_plugin_ocsinventoryng_ocslinks.ocsid AS ocsid',
               'glpi_computers.name AS name',
               'glpi_plugin_ocsinventoryng_ocslinks.use_auto_update',
               'glpi_plugin_ocsinventoryng_ocslinks.id',
            ],
            'FROM'      => 'glpi_plugin_ocsinventoryng_ocslinks',
            'LEFT JOIN' => [
               'glpi_computers' => [
                  'ON' => [
                     'glpi_computers'                      => 'id',
                     'glpi_plugin_ocsinventoryng_ocslinks' => 'computers_id',
                  ],
               ],
            ],
            'WHERE'     => [
               'glpi_plugin_ocsinventoryng_ocslinks.plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
               'glpi_plugin_ocsinventoryng_ocslinks.ocsid'                               => (int) $computer['META']['ID'],
            ] + getEntitiesRestrictCriteria('glpi_computers'),
            'ORDER'     => [
               'glpi_plugin_ocsinventoryng_ocslinks.use_auto_update DESC',
               'last_update',
               'name',
            ],
         ]);
         foreach ($iterator as $data) {
            if (strtotime($computer['META']["LASTDATE"]) > strtotime($data["last_update"])) {
               $checksum_debug = "";
               if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                  $checksum_server = intval($cfg_ocs["checksum"]);
                  $checksum_client = intval($computer['META']["CHECKSUM"]);
                  if ($checksum_server > 0
                      && $checksum_client > 0
                  ) {
                     $checksum_debug = intval($checksum_server & $checksum_client);
                  }
               } else {
                  $checksum_debug = $computer['META']["CHECKSUM"];
               }

               // DataTables writes the cells as HTML: the values reported by the agents are escaped
               $hardware["data"][] = [
                  'checked'        => "",
                  'id'             => (int) $data["id"],
                  'ocsid'          => (int) $data["ocsid"],
                  'name'           => htmlescape((string) $computer['META']["NAME"]),
                  'date'           => htmlescape((string) $computer['META']["LASTDATE"]),
                  'TAG'            => htmlescape((string) $computer['META']["TAG"]),
                  'computers_id'   => (int) $data["computers_id"],
                  'serial'         => htmlescape((string) $data["serial"]),
                  'last_update'    => htmlescape((string) $data["last_update"]),
                  'checksum_debug' => htmlescape((string) $checksum_debug),
               ];
            }
         }
      }
   }
}

echo json_encode($hardware);
