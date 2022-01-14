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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginOcsinventoryngTeamviewer
 */
class PluginOcsinventoryngTeamviewer extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Computer';
   static public $items_id = 'computers_id';

   static $rightname = "plugin_ocsinventoryng";

   static $tags = '[TWID]';

   /**
    * @param int $nb
    *
    * @return string
    */
   static function getTypeName($nb = 0) {
      return __('Teamviewer access', 'ocsinventoryng');
   }

   /**
    * Update config of the Teamviewer
    *
    * This function erase old data and import the new ones about Teamviewer
    *
    * @param $computers_id integer : glpi computer id.
    * @param $ocsComputer
    * @param $history_plugins boolean
    * @param $force
    */
   static function updateTeamviewer($computers_id, $ocsComputer, $cfg_ocs, $force) {


      $uninstall_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_plugins'] == 1 || $cfg_ocs['history_plugins'] == 3)) {
         $uninstall_history = 1;
      }
      $install_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_plugins'] == 1 || $cfg_ocs['history_plugins'] == 2)) {
         $install_history = 1;
      }

      if ($force) {
         self::resetTeamviewer($computers_id, $uninstall_history);
      }

      $CompTeam              = new self();
      $input                 = [];
      $input["computers_id"] = $computers_id;
      $input["version"]      = $ocsComputer["VERSION"];
      $input["twid"]         = $ocsComputer["TWID"];

      $CompTeam->add($input, ['disable_unicity_check' => true], $install_history);

   }

   /**
    * Delete old Teamviewer entries
    *
    * @param $glpi_computers_id integer : glpi computer id.
    * @param $uninstall_history boolean
    *
    */
   static function resetTeamviewer($glpi_computers_id, $uninstall_history) {

      $team = new self();
      $team->deleteByCriteria(['computers_id' => $glpi_computers_id], 1, $uninstall_history);

   }

   /**
    * @see CommonGLPI::getTabNameForItem()
    *
    * @param \CommonGLPI $item
    * @param int         $withtemplate
    *
    * @return array|string
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      $plugin_ocsinventoryng_ocsservers_id = PluginOcsinventoryngOcslink::getOCSServerForItem($item);
      if ($plugin_ocsinventoryng_ocsservers_id > 0
          && PluginOcsinventoryngOcsServer::serverIsActive($plugin_ocsinventoryng_ocsservers_id)) {
         $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
         // can exists for template
         if (($item->getType() == 'Computer')
             && Computer::canView()
             && $cfg_ocs["import_teamviewer"]) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
               $dbu = new DbUtils();
               $nb  = $dbu->countElementsInTable('glpi_plugin_ocsinventoryng_teamviewers',
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
    * @throws \GlpitestSQLError
*/
   static function showForComputer(Computer $comp, $withtemplate = '') {
      global $DB;

      $ID = $comp->fields['id'];

      if (!$comp->getFromDB($ID)
          || !$comp->can($ID, READ)) {
         return false;
      }

      echo "<div class='spaced center'>";

      if ($result = $DB->request('glpi_plugin_ocsinventoryng_teamviewers', ['computers_id' => $ID])) {
         echo "<table class='tab_cadre_fixehov'>";
         $colspan = 2;
         echo "<tr class='noHover'><th colspan='$colspan'>" . self::getTypeName($result->numrows()) .
              "</th></tr>";

         if ($result->numrows() != 0) {

            $header = "<tr><th>" . __('Teamviewer ID', 'ocsinventoryng') . "</th>";
            $header .= "<th>" . __('Version') . "</th>";
            $header .= "</tr>";
            echo $header;

            Session::initNavigateListItems(__CLASS__,
               //TRANS : %1$s is the itemtype name,
               //        %2$s is the name of the item (used for headings of a list)
                                           sprintf(__('%1$s = %2$s'),
                                                   Computer::getTypeName(1), $comp->getName()));

            foreach ($result as $data) {
               echo "<tr class='tab_bg_2'>";
               echo "<td>" . $data['twid'] . "</td>";
               echo "<td>" . $data['version'] . "</td>";
               echo "</tr>";
               Session::addToNavigateListItems(__CLASS__, $data['id']);

               $self = new self();
               $self->getFromDB($data['id']);
            }
            echo $header;

         } else {
            echo "<tr class='tab_bg_2'><th colspan='$colspan'>" . __('No item found') . "</th></tr>";
         }

         echo "</table></br>";

         if ($result->numrows() != 0) {
            $dbu      = new DbUtils();
            $restrict = $comp->getEntityID();
            $query    = "SELECT `glpi_links`.`id`,
                       `glpi_links`.`link` AS link,
                       `glpi_links`.`name` AS name ,
                       `glpi_links`.`data` AS data,
                       `glpi_links`.`open_window` AS open_window
                FROM `glpi_links`
                INNER JOIN `glpi_links_itemtypes`
                     ON `glpi_links`.`id` = `glpi_links_itemtypes`.`links_id`
                WHERE `glpi_links_itemtypes`.`itemtype`='PluginOcsinventoryngTeamviewer' " .
                        $dbu->getEntitiesRestrictRequest(" AND", "glpi_links", "entities_id",
                                                         $restrict, true) . "
                ORDER BY name";

            $result = $DB->query($query);

            if ($DB->numrows($result) > 0) {

               echo "<table class='tab_cadre_fixe'>";
               $colspan = 2;

               echo "<tr><th colspan='$colspan'>" . __('Teamviewer direct access', 'ocsinventoryng') . "</th></tr>";

               while ($data = $DB->fetchAssoc($result)) {

                  $links = Link::getAllLinksFor($self, $data);
                  foreach ($links as $link) {
                     echo "<tr class='tab_bg_2'>";
                     echo "<td class='center' colspan='$colspan'>$link</td></tr>";
                  }
               }

               echo "</table>";
            }
         }
      }
      echo "</div>";
   }

   /**
    * @param \Computer $comp
    * @param string    $withtemplate
    *
    * @return bool
    */
   static function showForSimpleForItem(Computer $comp, $withtemplate = '') {
      global $DB;

      $ID = $comp->fields['id'];

      if (!$comp->getFromDB($ID)
          || !$comp->can($ID, READ)) {
         return false;
      }

      if ($iterator = $DB->request('glpi_plugin_ocsinventoryng_teamviewers', ['computers_id' => $ID])) {

         if (count($iterator)) {
            $data = $iterator->next();

            $self = new self();
            $self->getFromDB($data['id']);

            $restrict = $comp->getEntityID();
            $dbu      = new DbUtils();
            $query    = "SELECT `glpi_links`.`id`,
                       `glpi_links`.`link` AS link,
                       `glpi_links`.`name` AS name ,
                       `glpi_links`.`data` AS data,
                       `glpi_links`.`open_window` AS open_window
                FROM `glpi_links`
                INNER JOIN `glpi_links_itemtypes`
                     ON `glpi_links`.`id` = `glpi_links_itemtypes`.`links_id`
                WHERE `glpi_links_itemtypes`.`itemtype`= 'PluginOcsinventoryngTeamviewer' " .
                        $dbu->getEntitiesRestrictRequest(" AND", "glpi_links", "entities_id",
                                                         $restrict, true) . "
                ORDER BY name";

            $result = $DB->query($query);

            if ($DB->numrows($result) > 0) {

               $colspan = 4;

               echo "<tr><th colspan='$colspan'>" . __('Teamviewer direct access', 'ocsinventoryng') . "</th></tr>";

               while ($data = $DB->fetchAssoc($result)) {

                  $links = Link::getAllLinksFor($self, $data);
                  foreach ($links as $link) {
                     echo "<tr class='tab_bg_2'>";
                     echo "<td class='center' colspan='$colspan'>$link</td></tr>";
                  }
               }
            }
         }
      }
   }

   /**
    * @param string     $link
    * @param CommonDBTM $item
    *
    * @return array
    */
   static function generateLinkContents($link, CommonDBTM $item) {

      if (strstr($link, "[TWID]")) {
         $link = str_replace("[TWID]", $item->fields['twid'], $link);
         return [$link];
      }

      return parent::generateLinkContents($link, $item);
   }
}
