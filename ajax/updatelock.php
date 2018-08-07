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

include('../../../inc/includes.php');

Session::checkLoginUser();
if (isset($_POST['update_lock'])) {
   $comp = new Computer();
   if ($comp->getFromDB($_POST['computers_id'])) {
      PluginOcsinventoryngOcsServer::deleteInOcsArray($_POST['computers_id'], $_POST['field'], true);
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($_POST['plugin_ocsinventoryng_ocsservers_id']);
      $cfg_ocs   = PluginOcsinventoryngOcsServer::getConfig($_POST['plugin_ocsinventoryng_ocsservers_id']);
      $options   = [
         "DISPLAY" => [
            "CHECKSUM" => PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE | PluginOcsinventoryngOcsClient::CHECKSUM_BIOS,
         ]
      ];

      $locks = PluginOcsinventoryngOcsServer::getLocksForComputer($_POST['computers_id']);

      $ocsComputer = $ocsClient->getComputer($_POST['ocsid'], $options);
      $params      = ['computers_id'                        => $_POST['computers_id'],
                      'plugin_ocsinventoryng_ocsservers_id' => $_POST['plugin_ocsinventoryng_ocsservers_id'],
                      'cfg_ocs'                             => $cfg_ocs,
                      'computers_updates'                   => $locks,
                      'ocs_id'                              => $_POST['ocsid'],
                      'entities_id'                         => $comp->fields['entities_id'],
                      'dohistory'                           => $cfg_ocs['history_hardware'],
                      'HARDWARE'                            => $ocsComputer['HARDWARE'],
                      'BIOS'                                => $ocsComputer['BIOS'],
      ];

      if (array_key_exists($_POST['field'], PluginOcsinventoryngOcsServer::getHardwareLockableFields())) {
         PluginOcsinventoryngOcsServer::setComputerHardware($params);
      }
      if (array_key_exists($_POST['field'], PluginOcsinventoryngOcsServer::getBiosLockableFields())) {
         PluginOcsinventoryngOcsServer::updateComputerFromBios($params);
      }
      if (array_key_exists($_POST['field'], PluginOcsinventoryngOcsServer::getRuleLockableFields())) {
         $locations_id = 0;
         $groups_id    = 0;
         $contact      = (isset($ocsComputer['META']["USERID"])) ? $ocsComputer['META']["USERID"] : "";
         if (!empty($contact) && $cfg_ocs["import_general_contact"] > 0) {
            $query  = "SELECT `id`
                            FROM `glpi_users`
                            WHERE `name` = '" . $contact . "';";
            $result = $DB->query($query);

            if ($DB->numrows($result) == 1) {
               $user_id = $DB->result($result, 0, 0);
               $user    = new User();
               $user->getFromDB($user_id);
               if ($cfg_ocs["import_user_location"] > 0) {
                  $locations_id = $user->fields["locations_id"];
               }
               if ($cfg_ocs["import_user_group"] > 0) {
                  $groups_id = self::getUserGroup($comp->fields["entities_id"],
                                                  $user_id,
                                                  '`is_itemgroup`',
                                                  true);
               }
            }
         }
         $rule = new RuleImportEntityCollection();

         $data = $rule->processAllRules(['ocsservers_id' => $_POST["plugin_ocsinventoryng_ocsservers_id"],
                                         '_source'       => 'ocsinventoryng',
                                         'locations_id'  => $locations_id,
                                         'groups_id'     => $groups_id],
                                        ['locations_id' => $locations_id,
                                         'groups_id'    => $groups_id],
                                        ['ocsid' => $_POST["ocsid"]]);

         PluginOcsinventoryngOcsServer::updateComputerFields($params, $data, $cfg_ocs);
      }
   }
}
