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
 * Class PluginOcsinventoryngWinuser
 */
class PluginOcsinventoryngWinuser extends CommonDBChild {

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
      return __('Windows Users', 'ocsinventoryng');
   }

   /**
    * Update config of the WinUsers
    *
    * This function erase old data and import the new ones about WinUser
    *
    * @param $computers_id integer : glpi computer id.
    * @param $ocsComputer
    * @param $history_plugins boolean
    * @param $force
    */
   static function updateWinuser($computers_id, $ocsComputer, $cfg_ocs, $force) {

      $uninstall_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_plugins'] == 1 || $cfg_ocs['history_plugins'] == 3)) {
         $uninstall_history = 1;
      }
      $install_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_plugins'] == 1 || $cfg_ocs['history_plugins'] == 2)) {
         $install_history = 1;
      }

      if ($force) {
         self::resetWinuser($computers_id, $uninstall_history);
      }
      $winusers = new self();
      //update data
      foreach ($ocsComputer as $wusers) {

         $wuser                 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($wusers));
         $input                 = [];
         $input["computers_id"] = $computers_id;
         if (!empty($wuser) && isset($wuser["NAME"])) {
            $input["name"]        = $wuser["NAME"];
            $input["type"]        = (isset($wuser["TYPE"]) ? $wuser["TYPE"] : '');
            $input["description"] = (isset($wuser["TYPE"]) ? $wuser["DESCRIPTION"] : '');
            $input["disabled"]    = (isset($wuser["TYPE"]) ? $wuser["DISABLED"] : '');
            $input["sid"]         = (isset($wuser["TYPE"]) ? $wuser["SID"] : '');

            $winusers->add($input, ['disable_unicity_check' => true], $install_history);
         }
      }
   }

   /**
    * Delete old Winuser entries
    *
    * @param $glpi_computers_id integer : glpi computer id.
    * @param $uninstall_history boolean
    */
   static function resetWinuser($glpi_computers_id, $uninstall_history) {

      $wuser = new self();
      $wuser->deleteByCriteria(['computers_id' => $glpi_computers_id], 1, $uninstall_history);

   }

   /**
    * @see CommonGLPI::getTabNameForItem()
    *
    * @param \CommonGLPI $item
    * @param int         $withtemplate
    *
    * @return array|string
    * @throws \GlpitestSQLError
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      $plugin_ocsinventoryng_ocsservers_id = PluginOcsinventoryngOcslink::getOCSServerForItem($item);
      if ($plugin_ocsinventoryng_ocsservers_id > 0
          && PluginOcsinventoryngOcsServer::serverIsActive($plugin_ocsinventoryng_ocsservers_id)) {

         $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
         // can exists for template
         if (($item->getType() == 'Computer')
             && Computer::canView()
             && $cfg_ocs["import_winusers"]) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
               $dbu = new DbUtils();
               $nb = $dbu->countElementsInTable('glpi_plugin_ocsinventoryng_winusers',
                                                ["computers_id" => $item->getID()]);
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
         }
         return '';
      }
      return '';
   }


   /**
    * @param $item            CommonGLPI object
    * @param $tabnum (default 1)
    * @param $withtemplate (default 0)
    *
    * @return bool|true
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $plugin_ocsinventoryng_ocsservers_id = PluginOcsinventoryngOcslink::getOCSServerForItem($item);
      if(!PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)){
         echo "<div class='spaced center'>";
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr class='noHover'><th colspan='5'>" . self::getTypeName(0) .
            "</th></tr>";
         echo "<tr class='tab_bg_2'><th colspan='5'>" . __('Server unreachable','ocsinventoryng') . "</th></tr>";
         echo "</table>";
         echo "</div>";
      }else {
         self::showForComputer($item, $withtemplate);
      }
      return true;
   }

   /**
    * Print the computers windows update states
    *
    * @param             $comp                  Computer object
    * @param bool|string $withtemplate boolean  Template or basic item (default '')
    *
    * @return bool
*/
   static function showForComputer(Computer $comp, $withtemplate = '') {
      global $DB;

      $ID = $comp->fields['id'];

      if (!$comp->getFromDB($ID)
          || !$comp->can($ID, READ)) {
         return false;
      }
      $canedit = $comp->canEdit($ID);

      echo "<div class='spaced center'>";

      if ($result = $DB->request('glpi_plugin_ocsinventoryng_winusers', ['computers_id' => $ID, 'ORDER' => 'type'])) {
         echo "<table class='tab_cadre_fixehov'>";
         $colspan = 5;
         echo "<tr class='noHover'><th colspan='$colspan'>" . self::getTypeName($result->numrows()) .
              "</th></tr>";

         if ($result->numrows() != 0) {

            $header = "<tr><th>" . __('Name') . "</th>";
            $header .= "<th>" . __('Type') . "</th>";
            $header .= "<th>" . __('Description') . "</th>";
            $header .= "<th>" . __('Disabled', 'ocsinventoryng') . "</th>";
            $header .= "<th>" . __('SID', 'ocsinventoryng') . "</th>";
            $header .= "</tr>";
            echo $header;

            Session::initNavigateListItems(__CLASS__,
               //TRANS : %1$s is the itemtype name,
               //        %2$s is the name of the item (used for headings of a list)
                                           sprintf(__('%1$s = %2$s'),
                                                   Computer::getTypeName(1), $comp->getName()));

            foreach ($result as $data) {
               echo "<tr class='tab_bg_2'>";
               echo "<td>" . $data['name'] . "</td>";
               echo "<td>" . $data['type'] . "</td>";
               echo "<td>" . $data['description'] . "</td>";
               echo "<td>" . $data['disabled'] . "</td>";
               echo "<td>" . $data['sid'] . "</td>";
               echo "</tr>";
               Session::addToNavigateListItems(__CLASS__, $data['id']);
            }
            echo $header;
         } else {
            echo "<tr class='tab_bg_2'><th colspan='$colspan'>" . __('No item found') . "</th></tr>";
         }

         echo "</table>";
      }
      echo "</div>";
   }
}