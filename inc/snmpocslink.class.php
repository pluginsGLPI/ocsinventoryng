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
 * Class PluginOcsinventoryngSnmpOcslink
 */
class PluginOcsinventoryngSnmpOcslink extends CommonDBTM
{

   static $snmptypes = array('Computer', 'NetworkEquipment', 'Peripheral', 'Phone', 'Printer');
   static $rightname = "plugin_ocsinventoryng";
   /** @const */
   private static $CARTRIDGE_COLOR_CYAN = array('cyan');
   private static $CARTRIDGE_COLOR_MAGENTA = array('magenta');
   private static $CARTRIDGE_COLOR_YELLOW = array('yellow', 'jaune');
   private static $CARTRIDGE_COLOR_BLACK = array('black', 'noir');
   const OTHER_DATA = 'other';

   /**
    * @see inc/CommonGLPI::getTabNameForItem()
    *
    * @param $item               CommonGLPI object
    * @param$withtemplate (default 0)
    *
    * @return string|translated
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {

      if (in_array($item->getType(), self::$snmptypes)
         && $this->canView()
      ) {
         if ($this->getFromDBByQuery("WHERE `items_id` = '" . $item->getID() . "' 
                                       AND `itemtype` = '" . $item->getType() . "'")
         ) {
            return __('OCSNG SNMP', 'ocsinventoryng');
         }
      } else if ($item->getType() == "PluginOcsinventoryngOcsServer") {

         if (PluginOcsinventoryngOcsServer::checkOCSconnection($item->getID())
            && PluginOcsinventoryngOcsServer::checkVersion($item->getID())
            && PluginOcsinventoryngOcsServer::checkTraceDeleted($item->getID())
         ) {
            $client = PluginOcsinventoryngOcsServer::getDBocs($item->getID());
            $version = $client->getTextConfig('GUI_VERSION');
            $snmp = $client->getIntConfig('SNMP');

            if ($version > PluginOcsinventoryngOcsServer::OCS2_1_VERSION_LIMIT && $snmp) {
               return __('SNMP Import', 'ocsinventoryng');
            }
         }

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
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {

      if (in_array($item->getType(), self::$snmptypes)) {

         self::showForItem($item);

      } else if ($item->getType() == "PluginOcsinventoryngOcsServer") {

         $conf = new self();
         $conf->ocsFormSNMPImportOptions($item->getID());
      }
      return true;
   }

   /**
    * @param $ID
    * @internal param $withtemplate (default '')
    * @internal param $templateid (default '')
    */
   function ocsFormSNMPImportOptions($ID)
   {

      $conf = new PluginOcsinventoryngOcsServer();
      $conf->getFromDB($ID);
      echo "<div class='center'>";
      echo "<form name='formsnmpconfig' id='formsnmpconfig' action='" . Toolbox::getItemTypeFormURL("PluginOcsinventoryngOcsServer") . "' method='post'>";
      echo "<table class='tab_cadre_fixe'>\n";

      echo "<tr><th colspan ='4'>";
      _e('All');

      echo $JS = <<<JAVASCRIPT
         <script type='text/javascript'>
            function form_init_all(form, value) {
                  var selects = $("form[id='formsnmpconfig'] select");
                  $.each(selects, function(index, select){
                  $(select).select2('val', value);
               });
            }
         </script>
JAVASCRIPT;
      Dropdown::showYesNo('init_all', 0, -1, array(
         'width' => '10%',
         'on_change' => "form_init_all(this.form, this.selectedIndex);"
      ));
      echo "</th></tr>";

      echo "<tr class='tab_bg_2'>\n";
      echo "<td class='top'>\n";

      echo $JS = <<<JAVASCRIPT
         <script type='text/javascript'>
         function accordions(id, openall) {
             if(id == undefined){
                 id  = 'accordions';
             }
             jQuery(document).ready(function () {
                 $("#"+id).accordion({
                     collapsible: true,
                     //active:[0, 1, 2, 3],
                     //heightStyle: "content"
                 });
                 //if (openall) {
                     //$('#'+id +' .ui-accordion-content').show();
                 //}
             });
         };
         </script>
JAVASCRIPT;

      echo "<div id='accordions'>";

      echo "<h2><a href='#'>" . __('General SNMP import options', 'ocsinventoryng') . "</a></h2>";
      echo "<div>";
      echo "<table class='tab_cadre' width='100%'>";
      echo "<tr><th colspan='4'>" . __('General SNMP import options', 'ocsinventoryng') . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP name', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_name", $conf->fields["importsnmp_name"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Import SNMP serial', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_serial", $conf->fields["importsnmp_serial"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP comment', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_comment", $conf->fields["importsnmp_comment"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Import SNMP contact', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_contact", $conf->fields["importsnmp_contact"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP location', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_location", $conf->fields["importsnmp_location"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Import SNMP domain', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_domain", $conf->fields["importsnmp_domain"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP manufacturer', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_manufacturer", $conf->fields["importsnmp_manufacturer"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Create network port', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_createport", $conf->fields["importsnmp_createport"]);
      echo "</td></tr>\n";
      
      echo "<tr><th colspan='4'>" . __('Computer SNMP import options', 'ocsinventoryng') . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP network cards', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_computernetworkcards", $conf->fields["importsnmp_computernetworkcards"]);

      echo "</td><td class='center'>" . __('Import SNMP memory', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_computermemory", $conf->fields["importsnmp_computermemory"]);
      echo "</td></tr>\n";
      
      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP processors', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_computerprocessors", $conf->fields["importsnmp_computerprocessors"]);

      echo "</td><td class='center'>" . __('Import SNMP softwares', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_computersoftwares", $conf->fields["importsnmp_computersoftwares"]);
      echo "</td></tr>\n";
      
      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP virtual machines', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_computervm", $conf->fields["importsnmp_computervm"]);
      echo "</td><td colspan='2'</td></tr>\n";
      
      echo "<tr><th colspan='4'>" . __('Printer SNMP import options', 'ocsinventoryng') . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP last pages counter', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_last_pages_counter", $conf->fields["importsnmp_last_pages_counter"]);

      echo "</td><td class='center'>" . __('Import SNMP printer memory', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_printermemory", $conf->fields["importsnmp_printermemory"]);
      echo "</td></tr>\n";

      echo "<tr><th colspan='4'>" . __('Networking SNMP import options', 'ocsinventoryng') . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP firmware', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_firmware", $conf->fields["importsnmp_firmware"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Import SNMP Power supplies', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_power", $conf->fields["importsnmp_power"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Import SNMP Fans', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("importsnmp_fan", $conf->fields["importsnmp_fan"]);
      echo "</td><td colspan='2'></td></tr>\n";
      echo "</table><br>";
      echo "</div>";

      //Components

      echo "<h2><a href='#'>" . __('General SNMP link options', 'ocsinventoryng') . "</a></h2>";

      /******Link ***/
      echo "<div>";
      echo "<table class='tab_cadre' width='100%'>";
      echo "<tr><th colspan='4'>" . __('General SNMP link options', 'ocsinventoryng') . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP name', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_name", $conf->fields["linksnmp_name"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Link SNMP serial', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_serial", $conf->fields["linksnmp_serial"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP comment', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_comment", $conf->fields["linksnmp_comment"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Link SNMP contact', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_contact", $conf->fields["linksnmp_contact"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP location', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_location", $conf->fields["linksnmp_location"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Link SNMP domain', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_domain", $conf->fields["linksnmp_domain"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP manufacturer', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_manufacturer", $conf->fields["linksnmp_manufacturer"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Create network port', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_createport", $conf->fields["linksnmp_createport"]);
      echo "</td></tr>\n";
      
      echo "<tr><th colspan='4'>" . __('Computer SNMP link options', 'ocsinventoryng') . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP network cards', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_computernetworkcards", $conf->fields["linksnmp_computernetworkcards"]);

      echo "</td><td class='center'>" . __('Link SNMP memory', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_computermemory", $conf->fields["linksnmp_computermemory"]);
      echo "</td></tr>\n";
      
      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP processors', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_computerprocessors", $conf->fields["linksnmp_computerprocessors"]);

      echo "</td><td class='center'>" . __('Link SNMP softwares', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_computersoftwares", $conf->fields["linksnmp_computersoftwares"]);
      echo "</td></tr>\n";
      
      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP virtual machines', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_computervm", $conf->fields["linksnmp_computervm"]);
      echo "</td><td colspan='2'></td></tr>\n";
      
      echo "<tr><th colspan='4'>" . __('Printer SNMP link options', 'ocsinventoryng') . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP last pages counter', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_last_pages_counter", $conf->fields["linksnmp_last_pages_counter"]);

      echo "</td><td class='center'>" . __('Link SNMP printer memory', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_printermemory", $conf->fields["linksnmp_printermemory"]);
      echo "</td></tr>\n";

      echo "<tr><th colspan='4'>" . __('Networking SNMP link options', 'ocsinventoryng') . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP firmware', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_firmware", $conf->fields["linksnmp_firmware"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Link SNMP Power supplies', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_power", $conf->fields["linksnmp_power"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Link SNMP Fans', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("linksnmp_fan", $conf->fields["linksnmp_fan"]);
      echo "</td><td colspan='2'></td></tr>\n";
      echo "</table>\n";
      echo "</div>";

      echo "</div>";

      echo "<script>accordions();</script>";

      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center' colspan='4'>";
      echo "<input type='hidden' name='id' value='$ID'>";
      echo "<input type='submit' name='updateSNMP' class='submit' value='" .
         _sx('button', 'Save') . "'>";
      echo "</td></tr>";

      echo "</table>\n";
      Html::closeForm();
      echo "</div>";
   }

   /**
    * @param $plugin_ocsinventoryng_ocsservers_id
    */
   static function snmpMenu($plugin_ocsinventoryng_ocsservers_id)
   {
      global $CFG_GLPI, $DB;
      $ocsservers = array();
      //echo "<div class='center'>";
      //echo "<img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/ocsinventoryng.png' " .
      //"alt='OCS Inventory NG' title='OCS Inventory NG'>";
      //echo "</div>";
      $numberActiveServers = countElementsInTable('glpi_plugin_ocsinventoryng_ocsservers', "`is_active`='1'");
      if ($numberActiveServers > 0) {
         echo "<form action=\"" . $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ocsng.php\"
                method='post'>";
         echo "<div class='center'><table class='tab_cadre_fixe' width='40%'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Choice of an OCSNG server', 'ocsinventoryng') .
            "</th></tr>\n";

         echo "<tr class='tab_bg_2'><td class='center'>" . __('Name') . "</td>";
         echo "<td class='center'>";
         $query = "SELECT `glpi_plugin_ocsinventoryng_ocsservers`.`id`
                   FROM `glpi_plugin_ocsinventoryng_ocsservers_profiles`
                   LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers`
                      ON `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id` = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
                   WHERE `profiles_id`= " . $_SESSION["glpiactiveprofile"]['id'] . " AND `glpi_plugin_ocsinventoryng_ocsservers`.`is_active`='1'
                   ORDER BY `name` ASC";
         //var_dump($query);
         foreach ($DB->request($query) as $data) {
            $ocsservers[] = $data['id'];
         }
         Dropdown::show('PluginOcsinventoryngOcsServer', array("condition" => "`id` IN ('" . implode("','", $ocsservers) . "')",
            "value" => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
            "on_change" => "this.form.submit()",
            "display_emptychoice" => false));
         echo "</td></tr>";
         echo "<tr class='tab_bg_2'><td colspan='2' class ='center red'>";
         _e('If you not find your OCSNG server in this dropdown, please check if your profile can access it !', 'ocsinventoryng');
         echo "</td></tr>";
         echo "</table></div>";
         Html::closeForm();
      }
      $sql = "SELECT `name`, `is_active`
              FROM `glpi_plugin_ocsinventoryng_ocsservers`
              LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers_profiles`
                  ON `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id` = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
              WHERE `glpi_plugin_ocsinventoryng_ocsservers`.`id` = '" . $plugin_ocsinventoryng_ocsservers_id . "' 
              AND `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`profiles_id`= '" . $_SESSION["glpiactiveprofile"]['id'] . "'";
      $result = $DB->query($sql);
      $isactive = 0;
      if ($DB->numrows($result) > 0) {
         $datas = $DB->fetch_array($result);
         $name = " : " . $datas["name"];
         $isactive = $datas["is_active"];
      }
      if ($isactive) {
         $client = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);

         //if (Session::haveRight("plugin_ocsinventoryng", UPDATE) && $version > self::OCS2_1_VERSION_LIMIT && $snmp) {
         //host not imported by thread
         echo "<div class='center'><table class='tab_cadre_fixe' width='40%'>";
         echo "<tr><th colspan='4'>";
         _e('OCSNG SNMP import', 'ocsinventoryng');
         echo "<br>";
         echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsserver.form.php?id=" . $plugin_ocsinventoryng_ocsservers_id . "&forcetab=PluginOcsinventoryngSnmpOcslink\$1'>";
         _e('See Setup : SNMP Import before', 'ocsinventoryng');
         echo "</a>";
         echo "</th></tr>";

         // SNMP device link feature
         echo "<tr class='tab_bg_1'><td class='center b' colspan='2'>
                  <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsngsnmp.link.php'>
                   <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/link.png' " .
            "alt='" . __s('Link SNMP devices to existing GLPI objects', 'ocsinventoryng') . "' " .
            "title=\"" . __s('Link SNMP devices to existing GLPI objects', 'ocsinventoryng') . "\">
                     <br>" . __('Link SNMP devices to existing GLPI objects', 'ocsinventoryng') . "
                  </a></td>";

         echo "<td class='center b' colspan='2'>
               <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsngsnmp.sync.php'>
                <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/synchro1.png' " .
            "alt='" . __s('Synchronize snmp devices already imported', 'ocsinventoryng') . "' " .
            "title=\"" . __s('Synchronize snmp devices already imported', 'ocsinventoryng') . "\" >
                  <br>" . __('Synchronize snmp devices already imported', 'ocsinventoryng') . "
               </a></td>";
         echo "</tr>";

         //SNMP device import feature
         echo "<tr class='tab_bg_1'><td class='center b' colspan='2'>
             <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsngsnmp.import.php'>
              <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/import.png' " .
            "alt='" . __s('Import new SNMP devices', 'ocsinventoryng') . "' " .
            "title=\"" . __s('Import new SNMP devices', 'ocsinventoryng') . "\">
                <br>" . __('Import new SNMP devices', 'ocsinventoryng') . "
             </a></td>";

         echo "<td></td>";
         echo "</tr>";
         echo "</table></div>";
      }
   }

   /**
    * Show OcsLink of an item
    *
    * @param $item                   CommonDBTM object
    * @return nothing
    * @internal param int|string $withtemplate integer  withtemplate param (default '')
    */
   static function showForItem(CommonDBTM $item)
   {
      global $DB;

      //$target = Toolbox::getItemTypeFormURL(__CLASS__);

      if (in_array($item->getType(), self::$snmptypes)) {
         $items_id = $item->getField('id');

         if (!empty($items_id)
            && $item->fields["is_dynamic"]
            && Session::haveRight("plugin_ocsinventoryng_view", READ)
         ) {

            $query = "SELECT *
                      FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
                      WHERE `items_id` = '" . $items_id . "' AND `itemtype` = '" . $item->getType() . "'";

            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               $data = $DB->fetch_assoc($result);
               $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));

               if (count($data)) {
                  echo "<table class='tab_cadre_fixe'>";
                  echo "<tr class='tab_bg_1'><th colspan='2'>" . __('OCS Inventory NG SNMP Import informations', 'ocsinventoryng') . "</th>";
                  $linked = __('Imported object', 'ocsinventoryng');
                  if ($data["linked"]) {
                     $linked = __('Linked object', 'ocsinventoryng');
                  }
                  echo "<tr class='tab_bg_1'><td>" . __('Import date in GLPI', 'ocsinventoryng');
                  echo "</td><td>" . Html::convDateTime($data["last_update"]) . " (" . $linked . ")</td></tr>";

                  $linked_ids [] = $data['ocs_id'];
                  $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($data['plugin_ocsinventoryng_ocsservers_id']);
                  $ocsResult = $ocsClient->getSnmp(array(
                     'MAX_RECORDS' => 1,
                     'FILTER' => array(
                        'IDS' => $linked_ids,
                     )
                  ));
                  if (isset($ocsResult['SNMP'])) {
                     if (count($ocsResult['SNMP']) > 0) {
                        foreach ($ocsResult['SNMP'] as $snmp) {
                           $LASTDATE = $snmp['META']['LASTDATE'];
                           $UPTIME = $snmp['META']['UPTIME'];

                           echo "<tr class='tab_bg_1'><td>" . __('Last OCSNG SNMP inventory date', 'ocsinventoryng');
                           echo "</td><td>" . Html::convDateTime($LASTDATE) . "</td></tr>";

                           echo "<tr class='tab_bg_1'><td>" . __('Uptime', 'ocsinventoryng');
                           echo "</td><td>" . $UPTIME . "</td></tr>";
                        }
                        if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                           echo "</table><table class='tab_cadre_fixe'>";
                           echo "<tr class='tab_bg_1'><th colspan='2'>" . __('SNMP Debug') . "</th>";
                           echo "<tr class='tab_bg_1'>";
                           echo "<td  colspan='2'>";
                           echo "<pre>";
                           print_r($ocsResult['SNMP']);
                           echo "</pre>";
                           echo "</td></tr>";
                           echo "</table>";
                        }

                     } else {
                        echo "</table>";
                     }
                  } else {
                     echo "</table>";
                  }
               }
            }
         }
      }
   }

   /**
    * if Printer purged
    *
    * @param $print   Printer object
    **/
   static function purgePrinter(Printer $print)
   {
      $snmp = new self();
      $snmp->deleteByCriteria(array('items_id' => $print->getField("id"),
         'itemtype' => $print->getType()));

      $ipdiscover = new PluginOcsinventoryngIpdiscoverOcslink();
      $ipdiscover->deleteByCriteria(array('items_id' => $print->getField("id"),
         'itemtype' => $print->getType()));
   }

   /**
    * if Printer purged
    *
    * @param $per   Peripheral object
    **/
   static function purgePeripheral(Peripheral $per)
   {
      $snmp = new self();
      $snmp->deleteByCriteria(array('items_id' => $per->getField("id"),
         'itemtype' => $per->getType()));
      $ipdiscover = new PluginOcsinventoryngIpdiscoverOcslink();
      $ipdiscover->deleteByCriteria(array('items_id' => $per->getField("id"),
         'itemtype' => $per->getType()));
   }

   /**
    * if NetworkEquipment purged
    *
    * @param NetworkEquipment $net
    * @internal param NetworkEquipment $comp object
    */
   static function purgeNetworkEquipment(NetworkEquipment $net)
   {
      $snmp = new self();
      $snmp->deleteByCriteria(array('items_id' => $net->getField("id"),
         'itemtype' => $net->getType()));
      $ipdiscover = new PluginOcsinventoryngIpdiscoverOcslink();
      $ipdiscover->deleteByCriteria(array('items_id' => $net->getField("id"),
         'itemtype' => $net->getType()));

   }

   /**
    * if Computer purged
    *
    * @param $comp   Computer object
    **/
   static function purgeComputer(Computer $comp)
   {
      $snmp = new self();
      $snmp->deleteByCriteria(array('items_id' => $comp->getField("id"),
         'itemtype' => $comp->getType()));
      $ipdiscover = new PluginOcsinventoryngIpdiscoverOcslink();
      $ipdiscover->deleteByCriteria(array('items_id' => $comp->getField("id"),
         'itemtype' => $comp->getType()));

   }

   /**
    * if Phone purged
    *
    * @param $pho   Phone object
    **/
   static function purgePhone(Phone $pho)
   {
      $snmp = new self();
      $snmp->deleteByCriteria(array('items_id' => $pho->getField("id"),
         'itemtype' => $pho->getType()));
      $ipdiscover = new PluginOcsinventoryngIpdiscoverOcslink();
      $ipdiscover->deleteByCriteria(array('items_id' => $pho->getField("id"),
         'itemtype' => $pho->getType()));
   }

   /**
    * Show simple inventory information of an item
    *
    * @param $item                   CommonDBTM object
    *
    * @return nothing
    **/
   static function showSimpleForItem(CommonDBTM $item)
   {
      global $DB;

      $target = Toolbox::getItemTypeFormURL(__CLASS__);

      if (in_array($item->getType(), self::$snmptypes)) {
         $items_id = $item->getField('id');

         if (!empty($items_id)
            && $item->fields["is_dynamic"]
            && Session::haveRight("plugin_ocsinventoryng_view", READ)
         ) {
            $query = "SELECT *
                      FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
                      WHERE `items_id` = '" . $items_id . "' AND  `itemtype` = '" . $item->getType() . "'";

            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               $data = $DB->fetch_assoc($result);

               if (count($data)) {
                  echo "<tr class='tab_bg_1'><th colspan='4'>" . __('OCS Inventory NG SNMP Import informations', 'ocsinventoryng') . "</th>";

                  echo "<tr class='tab_bg_1'><td>" . __('Import date in GLPI', 'ocsinventoryng');
                  $linked = __('Imported object', 'ocsinventoryng');
                  if ($data["linked"]) {
                     $linked = __('Linked object', 'ocsinventoryng');
                  }
                  echo "</td><td>" . Html::convDateTime($data["last_update"]) . " (" . $linked . ")</td>";
                  if (Session::haveRight("plugin_ocsinventoryng_sync", UPDATE)) {
                     echo "<td class='center' colspan='2'>";
                     Html::showSimpleForm($target, 'force_ocssnmp_resynch',
                        _sx('button', 'Force SNMP synchronization', 'ocsinventoryng'),
                        array('items_id' => $items_id,
                           'itemtype' => $item->getType(),
                           'id' => $data["id"],
                           'plugin_ocsinventoryng_ocsservers_id' => $data["plugin_ocsinventoryng_ocsservers_id"]));
                     echo "</td></tr>";

                  } else {
                     echo "<td colspan='2'></td>";
                  }
                  echo "</tr>";

                  $linked_ids [] = $data['ocs_id'];
                  $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($data['plugin_ocsinventoryng_ocsservers_id']);
                  $ocsResult = $ocsClient->getSnmp(array(
                     'MAX_RECORDS' => 1,
                     'FILTER' => array(
                        'IDS' => $linked_ids,
                     )
                  ));
                  if (isset($ocsResult['SNMP'])) {
                     if (count($ocsResult['SNMP']) > 0) {
                        foreach ($ocsResult['SNMP'] as $snmp) {
                           $LASTDATE = $snmp['META']['LASTDATE'];
                           $UPTIME = $snmp['META']['UPTIME'];

                           echo "<tr class='tab_bg_1'><td>" . __('Last OCSNG SNMP inventory date', 'ocsinventoryng');
                           echo "</td><td>" . Html::convDateTime($LASTDATE) . "</td>";

                           echo "<td>" . __('Uptime', 'ocsinventoryng');
                           echo "</td><td>" . $UPTIME . "</td></tr>";
                        }
                     }
                  }
                  if ($item->getType() == 'Printer') {
                     $cartridges = array();
                     $trays = array();
                     if (isset($ocsResult['SNMP'])) {
                        if (count($ocsResult['SNMP']) > 0) {
                           foreach ($ocsResult['SNMP'] as $snmp) {
                              $cartridges = $snmp['CARTRIDGES'];
                              $trays = $snmp['TRAYS'];
                           }
                        }
                     }
                     if (count($cartridges) > 0) {

                        $colors = array(self::$CARTRIDGE_COLOR_BLACK,
                           self::$CARTRIDGE_COLOR_CYAN,
                           self::$CARTRIDGE_COLOR_MAGENTA,
                           self::$CARTRIDGE_COLOR_YELLOW);

                        echo "<tr class='tab_bg_1'><th colspan='4'>" . __('Cartridges informations', 'ocsinventoryng') . "</th>";
                        foreach ($cartridges as $cartridge) {

                           if ($cartridge['TYPE'] != "wasteToner") {
                              echo "<tr class='tab_bg_1'>";
                              echo "<td>" . $cartridge['DESCRIPTION'] . "</td>";
                              $class = 'ocsinventoryng_toner_level_other';
                              foreach ($colors as $k => $v) {
                                 foreach ($v as $color) {

                                    if (preg_match('/(' . $color . ')/i', $cartridge['DESCRIPTION'], $matches)) {
                                       $class = 'ocsinventoryng_toner_level_' . strtolower($matches[1]);
                                       if ($matches[1] == "jaune") {
                                          $class = 'ocsinventoryng_toner_level_yellow';
                                       }
                                       if ($matches[1] == "noir") {
                                          $class = 'ocsinventoryng_toner_level_black';
                                       }
                                       break;
                                    }
                                 }
                              }
                              $percent = 0;
                              if ($cartridge['LEVEL'] > 0) {
                                 $percent = ($cartridge['LEVEL'] * 100) / $cartridge['MAXCAPACITY'];
                              }
                              echo "<td colspan='2'><div class='ocsinventoryng_toner_level'><div class='ocsinventoryng_toner_level $class' style='width:" . $percent . "%'></div></div></td>";
                              echo "<td>" . $cartridge['LEVEL'] . " %</td>";
                              echo "</tr>";
                           }
                        }

                     }
                     if (count($trays) > 0) {

                        echo "<tr class='tab_bg_1'><th colspan='4'>" . __('Trays informations', 'ocsinventoryng') . "</th>";
                        foreach ($trays as $tray) {

                           if ($tray['NAME'] != "Bypass Tray") {
                              echo "<tr class='tab_bg_1'>";
                              echo "<td>" . $tray['DESCRIPTION'] . "</td>";
                              $class = 'ocsinventoryng_toner_level_other';
                              $percent = 0;
                              if ($tray['LEVEL'] > 0) {
                                 $percent = ($tray['LEVEL'] * 100) / $tray['MAXCAPACITY'];
                              }
                              echo "<td colspan='2'><div class='ocsinventoryng_toner_level'><div class='ocsinventoryng_toner_level $class' style='width:" . $percent . "%'></div></div></td>";
                              echo "<td>" . $tray['LEVEL'] . " / " . $tray['MAXCAPACITY'] . "</td>";
                              echo "</tr>";
                           }
                        }

                     }
                  }
               }
            }
         }
      }
      //IPDiscover Links
      if (in_array($item->getType(), PluginOcsinventoryngIpdiscoverOcslink::$hardwareItemTypes)) {
         $items_id = $item->getField('id');

         if (!empty($items_id)
            //&& $item->fields["is_dynamic"]
            && Session::haveRight("plugin_ocsinventoryng_view", READ)
         ) {
            $query = "SELECT *
                      FROM `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`
                      WHERE `items_id` = '" . $items_id . "' AND  `itemtype` = '" . $item->getType() . "'";

            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               $data = $DB->fetch_assoc($result);

               if (count($data)) {
                  echo "<tr class='tab_bg_1'><th colspan='4'>" . __('OCS Inventory NG IPDiscover Import informations', 'ocsinventoryng') . "</th>";

                  echo "<tr class='tab_bg_1'><td>" . __('Import date in GLPI', 'ocsinventoryng');
                  echo "</td><td>" . Html::convDateTime($data["last_update"]) . "</td><td colspan='2'></td></tr>";
               }
            }
         }
      }
   }

   // SNMP PART HERE

   /**
    * @param $ocsid
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param int $lock
    * @param $params
    * @return array
    */
   static function processSnmp($ocsid, $plugin_ocsinventoryng_ocsservers_id, $lock = 0, $params)
   {
      global $DB;

      PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);

      //Check it machine is already present AND was imported by OCS AND still present in GLPI
      $query = "SELECT `glpi_plugin_ocsinventoryng_snmpocslinks`.`id`
             FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
             WHERE `ocs_id` = '$ocsid'
                   AND `plugin_ocsinventoryng_ocsservers_id` = '$plugin_ocsinventoryng_ocsservers_id'";
      $result_glpi_plugin_ocsinventoryng_ocslinks = $DB->query($query);

      if ($DB->numrows($result_glpi_plugin_ocsinventoryng_ocslinks)) {
         $datas = $DB->fetch_array($result_glpi_plugin_ocsinventoryng_ocslinks);
         //Return code to indicates that the machine was synchronized
         //or only last inventory date changed
         return self::updateSnmp($datas["id"], $plugin_ocsinventoryng_ocsservers_id);
      }
      return self::importSnmp($ocsid, $plugin_ocsinventoryng_ocsservers_id, $params);
   }

   /**
    * @param $ocsid
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param $params
    * @return array
    */
   static function importSnmp($ocsid, $plugin_ocsinventoryng_ocsservers_id, $params)
   {
      global $DB;

      $p['entity'] = -1;
      $p['itemtype'] = -1;
      foreach ($params as $key => $val) {
         $p[$key] = $val;
      }

      PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
      //TODOSNMP entites_id ?

      $ocsSnmp = $ocsClient->getSnmpDevice($ocsid);

      if ($ocsSnmp['META']['ID'] == $ocsid && $p['itemtype'] != -1) {
         $itemtype = $p['itemtype'];

         $loc_id = 0;
         $dom_id = 0;
         if ($cfg_ocs['importsnmp_location']) {
            $loc_id = Dropdown::importExternal('Location', PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['META']['LOCATION']));
         }
         if ($cfg_ocs['importsnmp_domain']) {
            $dom_id = Dropdown::importExternal('Domain', PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['META']['DOMAIN']));
         }

         if ($itemtype == "NetworkEquipment") {

            $id = self::addOrUpdateNetworkEquipment($plugin_ocsinventoryng_ocsservers_id, $itemtype, 0, $ocsSnmp, $loc_id, $dom_id, "add",false,$cfg_ocs);

         } else if ($itemtype == "Printer") {

            $id = self::addOrUpdatePrinter($plugin_ocsinventoryng_ocsservers_id, $itemtype, 0, $ocsSnmp, $loc_id, $dom_id, "add",false,$cfg_ocs);

         } else if ($itemtype == "Computer") {

            $id = self::addOrUpdateComputer($plugin_ocsinventoryng_ocsservers_id, $itemtype, 0, $ocsSnmp, $loc_id, $dom_id, "add",false,$cfg_ocs);

         } else if ($itemtype == "Peripheral" || $itemtype == "Phone") {

            $id = self::addOrUpdateOther($plugin_ocsinventoryng_ocsservers_id, $itemtype, 0, $ocsSnmp, $loc_id, $dom_id, "add",false,$cfg_ocs);

         }
         //TODOSNMP 
         //Monitor & Phone ???
         if ($id) {
            $date = date("Y-m-d H:i:s");
            //Add to snmp link

            $query = "INSERT INTO `glpi_plugin_ocsinventoryng_snmpocslinks`
                       SET `items_id` = '" . $id . "',
                            `ocs_id` = '" . $ocsid . "',
                            `itemtype` = '" . $itemtype . "',
                            `last_update` = '" . $date . "',
                           `plugin_ocsinventoryng_ocsservers_id` = '" . $plugin_ocsinventoryng_ocsservers_id . "'";

            $DB->query($query);

            return array('status' => PluginOcsinventoryngOcsServer::SNMP_IMPORTED,
               //'entities_id'  => $data['entities_id'],
            );
         } else {
            return array('status' => PluginOcsinventoryngOcsServer::SNMP_FAILED_IMPORT,
               //'entities_id'  => $data['entities_id'],
            );
         }
      } else {
         return array('status' => PluginOcsinventoryngOcsServer::SNMP_FAILED_IMPORT,
            //'entities_id'  => $data['entities_id'],
         );
      }
   }

   // Check if object already exist NOR create it with ocs snmp data
   /* static function checkIfExist($object, $data){

     // Check for loc and all theses stuff
     if ($data != "" or !empty($data) or is_null($data)){
     // Check for domain / loc / network .
     $location = new $object();
     $reponse = $location->find("name = '".$data."'");
     if (is_null($reponse) or empty($reponse)){
     $input = array(
     "entities_id" => $_SESSION['glpiactive_entity'],
     "name" => $data,
     );
     $id = $location->add($input, array('unicity_error_message' => false));
     }else{
     foreach($reponse as $ident => $fields){
     $id = $fields['id'];
     }
     }

     return $id;
     }

     return "";
     } */

   /**
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param $itemtype
    * @param int $ID
    * @param $ocsSnmp
    * @param $loc_id
    * @param $dom_id
    * @param $action
    * @param bool $linked
    * @return int
    */
   static function addOrUpdatePrinter($plugin_ocsinventoryng_ocsservers_id, $itemtype, $ID = 0, $ocsSnmp, $loc_id, $dom_id, $action, $linked = false, $cfg_ocs)
   {
      global $DB;

      $snmpDevice = new $itemtype();

      $input = array(
         "is_dynamic" => 1,
         "entities_id" => (isset($_SESSION['glpiactive_entity'])?$_SESSION['glpiactive_entity']:0),
         "have_ethernet" => 1,
      );

      //TODOSNMP TO TEST:
      //'PRINTER' => 
      // array (size=1)
      //   0 => 
      //     array (size=6)
      //       'SNMP_ID' => string '4' (length=1)
      //       'NAME' => string 'MP C3003' (length=8)
      //       'SERIALNUMBER' => string 'E1543632108' (length=11)
      //       'COUNTER' => string '98631 sheets' (length=12)
      //       'STATUS' => string 'idle' (length=4)
      //       'ERRORSTATE' => string '' (length=0)


      if (($cfg_ocs['importsnmp_name'] && $action == "add")
         || ($cfg_ocs['linksnmp_name'] && $linked)
         || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
         || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)
      ) {
         $input["name"] = $ocsSnmp['META']['NAME'];
      }
      if (($cfg_ocs['importsnmp_contact'] && $action == "add")
         || ($cfg_ocs['linksnmp_contact'] && $linked)
         || ($action == "update" && $cfg_ocs['importsnmp_contact'] && !$linked)
         || ($action == "update" && $cfg_ocs['linksnmp_contact'] && $linked)
      ) {
         $input["contact"] = $ocsSnmp['META']['CONTACT'];
      }
      if (($cfg_ocs['importsnmp_comment'] && $action == "add")
         || ($cfg_ocs['linksnmp_comment'] && $linked)
         || ($action == "update" && $cfg_ocs['importsnmp_comment'] && !$linked)
         || ($action == "update" && $cfg_ocs['linksnmp_contact'] && $linked)
      ) {
         $input["comment"] = $ocsSnmp['META']['DESCRIPTION'];
      }
      if (($cfg_ocs['importsnmp_serial'] && $action == "add")
         || ($cfg_ocs['linksnmp_serial'] && $linked)
         || ($action == "update" && $cfg_ocs['importsnmp_serial'] && !$linked)
         || ($action == "update" && $cfg_ocs['linksnmp_serial'] && $linked)
      ) {
         $input["serial"] = $ocsSnmp['PRINTER'][0]['SERIALNUMBER'];
      }
      if (($cfg_ocs['importsnmp_last_pages_counter'] && $action == "add")
         || ($cfg_ocs['linksnmp_last_pages_counter'] && $linked)
         || ($action == "update" && $cfg_ocs['importsnmp_last_pages_counter'] && !$linked)
         || ($action == "update" && $cfg_ocs['linksnmp_last_pages_counter'] && $linked)
      ) {
         $input["last_pages_counter"] = $ocsSnmp['PRINTER'][0]['COUNTER'];
      }


      if ($loc_id > 0) {
         $input["locations_id"] = $loc_id;
      }
      if ($dom_id > 0) {
         $input["domains_id"] = $dom_id;
      }

      $id_printer = 0;

      if ($action == "add") {
         $id_printer = $snmpDevice->add($input, array('unicity_error_message' => true), $cfg_ocs['history_hardware']);
      } else {
         $id_printer = $ID;
         $input["id"] = $ID;
         if ($snmpDevice->getFromDB($id_printer)) {
            $input["entities_id"] = $snmpDevice->fields['entities_id'];
         }

         $snmpDevice->update($input, $cfg_ocs['history_hardware'], array('unicity_error_message' => false,
               '_no_history' => !$cfg_ocs['history_hardware']));
      }


      if ($id_printer > 0
         && isset($ocsSnmp['MEMORIES'])
         && (($cfg_ocs['importsnmp_printermemory'] && $action == "add")
            || ($cfg_ocs['linksnmp_printermemory'] && $linked)
            || ($action == "update" && $cfg_ocs['importsnmp_printermemory'] && !$linked)
            || ($action == "update" && $cfg_ocs['linksnmp_printermemory'] && $linked))
         && count($ocsSnmp['MEMORIES']) > 0
         && $ocsSnmp['MEMORIES'][0]['CAPACITY'] > 0
      ) {
         
         $dev['designation'] = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep(__("Printer Memory", 'ocsinventoryng')));

         $item = new $itemtype();
         $entity = (isset($_SESSION['glpiactive_entity'])?$_SESSION['glpiactive_entity']:0);
         if ($item->getFromDB($id_printer)) {
            $entity = $item->fields['entities_id'];
         }

         $dev['entities_id'] = $entity;

         $device = new DeviceMemory();
         $device_id = $device->import($dev);
         if ($device_id) {
            $CompDevice = new Item_DeviceMemory();

            if ($cfg_ocs['history_devices']) {
               $table = getTableForItemType("Item_DeviceMemory");
               $query = "DELETE
                            FROM `" . $table . "`
                            WHERE `items_id` = '" . $id_printer . "'
                            AND `itemtype` = '" . $itemtype . "'";
               $DB->query($query);
            }
//            CANNOT USE BEFORE 9.1.2 - for _no_history problem
//            $CompDevice->deleteByCriteria(array('items_id' => $id_printer,
//               'itemtype' => $itemtype), 1);
            $CompDevice->add(array('items_id' => $id_printer,
               'itemtype' => $itemtype,
               'size' => $ocsSnmp['MEMORIES'][0]['CAPACITY'],
               'entities_id' => $entity,
               'devicememories_id' => $device_id,
               'is_dynamic' => 1), array(), $cfg_ocs['history_devices']);
         }
      }

      if ($id_printer > 0
         && (($cfg_ocs['importsnmp_createport'] && $action == "add")
            || ($cfg_ocs['linksnmp_createport'] && $linked)
            || ($action == "update" && $cfg_ocs['importsnmp_createport'] && !$linked)
            || ($action == "update" && $cfg_ocs['linksnmp_createport'] && $linked))
      ) {

         //Add network port
         $ip = $ocsSnmp['META']['IPADDR'];
         $mac = $ocsSnmp['META']['MACADDR'];

         $np = new NetworkPort();
         $np->getFromDBByQuery("WHERE `mac` LIKE '$mac' AND `items_id` = '$id_printer' AND `itemtype` LIKE '$itemtype' ");

         if (count($np->fields) < 1) {

            $item = new $itemtype();
            $entity = (isset($_SESSION['glpiactive_entity'])?$_SESSION['glpiactive_entity']:0);
            if ($item->getFromDB($id_printer)) {
               $entity = $item->fields['entities_id'];
            }

            $port_input = array('name' => $ocsSnmp['PRINTER'][0]['NAME'],
               'mac' => $mac,
               'items_id' => $id_printer,
               'itemtype' => $itemtype,
               'instantiation_type' => "NetworkPortEthernet",
               "entities_id" => $entity,
               "NetworkName__ipaddresses" => array("-100" => $ip),
               '_create_children' => 1,
               //'is_dynamic'                => 1,
               'is_deleted' => 0);

            $np->add($port_input, array(), $cfg_ocs['history_network']);
         }

         //TODOSNMP TO TEST:
         //'PRINTER' => 
         // array (size=1)
         //   0 => 
         //array (size=7)
         //  'ID' => string '6' (length=1)
         //  'SNMP_ID' => string '4' (length=1)
         //  'DESCRIPTION' => string 'Toner cyan' (length=10)
         //  'TYPE' => string 'toner' (length=5)
         //  'LEVEL' => string '30' (length=2)
         //  'MAXCAPACITY' => string '100' (length=3)
         //  'COLOR' => string '' (length=0)
         //TODOSNMP But complicated
         //if(!empty($ocsSnmp['CARTRIDGES'])){
         //   foreach($ocsSnmp['CARTRIDGES'] as $k => $val){
         //     $cartridge_item = new CartridgeItem();
         //     $input = array (
         //         "name" => $val['DESCRIPTION'],
         //         "entities_id" => $_SESSION['glpiactive_entity'],
         //"comment" => $ocsSnmp['CARTRIDGES']['DESCRIPTION'],
         //         "locations_id" => $loc_id,
         //     );
         //     $type_id = Dropdown::importExternal('CartridgeItemType',
         //      PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'],
         //      $val['TYPE']));
         //      $input['cartridgeitemtypes_id'] = $type_id;
         //     $cartridge_items_id = $cartridge_item->add($input, array('unicity_error_message' => false));
         //     $cartridges = new Cartridge();
         //     $values = array (
         //         "entities_id" => $_SESSION['glpiactive_entity'],
         //         "cartridgeitems_id" => $cartridge_items_id,
         //         "printers_id" => $id_printer,
         //         "date_use" => date("Y-m-d")
         //     );
         //     $cartridges->add($values, array('unicity_error_message' => false));
         //   }
         //}
      }

      return $id_printer;
   }

   /**
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param $itemtype
    * @param int $ID
    * @param $ocsSnmp
    * @param $loc_id
    * @param $dom_id
    * @param $action
    * @param bool $linked
    * @return int
    */
   static function addOrUpdateNetworkEquipment($plugin_ocsinventoryng_ocsservers_id, $itemtype, $ID = 0, $ocsSnmp, $loc_id, $dom_id, $action, $linked = false, $cfg_ocs)
   {
      global $DB;

      $snmpDevice = new $itemtype();

      $input = array(
         "is_dynamic" => 1,
         "entities_id" => (isset($_SESSION['glpiactive_entity'])?$_SESSION['glpiactive_entity']:0),
         "is_recursive" => 0,
      );

      if (($cfg_ocs['importsnmp_name'] && $action == "add")
         || ($cfg_ocs['linksnmp_name'] && $linked)
         || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
         || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)
      ) {
         if ($ocsSnmp['META']['NAME'] != "N/A") {
            $input["name"] = $ocsSnmp['META']['NAME'];
         } else {
            $input["name"] = $ocsSnmp['META']['DESCRIPTION'];
         }
      }
      if (($cfg_ocs['importsnmp_contact'] && $action == "add")
         || ($cfg_ocs['linksnmp_contact'] && $linked)
         || ($action == "update" && $cfg_ocs['importsnmp_contact'] && !$linked)
         || ($action == "update" && $cfg_ocs['linksnmp_contact'] && $linked)
      ) {
         $input["contact"] = $ocsSnmp['META']['CONTACT'];
      }
      if (($cfg_ocs['importsnmp_comment'] && $action == "add")
         || ($cfg_ocs['linksnmp_comment'] && $linked)
         || ($action == "update" && $cfg_ocs['importsnmp_comment'] && !$linked)
         || ($action == "update" && $cfg_ocs['linksnmp_comment'] && $linked)
      ) {
         $input["comment"] = $ocsSnmp['META']['DESCRIPTION'];
      }

      if ($loc_id > 0) {
         $input["locations_id"] = $loc_id;
      }
      if ($dom_id > 0) {
         $input["domains_id"] = $dom_id;
      }

      //if($ocsSnmp['META']['TYPE'] == null){
      //   $type_id = self::checkIfExist("NetworkEquipmentType", "Network Device");
      //} else {
      //   $type_id = self::checkIfExist("network", $ocsSnmp['META']['TYPE']);
      //}

      if (!empty($ocsSnmp['SWITCH'])) {

         if (($cfg_ocs['importsnmp_manufacturer'] && $action == "add")
            || ($cfg_ocs['linksnmp_manufacturer'] && $linked)
            || ($action == "update" && $cfg_ocs['importsnmp_manufacturer'] && !$linked)
            || ($action == "update" && $cfg_ocs['linksnmp_manufacturer'] && $linked)
         ) {
            $man_id = Dropdown::importExternal('Manufacturer', PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['SWITCH'][0]['MANUFACTURER']));
            $input['manufacturers_id'] = $man_id;
         }

         if (($cfg_ocs['importsnmp_firmware'] && $action == "add")
            || ($cfg_ocs['linksnmp_firmware'] && $linked)
            || ($action == "update" && $cfg_ocs['importsnmp_firmware'] && !$linked)
            || ($action == "update" && $cfg_ocs['linksnmp_firmware'] && $linked)
         ) {
            $firm_id = Dropdown::importExternal('NetworkEquipmentFirmware', PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['SWITCH'][0]['FIRMVERSION']));
            $input['networkequipmentfirmwares_id'] = $firm_id;
         }

         if (($cfg_ocs['importsnmp_serial'] && $action == "add")
            || ($cfg_ocs['linksnmp_serial'] && $linked)
            || ($action == "update" && $cfg_ocs['importsnmp_serial'] && !$linked)
            || ($action == "update" && $cfg_ocs['linksnmp_serial'] && $linked)
         ) {
            $input['serial'] = $ocsSnmp['SWITCH'][0]['SERIALNUMBER'];
         }
         //TODOSNMP = chassis ??
         //$mod_id = Dropdown::importExternal('NetworkEquipmentModel',
         //PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'],
         //$ocsSnmp['SWITCH'][0]['REFERENCE']));
         //$input['networkequipmentmodels_id'] = $mod_id;
         // TODOSNMP ?
         //$input['networkequipmenttypes_id'] = self::checkIfExist("NetworkEquipmentType", "Switch");
      }
      if (!empty($ocsSnmp['FIREWALLS'])) {

         if (($cfg_ocs['importsnmp_serial'] && $action == "add")
            || ($cfg_ocs['linksnmp_serial'] && $action == $linked)
            || ($action == "update" && $cfg_ocs['importsnmp_serial'] && !$linked)
            || ($action == "update" && $cfg_ocs['linksnmp_serial'] && $linked)
         ) {
            $input['serial'] = $ocsSnmp['FIREWALLS']['SERIALNUMBER'];
         }
         // TODOSNMP ?
         //$input['networkequipmenttypes_id'] = self::checkIfExist("NetworkEquipmentType", "Firewall");
      }
      $id_network = 0;
      if ($action == "add") {
         $id_network = $snmpDevice->add($input, array('unicity_error_message' => true), $cfg_ocs['history_hardware']);
      } else {
         $input["id"] = $ID;
         $id_network = $ID;
         if ($snmpDevice->getFromDB($id_network)) {
            $input["entities_id"] = $snmpDevice->fields['entities_id'];
         }
         $snmpDevice->update($input, $cfg_ocs['history_hardware'], array('unicity_error_message' => false,
               '_no_history' => !$cfg_ocs['history_hardware']));
      }

      if ($id_network > 0
         //&& $action == "add"
      ) {

         if (isset($ocsSnmp['POWERSUPPLIES'])
            && (($cfg_ocs['importsnmp_power'] && $action == "add")
               || ($cfg_ocs['linksnmp_power'] && $linked)
               || ($action == "update" && $cfg_ocs['importsnmp_power'] && !$linked)
               || ($action == "update" && $cfg_ocs['linksnmp_power'] && $linked))
            && count($ocsSnmp['POWERSUPPLIES']) > 0
         ) {

            $man_id = Dropdown::importExternal('Manufacturer', PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['POWERSUPPLIES'][0]['MANUFACTURER']));

            $pow['manufacturers_id'] = $man_id;
            $pow['designation'] = $ocsSnmp['POWERSUPPLIES'][0]['REFERENCE'];
            $pow['comment'] = $ocsSnmp['POWERSUPPLIES'][0]['DESCRIPTION'];

            $item = new $itemtype();
            $entity = (isset($_SESSION['glpiactive_entity'])?$_SESSION['glpiactive_entity']:0);
            if ($item->getFromDB($id_network)) {
               $entity = $item->fields['entities_id'];
            }

            $pow['entities_id'] = $entity;

            $power = new DevicePowerSupply();
            $power_id = $power->import($pow);
            if ($power_id) {
               $serial = $ocsSnmp['POWERSUPPLIES'][0]['SERIALNUMBER'];
               $CompDevice = new Item_DevicePowerSupply();

               if ($cfg_ocs['history_devices']) {
                  $table = getTableForItemType("Item_DevicePowerSupply");
                  $query = "DELETE
                            FROM `" . $table . "`
                            WHERE `items_id` = '" . $id_network . "'
                            AND `itemtype` = '" . $itemtype . "'";
                  $DB->query($query);
               }
//            CANNOT USE BEFORE 9.1.2 - for _no_history problem
//               $CompDevice->deleteByCriteria(array('items_id' => $id_network,
//                  'itemtype' => $itemtype), 1);
               $CompDevice->add(array('items_id' => $id_network,
                  'itemtype' => $itemtype,
                  'entities_id' => $entity,
                  'serial' => $serial,
                  'devicepowersupplies_id' => $power_id,
                  'is_dynamic' => 1), array(), $cfg_ocs['history_devices']);
            }
         }

         if (isset($ocsSnmp['FANS'])
            && (($cfg_ocs['importsnmp_fan'] && $action == "add")
               || ($cfg_ocs['linksnmp_fan'] && $linked)
               || ($action == "update" && $cfg_ocs['importsnmp_fan'] && !$linked)
               || ($action == "update" && $cfg_ocs['linksnmp_fan'] && $linked))
            && count($ocsSnmp['FANS']) > 0
         ) {

            $man_id = Dropdown::importExternal('Manufacturer', PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['FANS'][0]['MANUFACTURER']));
            $dev['manufacturers_id'] = $man_id;

            $dev['designation'] = $ocsSnmp['FANS'][0]['REFERENCE'];
            $dev['comment'] = $ocsSnmp['FANS'][0]['DESCRIPTION'];

            $item = new $itemtype();
            $entity = (isset($_SESSION['glpiactive_entity'])?$_SESSION['glpiactive_entity']:0);
            if ($item->getFromDB($id_network)) {
               $entity = $item->fields['entities_id'];
            }

            $dev['entities_id'] = $entity;

            $device = new DevicePci();
            $device_id = $device->import($dev);
            if ($device_id) {
               $CompDevice = new Item_DevicePci();
               if ($cfg_ocs['history_devices']) {
                  $table = getTableForItemType("Item_DevicePci");
                  $query = "DELETE
                            FROM `" . $table . "`
                            WHERE `items_id` = '" . $id_network . "'
                            AND `itemtype` = '" . $itemtype . "'";
                  $DB->query($query);
               }
//            CANNOT USE BEFORE 9.1.2 - for _no_history problem
//               $CompDevice->deleteByCriteria(array('items_id' => $id_network,
//                  'itemtype' => $itemtype), 1);
               $CompDevice->add(array('items_id' => $id_network,
                  'itemtype' => $itemtype,
                  'entities_id' => $entity,
                  'devicepcis_id' => $device_id,
                  'is_dynamic' => 1), array(), $cfg_ocs['history_devices']);
            }
         }
      }
      if ($id_network > 0
         && (($cfg_ocs['importsnmp_createport'] && $action == "add")
            || ($cfg_ocs['linksnmp_createport'] && $linked)
            || ($action == "update" && $cfg_ocs['importsnmp_createport'] && !$linked)
            || ($action == "update" && $cfg_ocs['linksnmp_createport'] && $linked))
      ) {
         //Add network port
         $ip = $ocsSnmp['META']['IPADDR'];
         $mac = $ocsSnmp['META']['MACADDR'];

         $np = new NetworkPort();
         $np->getFromDBByQuery("WHERE `mac` LIKE '$mac' AND `items_id` = '$id_network' AND `itemtype` LIKE '$itemtype' ");
         if (count($np->fields) < 1) {

            $item = new $itemtype();
            $entity = (isset($_SESSION['glpiactive_entity'])?$_SESSION['glpiactive_entity']:0);
            if ($item->getFromDB($id_network)) {
               $entity = $item->fields['entities_id'];
            }

            $port_input = array('name' => $ocsSnmp['META']['NAME'],
               'mac' => $mac,
               'items_id' => $id_network,
               'itemtype' => $itemtype,
               'instantiation_type' => "NetworkPortEthernet",
               "entities_id" => $entity,
               "NetworkName__ipaddresses" => array("-100" => $ip),
               '_create_children' => 1,
               //'is_dynamic'         => 1,
               'is_deleted' => 0);

            $np->add($port_input, array(), $cfg_ocs['history_network']);
         }
      }

      return $id_network;
   }
   
   
   /**
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param $itemtype
    * @param int $ID
    * @param $ocsSnmp
    * @param $loc_id
    * @param $dom_id
    * @param $action
    * @param bool $linked
    * @return int
    */
   static function addOrUpdateComputer($plugin_ocsinventoryng_ocsservers_id, $itemtype, $ID = 0, $ocsSnmp, $loc_id, $dom_id, $action, $linked = false, $cfg_ocs)
   {
      global $DB;
      
      $snmpDevice = new $itemtype();

      $input = array(
         "is_dynamic" => 1,
         "entities_id" => (isset($_SESSION['glpiactive_entity'])?$_SESSION['glpiactive_entity']:0)
      );

      if (($cfg_ocs['importsnmp_name'] && $action == "add")
         || ($cfg_ocs['linksnmp_name'] && $linked)
         || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
         || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)
      ) {
         $input["name"] = $ocsSnmp['META']['NAME'];
      }
      if (($cfg_ocs['importsnmp_contact'] && $action == "add")
         || ($cfg_ocs['linksnmp_contact'] && $linked)
         || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
         || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)
      ) {
         $input["contact"] = $ocsSnmp['META']['CONTACT'];
      }
      if (($cfg_ocs['importsnmp_comment'] && $action == "add")
         || ($cfg_ocs['linksnmp_comment'] && $linked)
         || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
         || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)
      ) {
         $input["comment"] = $ocsSnmp['META']['DESCRIPTION'];
      }

      if ($loc_id > 0) {
         $input["locations_id"] = $loc_id;
      }
      if ($dom_id > 0 && $itemtype != "Phone") {
         $input["domains_id"] = $dom_id;
      }

      $id_item = 0;

      if ($action == "add") {
         $id_item = $snmpDevice->add($input, array('unicity_error_message' => true), $cfg_ocs['history_hardware']);
      } else {
         $input["id"] = $ID;
         $id_item = $ID;
         if ($snmpDevice->getFromDB($id_item)) {
            $input["entities_id"] = $snmpDevice->fields['entities_id'];
         }
         $snmpDevice->update($input, $cfg_ocs['history_hardware'], array('unicity_error_message' => false,
               '_no_history' => !$cfg_ocs['history_hardware']));
      }
      
      if ($id_item > 0
         && isset($ocsSnmp['MEMORIES'])
         && (($cfg_ocs['importsnmp_computermemory'] && $action == "add")
            || ($cfg_ocs['linksnmp_computermemory'] && $linked)
            || ($action == "update" && $cfg_ocs['importsnmp_computermemory'] && !$linked)
            || ($action == "update" && $cfg_ocs['linksnmp_computermemory'] && $linked))
         && count($ocsSnmp['MEMORIES']) > 0
         && $ocsSnmp['MEMORIES'][0]['CAPACITY'] > 0
      ) {

         $dev['designation'] = __('Computer Memory', 'ocsinventoryng');

         $item = new $itemtype();
         $entity = (isset($_SESSION['glpiactive_entity'])?$_SESSION['glpiactive_entity']:0);
         if ($item->getFromDB($id_item)) {
            $entity = $item->fields['entities_id'];
         }

         $dev['entities_id'] = $entity;

         $device = new DeviceMemory();
         $device_id = $device->import($dev);
         if ($device_id) {
            $CompDevice = new Item_DeviceMemory();
            if ($cfg_ocs['history_devices']) {
               $table = getTableForItemType("Item_DeviceMemory");
               $query = "DELETE
                            FROM `" . $table . "`
                            WHERE `items_id` = '" . $id_item . "'
                            AND `itemtype` = '" . $itemtype . "'";
               $DB->query($query);
            }
//            CANNOT USE BEFORE 9.1.2 - for _no_history problem
//            $CompDevice->deleteByCriteria(array('items_id' => $id_item,
//               'itemtype' => $itemtype), 1);
            $CompDevice->add(array('items_id' => $id_item,
               'itemtype' => $itemtype,
               'size' => $ocsSnmp['MEMORIES'][0]['CAPACITY'],
               'entities_id' => $entity,
               'devicememories_id' => $device_id,
               'is_dynamic' => 1), array(), $cfg_ocs['history_devices']);
         }
      }
      
      if ($id_item > 0
         && isset($ocsSnmp['NETWORKS'])
         && (($cfg_ocs['importsnmp_computernetworkcards'] && $action == "add")
            || ($cfg_ocs['linksnmp_computernetworkcards'] && $linked)
            || ($action == "update" && $cfg_ocs['importsnmp_computernetworkcards'] && !$linked)
            || ($action == "update" && $cfg_ocs['linksnmp_computernetworkcards'] && $linked))
         && count($ocsSnmp['NETWORKS']) > 0
      ) {
         $CompDevice = new Item_DeviceNetworkCard();
         if ($cfg_ocs['history_devices']) {
            $table = getTableForItemType("Item_DeviceNetworkCard");
            $query = "DELETE
                            FROM `" . $table . "`
                            WHERE `items_id` = '" . $id_item . "'
                            AND `itemtype` = '" . $itemtype . "'";
            $DB->query($query);
         }
//            CANNOT USE BEFORE 9.1.2 - for _no_history problem
//         $CompDevice->deleteByCriteria(array('items_id' => $id_item,
//                                             'itemtype' => $itemtype), 1);
                  
         foreach ($ocsSnmp['NETWORKS'] as $k => $net) {
            $dev["designation"] = $net['SLOT'];
            $dev["comment"] = $net['TYPE'];
            $mac = $net['MACADDR'];
            /*$speed = 0;
            if (strstr($processor['SPEED'], "GHz")) {
               $speed = str_replace("GHz", "", $processor['SPEED']);
               $speed = $speed * 1000;
            }
            if (strstr($processor['SPEED'], "MHz")) {
               $speed = str_replace("MHz", "", $processor['SPEED']);
            }*/

            $item = new $itemtype();
            $entity = (isset($_SESSION['glpiactive_entity'])?$_SESSION['glpiactive_entity']:0);
            if ($item->getFromDB($id_item)) {
               $entity = $item->fields['entities_id'];
            }

            $dev['entities_id'] = $entity;

            $device = new DeviceNetworkCard();
            $device_id = $device->import($dev);

            if ($device_id) {
               
               $CompDevice->add(array('items_id' => $id_item,
                  'itemtype' => $itemtype,
                  'mac' => $mac,
                  'entities_id' => $entity,
                  'devicenetworkcards_id' => $device_id,
                  'is_dynamic' => 1), array(), $cfg_ocs['history_devices']);
            }
         }
      }
      
      if ($id_item > 0
         && isset($ocsSnmp['SOFTWARES'])
         && (($cfg_ocs['importsnmp_computersoftwares'] && $action == "add")
            || ($cfg_ocs['linksnmp_computersoftwares'] && $linked)
            || ($action == "update" && $cfg_ocs['importsnmp_computersoftwares'] && !$linked)
            || ($action == "update" && $cfg_ocs['linksnmp_computersoftwares'] && $linked))
         && count($ocsSnmp['SOFTWARES']) > 0
      ) {

         $entity = (isset($_SESSION['glpiactive_entity'])?$_SESSION['glpiactive_entity']:0);
         if ($item->getFromDB($id_item)) {
            $entity = $item->fields['entities_id'];
         }
         PluginOcsinventoryngOcsServer::updateSoftware($cfg_ocs, $id_item, $ocsSnmp["SOFTWARES"], $entity);
         
         }
      if ($id_item > 0
         && isset($ocsSnmp['CPU'])
         && (($cfg_ocs['importsnmp_computerprocessors'] && $action == "add")
            || ($cfg_ocs['linksnmp_computerprocessors'] && $linked)
            || ($action == "update" && $cfg_ocs['importsnmp_computerprocessors'] && !$linked)
            || ($action == "update" && $cfg_ocs['linksnmp_computerprocessors'] && $linked))
         && count($ocsSnmp['CPU']) > 0
      ) {
         $CompDevice = new Item_DeviceProcessor();
         if ($cfg_ocs['history_devices']) {
            $table = getTableForItemType("Item_DeviceProcessor");
            $query = "DELETE
                            FROM `" . $table . "`
                            WHERE `items_id` = '" . $id_item . "'
                            AND `itemtype` = '" . $itemtype . "'";
            $DB->query($query);
         }
//            CANNOT USE BEFORE 9.1.2 - for _no_history problem
//         $CompDevice->deleteByCriteria(array('items_id' => $id_item,
//                                             'itemtype' => $itemtype), 1);
                  
         foreach ($ocsSnmp['CPU'] as $k => $processor) {
            $dev["designation"] = $processor['TYPE'];
            $dev["manufacturers_id"] = Dropdown::importExternal('Manufacturer', PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $processor['MANUFACTURER']));
            $speed = 0;
            if (strstr($processor['SPEED'], "GHz")) {
               $speed = str_replace("GHz", "", $processor['SPEED']);
               $speed = $speed * 1000;
            }
            if (strstr($processor['SPEED'], "MHz")) {
               $speed = str_replace("MHz", "", $processor['SPEED']);
            }

            $item = new $itemtype();
            $entity = (isset($_SESSION['glpiactive_entity'])?$_SESSION['glpiactive_entity']:0);
            if ($item->getFromDB($id_item)) {
               $entity = $item->fields['entities_id'];
            }

            $dev['entities_id'] = $entity;

            $device = new DeviceProcessor();
            $device_id = $device->import($dev);

            if ($device_id) {
               
               $CompDevice->add(array('items_id' => $id_item,
                  'itemtype' => $itemtype,
                  'frequency' => $speed,
                  'entities_id' => $entity,
                  'deviceprocessors_id' => $device_id,
                  'is_dynamic' => 1), array(), $cfg_ocs['history_devices']);
            }
         }
      }
      
      
      if ($id_item > 0
         && isset($ocsSnmp['VIRTUALMACHINES'])
         && (($cfg_ocs['importsnmp_computervm'] && $action == "add")
            || ($cfg_ocs['linksnmp_computervm'] && $linked)
            || ($action == "update" && $cfg_ocs['importsnmp_computervm'] && !$linked)
            || ($action == "update" && $cfg_ocs['linksnmp_computervm'] && $linked))
         && count($ocsSnmp['VIRTUALMACHINES']) > 0
      ) {
         $already_processed = array();

         $virtualmachine = new ComputerVirtualMachine();
         foreach ($ocsSnmp['VIRTUALMACHINES'] as $k => $ocsVirtualmachine) {
         
            $ocsVirtualmachine = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($ocsVirtualmachine));
            $vm = array();
            $vm['name'] = $ocsVirtualmachine['NAME'];
            $vm['vcpu'] = $ocsVirtualmachine['CPU'];
            $vm['ram'] = $ocsVirtualmachine['MEMORY'];
            $vm['uuid'] = $ocsVirtualmachine['UUID'];
            $vm['computers_id'] = $id_item;
            $vm['is_dynamic'] = 1;

            $vm['virtualmachinestates_id'] = Dropdown::importExternal('VirtualMachineState', $ocsVirtualmachine['POWER']);
            //$vm['virtualmachinetypes_id'] = Dropdown::importExternal('VirtualMachineType', $ocsVirtualmachine['VMTYPE']);
            //$vm['virtualmachinesystems_id'] = Dropdown::importExternal('VirtualMachineType', $ocsVirtualmachine['SUBSYSTEM']);

            $query = "SELECT `id`
                         FROM `glpi_computervirtualmachines`
                         WHERE `computers_id`='$id_item'
                            AND `is_dynamic`";
            if ($ocsVirtualmachine['UUID']) {
               $query .= " AND `uuid`='" . $ocsVirtualmachine['UUID'] . "'";
            } else {
               // Failback on name
               $query .= " AND `name`='" . $ocsVirtualmachine['NAME'] . "'";
            }

            $results = $DB->query($query);
            if ($DB->numrows($results) > 0) {
               $id = $DB->result($results, 0, 'id');
            } else {
               $id = 0;
            }
            if (!$id) {
               $virtualmachine->reset();
               $id_vm = $virtualmachine->add($vm, array(), $cfg_ocs['history_vm']);
               if ($id_vm) {
                  $already_processed[] = $id_vm;
               }
            } else {
               if ($virtualmachine->getFromDB($id)) {
                  $vm['id'] = $id;
                  $virtualmachine->update($vm, $cfg_ocs['history_vm']);
               }
               $already_processed[] = $id;
            }
            // Delete Unexisting Items not found in OCS
            //Look for all ununsed virtual machines
            $query = "SELECT `id`
                      FROM `glpi_computervirtualmachines`
                      WHERE `computers_id`='$id_item'
                         AND `is_dynamic`";
            if (!empty($already_processed)) {
               $query .= "AND `id` NOT IN (" . implode(',', $already_processed) . ")";
            }
            foreach ($DB->request($query) as $data) {
               //Delete all connexions
               $virtualmachine->delete(array('id' => $data['id'],
                  '_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                  '_no_history' => !$cfg_ocs['history_vm']), true, $cfg_ocs['history_vm']);
            }
         }
      }
      
      if ($id_item > 0
         && (($cfg_ocs['importsnmp_createport'] && $action == "add")
            || ($cfg_ocs['linksnmp_createport'] && $linked)
            || ($action == "update" && $cfg_ocs['importsnmp_createport'] && !$linked)
            || ($action == "update" && $cfg_ocs['linksnmp_createport'] && $linked))
      ) {

         //Add network port
         $ip = $ocsSnmp['META']['IPADDR'];
         $mac = $ocsSnmp['META']['MACADDR'];

         $np = new NetworkPort();
         $np->getFromDBByQuery("WHERE `mac` LIKE '$mac' AND `items_id` = '$id_item' AND `itemtype` LIKE '$itemtype' ");
         if (count($np->fields) < 1) {

            $item = new $itemtype();
            $entity = (isset($_SESSION['glpiactive_entity'])?$_SESSION['glpiactive_entity']:0);
            if ($item->getFromDB($id_item)) {
               $entity = $item->fields['entities_id'];
            }
            $port_input = array('name' => $ocsSnmp['META']['NAME'],
               'mac' => $mac,
               'items_id' => $id_item,
               'itemtype' => $itemtype,
               'instantiation_type' => "NetworkPortEthernet",
               "entities_id" => $entity,
               "NetworkName__ipaddresses" => array("-100" => $ip),
               '_create_children' => 1,
               //'is_dynamic'         => 1,
               'is_deleted' => 0);

            $np->add($port_input, array(), $cfg_ocs['history_network']);
         }
      }

      return $id_item;
   }
   
   /**
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param $itemtype
    * @param int $ID
    * @param $ocsSnmp
    * @param $loc_id
    * @param $dom_id
    * @param $action
    * @param bool $linked
    * @return int
    */
   static function addOrUpdateOther($plugin_ocsinventoryng_ocsservers_id, $itemtype, $ID = 0, $ocsSnmp, $loc_id, $dom_id, $action, $linked = false, $cfg_ocs)
   {

      $snmpDevice = new $itemtype();

      $input = array(
         "is_dynamic" => 1,
         "entities_id" => (isset($_SESSION['glpiactive_entity'])?$_SESSION['glpiactive_entity']:0)
      );

      if (($cfg_ocs['importsnmp_name'] && $action == "add")
         || ($cfg_ocs['linksnmp_name'] && $linked)
         || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
         || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)
      ) {
         $input["name"] = $ocsSnmp['META']['NAME'];
      }
      if (($cfg_ocs['importsnmp_contact'] && $action == "add")
         || ($cfg_ocs['linksnmp_contact'] && $linked)
         || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
         || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)
      ) {
         $input["contact"] = $ocsSnmp['META']['CONTACT'];
      }
      if (($cfg_ocs['importsnmp_comment'] && $action == "add")
         || ($cfg_ocs['linksnmp_comment'] && $linked)
         || ($action == "update" && $cfg_ocs['importsnmp_name'] && !$linked)
         || ($action == "update" && $cfg_ocs['linksnmp_name'] && $linked)
      ) {
         $input["comment"] = $ocsSnmp['META']['DESCRIPTION'];
      }

      if ($loc_id > 0) {
         $input["locations_id"] = $loc_id;
      }
      if ($dom_id > 0 && $itemtype != "Phone") {
         $input["domains_id"] = $dom_id;
      }

      $id_item = 0;

      if ($action == "add") {
         $id_item = $snmpDevice->add($input, array('unicity_error_message' => true), $cfg_ocs['history_hardware']);
      } else {
         $input["id"] = $ID;
         $id_item = $ID;
         if ($snmpDevice->getFromDB($id_item)) {
            $input["entities_id"] = $snmpDevice->fields['entities_id'];
         }
            
         $snmpDevice->update($input, $cfg_ocs['history_hardware'], array('unicity_error_message' => false,
               '_no_history' => !$cfg_ocs['history_hardware']));
      }

      if ($id_item > 0
         && (($cfg_ocs['importsnmp_createport'] && $action == "add")
            || ($cfg_ocs['linksnmp_createport'] && $linked)
            || ($action == "update" && $cfg_ocs['importsnmp_createport'] && !$linked)
            || ($action == "update" && $cfg_ocs['linksnmp_createport'] && $linked))
      ) {

         //Add network port
         $ip = $ocsSnmp['META']['IPADDR'];
         $mac = $ocsSnmp['META']['MACADDR'];

         $np = new NetworkPort();
         $np->getFromDBByQuery("WHERE `mac` LIKE '$mac' AND `items_id` = '$id_item' AND `itemtype` LIKE '$itemtype' ");
         if (count($np->fields) < 1) {

            $item = new $itemtype();
            $entity = (isset($_SESSION['glpiactive_entity'])?$_SESSION['glpiactive_entity']:0);
            if ($item->getFromDB($id_item)) {
               $entity = $item->fields['entities_id'];
            }
            $port_input = array('name' => $ocsSnmp['META']['NAME'],
               'mac' => $mac,
               'items_id' => $id_item,
               'itemtype' => $itemtype,
               'instantiation_type' => "NetworkPortEthernet",
               "entities_id" => $entity,
               "NetworkName__ipaddresses" => array("-100" => $ip),
               '_create_children' => 1,
               //'is_dynamic'         => 1,
               'is_deleted' => 0);

            $np->add($port_input, array(), $cfg_ocs['history_network']);
         }
      }

      return $id_item;
   }

   /**
    * @param $ID
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @return array
    */
   static function updateSnmp($ID, $plugin_ocsinventoryng_ocsservers_id)
   {
      global $DB;

      $query = "SELECT * FROM `glpi_plugin_ocsinventoryng_snmpocslinks` 
               WHERE `id` = " . $ID . " 
               AND `plugin_ocsinventoryng_ocsservers_id` = " . $plugin_ocsinventoryng_ocsservers_id;
      $rep = $DB->query($query);
      while ($data = $DB->fetch_array($rep)) {
         $ocsid = $data['ocs_id'];
         $itemtype = $data['itemtype'];
         $items_id = $data['items_id'];
         $linked = $data['linked'];
      }

      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $ocsSnmp = $ocsClient->getSnmpDevice($ocsid);

      $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);

      $loc_id = 0;
      $dom_id = 0;
      if (($cfg_ocs['importsnmp_location'] && $linked == 0)
         || ($cfg_ocs['linksnmp_location'] && $linked)
      ) {
         $loc_id = Dropdown::importExternal('Location', PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['META']['LOCATION']));
      }
      if (($cfg_ocs['importsnmp_domain'] && $linked == 0)
         || ($cfg_ocs['linksnmp_domain'] && $linked)
      ) {
         $dom_id = Dropdown::importExternal('Domain', PluginOcsinventoryngOcsServer::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsSnmp['META']['DOMAIN']));
      }
      if ($itemtype == "Printer") {

         self::addOrUpdatePrinter($plugin_ocsinventoryng_ocsservers_id, $itemtype, $items_id, $ocsSnmp, $loc_id, $dom_id, "update", $linked,$cfg_ocs);

         $now = date("Y-m-d H:i:s");
         $sql = "UPDATE `glpi_plugin_ocsinventoryng_snmpocslinks` SET `last_update` = '" . $now . "' WHERE `id` = " . $ID . ";";
         $DB->query($sql);

         return array('status' => PluginOcsinventoryngOcsServer::SNMP_SYNCHRONIZED,
            //'entities_id'  => $data['entities_id'],
         );
      } else if ($itemtype == "NetworkEquipment") {

         self::addOrUpdateNetworkEquipment($plugin_ocsinventoryng_ocsservers_id, $itemtype, $items_id, $ocsSnmp, $loc_id, $dom_id, "update", $linked,$cfg_ocs);

         $now = date("Y-m-d H:i:s");
         $sql = "UPDATE `glpi_plugin_ocsinventoryng_snmpocslinks` SET `last_update` = '" . $now . "' WHERE `id` = " . $ID . ";";
         $DB->query($sql);

         return array('status' => PluginOcsinventoryngOcsServer::SNMP_SYNCHRONIZED,
            //'entities_id'  => $data['entities_id'],
         );
      } else if ($itemtype == "Computer") {

         self::addOrUpdateComputer($plugin_ocsinventoryng_ocsservers_id, $itemtype, $items_id, $ocsSnmp, $loc_id, $dom_id, "update", $linked,$cfg_ocs);

         $now = date("Y-m-d H:i:s");
         $sql = "UPDATE `glpi_plugin_ocsinventoryng_snmpocslinks` SET `last_update` = '" . $now . "' WHERE `id` = " . $ID . ";";
         $DB->query($sql);

         return array('status' => PluginOcsinventoryngOcsServer::SNMP_SYNCHRONIZED,
            //'entities_id'  => $data['entities_id'],
         );
      } else if ($itemtype == "Peripheral"
         || $itemtype == "Phone"
      ) {

         self::addOrUpdateOther($plugin_ocsinventoryng_ocsservers_id, $itemtype, $items_id, $ocsSnmp, $loc_id, $dom_id, "update", $linked,$cfg_ocs);

         $now = date("Y-m-d H:i:s");
         $sql = "UPDATE `glpi_plugin_ocsinventoryng_snmpocslinks` SET `last_update` = '" . $now . "' WHERE `id` = " . $ID . ";";
         $DB->query($sql);

         return array('status' => PluginOcsinventoryngOcsServer::SNMP_SYNCHRONIZED,
            //'entities_id'  => $data['entities_id'],
         );
      }


      return array('status' => PluginOcsinventoryngOcsServer::SNMP_NOTUPDATED,
         //'entities_id'  => $data['entities_id'],
      );
   }

   /**
    * Prints search form
    *
    * @param $params
    * @return nothing
    * @internal param the $manufacturer supplier choice
    * @internal param the $type device type
    */
   static function searchForm($params)
   {
      global $CFG_GLPI;
      
      // Default values of parameters
      $p['itemtype'] = '';
      $p['ip'] = '';
      $p['tolinked'] = 0;
      foreach ($params as $key => $val) {
         $p[$key] = $val;
      }

      $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsngsnmp.import.php';
      if ($p['tolinked'] > 0) {
         $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsngsnmp.link.php';
      }

      echo "<form name='form' method='post' action='" . $target . "'>";
      echo "<div align='center'><table class='tab_cadre_fixe' cellpadding='5'>";
      echo "<tr><th colspan='6'>" . __('Filter SNMP Objects list', 'ocsinventoryng') . "</th></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>";
      _e('By itemtype', 'ocsinventoryng');
      echo "</td><td class='center'>";
      Dropdown::showItemTypes("itemtype", self::$snmptypes, array('value' => $p['itemtype']));
      echo "</td>";

      echo "<td class='center'>";
      _e('By IP', 'ocsinventoryng');
      echo "</td><td class='center'>";
      echo "<input type=\"text\" name=\"ip\" value='" . $p['ip'] . "'>";
      echo "</td>";

      echo "<td>";
      echo "<input type=\"submit\" name=\"search\" class=\"submit\" value='" . _sx('button', 'Post') . "' >";

      echo "<a href='"
         . $target
         . (strpos($target, '?') ? '&amp;' : '?')
         . "reset=reset' >";
      echo "&nbsp;&nbsp;<img title=\"" . __s('Blank') . "\" alt=\"" . __s('Blank') . "\" src='" .
         $CFG_GLPI["root_doc"] . "/pics/reset.png' class='calendrier pointer'></a>";
      echo "</td>";
      echo "</tr>";

      echo "</table></div>";

      Html::closeForm();
   }

   // Show snmp devices to add :)
   /**
    * @param $params
    */
   static function showSnmpDeviceToAdd($params)
   {
      global $DB, $CFG_GLPI;

      // Default values of parameters
      $p['link'] = array();
      $p['field'] = array();
      $p['contains'] = array();
      $p['searchtype'] = array();
      $p['sort'] = '1';
      $p['order'] = 'ASC';
      $p['start'] = 0;
      $p['export_all'] = 0;
      $p['link2'] = '';
      $p['contains2'] = '';
      $p['field2'] = '';
      $p['itemtype2'] = '';
      $p['searchtype2'] = '';
      $p['itemtype'] = '';
      $p['ip'] = '';
      $p['tolinked'] = 0;
      $p['check'] = 'all';
      $p['plugin_ocsinventoryng_ocsservers_id'] = 0;

      foreach ($params as $key => $val) {
         $p[$key] = $val;
      }

      $tolinked = $p['tolinked'];
      $start = $p['start'];
      $plugin_ocsinventoryng_ocsservers_id = $p['plugin_ocsinventoryng_ocsservers_id'];

      $title = __('Import new SNMP devices', 'ocsinventoryng');
      if ($tolinked) {
         $title = __('Import new SNMP devices into glpi', 'ocsinventoryng');
      }
      $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsngsnmp.import.php';
      if ($tolinked) {
         $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsngsnmp.link.php';
      }

      if (!$start) {
         $start = 0;
      }

      // Get all links between glpi and OCS
      $query_glpi = "SELECT ocs_id
                     FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
                     WHERE `plugin_ocsinventoryng_ocsservers_id` = '" . $plugin_ocsinventoryng_ocsservers_id . "'";
      $result_glpi = $DB->query($query_glpi);
      $already_linked = array();
      if ($DB->numrows($result_glpi) > 0) {
         while ($data = $DB->fetch_array($result_glpi)) {
            $already_linked [] = $data["ocs_id"];
         }
      }

      $snmpOptions = array(
         'ORDER' => 'LASTDATE',
         'FILTER' => array(
            'EXCLUDE_IDS' => $already_linked
         ),
         'DISPLAY' => array(
            'CHECKSUM' => PluginOcsinventoryngOcsClient::CHECKSUM_BIOS
         ),
         'ORDER' => 'NAME'
      );

      //if ($cfg_ocs["tag_limit"] and $tag_limit = explode("$", trim($cfg_ocs["tag_limit"]))) {
      //   $snmpOptions['FILTER']['TAGS'] = $tag_limit;
      //}

      //if ($cfg_ocs["tag_exclude"] and $tag_exclude = explode("$", trim($cfg_ocs["tag_exclude"]))) {
      //   $snmpOptions['FILTER']['EXCLUDE_TAGS'] = $tag_exclude;
      //}
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $ocsResult = $ocsClient->getSnmp($snmpOptions);

      if (isset($ocsResult['SNMP'])) {
         if (count($ocsResult['SNMP'])) {
            // Get all hardware from OCS DB
            $hardware = array();
            $snmp = array_slice($ocsResult['SNMP'], $start, $_SESSION['glpilist_limit']);
            foreach ($snmp as $data) {
               $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));
               $id = $data['META']['ID'];
               $hardware[$id]["id"] = $data['META']["ID"];
               $hardware[$id]["date"] = $data['META']["LASTDATE"];
               $hardware[$id]["name"] = $data['META']["NAME"];
               $hardware[$id]["ipaddr"] = $data['META']["IPADDR"];
               $hardware[$id]["mac"] = $data['META']["MACADDR"];
               $hardware[$id]["snmpdeviceid"] = $data['META']["SNMPDEVICEID"];
               $hardware[$id]["description"] = $data['META']["DESCRIPTION"];
               $hardware[$id]["type"] = $data['META']["TYPE"];
               $hardware[$id]["contact"] = $data['META']["CONTACT"];
               $hardware[$id]["location"] = $data['META']["LOCATION"];
            }

            foreach ($hardware as $id => $field) {

               if ($field["type"] == "Network") {
                  $field["type"] = "NetworkEquipment";
               }

               if (!empty($p['itemtype'])
                  && $field['type'] != $p['itemtype']
               ) {
                  unset($hardware[$id]);
               }
               if (!empty($p['ip'])
                  && !preg_match("/" . $p['ip'] . "/", $field['ipaddr'])
               ) {
                  unset($hardware[$id]);
               }

            }
            $output_type = Search::HTML_OUTPUT;
            if (isset($_GET["display_type"])) {
               $output_type = $_GET["display_type"];
            }
            $parameters = "itemtype=" . $p['itemtype'] .
               "&amp;ip=" . $p['ip'];

            // Define begin and end var for loop
            // Search case
            $begin_display = $start;
            $end_display = $start + $_SESSION["glpilist_limit"];
            $numrows = $ocsResult['TOTAL_COUNT'];
            // Export All case
            if (isset($_GET['export_all'])) {
               $begin_display = 0;
               $end_display = $numrows;
            }
            $nbcols = 10;

            if ($output_type == Search::HTML_OUTPUT
               && $tolinked
               && count($hardware)
            ) {
               echo "<div class='center b'>" .
                  __('Caution! The imported data (see your configuration) will overwrite the existing one', 'ocsinventoryng') . "</div>";
            }

            if ($numrows) {
               $parameters = "";
               Html::printPager($start, $numrows, $target, $parameters);

               //Show preview form only in import even in multi-entity mode because computer import
               //can be refused by a rule
               /*if (!$tolinked) {
                  echo "<div class='firstbloc'>";
                  echo "<form method='post' name='ocsng_import_mode' id='ocsng_import_mode'
                         action='$target'>\n";
                  echo "<table class='tab_cadre_fixe'>";
                  echo "<tr><th>" . __('Manual import mode', 'ocsinventoryng') . "</th></tr>\n";
                  echo "<tr class='tab_bg_1'><td class='center'>";
                  echo "</td></tr>";
                  echo "</table>";
                  Html::closeForm();
                  echo "</div>";
               }*/
               if ($output_type == Search::HTML_OUTPUT) {
                  echo "<form method='post' name='ocsng_form' id='ocsng_form' action='$target'>";
               }
               if ($output_type == Search::HTML_OUTPUT && !$tolinked) {
                  echo "<div class='center'>";
                  PluginOcsinventoryngOcsServer::checkBox($target);
                  echo "</div>";
               }

               if ($output_type == Search::HTML_OUTPUT) {
                  echo "<table class='tab_cadrehov'>";

                  echo "<tr class='tab_bg_1'><td colspan='10' class='center'>";
                  if (!$tolinked) {
                     echo "<input class='submit' type='submit' name='import_ok' value=\"" .
                        _sx('button', 'Import', 'ocsinventoryng') . "\">";
                  } else {
                     echo "<input class='submit' type='submit' name='import_ok' value=\"" .
                        _sx('button', 'Link', 'ocsinventoryng') . "\">";
                  }
                  echo "</td></tr>\n";
               }

               echo Search::showHeader($output_type, $end_display - $begin_display + 1, $nbcols);
               echo Search::showNewLine($output_type);
               $header_num = 1;

               echo Search::showHeaderItem($output_type, __('Name'), $header_num);//, $linkto, $p['sort']==$val, $p['order']
               echo Search::showHeaderItem($output_type, __('Description'), $header_num);
               echo Search::showHeaderItem($output_type, __('IP address'), $header_num);
               echo Search::showHeaderItem($output_type, __('MAC address'), $header_num);
               echo Search::showHeaderItem($output_type, __('Date'), $header_num);
               echo Search::showHeaderItem($output_type, __('Contact SNMP', 'ocsinventoryng'), $header_num);
               echo Search::showHeaderItem($output_type, __('Location SNMP', 'ocsinventoryng'), $header_num);
               echo Search::showHeaderItem($output_type, __('Type SNMP', 'ocsinventoryng'), $header_num);

               if (!$tolinked) {
                  echo Search::showHeaderItem($output_type, __('Item type to create', 'ocsinventoryng'), $header_num, "", 0, "", 'width=15%');
                  echo Search::showHeaderItem($output_type, "", $header_num);
               } else {
                  echo Search::showHeaderItem($output_type, __('Item to link', 'ocsinventoryng'), $header_num, "", 0, "", 'width=15%');
               }
               // End Line for column headers
               echo Search::showEndLine($output_type);

               $row_num = 1;

               foreach ($hardware as $ID => $tab) {
                  $row_num++;
                  $item_num = 1;
                  if ($tab["type"] == "Network") {
                     $tab["type"] = "NetworkEquipment";
                  }

                  echo Search::showNewLine($output_type, $row_num % 2);
                  echo Search::showItem($output_type, $tab["name"], $item_num, $row_num);
                  echo Search::showItem($output_type, $tab["description"], $item_num, $row_num, 'width=15%');
                  echo Search::showItem($output_type, $tab["ipaddr"], $item_num, $row_num, 'width=5%');
                  echo Search::showItem($output_type, $tab["mac"], $item_num, $row_num, 'width=5%');
                  echo Search::showItem($output_type, Html::convDateTime($tab["date"]), $item_num, $row_num, 'width=15%');
                  echo Search::showItem($output_type, $tab["contact"], $item_num, $row_num, 'width=5%');
                  echo Search::showItem($output_type, $tab["location"], $item_num, $row_num, 'width=15%');
                  echo Search::showItem($output_type, $tab["type"], $item_num, $row_num);

                  if (!$tolinked) {
                     echo "<td width='15%'>";
                     $value = false;

                     if (getItemForItemtype($tab["type"])) {
                        $value = $tab["type"];
                     }
                     $type = "toimport_itemtype[" . $tab["id"] . "]";

                     Dropdown::showItemTypes($type, self::$snmptypes, array('value' => $value));
                     echo "</td>\n";
                  }
                  /* if ($p['change_import_mode'] && !$tolinked){
                    if (!isset ($data['entities_id']) || $data['entities_id'] == -1){
                    echo "<td class='center'><img src=\"".$CFG_GLPI['root_doc']. "/pics/redbutton.png\"></td>\n";
                    $data['entities_id'] = -1;
                    } else{
                    echo "<td class='center'>";
                    $tmprule = new RuleImportEntity();
                    if ($tmprule->can($data['_ruleid'],READ)){
                    echo "<a href='". $tmprule->getLinkURL()."'>".$tmprule->getName()."</a>";
                    }  else{
                    echo $tmprule->getName();
                    }
                    echo "</td>\n";
                    }
                    echo "<td width='30%'>";
                    $ent = "toimport_entities[".$tab["id"]."]";
                    Entity::dropdown(array('name'     => $ent,
                    'value'    => $data['entities_id'],
                    'comments' => 0));
                    echo "</td>\n";
                    } */
                  echo "<td width='10'>";
                  if (!$tolinked) {
                     echo "<input type='checkbox' name='toimport[" . $tab["id"] . "]' " .
                        ($p['check'] == "all" ? "checked" : "") . ">";
                  } else {

                     /* $tab['entities_id'] = $p['glpiactiveentities'];
                       $rulelink         = new RuleImportComputerCollection();
                       $rulelink_results = array();
                       $params           = array('entities_id' => $p['glpiactiveentities'],
                       'plugin_ocsinventoryng_ocsservers_id'
                       => $plugin_ocsinventoryng_ocsservers_id);
                       $rulelink_results = $rulelink->processAllRules(Toolbox::stripslashes_deep($tab),
                       array(), $params);

                       //Look for the computer using automatic link criterias as defined in OCSNG configuration
                       $options       = array('name' => "tolink[".$tab["id"]."]");
                       $show_dropdown = true;
                       //If the computer is not explicitly refused by a rule
                       if (!isset($rulelink_results['action'])
                       || $rulelink_results['action'] != PluginOcsinventoryngOcsServer::LINK_RESULT_NO_IMPORT){

                       if (!empty($rulelink_results['found_computers'])){
                       $options['value']  = $rulelink_results['found_computers'][0];
                       $options['entity'] = $p['glpiactiveentities'];
                       } */

                     /* } else{
                       echo "<img src='".$CFG_GLPI['root_doc']. "/pics/redbutton.png'>";
                       } */

                     $value = false;

                     if (getItemForItemtype($tab["type"])) {
                        $type = $tab["type"];
                        $options['name'] = "tolink_items[" . $tab["id"] . "]";

                        $self = new self;
                        if ($item = $self->getFromDBbyName($tab["type"], $tab["name"])) {
                           $options['value'] = (isset($item->fields['id'])) ? $item->fields['id'] : false;
                        }
                        $type::dropdown($options);
                        echo "<input type='hidden' name='tolink_itemtype[" . $tab["id"] . "]' value='" . $tab["type"] . "'>";

                     } else {

                        $mtrand = mt_rand();

                        $mynamei = "itemtype";
                        $myname = "tolink_items[" . $tab["id"] . "]";

                        $rand = Dropdown::showItemTypes($mynamei, self::$snmptypes, array('rand' => $mtrand));


                        $p = array('itemtype' => '__VALUE__',
                           //'entity_restrict' => $entity_restrict,
                           'id' => $tab["id"],
                           'rand' => $rand,
                           'myname' => $myname);
//print_r($p);
                        Ajax::updateItemOnSelectEvent("dropdown_$mynamei$rand", "results_$mynamei$rand", $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/ajax/dropdownitems.php", $p);
                        echo "<span id='results_$mynamei$rand'>\n";
                        echo "</span>\n";
                     }
                  }
                  echo "</td></tr>\n";
               }

               echo "<tr class='tab_bg_1'><td colspan='10' class='center'>";
               if (!$tolinked) {
                  echo "<input class='submit' type='submit' name='import_ok' value=\"" .
                     _sx('button', 'Import', 'ocsinventoryng') . "\">";
               } else {
                  echo "<input class='submit' type='submit' name='import_ok' value=\"" .
                     _sx('button', 'Link', 'ocsinventoryng') . "\">";
               }
               echo "<input type=hidden name='plugin_ocsinventoryng_ocsservers_id' " .
                  "value='" . $plugin_ocsinventoryng_ocsservers_id . "'>";
               echo "</td></tr>";
               echo "</table>\n";
               Html::closeForm();

               if (!$tolinked) {
                  echo "<div class='center'>";
                  PluginOcsinventoryngOcsServer::checkBox($target);
                  echo "</div>";
               }

               Html::printPager($start, $numrows, $target, $parameters);
            } else {
               echo "<table class='tab_cadre_fixe'>";
               echo "<tr><th>" . $title . "</th></tr>\n";
               echo "<tr class='tab_bg_1'>";
               echo "<td class='center b'>" . __('No new SNMP device to be imported', 'ocsinventoryng') .
                  "</td></tr>\n";
               echo "</table>";
            }
            echo "</div>";
         } else {
            echo "<div class='center'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . $title . "</th></tr>\n";
            echo "<tr class='tab_bg_1'>";
            echo "<td class='center b'>" . __('No new SNMP device to be imported', 'ocsinventoryng') .
               "</td></tr>\n";
            echo "</table></div>";
         }
      } else {
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>" . $title . "</th></tr>\n";
         echo "<tr class='tab_bg_1'>";
         echo "<td class='center b'>" . __('No new SNMP device to be imported', 'ocsinventoryng') .
            "</td></tr>\n";
         echo "</table></div>";
      }
   }

   /**
    * @param $itemtype
    * @param $name
    * @return itemtype
    */
   function getFromDBbyName($itemtype, $name)
   {
      $item = getItemForItemtype($itemtype);
      $item->getFromDBByQuery("WHERE `" . getTableForItemType($itemtype) . "`.`name` = '$name' ");
      return $item;
   }

   /**
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param $check
    * @param $start
    * @return bool|void
    */
   static function showSnmpDeviceToUpdate($plugin_ocsinventoryng_ocsservers_id, $check, $start)
   {
      global $DB, $CFG_GLPI;

      PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      if (!Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
         return false;
      }

      // Get linked computer ids in GLPI
      $already_linked_query = "SELECT `glpi_plugin_ocsinventoryng_snmpocslinks`.`ocs_id` AS ocsid
                               FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
                               WHERE `glpi_plugin_ocsinventoryng_snmpocslinks`.`plugin_ocsinventoryng_ocsservers_id`
                                            = '$plugin_ocsinventoryng_ocsservers_id'";
      $already_linked_result = $DB->query($already_linked_query);

      if ($DB->numrows($already_linked_result) == 0) {
         echo "<div class='center b'>" . __('No new SNMP device to be updated', 'ocsinventoryng') . "</div>";
         return;
      }

      $already_linked_ids = array();
      while ($data = $DB->fetch_assoc($already_linked_result)) {
         $already_linked_ids [] = $data['ocsid'];
      }

      // Fetch linked items from ocs
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $ocsResult = $ocsClient->getSnmp(array(
         'ORDER' => 'LASTDATE',
         'FILTER' => array(
            'IDS' => $already_linked_ids,
         )
      ));

      if (isset($ocsResult['SNMP'])) {
         if (count($ocsResult['SNMP']) > 0) {
            // Get all ids of the returned items
            $ocs_snmp_ids = array();
            $hardware = array();

            $snmps = array_slice($ocsResult['SNMP'], $start, $_SESSION['glpilist_limit']);
            foreach ($snmps as $snmp) {
               $LASTDATE = $snmp['META']['LASTDATE'];
               $ocs_snmp_inv [$snmp['META']['ID']] = $LASTDATE;
               $NAME = $snmp['META']['NAME'];
               $ocs_snmp_name [$snmp['META']['ID']] = $NAME;
               $ID = $snmp['META']['ID'];
               $ocs_snmp_ids[] = $ID;

               if (isset($snmp['PRINTER'])) {
                  $TYPE = "printer";
               } else {
                  $TYPE = "";
               }
               $ocs_snmp_type [$snmp['META']['ID']] = $TYPE;
            }

            // query snmp links
            $query = "SELECT * FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
                WHERE `glpi_plugin_ocsinventoryng_snmpocslinks`.`ocs_id` IN (" . implode(',', $ocs_snmp_ids) . ")";
            $result = $DB->query($query);

            // Get all links between glpi and OCS
            $already_linked = array();
            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetch_assoc($result)) {
                  $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));

                  $format = 'Y-m-d H:i:s';
//                  $last_glpi_update = DateTime::createFromFormat($format, $data['last_update']);
//                  $last_ocs_inventory = DateTime::createFromFormat($format, $ocs_snmp_inv[$data['ocs_id']]);
                  //TODOSNMP comment for test
                  //if ($last_ocs_inventory > $last_glpi_update) {
                  $already_linked[$data['id']] = $data;
                  //}
               }
            }
            echo "<div class='center'>";
            echo "<h2>" . __('Snmp device updated in OCSNG', 'ocsinventoryng') . "</h2>";

            $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsngsnmp.sync.php';
            if (($numrows = $ocsResult['TOTAL_COUNT']) > 0) {
               $parameters = "check=$check";
               Html::printPager($start, $numrows, $target, $parameters);

               echo "<form method='post' id='ocsng_form' name='ocsng_form' action='" . $target . "'>";
               PluginOcsinventoryngOcsServer::checkBox($target);

               echo "<table class='tab_cadre_fixe'>";
               echo "<tr class='tab_bg_1'><td colspan='6' class='center'>";
               echo "<input class='submit' type='submit' name='update_ok' value=\"" .
                  _sx('button', 'Synchronize', 'ocsinventoryng') . "\">";
               echo "&nbsp;<input class='submit' type='submit' name='delete' value=\"" .
                  _sx('button', 'Delete link', 'ocsinventoryng') . "\">";
               echo "</td></tr>\n";

               echo "<tr>";
               echo "<th>" . __('GLPI Object', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('Item type') . "</th>";
               echo "<th>" . __('OCS SNMP device', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('Import date in GLPI', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('Last OCSNG SNMP inventory date', 'ocsinventoryng') . "</th>";
               echo "<th>&nbsp;</th></tr>\n";

               foreach ($already_linked as $ID => $tab) {
                  echo "<tr class='tab_bg_2 center'>";
                  $item = new $tab["itemtype"]();
                  $item->getFromDB($tab["items_id"]);
                  echo "<td>" . $item->getLink() . "</td>\n";
                  echo "<td>" . $item->getTypeName() . "</td>\n";
                  echo "<td>" . $ocs_snmp_name[$tab["ocs_id"]] . "</td>\n";
                  echo "<td>" . Html::convDateTime($tab["last_update"]) . "</td>\n";
                  echo "<td>" . Html::convDateTime($ocs_snmp_inv[$tab["ocs_id"]]) . "</td>\n";
                  echo "<td><input type='checkbox' name='toupdate[" . $tab["id"] . "]' " .
                     (($check == "all") ? "checked" : "") . ">";
                  echo "</td></tr>\n";
               }

               echo "<tr class='tab_bg_1'><td colspan='6' class='center'>";
               echo "<input class='submit' type='submit' name='update_ok' value=\"" .
                  _sx('button', 'Synchronize', 'ocsinventoryng') . "\">";
               echo "&nbsp;<input class='submit' type='submit' name='delete' value=\"" .
                  _sx('button', 'Delete link', 'ocsinventoryng') . "\">";
               echo "<input type=hidden name='plugin_ocsinventoryng_ocsservers_id' " .
                  "value='$plugin_ocsinventoryng_ocsservers_id'>";
               echo "</td></tr>";

               echo "<tr class='tab_bg_1'><td colspan='6' class='center'>";
               PluginOcsinventoryngOcsServer::checkBox($target);
               echo "</table>\n";
               Html::closeForm();
               Html::printPager($start, $numrows, $target, $parameters);
            } else {
               echo "<br><span class='b'>" . __('Update SNMP device', 'ocsinventoryng') . "</span>";
            }
            echo "</div>";
         } else {
            echo "<div class='center b'>" . __('No new SNMP device to be updated', 'ocsinventoryng') . "</div>";
         }
      } else {
         echo "<div class='center b'>" . __('No new SNMP device to be updated', 'ocsinventoryng') . "</div>";
      }
   }

   /**
    * Make the item link between glpi and ocs.
    *
    * This make the database link between ocs and glpi databases
    *
    * @param $ocsid integer : ocs item unique id.
    * @param $plugin_ocsinventoryng_ocsservers_id integer : ocs server id
    * @param $items_id
    * @param $itemtype
    * return int : link id.
    * @internal param int $glpi_computers_id : glpi computer id
    * @return bool|item
    */
   static function ocsSnmpLink($ocsid, $plugin_ocsinventoryng_ocsservers_id, $items_id, $itemtype)
   {
      global $DB;

      PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);

      $ocsSnmp = $ocsClient->getSnmpDevice($ocsid);

      if (is_null($ocsSnmp)) {
         return false;
      }

      $query = "INSERT INTO `glpi_plugin_ocsinventoryng_snmpocslinks`
                       (`items_id`, `ocs_id`, `itemtype`,
                        `last_update`, `plugin_ocsinventoryng_ocsservers_id`, `linked`)
                VALUES ('$items_id', '$ocsid', '" . $itemtype . "',
                        '" . $_SESSION["glpi_currenttime"] . "', '$plugin_ocsinventoryng_ocsservers_id', '1')";
      $result = $DB->query($query);

      if ($result) {
         return ($DB->insert_id());
      }

      return false;
   }

   /**
    * @param $ocsid
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param $params
    * @return array|bool
    * @internal param $computers_id
    *
    */
   static function linkSnmpDevice($ocsid, $plugin_ocsinventoryng_ocsservers_id, $params)
   {

      PluginOcsinventoryngOcsServer::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      //TODOSNMP entites_id ?

      $p['itemtype'] = -1;
      $p['items_id'] = -1;
      foreach ($params as $key => $val) {
         $p[$key] = $val;
      }

      $ocs_id_change = true;
      /* $query = "SELECT *
        FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
        WHERE `ocs_id` = '$ocs_id'";

        $result           = $DB->query($query);
        $ocs_id_change    = false;
        $ocs_link_exists  = false;
        $numrows          = $DB->numrows($result);

        // Already link - check if the OCS computer already exists
        if ($numrows > 0) {
        $ocs_link_exists = true;
        $data            = $DB->fetch_assoc($result);

        $ocsComputer = $ocsClient->getComputer($data['ocsid']);

        // Not found
        if (is_null($ocsComputer)) {
        $idlink = $data["id"];
        $query  = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
        SET `ocsid` = '$ocsid'
        WHERE `id` = '" . $data["id"] . "'";

        if ($DB->query($query)) {
        $ocs_id_change = true;
        //Add history to indicates that the ocsid changed
        $changes[0] = '0';
        //Old ocsid
        $changes[1] = $data["ocsid"];
        //New ocsid
        $changes[2] = $ocsid;
        PluginOcsinventoryngOcslink::history($computers_id, $changes,
        PluginOcsinventoryngOcslink::HISTORY_OCS_IDCHANGED);
        }
        }
        }

        // No ocs_link or ocs id change does not exists so can link
        if ($ocs_id_change || !$ocs_link_exists) {
        $ocsConfig = PluginOcsinventoryngOcsServer::getConfig($plugin_ocsinventoryng_ocsservers_id);
        // Set OCS checksum to max value
        $ocsClient->setChecksum(PluginOcsinventoryngOcsClient::CHECKSUM_ALL, $ocsid);
       */
      if (
         //$ocs_id_change
         //||
         $p['itemtype'] != -1 && $p['items_id'] > 0 &&
         ($idlink = self::ocsSnmpLink($ocsid, $plugin_ocsinventoryng_ocsservers_id, $p['items_id'], $p['itemtype']))
      ) {
         /*
           // automatic transfer computer
           if (($CFG_GLPI['transfers_id_auto'] > 0)
           && Session::isMultiEntitiesMode()) {

           // Retrieve data from glpi_plugin_ocsinventoryng_ocslinks
           $ocsLink = new PluginOcsinventoryngOcslink();
           $ocsLink->getFromDB($idlink);

           if (count($ocsLink->fields)) {
           // Retrieve datas from OCS database
           $ocsComputer = $ocsClient->getComputer($ocsLink->fields['ocsid']);

           if (!is_null($ocsComputer)) {
           $ocsComputer = Toolbox::addslashes_deep($ocsComputer);
           PluginOcsinventoryngOcsServer::transferComputer($ocsLink->fields, $ocsComputer);
           }
           }
           }
           $comp = new Computer();
           $comp->getFromDB($computers_id);
           $input["id"]            = $computers_id;
           $input["entities_id"]   = $comp->fields['entities_id'];
           $input["is_dynamic"]    = 1;
           $input["_nolock"]       = true;

           // Not already import from OCS / mark default state
           if ((!$ocs_id_change && ($ocsConfig["states_id_default"] > 0))
           || (!$comp->fields['is_dynamic']
           && ($ocsConfig["states_id_default"] > 0))) {
           $input["states_id"] = $ocsConfig["states_id_default"];
           }
           $comp->update($input);
           // Auto restore if deleted
           if ($comp->fields['is_deleted']) {
           $comp->restore(array('id' => $computers_id));
           }

           // Reset only if not in ocs id change case
           if (!$ocs_id_change) {
           if ($ocsConfig["import_general_os"]) {
           PluginOcsinventoryngOcsServer::resetDropdown($computers_id, "operatingsystems_id", "glpi_operatingsystems");
           }
           if ($ocsConfig["import_device_processor"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'DeviceProcessor');
           }
           if ($ocsConfig["import_device_iface"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'DeviceNetworkCard');
           }
           if ($ocsConfig["import_device_memory"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'DeviceMemory');
           }
           if ($ocsConfig["import_device_hdd"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'DeviceHardDrive');
           }
           if ($ocsConfig["import_device_sound"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'DeviceSoundCard');
           }
           if ($ocsConfig["import_device_gfxcard"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'DeviceGraphicCard');
           }
           if ($ocsConfig["import_device_drive"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'DeviceDrive');
           }
           if ($ocsConfig["import_device_modem"] || $ocsConfig["import_device_port"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'DevicePci');
           }
           if ($ocsConfig["import_device_bios"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'PluginOcsinventoryngDeviceBiosdata');
           }
           if ($ocsConfig["import_device_motherboard"]) {
           PluginOcsinventoryngOcsServer::resetDevices($computers_id, 'DeviceMotherboard');
           }
           if ($ocsConfig["import_software"]) {
           PluginOcsinventoryngOcsServer::resetSoftwares($computers_id);
           }
           if ($ocsConfig["import_disk"]) {
           PluginOcsinventoryngOcsServer::resetDisks($computers_id);
           }
           if ($ocsConfig["import_periph"]) {
           PluginOcsinventoryngOcsServer::resetPeripherals($computers_id);
           }
           if ($ocsConfig["import_monitor"]==1) { // Only reset monitor as global in unit management
           PluginOcsinventoryngOcsServer::resetMonitors($computers_id);    // try to link monitor with existing
           }
           if ($ocsConfig["import_printer"]) {
           PluginOcsinventoryngOcsServer::resetPrinters($computers_id);
           }
           if ($ocsConfig["import_registry"]) {
           PluginOcsinventoryngOcsServer::resetRegistry($computers_id);
           }
           $changes[0] = '0';
           $changes[1] = "";
           $changes[2] = $ocsid;
           PluginOcsinventoryngOcslink::history($computers_id, $changes,
           PluginOcsinventoryngOcslink::HISTORY_OCS_LINK);
           }
          */
         self::updateSnmp($idlink, $plugin_ocsinventoryng_ocsservers_id);
         return array('status' => PluginOcsinventoryngOcsServer::SNMP_LINKED,
            //'entities_id'  => $data['entities_id'],
         );
      }
      /*
        } else {
        //TRANS: %s is the OCS id
        Session::addMessageAfterRedirect(sprintf(__('Unable to import, GLPI computer is already related to an element of OCSNG (%d)',
        'ocsinventoryng'), $ocsid),
        false, ERROR);
        } */
      return false;
   }
}