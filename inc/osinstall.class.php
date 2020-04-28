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

/**
 * Class PluginOcsinventoryngOsinstall
 */
class PluginOcsinventoryngOsinstall extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Computer';
   static public $items_id = 'computers_id';

   static $rightname = "plugin_ocsinventoryng";

   /**
    * @param int $nb
    *
    * @return string
    */
   static function getTypeName($nb = 0) {
      return __('OS Informations', 'ocsinventoryng');
   }

   /**
    * Update config of the OSInstall
    *
    * This function erase old data and import the new ones about OSInstall
    *
    * @param $computers_id integer : glpi computer id.
    * @param $ocsComputer
    * @param $history_plugins boolean
    * @param $force
    */
   static function updateOSInstall($computers_id, $ocsComputer, $cfg_ocs, $force) {

      $uninstall_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_plugins'] == 1 || $cfg_ocs['history_plugins'] == 3)) {
         $uninstall_history = 1;
      }
      $install_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_plugins'] == 1 || $cfg_ocs['history_plugins'] == 2)) {
         $install_history = 1;
      }

      if ($force) {
         self::resetOSInstall($computers_id, $uninstall_history);
      }
      //update data
      if (!empty($ocsComputer)) {

         $os                     = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($ocsComputer));
         $input                  = [];
         $input["computers_id"]  = $computers_id;
         $input["build_version"] = $os["BUILDVER"];
         $input["install_date"]  = $os["INSTDATE"];
         $input["codeset"]       = $os["CODESET"];
         $input["countrycode"]   = $os["COUNTRYCODE"];
         $input["oslanguage"]    = $os["OSLANGUAGE"];
         $input["curtimezone"]   = $os["CURTIMEZONE"];
         $input["locale"]        = $os["LOCALE"];
         $osinstall              = new self();
         $osinstall->add($input, ['disable_unicity_check' => true], $install_history);
      }

   }

   /**
    * Delete old osinstall entries
    *
    * @param $glpi_computers_id integer : glpi computer id.
    * @param $uninstall_history boolean
    *
    */
   static function resetOSInstall($glpi_computers_id, $uninstall_history) {

      $os = new self();
      $os->deleteByCriteria(['computers_id' => $glpi_computers_id], 1, $uninstall_history);

   }

   /**
    * Show
    *
    * @param $params
    */
   public static function showForItem_OperatingSystem($params) {
      global $DB;

      $item     = $params['item'];
      $computer = new Computer();
      if ($item->getField('itemtype') == 'Computer'
          && $computer->getFromDB($item->getField('items_id'))) {
         $plugin_ocsinventoryng_ocsservers_id = PluginOcsinventoryngOcslink::getOCSServerForItem($computer);

         if ($plugin_ocsinventoryng_ocsservers_id > 0
             && PluginOcsinventoryngOcsServer::serverIsActive($plugin_ocsinventoryng_ocsservers_id)) {

            if(!PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)){
               return false;
            }
            $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);

            // Manage locks pictures
            $dbu = new DbUtils();
            $items_id = $item->getField('items_id');
            $query = "SELECT *
                      FROM `glpi_plugin_ocsinventoryng_ocslinks`
                      WHERE `computers_id` = $items_id " .
                     $dbu->getEntitiesRestrictRequest("AND", "glpi_plugin_ocsinventoryng_ocslinks");

            $result = $DB->query($query);
            $data['ocsid'] = $DB->result($result, 0, "ocsid");
            $data['id'] = $DB->result($result, 0, "id");
            $data['plugin_ocsinventoryng_ocsservers_id'] = $plugin_ocsinventoryng_ocsservers_id;
            PluginOcsinventoryngOcslink::showLockIcon($items_id, $data);
            // can exists for template
            if (//               ($item->getType() == 'Item_OperatingSystem')
                //             && Computer::canView()
            //                &&
            $cfg_ocs["import_osinstall"]) {
               if ($result = $DB->request('glpi_plugin_ocsinventoryng_osinstalls',
                                          ['computers_id' => $computer->getID()])) {
                  echo "<table class='tab_cadre_fixe'>";
                  $colspan = 4;
                  echo "<tr class='noHover'><th colspan='$colspan'>" . self::getTypeName($result->numrows()) .
                       "</th></tr>";

                  if ($result->numrows() != 0) {

                     foreach ($result as $data) {
                        echo "<tr class='tab_bg_1'>";
                        echo "<td>" . __('Build version', 'ocsinventoryng') . "</td>";
                        echo "<td>" . $data['build_version'] . "</td>";
                        echo "<td>" . __('Installation date') . "</td>";
                        echo "<td>" . $data['install_date'] . "</td>";
                        echo "</tr>";

                        echo "<tr class='tab_bg_1'>";
                        echo "<td>" . __('Codeset', 'ocsinventoryng') . "</td>";
                        echo "<td>" . $data['codeset'] . "</td>";
                        echo "<td>" . __('Country code', 'ocsinventoryng') . "</td>";
                        echo "<td>" . $data['countrycode'] . "</td>";
                        echo "</tr>";

                        echo "<tr class='tab_bg_1'>";
                        echo "<td>" . __('OS Language', 'ocsinventoryng') . "</td>";
                        echo "<td>" . $data['oslanguage'] . "</td>";
                        echo "<td>" . __('Current Timezone', 'ocsinventoryng') . "</td>";
                        echo "<td>" . $data['curtimezone'] . "</td>";
                        echo "</tr>";

                        echo "<tr class='tab_bg_1'>";
                        echo "<td>" . __('Locale', 'ocsinventoryng') . "</td>";
                        echo "<td>" . $data['locale'] . "</td>";
                        echo "<td colspan='2'></td></tr>";
                     }
                  } else {
                     echo "<tr class='tab_bg_2'><td colspan='$colspan'>" . __('No item found') . "</td></tr>";
                  }

                  echo "</table>";
               }
            }
         }
      }
   }
}