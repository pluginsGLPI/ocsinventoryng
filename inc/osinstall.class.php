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

class PluginOcsinventoryngOsinstall extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Computer';
   static public $items_id = 'computers_id';

   static $rightname = "plugin_ocsinventoryng";

   static function getTypeName($nb = 0) {
      return __('OS Informations', 'ocsinventoryng');
   }

   function cleanDBonPurge() {

      $self = new self();
      $self->deleteByCriteria(array('computers_id' => $this->fields['id']));

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
      if ($item->getField('itemtype') == 'Computer' && $computer->getFromDB($item->getField('items_id'))) {
         $plugin_ocsinventoryng_ocsservers_id = PluginOcsinventoryngOcslink::getOCSServerForItem($computer);
         if ($plugin_ocsinventoryng_ocsservers_id > 0) {
            PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
            $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
            // can exists for template
            if (
//               ($item->getType() == 'Item_OperatingSystem')
                //             && Computer::canView()
//                &&
            $cfg_ocs["import_osinstall"]
            ) {
               if ($result = $DB->request('glpi_plugin_ocsinventoryng_osinstalls', array('computers_id' => $computer->getID()))) {
                  echo "<table class='tab_cadre_fixe'>";
                  $colspan = 4;
                  echo "<tr class='noHover'><th colspan='$colspan'>" . self::getTypeName($result->numrows()) .
                       "</th></tr>";

                  if ($result->numrows() != 0) {

                     foreach ($result as $data) {
                        echo "<tr class='tab_bg_1'>";
                        echo "<td>" . __('Build version', 'ocsinventoryng') . "</td>";
                        echo "<td>" . $data['build_version'] . "</td>";
                        echo "<td>" . __('Installation date', 'ocsinventoryng') . "</td>";
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
                     echo "<tr class='tab_bg_2'><th colspan='$colspan'>" . __('No item found') . "</th></tr>";
                  }

                  echo "</table>";
               }
            }
         }
      }
   }
}