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
$entities_id                         = $_POST["entities_id"];
$advanced                            = $_POST["advanced"];

$hardware["data"] = [];

if ($plugin_ocsinventoryng_ocsservers_id > 0) {

   // Get all links between glpi and OCS
   $query_glpi     = "SELECT ocsid
                     FROM `glpi_plugin_ocsinventoryng_ocslinks`
                     WHERE `plugin_ocsinventoryng_ocsservers_id` = $plugin_ocsinventoryng_ocsservers_id";
   $result_glpi    = $DB->query($query_glpi);
   $already_linked = [];
   if ($DB->numrows($result_glpi) > 0) {
      while ($data = $DB->fetchArray($result_glpi)) {
         $already_linked [] = $data["ocsid"];
      }
   }

   $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);

   $computerOptions = ['COMPLETE' => '0',
                       'FILTER'   => [
                          'EXCLUDE_IDS' => $already_linked
                       ],
                       'DISPLAY'  => [
                          'CHECKSUM' => PluginOcsinventoryngOcsClient::CHECKSUM_BIOS
                                        | PluginOcsinventoryngOcsClient::CHECKSUM_NETWORK_ADAPTERS
                       ],
   ];

   if ($cfg_ocs["tag_limit"] and $tag_limit = explode("$", trim($cfg_ocs["tag_limit"]))) {
      $computerOptions['FILTER']['TAGS'] = $tag_limit;
   }

   if ($cfg_ocs["tag_exclude"] and $tag_exclude = explode("$", trim($cfg_ocs["tag_exclude"]))) {
      $computerOptions['FILTER']['EXCLUDE_TAGS'] = $tag_exclude;
   }

   $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
   //      $numrows   = $ocsClient->countComputers($computerOptions);

   //      if ($start != 0) {
   //         $computerOptions['OFFSET'] = $start;
   //      }
   //      $computerOptions['MAX_RECORDS'] = $_SESSION['glpilist_limit'];
   $ocsResult = $ocsClient->getComputers($computerOptions);

   $computers = (isset($ocsResult['COMPUTERS']) ? $ocsResult['COMPUTERS'] : []);

   if (isset($computers)) {
      if (count($computers)) {
         // Get all hardware from OCS DB

         foreach ($computers as $data) {

            $data  = Glpi\Toolbox\Sanitizer::sanitize($data);
            $id    = $data['META']['ID'];
            $input = [
               'itemtype'    => "Computer",
               'name'        => $data['META']["NAME"],
               'entities_id' => $entities_id,
               'serial'      => $data['BIOS']["SSN"] ?? '',
               'is_dynamic'  => 1,
               'ocsid'       => $id,
               'id'          => $id,
            ];

            $serial       = "";
            $model        = "";
            $manufacturer = "";
            if (isset($data['BIOS']) && count($data['BIOS'])) {
               $serial       = $data['BIOS']["SSN"];
               $model        = $data['BIOS']["SMODEL"];
               $manufacturer = $data['BIOS']["SMANUFACTURER"];
            }

            $ssnblacklist = Blacklist::getSerialNumbers();
            $ok           = 1;
            $msg          = "";
            if (!in_array($serial, $ssnblacklist)) {
               $msg = sprintf(__('%1$s : %2$s'), __('Serial number'), $serial);
            } else {
               $msg = "<span class='red'>";
               $msg .= sprintf(__('%1$s : %2$s'), __('Blacklisted serial number', 'ocsinventoryng'), $serial);
               $msg .= "</span>";
               $ok  = 0;
            }
            $uuidblacklist = Blacklist::getUUIDs();

            if (!in_array($data['META']['UUID'], $uuidblacklist)) {
               $msg .= "<br>";
               $msg .= sprintf(__('%1$s : %2$s'), __('UUID'), $data['META']["UUID"]);
            } else {
               $msg .= "<br>";
               $msg .= "<span class='red'>";
               $msg .= sprintf(__('%1$s : %2$s'), __('Blacklisted UUID', 'ocsinventoryng'), $data['META']["UUID"]);
               $msg .= "</span>";
               $ok  = 0;
            }
            if (isset($data['NETWORKS'])) {
               $networks = $data['NETWORKS'];

               $ipblacklist  = Blacklist::getIPs();
               $macblacklist = Blacklist::getMACs();

               foreach ($networks as $opt) {

                  if (isset($opt['MACADDR'])) {
                     if (!in_array($opt['MACADDR'], $macblacklist)) {
                        $msg .= "<br>";
                        $msg .= sprintf(__('%1$s : %2$s'), __('MAC'), $opt['MACADDR']);
                     } else {
                        $msg .= "<br>";
                        $msg .= "<span class='red'>";
                        $msg .= sprintf(__('%1$s : %2$s'), __('Blacklisted MAC', 'ocsinventoryng'), $opt['MACADDR']);
                        $msg .= "</span>";
                        //$ok = 0;
                     }
                     if (!in_array($opt['IPADDRESS'], $ipblacklist)) {
                        $msg .= " - ";
                        $msg .= sprintf(__('%1$s : %2$s'), __('IP'), $opt['IPADDRESS']);
                     } else {
                        $msg .= " - ";
                        $msg .= "<span class='red'>";
                        $msg .= sprintf(__('%1$s : %2$s'), __('Blacklisted IP', 'ocsinventoryng'), $opt['IPADDRESS']);
                        $msg .= "</span>";
                        //$ok = 0;
                     }
                  }
               }
            }

            $valTip = "&nbsp;" . Html::showToolTip(
                  $msg, [
                         'awesome-class' => 'fa-comments',
                         'display'       => false,
                         'autoclose'     => false,
                         'onclick'       => true
                      ]
               );

            $toimport_disable_unicity_check= "";
            $rule_matched         = "";
            $toimport_entities    = "";
            $toimport_recursive   = "";
            $computers_id_founded = "";
            if ($advanced) {

               $rec                            = "disable_unicity_check[" . $id . "]";
               $toimport_disable_unicity_check = Dropdown::showYesNo($rec, 0, -1, ['display' => false]);

               $rule      = new RuleImportEntityCollection();
               $recursive = isset($data["is_recursive"]) ? $data["is_recursive"] : 0;
               $datar     = $rule->processAllRules(['ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                                                    '_source'       => 'ocsinventoryng',
                                                    'is_recursive'  => $recursive,
                                                   ], ['is_recursive' => $recursive], ['ocsid' => $id, 'return' => true]);

               if (!isset($datar['entities_id']) || $datar['entities_id'] == -1) {
                  $datar['entities_id'] = -1;
               } else {
                  $tmprule = new RuleImportEntity();
                  if ($tmprule->can($datar['_ruleid'], READ)) {
                     $rule_matched = "<a href='" . $tmprule->getLinkURL() . "'>" . $tmprule->getName() . "</a>";
                  } else {
                     $rule_matched = $tmprule->getName();
                  }
               }
               $ent               = "toimport_entities[" . $id . "]";
               $toimport_entities = Entity::dropdown(['name'     => $ent,
                                                      'value'    => $datar['entities_id'],
                                                      'comments' => 0,
                                                      'display'  => false]);

               if (!isset($datar['is_recursive'])) {
                  $datar['is_recursive'] = 0;
               }
               $rec                = "toimport_recursive[" . $id . "]";
               $toimport_recursive = Dropdown::showYesNo($rec, $datar['is_recursive'], -1, ['display' => false]);
            }
            //Look for the computer using automatic link criterias as defined in OCSNG configuration
            if ($advanced) {
               $rulelink         = new RuleImportAssetCollection();
               $params           = ['entities_id'                         => $entities_id,
                                    'itemtype'                            => 'Computer',
                                    'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                                    'ocsid'                               => $id,
                                    'return'                              => true];
               $rulelink_results = $rulelink->processAllRules(Toolbox::stripslashes_deep($input), [], $params);
               $options          = ['name' => "tolink[" . $id . "]"];

               if (isset($rulelink_results['found_inventories'][0])
                   && $rulelink_results['found_inventories'][0] > 0) {
                  $options['value']  = $rulelink_results['found_inventories'][0];
                  $options['entity'] = $entities_id;

                  $options['width'] = "100%";

                  //                  if (isset($options['value']) && $options['value'] > 0) {
                  //
                  //                     $query  = "SELECT *
                  //                            FROM `glpi_plugin_ocsinventoryng_ocslinks`
                  //                            WHERE `computers_id` = '" . $options['value'] . "' ";
                  //                     $result = $DB->query($query);
                  //                     if ($DB->numrows($result) > 0) {
                  //                        $ko = 1;
                  //                     }
                  //                  }
                  $options['comments']  = false;
                  $options['display']   = false;
                  $computers_id_founded = Computer::dropdown($options);
               }
            }
            $hardware["data"][] = [
               'checked'                        => "",
               'id'                             => $data['META']["ID"],
               'name'                           => $data['META']["NAME"],
               'date'                           => $data['META']["LASTDATE"],
               'TAG'                            => $data['META']["TAG"],
               'serial'                         => $serial,
               'model'                          => $model,
               'manufacturer'                   => $manufacturer,
               'infos'                          => $valTip,
               'toimport_disable_unicity_check' => $toimport_disable_unicity_check,
               'rule_matched'                   => $rule_matched,
               'toimport_entities'              => $toimport_entities,
               'toimport_recursive'             => $toimport_recursive,
               'computers_id_founded'           => $computers_id_founded
            ];
         }
      }
   }
}

$json = json_encode($hardware);

echo $json;


