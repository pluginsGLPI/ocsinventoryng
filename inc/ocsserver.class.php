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

/// OCS config class
/**
 * Class PluginOcsinventoryngOcsServer
 */
class PluginOcsinventoryngOcsServer extends CommonDBTM {

   static $types = array('Computer');
   static $rightname = "plugin_ocsinventoryng";
   // From CommonDBTM
   public $dohistory = true;

   // Connection types
   const CONN_TYPE_DB = 0;
   const CONN_TYPE_SOAP = 1;
   const OCS_VERSION_LIMIT = 4020;
   const OCS1_3_VERSION_LIMIT = 5004;
   const OCS2_VERSION_LIMIT = 6000;
   const OCS2_1_VERSION_LIMIT = 7006;
   const OCS2_2_VERSION_LIMIT = 7009;
   // Class constants - import_ management
   const FIELD_SEPARATOR = '$$$$$';
   const IMPORT_TAG_070 = '_version_070_';
   const IMPORT_TAG_072 = '_version_072_';
   const IMPORT_TAG_078 = '_version_078_';
   // TODO use PluginOcsinventoryngOcsClient constants
   // Class constants - OCSNG Flags on Checksum
   // See Apache/Ocsinventory/Map.pm
   const HARDWARE_FL = 0;//1
   const BIOS_FL = 1;//2
   const MEMORIES_FL = 2;//4
   const SLOTS_FL = 3;//8
   const REGISTRY_FL = 4;//16
   const CONTROLLERS_FL = 5;//32
   const MONITORS_FL = 6;//64
   const PORTS_FL = 7;//128
   const STORAGES_FL = 8;//256
   const DRIVES_FL = 9;//512
   const INPUTS_FL = 10;//1024
   const MODEMS_FL = 11;//2048
   const NETWORKS_FL = 12;//4096
   const PRINTERS_FL = 13;//8192
   const SOUNDS_FL = 14;//16384
   const VIDEOS_FL = 15;//32768
   const SOFTWARES_FL = 16;//65536
   const VIRTUALMACHINES_FL = 17; //131072
   const CPUS_FL = 18; //added into OCS2_1_VERSION_LIMIT - 262144

   const SIMS_FL = 19; //not used added into OCS2_1_VERSION_LIMIT - 524288
   const BATTERIES_FL = 20; //not used  added into OCS2_2_VERSION_LIMIT - 1048576

   const MAX_CHECKSUM = 524287;//262143;//With < 19 (with 20 : 2097151)
   // Class constants - Update result
   const COMPUTER_IMPORTED = 0; //Computer is imported in GLPI
   const COMPUTER_SYNCHRONIZED = 1; //Computer is synchronized
   const COMPUTER_LINKED = 2; //Computer is linked to another computer already in GLPI
   const COMPUTER_FAILED_IMPORT = 3; //Computer cannot be imported because it matches none of the rules
   const COMPUTER_NOTUPDATED = 4; //Computer should not be updated, nothing to do
   const COMPUTER_NOT_UNIQUE = 5; //Computer import is refused because it's not unique
   const COMPUTER_LINK_REFUSED = 6; //Computer cannot be imported because a rule denies its import
   const LINK_RESULT_IMPORT = 0;
   const LINK_RESULT_NO_IMPORT = 1;
   const LINK_RESULT_LINK = 2;
   // Class constants - Update result
   const SNMP_IMPORTED = 10; //SNMP Object is imported in GLPI
   const SNMP_SYNCHRONIZED = 11; //SNMP is synchronized
   const SNMP_LINKED = 12; //SNMP is linked to another object already in GLPI
   const SNMP_FAILED_IMPORT = 13; //SNMP cannot be imported - no itemtype
   const SNMP_NOTUPDATED = 14; //SNMP should not be updated, nothing to do
   const IPDISCOVER_IMPORTED = 15; //IPDISCOVER Object is imported in GLPI
   const IPDISCOVER_NOTUPDATED = 16; //IPDISCOVER should not be updated, nothing to do
   const IPDISCOVER_FAILED_IMPORT = 17; //IPDISCOVER cannot be imported - no itemtype
   const IPDISCOVER_SYNCHRONIZED = 18; //IPDISCOVER is synchronized

   const ACTION_PURGE_COMPUTER = 0; // Action cronCleanOldAgents : Purge computer
   const ACTION_DELETE_COMPUTER = 1; // Action cronCleanOldAgents : delete computer

   /**
    * @param int $nb
    * @return translated
    */
   static function getTypeName($nb = 0)
   {
      return _n('OCSNG server', 'OCSNG servers', $nb, 'ocsinventoryng');
   }

   /**
    * @param CommonGLPI $item
    * @param int $withtemplate
    * @return string
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               //If connection to the OCS DB  is ok, and all rights are ok too
               $ong[1] = __('Test');

               if (self::checkOCSconnection($item->getID())
                  && self::checkVersion($item->getID())
                  && self::checkTraceDeleted($item->getID())
               ) {
                  $ong[2] = __('Datas to import', 'ocsinventoryng');
                  $ong[3] = __('Import options', 'ocsinventoryng');
                  $ong[4] = __('General history', 'ocsinventoryng');
               }
               if ($item->getField('ocs_url')) {
                  $ong[5] = __('OCSNG console', 'ocsinventoryng');
               }

               return $ong;
         }


      }
      return '';
   }

   /**
    * @param CommonGLPI $item
    * @param int $tabnum
    * @param int $withtemplate
    * @return bool
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {

      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1 :
               $item->showDBConnectionStatus($item->getID());
               break;

            case 2 :
               $item->ocsFormConfig($item->getID());
               break;

            case 3 :
               $item->ocsFormImportOptions($item->getID());
               break;

            case 4 :
               $item->ocsHistoryConfig($item->getID());
               break;

            case 5 :
               self::showOcsReportsConsole($item->getID());
               break;
         }
      }
      return true;
   }

   /**
    * @param array $options
    * @return array
    */
   function defineTabs($options = array())
   {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('PluginOcsinventoryngSnmpOcslink', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);
      return $ong;

   }

   /**
    * @return array
    */
   function getSearchOptions()
   {

      $tab = array();

      $tab['common'] = self::getTypeName();

      $tab[1]['table'] = $this->getTable();
      $tab[1]['field'] = 'name';
      $tab[1]['name'] = __('Name');
      $tab[1]['datatype'] = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[2]['table'] = $this->getTable();
      $tab[2]['field'] = 'id';
      $tab[2]['name'] = __('ID');
      $tab[2]['massiveaction'] = false;

      $tab[3]['table'] = $this->getTable();
      $tab[3]['field'] = 'ocs_db_host';
      $tab[3]['name'] = __('Server');

      $tab[6]['table'] = $this->getTable();
      $tab[6]['field'] = 'is_active';
      $tab[6]['name'] = __('Active');
      $tab[6]['datatype'] = 'bool';

      $tab[19]['table'] = $this->getTable();
      $tab[19]['field'] = 'date_mod';
      $tab[19]['name'] = __('Last update');
      $tab[19]['datatype'] = 'datetime';
      $tab[19]['massiveaction'] = false;

      $tab[16]['table'] = $this->getTable();
      $tab[16]['field'] = 'comment';
      $tab[16]['name'] = __('Comments');
      $tab[16]['datatype'] = 'text';

      $tab[17]['table'] = $this->getTable();
      $tab[17]['field'] = 'use_massimport';
      $tab[17]['name'] = __('Expert sync mode', 'ocsinventoryng');
      $tab[17]['datatype'] = 'bool';

      $tab[18]['table'] = $this->getTable();
      $tab[18]['field'] = 'ocs_db_utf8';
      $tab[18]['name'] = __('Database in UTF8', 'ocsinventoryng');
      $tab[18]['datatype'] = 'bool';

      return $tab;
   }

   /**
    * Print ocs menu
    *
    * @param $plugin_ocsinventoryng_ocsservers_id Integer : Id of the ocs config
    *
    * @return Nothing (display)
    * */
   static function setupMenu($plugin_ocsinventoryng_ocsservers_id)
   {
      global $CFG_GLPI, $DB;
      $name = "";
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
                      ON `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id`
                        = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
                   WHERE `profiles_id`= " . $_SESSION["glpiactiveprofile"]['id'] . "
                         AND `glpi_plugin_ocsinventoryng_ocsservers`.`is_active`='1'
                   ORDER BY `name` ASC";
         foreach ($DB->request($query) as $data) {
            $ocsservers[] = $data['id'];
         }
         Dropdown::show('PluginOcsinventoryngOcsServer', array("condition"           => "`id` IN ('" . implode("','", $ocsservers) . "')",
                                                               "value"               => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                                                               "on_change"           => "this.form.submit()",
                                                               "display_emptychoice" => false));
         echo "</td></tr>";
         echo "<tr class='tab_bg_2'><td colspan='2' class ='center red'>";
         echo __('If you not find your OCSNG server in this dropdown, please check if your profile can access it !', 'ocsinventoryng');
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

      $usemassimport = self::useMassImport();

      echo "<div class='center'><table class='tab_cadre_fixe' width='40%'>";

      echo "<tr><th colspan='4'>";
      printf(__('%1$s %2$s'), __('OCSNG server', 'ocsinventoryng'), $name);
      echo "</th></tr>";

      if (Session::haveRight("plugin_ocsinventoryng", READ)) {

         //config server
         if ($isactive) {
            echo "<tr class='tab_bg_1'><td class='center b' colspan='" . ($usemassimport ? 2 : 4) . "'>
                  <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsserver.form.php?id=$plugin_ocsinventoryng_ocsservers_id'>
                   <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/ocsserver.png' " .
               "alt='" . __s("Configuration of OCSNG server", 'ocsinventoryng') . "' " .
               "title=\"" . __s("Configuration of OCSNG server", 'ocsinventoryng') . "\">
                   <br>" . sprintf(__('Configuration of OCSNG server %s', 'ocsinventoryng'), $name) . "
                  </a></td>";

            if ($usemassimport && Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
               //config massimport
               echo "<td class='center b' colspan='2'>
                     <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/config.form.php'>
                      <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/synchro.png' " .
                  "alt='" . __s("Automatic synchronization's configuration", 'ocsinventoryng') . "' " .
                  "title=\"" . __s("Automatic synchronization's configuration", 'ocsinventoryng') . "\">
                        <br>" . __("Automatic synchronization's configuration", 'ocsinventoryng') . "
                     </a></td>";
            }
            echo "</tr>\n";

         }

      }

      echo "<tr><th colspan='4'>";
      echo __('Setup rules engine', 'ocsinventoryng');
      echo "</th></tr>";

      echo "<tr class='tab_bg_1'><td class='center b' colspan='2'>
            <a href='" . $CFG_GLPI["root_doc"] . "/front/ruleimportentity.php'>" . __('Rules for assigning an item to an entity') . "
            </a>";
      echo "<br><div class='red'>";
      echo __('Setup rules for choose entity on items import', 'ocsinventoryng');
      echo "</div></td>";

      echo "<td class='center b' colspan='2'>
            <a href='" . $CFG_GLPI["root_doc"] . "/front/ruleimportcomputer.php'>" . __('Rules for import and link computers') . "
         </a>";
      echo "<br><div class='red'>";
      echo __('Setup rules for select criteria for items link', 'ocsinventoryng');
      echo "</div></td>";

      echo "</tr>\n";

      echo "</table></div>";
   }


   /**
    * Print ocs menu
    *
    * @param $plugin_ocsinventoryng_ocsservers_id Integer : Id of the ocs config
    *
    * @return Nothing (display)
    * */
   static function importMenu($plugin_ocsinventoryng_ocsservers_id) {
      global $CFG_GLPI, $DB;

      $name                = "";
      $ocsservers          = array();
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
         foreach ($DB->request($query) as $data) {
            $ocsservers[] = $data['id'];
         }
         Dropdown::show('PluginOcsinventoryngOcsServer', array("condition"           => "`id` IN ('" . implode("','", $ocsservers) . "')",
                                                               "value"               => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                                                               "on_change"           => "this.form.submit()",
                                                               "display_emptychoice" => false));
         echo "</td></tr>";
         echo "<tr class='tab_bg_2'><td colspan='2' class ='center red'>";
         echo __('If you not find your OCSNG server in this dropdown, please check if your profile can access it !', 'ocsinventoryng');
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
      $result   = $DB->query($sql);

      $isactive = 0;
      if ($DB->numrows($result) > 0) {
         $datas    = $DB->fetch_array($result);
         $name     = " : " . $datas["name"];
         $isactive = $datas["is_active"];
      }

      $usemassimport = self::useMassImport();

      echo "<div class='center'><table class='tab_cadre_fixe' width='40%'>";
      echo "<tr><th colspan='" . ($usemassimport ? 4 : 2) . "'>";
      printf(__('%1$s %2$s'), __('OCSNG server', 'ocsinventoryng'), $name);
      echo "<br>";
      if (Session::haveRight("plugin_ocsinventoryng", READ)
          && $isactive) {
         echo "<a href='".$CFG_GLPI["root_doc"]."/plugins/ocsinventoryng/front/ocsserver.form.php?id=".
                $plugin_ocsinventoryng_ocsservers_id . "&forcetab=PluginOcsinventoryngOcsServer\$2'>";
         echo __('See Setup : Datas to import before', 'ocsinventoryng');
         echo "</a>";
      }
      echo "</th></tr>";

      if (Session::haveRight("plugin_ocsinventoryng", READ)) {
         //config server
         if ($isactive) {
            if (Session::haveRight("plugin_ocsinventoryng_import", UPDATE)
                || Session::haveRight("plugin_ocsinventoryng", UPDATE)) {

                echo "<tr class='tab_bg_1'>";
                if (Session::haveRight("plugin_ocsinventoryng_import", UPDATE)) {
                   echo "<td class='center b' colspan='2'>
                         <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.import.php'>
                         <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/import.png' " .
                           "alt='" . __s('Import new computers', 'ocsinventoryng') . "' " .
                           "title=\"" . __s('Import new computers', 'ocsinventoryng') . "\">
                         <br>" . __('Import new computers', 'ocsinventoryng') . "
                         </a></td>";
                } else {
                   echo "<td class='center b' colspan='2'></td>";
                }

                if ($usemassimport && Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
                  //threads
                  echo "<td class='center b' colspan='2'>
                        <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/thread.php?plugin_ocsinventoryng_ocsservers_id=" . $plugin_ocsinventoryng_ocsservers_id . "'>
                        <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/thread.png' " .
                          "alt='" . __s('Scripts execution of automatic actions', 'ocsinventoryng') . "' " .
                          "title=\"" . __s('Scripts execution of automatic actions', 'ocsinventoryng') . "\">
                        <br>" . __('Scripts execution of automatic actions', 'ocsinventoryng') . "
                        </a></td>";
                }
                echo "</tr>\n";
             }

             //manual synchro
             if (Session::haveRight("plugin_ocsinventoryng_sync", READ)
                || Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
                echo "<tr class='tab_bg_1'>";
                if (Session::haveRight("plugin_ocsinventoryng_sync", READ) && $isactive) {
                   echo "<td class='center b' colspan='2'>
                         <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.sync.php'>
                         <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/synchro1.png' " .
                           "alt='" . __s('Synchronize computers already imported', 'ocsinventoryng') . "' " .
                           "title=\"" . __s('Synchronize computers already imported', 'ocsinventoryng') . "\">
                         <br>" . __('Synchronize computers already imported', 'ocsinventoryng') . "
                         </a></td>";
                } else {
                   echo "<td class='center b' colspan='2'></td>";
                }

                if (Session::haveRight("plugin_ocsinventoryng", UPDATE) && $usemassimport) {
                   //host imported by thread
                   echo "<td class='center b' colspan='2'>
                         <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/detail.php'>
                         <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/detail.png' " .
                           "alt='" . __s('Computers imported by automatic actions', 'ocsinventoryng') . "' " .
                           "title=\"" . __s('Computers imported by automatic actions', 'ocsinventoryng') . "\">
                         <br>" . __('Computers imported by automatic actions', 'ocsinventoryng') . "
                         </a></td>";
                }
                echo "</tr>\n";
             }

             //link
             if (Session::haveRight("plugin_ocsinventoryng_link", UPDATE)
                 || Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
                echo "<tr class='tab_bg_1'>";

                if (Session::haveRight("plugin_ocsinventoryng_link", UPDATE) && $isactive) {
                   echo "<td class='center b' colspan='2'>
                         <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.link.php'>
                         <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/link.png' " .
                           "alt='" . __s('Link new OCSNG computers to existing GLPI computers', 'ocsinventoryng') . "' " .
                           "title=\"" . __s('Link new OCSNG computers to existing GLPI computers', 'ocsinventoryng') . "\">
                         <br>" . __('Link new OCSNG computers to existing GLPI computers', 'ocsinventoryng') . "
                         </a></td>";
                } else {
                   echo "<td class='center b' colspan='2'></td>";
                }

                if ($usemassimport && Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
                //host not imported by thread
                   echo "<td class='center b' colspan='2'>
                         <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/notimportedcomputer.php'>
                         <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/notimported.png' " .
                           "alt='" . __s('Computers not imported by automatic actions', 'ocsinventoryng') . "' " .
                           "title=\"" . __s('Computers not imported by automatic actions', 'ocsinventoryng') . "\" >
                         <br>" . __('Computers not imported by automatic actions', 'ocsinventoryng') . "
                         </a></td>";
                }
                echo "</tr>\n";
             }
          }
          if (!$isactive) {
             echo "<tr class='tab_bg_2'><td class='center red' colspan='2'>";
             echo __('The selected server is not active. Import and synchronisation is not available', 'ocsinventoryng');
             echo "</td></tr>\n";
          }
      }

      if ((Session::haveRight("plugin_ocsinventoryng_clean", UPDATE)
           || Session::haveRight(static::$rightname, UPDATE))
          && $isactive) {
         if (Session::haveRight(static::$rightname, UPDATE)) {
            echo "<tr class='tab_bg_1'><td class='center b' colspan='" . ($usemassimport ? 4 : 2) . "'>
                  <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/deleted_equiv.php'>
                  <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/trash.png' " .
                    "alt='" . __s('Clean OCSNG deleted computers', 'ocsinventoryng') . "' " .
                    "title=\"" . __s('Clean OCSNG deleted computers', 'ocsinventoryng') . "\" >
                  <br>" . __('Clean OCSNG deleted computers', 'ocsinventoryng') . "
                  </a></td></tr>";
         }

         echo "<tr class='tab_bg_1'><td class='center b' colspan='" . ($usemassimport ? 4 : 2) . "'>
               <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.clean.php'>
               <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/clean.png' " .
                  "alt='" . __s('Clean links between GLPI and OCSNG', 'ocsinventoryng') . "' " .
                  "title=\"" . __s('Clean links between GLPI and OCSNG', 'ocsinventoryng') . "\" >
               <br>" . __('Clean links between GLPI and OCSNG', 'ocsinventoryng') . "
               </a></td><tr>";
      }
      echo "</table></div>";
   }


   /**
    * Print ocs config form
    *
    * @param $ID Integer : Id of the ocs config
    * @return bool
    * @internal param form $target target
    */
   function ocsFormConfig($ID)
   {

      if (!Session::haveRight("plugin_ocsinventoryng", READ)) {
         return false;
      }
      $this->getFromDB($ID);
      echo "<div class='center'>";
      echo "<form name='formconfig' id='formconfig' action='" . Toolbox::getItemTypeFormURL("PluginOcsinventoryngOcsServer") . "' method='post'>";
      echo "<table class='tab_cadre_fixe'>\n";
      echo "<tr><th>";
      echo __('All');

      echo $JS = <<<JAVASCRIPT
         <script type='text/javascript'>
            function form_init_all(form, value) {
               var selects = $("form[id='formconfig'] select");

               $.each(selects, function(index, select){
                  if (select.name != "import_otherserial"
                        && select.name != "import_location"
                           && select.name != "import_group"
                              && select.name != "import_contact_num"
                                 && select.name != "import_use_date"
                                    && select.name != "import_network") {
                    $(select).select2('val', value);
                  }
               });
            }
         </script>
JAVASCRIPT;
      Dropdown::showYesNo('init_all', 0, -1, array(
         'width'     => '10%',
         'on_change' => "form_init_all(this.form, this.selectedIndex);"
      ));
      echo "</th></tr>";

      echo "<tr class='tab_bg_2'>\n";
      echo "<td class='top'>\n";


      echo $JS = <<<JAVASCRIPT
         <script type='text/javascript'>
         function accordion(id, openall) {
             if(id == undefined){
                 id  = 'accordion';
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

      echo "<div id='accordion'>";

      echo "<h2><a href='#'>" . __('General information', 'ocsinventoryng') . "</a></h2>";

      echo "<div>";
      echo "<table class='tab_cadre' width='100%'>";
      echo "<tr class='tab_bg_2'>";
      echo "<th colspan='4'><input type='hidden' name='id' value='$ID'>" . __('General information', 'ocsinventoryng') . "<br><span style='color:red;'>" . __('Warning : the import entity rules depends on selected fields', 'ocsinventoryng') . "</span>\n";
      echo "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Name') . "</td>\n<td>";
      Dropdown::showYesNo("import_general_name", $this->fields["import_general_name"]);
      echo "</td>\n";
      echo "<td class='center'>" . __('Operating system') . "</td>\n<td>";
      Dropdown::showYesNo("import_general_os", $this->fields["import_general_os"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . __('Serial of the operating system') . "</td>\n<td>";
      Dropdown::showYesNo("import_os_serial", $this->fields["import_os_serial"]);
      echo "</td>\n";
      echo "<td class='center'>" . __('Serial number') . "</td>\n<td>";
      Dropdown::showYesNo("import_general_serial", $this->fields["import_general_serial"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Depends on Bios import', 'ocsinventoryng')));
      echo "&nbsp;</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Model') . "</td>\n<td>";
      Dropdown::showYesNo("import_general_model", $this->fields["import_general_model"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Depends on Bios import', 'ocsinventoryng')));
      echo "&nbsp;</td>\n";
      echo "<td class='center'>" . _n('Manufacturer', 'Manufacturers', 1) . "</td>\n<td>";
      Dropdown::showYesNo("import_general_manufacturer", $this->fields["import_general_manufacturer"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Depends on Bios import', 'ocsinventoryng')));
      echo "&nbsp;</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Type') . "</td>\n<td>";
      Dropdown::showYesNo("import_general_type", $this->fields["import_general_type"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Depends on Bios import', 'ocsinventoryng')));
      echo "&nbsp;</td>\n";
      echo "<td class='center'>" . __('Domain') . "</td>\n<td>";
      Dropdown::showYesNo("import_general_domain", $this->fields["import_general_domain"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . __('Comments') . "</td>\n<td>";
      Dropdown::showYesNo("import_general_comment", $this->fields["import_general_comment"]);
      echo "</td>\n";

      if (self::checkOCSconnection($ID) && self::checkVersion($ID)) {
         echo "<td class='center'>" . __('UUID') . "</td>\n<td>";
         Dropdown::showYesNo("import_general_uuid", $this->fields["import_general_uuid"]);
      } else {
         echo "<td class='center'>";
         echo "<input type='hidden' name='import_general_uuid' value='0'>";
      }
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('IP') . "</td>\n<td>";
      Dropdown::showYesNo("import_ip", $this->fields["import_ip"]);
      echo "</td>\n";

      echo "<td colspan='2'></td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<th colspan='4'>" . __('User informations', 'ocsinventoryng');
      echo "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Alternate username') . "</td>\n<td>";
      Dropdown::showYesNo("import_general_contact", $this->fields["import_general_contact"]);
      echo "</td>\n";
      echo "<td class='center'>" . __('Affect user from contact') . "</td>\n<td>";
      Dropdown::showYesNo("import_user", $this->fields["import_user"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Depends on contact import', 'ocsinventoryng')));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Affect user location by default') . "</td>\n<td>";
      Dropdown::showYesNo("import_user_location", $this->fields["import_user_location"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Depends on contact import', 'ocsinventoryng')));
      echo "</td>\n";
      echo "<td class='center'>" . __('Affect first group of user by default') . "</td>\n<td>";
      Dropdown::showYesNo("import_user_group", $this->fields["import_user_group"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Depends on contact import', 'ocsinventoryng')));
      echo "</td></tr>\n";

      echo "</table>";
      echo "</div>";

      //Components

      echo "<h2><a href='#'>" . _n('Component', 'Components', 2) . "</a></h2>";

      echo "<div>";
      echo "<table class='tab_cadre' width='100%'>";
      echo "<th colspan='4'>" . _n('Component', 'Components', 2);
      echo "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Processor') . "</td>\n<td>";
      Dropdown::showYesNo("import_device_processor", $this->fields["import_device_processor"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('After 7006 version of OCS Inventory NG', 'ocsinventoryng')));
      echo "&nbsp;</td>\n";
      echo "<td class='center'>" . __('Memory') . "</td>\n<td>";
      Dropdown::showYesNo("import_device_memory", $this->fields["import_device_memory"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Hard drive') . "</td>\n<td>";
      Dropdown::showYesNo("import_device_hdd", $this->fields["import_device_hdd"]);
      echo "</td>\n";
      echo "<td class='center'>" . __('Network card') . "</td>\n<td>";
      Dropdown::showYesNo("import_device_iface", $this->fields["import_device_iface"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Graphics card') . "</td>\n<td>";
      Dropdown::showYesNo("import_device_gfxcard", $this->fields["import_device_gfxcard"]);
      echo "&nbsp;&nbsp;</td>";
      echo "<td class='center'>" . __('Soundcard') . "</td>\n<td>";
      Dropdown::showYesNo("import_device_sound", $this->fields["import_device_sound"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . _n('Drive', 'Drives', 2) . "</td>\n<td>";
      Dropdown::showYesNo("import_device_drive", $this->fields["import_device_drive"]);
      echo "</td>\n";
      echo "<td class='center'>" . __('Modems') . "</td>\n<td>";
      Dropdown::showYesNo("import_device_modem", $this->fields["import_device_modem"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . _n('Port', 'Ports', 2) . "</td>\n<td>";
      Dropdown::showYesNo("import_device_port", $this->fields["import_device_port"]);
      echo "</td>\n";
      echo "<td class='center'>" . __('Bios') . "</td>\n<td>";
      Dropdown::showYesNo("import_device_bios", $this->fields["import_device_bios"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('System board') . "</td>\n<td>";
      Dropdown::showYesNo("import_device_motherboard", $this->fields["import_device_motherboard"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('After 7009 version of OCS Inventory NG && Depends on Bios import', 'ocsinventoryng')));
      echo "&nbsp;</td>\n";
      echo "<td class='center'>" . _n('Controller', 'Controllers', 2) . "</td>\n<td>";
      Dropdown::showYesNo("import_device_controller", $this->fields["import_device_controller"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . _n('Other component', 'Other components', 2) . "</td>\n<td>";
      Dropdown::showYesNo("import_device_slot", $this->fields["import_device_slot"]);
      echo "</td><td colspan='2'></td></tr>\n";

      echo "</table>";
      echo "</div>";

      //Linked objects

      echo "<h2><a href='#'>" . __('Linked objects', 'ocsinventoryng') . "</a></h2>";

      echo "<div>";
      echo "<table class='tab_cadre' width='100%'>";

      echo "<tr><th colspan='4'>" . __('Linked objects', 'ocsinventoryng') . "</th>\n";

      $import_array = array("0" => __('No import'),
                            "1" => __('Global import', 'ocsinventoryng'),
                            "2" => __('Unit import', 'ocsinventoryng'));

      $import_array2 = array("0" => __('No import'),
                             "1" => __('Global import', 'ocsinventoryng'),
                             "2" => __('Unit import', 'ocsinventoryng'),
                             "3" => __('Unit import on serial number', 'ocsinventoryng'),
                             "4" => __('Unit import serial number only', 'ocsinventoryng'));

      $periph = $this->fields["import_periph"];
      $monitor = $this->fields["import_monitor"];
      $printer = $this->fields["import_printer"];
      $software = $this->fields["import_software"];

      echo "<tr class='tab_bg_2'><td class='center'>" . _n('Device', 'Devices', 2) . " </td>\n<td>";
      Dropdown::showFromArray("import_periph", $import_array, array('value' => $periph));
      echo "</td>\n";
      echo "<td class='center'>" . _n('Monitor', 'Monitors', 2) . "</td>\n<td>";
      Dropdown::showFromArray("import_monitor", $import_array2, array('value' => $monitor));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Comments') . " " . _n('Monitor', 'Monitors', 2) . " </td>\n<td>";
      Dropdown::showYesNo("import_monitor_comment", $this->fields["import_monitor_comment"]);
      echo "</td>\n";
      echo "<td class='center'>" . _n('Printer', 'Printers', 2) . "</td>\n<td>";
      Dropdown::showFromArray("import_printer", $import_array, array('value' => $printer));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . _n('Software', 'Software', 2) . "</td>\n<td>";
      $import_array = array("0" => __('No import'),
                            "1" => __('Unit import', 'ocsinventoryng'));
      Dropdown::showFromArray("import_software", $import_array, array('value' => $software));
      echo "</td>\n";
      echo "<td class='center'>" . _n('Volume', 'Volumes', 2) . "</td>\n<td>";
      Dropdown::showYesNo("import_disk", $this->fields["import_disk"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Registry', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("import_registry", $this->fields["import_registry"]);
      echo "</td>\n";
      //check version
      if ($this->fields['ocs_version'] > self::OCS1_3_VERSION_LIMIT) {
         echo "<td class='center'>" .
            _n('Virtual machine', 'Virtual machines', 2) . "</td>\n<td>";
         Dropdown::showYesNo("import_vms", $this->fields["import_vms"]);
         echo "</td>\n";
      } else {
         echo "<td class='center'>";
         echo "<input type='hidden' name='import_vms' value='0'>";
         echo "</td>\n";
      }
      echo "</tr>\n";

      echo "<tr><th colspan='4'>" . __('OCS Inventory NG plugins', 'ocsinventoryng') . "</th>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Microsoft Office licenses', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("import_officepack", $this->fields["import_officepack"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Depends on software import and OfficePack Plugin for (https://github.com/PluginsOCSInventory-NG/officepack) OCSNG must be installed', 'ocsinventoryng')));
      echo "</td><td class='center'>" . __('Antivirus', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("import_antivirus", $this->fields["import_antivirus"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Security Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/security) must be installed', 'ocsinventoryng')));
      echo "&nbsp;</td>\n";
      echo "</tr>";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Uptime', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("import_uptime", $this->fields["import_uptime"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Uptime Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/uptime) must be installed', 'ocsinventoryng')));
      echo "&nbsp;</td><td class='center'>" . __('Windows Update State', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("import_winupdatestate", $this->fields["import_winupdatestate"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Winupdate Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/winupdate) must be installed', 'ocsinventoryng')));
      echo "&nbsp;</td></tr>\n";


      echo "<tr class='tab_bg_2'><td class='center'>" . __('Teamviewer', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("import_teamviewer", $this->fields["import_teamviewer"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Teamviewer Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/teamviewer) must be installed', 'ocsinventoryng')));
      echo "&nbsp;</td><td class='center'>" . __('Proxy Settings', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("import_proxysetting", $this->fields["import_proxysetting"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Navigator Proxy Setting Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/navigatorproxysetting) must be installed', 'ocsinventoryng')));
      echo "&nbsp;</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Windows Users', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("import_winusers", $this->fields["import_winusers"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Winusers Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/winusers) must be installed', 'ocsinventoryng')));
      echo "&nbsp;</td><td class='center'>" . __('Service', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("import_service", $this->fields["import_service"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Service Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/services) must be installed', 'ocsinventoryng')));
      echo "&nbsp;</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Running Process', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("import_runningprocess", $this->fields["import_runningprocess"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Running Process Plugin for OCSNG (https://github.com/PluginsOCSInventory-NG/runningProcess) must be installed', 'ocsinventoryng')));
      echo "&nbsp;</td><td class='center' colspan='2'></td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center b red' colspan='4'>";
      echo __('No import: the plugin will not import these elements', 'ocsinventoryng');
      echo "<br>" . __('Global import: everything is imported but the material is globally managed (without duplicate)', 'ocsinventoryng');
      echo "<br>" . __("Unit import: everything is imported as it is", 'ocsinventoryng');
      echo "</td></tr>";

      echo "</table>";
      echo "</div>";

      //Administrative information

      echo "<h2><a href='#'>" . __('OCSNG administrative information', 'ocsinventoryng') . "</a></h2>";

      echo "<div>";
      echo "<table class='tab_cadre' width='100%'>";
      echo "<th colspan='4'>" . __('OCSNG administrative information', 'ocsinventoryng');
      echo "</th></tr>\n";

      $opt = self::getColumnListFromAccountInfoTable($ID, 'accountinfo');
      $oserial = $opt;
      $oserial['ASSETTAG'] = "ASSETTAG";
      echo "<table class='tab_cadre' width='100%'>";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Inventory number') . " </td>\n";
      echo "<td>";
      $link = new PluginOcsinventoryngOcsAdminInfosLink();
      $link->getFromDBbyOcsServerIDAndGlpiColumn($ID, "otherserial");

      $value = (isset($link->fields["ocs_column"]) ? $link->fields["ocs_column"] : "");
      Dropdown::showFromArray("import_otherserial", $oserial, array('value' => $value,
                                                                    'width' => '100%'));
      echo "</td>\n";
      echo "<td class='center'>" . __('Location') . " </td>\n";
      echo "<td>";
      $link = new PluginOcsinventoryngOcsAdminInfosLink();
      $link->getFromDBbyOcsServerIDAndGlpiColumn($ID, "locations_id");

      $value = (isset($link->fields["ocs_column"]) ? $link->fields["ocs_column"] : "");
      Dropdown::showFromArray("import_location", $opt, array('value' => $value,
                                                             'width' => '100%'));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Group') . " </td>\n";
      echo "<td>";
      $link = new PluginOcsinventoryngOcsAdminInfosLink();
      $link->getFromDBbyOcsServerIDAndGlpiColumn($ID, "groups_id");

      $value = (isset($link->fields["ocs_column"]) ? $link->fields["ocs_column"] : "");
      Dropdown::showFromArray("import_group", $opt, array('value' => $value,
                                                          'width' => '100%'));

      echo "</td>\n";
      echo "<td class='center'>" . __('Alternate username number') . " </td>\n";
      echo "<td>";
      $link = new PluginOcsinventoryngOcsAdminInfosLink();
      $link->getFromDBbyOcsServerIDAndGlpiColumn($ID, "contact_num");

      $value = (isset($link->fields["ocs_column"]) ? $link->fields["ocs_column"] : "");
      Dropdown::showFromArray("import_contact_num", $opt, array('value' => $value,
                                                                'width' => '100%'));

      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Network') . " </td>\n";
      echo "<td>";
      $link = new PluginOcsinventoryngOcsAdminInfosLink();
      $link->getFromDBbyOcsServerIDAndGlpiColumn($ID, "networks_id");

      $value = (isset($link->fields["ocs_column"]) ? $link->fields["ocs_column"] : "");
      Dropdown::showFromArray("import_network", $opt, array('value' => $value,
                                                            'width' => '100%'));

      echo "</td>\n";
      $opt_date = self::getColumnListFromAccountInfoTable($ID, 'hardware');
      echo "<td class='center'>" . __('Startup date') . " </td>\n";
      echo "<td>";
      $link = new PluginOcsinventoryngOcsAdminInfosLink();
      $link->getFromDBbyOcsServerIDAndGlpiColumn($ID, "use_date");

      $value = (isset($link->fields["ocs_column"]) ? $link->fields["ocs_column"] : "");
      Dropdown::showFromArray("import_use_date", $opt_date, array('value' => $value,
                                                                  'width' => '100%'));
      echo "</td></tr>\n";

      echo "</table>";
      echo "</div>";

      echo "</div>";

      echo "<script>accordion();</script>";

      echo "</td></tr>\n";

      if (Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
         echo "<tr class='tab_bg_2 center'><td colspan='4'>";
         echo "<input type='submit' name='update' class='submit' value=\"" .
                _sx('button', 'Save') . "\">";
         echo "</td></tr>\n";
      }
      echo "</table>\n";
      Html::closeForm();
      echo "</div>\n";
   }

   /**
    * Print ocs history form
    *
    * @param $ID Integer : Id of the ocs config
    *
    *
    * @return bool
    */
   function ocsHistoryConfig($ID)
   {

      if (!Session::haveRight("plugin_ocsinventoryng", READ)) {
         return false;
      }
      $this->getFromDB($ID);
      echo "<div class='center'>";
      echo "<form name='historyconfig' id='historyconfig' action='" . Toolbox::getItemTypeFormURL("PluginOcsinventoryngOcsServer") . "' method='post'>";
      echo "<table class='tab_cadre_fixe'>\n";
      echo "<tr><th colspan ='4'>";
      echo __('All');

      echo $JS = <<<JAVASCRIPT
         <script type='text/javascript'>
            function form_init_all(form, value) {
               var selects = $("form[id='historyconfig'] select");

               $.each(selects, function(index, select){
                    $(select).select2('val', value);
               });
            }
         </script>
JAVASCRIPT;
      Dropdown::showYesNo('init_all', 0, -1, array(
         'width'     => '10%',
         'on_change' => "form_init_all(this.form, this.selectedIndex);"
      ));
      echo "</th></tr>";
      echo "<tr>
      <th colspan='4'><input type='hidden' name='id' value='$ID'>" . __('General history', 'ocsinventoryng') .
         "</th>\n";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Do history', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("dohistory", $this->fields["dohistory"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('System history', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("history_hardware", $this->fields["history_hardware"]);
      echo "&nbsp;";
      $fields = __('Operating system');
      $fields .= "<br>";
      $fields .= __('Serial of the operating system');
      $fields .= "<br>";
      $fields .= __('Domain');
      $fields .= "<br>";
      $fields .= __('Alternate username');
      $fields .= "<br>";
      $fields .= __('Comments');
      Html::showToolTip(nl2br($fields));
      echo "&nbsp;</td></tr>\n";

      //history_bios
      echo "<tr class='tab_bg_2'><td class='center'>" . __('Bios history', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("history_bios", $this->fields["history_bios"]);
      echo "&nbsp;";
      $fields = __('Serial number');
      $fields .= "<br>";
      $fields .= __('Model');
      $fields .= "<br>";
      $fields .= _n('Manufacturer', 'Manufacturers', 1);
      $fields .= "<br>";
      $fields .= __('Type');
      $fields .= "<br>";
      if (self::checkOCSconnection($ID) && self::checkVersion($ID)) {
         $fields .= __('UUID');
      }
      Html::showToolTip(nl2br($fields));
      echo "&nbsp;</td>\n";

      echo "<td class='center'>" . __('Devices history', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("history_devices", $this->fields["history_devices"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Volumes history', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("history_drives", $this->fields["history_drives"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Network history', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("history_network", $this->fields["history_network"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Monitor connection history', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("history_monitor", $this->fields["history_monitor"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Printer connection history', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("history_printer", $this->fields["history_printer"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Peripheral connection history', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("history_peripheral", $this->fields["history_peripheral"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Software connection history', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("history_software", $this->fields["history_software"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Virtual machines history', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("history_vm", $this->fields["history_vm"]);
      echo "</td>\n";

      echo "<td class='center'>" . __('Administrative infos history', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("history_admininfos", $this->fields["history_admininfos"]);
      echo "</td></tr>\n";

      if (Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
         echo "<tr class='tab_bg_2 center'><td colspan='4'>";
         echo "<input type='submit' name='update' class='submit' value=\"" .
                _sx('button', 'Save') . "\">";
         echo "</td></tr>\n";
      }
      echo "</table>\n";
      Html::closeForm();
      echo "</div>\n";
   }

   /**
    * @param $ID
    * @internal param $withtemplate (default '')
    * @internal param $templateid (default '')
    */
   function ocsFormImportOptions($ID)
   {
      $this->getFromDB($ID);
      echo "<div class='center'>";
      echo "<form name='formconfig' action='" . Toolbox::getItemTypeFormURL("PluginOcsinventoryngOcsServer") . "' method='post'>";
      echo "<table class='tab_cadre_fixe'>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . __('Web address of the OCSNG console', 'ocsinventoryng');
      echo "<input type='hidden' name='id' value='$ID'>" . " </td>\n";
      echo "<td><input type='text' size='30' name='ocs_url' value=\"" . $this->fields["ocs_url"] . "\">";
      echo "</td></tr>\n";

      echo "<tr><th colspan='2'>" . __('Import options', 'ocsinventoryng') . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" .
         __('Limit the import to the following tags (separator $, nothing for all)', 'ocsinventoryng') . "</td>\n";
      echo "<td><input type='text' size='30' name='tag_limit' value='" . $this->fields["tag_limit"] . "'>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" .
         __('Exclude the following tags (separator $, nothing for all)', 'ocsinventoryng') .
         "</td>\n";
      echo "<td><input type='text' size='30' name='tag_exclude' value='" .
         $this->fields["tag_exclude"] . "'></td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Default status', 'ocsinventoryng') .
         "</td>\n<td>";
      State::dropdown(array('name'  => 'states_id_default',
                            'value' => $this->fields["states_id_default"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Behavior when disconnecting', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showFromArray("deconnection_behavior", array(''       => __('Preserve link', 'ocsinventoryng'),
                                                             "trash"  => __('Put the link in dustbin and add a lock', 'ocsinventoryng'),
                                                             "delete" => __('Delete  the link permanently', 'ocsinventoryng')), array('value' => $this->fields["deconnection_behavior"]));
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center b red' colspan='4'>";
      echo __("Define the action to do on link with other objects when computer is disconnecting from them", 'ocsinventoryng');
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Use the OCSNG software dictionary', 'ocsinventoryng') . "</td>\n<td>";
      Dropdown::showYesNo("use_soft_dict", $this->fields["use_soft_dict"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center b red' colspan='4'>";
      echo __("If Use the OCSNG software dictionary parameter is checked, no software will be imported before you setup them into OCS", 'ocsinventoryng');
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>" .
         __('Number of items to synchronize via the automatic OCSNG action', 'ocsinventoryng') .
         "</td>\n<td>";
      Dropdown::showNumber('cron_sync_number', array('value' => $this->fields['cron_sync_number'],
                                                     'min'   => 1,
                                                     'toadd' => array(0 => __('None'))));
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center b red' colspan='4'>";
      echo __("The automatic task ocsng is launched only if server is not in expert mode", 'ocsinventoryng');
      echo "<br>" . __("The automatic task ocsng only synchronize existant computers, it doesn't import new computers", 'ocsinventoryng');
      echo "<br>" . __("If you want to import new computers, disable this parameter, change to expert mode and use script from system", 'ocsinventoryng');
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>" .
         __('Behavior to the deletion of a computer in OCSNG', 'ocsinventoryng') . "</td>";
      echo "<td>";
      $actions[0] = Dropdown::EMPTY_VALUE;
      $actions[1] = __('Put in dustbin');
      foreach (getAllDatasFromTable('glpi_states') as $state) {
         $actions['STATE_' . $state['id']] = sprintf(__('Change to state %s', 'ocsinventoryng'), $state['name']);
      }
      Dropdown::showFromArray('deleted_behavior', $actions, array('value' => $this->fields['deleted_behavior']));
      echo "</td></tr>";

      if (Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
         echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
         echo "<input type='submit' name='update' class='submit' value='" .
                _sx('button', 'Save') . "'>";
         echo "</td></tr>";
      }

      echo "</table>\n";
      Html::closeForm();
      echo "</div>";
   }

   /**
    *
    */
   function post_getEmpty()
   {
      $this->fields['ocs_db_host'] = "localhost";
      $this->fields['ocs_db_name'] = "ocsweb";
      $this->fields['ocs_db_user'] = "ocsuser";
      $this->fields['ocs_db_utf8'] = 1;
      $this->fields['is_active'] = 1;
      $this->fields['use_locks'] = 1;
   }

   /**
    * Print simple ocs config form (database part)
    *
    * @param $ID        integer : Id of the ocs config
    * @param $options   array
    *     - target form target
    *
    * @return Nothing (display)
    * */
   function showForm($ID, $options = array())
   {

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      $conn_type_values = array(
         0 => __('Database', 'ocsinventoryng'),
         //1 => __('Webservice (SOAP)', 'ocsinventoryng'),
      );

      $sync_method_values = array(
         0 => __("Standard (allow manual actions)", "ocsinventoryng"),
         1 => __("Expert (Fully automatic, for large configuration)", "ocsinventoryng")
      );

      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>" . __('Connection type', 'ocsinventoryng') . "</td>";
      echo "<td id='conn_type_container'>";
      Dropdown::showFromArray('conn_type', $conn_type_values, array('value'     => $this->fields['conn_type'],
                                                                    'on_change' => "form_init_all(this.form, this.selectedIndex);"));
      echo "</td>";
      echo "<td class='center'>" . __("Active") . "</td>";
      echo "<td>";
      Dropdown::showYesNo("is_active", $this->fields["is_active"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>" . __("Name") . "</td>";
      echo "<td><input type='text' name='name' value='" . $this->fields["name"] . "'></td>";
      echo "<td class='center'>";
      if ($ID) {
         printf(__('%1$s : %2$s'), _n("Version", "Versions", 1), $this->fields["ocs_version"]);
      }
      echo "</td>";
      echo "<td>";
      if ($ID) {
         printf(__('%1$s : %2$s'), "Checksum", $this->fields["checksum"]);
         echo "&nbsp;";
         Html::showSimpleForm(Toolbox::getItemTypeFormURL("PluginOcsinventoryngOcsServer"), 'force_checksum',
            _sx('button', 'Reload Checksum', 'ocsinventoryng'),
            array('id' => $ID));
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>" . __("Host", "ocsinventoryng") . "</td>";
      echo "<td><input type='text' name='ocs_db_host' value='" . $this->fields["ocs_db_host"] . "'>";
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Like http://127.0.0.1 for SOAP method', 'ocsinventoryng')));
      echo "</td>";
      echo "<td class='center'>" . __("Synchronisation method", "ocsinventoryng") . "</td>";
      echo "<td>";
      Dropdown::showFromArray('use_massimport', $sync_method_values, array('value' => $this->fields['use_massimport']));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1 hide_if_soap' ";
      if ($this->fields["conn_type"] == self::CONN_TYPE_SOAP) {
         echo "style='display:none'";
      }
      echo "><td class='center'>" . __("Database") . "</td>";
      echo "<td><input type='text' name='ocs_db_name' value='" . $this->fields["ocs_db_name"] . "'></td>";
      echo "<td class='center'>" . __("Database in UTF8", "ocsinventoryng") . "</td>";
      echo "<td>";
      Dropdown::showYesNo("ocs_db_utf8", $this->fields["ocs_db_utf8"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>" . _n("User", "Users", 1) . "</td>";
      echo "<td><input type='text' name='ocs_db_user' value='" . $this->fields["ocs_db_user"] . "'></td>";
      echo "<td class='center' rowspan='2'>" . __("Comments") . "</td>";
      echo "<td rowspan='2'><textarea cols='45' rows='6' name='comment'>" . $this->fields["comment"] . "</textarea></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>" . __("Password") . "</td>";
      echo "<td><input type='password' name='ocs_db_passwd' value='' autocomplete='off'>";
      if ($ID) {
         echo "<input type='checkbox' name='_blank_passwd'>&nbsp;" . __("Clear");
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>" . __('Use automatic action for clean old agents & drop from OCSNG software', 'ocsinventoryng') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("use_cleancron", $this->fields["use_cleancron"], -1 , array('on_change' => 'hide_show_cleancron(this.value);'));
      echo "</td>";
      echo Html::scriptBlock("
         function hide_show_cleancron(val) {
            var display = (val == 0) ? 'none' : '';
            var notdisplay = (val == 0) ? '' : 'none';
            document.getElementById('show_cleancron_td1').style.display = display;
            document.getElementById('show_cleancron_td2').style.display = display;
            document.getElementById('show_cleancron_td3').style.display = notdisplay;
            document.getElementById('show_cleancron_tr1').style.display = display;
         }");
      $style = ($this->fields["use_cleancron"]) ? "" : "style='display: none '";
      $notstyle = ($this->fields["use_cleancron"]) ? "style='display: none '" : "";

      echo "<td class='center' id='show_cleancron_td1' $style>" . __("Action") . "</td>";
      $actions = self::getValuesActionCron();
      echo "<td id='show_cleancron_td2' $style>";
      Dropdown::showFromArray("action_cleancron", $actions, array('value' => $this->fields["action_cleancron"]));
      echo "</td>";

      echo "<td colspan ='2' id='show_cleancron_td3' $notstyle></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' id='show_cleancron_tr1' $style>";
      echo "<td class='center'>" . __('Use automatic action restore deleted computer', 'ocsinventoryng') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("use_restorationcron", $this->fields["use_restorationcron"], -1, array('on_change' => 'hide_show_restorecron(this.value);'));
      echo "</td>";

      echo Html::scriptBlock("
         function hide_show_restorecron(val) {
            var display = (val == 0) ? 'none' : '';
            var notdisplay = (val == 0) ? '' : 'none';
            document.getElementById('show_restorecron_td1').style.display = display;
            document.getElementById('show_restorecron_td2').style.display = display;
            document.getElementById('show_restorecron_td3').style.display = notdisplay;
         }");
      $style = ($this->fields["use_restorationcron"]) ? "" : "style='display: none '";
      $notstyle = ($this->fields["use_restorationcron"]) ? "style='display: none '" : "";

      echo "<td class='center' id='show_restorecron_td1' $style>" . __("Number of days for the restoration of computers from the date of last inventory", "ocsinventoryng") . "</td>";
      echo "<td id='show_restorecron_td2' $style>";
      Dropdown::showNumber("delay_restorationcron", array('value' => $this->fields["delay_restorationcron"]));
      echo "</td>";

      echo "<td colspan ='2' id='show_restorecron_td3' $notstyle></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>" . __('Use automatic action to check entity assignment rules', 'ocsinventoryng');
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Use automatic action to check and send a notification for machines that no longer respond the entity and location assignment rules', 'ocsinventoryng')));
      echo "</td>";
      echo "<td colspan='3'>";
      Dropdown::showYesNo("use_checkruleimportentity", $this->fields["use_checkruleimportentity"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>" . __('Use automatic locks', 'ocsinventoryng') . "</td>";
      echo "<td colspan='3'>";
      Dropdown::showYesNo("use_locks", $this->fields["use_locks"]);
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
   }

   /**
    *
    * @return type
    */
   static function getValuesActionCron() {
      $values = array();
      $values[self::ACTION_PURGE_COMPUTER] = self::getActionCronName(self::ACTION_PURGE_COMPUTER);
      $values[self::ACTION_DELETE_COMPUTER] = self::getActionCronName(self::ACTION_DELETE_COMPUTER);

      return $values;
   }

   /**
    *
    * @param type $value
    * @return type
    */
   static function getActionCronName($value) {
      switch ($value) {
         case self::ACTION_PURGE_COMPUTER :
            return __('Purge computers in OCSNG', 'ocsinventoryng');

         case self::ACTION_DELETE_COMPUTER :
            return __('Delete computers', 'ocsinventoryng');
         default :
            // Return $value if not define
            return $value;
      }
   }

   /**
    * check is one of the servers use_mass_import sync mode
    *
    * @return boolean
    * */
   static function useMassImport()
   {
      return countElementsInTable('glpi_plugin_ocsinventoryng_ocsservers', 'use_massimport');
   }

   /**
    * @param $ID
    * */
   function showDBConnectionStatus($ID)
   {

      $out = "<br><div class='center'>\n";
      $out .= "<table class='tab_cadre_fixe'>";
      $out .= "<tr><th>" . __('Connecting to the database', 'ocsinventoryng') . "</th></tr>\n";
      $out .= "<tr class='tab_bg_2'>";
      if ($ID != -1) {
         if (!self::checkOCSconnection($ID)) {
            $out .= "<td class='center red'>" . __('Connection to the database failed', 'ocsinventoryng');
         } else if (!self::checkVersion($ID)) {
            $out .= "<td class='center red'>" . __('Invalid OCSNG Version: RC3 is required', 'ocsinventoryng');
         } else if (!self::checkTraceDeleted($ID)) {
            $out .= "<td class='center red'>" . __('Invalid OCSNG configuration (TRACE_DELETED must be active)', 'ocsinventoryng');
            // TODO
            /* } else if (!self::checkConfig(4)) {
              $out .= __('Access denied on database (Need write rights on hardware.CHECKSUM necessary)',
              'ocsinventoryng');
              } else if (!self::checkConfig(8)) {
              $out .= __('Access denied on database (Delete rights in deleted_equiv table necessary)',
              'ocsinventoryng'); */
         } else {
            $out .= "<td class='center'>" . __('Connection to database successful', 'ocsinventoryng');
            $out .= "</td></tr>\n<tr class='tab_bg_2'>" .
               "<td class='center'>" . __('Valid OCSNG configuration and version', 'ocsinventoryng');
         }
      }
      $out .= "</td></tr>\n";
      $out .= "</table></div>";
      echo $out;
   }

   /**
    * @param datas $input
    * @return datas
    */
   function prepareInputForUpdate($input)
   {

      $this->updateAdminInfo($input);
      if (isset($input["ocs_db_passwd"]) && !empty($input["ocs_db_passwd"])) {
         $input["ocs_db_passwd"] = rawurlencode(stripslashes($input["ocs_db_passwd"]));
      } else {
         unset($input["ocs_db_passwd"]);
      }

      if (isset($input["_blank_passwd"]) && $input["_blank_passwd"]) {
         $input['ocs_db_passwd'] = '';
      }

      return $input;
   }

   /**
    *
    */
   function pre_updateInDB()
   {

      // Update checksum
      $checksum = 0;
      if (
         //$this->fields["import_device_processor"] ||
         $this->fields["import_general_contact"]
         || $this->fields["import_general_comment"]
         || $this->fields["import_general_domain"]
         || $this->fields["import_general_os"]
         || $this->fields["import_general_name"]
      ) {

         $checksum |= pow(2, self::HARDWARE_FL);
      }
      if ($this->fields["import_device_bios"]) {
         $checksum |= $checksum + pow(2, self::BIOS_FL);
      }
      if ($this->fields["import_device_memory"]) {
         $checksum |= $checksum + pow(2, self::MEMORIES_FL);
      }
      if ($this->fields["import_registry"]) {
         $checksum |= pow(2, self::REGISTRY_FL);
      }
      if ($this->fields["import_device_controller"]) {
         $checksum |= pow(2, self::CONTROLLERS_FL);
      }
      if ($this->fields["import_device_slot"]) {
         $checksum |= pow(2, self::SLOTS_FL);
      }
      if ($this->fields["import_monitor"]) {
         $checksum |= pow(2, self::MONITORS_FL);
      }
      if ($this->fields["import_device_port"]) {
         $checksum |= pow(2, self::PORTS_FL);
      }
      if ($this->fields["import_device_hdd"] || $this->fields["import_device_drive"]) {
         $checksum |= pow(2, self::STORAGES_FL);
      }
      if ($this->fields["import_disk"]) {
         $checksum |= pow(2, self::DRIVES_FL);
      }
      if ($this->fields["import_periph"]) {
         $checksum |= pow(2, self::INPUTS_FL);
      }
      if ($this->fields["import_device_modem"]) {
         $checksum |= pow(2, self::MODEMS_FL);
      }
      if ($this->fields["import_ip"]
         || $this->fields["import_device_iface"]
      ) {
         $checksum |= pow(2, self::NETWORKS_FL);
      }
      if ($this->fields["import_printer"]) {
         $checksum |= pow(2, self::PRINTERS_FL);
      }
      if ($this->fields["import_device_sound"]) {
         $checksum |= pow(2, self::SOUNDS_FL);
      }
      if ($this->fields["import_device_gfxcard"]) {
         $checksum |= pow(2, self::VIDEOS_FL);
      }
      if ($this->fields["import_software"]) {
         $checksum |= pow(2, self::SOFTWARES_FL);
      }
      if ($this->fields["import_vms"]) {
         $checksum |= pow(2, self::VIRTUALMACHINES_FL);
      }
      if ($this->fields["import_device_processor"]) {
         $checksum |= pow(2, self::CPUS_FL);
      }

      $this->updates[] = "checksum";
      $this->fields["checksum"] = $checksum;
   }

   /**
    * @param datas $input
    * @return bool|datas
    */
   function prepareInputForAdd($input)
   {
      global $DB;

      // Check if server config does not exists
      $query = "SELECT *
                FROM `" . $this->getTable() . "`
                WHERE `name` = '" . $input['name'] . "';";
      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         Session::addMessageAfterRedirect(__('Unable to add. The OCSNG server already exists.', 'ocsinventoryng'), false, ERROR);
         return false;
      }

      if (isset($input["ocs_db_passwd"]) && !empty($input["ocs_db_passwd"])) {
         $input["ocs_db_passwd"] = rawurlencode(stripslashes($input["ocs_db_passwd"]));
      } else {
         unset($input["ocs_db_passwd"]);
      }
      return $input;
   }

   /**
    *
    */
   function post_addItem()
   {
      global $DB;

      $query = "INSERT INTO  `glpi_plugin_ocsinventoryng_ocsservers_profiles` (`id` ,`plugin_ocsinventoryng_ocsservers_id` , `profiles_id`)
                                    VALUES (NULL ,  '" . $this->fields['id'] . "',  '" . $_SESSION["glpiactiveprofile"]['id'] . "');";
      $DB->query($query);
   }

   /**
    *
    */
   function cleanDBonPurge()
   {

      $link = new PluginOcsinventoryngOcslink();
      $link->deleteByCriteria(array('plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']));

      $admin = new PluginOcsinventoryngOcsAdminInfosLink();
      $admin->deleteByCriteria(array('plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']));

      $server = new PluginOcsinventoryngServer();
      $server->deleteByCriteria(array('plugin_ocsinventoryng_ocsservers_id' => $this->fields['id']));

      unset($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);

      // ocsservers_id for RuleImportComputer, OCS_SERVER for RuleImportEntity
      Rule::cleanForItemCriteria($this);
      Rule::cleanForItemCriteria($this, 'OCS_SERVER');
   }

   /**
    * Update Admin Info retrieve config
    *
    * @param $tab data array
    * */
   function updateAdminInfo($tab)
   {
      if (isset($tab["import_location"])
         || isset($tab["import_otherserial"])
         || isset($tab["import_group"])
         || isset($tab["import_network"])
         || isset($tab["import_contact_num"])
         || isset($tab["import_use_date"])
      ) {

         $adm = new PluginOcsinventoryngOcsAdminInfosLink();
         $adm->cleanForOcsServer($tab["id"]);

         if (isset($tab["import_location"])) {
            if ($tab["import_location"] != "") {
               $adm = new PluginOcsinventoryngOcsAdminInfosLink();
               $adm->fields["plugin_ocsinventoryng_ocsservers_id"] = $tab["id"];
               $adm->fields["glpi_column"] = "locations_id";
               $adm->fields["ocs_column"] = $tab["import_location"];
               $adm->addToDB();
            }
         }

         if (isset($tab["import_otherserial"])) {
            if ($tab["import_otherserial"] != "") {
               $adm = new PluginOcsinventoryngOcsAdminInfosLink();
               $adm->fields["plugin_ocsinventoryng_ocsservers_id"] = $tab["id"];
               $adm->fields["glpi_column"] = "otherserial";
               $adm->fields["ocs_column"] = $tab["import_otherserial"];
               $adm->addToDB();
            }
         }

         if (isset($tab["import_group"])) {
            if ($tab["import_group"] != "") {
               $adm = new PluginOcsinventoryngOcsAdminInfosLink();
               $adm->fields["plugin_ocsinventoryng_ocsservers_id"] = $tab["id"];
               $adm->fields["glpi_column"] = "groups_id";
               $adm->fields["ocs_column"] = $tab["import_group"];
               $adm->addToDB();
            }
         }

         if (isset($tab["import_network"])) {
            if ($tab["import_network"] != "") {
               $adm = new PluginOcsinventoryngOcsAdminInfosLink();
               $adm->fields["plugin_ocsinventoryng_ocsservers_id"] = $tab["id"];
               $adm->fields["glpi_column"] = "networks_id";
               $adm->fields["ocs_column"] = $tab["import_network"];
               $adm->addToDB();
            }
         }

         if (isset($tab["import_contact_num"])) {
            if ($tab["import_contact_num"] != "") {
               $adm = new PluginOcsinventoryngOcsAdminInfosLink();
               $adm->fields["plugin_ocsinventoryng_ocsservers_id"] = $tab["id"];
               $adm->fields["glpi_column"] = "contact_num";
               $adm->fields["ocs_column"] = $tab["import_contact_num"];
               $adm->addToDB();
            }
         }

         if (isset($tab["import_use_date"])) {
            if ($tab["import_use_date"] != "") {
               $adm = new PluginOcsinventoryngOcsAdminInfosLink();
               $adm->fields["plugin_ocsinventoryng_ocsservers_id"] = $tab["id"];
               $adm->fields["glpi_column"] = "use_date";
               $adm->fields["ocs_column"] = $tab["import_use_date"];
               $adm->addToDB();
            }
         }
      }
   }

   /**
    *
    * Encode data coming from OCS DB in utf8 is needed
    * @since 1.0
    * @param boolean $is_ocsdb_utf8 is OCS database declared as utf8 in GLPI configuration
    * @param string $value value to encode in utf8
    * @return string value encoded in utf8
    */
   static function encodeOcsDataInUtf8($is_ocsdb_utf8, $value)
   {
      if (!$is_ocsdb_utf8 && !Toolbox::seems_utf8($value)) {
         return Toolbox::encodeInUtf8($value);
      } else {
         return $value;
      }
   }

   /**
    * Get the ocs server id of a machine, by giving the machine id
    *
    * @param $ID the machine ID
    *
    * return the ocs server id of the machine
    *
    * @return int
    */
   static function getByMachineID($ID)
   {
      global $DB;

      $sql = "SELECT `plugin_ocsinventoryng_ocsservers_id`
              FROM `glpi_plugin_ocsinventoryng_ocslinks`
              WHERE `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id` = '$ID'";
      $result = $DB->query($sql);
      if ($DB->numrows($result) > 0) {
         $datas = $DB->fetch_array($result);
         return $datas["plugin_ocsinventoryng_ocsservers_id"];
      }
      return -1;
   }

   /**
    * Get an Ocs Server name, by giving his ID
    *
    * @param $ID the server ID
    * @return the ocs server name
    * */
   static function getServerNameByID($ID)
   {

      $plugin_ocsinventoryng_ocsservers_id = self::getByMachineID($ID);
      $conf = self::getConfig($plugin_ocsinventoryng_ocsservers_id);
      return $conf["name"];
   }

   /**
    * Get a random plugin_ocsinventoryng_ocsservers_id
    * use for standard sync server selection
    *
    * return an ocs server id
    * */
   static function getRandomServerID()
   {
      global $DB;

      $sql = "SELECT `id`
              FROM `glpi_plugin_ocsinventoryng_ocsservers`
              WHERE `is_active` = '1'
                AND NOT `use_massimport`
              ORDER BY RAND()
              LIMIT 1";
      $result = $DB->query($sql);

      if ($DB->numrows($result) > 0) {
         $datas = $DB->fetch_array($result);
         return $datas["id"];
      }
      return -1;
   }

   /**
    * Get OCSNG mode configuration
    *
    * Get all config of the OCSNG mode
    *
    * @param $id int : ID of the OCS config (default value 1)
    *
    * @return Value of $confVar fields or false if unfound.
    * */
   static function getConfig($id)
   {
      global $DB;

      $query = "SELECT *
                FROM `glpi_plugin_ocsinventoryng_ocsservers`
                WHERE `id` = '$id'";
      $result = $DB->query($query);

      if ($result) {
         $data = $DB->fetch_assoc($result);
      } else {
         $data = 0;
      }

      return $data;
   }

   /**
    * @param $cfg_ocs
    * @return string
    */
   static function getTagLimit($cfg_ocs)
   {

      $WHERE = "";
      if (!empty($cfg_ocs["tag_limit"])) {
         $splitter = explode("$", trim($cfg_ocs["tag_limit"]));
         if (count($splitter)) {
            $WHERE = " `accountinfo`.`TAG` = '" . $splitter[0] . "' ";
            for ($i = 1; $i < count($splitter); $i++) {
               $WHERE .= " OR `accountinfo`.`TAG` = '" . $splitter[$i] . "' ";
            }
         }
      }

      if (!empty($cfg_ocs["tag_exclude"])) {
         $splitter = explode("$", $cfg_ocs["tag_exclude"]);
         if (count($splitter)) {
            if (!empty($WHERE)) {
               $WHERE .= " AND ";
            }
            $WHERE .= " `accountinfo`.`TAG` <> '" . $splitter[0] . "' ";
            for ($i = 1; $i < count($splitter); $i++) {
               $WHERE .= " AND `accountinfo`.`TAG` <> '" . $splitter[$i] . "' ";
            }
         }
      }

      return $WHERE;
   }

   /**
    * Make the item link between glpi and ocs.
    *
    * This make the database link between ocs and glpi databases
    *
    * @param $ocsid integer : ocs item unique id.
    * @param $plugin_ocsinventoryng_ocsservers_id integer : ocs server id
    * @param $glpi_computers_id integer : glpi computer id
    *
    * return integer : link id.
    *
    * @return bool|item
    */
   static function ocsLink($ocsid, $plugin_ocsinventoryng_ocsservers_id, $glpi_computers_id)
   {
      global $DB;

      // Retrieve informations from computer
      $comp = new Computer();
      $comp->getFromDB($glpi_computers_id);
      if (isset($glpi_computers_id)
         && $glpi_computers_id > 0
      ) {
         $input["is_dynamic"] = 1;
         $input["id"] = $glpi_computers_id;
         $comp->update($input);
      }
      self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);

      $ocsComputer = $ocsClient->getComputer($ocsid);

      if (is_null($ocsComputer)) {
         return false;
      }
      $link = new PluginOcsinventoryngOcslink;
      $data = $link->find("`ocsid` = '" . $ocsid . "' AND `plugin_ocsinventoryng_ocsservers_id` = '" . $plugin_ocsinventoryng_ocsservers_id . "'");
      if (count($data) > 0) {
         return false;
      }
      $query = "INSERT INTO `glpi_plugin_ocsinventoryng_ocslinks`
                       (`computers_id`, `ocsid`, `ocs_deviceid`,
                        `last_update`, `plugin_ocsinventoryng_ocsservers_id`,
                        `entities_id`, `tag`)
                VALUES ('$glpi_computers_id', '$ocsid', '" . $ocsComputer['META']['DEVICEID'] . "',
                        '" . $_SESSION["glpi_currenttime"] . "', '$plugin_ocsinventoryng_ocsservers_id',
                        '" . $comp->fields['entities_id'] . "', '" . $ocsComputer['META']['TAG'] . "')";
      $result = $DB->query($query);

      if ($result) {
         return ($DB->insert_id());
      }

      return false;
   }

   /**
    * @param $ocsid
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param $computers_id
    *
    * @return bool
    */
   static function linkComputer($ocsid, $plugin_ocsinventoryng_ocsservers_id, $computers_id)
   {
      global $DB, $CFG_GLPI;

      self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $cfg_ocs = self::getConfig($plugin_ocsinventoryng_ocsservers_id);

      $query = "SELECT *
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `computers_id` = '$computers_id'";

      $result = $DB->query($query);
      $ocs_id_change = false;
      $ocs_link_exists = false;
      $numrows = $DB->numrows($result);

      // Already link - check if the OCS computer already exists
      if ($numrows > 0) {
         $ocs_link_exists = true;
         $data = $DB->fetch_assoc($result);

         // Not found
         if (!$ocsClient->getIfOCSComputersExists($data['ocsid'])) {
            $idlink = $data["id"];
            $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
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
               PluginOcsinventoryngOcslink::history($computers_id, $changes, PluginOcsinventoryngOcslink::HISTORY_OCS_IDCHANGED);
            }
         }
      }
      $options = array(
         "DISPLAY" => array(
            "CHECKSUM" => PluginOcsinventoryngOcsClient::CHECKSUM_BIOS | PluginOcsinventoryngOcsClient::CHECKSUM_NETWORK_ADAPTERS,
         )
      );
      $ocsComputer = $ocsClient->getComputer($ocsid, $options);

      $serial = (isset($ocsComputer['BIOS']["SSN"])) ? $ocsComputer['BIOS']["SSN"] : "";
      $ssnblacklist = Blacklist::getSerialNumbers();
      if (in_array($serial, $ssnblacklist)) {
         Session::addMessageAfterRedirect(sprintf(__('Unable to link this computer, Serial number is blacklisted (%d)', 'ocsinventoryng'), $ocsid), false, ERROR);
         return false;
      }

      $uuid = (isset($ocsComputer['META']["UUID"])) ? $ocsComputer['META']["UUID"] : "";
      $uuidblacklist = Blacklist::getUUIDs();
      if (in_array($uuid, $uuidblacklist)) {
         Session::addMessageAfterRedirect(sprintf(__('Unable to link this computer, UUID is blacklisted (%d)', 'ocsinventoryng'), $ocsid), false, ERROR);
         return false;
      }

      // No ocs_link or ocs id change does not exists so can link
      if ($ocs_id_change || !$ocs_link_exists) {
         $ocsConfig = self::getConfig($plugin_ocsinventoryng_ocsservers_id);
         // Set OCS checksum to max value
         $ocsClient->setChecksum(PluginOcsinventoryngOcsClient::CHECKSUM_ALL, $ocsid);

         if ($ocs_id_change || ($idlink = self::ocsLink($ocsid, $plugin_ocsinventoryng_ocsservers_id, $computers_id))) {

            // automatic transfer computer
            if (($CFG_GLPI['transfers_id_auto'] > 0) && Session::isMultiEntitiesMode()) {

               // Retrieve data from glpi_plugin_ocsinventoryng_ocslinks
               $ocsLink = new PluginOcsinventoryngOcslink();
               $ocsLink->getFromDB($idlink);

               if (count($ocsLink->fields)) {
                  // Retrieve datas from OCS database
                  $ocsComputer = $ocsClient->getComputer($ocsLink->fields['ocsid']);

                  if (!is_null($ocsComputer)) {
                     $ocsComputer = Toolbox::addslashes_deep($ocsComputer);
                     self::transferComputer($ocsLink->fields);
                  }
               }
            }
            $comp = new Computer();
            $comp->getFromDB($computers_id);
            $input["id"] = $computers_id;
            $input["entities_id"] = $comp->fields['entities_id'];
            $input["is_dynamic"] = 1;
            $input["_nolock"] = true;

            // Not already import from OCS / mark default state
            if ((!$ocs_id_change && ($ocsConfig["states_id_default"] > 0)) || (!$comp->fields['is_dynamic'] && ($ocsConfig["states_id_default"] > 0))) {
               $input["states_id"] = $ocsConfig["states_id_default"];
            }
            $comp->update($input, $cfg_ocs['history_hardware']);
            // Auto restore if deleted
            if ($comp->fields['is_deleted']) {
               $comp->restore(array('id' => $computers_id));
            }

            // Reset only if not in ocs id change case
            if (!$ocs_id_change) {

               $changes[0] = '0';
               $changes[1] = "";
               $changes[2] = $ocsid;
               PluginOcsinventoryngOcslink::history($computers_id, $changes, PluginOcsinventoryngOcslink::HISTORY_OCS_LINK);

               if ($ocsConfig["import_general_os"]) {
                  self::resetDropdown($computers_id, "operatingsystems_id", "glpi_operatingsystems");
               }
               if ($ocsConfig["import_device_processor"]) {
                  self::resetDevices($computers_id, 'DeviceProcessor', $cfg_ocs);
               }
               if ($ocsConfig["import_device_iface"]) {
                  self::resetDevices($computers_id, 'DeviceNetworkCard', $cfg_ocs);
               }
               if ($ocsConfig["import_device_memory"]) {
                  self::resetDevices($computers_id, 'DeviceMemory', $cfg_ocs);
               }
               if ($ocsConfig["import_device_hdd"]) {
                  self::resetDevices($computers_id, 'DeviceHardDrive', $cfg_ocs);
               }
               if ($ocsConfig["import_device_sound"]) {
                  self::resetDevices($computers_id, 'DeviceSoundCard', $cfg_ocs);
               }
               if ($ocsConfig["import_device_gfxcard"]) {
                  self::resetDevices($computers_id, 'DeviceGraphicCard', $cfg_ocs);
               }
               if ($ocsConfig["import_device_drive"]) {
                  self::resetDevices($computers_id, 'DeviceDrive', $cfg_ocs);
               }
               if ($ocsConfig["import_device_modem"]
                  || $ocsConfig["import_device_port"]
                  || $ocsConfig["import_device_slot"]
               ) {
                  self::resetDevices($computers_id, 'DevicePci', $cfg_ocs);
               }
               if ($ocsConfig["import_device_bios"]) {
                  self::resetDevices($computers_id, 'PluginOcsinventoryngDeviceBiosdata', $cfg_ocs);
               }
               if ($ocsConfig["import_device_motherboard"]) {
                  self::resetDevices($computers_id, 'DeviceMotherboard', $cfg_ocs);
               }
               if ($ocsConfig["import_device_controller"]) {
                  self::resetDevices($computers_id, 'DeviceControl', $cfg_ocs);
               }
               if ($ocsConfig["import_software"]) {
                  self::resetSoftwares($computers_id, $cfg_ocs);
               }
               if ($ocsConfig["import_disk"]) {
                  self::resetDisks($computers_id, $cfg_ocs);
               }
               if ($ocsConfig["import_periph"]) {
                  self::resetPeripherals($computers_id, $cfg_ocs);
               }
               if ($ocsConfig["import_monitor"] == 1) { // Only reset monitor as global in unit management
                  self::resetMonitors($computers_id, $cfg_ocs);    // try to link monitor with existing
               }
               if ($ocsConfig["import_printer"]) {
                  self::resetPrinters($computers_id, $cfg_ocs);
               }
               if ($ocsConfig["import_registry"]) {
                  self::resetRegistry($computers_id);
               }
               if ($ocsConfig["import_antivirus"]) {
                  self::resetAntivirus($computers_id, $cfg_ocs);
               }
               if ($ocsConfig["import_winupdatestate"]) {
                  self::resetWinupdatestate($computers_id, $cfg_ocs);
               }
               if ($ocsConfig["import_proxysetting"]) {
                  self::resetProxysetting($computers_id, $cfg_ocs);
               }
               if ($ocsConfig["import_winusers"]) {
                  self::resetWinuser($computers_id, $cfg_ocs);
               }
               if ($ocsConfig["import_teamviewer"]) {
                  self::resetTeamviewer($computers_id, $cfg_ocs);
               }
               if ($ocsConfig["import_officepack"]) {
                  self::resetOfficePack($computers_id);
               }
               if ($ocsConfig["import_service"]) {
                  self::resetService($computers_id, $cfg_ocs);
               }
               if ($ocsConfig["import_runningprocess"]) {
                  self::resetRunningProcess($computers_id, $cfg_ocs);
               }
               $changes[0] = '0';
               $changes[1] = "";
               $changes[2] = $ocsid;
               PluginOcsinventoryngOcslink::history($computers_id, $changes, PluginOcsinventoryngOcslink::HISTORY_OCS_LINK);
            }

            self::updateComputer($idlink, $plugin_ocsinventoryng_ocsservers_id, 0);
            return true;
         }
      } else {
         //TRANS: %s is the OCS id
         Session::addMessageAfterRedirect(sprintf(__('Unable to import, GLPI computer is already related to an element of OCSNG (%d)', 'ocsinventoryng'), $ocsid), false, ERROR);
      }
      return false;
   }

   /**
    * @param $ocsid
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param int $lock
    * @param int $defaultentity
    * @param int $defaultlocation
    * @return array
    */
   static function processComputer($ocsid, $plugin_ocsinventoryng_ocsservers_id, $lock = 0, $defaultentity = -1, $defaultlocation = -1)
   {
      global $DB;
      self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $cfg_ocs = self::getConfig($plugin_ocsinventoryng_ocsservers_id);
      $dohistory = (isset($cfg_ocs['dohistory']) ? $cfg_ocs['dohistory'] : false);

      //Check it machine is already present AND was imported by OCS AND still present in GLPI
      $query = "SELECT `glpi_plugin_ocsinventoryng_ocslinks`.`id`, `computers_id`, `ocsid`
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                LEFT JOIN `glpi_computers`
                     ON `glpi_computers`.`id`=`glpi_plugin_ocsinventoryng_ocslinks`.`computers_id`
                WHERE `glpi_computers`.`id` IS NOT NULL
                      AND `ocsid` = '$ocsid'
                      AND `plugin_ocsinventoryng_ocsservers_id` = '$plugin_ocsinventoryng_ocsservers_id'";
      $result_glpi_plugin_ocsinventoryng_ocslinks = $DB->query($query);
      if ($DB->numrows($result_glpi_plugin_ocsinventoryng_ocslinks)) {
         $datas = $DB->fetch_array($result_glpi_plugin_ocsinventoryng_ocslinks);
         //Return code to indicates that the machine was synchronized
         //or only last inventory date changed
         return self::updateComputer($datas["id"], $plugin_ocsinventoryng_ocsservers_id, $dohistory, 0);
      }
      return self::importComputer($ocsid, $plugin_ocsinventoryng_ocsservers_id, $lock, $defaultentity, $defaultlocation);

   }

   /**
    * @param $ID
    * @return bool
    */
   public static function checkVersion($ID)
   {
      $client = self::getDBocs($ID);
      $version = $client->getTextConfig('GUI_VERSION');

      if ($version) {
         $server = new self();
         $server->update(array(
            'id'          => $ID,
            'ocs_version' => $version
         ));
      }
      return true;
      if (!$version || ($version < self::OCS_VERSION_LIMIT && strpos($version, '2.0') !== 0)) { // hack for 2.0 RC
         return false;
      } else {
         return true;
      }
   }


   /**
    * @param $ID
    * @return int
    */
   public static function checkTraceDeleted($ID)
   {
      $client = self::getDBocs($ID);
      return $client->getIntConfig('TRACE_DELETED');
   }

   /**
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param bool $redirect
    * @return bool
    */
   static function manageDeleted($plugin_ocsinventoryng_ocsservers_id, $redirect = true)
   {
      global $DB;

      if (!(self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id)
         && self::checkVersion($plugin_ocsinventoryng_ocsservers_id))
      ) {
         return false;
      }

      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $deleted = $ocsClient->getDeletedComputers();

      //if (strpos($_SERVER['PHP_SELF'], "deleted_equiv.php") == true){
      if (count($deleted)) {

         foreach ($deleted as $del => $equiv) {
            if (!empty($equiv) && $equiv != "NULL") { // New name ($equiv = VARCHAR)
               // Get hardware due to bug of duplicates management of OCS
               if (strpos($equiv, "-") !== false) {
                  $res = $ocsClient->searchComputers('DEVICEID', $equiv);
                  if (isset($res['COMPUTERS']) && count($res['COMPUTERS'])) {
                     if (isset($res['COMPUTERS']['META']) && is_array(isset($res['COMPUTERS']['META']))) {
                        $data = end($res['COMPUTERS']['META']);
                        $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                                        SET `ocsid` = '" . $data["ID"] . "',
                                            `ocs_deviceid` = '" . $data["DEVICEID"] . "'
                                        WHERE `ocs_deviceid` = '$del'
                                              AND `plugin_ocsinventoryng_ocsservers_id`
                                                      = '$plugin_ocsinventoryng_ocsservers_id'";
                        $DB->query($query);
                        $ocsClient->setChecksum($data['CHECKSUM'] | PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE, $data['ID']);
                     }
                     // } else {
                     // We're damned ! no way to find new ID
                     // TODO : delete ocslinks ?
                  }
               } else {
                  $res = $ocsClient->searchComputers('ID', $equiv);
                  if (isset($res['COMPUTERS']) && count($res['COMPUTERS'])) {

                     $data = $res['COMPUTERS'][$equiv]['META'];
                     $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                                     SET `ocsid` = '" . $data["ID"] . "',
                                         `ocs_deviceid` = '" . $data["DEVICEID"] . "'
                                     WHERE `ocsid` = '$del'
                                           AND `plugin_ocsinventoryng_ocsservers_id`
                                                   = '$plugin_ocsinventoryng_ocsservers_id'";
                     $DB->query($query);
                     $ocsClient->setChecksum($data['CHECKSUM'] | PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE, $data['ID']);
                  } else {
                     // Not found, probably because ID change twice since previous sync
                     // No way to found new DEVICEID
                     $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                                     SET `ocsid` = '$equiv'
                                     WHERE `ocsid` = '$del'
                                           AND `plugin_ocsinventoryng_ocsservers_id` = '$plugin_ocsinventoryng_ocsservers_id'";
                     $DB->query($query);
                     // for history, see below
                     $data = array('ID' => $equiv);
                  }
               }
               //foreach($ocs_deviceid as $deviceid => $equiv){
               //$query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
               //SET `ocsid` = '$equiv'
               //WHERE `ocs_deviceid` = '$del'
               //AND `plugin_ocsinventoryng_ocsservers_id` = '$plugin_ocsinventoryng_ocsservers_id'";
               //}
               //TODO
               //http://www.karlrixon.co.uk/writing/update-multiple-rows-with-different-values-and-a-single-sql-query/
               if (isset($data)) {
                  $sql_id = "SELECT `computers_id`
                                   FROM `glpi_plugin_ocsinventoryng_ocslinks`
                                   WHERE `ocsid` = '" . $data["ID"] . "'
                                         AND `plugin_ocsinventoryng_ocsservers_id`
                                                = '$plugin_ocsinventoryng_ocsservers_id'";
                  if ($res_id = $DB->query($sql_id)) {
                     if ($DB->numrows($res_id) > 0) {
                        //Add history to indicates that the ocsid changed
                        $changes[0] = '0';
                        //Old ocsid
                        $changes[1] = $del;
                        //New ocsid
                        $changes[2] = $data["ID"];
                        PluginOcsinventoryngOcslink::history($DB->result($res_id, 0, "computers_id"), $changes, PluginOcsinventoryngOcslink::HISTORY_OCS_IDCHANGED);
                     }
                  }
               }
            } else { // Deleted
               $ocslinks_toclean = array();
               if (strstr($del, "-")) {
                  $link = "ocs_deviceid";
               } else {
                  $link = "ocsid";
               }
               $query = "SELECT *
                               FROM `glpi_plugin_ocsinventoryng_ocslinks`
                               WHERE `" . $link . "` = '$del'
                                     AND `plugin_ocsinventoryng_ocsservers_id`
                                                = '$plugin_ocsinventoryng_ocsservers_id'";

               if ($result = $DB->query($query)) {
                  if ($DB->numrows($result) > 0) {
                     $data = $DB->fetch_array($result);
                     $ocslinks_toclean[$data['id']] = $data['id'];
                  }
               }
               self::cleanLinksFromList($plugin_ocsinventoryng_ocsservers_id, $ocslinks_toclean);
            }
            //Delete from deleted_equiv
            if (!empty($equiv)) {
               $ocsClient->removeDeletedComputers($del, $equiv);
            } else {
               $to_del[] = $del;
            }
         }
         //Delete from deleted_equiv
         if (!empty($to_del)) {
            $ocsClient->removeDeletedComputers($to_del);
         }
         //If cron, no redirect
         if ($redirect) {
            if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != "") {
               $redirection = $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING'];
            } else {
               $redirection = $_SERVER['PHP_SELF'];
            }
            Html::redirect($redirection);
         }
      } else {
         $_SESSION['ocs_deleted_equiv']['computers_to_del'] = false;
         $_SESSION['ocs_deleted_equiv']['computers_deleted'] = 0;
      }
      // New way to delete entry from deleted_equiv table
      //} else if (count($deleted)) {
      //   $message = sprintf(__('Please consider cleaning the deleted computers in OCSNG <a href="%s">Clean OCSNG datatabase </a>', 'ocsinventoryng'), $CFG_GLPI['root_doc']."/plugins/ocsinventoryng/front/deleted_equiv.php");
      //   echo "<tr><th colspan='2'>";
      //   Html::displayTitle($CFG_GLPI['root_doc']."/pics/warning.png", $message, $message);
      //   echo "</th></tr>";
      //}
   }

   /**
    * Delete computers
    *
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param type $agents
    *
    * @return int
    */
   static function deleteComputers($plugin_ocsinventoryng_ocsservers_id, $agents){

      $ocslink = new PluginOcsinventoryngOcslink();
      $computer = new Computer();

      $nb = 0;
      foreach ($agents as $key => $val) {
         foreach ($val as $k => $agent) {
            //search for OCS links
            if ($ocslink->getFromDBByQuery("WHERE `ocsid` = '$agent' AND `plugin_ocsinventoryng_ocsservers_id` = '$plugin_ocsinventoryng_ocsservers_id'")) {
               //search computer
               if ($computer->getFromDB($ocslink->fields['computers_id'])) {
                  if(!$computer->fields['is_deleted']) {
                     $computer->fields['is_deleted'] = 1;
                     $computer->fields['date_mod'] = $_SESSION['glpi_currenttime'];
                     $computer->updateInDB(array('is_deleted', 'date_mod'));
                     //add history
                     $changes[0] = 0;
                     $changes[2] = "";
                     $changes[1] = "";
                     Log::history($ocslink->fields['computers_id'], 'Computer', $changes, 0,
                                  Log::HISTORY_DELETE_ITEM);

                     $nb += 1;
                  }
               }
            }
         }
      }

      return $nb;
   }

   /**
    * Return field matching between OCS and GLPI
    *
    * @return array of glpifield => ocsfield
    * */
   static function getOcsFieldsMatching()
   {

      // Manufacturer and Model both as text (for rules) and as id (for import)
      return array('manufacturer'                    => array('BIOS', 'SMANUFACTURER'),
                   'manufacturers_id'                => array('BIOS', 'SMANUFACTURER'),
                   'os_license_number'               => array('HARDWARE', 'WINPRODKEY'),
                   'os_licenseid'                    => array('HARDWARE', 'WINPRODID'),
                   'operatingsystems_id'             => array('HARDWARE', 'OSNAME'),
                   'operatingsystemversions_id'      => array('HARDWARE', 'OSVERSION'),
                   'operatingsystemarchitectures_id' => array('HARDWARE', 'ARCH'),
                   'operatingsystemservicepacks_id'  => array('HARDWARE', 'OSCOMMENTS'),
                   'domains_id'                      => array('HARDWARE', 'WORKGROUP'),
                   'contact'                         => array('HARDWARE', 'USERID'),
                   'name'                            => array('META', 'NAME'),
                   'comment'                         => array('HARDWARE', 'DESCRIPTION'),
                   'serial'                          => array('BIOS', 'SSN'),
                   'model'                           => array('BIOS', 'SMODEL'),
                   'computermodels_id'               => array('BIOS', 'SMODEL'),
                   'TAG'                             => array('ACCOUNTINFO', 'TAG')
      );
   }

   /**
    * @param array $ocs_fields
    * @param $cfg_ocs
    * @param $entities_id
    * @param int $locations_id
    * @return array
    */
   static function getComputerInformations($ocs_fields = array(), $cfg_ocs, $entities_id, $locations_id = 0, $groups_id = 0) {
      $input = array();
      $input["is_dynamic"] = 1;

      if ($cfg_ocs["states_id_default"] > 0) {
         $input["states_id"] = $cfg_ocs["states_id_default"];
      }

      $input["entities_id"] = $entities_id;

      if ($locations_id) {
         $input["locations_id"] = $locations_id;
      }

      if ($groups_id) {
         $input["groups_id"] = $groups_id;
      }

      $input['ocsid'] = $ocs_fields['META']['ID'];
      $ocs_fields_matching = self::getOcsFieldsMatching();
      foreach ($ocs_fields_matching as $glpi_field => $ocs_field) {
         $ocs_section = $ocs_field[0];
         $ocs_field = $ocs_field[1];

         $table = getTableNameForForeignKeyField($glpi_field);

         $ocs_val = null;
         if (isset($ocs_fields[$ocs_section]) && is_array($ocs_fields[$ocs_section])) {
            if (array_key_exists($ocs_field, $ocs_fields[$ocs_section])) {
               $ocs_val = $ocs_fields[$ocs_section][$ocs_field];
            } else if (isset($ocs_fields[$ocs_section][0])
               && array_key_exists($ocs_field, $ocs_fields[$ocs_section][0])) {
               $ocs_val = $ocs_fields[$ocs_section][0][$ocs_field];
            }
         }
         if (!is_null($ocs_val)) {
            $ocs_field = Toolbox::encodeInUtf8($ocs_field);

            //Field is a foreing key
            if ($table != '') {
               if (!($item = getItemForItemtype($table))) {
                  continue;
               }
               $itemtype = getItemTypeForTable($table);
               $external_params = array();

               foreach ($item->additional_fields_for_dictionnary as $field) {
                  $additional_ocs_section = $ocs_fields_matching[$field][0];
                  $additional_ocs_field = $ocs_fields_matching[$field][1];

                  if (isset($ocs_fields[$additional_ocs_section][$additional_ocs_field])) {
                     $external_params[$field] = $ocs_fields[$additional_ocs_section][$additional_ocs_field];
                  } else if (isset($ocs_fields[$additional_ocs_section][0][$additional_ocs_field])) {
                     $external_params[$field] = $ocs_fields[$additional_ocs_section][0][$additional_ocs_field];
                  } else {
                     $external_params[$field] = "";
                  }
               }

               $input[$glpi_field] = Dropdown::importExternal($itemtype, $ocs_val, $entities_id, $external_params);
            } else {
               switch ($glpi_field) {
                  case 'contact' :
                     if ($users_id = User::getIDByField('name', $ocs_val)) {
                        $input[$glpi_field] = $users_id;
                     }
                     break;

                  case 'comment' :
                     $input[$glpi_field] = '';
                     if ($ocs_val && $ocs_val != NOT_AVAILABLE) {
                        $input[$glpi_field] .= $ocs_val . "\r\n";
                     }
                     $input[$glpi_field] .= addslashes(sprintf(__('%1$s %2$s'), $input[$glpi_field], sprintf(__('%1$s: %2$s'), __('Swap', 'ocsinventoryng'), $ocs_fields['HARDWARE']['SWAP'])));
                     break;

                  default :
                     $input[$glpi_field] = $ocs_val;
                     break;
               }
            }
         }
      }
      if (intval($cfg_ocs["import_general_name"]) == 0) {
         unset($input["name"]);
      }

      if (intval($cfg_ocs["import_general_os"]) == 0) {
         unset($input["operatingsystems_id"]);
         unset($input["operatingsystemversions_id"]);
         unset($input["operatingsystemservicepacks_id"]);
         unset($input["operatingsystemarchitectures_id"]);
      }

      if (intval($cfg_ocs["import_os_serial"]) == 0) {
         unset($input["os_license_number"]);
         unset($input["os_licenseid"]);
      }

      if (intval($cfg_ocs["import_general_serial"]) == 0) {
         unset($input["serial"]);
      }

      if (intval($cfg_ocs["import_general_model"]) == 0) {
         unset($input["model"]);
         unset($input["computermodels_id"]);
      }

      if (intval($cfg_ocs["import_general_manufacturer"]) == 0) {
         unset($input["manufacturer"]);
         unset($input["manufacturers_id"]);
      }

      if (intval($cfg_ocs["import_general_type"]) == 0) {
         unset($input["computertypes_id"]);
      }

      if (intval($cfg_ocs["import_general_comment"]) == 0) {
         unset($input["comment"]);
      }

      if (intval($cfg_ocs["import_general_contact"]) == 0) {
         unset($input["contact"]);
         unset($input["users_id"]);
      }

      if (intval($cfg_ocs["import_general_domain"]) == 0) {
         unset($input["domains_id"]);
      }

      return $input;
   }

   /**
    * @param $ocsid
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param int $lock
    * @param int $defaultentity
    * @param int $defaultlocation
    * @return array
    */
   static function importComputer($ocsid, $plugin_ocsinventoryng_ocsservers_id, $lock = 0, $defaultentity = -1, $defaultlocation = -1) {
      global $DB;

      self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $cfg_ocs = self::getConfig($plugin_ocsinventoryng_ocsservers_id);
      $comp = new Computer();

      $rules_matched = array();
      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $ocsClient->setChecksum(PluginOcsinventoryngOcsClient::CHECKSUM_ALL, $ocsid);

      $ocsComputer = $ocsClient->getComputer($ocsid, array(
         'DISPLAY' => array(
            'CHECKSUM' => PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE | PluginOcsinventoryngOcsClient::CHECKSUM_BIOS,
            'WANTED'   => PluginOcsinventoryngOcsClient::WANTED_ACCOUNTINFO
         )
      ));

      $locations_id = 0;
      $groups_id    = 0;
      $contact = (isset($ocsComputer['META']["USERID"])) ? $ocsComputer['META']["USERID"] : "";
      if (!empty($contact) && $cfg_ocs["import_general_contact"] > 0) {
         $query = "SELECT `id`
                   FROM `glpi_users`
                   WHERE `name` = '" . $contact . "';";
         $result = $DB->query($query);

         if ($DB->numrows($result) == 1) {
            $user_id = $DB->result($result, 0, 0);
            $user = new User();
            $user->getFromDB($user_id);
            if ($cfg_ocs["import_user_location"] > 0) {
               $locations_id = $user->fields["locations_id"];
            }
            if ($cfg_ocs["import_user_group"] > 0) {
               $groups_id = self::getUserGroup(0, $user_id, '`is_requester`', true);
            }
         }
      }

      //No entity or location predefined, check rules
      if ($defaultentity == -1 && ($defaultlocation == -1 || $defaultlocation == 0)) {
         //Try to affect computer to an entity
         $rule = new RuleImportEntityCollection();
         $data = array();
         $data = $rule->processAllRules(array('ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                                              '_source'       => 'ocsinventoryng',
                                              'locations_id'  => $locations_id,
                                              'groups_id'     => $groups_id
                                        ), array(
                                           'locations_id' => $locations_id,
                                           'groups_id'    => $groups_id
                                        ), array('ocsid' => $ocsid));

         if (isset($data['_ignore_import']) && $data['_ignore_import'] == 1) {
            //ELSE Return code to indicates that the machine was not imported because it doesn't matched rules
            return array('status'       => self::COMPUTER_LINK_REFUSED,
                         'rule_matched' => $data['_ruleid']);
         }
      } else {
         //An entity or a location has already been defined via the web interface
         $data['entities_id'] = $defaultentity;
         $data['locations_id'] = $defaultlocation;
      }
      //Try to match all the rules, return the first good one, or null if not rules matched
      if (isset($data['entities_id']) && $data['entities_id'] >= 0) {
         if ($lock) {
            while (!$fp = self::setEntityLock($data['entities_id'])) {
               sleep(1);
            }
         }

         //Store rule that matched
         if (isset($data['_ruleid'])) {
            $rules_matched['RuleImportEntity'] = $data['_ruleid'];
         }

         if (!is_null($ocsComputer)) {

            $computer = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($ocsComputer));

            $locations_id = (isset($data['locations_id']) ? $data['locations_id'] : 0);
            $groups_id    = (isset($data['groups_id']) ? $data['groups_id'] : 0);
            $input        = self::getComputerInformations($computer, self::getConfig($plugin_ocsinventoryng_ocsservers_id),
                                                          $data['entities_id'], $locations_id, $groups_id);
            //Check if machine could be linked with another one already in DB
            $rulelink = new RuleImportComputerCollection();
            $rulelink_results = array();
            $params = array('entities_id' => $data['entities_id'],
                            'plugin_ocsinventoryng_ocsservers_id'
                                          => $plugin_ocsinventoryng_ocsservers_id,
                            'ocsid'       => $ocsid);
            $rulelink_results = $rulelink->processAllRules(Toolbox::stripslashes_deep($input), array(), $params);

            //If at least one rule matched
            //else do import as usual
            if (isset($rulelink_results['action'])) {
               $rules_matched['RuleImportComputer'] = $rulelink_results['_ruleid'];

               switch ($rulelink_results['action']) {
                  case self::LINK_RESULT_NO_IMPORT :
                     return array('status'       => self::COMPUTER_LINK_REFUSED,
                                  'entities_id'  => $data['entities_id'],
                                  'rule_matched' => $rules_matched);

                  case self::LINK_RESULT_LINK :
                     if (is_array($rulelink_results['found_computers']) && count($rulelink_results['found_computers']) > 0) {

                        foreach ($rulelink_results['found_computers'] as $tmp => $computers_id) {

                           if (self::linkComputer($ocsid, $plugin_ocsinventoryng_ocsservers_id, $computers_id)) {
                              return array('status'       => self::COMPUTER_LINKED,
                                           'entities_id'  => $data['entities_id'],
                                           'rule_matched' => $rules_matched,
                                           'computers_id' => $computers_id);
                           }
                        }
                        break;
                     }
               }
            }
            //ADD IF NOT LINKED
            $computers_id = $comp->add($input, array('unicity_error_message' => false));
            if ($computers_id) {
               $ocsid = $computer['META']['ID'];
               $changes[0] = '0';
               $changes[1] = "";
               $changes[2] = $ocsid;
               PluginOcsinventoryngOcslink::history($computers_id, $changes, PluginOcsinventoryngOcslink::HISTORY_OCS_IMPORT);

               if ($idlink = self::ocsLink($computer['META']['ID'], $plugin_ocsinventoryng_ocsservers_id, $computers_id)) {
                  self::updateComputer($idlink, $plugin_ocsinventoryng_ocsservers_id, 0);
               }

               //Return code to indicates that the machine was imported
               // var_dump("post",$_POST,"session",$_SESSION,"server",$_SERVER,"get",$_GET,"cookie",$_COOKIE,"request",$_REQUEST);
               //die("lets see the thisngs in post get session, cookie and reques");
               return array('status'       => self::COMPUTER_IMPORTED,
                            'entities_id'  => $data['entities_id'],
                            'rule_matched' => $rules_matched,
                            'computers_id' => $computers_id);
            }
            return array('status'       => self::COMPUTER_NOT_UNIQUE,
                         'entities_id'  => $data['entities_id'],
                         'rule_matched' => $rules_matched);
         }

         if ($lock) {
            self::removeEntityLock($data['entities_id'], $fp);
         }
      }
      //ELSE Return code to indicates that the machine was not imported because it doesn't matched rules
      return array('status'       => self::COMPUTER_FAILED_IMPORT,
                   'rule_matched' => $rules_matched);
   }

   /** Update a ocs computer
    *
    * @param $ID integer : ID of ocslinks row
    * @param $plugin_ocsinventoryng_ocsservers_id integer : ocs server ID
    * @param $dohistory bool : do history ?
    * @param bool|int $force bool : force update ?
    * return action done
    * @return array
    */
   static function updateComputer($ID, $plugin_ocsinventoryng_ocsservers_id, $dohistory, $force = 0)
   {
      global $DB, $CFG_GLPI;

      self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $cfg_ocs = self::getConfig($plugin_ocsinventoryng_ocsservers_id);

      $query = "SELECT *
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `id` = '$ID'
                AND `plugin_ocsinventoryng_ocsservers_id`
                = '$plugin_ocsinventoryng_ocsservers_id'";
      $result = $DB->query($query);

      if ($DB->numrows($result) == 1) {
         $line = $DB->fetch_assoc($result);
         $comp = new Computer();
         $comp->getFromDB($line["computers_id"]);
         $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);
         $options = array(
            "DISPLAY" => array(
               "CHECKSUM" => PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE,
            )
         );
         $computer = $ocsClient->getComputer($line['ocsid'], $options);
         $data_ocs = $computer;

         // Need do history to be 2 not to lock fields
         //TODO MODIFY ???
         if ($dohistory) {
            $dohistory = 2;
         }

         if ($computer) {
            // automatic transfer computer
            if ($CFG_GLPI['transfers_id_auto'] > 0
               && Session::isMultiEntitiesMode()
            ) {
               self::transferComputer($line);
               $comp->getFromDB($line["computers_id"]);

            } else {

               $locations_id = 0;
               $groups_id = 0;
               $contact = (isset($computer['META']["USERID"])) ? $computer['META']["USERID"] : "";
               if (!empty($contact) && $cfg_ocs["import_general_contact"] > 0) {
                  $query = "SELECT `id`
                            FROM `glpi_users`
                            WHERE `name` = '" . $contact . "';";
                  $result = $DB->query($query);

                  if ($DB->numrows($result) == 1) {
                     $user_id = $DB->result($result, 0, 0);
                     $user = new User();
                     $user->getFromDB($user_id);
                     if ($cfg_ocs["import_user_location"] > 0) {
                        $locations_id = $user->fields["locations_id"];
                     }
                     if ($cfg_ocs["import_user_group"] > 0) {
                        $groups_id = self::getUserGroup($comp->fields["entities_id"], $user_id, '`is_requester`', true);
                     }
                  }
               }
               $rule = new RuleImportEntityCollection();

               $data = array();
               $data = $rule->processAllRules(array('ocsservers_id' => $line["plugin_ocsinventoryng_ocsservers_id"],
                                                    '_source'       => 'ocsinventoryng',
                                                    'locations_id'  => $locations_id,
                                                    'groups_id'  => $groups_id),
                                              array('locations_id' => $locations_id,
                                                    'groups_id'    => $groups_id),
                                              array('ocsid' => $line["ocsid"]));
               self::updateComputerFields($line, $data, $cfg_ocs);
            }

            // update last_update and and last_ocs_update
            $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                             SET `last_update` = '" . $_SESSION["glpi_currenttime"] . "',
                             `last_ocs_update` = '" . $data_ocs["META"]["LASTDATE"] . "',
                             `ocs_agent_version` = '" . $data_ocs["HARDWARE"]["USERAGENT"] . " ',
                             `last_ocs_conn` = '" . $data_ocs["HARDWARE"]["LASTCOME"] . " ',
                             `ip_src` = '" . $data_ocs["HARDWARE"]["IPSRC"] . " ',
                             `ocs_deviceid` = '" . $data_ocs["HARDWARE"]["DEVICEID"] . " '
                             WHERE `id` = '$ID'";
            $DB->query($query);
            //Add  || $data_ocs["META"]["CHECKSUM"] > self::MAX_CHECKSUM for bug of checksum 18446744073689088230
            if ($force || $data_ocs["META"]["CHECKSUM"] > self::MAX_CHECKSUM) {
               $ocs_checksum = self::MAX_CHECKSUM;
               self::getDBocs($plugin_ocsinventoryng_ocsservers_id)->setChecksum($ocs_checksum, $line['ocsid']);
            } else {
               $ocs_checksum = $data_ocs["META"]["CHECKSUM"];
            }
            $mixed_checksum = intval($ocs_checksum) & intval($cfg_ocs["checksum"]);

            // Is an update to do ?
            $bios            = false;
            $memories        = false;
            $storages        = array();
            $cpus            = false;
            $hardware        = false;
            $videos          = false;
            $sounds          = false;
            $networks        = false;
            $modems          = false;
            $ports           = false;
            $monitors        = false;
            $printers        = false;
            $inputs          = false;
            $softwares       = false;
            $drives          = false;
            $registry        = false;
            $antivirus       = false;
            $uptime          = false;
            $officepack      = false;
            $winupdatestate  = false;
            $proxysetting    = false;
            $winuser         = false;
            $teamviewer      = false;
            $virtualmachines = false;
            $mb              = false;
            $controllers     = false;
            $slots           = false;
            $service         = false;
            $runningprocess  = false;

            if ($mixed_checksum) {

               // Get updates on computers
               $computer_updates = importArrayFromDB($line["computer_update"]);
               if (!in_array(self::IMPORT_TAG_078, $computer_updates)) {
                  $computer_updates = self::migrateComputerUpdates($line["computers_id"], $computer_updates);
               }

               $ocsCheck = array();
               $ocsPlugins = array();
               if ($mixed_checksum & pow(2, self::HARDWARE_FL)) {
                  $hardware = true;
                  $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE;
               }
               if ($mixed_checksum & pow(2, self::BIOS_FL)) {
                  $bios = true;
                  $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_BIOS;
                  if ($cfg_ocs["import_device_motherboard"] && $cfg_ocs['ocs_version'] >= self::OCS2_2_VERSION_LIMIT) {
                     $mb = true;
                  }
               }
               if ($mixed_checksum & pow(2, self::MEMORIES_FL)) {

                  if ($cfg_ocs["import_device_memory"]) {
                     $memories = true;
                     $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_MEMORY_SLOTS;
                  }
               }
               if ($mixed_checksum & pow(2, self::CONTROLLERS_FL)) {

                  if ($cfg_ocs["import_device_controller"]) {
                     $controllers = true;
                     $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_SYSTEM_CONTROLLERS;
                  }
               }
               if ($mixed_checksum & pow(2, self::SLOTS_FL)) {

                  if ($cfg_ocs["import_device_slot"]) {
                     $slots = true;
                     $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_SYSTEM_SLOTS;
                  }
               }
               if ($mixed_checksum & pow(2, self::STORAGES_FL)) {

                  if ($cfg_ocs["import_device_hdd"]) {
                     $storages["hdd"] = true;
                     $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_STORAGE_PERIPHERALS;
                  }
                  if ($cfg_ocs["import_device_drive"]) {
                     $storages["drive"] = true;
                     $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_STORAGE_PERIPHERALS;
                  }
               }
               if ($mixed_checksum & pow(2, self::CPUS_FL)) {
                  if ($cfg_ocs["import_device_processor"]
                     && !($cfg_ocs['ocs_version'] < self::OCS2_1_VERSION_LIMIT)
                  ) {
                     $cpus = true;
                     $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_CPUS;
                  }
               }
               if ($mixed_checksum & pow(2, self::VIDEOS_FL)) {
                  if ($cfg_ocs["import_device_gfxcard"]) {
                     $videos = true;
                     $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_VIDEO_ADAPTERS;
                  }
               }
               if ($mixed_checksum & pow(2, self::SOUNDS_FL)) {

                  if ($cfg_ocs["import_device_sound"]) {
                     $sounds = true;
                     $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_SOUND_ADAPTERS;
                  }
               }
               if ($mixed_checksum & pow(2, self::NETWORKS_FL)) {


                  if ($cfg_ocs["import_device_iface"]
                     || $cfg_ocs["import_ip"]
                  ) {
                     $networks = true;
                     $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_NETWORK_ADAPTERS;
                  }
               }
               if ($mixed_checksum & pow(2, self::MODEMS_FL)
                  || $mixed_checksum & pow(2, self::PORTS_FL)
               ) {

                  if ($cfg_ocs["import_device_modem"]) {
                     $modems = true;
                     $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_MODEMS;
                  }
               }
               if ($mixed_checksum & pow(2, self::PORTS_FL)) {

                  if ($cfg_ocs["import_device_modem"]) {
                     $ports = true;
                     $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_MODEMS;
                  }
               }
               if ($mixed_checksum & pow(2, self::MONITORS_FL)) {

                  if ($cfg_ocs["import_monitor"]) {
                     $monitors = true;
                     $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_MONITORS;
                  }
               }
               if ($mixed_checksum & pow(2, self::PRINTERS_FL)) {

                  if ($cfg_ocs["import_printer"]) {
                     $printers = true;
                     $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_PRINTERS;
                  }
               }
               if ($mixed_checksum & pow(2, self::INPUTS_FL)) {

                  if ($cfg_ocs["import_periph"]) {
                     $inputs = true;
                     $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_INPUT_DEVICES;
                  }
               }
               if ($mixed_checksum & pow(2, self::SOFTWARES_FL)) {
                  if ($cfg_ocs["import_software"]) {
                     $softwares = true;
                     $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_SOFTWARE;
                     if ($cfg_ocs["use_soft_dict"]) {
                        $ocsWanted = PluginOcsinventoryngOcsClient::WANTED_DICO_SOFT;
                     }
                  }
               }
               if ($mixed_checksum & pow(2, self::DRIVES_FL)) {
                  $drives = true;
                  $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_LOGICAL_DRIVES;
               }
               if ($mixed_checksum & pow(2, self::REGISTRY_FL)) {
                  if ($cfg_ocs["import_registry"]) {
                     $registry = true;
                     $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_REGISTRY;
                  }
               }
               if ($cfg_ocs["import_antivirus"]) {
                  $antivirus = true;
                  $ocsPlugins[] = PluginOcsinventoryngOcsClient::PLUGINS_SECURITY;
               }
               if ($cfg_ocs["import_software"] && $cfg_ocs["import_officepack"]) {
                  $officepack   = true;
                  $ocsPlugins[] = PluginOcsinventoryngOcsClient::PLUGINS_OFFICE;
               }
               if ($cfg_ocs["import_uptime"]) {
                  $uptime = true;
                  $ocsPlugins[] = PluginOcsinventoryngOcsClient::PLUGINS_UPTIME;
               }
               if ($cfg_ocs["import_winupdatestate"]) {
                  $winupdatestate = true;
                  $ocsPlugins[] = PluginOcsinventoryngOcsClient::PLUGINS_WUPDATE;
               }
               if ($cfg_ocs["import_proxysetting"]) {
                  $proxysetting = true;
                  $ocsPlugins[] = PluginOcsinventoryngOcsClient::PLUGINS_PROXYSETTING;
               }
               if ($cfg_ocs["import_winusers"]) {
                  $winuser = true;
                  $ocsPlugins[] = PluginOcsinventoryngOcsClient::PLUGINS_WINUSERS;
               }
               if ($cfg_ocs["import_teamviewer"]) {
                  $teamviewer = true;
                  $ocsPlugins[] = PluginOcsinventoryngOcsClient::PLUGINS_TEAMVIEWER;
               }
               if ($cfg_ocs["import_runningprocess"]) {
                  $runningprocess = true;
                  $ocsPlugins[] = PluginOcsinventoryngOcsClient::PLUGINS_RUNNINGPROCESS;
               }
               if ($cfg_ocs["import_service"]) {
                  $service = true;
                  $ocsPlugins[] = PluginOcsinventoryngOcsClient::PLUGINS_SERVICE;
               }
               if ($mixed_checksum & pow(2, self::VIRTUALMACHINES_FL)) {
                  //no vm in ocs before 1.3
                  if (!($cfg_ocs['ocs_version'] < self::OCS1_3_VERSION_LIMIT)) {
                     $virtualmachines = true;
                     $ocsCheck[] = PluginOcsinventoryngOcsClient::CHECKSUM_VIRTUAL_MACHINES;
                  }
               }

               if (count($ocsCheck) > 0) {
                  $ocsCheckResult = $ocsCheck[0];
                  foreach ($ocsCheck as $k => $ocsChecksum) {
                     $ocsCheckResult = $ocsCheckResult | $ocsChecksum;
                  }
               } else {
                  $ocsCheckResult = 0;
               }

               if (!isset($ocsWanted)) {
                  $ocsWanted = 0;
               }
               $ocsPluginsResult = array();
               if ($ocsPlugins) {
                  $ocsPluginsResult = $ocsPlugins[0];
                  foreach ($ocsPlugins as $plug) {
                     $ocsPluginsResult = $ocsPluginsResult | $plug;
                  }
               } else {
                  $ocsPluginsResult = 0;
               }

               $import_options = array(
                  'DISPLAY' => array(
                     'CHECKSUM' => $ocsCheckResult,
                     'WANTED'   => $ocsWanted,
                     'PLUGINS'   => $ocsPluginsResult
                  ),
               );

               $ocsComputer = $ocsClient->getComputer($line['ocsid'], $import_options);

               // Update Administrative informations
               self::updateAdministrativeInfo($line['computers_id'], $line['ocsid'], $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs, $computer_updates, $comp->fields['entities_id']);
               $computer_updates = self::updateAdministrativeInfoUseDate($line['computers_id'], $plugin_ocsinventoryng_ocsservers_id, $computer_updates, $ocsComputer);

               //By default log history
               $loghistory["history"] = $cfg_ocs['history_hardware'];

               if ($hardware) {
                  $p = array('computers_id'                        => $line['computers_id'],
                             'ocs_id'                              => $line['ocsid'],
                             'plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
                             'cfg_ocs'                             => $cfg_ocs,
                             'computers_updates'                   => $computer_updates,
                             'dohistory'                           => $cfg_ocs['history_hardware'],
                             'check_history'                       => true,
                             'entities_id'                         => $comp->fields['entities_id']);
                  self::updateHardware($p);
               }
               if ($bios) {
                  self::updateBios($line['computers_id'], $ocsComputer, $cfg_ocs, $computer_updates, $comp->fields['entities_id']);
               }
               // Get import devices
               $import_device = array();
               $types = $CFG_GLPI['ocsinventoryng_devices_index'];
               foreach ($types as $old => $type) {
                  $associated_type = str_replace('Item_', '', $type);
                  $associated_table = getTableForItemType($associated_type);
                  $fk = getForeignKeyFieldForTable($associated_table);

                  $query = "SELECT `i`.`id`, `t`.`designation` as `name`
                        FROM `" . getTableForItemType($type) . "` as i
                        LEFT JOIN `$associated_table` as t ON (`t`.`id`=`i`.`$fk`)
                        WHERE `itemtype`='Computer'
                        AND `items_id`='" . $line['computers_id'] . "'
                        AND `is_dynamic`";

                  $prevalue = $type . self::FIELD_SEPARATOR;
                  foreach ($DB->request($query) as $data) {

                     $import_device[$prevalue . $data['id']] = $prevalue . $data["name"];

                     // TODO voir si il ne serait pas plus simple propre
                     // en adaptant updateDevices
                     // $import_device[$type][$data['id']] = $data["name"];
                  }
               }
               if ($bios && isset($ocsComputer['BIOS'])) {
                  self::updateDevices("PluginOcsinventoryngItem_DeviceBiosdata", $line['computers_id'], $ocsComputer['BIOS'], $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs, $import_device, '');
               }
               if ($memories && isset($ocsComputer['MEMORIES'])) {
                  self::updateDevices("Item_DeviceMemory", $line['computers_id'], $ocsComputer['MEMORIES'], $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs, $import_device, '');
               }
               if ($storages && isset($ocsComputer['STORAGES'])) {
                  if (isset($storages["hdd"]) && $storages["hdd"]) {
                     self::updateDevices("Item_DeviceHardDrive", $line['computers_id'], $ocsComputer['STORAGES'], $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs, $import_device, '');
                  }
                  if (isset($storages["drive"]) && $storages["drive"]) {
                     self::updateDevices("Item_DeviceDrive", $line['computers_id'], $ocsComputer['STORAGES'], $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs, $import_device, '');
                  }
               }
               if ($cpus && isset($ocsComputer['CPUS'])) {
                  self::updateDevices("Item_DeviceProcessor", $line['computers_id'], $ocsComputer['CPUS'], $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs, $import_device, '');
               }
               if ($videos && isset($ocsComputer['VIDEOS'])) {
                  self::updateDevices("Item_DeviceGraphicCard", $line['computers_id'], $ocsComputer['VIDEOS'], $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs, $import_device, '');
               }
               if ($mb && isset($ocsComputer['BIOS'])) {
                  self::updateDevices("Item_DeviceMotherboard", $line['computers_id'], $ocsComputer['BIOS'], $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs, $import_device, '');
               }
               if ($controllers && isset($ocsComputer['CONTROLLERS'])) {
                  self::updateDevices("Item_DeviceControl", $line['computers_id'], $ocsComputer['CONTROLLERS'], $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs, $import_device, '');
               }
               if ($sounds && isset($ocsComputer['SOUNDS'])) {
                  self::updateDevices("Item_DeviceSoundCard", $line['computers_id'], $ocsComputer['SOUNDS'], $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs, $import_device, '');
               }
               if ($networks && isset($ocsComputer['NETWORKS'])) {
                  self::updateDevices("Item_DeviceNetworkCard", $line['computers_id'], $ocsComputer['NETWORKS'], $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs, $import_device, array());
               }
               if ($modems && isset($ocsComputer['MODEMS'])) {
                  self::updateDevices("Item_DevicePci", $line['computers_id'], $ocsComputer, $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs, $import_device, '');
               }
               if ($slots && isset($ocsComputer['SLOTS'])) {
                  self::updateDevices("Item_DevicePci", $line['computers_id'], $ocsComputer, $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs, $import_device, '');
               }
               if ($ports && isset($ocsComputer['PORTS'])) {
                  self::updateDevices("Item_DevicePci", $line['computers_id'], $ocsComputer, $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs, $import_device, '');
               }
               if ($monitors && isset($ocsComputer["MONITORS"])) {
                  self::importMonitor($cfg_ocs, $line['computers_id'], $plugin_ocsinventoryng_ocsservers_id, $ocsComputer["MONITORS"], $comp->fields["entities_id"]);
               }
               if ($printers && isset($ocsComputer["PRINTERS"])) {
                  self::importPrinter($cfg_ocs, $line['computers_id'], $plugin_ocsinventoryng_ocsservers_id, $ocsComputer["PRINTERS"], $comp->fields["entities_id"]);
               }
               if ($inputs && isset($ocsComputer["INPUTS"])) {
                  self::importPeripheral($cfg_ocs, $line['computers_id'], $plugin_ocsinventoryng_ocsservers_id, $ocsComputer["INPUTS"], $comp->fields["entities_id"]);
               }
               if ($softwares && isset($ocsComputer["SOFTWARES"])) {
                  // Get import software
                  self::updateSoftware($cfg_ocs, $line['computers_id'], $ocsComputer, $comp->fields["entities_id"], $officepack);
               }
               if ($drives && isset($ocsComputer["DRIVES"])) {
                  // Get import drives
                  self::updateDisk($line['computers_id'], $ocsComputer["DRIVES"], $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs);
               }
               if ($registry && isset($ocsComputer["REGISTRY"])) {
                  //import registry entries not needed
                  self::updateRegistry($line['computers_id'], $ocsComputer["REGISTRY"]);
               }

               if ($antivirus && isset($ocsComputer["SECURITYCENTER"])) {
                  //import antivirus entries
                  self::updateAntivirus($line['computers_id'], $ocsComputer["SECURITYCENTER"], $cfg_ocs);
               }

               if ($winupdatestate && isset($ocsComputer["WINUPDATESTATE"])) {
                  //import winupdatestate entries
                  self::updateWinupdatestate($line['computers_id'], $ocsComputer["WINUPDATESTATE"], $cfg_ocs);
               }

               if ($proxysetting && isset($ocsComputer["NAVIGATORPROXYSETTING"])) {
                  //import proxysetting entries
                  self::updateProxysetting($line['computers_id'], $ocsComputer["NAVIGATORPROXYSETTING"], $cfg_ocs);
               }

               if ($winuser && isset($ocsComputer["WINUSERS"])) {
                  //import proxysetting entries
                  self::updateWinuser($line['computers_id'], $ocsComputer["WINUSERS"], $cfg_ocs);
               }

               if ($teamviewer && isset($ocsComputer["TEAMVIEWER"])) {
                  //import teamviewer entries
                  self::updateTeamviewer($line['computers_id'], $ocsComputer["TEAMVIEWER"], $cfg_ocs);
               }

               if ($uptime && isset($ocsComputer["UPTIME"])) {
                  //import uptime
                  self::updateUptime($line['id'], $ocsComputer["UPTIME"], $cfg_ocs);
               }

               if ($virtualmachines && isset($ocsComputer["VIRTUALMACHINES"])) {
                  // Get import vm
                  self::updateVirtualMachines($line['computers_id'], $ocsComputer["VIRTUALMACHINES"], $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs);
               }

               if ($runningprocess && isset($ocsComputer["RUNNINGPROCESS"])) {
                  //import runningprocess entries
                  self::updateRunningprocess($line['computers_id'], $ocsComputer["RUNNINGPROCESS"], $cfg_ocs);
               }
               if ($service && isset($ocsComputer["SERVICE"])) {
                  //import service entries
                  self::updateService($line['computers_id'], $ocsComputer["SERVICE"], $cfg_ocs);
               }
            }
            //Update TAG
            self::updateTag($line);
            // Update OCS Cheksum
            $oldChecksum = $ocsClient->getChecksum($line['ocsid']);
            $newchecksum = $oldChecksum - $mixed_checksum;
            $ocsClient->setChecksum($newchecksum, $line['ocsid']);
            //Return code to indicate that computer was synchronized
            return array('status'       => self::COMPUTER_SYNCHRONIZED,
                         'entities_id'  => $comp->fields["entities_id"],
                         'rule_matched' => array(),
                         'computers_id' => $line['computers_id']);
         }
         // ELSE Return code to indicate only last inventory date changed
         return array('status'       => self::COMPUTER_NOTUPDATED,
                      'entities_id'  => $comp->fields["entities_id"],
                      'rule_matched' => array(),
                      'computers_id' => $line['computers_id']);
      }
   }

   /**
    * @param array $params
    * @return array
    */
   static function getComputerHardware($params = array())
   {
      global $DB;

      $options['computers_id'] = 0;
      $options['ocs_id'] = 0;
      $options['plugin_ocsinventoryng_ocsservers_id'] = 0;
      $options['cfg_ocs'] = array();
      $options['computers_update'] = array();
      $options['check_history'] = true;
      $options['do_history'] = 2;

      foreach ($params as $key => $value) {
         $options[$key] = $value;
      }

      $is_utf8 = $options['cfg_ocs']["ocs_db_utf8"];
      $ocsServerId = $options['plugin_ocsinventoryng_ocsservers_id'];
      self::checkOCSconnection($ocsServerId);
      $ocsClient = self::getDBocs($ocsServerId);

      $opts = array(
         'DISPLAY' => array(
            'CHECKSUM' => PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE
         )
      );
      $ocsComputer = $ocsClient->getComputer($options['ocs_id'], $opts);

      $logHistory = $options['cfg_ocs']["history_hardware"];

      if ($ocsComputer) {
         $hardware = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($ocsComputer['HARDWARE']));
         $compupdate = array();

         if (intval($options['cfg_ocs']["import_os_serial"]) > 0
            && !in_array("os_license_number", $options['computers_updates'])
         ) {

            if (!empty($hardware["WINPRODKEY"])) {
               $compupdate["os_license_number"] = self::encodeOcsDataInUtf8($is_utf8, $hardware["WINPRODKEY"]);
            }
            if (!empty($hardware["WINPRODID"])) {
               $compupdate["os_licenseid"] = self::encodeOcsDataInUtf8($is_utf8, $hardware["WINPRODID"]);
            }
         }

         if ($options['check_history']) {
            $sql_computer = "SELECT `glpi_operatingsystems`.`name` AS os_name,
                                    `glpi_operatingsystemservicepacks`.`name` AS os_sp
                             FROM `glpi_computers`,
                                  `glpi_plugin_ocsinventoryng_ocslinks`,
                                  `glpi_operatingsystems`,
                                  `glpi_operatingsystemservicepacks`
                             WHERE `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id`
                                          = `glpi_computers`.`id`
                                   AND `glpi_operatingsystems`.`id`
                                          = `glpi_computers`.`operatingsystems_id`
                                   AND `glpi_operatingsystemservicepacks`.`id`
                                          =`glpi_computers`.`operatingsystemservicepacks_id`
                                   AND `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid`
                                          = '" . $options['ocs_id'] . "'
                                   AND `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id`
                                          = '" . $ocsServerId . "'";

            $res_computer = $DB->query($sql_computer);

            if ($DB->numrows($res_computer) == 1) {
               $data_computer = $DB->fetch_array($res_computer);
               $computerOS = $data_computer["os_name"];
               $computerOSSP = $data_computer["os_sp"];

               //Do not log software history in case of OS or Service Pack change
               if (!$options['do_history']
                  || $computerOS != $hardware["OSNAME"]
                  || $computerOSSP != $hardware["OSCOMMENTS"]
               ) {
                  $logHistory = 0;
               }
            }
         }

         if (intval($options['cfg_ocs']["import_general_os"]) > 0) {
            if (!in_array("operatingsystems_id", $options['computers_updates'])) {
               $osname = self::encodeOcsDataInUtf8($is_utf8, $hardware['OSNAME']);
               $compupdate["operatingsystems_id"] = Dropdown::importExternal('OperatingSystem', $osname);
            }

            if (!in_array("operatingsystemversions_id", $options['computers_updates'])) {
               $compupdate["operatingsystemversions_id"] = Dropdown::importExternal('OperatingSystemVersion', self::encodeOcsDataInUtf8($is_utf8, $hardware["OSVERSION"]));
            }

            if (!strpos($hardware["OSCOMMENTS"], "CEST")
               && !in_array("operatingsystemservicepacks_id", $options['computers_updates'])
            ) {// Not linux comment
               $compupdate["operatingsystemservicepacks_id"] = Dropdown::importExternal('OperatingSystemServicePack', self::encodeOcsDataInUtf8($is_utf8, $hardware["OSCOMMENTS"]));
            }
            //Enable For GLPI 9.1
            if ((!in_array("operatingsystemarchitectures_id", $options['computers_updates']))
               && FieldExists("glpi_computers", "operatingsystemarchitectures_id")
                  && isset($hardware["ARCH"])
            ) {
               $compupdate["operatingsystemarchitectures_id"] = Dropdown::importExternal('OperatingSystemArchitecture', self::encodeOcsDataInUtf8($is_utf8, $hardware["ARCH"]));
            }
         }

         if (intval($options['cfg_ocs']["import_general_domain"]) > 0
            && !in_array("domains_id", $options['computers_updates'])
         ) {
            $compupdate["domains_id"] = Dropdown::importExternal('Domain', self::encodeOcsDataInUtf8($is_utf8, $hardware["WORKGROUP"]));
         }

         if (intval($options['cfg_ocs']["import_general_contact"]) > 0
             && !in_array("contact", $options['computers_updates'])) {

            $compupdate["contact"] = self::encodeOcsDataInUtf8($is_utf8, $hardware["USERID"]);

            if (intval($options['cfg_ocs']["import_user"]) > 0) {
               $query  = "SELECT `id`
                      FROM `glpi_users`
                      WHERE `name` = '" . $hardware["USERID"] . "';";
               $result = $DB->query($query);

               if ($DB->numrows($result) == 1
                   && !in_array("users_id", $options['computers_updates'])) {
                  $compupdate["users_id"] = $DB->result($result, 0, 0);
               }
            }
         }

         if (intval($options['cfg_ocs']["import_general_name"]) > 0
            && !in_array("name", $options['computers_updates'])
         ) {
            $compupdate["name"] = self::encodeOcsDataInUtf8($is_utf8, $hardware["NAME"]);
         }

         if (intval($options['cfg_ocs']["import_general_comment"]) > 0
            && !in_array("comment", $options['computers_updates'])
         ) {

            $compupdate["comment"] = "";
            if (!empty($hardware["DESCRIPTION"])
               && $hardware["DESCRIPTION"] != NOT_AVAILABLE
            ) {
               $compupdate["comment"] .= self::encodeOcsDataInUtf8($is_utf8, $hardware["DESCRIPTION"])
                  . "\r\n";
            }
            $compupdate["comment"] .= sprintf(__('%1$s: %2$s'), __('Swap', 'ocsinventoryng'), self::encodeOcsDataInUtf8($is_utf8, $hardware["SWAP"]));
         }

         if ($options['cfg_ocs']['ocs_version'] >= self::OCS1_3_VERSION_LIMIT
            && intval($options['cfg_ocs']["import_general_uuid"]) > 0
            && !in_array("uuid", $options['computers_updates'])
         ) {
            $compupdate["uuid"] = $hardware["UUID"];
         }

         return array('logHistory' => $logHistory, 'fields' => $compupdate);
      }
   }

   /**
    * Update the computer hardware configuration
    *
    * @param $params array
    *
    *
    * @return array
    */
   static function updateHardware($params = array())
   {

      $p = array('computers_id'                        => 0,
                 'ocs_id'                              => 0,
                 'plugin_ocsinventoryng_ocsservers_id' => 0,
                 'cfg_ocs'                             => array(),
                 'computers_updates'                   => array(),
                 'dohistory'                           => true,
                 'check_history'                       => true,
                 'entities_id'                         => 0);
      foreach ($params as $key => $value) {
         $p[$key] = $value;
      }

      self::checkOCSconnection($p['plugin_ocsinventoryng_ocsservers_id']);
      $results = self::getComputerHardware($params);

      if (count($results['fields'])) {
         $results['fields']["id"] = $p['computers_id'];
         $results['fields']["entities_id"] = $p['entities_id'];
         $results['fields']["_nolock"] = true;
         $comp = new Computer();
         $comp->update($results['fields'], $p['dohistory']);
      }
      //}

      return array("history" => $results['logHistory']);
   }

   /**
    * Update the computer bios configuration
    *
    *
    * @param $computers_id integer : ocs computer id.
    * @param $ocsComputer
    * @param $cfg_ocs array : ocs config
    * @param $computer_updates array : already updated fields of the computer
    * @param int|the $entities_id
    * @return nothing .
    * @internal param int $plugin_ocsinventoryng_ocsservers_id : ocs server id
    * @internal param int $ocsid : glpi computer id
    * @internal param the $entities_id entity in which the computer is imported
    */
   static function updateBios($computers_id, $ocsComputer, $cfg_ocs, $computer_updates, $entities_id = 0)
   {

      $compupdate = array();
      $computer = $ocsComputer;
      if (!is_null($computer)) {
         $compudate = array();

         if (isset($computer["BIOS"])) {

            $bios = $computer['BIOS'];

            if ($cfg_ocs["import_general_serial"]
               && $cfg_ocs["import_general_serial"] > 0
               && intval($cfg_ocs["import_device_bios"]) > 0
               && !in_array("serial", $computer_updates)
            ) {
               $compupdate["serial"] = self::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $bios["SSN"]);
            }

            if (intval($cfg_ocs["import_general_model"]) > 0
               && intval($cfg_ocs["import_device_bios"]) > 0
               && !in_array("computermodels_id", $computer_updates)
            ) {

               $compupdate["computermodels_id"] = Dropdown::importExternal('ComputerModel', self::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $bios["SMODEL"]), -1, (isset($bios["SMANUFACTURER"]) ? array("manufacturer" => $bios["SMANUFACTURER"]) : array()));
            }

            if (intval($cfg_ocs["import_general_manufacturer"]) > 0
               && intval($cfg_ocs["import_device_bios"]) > 0
               && !in_array("manufacturers_id", $computer_updates)
            ) {

               $compupdate["manufacturers_id"] = Dropdown::importExternal('Manufacturer', self::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $bios["SMANUFACTURER"]));
            }

            if (intval($cfg_ocs["import_general_type"]) > 0
               && intval($cfg_ocs["import_device_bios"]) > 0
               && !empty($bios["TYPE"])
               && !in_array("computertypes_id", $computer_updates)
            ) {

               $compupdate["computertypes_id"] = Dropdown::importExternal('ComputerType', self::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $bios["TYPE"]));
            }

            if (count($compupdate)) {
               Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($compupdate));
               $compupdate["id"] = $computers_id;
               $compupdate["entities_id"] = $entities_id;
               $compupdate["_nolock"] = true;
               $comp = new Computer();
               $comp->update($compupdate, $cfg_ocs['history_bios']);
            }
         }
      }
   }

   /**
    * Import a group from OCS table.
    *
    * @param $value string : Value of the new dropdown.
    * @param $entities_id int : entity in case of specific dropdown
    *
    * @return integer : dropdown id.
    * */
   static function importGroup($value, $entities_id)
   {
      global $DB;

      if (empty($value)) {
         return 0;
      }

      $query = "SELECT `id`
                 FROM `glpi_groups`
                 WHERE `name` = '$value' ";

      $query .= getEntitiesRestrictRequest(' AND ', 'glpi_groups', '',
         $entities_id, true);

      $result = $DB->query($query);

      if ($DB->numrows($result) == 0) {
         $group = new Group();
         $input["name"] = $value;
         $input["entities_id"] = $entities_id;
         return $group->add($input);
      }
      $line = $DB->fetch_array($result);
      return $line["id"];
   }

   /**
    * Displays a list of computers that can be cleaned.
    *
    * @param $plugin_ocsinventoryng_ocsservers_id int : id of ocs server in GLPI
    * @param $check string : parameter for HTML input checkbox
    * @param $start int : parameter for Html::printPager method
    *
    *
    * @return bool
    */
   static function showComputersToClean($plugin_ocsinventoryng_ocsservers_id, $check, $start)
   {
      global $DB, $CFG_GLPI;

      self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);

      if (!Session::haveRight("plugin_ocsinventoryng_clean", READ)) {
         return false;
      }
      $canedit = Session::haveRight("plugin_ocsinventoryng_clean", UPDATE);

      // Select unexisting OCS hardware
      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);

      $computerOptions = array(
         'COMPLETE' => '0',
      );

      $computers = $ocsClient->getComputers($computerOptions);

      if (isset($computers['COMPUTERS'])
         && count($computers['COMPUTERS']) > 0) {
         $hardware = $computers['COMPUTERS'];
      } else {
         $hardware = array();
      }

      $query = "SELECT `ocsid`
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `plugin_ocsinventoryng_ocsservers_id`
                           = '$plugin_ocsinventoryng_ocsservers_id'";
      $result = $DB->query($query);

      $ocs_missing = array();
      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetch_array($result)) {
            $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));
            if (!isset($hardware[$data["ocsid"]])) {
               $ocs_missing[$data["ocsid"]] = $data["ocsid"];
            }
         }
      }

      $sql_ocs_missing = "";
      if (count($ocs_missing)) {
         $sql_ocs_missing = " OR `ocsid` IN ('" . implode("','", $ocs_missing) . "')";
      }

      //Select unexisting computers
      $query_glpi = "SELECT `glpi_plugin_ocsinventoryng_ocslinks`.`entities_id` AS entities_id,
                            `glpi_plugin_ocsinventoryng_ocslinks`.`ocs_deviceid` AS ocs_deviceid,
                            `glpi_plugin_ocsinventoryng_ocslinks`.`last_update` AS last_update,
                            `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid` AS ocsid,
                            `glpi_plugin_ocsinventoryng_ocslinks`.`id`,
                            `glpi_computers`.`name` AS name
                     FROM `glpi_plugin_ocsinventoryng_ocslinks`
                     LEFT JOIN `glpi_computers`
                           ON `glpi_computers`.`id` = `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id`
                     WHERE ((`glpi_computers`.`id` IS NULL
                             AND `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id`
                                    = '$plugin_ocsinventoryng_ocsservers_id')" .
         $sql_ocs_missing . ")" .
         getEntitiesRestrictRequest(" AND", "glpi_plugin_ocsinventoryng_ocslinks");

      $result_glpi = $DB->query($query_glpi);

      // fetch all links missing between glpi and OCS
      $already_linked = array();
      if ($DB->numrows($result_glpi) > 0) {
         while ($data = $DB->fetch_assoc($result_glpi)) {
            $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));

            $already_linked[$data["ocsid"]]["entities_id"] = $data["entities_id"];
            $already_linked[$data["ocsid"]]["ocs_deviceid"] = $data["ocs_deviceid"];
            $already_linked[$data["ocsid"]]["date"] = $data["last_update"];
            $already_linked[$data["ocsid"]]["id"] = $data["id"];
            $already_linked[$data["ocsid"]]["in_ocs"] = isset($hardware[$data["ocsid"]]);

            if ($data["name"] == null) {
               $already_linked[$data["ocsid"]]["in_glpi"] = 0;
            } else {
               $already_linked[$data["ocsid"]]["in_glpi"] = 1;
            }
         }
      }

      echo "<div class='center'>";
      echo "<h2>" . __('Clean links between GLPI and OCSNG', 'ocsinventoryng') . "</h2>";

      $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsng.clean.php';
      if (($numrows = count($already_linked)) > 0) {
         $parameters = "check=$check";
         Html::printPager($start, $numrows, $target, $parameters);

         // delete end
         array_splice($already_linked, $start + $_SESSION['glpilist_limit']);

         // delete begin
         if ($start > 0) {
            array_splice($already_linked, 0, $start);
         }

         echo "<form method='post' id='ocsng_form' name='ocsng_form' action='" . $target . "'>";
         if ($canedit) {
            self::checkBox($target);
         }
         echo "<table class='tab_cadre'>";
         echo "<tr><th>" . __('Item') . "</th><th>" . __('Import date in GLPI', 'ocsinventoryng') . "</th>";
         echo "<th>" . __('Existing in GLPI', 'ocsinventoryng') . "</th>";
         echo "<th>" . __('Existing in OCSNG', 'ocsinventoryng') . "</th>";
         if (Session::isMultiEntitiesMode()) {
            echo "<th>" . __('Entity') . "</th>";
         }
         if ($canedit) {
            echo "<th>&nbsp;</th>";
         }
         echo "</tr>\n";

         echo "<tr class='tab_bg_1'><td colspan='6' class='center'>";
         if ($canedit) {
            echo "<input class='submit' type='submit' name='clean_ok' value=\"" .
               _sx('button', 'Clean') . "\">";
         }
         echo "</td></tr>\n";

         foreach ($already_linked as $ID => $tab) {
            echo "<tr class='tab_bg_2 center'>";
            echo "<td>" . $tab["ocs_deviceid"] . "</td>\n";
            echo "<td>" . Html::convDateTime($tab["date"]) . "</td>\n";
            echo "<td>" . Dropdown::getYesNo($tab["in_glpi"]) . "</td>\n";
            echo "<td>" . Dropdown::getYesNo($tab["in_ocs"]) . "</td>\n";
            if (Session::isMultiEntitiesMode()) {
               echo "<td>" . Dropdown::getDropdownName('glpi_entities', $tab['entities_id']) . "</td>\n";
            }
            if ($canedit) {
               echo "<td><input type='checkbox' name='toclean[" . $tab["id"] . "]' " .
                  (($check == "all") ? "checked" : "") . "></td>";
            }
            echo "</tr>\n";
         }

         echo "<tr class='tab_bg_1'><td colspan='6' class='center'>";
         if ($canedit) {
            echo "<input class='submit' type='submit' name='clean_ok' value=\"" .
               _sx('button', 'Clean') . "\">";
         }
         echo "</td></tr>";
         echo "</table>\n";
         Html::closeForm();
         Html::printPager($start, $numrows, $target, $parameters);
      } else {
         echo "<div class='center b '>" . __('No item to clean', 'ocsinventoryng') . "</div>";
         echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.php'>";
         echo __('Back');
         echo "</a>";
      }
      echo "</div>";
   }

   /**
    * Clean links between GLPI and OCS from a list.
    *
    * @param $plugin_ocsinventoryng_ocsservers_id int : id of ocs server in GLPI
    * @param $ocslinks_id array : ids of ocslinks to clean
    *
    * @return nothing
    * */
   static function cleanLinksFromList($plugin_ocsinventoryng_ocsservers_id, $ocslinks_id)
   {
      global $DB;

      $cfg_ocs = self::getConfig($plugin_ocsinventoryng_ocsservers_id);

      foreach ($ocslinks_id as $key => $val) {

         $query = "SELECT *
                   FROM `glpi_plugin_ocsinventoryng_ocslinks`
                   WHERE `id` = '$key'
                         AND `plugin_ocsinventoryng_ocsservers_id`
                                 = '$plugin_ocsinventoryng_ocsservers_id'";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               $data = $DB->fetch_array($result);

               $comp = new Computer();
               if ($cfg_ocs['deleted_behavior']) {
                  if ($cfg_ocs['deleted_behavior'] == 1) {
                     $comp->delete(array("id" => $data["computers_id"]), 0);
                  } else {
                     if (preg_match('/STATE_(.*)/', $cfg_ocs['deleted_behavior'], $results)) {
                        $tmp['id'] = $data["computers_id"];
                        $tmp['states_id'] = $results[1];
                        $tmp['entities_id'] = $data['entities_id'];
                        $tmp["_nolock"] = true;
                        $comp->update($tmp);
                     }
                  }
               }

               //Add history to indicates that the machine was deleted from OCS
               $changes[0] = '0';
               $changes[1] = $data["ocsid"];
               $changes[2] = "";
               PluginOcsinventoryngOcslink::history($data["computers_id"], $changes, PluginOcsinventoryngOcslink::HISTORY_OCS_DELETE);

               $query = "DELETE
                         FROM `glpi_plugin_ocsinventoryng_ocslinks`
                         WHERE `id` = '" . $data["id"] . "'";
               $DB->query($query);
            }
         }
      }
   }

   /**
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param $check
    * @param $start
    * @return bool|void
    */
   static function showComputersToUpdate($plugin_ocsinventoryng_ocsservers_id, $check, $start)
   {
      global $DB, $CFG_GLPI;

      self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      if (!Session::haveRight("plugin_ocsinventoryng", UPDATE)
          && !Session::haveRight("plugin_ocsinventoryng_sync", UPDATE)) {
         return false;
      }

      $cfg_ocs = self::getConfig($plugin_ocsinventoryng_ocsservers_id);

      // Get linked computer ids in GLPI
      $already_linked_query = "SELECT `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid` AS ocsid
                               FROM `glpi_plugin_ocsinventoryng_ocslinks`
                               WHERE `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id`
                                            = '$plugin_ocsinventoryng_ocsservers_id'";
      $already_linked_result = $DB->query($already_linked_query);

      if ($DB->numrows($already_linked_result) == 0) {
         echo "<div class='center b'>" . __('No new computer to be updated', 'ocsinventoryng');
         echo "<br><a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.php'>";
         echo __('Back');
         echo "</a>";
         echo "</div>";
         return;
      }

      $already_linked_ids = array();
      while ($data = $DB->fetch_assoc($already_linked_result)) {
         $already_linked_ids [] = $data['ocsid'];
      }

      $query = "SELECT MAX(`last_ocs_update`)
                   FROM `glpi_plugin_ocsinventoryng_ocslinks`
                   WHERE `plugin_ocsinventoryng_ocsservers_id`='$plugin_ocsinventoryng_ocsservers_id'";
      $max_date = "0000-00-00 00:00:00";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            $max_date = $DB->result($result, 0, 0);
         }
      }

      // Fetch linked computers from ocs
      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);

      $ocsResult = $ocsClient->getComputers(array(
         'ORDER'    => 'LASTDATE',
         'COMPLETE' => '0',
         'FILTER'   => array(
            'IDS'      => $already_linked_ids,
            'CHECKSUM' => $cfg_ocs["checksum"],
            //'INVENTORIED_BEFORE' => 'NOW()',
            //'INVENTORIED_SINCE' => $max_date,
         )
      ));

      if (isset($ocsResult['COMPUTERS'])) {
         if (count($ocsResult['COMPUTERS']) > 0) {
            // Get all ids of the returned computers
            $ocs_computer_ids = array();
            $hardware = array();
            $computers = array_slice($ocsResult['COMPUTERS'], $start, $_SESSION['glpilist_limit']);
            foreach ($computers as $computer) {
               $ID = $computer['META']['ID'];
               $ocs_computer_ids [] = $ID;

               $hardware[$ID]["date"] = $computer['META']["LASTDATE"];
               $hardware[$ID]["checksum"] = $computer['META']["CHECKSUM"];
               $hardware[$ID]["tag"] = $computer['META']["TAG"];
               $hardware[$ID]["name"] = addslashes($computer['META']["NAME"]);
            }

            // Fetch all linked computers from GLPI that were returned from OCS
            $query_glpi = "SELECT `glpi_plugin_ocsinventoryng_ocslinks`.`last_update` AS last_update,
                                  `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id` AS computers_id,
                                  `glpi_computers`.`serial` AS serial,
                                  `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid` AS ocsid,
                                  `glpi_computers`.`name` AS name,
                                  `glpi_plugin_ocsinventoryng_ocslinks`.`use_auto_update`,
                                  `glpi_plugin_ocsinventoryng_ocslinks`.`id`
                           FROM `glpi_plugin_ocsinventoryng_ocslinks`
                           LEFT JOIN `glpi_computers` ON (`glpi_computers`.`id`= `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id`)
                           WHERE `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id`
                                       = '$plugin_ocsinventoryng_ocsservers_id'
                                  AND `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid` IN (" . implode(',', $ocs_computer_ids) . ")
                           ORDER BY `glpi_plugin_ocsinventoryng_ocslinks`.`use_auto_update` DESC,
                                    `last_update`,
                                    `name`";
            $result_glpi = $DB->query($query_glpi);

            // Get all links between glpi and OCS
            $already_linked = array();
            if ($DB->numrows($result_glpi) > 0) {
               while ($data = $DB->fetch_assoc($result_glpi)) {
                  $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));
                  if (isset($hardware[$data["ocsid"]])) {
                     $already_linked[$data["ocsid"]]["date"] = $data["last_update"];
                     $already_linked[$data["ocsid"]]["name"] = $data["name"];
                     if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                        $already_linked[$data["ocsid"]]["name"] = sprintf(__('%1$s (%2$s)'), $data["name"], $data["id"]);
                     }
                     $already_linked[$data["ocsid"]]["id"] = $data["id"];
                     $already_linked[$data["ocsid"]]["computers_id"] = $data["computers_id"];
                     $already_linked[$data["ocsid"]]["serial"] = $data["serial"];
                     $already_linked[$data["ocsid"]]["ocsid"] = $data["ocsid"];
                     $already_linked[$data["ocsid"]]["use_auto_update"] = $data["use_auto_update"];
                  }
               }
            }
            echo "<div class='center'>";
            echo "<h2>" . __('Computers updated in OCSNG', 'ocsinventoryng') . "</h2>";

            $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsng.sync.php';
            if (($numrows = $ocsResult['TOTAL_COUNT']) > 0) {
               $parameters = "check=$check";
               Html::printPager($start, $numrows, $target, $parameters);

               echo "<form method='post' id='ocsng_form' name='ocsng_form' action='" . $target . "'>";
               self::checkBox($target);

               echo "<table class='tab_cadre_fixe'>";
               $colspan = 6;
               if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                  $colspan = 7;
               }
               echo "<tr class='tab_bg_1'><td colspan='$colspan' class='center'>";
               echo "<input class='submit' type='submit' name='update_ok' value=\"" .
                  _sx('button', 'Synchronize', 'ocsinventoryng') . "\">";
               echo "</td></tr>\n";

               echo "<tr><th>" . __('Update computers', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('Serial number') . "</th>";
               echo "<th>" . __('Import date in GLPI', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('Last OCSNG inventory date', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('OCSNG TAG', 'ocsinventoryng') . "</th>";
               if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                  echo "<th>" . __('DEBUG') . "</th>";
               }
               echo "<th>&nbsp;</th></tr>\n";

               foreach ($already_linked as $ID => $tab) {
                  echo "<tr class='tab_bg_2 center'>";
                  echo "<td><a href='" . $CFG_GLPI["root_doc"] . "/front/computer.form.php?id=" .
                     $tab["computers_id"] . "'>" . $tab["name"] . "</a></td>\n";
                  echo "<td>" . $tab["serial"] . "</td>\n";
                  echo "<td>" . Html::convDateTime($tab["date"]) . "</td>\n";
                  echo "<td>" . Html::convDateTime($hardware[$tab["ocsid"]]["date"]) . "</td>\n";
                  echo "<td>" . $hardware[$tab["ocsid"]]["tag"] . "</td>\n";

                  if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                     echo "<td>";
                     $checksum_server = intval($cfg_ocs["checksum"]);
                     $checksum_client = intval($hardware[$tab["ocsid"]]["checksum"]);
                     if ($checksum_client > 0
                        && $checksum_client > 0
                     ) {
                        $result = $checksum_server & $checksum_client;
                        echo intval($result);
                     }
                     echo "</td>";
                  }

                  echo "<td><input type='checkbox' name='toupdate[" . $tab["id"] . "]' " .
                     (($check == "all") ? "checked" : "") . "></td></tr>\n";
               }

               echo "<tr class='tab_bg_1'><td colspan='$colspan' class='center'>";
               echo "<input class='submit' type='submit' name='update_ok' value=\"" .
                  _sx('button', 'Synchronize', 'ocsinventoryng') . "\">";
               echo "<input type=hidden name='plugin_ocsinventoryng_ocsservers_id' " .
                  "value='$plugin_ocsinventoryng_ocsservers_id'>";
               echo "</td></tr>";

               echo "<tr class='tab_bg_1'><td colspan='$colspan' class='center'>";
               self::checkBox($target);
               echo "</table>\n";
               Html::closeForm();
               Html::printPager($start, $numrows, $target, $parameters);
            } else {
               echo "<br><span class='b'>" . __('Update computers', 'ocsinventoryng') . "</span>";
            }
            echo "</div>";
         } else {
            echo "<div class='center b'>" . __('No new computer to be updated', 'ocsinventoryng');
            echo "<br><a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.php'>";
            echo __('Back');
            echo "</a>";
            echo "</div>";
         }
      } else {
         echo "<div class='center b'>" . __('No new computer to be updated', 'ocsinventoryng');
         echo "<br><a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.php'>";
         echo __('Back');
         echo "</a>";
         echo "</div>";
      }
   }

   /**
    * @param $computers_id
    * @param $tomerge
    * @param $field
    * @return bool
    */
   static function mergeOcsArray($computers_id, $tomerge, $field)
   {
      global $DB;

      $query = "SELECT `$field`
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `computers_id` = '$computers_id'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            $tab = importArrayFromDB($DB->result($result, 0, 0));
            $newtab = array_merge($tomerge, $tab);
            $newtab = array_unique($newtab);

            $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                      SET `$field` = '" . addslashes(exportArrayToDB($newtab)) . "'
                      WHERE `computers_id` = '$computers_id'";
            if ($DB->query($query)) {
               return true;
            }
         }
      }
      return false;
   }

   /**
    * @param $computers_id
    * @param $todel
    * @param $field
    * @param bool $is_value_to_del
    * @return bool
    */
   static function deleteInOcsArray($computers_id, $todel, $field, $is_value_to_del = false)
   {
      global $DB;

      $query = "SELECT `$field`, `plugin_ocsinventoryng_ocsservers_id`
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `computers_id` = '$computers_id'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {

            $cfg_ocs = self::getConfig($DB->result($result, 0, "plugin_ocsinventoryng_ocsservers_id"));
            if ($cfg_ocs["use_locks"]) {

               $tab = importArrayFromDB($DB->result($result, 0, $field));

               if ($is_value_to_del) {
                  $todel = array_search($todel, $tab);
               }
               if (isset($tab[$todel])) {
                  unset($tab[$todel]);
                  $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                            SET `$field` = '" . addslashes(exportArrayToDB($tab)) . "'
                            WHERE `computers_id` = '$computers_id'";
                  if ($DB->query($query)) {
                     return true;
                  }
               }
            }
         }
      }
      return false;
   }

   /**
    * @param $computers_id
    * @param $newArray
    * @param $field
    * @param bool $lock
    * @return bool
    */
   static function replaceOcsArray($computers_id, $newArray, $field, $lock = true)
   {
      global $DB;

      $newArray = addslashes(exportArrayToDB($newArray));

      $query = "SELECT `$field`, `plugin_ocsinventoryng_ocsservers_id`
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `computers_id` = '$computers_id'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {

            $cfg_ocs = self::getConfig($DB->result($result, 0, "plugin_ocsinventoryng_ocsservers_id"));
            if ($lock && $cfg_ocs["use_locks"]) {

               $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                         SET `$field` = '" . $newArray . "'
                         WHERE `computers_id` = '$computers_id'";
               $DB->query($query);

               return true;
            }
         }
      }
      return false;
   }


   /**
    * @param $computers_id
    * @param $toadd
    * @param $field
    * @return bool
    */
   static function addToOcsArray($computers_id, $toadd, $field)
   {
      global $DB;

      $query = "SELECT `$field`, `plugin_ocsinventoryng_ocsservers_id`
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `computers_id` = '$computers_id'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {

            $cfg_ocs = self::getConfig($DB->result($result, 0, "plugin_ocsinventoryng_ocsservers_id"));
            if ($cfg_ocs["use_locks"]) {

               $tab = importArrayFromDB($DB->result($result, 0, $field));

               // Stripslashes because importArray get clean array
               foreach ($toadd as $key => $val) {
                  $tab[] = stripslashes($val);
               }

               $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                         SET `$field` = '" . addslashes(exportArrayToDB($tab)) . "'
                         WHERE `computers_id` = '$computers_id'";
               $DB->query($query);

               return true;
            }
         }
      }
      return false;
   }

   /**
    * Display a list of computers to add or to link
    *
    * @param $serverId
    * @param display $advanced
    * @param indicates $check
    * @param display $start
    * @param a|int $entity
    * @param bool|false $tolinked
    * @internal param the $plugin_ocsinventoryng_ocsservers_id ID of the ocs server
    * @internal param display $advanced detail about the computer import or not (target entity, matched rules, etc.)
    * @internal param indicates $check if checkboxes are checked or not
    * @internal param display $start a list of computers starting at rowX
    * @internal param a $entity list of entities in which computers can be added or linked
    * @internal param false $tolinked for an import, true for a link
    *
    * @return bool
    */
   static function showComputersToAdd($serverId, $advanced, $check, $start, $entity = 0, $tolinked = false)
   {
      global $DB, $CFG_GLPI;

      if (!Session::haveRight("plugin_ocsinventoryng", UPDATE)
          && !Session::haveRight("plugin_ocsinventoryng_import", UPDATE)
          && !Session::haveRight("plugin_ocsinventoryng_link", UPDATE)) {
         return false;
      }

      $caneditimport = Session::haveRight('plugin_ocsinventoryng_import', UPDATE);
      $caneditlink   = Session::haveRight('plugin_ocsinventoryng_link', UPDATE);

      $title = __('Import new computers', 'ocsinventoryng');
      if ($tolinked) {
         $title = __('Link new OCSNG computers to existing GLPI computers', 'ocsinventoryng');
      }
      $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsng.import.php';
      if ($tolinked) {
         $target = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng/front/ocsng.link.php';
      }

      // Get all links between glpi and OCS
      $query_glpi = "SELECT ocsid
                     FROM `glpi_plugin_ocsinventoryng_ocslinks`
                     WHERE `plugin_ocsinventoryng_ocsservers_id` = '$serverId'";
      $result_glpi = $DB->query($query_glpi);
      $already_linked = array();
      if ($DB->numrows($result_glpi) > 0) {
         while ($data = $DB->fetch_array($result_glpi)) {
            $already_linked [] = $data["ocsid"];
         }
      }

      $cfg_ocs = self::getConfig($serverId);
      $computerOptions = array('ORDER'    => 'LASTDATE',
                               'COMPLETE' => '0',
                               'FILTER'   => array(
                                  'EXCLUDE_IDS' => $already_linked
                               ),
                               'DISPLAY'  => array(
                                  'CHECKSUM' => PluginOcsinventoryngOcsClient::CHECKSUM_BIOS | PluginOcsinventoryngOcsClient::CHECKSUM_NETWORK_ADAPTERS
                               ),
                               'ORDER'    => 'NAME'
      );

      if ($cfg_ocs["tag_limit"] and $tag_limit = explode("$", trim($cfg_ocs["tag_limit"]))) {
         $computerOptions['FILTER']['TAGS'] = $tag_limit;
      }

      if ($cfg_ocs["tag_exclude"] and $tag_exclude = explode("$", trim($cfg_ocs["tag_exclude"]))) {
         $computerOptions['FILTER']['EXCLUDE_TAGS'] = $tag_exclude;
      }

      $ocsClient = self::getDBocs($serverId);
      $allComputers = $ocsClient->countComputers($computerOptions);

      if ($start != 0) {
         $computerOptions['OFFSET'] = $start;
      }
      $computerOptions['MAX_RECORDS'] = $start + $_SESSION['glpilist_limit'];
      $ocsResult = $ocsClient->getComputers($computerOptions);

      $computers = (isset($ocsResult['COMPUTERS'])?$ocsResult['COMPUTERS']:array());

      $hardware = array();
      if (isset($computers)) {
         if (count($computers)) {
            // Get all hardware from OCS DB

            foreach ($computers as $data) {

               $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));

               $id = $data['META']['ID'];
               $hardware[$id]["date"] = $data['META']["LASTDATE"];
               $hardware[$id]["name"] = $data['META']["NAME"];
               $hardware[$id]["TAG"] = $data['META']["TAG"];
               $hardware[$id]["id"] = $data['META']["ID"];
               $hardware[$id]["UUID"] = $data['META']["UUID"];
               $contact = $data['META']["USERID"];

               if (!empty($contact)) {
                  $query = "SELECT `id`
                            FROM `glpi_users`
                            WHERE `name` = '" . $contact . "';";
                  $result = $DB->query($query);
                  $hardware[$id]["locations_id"] = 0;
                  if ($DB->numrows($result) == 1) {
                     $user_id = $DB->result($result, 0, 0);
                     $user = new User();
                     $user->getFromDB($user_id);
                     $hardware[$id]["locations_id"] = $user->fields["locations_id"];
                  }
               }
               if (isset($data['BIOS']) && count($data['BIOS'])) {
                  $hardware[$id]["serial"] = $data['BIOS']["SSN"];
                  $hardware[$id]["model"] = $data['BIOS']["SMODEL"];
                  $hardware[$id]["manufacturer"] = $data['BIOS']["SMANUFACTURER"];
               } else {
                  $hardware[$id]["serial"] = '';
                  $hardware[$id]["model"] = '';
                  $hardware[$id]["manufacturer"] = '';
               }

               if (isset($data['NETWORKS']) && count($data['NETWORKS'])) {
                  $hardware[$id]["NETWORKS"] = $data["NETWORKS"];

               }
            }

            if ($tolinked && count($hardware)) {
               echo "<div class='center b'>" .
                  __('Caution! The imported data (see your configuration) will overwrite the existing one', 'ocsinventoryng') . "</div>";
            }
            echo "<div class='center'>";

            if ($numrows = count($allComputers)) {
               $parameters = "check=$check";
               Html::printPager($start, $numrows, $target, $parameters);

               //Show preview form only in import even in multi-entity mode because computer import
               //can be refused by a rule
               if (!$tolinked) {
                  echo "<div class='firstbloc'>";
                  echo "<form method='post' name='ocsng_import_mode' id='ocsng_import_mode'
                         action='$target'>\n";
                  echo "<table class='tab_cadre_fixe'>";
                  echo "<tr><th>" . __('Manual import mode', 'ocsinventoryng') . "</th></tr>\n";
                  echo "<tr class='tab_bg_1'><td class='center'>";
                  if ($advanced) {
                     Html::showSimpleForm($target, 'change_import_mode', __('Disable preview', 'ocsinventoryng'), array('id' => 'false'));
                  } else {
                     Html::showSimpleForm($target, 'change_import_mode', __('Enable preview', 'ocsinventoryng'), array('id' => 'true'));
                  }
                  echo "</td></tr>";
                  echo "<tr class='tab_bg_1'><td class='center b'>" .
                     __('Check first that duplicates have been correctly managed in OCSNG', 'ocsinventoryng') . "</td>";
                  echo "</tr></table>";
                  Html::closeForm();
                  echo "</div>";
               }

               echo "<form method='post' name='ocsng_form' id='ocsng_form' action='$target'>";
               if ($caneditlink && $caneditimport) {
                  self::checkBox($target);
               }
               echo "<table class='tab_cadre_fixe'>";
               echo "<tr class='tab_bg_1'><td colspan='" . (($advanced || $tolinked) ? 10 : 7) . "' class='center'>";
               if (($tolinked && $caneditlink)
                  || (!$tolinked && $caneditimport)) {
                  if (($tolinked && $caneditlink)) {
                  echo "<input class='submit' type='submit' name='import_ok' value=\"" .
                       _sx('button', 'Link', 'ocsinventoryng') . "\">";
                  echo "&nbsp;<input class='submit' type='submit' name='delete_link' value=\"" .
                       _sx('button', 'Delete link', 'ocsinventoryng') . "\">";
                  } elseif (!$tolinked && $caneditimport) {
                  echo "<input class='submit' type='submit' name='import_ok' value=\"" .
                       _sx('button', 'Import', 'ocsinventoryng') . "\">";
                  }
               }
               echo "</td></tr>\n";
               echo "<tr>";
               if ($caneditlink || $caneditimport) {
                  echo "<th width='5%'>&nbsp;</th>";
               }
               echo "<th>" . __('Name') . "</th>\n";
               echo "<th>" . __('Manufacturer') . "</th>\n";
               echo "<th>" . __('Model') . "</th>\n";
               echo "<th>" . _n('Information', 'Informations', 2) . "</th>\n";
               echo "<th>" . __('Date') . "</th>\n";
               echo "<th>" . __('OCSNG TAG', 'ocsinventoryng') . "</th>\n";
               if ($advanced && !$tolinked) {
                  echo "<th>" . __('Match the rule ?', 'ocsinventoryng') . "</th>\n";
                  echo "<th>" . __('Destination entity') . "</th>\n";
                  echo "<th>" . __('Target location', 'ocsinventoryng') . "</th>\n";
               }
               if ($tolinked) {
                  echo "<th width='30%'>" . __('Item to link', 'ocsinventoryng') . "</th>";
               }
               echo "</tr>\n";

               $rule = new RuleImportEntityCollection();
               foreach ($hardware as $ID => $tab) {
                  //$comp = new Computer();
                  //$comp->fields["id"] = $tab["id"];
                  $data = array();

                  echo "<tr class='tab_bg_2'>";
                  //if (!$tolinked) {
                     echo "<td>";
                     echo "<input type='checkbox' name='toimport[" . $tab["id"] . "]' " .
                        ($check == "all" ? "checked" : "") . ">";
                     echo "</td>";
                  //}
                  if ($advanced && !$tolinked) {
                     $location = isset($tab["locations_id"]) ? $tab["locations_id"] : 0;
                     $data = $rule->processAllRules(array('ocsservers_id' => $serverId,
                                                          '_source'       => 'ocsinventoryng',
                                                          'locations_id'  => $location
                     ), array('locations_id' => $location), array('ocsid' => $tab["id"]));
                  }
                  echo "<td>" . $tab["name"] . "</td>\n";
                  echo "<td>" . $tab["manufacturer"] . "</td>";
                  echo "<td>" . $tab["model"] . "</td>";

                  echo "<td>";
                  $ssnblacklist = Blacklist::getSerialNumbers();
                  $ok = 1;
                  if (!in_array($tab['serial'], $ssnblacklist)) {
                     printf(__('%1$s : %2$s'), __('Serial number'), $tab["serial"]);
                  } else {
                     echo "<span class='red'>";
                     printf(__('%1$s : %2$s'), __('Blacklisted serial number', 'ocsinventoryng'), $tab["serial"]);
                     echo "</span>";
                     $ok = 0;
                  }
                  $uuidblacklist = Blacklist::getUUIDs();

                  if (!in_array($tab['UUID'], $uuidblacklist)) {
                     echo "<br>";
                     printf(__('%1$s : %2$s'), __('UUID'), $tab["UUID"]);
                  } else {
                     echo "<br>";
                     echo "<span class='red'>";
                     printf(__('%1$s : %2$s'), __('Blacklisted UUID', 'ocsinventoryng'), $tab["UUID"]);
                     echo "</span>";
                     $ok = 0;
                  }
                  if (isset($tab['NETWORKS'])) {
                     $networks = $tab['NETWORKS'];

                     $ipblacklist = Blacklist::getIPs();
                     $macblacklist = Blacklist::getMACs();

                     foreach ($networks as $opt) {

                        if (isset($opt['MACADDR'])) {
                           if (!in_array($opt['MACADDR'], $macblacklist)) {
                              echo "<br>";
                              printf(__('%1$s : %2$s'), __('MAC'), $opt['MACADDR']);
                           } else {
                              echo "<br>";
                              echo "<span class='red'>";
                              printf(__('%1$s : %2$s'), __('Blacklisted MAC', 'ocsinventoryng'), $opt['MACADDR']);
                              echo "</span>";
                              //$ok = 0;
                           }
                           if (!in_array($opt['IPADDRESS'], $ipblacklist)) {
                              echo " - ";
                              printf(__('%1$s : %2$s'), __('IP'), $opt['IPADDRESS']);
                           } else {
                              echo " - ";
                              echo "<span class='red'>";
                              printf(__('%1$s : %2$s'), __('Blacklisted IP', 'ocsinventoryng'), $opt['IPADDRESS']);
                              echo "</span>";
                              //$ok = 0;
                           }
                        }
                     }
                  }
                  echo "</td>";

                  echo "<td>" . Html::convDateTime($tab["date"]) . "</td>\n";
                  echo "<td>" . $tab["TAG"] . "</td>\n";
                  if ($advanced && !$tolinked) {
                     if (!isset($data['entities_id']) || $data['entities_id'] == -1) {
                        echo "<td class='center'><img src=\"" . $CFG_GLPI['root_doc'] . "/pics/redbutton.png\"></td>\n";
                        $data['entities_id'] = -1;
                     } else {
                        echo "<td  width='15%' class='center'>";
                        $tmprule = new RuleImportEntity();
                        if ($tmprule->can($data['_ruleid'], READ)) {
                           echo "<a href='" . $tmprule->getLinkURL() . "'>" . $tmprule->getName() . "</a>";
                        } else {
                           echo $tmprule->getName();
                        }
                        echo "</td>\n";
                     }
                     echo "<td width='20%'>";
                     $ent = "toimport_entities[" . $tab["id"] . "]";
                     Entity::dropdown(array('name'     => $ent,
                                            'value'    => $data['entities_id'],
                                            'comments' => 0));
                     echo "</td>\n";
                     echo "<td width='20%'>";
                     if (!isset($data['locations_id'])) {
                        $data['locations_id'] = 0;
                     }
                     $loc = "toimport_locations[" . $tab["id"] . "]";
                     Location::dropdown(array('name'     => $loc,
                                              'value'    => $data['locations_id'],
                                              'comments' => 0));
                     echo "</td>\n";
                  }

                  if ($tolinked) {
                     $ko = 0;
                     echo "<td  width='30%'>";
                     $tab['entities_id'] = $entity;
                     $rulelink = new RuleImportComputerCollection();
                     $rulelink_results = array();
                     $params = array('entities_id' => $entity,
                                     'plugin_ocsinventoryng_ocsservers_id'
                                                   => $serverId);
                     $rulelink_results = $rulelink->processAllRules(Toolbox::stripslashes_deep($tab), array(), $params);

                     //Look for the computer using automatic link criterias as defined in OCSNG configuration
                     $options = array('name' => "tolink[" . $tab["id"] . "]");
                     $show_dropdown = true;
                     //If the computer is not explicitly refused by a rule
                     if (!isset($rulelink_results['action'])
                        || $rulelink_results['action'] != self::LINK_RESULT_NO_IMPORT
                        && $ok
                     ) {

                        if (!empty($rulelink_results['found_computers'])) {
                           $options['value'] = $rulelink_results['found_computers'][0];
                           $options['entity'] = $entity;
                        }
                        $options['width'] = "100%";

                        if (isset($options['value']) && $options['value'] > 0) {

                           $query = "SELECT *
                                     FROM `glpi_plugin_ocsinventoryng_ocslinks`
                                     WHERE `computers_id` = '".$options['value']."' ";

                           $result = $DB->query($query);
                           if ($DB->numrows($result) > 0) {
                              $ko = 1;
                           }
                        }
                        $options['comments'] = false;
                        Computer::dropdown($options);
                        if ($ko > 0) {
                           echo "<div class='red'>";
                           echo __('Warning ! This computer is already linked with another OCS computer.', 'ocsinventoryng');
                           echo "</br>";
                           echo __('Check first that duplicates have been correctly managed in OCSNG', 'ocsinventoryng');
                           echo "</div>";
                        }
                     } else {
                        echo "<img src='" . $CFG_GLPI['root_doc'] . "/pics/redbutton.png'>";
                     }
                     echo "</td>";
                  }
                  echo "</tr>\n";
               }

               if (($tolinked && $caneditlink)
                  || (!$tolinked && $caneditimport)) {
                  echo "<tr class='tab_bg_1'><td colspan='" . (($advanced || $tolinked) ? 10 : 7) . "' class='center'>";
                  if ($tolinked) {
                     echo "<input class='submit' type='submit' name='import_ok' value=\"" .
                           _sx('button', 'Link', 'ocsinventoryng') . "\">";
                     echo "&nbsp;<input class='submit' type='submit' name='delete_link' value=\"" .
                                  _sx('button', 'Delete link', 'ocsinventoryng') . "\">";
                  } else {
                     echo "<input class='submit' type='submit' name='import_ok' value=\"" .
                     _sx('button', 'Import', 'ocsinventoryng') . "\">";
                  }
                  echo "<input type=hidden name='plugin_ocsinventoryng_ocsservers_id' " .
                           "value='$serverId'>";
                  echo "</td></tr>";
               }
               echo "</table>\n";
               Html::closeForm();

               if (!$tolinked && $caneditimport) {
                  self::checkBox($target);
               }

               Html::printPager($start, $numrows, $target, $parameters);
            } else {
               echo "<table class='tab_cadre_fixe'>";
               echo "<tr><th>" . $title . "</th></tr>\n";
               echo "<tr class='tab_bg_1'>";
               echo "<td class='center b'>" . __('No new computer to be imported', 'ocsinventoryng') .
                  "</td></tr>\n";
               echo "</table>";
               echo "<br><div class='center'>";
               echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.php'>";
               echo __('Back');
               echo "</a>";
               echo "</div>";
            }
            echo "</div>";
         } else {
            echo "<div class='center'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . $title . "</th></tr>\n";
            echo "<tr class='tab_bg_1'>";
            echo "<td class='center b'>" . __('No new computer to be imported', 'ocsinventoryng') .
               "</td></tr>\n";
            echo "</table></div>";
            echo "<br><div class='center'>";
            echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.php'>";
            echo __('Back');
            echo "</a>";
            echo "</div>";
         }
      } else {
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>" . $title . "</th></tr>\n";
         echo "<tr class='tab_bg_1'>";
         echo "<td class='center b'>" . __('No new computer to be imported', 'ocsinventoryng') .
            "</td></tr>\n";
         echo "</table></div>";
         echo "<br><div class='center'>";
         echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsng.php'>";
         echo __('Back');
         echo "</a>";
         echo "</div>";
      }
   }

   /**
    * @param $import_device
    * @return array
    */
   static function migrateImportDevice($import_device)
   {

      $new_import_device = array(self::IMPORT_TAG_078);
      if (count($import_device)) {
         foreach ($import_device as $key => $val) {
            $tmp = explode(self::FIELD_SEPARATOR, $val);

            if (isset($tmp[1])) { // Except for old IMPORT_TAG
               $tmp2 = explode(self::FIELD_SEPARATOR, $key);
               // Index Could be 1330395 (from glpi 0.72)
               // Index Could be 5$$$$$5$$$$$5$$$$$5$$$$$5$$$$$1330395 (glpi 0.78 bug)
               // So take the last part of the index
               $key2 = $tmp[0] . self::FIELD_SEPARATOR . array_pop($tmp2);
               $new_import_device[$key2] = $val;
            }
         }
      }
      //Add the new tag as the first occurence in the array
      //self::replaceOcsArray($computers_id, $new_import_device, "import_device");
      return $new_import_device;
   }

   /**
    * @param $computers_id
    * @param $computer_update
    * @return array
    */
   static function migrateComputerUpdates($computers_id, $computer_update)
   {

      $new_computer_update = array(self::IMPORT_TAG_078);

      $updates = array('ID'                 => 'id',
                       'FK_entities'        => 'entities_id',
                       'tech_num'           => 'users_id_tech',
                       'comments'           => 'comment',
                       'os'                 => 'operatingsystems_id',
                       'os_version'         => 'operatingsystemversions_id',
                       'os_sp'              => 'operatingsystemservicepacks_id',
                       'os_license_id'      => 'os_licenseid',
                       'auto_update'        => 'autoupdatesystems_id',
                       'location'           => 'locations_id',
                       'domain'             => 'domains_id',
                       'network'            => 'networks_id',
                       'model'              => 'computermodels_id',
                       'type'               => 'computertypes_id',
                       'tplname'            => 'template_name',
                       'FK_glpi_enterprise' => 'manufacturers_id',
                       'deleted'            => 'is_deleted',
                       'notes'              => 'notepad',
                       'ocs_import'         => 'is_dynamic',
                       'FK_users'           => 'users_id',
                       'FK_groups'          => 'groups_id',
                       'state'              => 'states_id');

      if (count($computer_update)) {
         foreach ($computer_update as $field) {
            if (isset($updates[$field])) {
               $new_computer_update[] = $updates[$field];
            } else {
               $new_computer_update[] = $field;
            }
         }
      }

      //Add the new tag as the first occurence in the array
      self::replaceOcsArray($computers_id, $new_computer_update, "computer_update", false);
      return $new_computer_update;
   }

   /*
     static function unlockItems($computers_id, $field){
     global $DB;

     if (!in_array($field, array("import_disk", "import_ip", "import_monitor", "import_peripheral",
     "import_printer", "import_software"))){
     return false;
     }

     $query = "SELECT `$field`
     FROM `glpi_plugin_ocsinventoryng_ocslinks`
     WHERE `computers_id` = '$computers_id'";

     if ($result = $DB->query($query)){
     if ($DB->numrows($result)){
     $tab         = importArrayFromDB($DB->result($result, 0, 0));
     $update_done = false;

     foreach ($tab as $key => $val){
     if ($val != "_version_070_"){
     switch ($field){
     case "import_monitor":
     case "import_printer":
     case "import_peripheral":
     $querySearchLocked = "SELECT `items_id`
     FROM `glpi_computers_items`
     WHERE `id` = '$key'";
     break;

     case "import_software":
     $querySearchLocked = "SELECT `id`
     FROM `glpi_computers_softwareversions`
     WHERE `id` = '$key'";
     break;

     case "import_ip":
     $querySearchLocked = "SELECT*
     FROM `glpi_networkports`
     LEFT JOIN `glpi_networknames`
     ON (`glpi_networkports`.`id` = `glpi_networknames`.`items_id`)
     LEFT JOIN `glpi_ipaddresses`
     ON (`glpi_ipaddresses`.`items_id` = `glpi_networknames`.`id`)
     WHERE `glpi_networkports`.`items_id` = '$computers_id'
     AND `glpi_networkports`.`itemtype` = 'Computer'
     AND `glpi_ipaddresses`.`name` = '$val'";
     break;

     case "import_disk":
     $querySearchLocked = "SELECT `id`
     FROM `glpi_computerdisks`
     WHERE `id` = '$key'";
     break;

     default:
     return;
     }

     $resultSearch = $DB->query($querySearchLocked);
     if ($DB->numrows($resultSearch) == 0){
     unset($tab[$key]);
     $update_done = true;
     }
     }
     }

     if ($update_done){
     $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
     SET `$field` = '" . exportArrayToDB($tab) . "'
     WHERE `computers_id` = '$computers_id'";
     $DB->query($query);
     }
     }
     }
     }
    */

   /**
    * Import the devices for a computer
    *
    * @param $devicetype integer : device type
    * @param $computers_id integer : glpi computer id.
    * @param $ocsComputer
    * @param $plugin_ocsinventoryng_ocsservers_id integer : ocs server id
    * @param $cfg_ocs array : ocs config
    * @param $import_device array : already imported devices
    * @param $import_ip array : already imported ip
    * @return Nothing .
    * @internal param int $ocsid : ocs computer id (ID).
    * @internal param bool $dohistory : log changes?
    *
    */
   static function updateDevices($devicetype, $computers_id, $ocsComputer, $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs, $import_device, $import_ip)
   {
      $prevalue = $devicetype . self::FIELD_SEPARATOR;
      $do_clean = false;
      $comp = new Computer();
      $comp->getFromDB($computers_id);
      $entities_id = $comp->fields['entities_id'];
      switch ($devicetype) {

         case "PluginOcsinventoryngItem_DeviceBiosdata":
            $CompDevice = new $devicetype();
            //Bios
            $do_clean = true;
            $bios["designation"] = $ocsComputer["BVERSION"];
            $bios["assettag"] = $ocsComputer["ASSETTAG"];
            $bios["entities_id"] = $entities_id;
            //$date = str_replace("/", "-", $ocsComputer['BIOS']["BDATE"]);
            //$date = date("Y-m-d", strtotime($date));
            $bios["date"] = $ocsComputer["BDATE"];
            $bios["manufacturers_id"] = Dropdown::importExternal('Manufacturer', self::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsComputer["SMANUFACTURER"]));
            if (!in_array(stripslashes($prevalue . $bios["designation"]), $import_device)) {

               $DeviceBios = new PluginOcsinventoryngDeviceBiosdata();
               $bios_id = $DeviceBios->import($bios);
               if ($bios_id) {
                  $CompDevice->add(array('items_id'                                 => $computers_id,
                                         'itemtype'                                 => 'Computer',
                                         'plugin_ocsinventoryng_devicebiosdatas_id' => $bios_id,
                                         'is_dynamic'                               => 1,
                                         'entities_id'                              => $entities_id), array(), $cfg_ocs['history_devices']);
               }
            } else {
               $tmp = array_search(stripslashes($prevalue . $bios["designation"]), $import_device);
               unset($import_device[$tmp]);
            }
            break;

         case "Item_DeviceMemory":
            //MEMORIES
            $CompDevice = new $devicetype();
            $do_clean = true;
            foreach ($ocsComputer as $line2) {
               $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
               if (isset($line2["CAPACITY"]) && $line2["CAPACITY"] != "No") {
                  $ram["designation"] = "";
                  if ($line2["TYPE"] != "Empty Slot" && $line2["TYPE"] != "Unknown") {
                     $ram["designation"] = $line2["TYPE"];
                  }
                  if ($line2["DESCRIPTION"]) {
                     if (!empty($ram["designation"])) {
                        $ram["designation"] .= " - ";
                     }
                     $ram["designation"] .= $line2["DESCRIPTION"];
                  }
                  if (!is_numeric($line2["CAPACITY"])) {
                     $line2["CAPACITY"] = 0;
                  }
                  if (is_numeric($line2["CAPACITY"])) {
                     $ram["size_default"] = $line2["CAPACITY"];
                  }
                  $ram["entities_id"] = $entities_id;
                  if (!in_array(stripslashes($prevalue . $ram["designation"]), $import_device)) {

                     if ($line2["SPEED"] != "Unknown" && is_numeric($line2["SPEED"])) {
                        $ram["frequence"] = $line2["SPEED"];
                     }
                     $ram["devicememorytypes_id"] = Dropdown::importExternal('DeviceMemoryType', $line2["TYPE"]);

                     $DeviceMemory = new DeviceMemory();
                     $ram_id = $DeviceMemory->import($ram);
                     if ($ram_id) {
                        $CompDevice->add(array('items_id'          => $computers_id,
                                               'itemtype'          => 'Computer',
                                               'entities_id'       => $entities_id,
                                               'devicememories_id' => $ram_id,
                                               'size'              => $line2["CAPACITY"],
                                               'is_dynamic'        => 1), array(), $cfg_ocs['history_devices']);
                     }
                  } else {
                     $tmp = array_search(stripslashes($prevalue . $ram["designation"]), $import_device);
                     list($type, $id) = explode(self::FIELD_SEPARATOR, $tmp);

                     $CompDevice->update(array('id'          => $id,
                                               'size'        => $line2["CAPACITY"]), $cfg_ocs['history_devices']);
                     unset($import_device[$tmp]);
                  }
               }
            }
            break;

         case "Item_DeviceHardDrive":
            $CompDevice = new $devicetype();
            //Disque Dur
            $do_clean = true;
            foreach ($ocsComputer as $line2) {
               $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
               if (!empty($line2["DISKSIZE"]) && preg_match("/disk|spare\sdrive/i", $line2["TYPE"])) {
                  if ($line2["NAME"]) {
                     $dd["designation"] = $line2["NAME"];
                  } else {
                     if ($line2["MODEL"]) {
                        $dd["designation"] = $line2["MODEL"];
                     } else {
                        $dd["designation"] = "Unknown";
                     }
                  }
                  if (!is_numeric($line2["DISKSIZE"])) {
                     $line2["DISKSIZE"] = 0;
                  }
                  $dd["entities_id"] = $entities_id;
                  if (!in_array(stripslashes($prevalue . $dd["designation"]), $import_device)) {
                     $dd["capacity_default"] = $line2["DISKSIZE"];
                     $DeviceHardDrive = new DeviceHardDrive();
                     $dd_id = $DeviceHardDrive->import($dd);
                     if ($dd_id) {
                        $CompDevice->add(array('items_id'            => $computers_id,
                                               'itemtype'            => 'Computer',
                                               'entities_id'         => $entities_id,
                                               'deviceharddrives_id' => $dd_id,
                                               'serial'              => $line2["SERIALNUMBER"],
                                               'capacity'            => $line2["DISKSIZE"],
                                               'is_dynamic'          => 1), array(), $cfg_ocs['history_devices']);
                     }
                  } else {
                     $tmp = array_search(stripslashes($prevalue . $dd["designation"]), $import_device);
                     list($type, $id) = explode(self::FIELD_SEPARATOR, $tmp);
                     $CompDevice->update(array('id'          => $id,
                                               'capacity'    => $line2["DISKSIZE"],
                                               'serial'      => $line2["SERIALNUMBER"]), $cfg_ocs['history_devices']);
                     unset($import_device[$tmp]);
                  }
               }
            }
            break;

         case "Item_DeviceDrive":
            $CompDevice = new $devicetype();
            //lecteurs
            $do_clean = true;
            foreach ($ocsComputer as $line2) {
               $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
               if (empty($line2["DISKSIZE"]) || !preg_match("/disk/i", $line2["TYPE"])) {
                  if ($line2["NAME"]) {
                     $stor["designation"] = $line2["NAME"];
                  } else {
                     if ($line2["MODEL"]) {
                        $stor["designation"] = $line2["MODEL"];
                     } else {
                        $stor["designation"] = "Unknown";
                     }
                  }
                  $stor["entities_id"] = $entities_id;
                  if (!in_array(stripslashes($prevalue . $stor["designation"]), $import_device)) {
                     $DeviceDrive = new DeviceDrive();
                     $stor_id = $DeviceDrive->import($stor);
                     if ($stor_id) {
                        $CompDevice->add(array('items_id'        => $computers_id,
                                               'itemtype'        => 'Computer',
                                               'entities_id'     => $entities_id,
                                               'devicedrives_id' => $stor_id,
                                               'is_dynamic'      => 1), array(), $cfg_ocs['history_devices']);
                     }
                  } else {
                     $tmp = array_search(stripslashes($prevalue . $stor["designation"]), $import_device);
                     unset($import_device[$tmp]);
                  }
               }
            }

            break;

         case "Item_DevicePci":
            if (isset($ocsComputer['MODEMS'])) {
               $CompDevice = new $devicetype();
               //Modems
               $do_clean = true;
               foreach ($ocsComputer['MODEMS'] as $line2) {
                  $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
                  $mdm["designation"] = $line2["NAME"];
                  $mdm["entities_id"] = $entities_id;
                  if (!in_array(stripslashes($prevalue . $mdm["designation"]), $import_device)) {
                     if (!empty($line2["DESCRIPTION"])) {
                        $mdm["comment"] = $line2["TYPE"] . "\r\n" . $line2["DESCRIPTION"];
                     }
                     $DevicePci = new DevicePci();
                     $mdm_id = $DevicePci->import($mdm);
                     if ($mdm_id) {
                        $CompDevice->add(array('items_id'      => $computers_id,
                                               'itemtype'      => 'Computer',
                                               'entities_id'   => $entities_id,
                                               'devicepcis_id' => $mdm_id,
                                               'is_dynamic'    => 1), array(), $cfg_ocs['history_devices']);
                     }
                  } else {
                     $tmp = array_search(stripslashes($prevalue . $mdm["designation"]), $import_device);
                     unset($import_device[$tmp]);
                  }
               }
            }
            //Ports
            if (isset($ocsComputer['PORTS'])) {
               $CompDevice = new $devicetype();
               foreach ($ocsComputer['PORTS'] as $line2) {
                  $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
                  $port["designation"] = "";
                  if ($line2["TYPE"] != "Other") {
                     $port["designation"] .= $line2["TYPE"];
                  }
                  if ($line2["NAME"] != "Not Specified") {
                     $port["designation"] .= " " . $line2["NAME"];
                  } else if ($line2["CAPTION"] != "None") {
                     $port["designation"] .= " " . $line2["CAPTION"];
                  }
                  $port["entities_id"] = $entities_id;
                  if (!empty($port["designation"])) {
                     if (!in_array(stripslashes($prevalue . $port["designation"]), $import_device)) {
                        if (!empty($line2["DESCRIPTION"]) && $line2["DESCRIPTION"] != "None") {
                           $port["comment"] = $line2["DESCRIPTION"];
                        }
                        $DevicePci = new DevicePci();
                        $port_id = $DevicePci->import($port);
                        if ($port_id) {
                           $CompDevice->add(array('items_id'      => $computers_id,
                                                  'itemtype'      => 'Computer',
                                                  'entities_id'   => $entities_id,
                                                  'devicepcis_id' => $port_id,
                                                  'is_dynamic'    => 1), array(), $cfg_ocs['history_devices']);
                        }
                     } else {
                        $tmp = array_search(stripslashes($prevalue . $port["designation"]), $import_device);
                        unset($import_device[$tmp]);
                     }
                  }
               }
            }
            //Slots
            if (isset($ocsComputer['SLOTS'])) {

               $CompDevice = new $devicetype();
               $do_clean = true;

               foreach ($ocsComputer['SLOTS'] as $line2) {
                  $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
                  if ($line2['NAME']) {
                     if (!$cfg_ocs["ocs_db_utf8"] && !Toolbox::seems_utf8($line2["NAME"])) {
                        $line2["NAME"] = Toolbox::encodeInUtf8($line2["NAME"]);
                     }
                     $pci["entities_id"] = $entities_id;
                     $pci["designation"] = $line2["NAME"];
                     if (!in_array(stripslashes($prevalue . $pci["designation"]), $import_device)) {
                        if (!empty($line2["DESCRIPTION"])) {
                           $pci["comment"] = $line2["DESCRIPTION"];
                        }
                        $DevicePci = new DevicePci();
                        $pci_id = $DevicePci->import($pci);
                        if ($pci_id) {
                           $CompDevice->add(array('items_id'      => $computers_id,
                                                  'itemtype'      => 'Computer',
                                                  'entities_id'   => $entities_id,
                                                  'devicepcis_id' => $pci_id,
                                                  'is_dynamic'    => 1), array(), $cfg_ocs['history_devices']);
                        }
                     } else {
                        $id = array_search(stripslashes($prevalue . $pci["designation"]), $import_device);
                        unset($import_device[$id]);
                     }
                  }
               }
            }
            break;
         case "Item_DeviceProcessor":
            $CompDevice = new $devicetype();
            //Processeurs:
            $do_clean = true;
            foreach ($ocsComputer as $line2) {
               $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
               $processor = array();
               $processor["designation"] = $line2["TYPE"];
               if (!is_numeric($line2["SPEED"])) {
                  $line2["SPEED"] = 0;
               }
               $processor["manufacturers_id"] = Dropdown::importExternal('Manufacturer', self::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $line2["MANUFACTURER"]));
               $processor["frequency_default"] = $line2["SPEED"];
               $processor["nbcores_default"] = $line2["CORES"];
               //$processor["nbthreads_default"] = $line2["LOGICAL_CPUS"];
               $processor["frequence"] = $line2["CURRENT_SPEED"];
               $processor["entities_id"] = $entities_id;
               if (!in_array(stripslashes($prevalue . $processor["designation"]), $import_device)) {
                  $DeviceProcessor = new DeviceProcessor();
                  $proc_id = $DeviceProcessor->import($processor);
                  if ($proc_id) {
                     $CompDevice->add(array('items_id'            => $computers_id,
                                            'itemtype'            => 'Computer',
                                            'entities_id'         => $entities_id,
                                            'deviceprocessors_id' => $proc_id,
                                            'frequency'           => $line2["SPEED"],
                                            'is_dynamic'          => 1), array(), $cfg_ocs['history_devices']);
                  }
               } else {
                  $tmp = array_search(stripslashes($prevalue . $processor["designation"]), $import_device);
                  list($type, $id) = explode(self::FIELD_SEPARATOR, $tmp);
                  $CompDevice->update(array('id'          => $id,
                                            'frequency'   => $line2["SPEED"]), $cfg_ocs['history_devices']);
                  unset($import_device[$tmp]);
               }
            }
            break;

         case "Item_DeviceNetworkCard":
            //Carte reseau
            PluginOcsinventoryngNetworkPort::importNetwork($cfg_ocs, $ocsComputer, $computers_id, $entities_id);
            break;

         case "Item_DeviceGraphicCard":
            $CompDevice = new $devicetype();
            //carte graphique
            $do_clean = true;
            foreach ($ocsComputer as $line2) {
               $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
               if ($line2['NAME']) {
                  $video["designation"] = $line2["NAME"];
                  $video["entities_id"] = $entities_id;
                  if (!is_numeric($line2["MEMORY"])) {
                     $line2["MEMORY"] = 0;
                  }
                  if (!in_array(stripslashes($prevalue . $video["designation"]), $import_device)) {
                     $video["memory_default"] = $line2["MEMORY"];
                     $DeviceGraphicCard = new DeviceGraphicCard();
                     $video_id = $DeviceGraphicCard->import($video);
                     if ($video_id) {
                        $CompDevice->add(array('items_id'              => $computers_id,
                                               'itemtype'              => 'Computer',
                                               'entities_id'           => $entities_id,
                                               'devicegraphiccards_id' => $video_id,
                                               'memory'                => $line2["MEMORY"],
                                               'is_dynamic'            => 1), array(), $cfg_ocs['history_devices']);
                     }
                  } else {
                     $tmp = array_search(stripslashes($prevalue . $video["designation"]), $import_device);
                     list($type, $id) = explode(self::FIELD_SEPARATOR, $tmp);
                     $CompDevice->update(array('id'          => $id,
                                               'memory'      => $line2["MEMORY"]), $cfg_ocs['history_devices']);
                     unset($import_device[$tmp]);
                  }
               }
            }
            break;

         case "Item_DeviceSoundCard":
            $CompDevice = new $devicetype();
            //carte son
            $do_clean = true;
            foreach ($ocsComputer as $line2) {
               $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
               if ($line2['NAME']) {
                  if (!$cfg_ocs["ocs_db_utf8"] && !Toolbox::seems_utf8($line2["NAME"])) {
                     $line2["NAME"] = Toolbox::encodeInUtf8($line2["NAME"]);
                  }
                  $snd["entities_id"] = $entities_id;
                  $snd["designation"] = $line2["NAME"];
                  if (!in_array(stripslashes($prevalue . $snd["designation"]), $import_device)) {
                     if (!empty($line2["DESCRIPTION"])) {
                        $snd["comment"] = $line2["DESCRIPTION"];
                     }
                     $DeviceSoundCard = new DeviceSoundCard();
                     $snd_id = $DeviceSoundCard->import($snd);
                     if ($snd_id) {
                        $CompDevice->add(array('items_id'            => $computers_id,
                                               'itemtype'            => 'Computer',
                                               'entities_id'         => $entities_id,
                                               'devicesoundcards_id' => $snd_id,
                                               'is_dynamic'          => 1), array(), $cfg_ocs['history_devices']);
                     }
                  } else {
                     $id = array_search(stripslashes($prevalue . $snd["designation"]), $import_device);
                     unset($import_device[$id]);
                  }
               }
            }
            break;
         case "Item_DeviceMotherboard":
            $CompDevice = new $devicetype();
            //Bios
            $do_clean = true;
            $mb["designation"] = $ocsComputer["MMODEL"];

            $mb["entities_id"] = $entities_id;
            $mb["manufacturers_id"] = Dropdown::importExternal('Manufacturer', self::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $ocsComputer["MMANUFACTURER"]));

            if (!in_array(stripslashes($prevalue . $mb["designation"]), $import_device)) {

               $DeviceMB = new DeviceMotherboard();
               $devicemotherboards_id = $DeviceMB->import($mb);
               if ($devicemotherboards_id) {
                  $serial = $ocsComputer["MSN"];
                  $CompDevice->add(array('items_id'              => $computers_id,
                                         'itemtype'              => 'Computer',
                                         'devicemotherboards_id' => $devicemotherboards_id,
                                         'is_dynamic'            => 1,
                                         'serial'                => $serial,
                                         'entities_id'           => $entities_id), array(), $cfg_ocs['history_devices']);
               }
            } else {
               $tmp = array_search(stripslashes($prevalue . $mb["designation"]), $import_device);
               unset($import_device[$tmp]);
            }
            break;
         case "Item_DeviceControl":
            //controllers
            $do_clean = true;
            $CompDevice = new $devicetype();
            foreach ($ocsComputer as $line2) {
               $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
               if ($line2['NAME']) {
                  if (!$cfg_ocs["ocs_db_utf8"] && !Toolbox::seems_utf8($line2["NAME"])) {
                     $line2["NAME"] = Toolbox::encodeInUtf8($line2["NAME"]);
                  }
                  $ctrl["entities_id"] = $entities_id;
                  $ctrl["designation"] = $line2["NAME"];
                  //TODO : OCS TYPE = IDE Controller
                  // GLPI : interface = IDE
                  //$ctrl["interfacetypes_id"] = $line2["TYPE"];
                  $ctrl["manufacturers_id"] = Dropdown::importExternal('Manufacturer', self::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $line2["MANUFACTURER"]));
                  if (!in_array(stripslashes($prevalue . $ctrl["designation"]), $import_device)) {
                     if (!empty($line2["DESCRIPTION"])) {
                        $ctrl["comment"] = $line2["DESCRIPTION"];
                     }
                     $DeviceControl = new DeviceControl();
                     $ctrl_id = $DeviceControl->import($ctrl);
                     if ($ctrl_id) {
                        $CompDevice->add(array('items_id'          => $computers_id,
                                               'itemtype'          => 'Computer',
                                               'entities_id'       => $entities_id,
                                               'devicecontrols_id' => $ctrl_id,
                                               'is_dynamic'        => 1), array(), $cfg_ocs['history_devices']);
                     }
                  } else {
                     $id = array_search(stripslashes($prevalue . $ctrl["designation"]), $import_device);
                     unset($import_device[$id]);
                  }
               }
            }
            break;
      }

      // Delete Unexisting Items not found in OCS
      if ($do_clean && count($import_device)) {
         foreach ($import_device as $key => $val) {
            if (!(strpos($key, $devicetype . '$$') === false)) {
               list($type, $id) = explode(self::FIELD_SEPARATOR, $key);
               $CompDevice = new $devicetype();
               $CompDevice->delete(array('id'          => $id,
                                         '_no_history' => !$cfg_ocs['history_devices']), true, $cfg_ocs['history_devices']);
            }
         }
      }

      //TODO Import IP
      if ($do_clean && count($import_ip) && $devicetype == "Item_DeviceNetworkCard") {
         foreach ($import_ip as $key => $val) {
            if ($key > 0) {
               $netport = new NetworkPort();
               $netport->delete(array('id'          => $key,
                                      '_no_history' => !$cfg_ocs['history_network']), 0, $cfg_ocs['history_network']);
            }
         }
      }
      //Alimentation
      //Carte mere
   }

   /**
    * Get a direct link to the computer in ocs console
    *
    * @param $plugin_ocsinventoryng_ocsservers_id the ID of the OCS server
    * @param $ocsid ID of the computer in OCS hardware table
    * @param $todisplay the link's label to display
    * @param $only_url
    *
    *
    * return the html link to the computer in ocs console
    *
    * @return string
    */
   static function getComputerLinkToOcsConsole($plugin_ocsinventoryng_ocsservers_id, $ocsid, $todisplay, $only_url = false)
   {

      $ocs_config = self::getConfig($plugin_ocsinventoryng_ocsservers_id);
      $url = '';

      if ($ocs_config["ocs_url"] != '') {
         //Display direct link to the computer in ocsreports
         $url = $ocs_config["ocs_url"];
         if (!preg_match("/\/$/i", $ocs_config["ocs_url"])) {
            $url .= '/';
         }
         if ($ocs_config['ocs_version'] > self::OCS2_VERSION_LIMIT) {
            $url = $url . "index.php?function=computer&amp;head=1&amp;systemid=$ocsid";
         } else {
            $url = $url . "machine.php?systemid=$ocsid";
         }

         if ($only_url) {
            return $url;
         }
         return "<a class='vsubmit' target='_blank' href='$url'>" . $todisplay . "</a>";
      }
      return $url;
   }

   /**
    * Get IP address from OCS hardware table
    *
    * @param the $plugin_ocsinventoryng_ocsservers_id
    * @param ID $computers_id
    * return the ip address or ''
    * @internal param the $plugin_ocsinventoryng_ocsservers_id ID of the OCS server
    * @internal param ID $computers_id of the computer in OCS hardware table
    *
    * @return string
    */
   static function getGeneralIpAddress($plugin_ocsinventoryng_ocsservers_id, $computers_id)
   {
      self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $options = array(
         'DISPLAY' => array(
            'CHECKSUM' => PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE
         )
      );
      $computer = $ocsClient->getComputer($computers_id, $options);
      $ipaddress = $computer["HARDWARE"]["IPADDR"];
      if ($ipaddress) {
         return $ipaddress;
      }


      return '';
   }

   /**
    * @param $ocs_config
    * @param $itemtype
    * @return mixed
    */
   static function getDevicesManagementMode($ocs_config, $itemtype)
   {

      switch ($itemtype) {
         case 'Monitor':
            return $ocs_config["import_monitor"];

         case 'Printer':
            return $ocs_config["import_printer"];

         case 'Peripheral':
            return $ocs_config["import_periph"];
      }
   }

   /**
    * @param $entity
    * @return bool|resource
    */
   static function setEntityLock($entity)
   {

      $fp = fopen(GLPI_LOCK_DIR . "/lock_entity_" . $entity, "w+");
      if (flock($fp, LOCK_EX)) {
         return $fp;
      }
      fclose($fp);
      return false;
   }

   /**
    * @param $entity
    * @param $fp
    */
   static function removeEntityLock($entity, $fp)
   {

      flock($fp, LOCK_UN);
      fclose($fp);

      //Test if the lock file still exists before removing it
      // (sometimes another thread already removed the file)
      clearstatcache();
      if (file_exists(GLPI_LOCK_DIR . "/lock_entity_" . $entity)) {
         @unlink(GLPI_LOCK_DIR . "/lock_entity_" . $entity);
      }
   }

   /**
    * @param $ID
    * @param $templateid
    * @return string
    */
   static function getFormServerAction($ID, $templateid)
   {

      $action = "";
      if (!isset($withtemplate) || $withtemplate == "") {
         $action = "edit_server";
      } else if (isset($withtemplate) && $withtemplate == 1) {
         if ($ID == -1 && $templateid == '') {
            $action = "add_template";
         } else {
            $action = "update_template";
         }
      } else if (isset($withtemplate) && $withtemplate == 2) {
         if ($templateid == '') {
            $action = "edit_server";
         } else if ($ID == -1) {
            $action = "add_server_with_template";
         } else {
            $action = "update_server_with_template";
         }
      }

      return $action;
   }

   /**
    * @param $ID
    * @param $table
    * @return array
    */
   static function getColumnListFromAccountInfoTable($ID, $table)
   {

      $listColumn = array("0" => __('No import'));
      if ($ID != -1) {
         if (self::checkOCSconnection($ID)) {
            $ocsClient = self::getDBocs($ID);
            $AccountInfoColumns = $ocsClient->getAccountInfoColumns($table);
            if (count($AccountInfoColumns) > 0) {
               foreach ($AccountInfoColumns as $id => $name) {
                  $listColumn[$id] = $name;
               }
            }
         }
      }
      return $listColumn;
   }

   /**
    * Check if OCS connection is still valid
    * If not, then establish a new connection on the good server
    *
    * @param $serverId
    * @return bool
    * @internal param the $plugin_ocsinventoryng_ocsservers_id ocs server id
    *
    */
   static function checkOCSconnection($serverId)
   {
      return self::getDBocs($serverId)->checkConnection();
   }

   /**
    * Get a connection to the OCS server
    *
    * @param $serverId the ocs server id
    *
    * @return PluginOcsinventoryngOcsClient the ocs client (database or soap)
    */
   static function getDBocs($serverId)
   {
      $config = self::getConfig($serverId);

      if ($config['conn_type'] == self::CONN_TYPE_DB) {
         return new PluginOcsinventoryngOcsDbClient(
            $serverId, $config['ocs_db_host'], $config['ocs_db_user'], $config['ocs_db_passwd'], $config['ocs_db_name']
         );
      } else {
         return new PluginOcsinventoryngOcsSoapClient(
            $serverId, $config['ocs_db_host'], $config['ocs_db_user'], $config['ocs_db_passwd']
         );
      }
   }

   /**
    * Choose an ocs server
    *
    * @return nothing.
    * */
   static function showFormServerChoice()
   {
      global $DB, $CFG_GLPI;

      $query = "SELECT*
                FROM `glpi_plugin_ocsinventoryng_ocsservers`
                WHERE `is_active`='1'
                ORDER BY `name` ASC";
      $result = $DB->query($query);

      if ($DB->numrows($result) > 1) {
         echo "<form action=\"" . $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ocsng.php\" method='post'>";
         echo "<div class='center'><table class='tab_cadre'>";
         echo "<tr class='tab_bg_2'>";
         echo "<th colspan='2'>" . __('Choice of an OCSNG server', 'ocsinventoryng') . "</th></tr>\n";

         echo "<tr class='tab_bg_2'><td class='center'>" . __('Name') . "</td>";
         echo "<td class='center'>";
         echo "<select name='plugin_ocsinventoryng_ocsservers_id'>";
         while ($ocs = $DB->fetch_array($result)) {
            echo "<option value='" . $ocs["id"] . "'>" . $ocs["name"] . "</option>";
         }
         echo "</select></td></tr>\n";

         echo "<tr class='tab_bg_2'><td class='center' colspan=2>";
         echo "<input class='submit' type='submit' name='ocs_showservers' value=\"" .
            _sx('button', 'Post') . "\"></td></tr>";
         echo "</table></div>\n";
         Html::closeForm();
      } else if ($DB->numrows($result) == 1) {
         $ocs = $DB->fetch_array($result);
         Html::redirect($CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ocsng.php?plugin_ocsinventoryng_ocsservers_id=" . $ocs["id"]);
      } else {
         echo "<div class='center'><table class='tab_cadre'>";
         echo "<tr class='tab_bg_2'>";
         echo "<th colspan='2'>" . __('Choice of an OCSNG server', 'ocsinventoryng') . "</th></tr>\n";

         echo "<tr class='tab_bg_2'>";
         echo "<td class='center' colspan=2>" . __('No OCSNG server defined', 'ocsinventoryng') .
            "</td></tr>";
         echo "</table></div>\n";
      }
   }


   /**
    * Delete old devices settings
    *
    * @param $glpi_computers_id integer : glpi computer id.
    * @param $itemtype integer : device type identifier.
    *
    * @param $cfg_ocs
    * @return nothing .
    */
   static function resetDevices($glpi_computers_id, $itemtype, $cfg_ocs)
   {
      global $DB;

      if ($cfg_ocs['history_devices']) {

         $linktable = getTableForItemType('Item_' . $itemtype);
         if ($itemtype == "PluginOcsinventoryngDeviceBiosdata") {
            $linktable = getTableForItemType('PluginOcsinventoryngItem_DeviceBiosdata');
         }

         $query = "DELETE
                            FROM `" . $linktable . "`
                            WHERE `items_id` = '" . $glpi_computers_id . "'
                            AND `itemtype` = 'Computer'
                            AND `is_dynamic` = '1'";
         $DB->query($query);
      }
//            CANNOT USE BEFORE 9.1.2 - for _no_history problem
//      $item = new $itemtype();
//      $item->deleteByCriteria(array('computers_id' => $glpi_computers_id,
//         'itemtype' => 'Computer',
//         'is_dynamic' => 1));
   }

   /**
    * Delete old dropdown value
    *
    * Delete all old dropdown value of a computer.
    *
    * @param $glpi_computers_id integer : glpi computer id.
    * @param $field string : string of the computer table
    * @param $table string : dropdown table name
    *
    * @return nothing.
    * */
   static function resetDropdown($glpi_computers_id, $field, $table)
   {
      global $DB;

      $query = "SELECT `$field` AS val
                FROM `glpi_computers`
                WHERE `id` = '$glpi_computers_id'";
      $result = $DB->query($query);

      if ($DB->numrows($result) == 1) {
         $value = $DB->result($result, 0, "val");
         $query = "SELECT COUNT(*) AS cpt
                   FROM `glpi_computers`
                   WHERE `$field` = '$value'";
         $result = $DB->query($query);

         if ($DB->result($result, 0, "cpt") == 1) {
            $query2 = "DELETE
                       FROM `$table`
                       WHERE `id` = '$value'";
            $DB->query($query2);
         }
      }
   }

   /**
    * Delete old registry entries
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @return nothing.
    * */
   static function resetRegistry($glpi_computers_id)
   {
      global $DB;

      $table = getTableForItemType('PluginOcsinventoryngRegistryKey');
      $query = "DELETE
                  FROM `" . $table . "`
                     WHERE `computers_id` = '" . $glpi_computers_id . "' ";
      $DB->query($query);

      //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
//      $registry = new PluginOcsinventoryngRegistryKey();
//      $registry->deleteByCriteria(array('computers_id' => $glpi_computers_id), 1);
   }

   /**
    * Delete old antivirus entries
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @param $cfg_ocs
    * @return nothing .
    */
   static function resetAntivirus($glpi_computers_id, $cfg_ocs)
   {
      global $DB;
//      TODO add history for antivirus
//      if ($cfg_ocs['history_antivirus']) {
      $table = getTableForItemType('ComputerAntivirus');
      $query = "DELETE
                            FROM `" . $table . "`
                            WHERE `computers_id` = '" . $glpi_computers_id . "'
                            AND `is_dynamic` = '1'";
      $DB->query($query);
//      }
      //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
//      $av = new ComputerAntivirus();
//      $av->deleteByCriteria(array('computers_id' => $glpi_computers_id,
//         'is_dynamic' => 1));
   }

   /**
    * Delete old Winupdatestate entries
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @param $cfg_ocs
    * @return nothing .
    */
   static function resetWinupdatestate($glpi_computers_id, $cfg_ocs)
   {
      global $DB;
//      TODO add history for antivirus
//      if ($cfg_ocs['history_antivirus']) {
      $table = getTableForItemType('PluginOcsinventoryngWinupdate');
      $query = "DELETE
                            FROM `" . $table . "`
                            WHERE `computers_id` = '" . $glpi_computers_id . "'";
      $DB->query($query);
//      }
      //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
//      $av = new ComputerAntivirus();
//      $av->deleteByCriteria(array('computers_id' => $glpi_computers_id,
//         'is_dynamic' => 1));
   }

   /**
    * Delete old Proxysetting entries
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @param $cfg_ocs
    * @return nothing .
    */
   static function resetProxysetting($glpi_computers_id, $cfg_ocs)
   {
      global $DB;
//      TODO add history for antivirus
//      if ($cfg_ocs['history_antivirus']) {
      $table = getTableForItemType('PluginOcsinventoryngProxysetting');
      $query = "DELETE
                            FROM `" . $table . "`
                            WHERE `computers_id` = '" . $glpi_computers_id . "'";
      $DB->query($query);
//      }
      //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
//      $av = new ComputerAntivirus();
//      $av->deleteByCriteria(array('computers_id' => $glpi_computers_id,
//         'is_dynamic' => 1));
   }

   /**
    * Delete old Winuser entries
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @param $cfg_ocs
    * @return nothing .
    */
   static function resetWinuser($glpi_computers_id, $cfg_ocs)
   {
      global $DB;
//      TODO add history for antivirus
//      if ($cfg_ocs['history_antivirus']) {
      $table = getTableForItemType('PluginOcsinventoryngWinuser');
      $query = "DELETE
                            FROM `" . $table . "`
                            WHERE `computers_id` = '" . $glpi_computers_id . "'";
      $DB->query($query);
//      }
      //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
//      $av = new ComputerAntivirus();
//      $av->deleteByCriteria(array('computers_id' => $glpi_computers_id,
//         'is_dynamic' => 1));
   }

   /**
    * Delete old Teamviewer entries
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @param $cfg_ocs
    * @return nothing .
    */
   static function resetTeamviewer($glpi_computers_id, $cfg_ocs)
   {
      global $DB;
//      TODO add history for antivirus
//      if ($cfg_ocs['history_antivirus']) {
      $table = getTableForItemType('PluginOcsinventoryngTeamviewer');
      $query = "DELETE
                            FROM `" . $table . "`
                            WHERE `computers_id` = '" . $glpi_computers_id . "'";
      $DB->query($query);
//      }
      //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
//      $av = new ComputerAntivirus();
//      $av->deleteByCriteria(array('computers_id' => $glpi_computers_id,
//         'is_dynamic' => 1));
   }

   /**
    * Delete old licenses software entries
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @return nothing.
    * */
   static function resetOfficePack($glpi_computers_id) {
      global $DB;

      $query  = "SELECT *
                FROM `glpi_computers_softwarelicenses`
                WHERE `computers_id` = '$glpi_computers_id' AND `is_dynamic`";

      $result = $DB->query($query);

      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetch_assoc($result)) {
            $query2  = "SELECT COUNT(*)
                       FROM `glpi_computers_softwarelicenses`
                       WHERE `softwarelicenses_id` = '" . $data['softwarelicenses_id'] . "'";
            $result2 = $DB->query($query2);

            if ($DB->result($result2, 0, 0) == 1) {
               $license    = new SoftwareLicense();
               $license->getFromDB($data['softwarelicenses_id']);
               $query3  = "SELECT COUNT(*)
                          FROM `glpi_softwarelicenses`
                          WHERE `softwares_id`='" . $license->fields['softwares_id'] . "'";
               $result3 = $DB->query($query3);

               if ($DB->result($result3, 0, 0) == 1) {
                  $soft = new Software();
                  $soft->delete(array('id' => $license->fields['softwares_id']), 1);
               }
               $license->delete(array("id" => $data['softwarelicenses_id']));
            }
         }

         $computer_softwarelicenses = new Computer_SoftwareVersion();
         $computer_softwarelicenses->deleteByCriteria(array('computers_id' => $glpi_computers_id));
      }
   }

   /**
    * Delete all old printers of a computer.
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @param $cfg_ocs
    * @return nothing .
    */
   static function resetPrinters($glpi_computers_id, $cfg_ocs)
   {
      global $DB;

      $query = "SELECT *
                FROM `glpi_computers_items`
                WHERE `computers_id` = '$glpi_computers_id'
                      AND `itemtype` = 'Printer'
                      AND `is_dynamic`";
      $result = $DB->query($query);

      if ($DB->numrows($result) > 0) {
         $conn = new Computer_Item();

         while ($data = $DB->fetch_assoc($result)) {
            $conn->delete(array('id' => $data['id'], '_no_history' => !$cfg_ocs['history_printer']), true, $cfg_ocs['history_printer']);

            $query2 = "SELECT COUNT(*)
                       FROM `glpi_computers_items`
                       WHERE `items_id` = '" . $data['items_id'] . "'
                             AND `itemtype` = 'Printer'";
            $result2 = $DB->query($query2);

            $printer = new Printer();
            if ($DB->result($result2, 0, 0) == 1) {
               $printer->delete(array('id' => $data['items_id'], '_no_history' => !$cfg_ocs['history_printer']), true, $cfg_ocs['history_printer']);
            }
         }
      }
   }

   /**
    * Delete all old monitors of a computer.
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @param $cfg_ocs
    * @return nothing .
    */
   static function resetMonitors($glpi_computers_id, $cfg_ocs)
   {
      global $DB;

      $query = "SELECT *
                FROM `glpi_computers_items`
                WHERE `computers_id` = '$glpi_computers_id'
                      AND `itemtype` = 'Monitor'
                      AND `is_dynamic`";
      $result = $DB->query($query);

      $mon = new Monitor();
      if ($DB->numrows($result) > 0) {
         $conn = new Computer_Item();

         while ($data = $DB->fetch_assoc($result)) {

            $conn->delete(array('id' => $data['id'], '_no_history' => !$cfg_ocs['history_monitor']), true, $cfg_ocs['history_monitor']);

            $query2 = "SELECT COUNT(*)
                       FROM `glpi_computers_items`
                       WHERE `items_id` = '" . $data['items_id'] . "'
                             AND `itemtype` = 'Monitor'";
            $result2 = $DB->query($query2);

            if ($DB->result($result2, 0, 0) == 1) {
               $mon->delete(array('id' => $data['items_id'], '_no_history' => !$cfg_ocs['history_monitor']), true, $cfg_ocs['history_monitor']);
            }
         }
      }
   }

   /**
    * Delete all old periphs for a computer.
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @param $cfg_ocs
    * @return nothing .
    */
   static function resetPeripherals($glpi_computers_id, $cfg_ocs)
   {
      global $DB;

      $query = "SELECT *
                FROM `glpi_computers_items`
                WHERE `computers_id` = '$glpi_computers_id'
                      AND `itemtype` = 'Peripheral'
                      AND `is_dynamic`";
      $result = $DB->query($query);

      $per = new Peripheral();
      if ($DB->numrows($result) > 0) {
         $conn = new Computer_Item();
         while ($data = $DB->fetch_assoc($result)) {
            $conn->delete(array('id' => $data['id'], '_no_history' => !$cfg_ocs['history_peripheral']), true, $cfg_ocs['history_peripheral']);

            $query2 = "SELECT COUNT(*)
                       FROM `glpi_computers_items`
                       WHERE `items_id` = '" . $data['items_id'] . "'
                             AND `itemtype` = 'Peripheral'";
            $result2 = $DB->query($query2);

            if ($DB->result($result2, 0, 0) == 1) {
               $per->delete(array('id' => $data['items_id'], '_no_history' => !$cfg_ocs['history_peripheral']), true, $cfg_ocs['history_peripheral']);
            }
         }
      }
   }

   /**
    * Delete all old softwares of a computer.
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @param $cfg_ocs
    * @return nothing .
    */
   static function resetSoftwares($glpi_computers_id, $cfg_ocs)
   {
      global $DB;

      $query = "SELECT *
                FROM `glpi_computers_softwareversions`
                WHERE `computers_id` = '$glpi_computers_id'
                     AND `is_dynamic`";
      $result = $DB->query($query);

      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetch_assoc($result)) {
            $query2 = "SELECT COUNT(*)
                       FROM `glpi_computers_softwareversions`
                       WHERE `softwareversions_id` = '" . $data['softwareversions_id'] . "'";
            $result2 = $DB->query($query2);

            if ($DB->result($result2, 0, 0) == 1) {
               $vers = new SoftwareVersion();
               $vers->getFromDB($data['softwareversions_id']);
               $query3 = "SELECT COUNT(*)
                          FROM `glpi_softwareversions`
                          WHERE `softwares_id`='" . $vers->fields['softwares_id'] . "'";
               $result3 = $DB->query($query3);

               if ($DB->result($result3, 0, 0) == 1) {
                  $soft = new Software();
                  $soft->delete(array('id' => $vers->fields['softwares_id'], '_no_history' => !$cfg_ocs['history_software']), true, $cfg_ocs['history_software']);
               }
               $vers->delete(array("id" => $data['softwareversions_id'], '_no_history' => !$cfg_ocs['history_software']), true, $cfg_ocs['history_software']);
            }
         }

         if ($cfg_ocs['history_software']) {
            $table = getTableForItemType('Computer_SoftwareVersion');
            $query = "DELETE
                            FROM `" . $table . "`
                            WHERE `computers_id` = '" . $glpi_computers_id . "'
                            AND `is_dynamic` = '1'";
            $DB->query($query);
         }
         //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
//         $csv = new Computer_SoftwareVersion();
//         $csv->deleteByCriteria(array('computers_id' => $glpi_computers_id,
//            'is_dynamic' => 1));
      }
   }

   /**
    * Delete all old disks of a computer.
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @param $cfg_ocs
    * @return nothing .
    */
   static function resetDisks($glpi_computers_id, $cfg_ocs)
   {
      global $DB;

      if ($cfg_ocs['history_drives']) {
         $table = getTableForItemType('ComputerDisk');
         $query = "DELETE
                            FROM `" . $table . "`
                            WHERE `computers_id` = '" . $glpi_computers_id . "'
                            AND `is_dynamic` = '1'";
         $DB->query($query);
      }
      //            CANNOT USE BEFORE 9.1.2 - for _no_history problem
//      $dd = new ComputerDisk();
//      $dd->deleteByCriteria(array('computers_id' => $glpi_computers_id,
//         'is_dynamic' => 1));
   }

   /**
    * Delete old Services entries
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @param $cfg_ocs
    *
    * @return nothing .
    */
   static function resetService($glpi_computers_id, $cfg_ocs) {

      $service = new PluginOcsinventoryngService();
      $service->deleteByCriteria(array('computers_id' => $glpi_computers_id), 1);

   }

   /**
    * Delete old Runningprocess entries
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @param $cfg_ocs
    *
    * @return nothing .
    */
   static function resetRunningProcess($glpi_computers_id, $cfg_ocs) {

      $runningprocess = new PluginOcsinventoryngRunningprocess();
      $runningprocess->deleteByCriteria(array('computers_id' => $glpi_computers_id), 1);

   }

   /**
    * Update config of a new version
    *
    * This function create a new software in GLPI with some general datas.
    *
    * @param $software : id of a software.
    * @param $version : version of the software
    *
    * @param $comments
    * @param $dohistory
    * return int : inserted version id.
    */
   static function updateVersion($software, $version, $comments, $dohistory)
   {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_softwareversions`
                WHERE `softwares_id` = '$software'
                      AND `name` = '$version'";
      $result = $DB->query($query);

      if ($DB->numrows($result) > 0) {
         $data = $DB->fetch_array($result);
         $input["id"] = $data["id"];
         $input["comment"] = $comments;
         $vers = new SoftwareVersion();
         $vers->update($input, $dohistory);
         return $data["id"];
      }

      return;
   }

   /**
    * Import config of a new version
    *
    * This function create a new software in GLPI with some general datas.
    *
    * @param $cfg_ocs
    * @param $software : id of a software.
    * @param $version : version of the software
    *
    * @param $comments
    *
    * @return int : inserted version id.
    */
   static function importVersion($cfg_ocs, $software, $version, $comments)
   {
      global $DB;

      $isNewVers = 0;
      $query = "SELECT `id`
                FROM `glpi_softwareversions`
                WHERE `softwares_id` = '$software'
                      AND `name` = '$version'";
      $result = $DB->query($query);

      if ($DB->numrows($result) > 0) {
         $data = $DB->fetch_array($result);
         $isNewVers = $data["id"];
      }

      if (!$isNewVers) {
         $vers = new SoftwareVersion();
         // TODO : define a default state ? Need a new option in config
         // Use $cfg_ocs["states_id_default"] or create a specific one?
         $input["softwares_id"] = $software;
         $input["name"] = $version;
         $input["comment"] = $comments;
         $isNewVers = $vers->add($input, array(), $cfg_ocs['history_software']);
      }

      return ($isNewVers);
   }

   /**
    *
    * Synchronize virtual machines
    *
    * @param unknown $computers_id
    * @param $ocsComputer
    * @param unknown $ocsservers_id
    * @param unknown $cfg_ocs
    * @return bool
    * @internal param unknown $ocsid
    * @internal param unknown $dohistory
    */
   static function updateVirtualMachines($computers_id, $ocsComputer, $ocsservers_id, $cfg_ocs)
   {
      global $DB;
      $already_processed = array();

      $virtualmachine = new ComputerVirtualMachine();
      $ocsVirtualmachines = $ocsComputer;

      if (count($ocsVirtualmachines) > 0) {
         foreach ($ocsVirtualmachines as $ocsVirtualmachine) {
            $ocsVirtualmachine = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($ocsVirtualmachine));
            $vm = array();
            $vm['name'] = $ocsVirtualmachine['NAME'];
            $vm['vcpu'] = $ocsVirtualmachine['VCPU'];
            $vm['ram'] = $ocsVirtualmachine['MEMORY'];
            $vm['uuid'] = $ocsVirtualmachine['UUID'];
            $vm['computers_id'] = $computers_id;
            $vm['is_dynamic'] = 1;

            $vm['virtualmachinestates_id'] = Dropdown::importExternal('VirtualMachineState', $ocsVirtualmachine['STATUS']);
            $vm['virtualmachinetypes_id'] = Dropdown::importExternal('VirtualMachineType', $ocsVirtualmachine['VMTYPE']);
            $vm['virtualmachinesystems_id'] = Dropdown::importExternal('VirtualMachineType', $ocsVirtualmachine['SUBSYSTEM']);

            $query = "SELECT `id`
                         FROM `glpi_computervirtualmachines`
                         WHERE `computers_id`='$computers_id'
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
         }
      }
      // Delete Unexisting Items not found in OCS
      //Look for all ununsed virtual machines
      $query = "SELECT `id`
                FROM `glpi_computervirtualmachines`
                WHERE `computers_id`='$computers_id'
                   AND `is_dynamic`";
      if (!empty($already_processed)) {
         $query .= "AND `id` NOT IN (" . implode(',', $already_processed) . ")";
      }
      foreach ($DB->request($query) as $data) {
         //Delete all connexions
         $virtualmachine->delete(array('id'             => $data['id'],
                                       '_ocsservers_id' => $ocsservers_id, '_no_history' => !$cfg_ocs['history_vm']), true, $cfg_ocs['history_vm']);
      }
   }

   /**
    *
    * Update config of a new software office
    *
    * This function create a officepack in GLPI with some general data.
    *
    * @param type $computers_id
    * @param $entity
    * @param type $ocsComputer
    * @param type $cfg_ocs
    *
    * @internal param \type $ocsservers_id
    */
   static function updateOfficePack($computers_id, $softwares_id, $softwares_name, $softwareversions_id, $entity, $ocsOfficePacks, $cfg_ocs, &$imported_licences){
      global $DB;

      if (count($ocsOfficePacks) > 0) {
         foreach ($ocsOfficePacks as $ocsOfficePack) {
            $ocsOfficePack = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($ocsOfficePack));

            if ($ocsOfficePack['PRODUCT'] == $softwares_name) {

               $soft_l['softwares_id']            = $softwares_id;
               $soft_l['softwareversions_id_use'] = $softwareversions_id;
               $soft_l['entities_id']             = $entity;
               $soft_l['name']                    = $ocsOfficePack['OFFICEKEY'];
               $soft_l['serial']                  = $ocsOfficePack['OFFICEKEY'];
               $soft_l['comment']                 = $ocsOfficePack['NOTE'];

               $id = array_search($softwareversions_id, $imported_licences);

               $software_licenses         = new SoftwareLicense();
               $computer_softwarelicenses = new Computer_SoftwareLicense();
               if ($id) {
                  //-------------------------------------------------------------------------//
                  //---- The software exists in this license for this computer --------------//
                  //---------------------------- Update comments ----------------------------//
                  //---------------------------------------------------- --------------------//
                  if (!empty($ocsOfficePack['OFFICEKEY'])) {
                     if ($software_licenses->getFromDBByQuery("WHERE `softwares_id` = " . $softwares_id . "
                                                            AND `serial` = '" . $ocsOfficePack['OFFICEKEY'] . "'
                                                            AND `softwareversions_id_use` = " . $softwareversions_id)
                     ) {

                        $software_licenses->update(array('id'      => $software_licenses->getID(),
                                                         'comment' => $ocsOfficePack['NOTE']));
                        if (!$computer_softwarelicenses->getFromDBByQuery("WHERE `computers_id` = " . $computers_id . "
                              AND `softwarelicenses_id` = " . $software_licenses->getID())
                        ) {

                           $computer_soft_l['computers_id']        = $computers_id;
                           $computer_soft_l['softwarelicenses_id'] = $software_licenses->getID();
                           $computer_soft_l['is_dynamic']          = -1;
                           $computer_softwarelicenses->add($computer_soft_l);
                           //Update for validity
                           $software_licenses->update(array('id'       => $software_licenses->getID(),
                                                            'is_valid' => 1));
                        }
                     }
                  }

                  unset($imported_licences[$id]);
               } else {
                  //------------------------------------------------------------------------//
                  //---- The software doesn't exists in this license for this computer -----//
                  //------------------------------------------------------------------------//
                  if (!empty($ocsOfficePack['OFFICEKEY'])) {
                     if ($software_licenses->getFromDBByQuery("WHERE `softwares_id` = " . $softwares_id . "
                                                           AND `serial` = '" . $ocsOfficePack['OFFICEKEY'] . "'
                                                           AND `softwareversions_id_use` = " . $softwareversions_id)
                     ) {
                        $id_software_licenses = $software_licenses->getID();
                     } else {
                        $software_licenses->fields['softwares_id'] = $softwares_id;
                        $id_software_licenses                      = $software_licenses->add($soft_l, array(), $cfg_ocs['history_software']);
                     }

                     if ($id_software_licenses) {
                        $computer_soft_l['computers_id']        = $computers_id;
                        $computer_soft_l['softwarelicenses_id'] = $id_software_licenses;
                        $computer_soft_l['is_dynamic']          = 1;
                        $computer_soft_l['number']              = -1;

                        if(!$computer_softwarelicenses->getFromDBByQuery("WHERE `computers_id` = $computers_id
                                                                         AND `softwarelicenses_id` = $id_software_licenses")) {
                           $computer_softwarelicenses->add($computer_soft_l);
                        }
                        //Update for validity
                        $software_licenses->update(array('id'       => $id_software_licenses,
                                                         'is_valid' => 1));
                     }
                  }
               }
            }
         }
      }

   }

   /**
    * Update config of a new Disk
    *
    * This function create a new disk in GLPI with some general datas.
    *
    * @param $computers_id integer : glpi computer id.
    * @param $ocsComputer
    * @param $ocsservers_id integer : ocs server id
    * @param $cfg_ocs array : ocs config
    * @return Nothing .
    * @internal param int $ocsid : ocs computer id (ID).
    */
   static function updateDisk($computers_id, $ocsComputer, $ocsservers_id, $cfg_ocs)
   {
      global $DB;

      $already_processed = array();
      $logical_drives = $ocsComputer;

      $d = new ComputerDisk();
      if (count($logical_drives) > 0) {
         foreach ($logical_drives as $logical_drive) {
            $logical_drive = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($logical_drive));

            // Only not empty disk
            if ($logical_drive['TOTAL'] > 0) {
               $disk = array();
               $disk['computers_id'] = $computers_id;
               $disk['is_dynamic'] = 1;

               // TYPE : vxfs / ufs  : VOLUMN = mount / FILESYSTEM = device
               if (in_array($logical_drive['TYPE'], array("vxfs", "ufs"))) {
                  $disk['name'] = $logical_drive['VOLUMN'];
                  $disk['mountpoint'] = $logical_drive['VOLUMN'];
                  $disk['device'] = $logical_drive['FILESYSTEM'];
                  $disk['filesystems_id'] = Dropdown::importExternal('Filesystem', $logical_drive["TYPE"]);
               } else if (in_array($logical_drive['FILESYSTEM'], array('ext2', 'ext3', 'ext4', 'ffs',
                  'fuseblk', 'fusefs', 'hfs', 'jfs',
                  'jfs2', 'Journaled HFS+', 'nfs',
                  'smbfs', 'reiserfs', 'vmfs', 'VxFS',
                  'ufs', 'xfs', 'zfs'))) {
                  // Try to detect mount point : OCS database is dirty
                  $disk['mountpoint'] = $logical_drive['VOLUMN'];
                  $disk['device'] = $logical_drive['TYPE'];

                  // Found /dev in VOLUMN : invert datas
                  if (strstr($logical_drive['VOLUMN'], '/dev/')) {
                     $disk['mountpoint'] = $logical_drive['TYPE'];
                     $disk['device'] = $logical_drive['VOLUMN'];
                  }

                  if ($logical_drive['FILESYSTEM'] == "vmfs") {
                     $disk['name'] = basename($logical_drive['TYPE']);
                  } else {
                     $disk['name'] = $disk['mountpoint'];
                  }
                  $disk['filesystems_id'] = Dropdown::importExternal('Filesystem', $logical_drive["FILESYSTEM"]);
               } else if (in_array($logical_drive['FILESYSTEM'], array('FAT', 'FAT32', 'NTFS'))) {
                  if (!empty($logical_drive['VOLUMN'])) {
                     $disk['name'] = $logical_drive['VOLUMN'];
                  } else {
                     $disk['name'] = $logical_drive['LETTER'];
                  }
                  $disk['mountpoint'] = $logical_drive['LETTER'];
                  $disk['filesystems_id'] = Dropdown::importExternal('Filesystem', $logical_drive["FILESYSTEM"]);
               }

               // Ok import disk
               if (isset($disk['name']) && !empty($disk["name"])) {
                  $disk['totalsize'] = $logical_drive['TOTAL'];
                  $disk['freesize'] = $logical_drive['FREE'];

                  $query = "SELECT `id`
                            FROM `glpi_computerdisks`
                            WHERE `computers_id`='$computers_id'
                               AND `name`='" . $disk['name'] . "'
                               AND `is_dynamic`";
                  $results = $DB->query($query);
                  if ($DB->numrows($results) == 1) {
                     $id = $DB->result($results, 0, 'id');
                  } else {
                     $id = false;
                  }

                  if (!$id) {
                     $d->reset();
                     $disk['is_dynamic'] = 1;
                     $id_disk = $d->add($disk, array(), $cfg_ocs['history_drives']);
                     $already_processed[] = $id_disk;
                  } else {
                     // Only update if needed
                     if ($d->getFromDB($id)) {

                        // Update on type, total size change or variation of 5%
                        if ($d->fields['totalsize'] != $disk['totalsize']
                           || ($d->fields['filesystems_id'] != $disk['filesystems_id'])
                           || ((abs($disk['freesize'] - $d->fields['freesize']) / $disk['totalsize']) > 0.05)
                        ) {

                           $toupdate['id'] = $id;
                           $toupdate['totalsize'] = $disk['totalsize'];
                           $toupdate['freesize'] = $disk['freesize'];
                           $toupdate['filesystems_id'] = $disk['filesystems_id'];
                           $d->update($toupdate, $cfg_ocs['history_drives']);
                        }
                        $already_processed[] = $id;
                     }
                  }
               }
            }
         }
      }
      // Delete Unexisting Items not found in OCS
      //Look for all ununsed disks
      $query = "SELECT `id`
                FROM `glpi_computerdisks`
                WHERE `computers_id`='$computers_id'
                   AND `is_dynamic`";
      if (!empty($already_processed)) {
         $query .= "AND `id` NOT IN (" . implode(',', $already_processed) . ")";
      }
      foreach ($DB->request($query) as $data) {
         //Delete all connexions
         $d->delete(array('id'             => $data['id'],
                          '_ocsservers_id' => $ocsservers_id, '_no_history' => !$cfg_ocs['history_drives']), true, $cfg_ocs['history_drives']);
      }
   }

   /**
    * Install a software on a computer - check if not already installed
    *
    * @param $computers_id ID of the computer where to install a software
    * @param $softwareversions_id ID of the version to install
    * @param $installdate
    * @param Do|int $dohistory Do history?
    * @return int|Value
    */
   static function installSoftwareVersion($computers_id, $softwareversions_id, $installdate, $dohistory = 1)
   {
      global $DB;
      if (!empty($softwareversions_id) && $softwareversions_id > 0) {
         $query_exists = "SELECT `id`
                          FROM `glpi_computers_softwareversions`
                          WHERE (`computers_id` = '$computers_id'
                                 AND `softwareversions_id` = '$softwareversions_id')";
         $result = $DB->query($query_exists);

         if ($DB->numrows($result) > 0) {
            return $DB->result($result, 0, "id");
         }

         $tmp = new Computer_SoftwareVersion();
         return $tmp->add(array('computers_id'        => $computers_id,
                                'softwareversions_id' => $softwareversions_id,
                                'date_install'         => $installdate,
                                'is_dynamic'          => 1,
                                'is_deleted'          => 0), array(), $dohistory);
      }
      return 0;
   }

   /**
    * Update a software on a computer - check if not already installed
    *
    * @param $computers_id ID of the computer where to install a software
    * @param $softwareversions_id ID of the version to install
    * @param $installdate
    * @param int $dohistory
    *
    * @return \nothing
    */
   static function updateSoftwareVersion($computers_id, $softwareversions_id, $installdate, $dohistory = 1) {
      global $DB;

      if (!empty($softwareversions_id) && $softwareversions_id > 0) {
         $query_exists = "SELECT `id`
                          FROM `glpi_computers_softwareversions`
                          WHERE (`computers_id` = '$computers_id'
                                 AND `softwareversions_id` = '$softwareversions_id')";
         $result       = $DB->query($query_exists);

         if ($DB->numrows($result) > 0) {
            $data = $DB->fetch_array($result);
            $tmp  = new Computer_SoftwareVersion();


            $input = array('id'           => $data['id'],
                           '_no_history'  => !$dohistory,
                           'date_install' => $installdate);
            return $tmp->update($input);

         }
      }
      return 0;
   }

   /**
    * Update config of a new software
    *
    * This function create a new software in GLPI with some general data.
    *
    * @param $cfg_ocs OCSNG mode configuration
    * @param $computers_id computer's id in GLPI
    * @param $ocsComputer
    * @param $entity the entity in which the peripheral will be created
    * @return Nothing .
    * @internal param OCS $ocsservers_id server id
    * @internal param computer $ocsid 's id in OCS
    */
   static function updateSoftware($cfg_ocs, $computers_id, $ocsComputer, $entity, $officepack)
   {
      global $DB;

      $is_utf8 = $cfg_ocs["ocs_db_utf8"];
      $computer_softwareversion = new Computer_SoftwareVersion();
      $softwares = array();
      //---- Get all the softwares for this machine from OCS -----//
      $softwares = (isset($ocsComputer["SOFTWARES"])?$ocsComputer["SOFTWARES"]:array());

      $soft = new Software();

      // Read imported software in last sync
      $query = "SELECT `glpi_computers_softwareversions`.`id` as id,
                             `glpi_softwares`.`name` as sname,
                             `glpi_softwareversions`.`name` as vname
                      FROM `glpi_computers_softwareversions`
                      INNER JOIN `glpi_softwareversions`
                              ON `glpi_softwareversions`.`id`= `glpi_computers_softwareversions`.`softwareversions_id`
                      INNER JOIN `glpi_softwares`
                              ON `glpi_softwares`.`id`= `glpi_softwareversions`.`softwares_id`
                      WHERE `glpi_computers_softwareversions`.`computers_id`='$computers_id'
                            AND `is_dynamic`";
      $imported = array();


      foreach ($DB->request($query) as $data) {
         $imported[$data['id']] = strtolower($data['sname'] . self::FIELD_SEPARATOR . $data['vname']);
      }

      if($officepack) {
         // Read imported software in last sync
         $query             = "SELECT `glpi_computers_softwarelicenses`.`id` as id,
                          `glpi_softwares`.`name` as sname,
                          `glpi_softwarelicenses`.`name` as lname,
                          `glpi_softwareversions`.`id` as vid
                   FROM `glpi_computers_softwarelicenses`
                   INNER JOIN `glpi_softwarelicenses`
                           ON `glpi_softwarelicenses`.`id`= `glpi_computers_softwarelicenses`.`softwarelicenses_id`
                   INNER JOIN `glpi_softwares`
                           ON `glpi_softwares`.`id`= `glpi_softwarelicenses`.`softwares_id`
                   INNER JOIN `glpi_softwareversions`
                           ON `glpi_softwarelicenses`.`softwareversions_id_use` = `glpi_softwareversions`.`id`
                   WHERE `glpi_computers_softwarelicenses`.`computers_id`='$computers_id'
                         AND `is_dynamic`";
         $imported_licences = array();

         foreach ($DB->request($query) as $data) {
            $imported_licences[$data['id']] = strtolower($data['vid']);
         }
      }

      if (count($softwares) > 0) {
         foreach ($softwares as $software) {
            $software = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($software));

            //As we cannot be sure that data coming from OCS are in utf8, let's try to encode them
            //if possible
            foreach (array('NAME', 'PUBLISHER', 'VERSION') as $field) {
               if (isset($software[$field])) {
                  $software[$field] = self::encodeOcsDataInUtf8($is_utf8, $software[$field]);
               }
            }
            $manufacturer = "";
            //Replay dictionnary on manufacturer
            if (isset($software["PUBLISHER"])) {
               $manufacturer = Manufacturer::processName($software["PUBLISHER"]);
            }
            $version     = $software['VERSION'];
            $name        = $software['NAME'];
            $installdate = $software['INSTALLDATE'];

            //Software might be created in another entity, depending on the entity's configuration
            $target_entity = Entity::getUsedConfig('entities_id_software', $entity);
            //Do not change software's entity except if the dictionnary explicity changes it
            if ($target_entity < 0) {
               $target_entity = $entity;
            }
            $modified_name = $name;
            $modified_version = $version;
            $version_comments = $software['COMMENTS'];
            $is_helpdesk_visible = NULL;
            if (!$cfg_ocs["use_soft_dict"]) {
               //Software dictionnary
               $params = array("name"         => $name,
                               "manufacturer" => $manufacturer,
                               "old_version"  => $version,
                               "entities_id"  => $entity);
               $rulecollection = new RuleDictionnarySoftwareCollection();
               $res_rule = $rulecollection->processAllRules(Toolbox::stripslashes_deep($params), array(), Toolbox::stripslashes_deep(array('version' => $version)));

               if (isset($res_rule["name"])
                  && $res_rule["name"]
               ) {
                  $modified_name = $res_rule["name"];
               }

               if (isset($res_rule["version"])
                  && $res_rule["version"]
               ) {
                  $modified_version = $res_rule["version"];
               }

               if (isset($res_rule["is_helpdesk_visible"])
                  && strlen($res_rule["is_helpdesk_visible"])
               ) {

                  $is_helpdesk_visible = $res_rule["is_helpdesk_visible"];
               }

               if (isset($res_rule['manufacturer'])
                  && $res_rule['manufacturer']
               ) {
                  $manufacturer = Toolbox::addslashes_deep($res_rule["manufacturer"]);
               }

               //If software dictionnary returns an entity, it overrides the one that may have
               //been defined in the entity's configuration
               if (isset($res_rule["new_entities_id"])
                  && strlen($res_rule["new_entities_id"])
               ) {
                  $target_entity = $res_rule["new_entities_id"];
               }
            }

            //If software must be imported
            if (!isset($res_rule["_ignore_import"])
               || !$res_rule["_ignore_import"]
            ) {
               // Clean software object
               $soft->reset();

               // EXPLANATION About dictionnaries
               // OCS dictionnary : if software name change, as we don't store INITNAME
               //     GLPI will detect an uninstall (oldname) + install (newname)
               // GLPI dictionnary : is rule have change
               //     if rule have been replayed, modifiedname will be found => ok
               //     if not, GLPI will detect an uninstall (oldname) + install (newname)

               $id = array_search(strtolower(stripslashes($modified_name . self::FIELD_SEPARATOR . $modified_version)), $imported);

               if ($id) {
                  //-------------------------------------------------------------------------//
                  //---- The software exists in this version for this computer - Update comments --------------//
                  //----  Update date install --------------//
                  //---------------------------------------------------- --------------------//
                  $isNewSoft = $soft->addOrRestoreFromTrash($modified_name, $manufacturer, $target_entity, '', ($entity != $target_entity), $is_helpdesk_visible);
                  //Update version for this software
                  $versionID = self::updateVersion($isNewSoft, $modified_version, $version_comments, $cfg_ocs['history_software']);
                  //Update version for this machine
                  self::updateSoftwareVersion($computers_id, $versionID, $installdate, $cfg_ocs['history_software']);

//                  unset($isNewSoft);
                  unset($imported[$id]);
               } else {
                  //------------------------------------------------------------------------//
                  //---- The software doesn't exists in this version for this computer -----//
                  //------------------------------------------------------------------------//
                  $isNewSoft = $soft->addOrRestoreFromTrash($modified_name, $manufacturer, $target_entity, '', ($entity != $target_entity), $is_helpdesk_visible);
                  //Import version for this software
                  $versionID = self::importVersion($cfg_ocs, $isNewSoft, $modified_version, $version_comments);
                  //Install version for this machine
                  $instID = self::installSoftwareVersion($computers_id, $versionID, $installdate,  $cfg_ocs['history_software']);
               }
               if ($officepack && isset($ocsComputer["OFFICEPACK"])) {
                  // Get import officepack
                  self::updateOfficePack($computers_id, $isNewSoft, $name, $versionID, $entity, $ocsComputer["OFFICEPACK"], $cfg_ocs, $imported_licences);
               }
            }
         }
      }

      foreach ($imported as $id => $unused) {
         $computer_softwareversion->delete(array('id' => $id, '_no_history' => !$cfg_ocs['history_software']), true, $cfg_ocs['history_software']);
         // delete cause a getFromDB, so fields contains values
         $verid = $computer_softwareversion->getField('softwareversions_id');

         if (countElementsInTable('glpi_computers_softwareversions', "softwareversions_id = '$verid'") == 0
            && countElementsInTable('glpi_softwarelicenses', "softwareversions_id_buy = '$verid'") == 0
         ) {

            $vers = new SoftwareVersion();
            if ($vers->getFromDB($verid)
               && countElementsInTable('glpi_softwarelicenses', "softwares_id = '" . $vers->fields['softwares_id'] . "'") == 0
               && countElementsInTable('glpi_softwareversions', "softwares_id = '" . $vers->fields['softwares_id'] . "'") == 1
            ) {
               // 1 is the current to be removed
               $soft->putInTrash($vers->fields['softwares_id'], __('Software deleted by OCSNG synchronization', 'ocsinventoryng'));
            }
            $vers->delete(array("id" => $verid, '_no_history' => !$cfg_ocs['history_software']), true, $cfg_ocs['history_software']);
         }
      }

      if($officepack) {
         $computer_softwarelicenses = new Computer_SoftwareLicense();
         foreach ($imported_licences as $id => $unused) {
            $computer_softwarelicenses->delete(array('id' => $id), true, $cfg_ocs['history_software']);
            // delete cause a getFromDB, so fields contains values
            $verid = $computer_softwarelicenses->getField('softwareversions_id');

            if (countElementsInTable('glpi_computers_softwarelicenses', "softwarelicenses_id = '$verid'") == 0) {

               $vers = new SoftwareVersion();
               if ($vers->getFromDB($verid)
                   && countElementsInTable('glpi_softwarelicenses', "softwares_id = '" . $vers->fields['softwares_id'] . "'") == 0) {
                  $soft = new Software();
                  $soft->delete(array('id' => $vers->fields['softwares_id']), 1);
               }
               $vers->delete(array("id" => $verid));
            }
         }
      }
   }

   /**
    * Update config of the registry
    *
    * This function erase old data and import the new ones about registry (Microsoft OS after Windows 95)
    *
    * @param $computers_id integer : glpi computer id.
    * @param $ocsComputer
    * @internal param int $plugin_ocsinventoryng_ocsservers_id : ocs server id
    * @internal param array $cfg_ocs : ocs config
    * @internal param int $ocsid : ocs computer id (ID).
    */
   static function updateRegistry($computers_id, $ocsComputer)
   {
      //before update, delete all entries about $computers_id
      self::resetRegistry($computers_id);

      $reg = new PluginOcsinventoryngRegistryKey();
      //update data
      foreach ($ocsComputer as $registry) {
         $registry = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($registry));
         $input = array();
         $input["computers_id"] = $computers_id;
         $input["hive"] = $registry["regtree"];
         $input["value"] = $registry["regvalue"];
         $input["path"] = $registry["regkey"];
         $input["ocs_name"] = $registry["name"];
         $reg->add($input, array('disable_unicity_check' => true));
         unset($reg->fields);
      }

      return;
   }

   /**
    * Update config of the antivirus
    *
    * This function erase old data and import the new ones about antivirus
    *
    * @param $computers_id integer : glpi computer id.
    * @param $ocsComputer
    * @param $cfg_ocs array : ocs config
    */
   static function updateAntivirus($computers_id, $ocsComputer, $cfg_ocs)
   {

      self::resetAntivirus($computers_id, $cfg_ocs);

      $av = new ComputerAntivirus();
      //update data
      foreach ($ocsComputer as $anti) {

         $antivirus = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($anti));
         $input = array();

         if ($antivirus["category"] == "AntiVirus") {
            $input["computers_id"] = $computers_id;
            $input["name"] = $antivirus["product"];
            $input["manufacturers_id"] = Dropdown::importExternal('Manufacturer', self::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'], $antivirus["company"]));
            $input["antivirus_version"] = $antivirus["version"];
            $input["is_active"] = $antivirus["enabled"];
            $input["is_uptodate"] = $antivirus["uptodate"];
            $input["is_dynamic"] = 1;
            $av->add($input, array('disable_unicity_check' => true), 0);
            unset($anti->fields);
         }
      }

      return;
   }


   /**
    * Update config of the Winupdatestate
    *
    * This function erase old data and import the new ones about Winupdate
    *
    * @param $computers_id integer : glpi computer id.
    * @param $ocsComputer
    * @param $cfg_ocs array : ocs config
    */
   static function updateWinupdatestate($computers_id, $ocsComputer, $cfg_ocs)
   {

      self::resetWinupdatestate($computers_id, $cfg_ocs);

      $CompWupdate = new PluginOcsinventoryngWinupdate();
      //update data
      foreach ($ocsComputer as $wup) {

         $wupdate = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($wup));
         $input = array();

         $input["computers_id"] = $computers_id;
         $computer = new Computer;
         if ($computer->getFromDB($computers_id)) {
            $input["entities_id"] = $computer->fields['entities_id'];
         }
         $input["auoptions"] = $wupdate["AUOPTIONS"];
         $input["scheduleinstalldate"] = $wupdate["SCHEDULEDINSTALLDATE"];
         $input["lastsuccesstime"] = $wupdate["LASTSUCCESSTIME"];
         $input["detectsuccesstime"] = $wupdate["DETECTSUCCESSTIME"];
         $input["downloadsuccesstime"] = $wupdate["DOWNLOADSUCCESSTIME"];

         $CompWupdate->add($input, array('disable_unicity_check' => true), 0);
         unset($wup->fields);
      }

      return;
   }


   /**
    * Update config of the Proxysetting
    *
    * This function erase old data and import the new ones about Proxysetting
    *
    * @param $computers_id integer : glpi computer id.
    * @param $ocsComputer
    * @param $cfg_ocs array : ocs config
    */
   static function updateProxysetting($computers_id, $ocsComputer, $cfg_ocs)
   {

      self::resetProxysetting($computers_id, $cfg_ocs);

      $ProxySetting = new PluginOcsinventoryngProxysetting();

      //update data
      foreach ($ocsComputer as $prox) {

         $proxy = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($prox));
         $input = array();

         $input["computers_id"] = $computers_id;
         $computer = new Computer;
         if ($computer->getFromDB($computers_id)) {
            $input["entities_id"] = $computer->fields['entities_id'];
         }
         $input["user"] = $proxy["USER"];
         $input["enable"] = $proxy["ENABLE"];
         if (isset($ocsComputer["AUTOCONFIGURL"])) {
            $input["autoconfigurl"] = $proxy["AUTOCONFIGURL"];
         }
         $input["address"] = $proxy["ADDRESS"];
         if (isset($proxy["OVERRIDE"])) {
            $input["override"] = $proxy["OVERRIDE"];
         }
         $ProxySetting->add($input, array('disable_unicity_check' => true), 0);
         unset($prox->fields);
      }

      return;

   }


   /**
    * Update config of the WinUsers
    *
    * This function erase old data and import the new ones about WinUser
    *
    * @param $computers_id integer : glpi computer id.
    * @param $ocsComputer
    * @param $cfg_ocs array : ocs config
    */
   static function updateWinuser($computers_id, $ocsComputer, $cfg_ocs)
   {

      self::resetWinuser($computers_id, $cfg_ocs);

      $winusers = new PluginOcsinventoryngWinuser();
      //update data
      foreach ($ocsComputer as $wusers) {

         $wuser = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($wusers));
         $input = array();

         $input["computers_id"] = $computers_id;
         $computer = new Computer;
         if ($computer->getFromDB($computers_id)) {
            $input["entities_id"] = $computer->fields['entities_id'];
         }
         $input["name"] = $wuser["NAME"];
         $input["type"] = $wuser["TYPE"];
         $input["description"] = $wuser["DESCRIPTION"];
         $input["disabled"] = $wuser["DISABLED"];
         $input["sid"] = $wuser["SID"];

         $winusers->add($input, array('disable_unicity_check' => true), 0);
         unset($wusers->fields);
      }

      return;
   }

   /**
    * Update config of the Teamviewer
    *
    * This function erase old data and import the new ones about Teamviewer
    *
    * @param $computers_id integer : glpi computer id.
    * @param $ocsComputer
    * @param $cfg_ocs array : ocs config
    */
   static function updateTeamviewer($computers_id, $ocsComputer, $cfg_ocs)
   {

      self::resetTeamviewer($computers_id, $cfg_ocs);

      $CompTeam = new PluginOcsinventoryngTeamviewer();
      $input = array();

      $input["computers_id"] = $computers_id;
      $computer = new Computer;
      if ($computer->getFromDB($computers_id)) {
         $input["entities_id"] = $computer->fields['entities_id'];
      }
      $input["version"] = $ocsComputer["VERSION"];
      $input["twid"] = $ocsComputer["TWID"];

      $CompTeam->add($input, array('disable_unicity_check' => true), 0);

      return;
   }

   /**
    * @param $id
    * @param $ocsComputer
    * @param $cfg_ocs
    */
   static function updateUptime($id, $ocsComputer, $cfg_ocs)
   {
      global $DB;

      if ($id) {

         if (isset($ocsComputer["time"])) {
            $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                      SET `uptime` = '" . $ocsComputer["time"] . "'
                      WHERE `id` = '" . $id . "'";

            $DB->query($query);
         }
      }
   }

   /**
    * Update config of the Runningprocess
    *
    * This function erase old data and import the new ones about Runningprocess
    *
    * @param $computers_id integer : glpi computer id.
    * @param $ocsComputer
    * @param $cfg_ocs array : ocs config
    */
   static function updateRunningprocess($computers_id, $ocsComputer, $cfg_ocs) {

      self::resetRunningProcess($computers_id, $cfg_ocs);

      $Runningprocess = new PluginOcsinventoryngRunningprocess();

      //update data
      foreach ($ocsComputer as $runningprocess) {

         $process = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($runningprocess));
         $input = array_change_key_case($process, CASE_LOWER);
         $input["computers_id"] = $computers_id;

         $Runningprocess->add($input, array('disable_unicity_check' => true), 0);
         $Runningprocess->fields = [];
      }

      return;
   }

   /**
    * Update config of the Service
    *
    * This function erase old data and import the new ones about Service
    *
    * @param $computers_id integer : glpi computer id.
    * @param $ocsComputer
    * @param $cfg_ocs array : ocs config
    */
   static function updateService($computers_id, $ocsComputer, $cfg_ocs) {

      self::resetService($computers_id, $cfg_ocs);

      $Service = new PluginOcsinventoryngService();

      //update data
      foreach ($ocsComputer as $service) {

         $service = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($service));
         $input = array_change_key_case($service, CASE_LOWER);
         $input["computers_id"] = $computers_id;

         $Service->add($input, array('disable_unicity_check' => true), 0);
         $Service->fields = [];
      }

      return;
   }

   /**
    * Update the administrative informations
    *
    * This function erase old data and import the new ones about administrative informations
    *
    * @param $computers_id integer : glpi computer id.
    * @param $ocsid integer : ocs computer id (ID).
    * @param $plugin_ocsinventoryng_ocsservers_id integer : ocs server id
    * @param $cfg_ocs array : configuration ocs of the server
    * @param $computer_updates array : already updated fields of the computer
    * @param $entities_id
    * @return Nothing .
    * @internal param int $entity : entity of the computer
    * @internal param bool $dohistory : log changes?
    *
    */
   static function updateAdministrativeInfo($computers_id, $ocsid, $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs, $computer_updates, $entities_id)
   {
      global $DB;
      self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      //check link between ocs and glpi column
      $queryListUpdate = "SELECT*
                          FROM `glpi_plugin_ocsinventoryng_ocsadmininfoslinks`
                          WHERE `plugin_ocsinventoryng_ocsservers_id` = '$plugin_ocsinventoryng_ocsservers_id' ";
      $result = $DB->query($queryListUpdate);

      if ($DB->numrows($result) > 0) {
         $options = array(
            "DISPLAY" => array(
               "CHECKSUM" => PluginOcsinventoryngOcsClient::CHECKSUM_BIOS,
               "WANTED"   => PluginOcsinventoryngOcsClient::WANTED_ACCOUNTINFO,
            )
         );
         $computer = $ocsClient->getComputer($ocsid, $options);

         if ($computer) {
            $accountinfos = $computer["ACCOUNTINFO"];
            foreach ($accountinfos as $key => $accountinfo) {
               $comp = new Computer();
               //update data
               while ($links_glpi_ocs = $DB->fetch_array($result)) {
                  //get info from ocs
                  $ocs_column = $links_glpi_ocs['ocs_column'];
                  $glpi_column = $links_glpi_ocs['glpi_column'];

                  if ($computer_updates
                     && array_key_exists($ocs_column, $accountinfo)
                     && !in_array($glpi_column, $computer_updates)
                  ) {

                     if (isset($accountinfo[$ocs_column])) {
                        $var = addslashes($accountinfo[$ocs_column]);
                     } else {
                        $var = "";
                     }
                     switch ($glpi_column) {
                        case "groups_id":
                           $var = self::importGroup($var, $entities_id);
                           break;

                        case "locations_id":
                           $var = Dropdown::importExternal("Location", $var, $entities_id);
                           break;

                        case "networks_id":
                           $var = Dropdown::importExternal("Network", $var);
                           break;
                     }

                     $input = array();
                     $input[$glpi_column] = $var;
                     $input["id"] = $computers_id;
                     $input["entities_id"] = $entities_id;
                     $input["_nolock"] = true;
                     $comp->update($input, $cfg_ocs['history_admininfos']);
                  }

                  if ($computer_updates
                     && $ocs_column == 'ASSETTAG'
                     && !in_array($glpi_column, $computer_updates)
                  ) {

                     $var = $computer["BIOS"]["ASSETTAG"];
                     if (isset($computer["BIOS"]["ASSETTAG"])
                        && !empty($computer["BIOS"]["ASSETTAG"])
                     ) {
                        $input = array();
                        $input[$glpi_column] = $var;
                        $input["id"] = $computers_id;
                        $input["entities_id"] = $entities_id;
                        $input["_nolock"] = true;
                        $comp->update($input, $cfg_ocs['history_admininfos']);
                     }
                  }
               }
            }
         }
      }
   }

   /**
    * Update the administrative informations
    *
    * This function erase old data and import the new ones about administrative informations
    *
    * @param $computers_id integer : glpi computer id.
    * @param $plugin_ocsinventoryng_ocsservers_id integer : ocs server id
    * @param $computer_updates array : already updated fields of the computer
    * @param $ocsComputer
    * @return Nothing .
    * @internal param int $ocsid : ocs computer id (ID).
    * @internal param array $cfg_ocs : configuration ocs of the server
    * @internal param int $entity : entity of the computer
    * @internal param bool $dohistory : log changes?
    *
    */
   static function updateAdministrativeInfoUseDate($computers_id, $plugin_ocsinventoryng_ocsservers_id, $computer_updates, $ocsComputer)
   {
      global $DB;
      self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);

      //check link between ocs and glpi column
      $queryListUpdate = "SELECT *
                          FROM `glpi_plugin_ocsinventoryng_ocsadmininfoslinks`
                          WHERE `plugin_ocsinventoryng_ocsservers_id` = '$plugin_ocsinventoryng_ocsservers_id' ";
      $result = $DB->query($queryListUpdate);

      if ($DB->numrows($result) > 0) {


         if (isset($ocsComputer['HARDWARE'])) {
            $metas = $ocsComputer['HARDWARE'];
            foreach ($metas as $key => $meta) {

               //update data
               while ($links_glpi_ocs = $DB->fetch_array($result)) {
                  //get info from ocs
                  $ocs_column = $links_glpi_ocs['ocs_column'];
                  $glpi_column = $links_glpi_ocs['glpi_column'];
                  if ($computer_updates
                     && array_key_exists($ocs_column, $metas)
                     && !in_array($glpi_column, $computer_updates)
                  ) {
                     if (isset($metas[$ocs_column])) {
                        $var = addslashes($metas[$ocs_column]);
                     } else {
                        $var = "";
                     }
                     switch ($glpi_column) {
                        case "use_date":
                           $date = str_replace($metas['NAME'] . "-", "", $var);
                           $computer_updates = PluginOcsinventoryngOcsAdminInfosLink::addInfocomsForComputer($computers_id, $date, $computer_updates);
                           break;


                     }
                  }
               }
            }
         }
      }
      return $computer_updates;
   }

   /**
    * @param $name
    * @return array
    */
   static function cronInfo($name)
   {
      // no translation for the name of the project
      switch ($name) {
         case 'ocsng':
            return array('description' => __('OCSNG', 'ocsinventoryng') . " - " . __('Launch OCSNG synchronization script', 'ocsinventoryng'));
            break;
         case 'CleanOldAgents':
            return array('description' => __('OCSNG', 'ocsinventoryng') . " - " . __('Clean old agents & drop from OCSNG software', 'ocsinventoryng'));
            break;
         case 'RestoreOldAgents':
            return array('description' => __('OCSNG', 'ocsinventoryng') . " - " . __('Restore computers from the date of last inventory', 'ocsinventoryng'));
            break;
      }
   }

   /**
    * cron Clean Old Agents
    *
    * @param $task
    * @return int
    */
   static function cronCleanOldAgents($task)
   {
      global $DB;

      $CronTask = new CronTask();
      if ($CronTask->getFromDBbyName("PluginOcsinventoryngOcsServer", "CleanOldAgents")) {
         if ($CronTask->fields["state"] == CronTask::STATE_DISABLE) {
            return 0;
         }
      } else {
         return 0;
      }

      $cron_status = 0;
      $plugin_ocsinventoryng_ocsservers_id = 0;
      foreach ($DB->request("glpi_plugin_ocsinventoryng_ocsservers", "`is_active` = 1 AND `use_cleancron` = 1") as $config) {
         $plugin_ocsinventoryng_ocsservers_id = $config["id"];
         if ($plugin_ocsinventoryng_ocsservers_id > 0) {

            $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);
            $agents = $ocsClient->getOldAgents();

            if ($config['action_cleancron'] == self::ACTION_PURGE_COMPUTER) {
               //action purge agents OCSNG

               $computers = array();
               if (count($agents) > 0) {

                  $nb = $ocsClient->deleteOldAgents($agents);
                  if ($nb) {
                     self::manageDeleted($plugin_ocsinventoryng_ocsservers_id, false);
                     $cron_status = 1;
                     if ($task) {
                        $task->addVolume($nb);
                        $task->log(__('Clean old agents OK', 'ocsinventoryng'));
                     }
                  } else {
                     $task->log(__('Clean old agents failed', 'ocsinventoryng'));
                  }
               }
            } else {
               //action delete computers

               if (count($agents) > 0) {
                  $nb = self::deleteComputers($plugin_ocsinventoryng_ocsservers_id, $agents);
                  if ($nb) {
                     $cron_status = 1;
                     if ($task) {
                        $task->addVolume($nb);
                        $task->log(__('Delete computers OK', 'ocsinventoryng'));
                     }
                  } else {
                     $task->log(__('Delete computers failed', 'ocsinventoryng'));
                  }
               }
            }
         }
      }

      return $cron_status;
   }

   /**
    * cron Restore Old Agents
    *
    * @global type $DB
    * @global type $CFG_GLPI
    * @param type $task
    * @return int
    */
   static function cronRestoreOldAgents($task) {
      global $DB, $CFG_GLPI;

      $CronTask = new CronTask();
      if ($CronTask->getFromDBbyName("PluginOcsinventoryngOcsServer", "RestoreOldAgents")) {
         if ($CronTask->fields["state"] == CronTask::STATE_DISABLE) {
            return 0;
         }
      } else {
         return 0;
      }

      $cron_status                         = 0;
      $plugin_ocsinventoryng_ocsservers_id = 0;
      foreach ($DB->request("glpi_plugin_ocsinventoryng_ocsservers",
                           "`is_active` = 1 AND `use_cleancron` = 1 AND `use_restorationcron` = 1") as $config) {
         $plugin_ocsinventoryng_ocsservers_id = $config["id"];
         if ($plugin_ocsinventoryng_ocsservers_id > 0) {
            $delay = $config['delay_restorationcron'];

            $query = "SELECT `glpi_computers`.`id`
                     FROM `glpi_computers`
                     INNER JOIN `glpi_plugin_ocsinventoryng_ocslinks` AS ocslink
                        ON ocslink.`computers_id` = `glpi_computers`.`id`
                     WHERE `glpi_computers`.`is_deleted` = 1
                     AND ( unix_timestamp(ocslink.`last_ocs_update`) >= UNIX_TIMESTAMP(NOW() - INTERVAL $delay DAY))";


            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               $computer = new Computer();
               while ($data = $DB->fetch_assoc($result)) {
                  $computer->fields['id']         = $data['id'];
                  $computer->fields['is_deleted'] = 0;
                  $computer->fields['date_mod'] = $_SESSION['glpi_currenttime'];
                  $computer->updateInDB(array('is_deleted', 'date_mod'));
                  //add history
                  $changes[0] = 0;
                  $changes[2] = "";
                  $changes[1] = "";
                  Log::history($data['id'], 'Computer', $changes, 0,
                               Log::HISTORY_RESTORE_ITEM);
//                  $computer->update(array('id' => $data['id'], 'is_deleted' => 0));
               }
               $cron_status = 1;
               if ($task) {
                  $task->addVolume($DB->numrows($result));
                  $task->log(__('Restore computers OK', 'ocsinventoryng'));
               }
            } else {
               $task->log(__('Restore computers failed', 'ocsinventoryng'));
            }
         }
      }

      return $cron_status;
   }


   /**
    * @param $task
    * @return int
    */
   static function cronocsng($task)
   {
      global $DB;

      //Get a randon server id
      $plugin_ocsinventoryng_ocsservers_id = self::getRandomServerID();
      if ($plugin_ocsinventoryng_ocsservers_id > 0) {
         //Initialize the server connection
         $PluginOcsinventoryngDBocs = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);

         $cfg_ocs = self::getConfig($plugin_ocsinventoryng_ocsservers_id);
         $task->log(__('Launch OCSNG synchronization script from server', 'ocsinventoryng') . " " . $cfg_ocs['name'] . "\n");

         if (!$cfg_ocs["cron_sync_number"]) {
            return 0;
         }
         self::manageDeleted($plugin_ocsinventoryng_ocsservers_id);

         $query = "SELECT MAX(`last_ocs_update`)
                   FROM `glpi_plugin_ocsinventoryng_ocslinks`
                   WHERE `plugin_ocsinventoryng_ocsservers_id`='$plugin_ocsinventoryng_ocsservers_id'";
         $max_date = "0000-00-00 00:00:00";
         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               $max_date = $DB->result($result, 0, 0);
            }
         }

         $res = $PluginOcsinventoryngDBocs->getComputersToUpdate($cfg_ocs, $max_date);

         $task->setVolume(0);
         if (count($res) > 0) {

            foreach ($res as $k => $data) {
               if (count($data) > 0) {
                  // Fetch all linked computers from GLPI that were returned from OCS
                  $query_glpi = "SELECT `id`, `ocs_deviceid`
                                 FROM `glpi_plugin_ocsinventoryng_ocslinks`
                                 WHERE `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id`
                                             = '$plugin_ocsinventoryng_ocsservers_id'
                                        AND `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid` = '" . $data["ID"] . "'";
                  $result_glpi = $DB->query($query_glpi);
                  if ($DB->numrows($result_glpi) > 0) {
                     while ($values = $DB->fetch_assoc($result_glpi)) {
                        $task->addVolume(1);
                        $task->log(sprintf(__('%1$s: %2$s'), _n('Computer', 'Computer', 1), sprintf(__('%1$s (%2$s)'), $values["ocs_deviceid"], $values["id"])));

                        $dohistory = (isset($cfg_ocs['dohistory']) ? $cfg_ocs['dohistory'] : false);
                        self::updateComputer($values["id"], $plugin_ocsinventoryng_ocsservers_id, $dohistory);

                     }
                  }
               }
            }
         } else {
            return 0;
         }
      }
      return 1;
   }

   /**
    * @param $printer_infos
    * @param string $port
    */
   static function analizePrinterPorts(&$printer_infos, $port = '')
   {

      if (preg_match("/USB[0-9]*/i", $port)) {
         $printer_infos['have_usb'] = 1;
      } else if (preg_match("/IP_/i", $port)) {
         $printer_infos['have_ethernet'] = 1;
      } else if (preg_match("/LPT[0-9]:/i", $port)) {
         $printer_infos['have_parallel'] = 1;
      }
   }

   /**
    * @param bool $snmp
    * @param bool $ipdiscover
    * @return array
    */
   static function getAvailableStatistics($snmp = false, $ipdiscover = false)
   {

      $stats = array('imported_machines_number'     => __('Computers imported', 'ocsinventoryng'),
                     'synchronized_machines_number' => __('Computers synchronized', 'ocsinventoryng'),
                     'linked_machines_number'       => __('Computers linked', 'ocsinventoryng'),
                     'notupdated_machines_number'   => __('Computers not updated', 'ocsinventoryng'),
                     'failed_rules_machines_number' => __("Computers don't check any rule", 'ocsinventoryng'),
                     'not_unique_machines_number'   => __('Duplicate computers', 'ocsinventoryng'),
                     'link_refused_machines_number' => __('Computers whose import is refused by a rule', 'ocsinventoryng'));
      if ($snmp) {
         $stats = array('imported_snmp_number'        => __('SNMP objects imported', 'ocsinventoryng'),
                        'synchronized_snmp_number'    => __('SNMP objects synchronized', 'ocsinventoryng'),
                        'linked_snmp_number'          => __('SNMP objects linked', 'ocsinventoryng'),
                        'notupdated_snmp_number'      => __('SNMP objects not updated', 'ocsinventoryng'),
                        'failed_imported_snmp_number' => __("SNMP objects not imported", 'ocsinventoryng'));
      }
      if ($ipdiscover) {
         $stats = array('imported_ipdiscover_number'        => __('IPDISCOVER objects imported', 'ocsinventoryng'),
                        'synchronized_ipdiscover_number'    => __('IPDISCOVER objects synchronized', 'ocsinventoryng'),
                        'notupdated_ipdiscover_number'      => __('IPDISCOVER objects not updated', 'ocsinventoryng'),
                        'failed_imported_ipdiscover_number' => __("IPDISCOVER objects not imported", 'ocsinventoryng'));
      }

      return $stats;
   }

   /**
    * @param array $statistics
    * @param bool $action
    * @param bool $snmp
    * @param bool $ipdiscover
    */
   static function manageImportStatistics(&$statistics = array(), $action = false, $snmp = false, $ipdiscover = false)
   {

      if (empty($statistics)) {
         foreach (self::getAvailableStatistics($snmp, $ipdiscover) as $field => $label) {
            $statistics[$field] = 0;
         }
      }

      switch ($action) {
         case self::COMPUTER_SYNCHRONIZED:
            $statistics["synchronized_machines_number"]++;
            break;

         case self::COMPUTER_IMPORTED:
            $statistics["imported_machines_number"]++;
            break;

         case self::COMPUTER_FAILED_IMPORT:
            $statistics["failed_rules_machines_number"]++;
            break;

         case self::COMPUTER_LINKED:
            $statistics["linked_machines_number"]++;
            break;

         case self::COMPUTER_NOT_UNIQUE:
            $statistics["not_unique_machines_number"]++;
            break;

         case self::COMPUTER_NOTUPDATED:
            $statistics["notupdated_machines_number"]++;
            break;

         case self::COMPUTER_LINK_REFUSED:
            $statistics["link_refused_machines_number"]++;
            break;

         case self::SNMP_SYNCHRONIZED:
            $statistics["synchronized_snmp_number"]++;
            break;

         case self::SNMP_IMPORTED:
            $statistics["imported_snmp_number"]++;
            break;

         case self::SNMP_FAILED_IMPORT:
            $statistics["failed_imported_snmp_number"]++;
            break;

         case self::SNMP_LINKED:
            $statistics["linked_snmp_number"]++;
            break;

         case self::SNMP_NOTUPDATED:
            $statistics["notupdated_snmp_number"]++;
            break;

         case self::IPDISCOVER_IMPORTED:
            $statistics["imported_ipdiscover_number"]++;
            break;

         case self::IPDISCOVER_FAILED_IMPORT:
            $statistics["failed_imported_ipdiscover_number"]++;
            break;

         case self::IPDISCOVER_NOTUPDATED:
            $statistics["notupdated_ipdiscover_number"]++;
            break;

         case self::IPDISCOVER_SYNCHRONIZED:
            $statistics["synchronized_ipdiscover_number"]++;
            break;
      }
   }

   /**
    * @param array $statistics
    * @param bool $finished
    * @param bool $snmp
    * @param bool $ipdiscover
    */
   static function showStatistics($statistics = array(), $finished = false, $snmp = false, $ipdiscover = false)
   {

      echo "<div class='center b'>";
      echo "<table class='tab_cadre_fixe'>";
      if ($snmp) {
         echo "<th colspan='2'>" . __('Statistics of the OCSNG SNMP import', 'ocsinventoryng');
      } else if ($ipdiscover) {
         echo "<th colspan='2'>" . __('Statistics of the OCSNG IPDISCOVER import', 'ocsinventoryng');
      } else {
         echo "<th colspan='2'>" . __('Statistics of the OCSNG link', 'ocsinventoryng');
      }

      if ($finished) {
         echo "&nbsp;-&nbsp;";
         echo __('Task completed.');
      }
      echo "</th>";

      foreach (self::getAvailableStatistics($snmp, $ipdiscover) as $field => $label) {
         echo "<tr class='tab_bg_1'><td>" . $label . "</td><td>" . $statistics[$field] . "</td></tr>";
      }
      echo "</table></div>";
   }

   /**
    * Do automatic transfer if option is enable
    *
    * @param $line_links array : data from glpi_plugin_ocsinventoryng_ocslinks table
    * @return nothing
    * @internal param array $line_ocs : data from ocs tables
    *
    */
   static function transferComputer($line_links) {
      global $DB, $CFG_GLPI;

      $ocsClient = self::getDBocs($line_links["plugin_ocsinventoryng_ocsservers_id"]);
      $cfg_ocs = self::getConfig($line_links["plugin_ocsinventoryng_ocsservers_id"]);
      $ocsComputer = $ocsClient->getComputer($line_links["ocsid"]);

      $locations_id = 0;
      $groups_id = 0;
      $contact = (isset($ocsComputer['META']["USERID"])) ? $ocsComputer['META']["USERID"] : "";
      if (!empty($contact) && $cfg_ocs["import_general_contact"] > 0) {
         $query = "SELECT `id`
                   FROM `glpi_users`
                   WHERE `name` = '" . $contact . "';";
         $result = $DB->query($query);

         if ($DB->numrows($result) == 1) {
            $user_id = $DB->result($result, 0, 0);
            $user = new User();
            $user->getFromDB($user_id);

            if ($cfg_ocs["import_user_location"] > 0) {
               $locations_id = $user->fields["locations_id"];
            }
            if ($cfg_ocs["import_user_group"] > 0) {
               $comp = new Computer();
               $comp->getFromDB($line_links["computers_id"]);
               $groups_id = self::getUserGroup($comp->fields["entities_id"], $user_id, '`is_requester`', true);
            }
         }
      }

      // Get all rules for the current plugin_ocsinventoryng_ocsservers_id
      $rule = new RuleImportEntityCollection();

      $data = $rule->processAllRules(array('ocsservers_id' => $line_links["plugin_ocsinventoryng_ocsservers_id"],
                                           '_source'       => 'ocsinventoryng',
                                           'locations_id'  => $locations_id,
                                           'groups_id'     => $groups_id
                                     ), array(
                                        'locations_id' => $locations_id,
                                        'groups_id'    => $groups_id
                                     ), array('ocsid' => $line_links["ocsid"]));

      // If entity is changing move items to the new entities_id
      if (isset($data['entities_id']) && $data['entities_id'] > -1 && $data['entities_id'] != $line_links['entities_id']) {

         if (!isCommandLine() && !Session::haveAccessToEntity($data['entities_id'])) {
            Html::displayRightError();
         }

         $transfer = new Transfer();
         $transfer->getFromDB($CFG_GLPI['transfers_id_auto']);

         $item_to_transfer = array("Computer" => array($line_links['computers_id']
                                                       => $line_links['computers_id']));

         $transfer->moveItems($item_to_transfer, $data['entities_id'], $transfer->fields);
      }

      //If location is update by a rule
      self::updateComputerFields($line_links, $data, $cfg_ocs);
   }

   /**
    * @param        $entity
    * @param        $userid
    * @param string $filter
    * @param bool   $first
    *
    * @return array|int
    */
   static private function getUserGroup ($entity, $userid, $filter='', $first=true) {
      global $DB;

      $query = "SELECT `glpi_groups`.`id`
                FROM `glpi_groups_users`
                INNER JOIN `glpi_groups` ON (`glpi_groups`.`id` = `glpi_groups_users`.`groups_id`)
                WHERE `glpi_groups_users`.`users_id` = '".$userid."'".
               getEntitiesRestrictRequest(' AND ', 'glpi_groups', '', $entity, true);

      if ($filter) {
         $query .= "AND (".$filter.")";
      }
      $rep = [];
      foreach ($DB->request($query) as $data) {
         if ($first) {
            return $data['id'];
         }
         $rep[] = $data['id'];
      }
      return ($first ? 0 : $rep);
   }

   /**
    * Update fields : location / group for a computer if needed after rule processing
    *
    * @param $line_links
    * @param $data
    * @param $cfg_ocs
    * @return nothing
    * @internal param $line_links
    * @internal param $data
    *
    */
   static function updateComputerFields($line_links, $data, $cfg_ocs)
   {

      //If there's a location to update
      if (isset($data['locations_id'])) {
         $computer = new Computer();
         $computer->getFromDB($line_links['computers_id']);
         $ancestors = getAncestorsOf('glpi_entities', $computer->fields['entities_id']);

         $location = new Location();
         if ($location->getFromDB($data['locations_id'])) {
            //If location is in the same entity as the computer, or if the location is
            //defined in a parent entity, but recursive
            if ($location->fields['entities_id'] == $computer->fields['entities_id']
               || (in_array($location->fields['entities_id'], $ancestors)
                  && $location->fields['is_recursive'])
            ) {
               $ko = 0;
               $locks = self::getLocksForComputer($line_links['computers_id']);
               if (is_array($locks) && count($locks)) {
                  if (in_array("locations_id", $locks)) {
                     $ko = 1;
                  }
               }
               if ($ko == 0) {
                  $tmp['locations_id'] = $data['locations_id'];
                  $tmp['id'] = $line_links['computers_id'];
                  $computer->update($tmp, $cfg_ocs['history_hardware']);
               }
            }
         }
      }

      //If there's a Group to update
      if (isset($data['groups_id'])) {
         $computer = new Computer();
         $computer->getFromDB($line_links['computers_id']);
         $ancestors = getAncestorsOf('glpi_entities', $computer->fields['entities_id']);

         $group = new Group();
         if ($group->getFromDB($data['groups_id'])) {
            //If group is in the same entity as the computer, or if the group is
            //defined in a parent entity, but recursive
            if ($group->fields['entities_id'] == $computer->fields['entities_id']
                || (in_array($group->fields['entities_id'], $ancestors)
                    && $group->fields['is_recursive'])) {
               $ko    = 0;
               $locks = self::getLocksForComputer($line_links['computers_id']);
               if (is_array($locks) && count($locks)) {
                  if (in_array("groups_id", $locks)) {
                     $ko = 1;
                  }
               }
               if ($ko == 0) {
                  $tmp['groups_id'] = $data['groups_id'];
                  $tmp['id']           = $line_links['computers_id'];
                  $computer->update($tmp, $cfg_ocs['history_hardware']);
               }
            }
         }
      }
   }

   /**
    * @param $ID
    * @return array|null
    */
   static function getLocksForComputer($ID)
   {
      global $DB;

      $query = "SELECT *
      FROM `glpi_plugin_ocsinventoryng_ocslinks`
      WHERE `computers_id` = '$ID'";
      $locks = array();
      $result = $DB->query($query);
      if ($DB->numrows($result) == 1) {
         $data = $DB->fetch_assoc($result);

         $cfg_ocs = self::getConfig($data["plugin_ocsinventoryng_ocsservers_id"]);
         if ($cfg_ocs["use_locks"]) {

            // Print lock fields for OCSNG
            $lockable_fields = self::getLockableFields();
            $locked = importArrayFromDB($data["computer_update"]);

            if (!in_array(self::IMPORT_TAG_078, $locked)) {
               $locked = self::migrateComputerUpdates($ID, $locked);
            }

            if (count($locked) > 0) {
               foreach ($locked as $key => $val) {
                  if (!isset($lockable_fields[$val])) {
                     unset($locked[$key]);
                  }
               }
            }

            if (count($locked)) {

               foreach ($locked as $key => $val) {
                  $locks[$key] = $val;
               }
            }
         } else {
            $locks = null;
         }
         return $locks;
      }
   }

   /**
    * Update TAG information in glpi_plugin_ocsinventoryng_ocslinks table
    *
    * @param $line_links array : data from glpi_plugin_ocsinventoryng_ocslinks table
    * @return string : current tag of computer on update
    * @internal param array $line_ocs : data from ocs tables
    *
    */
   static function updateTag($line_links)
   {
      global $DB;
      $ocsClient = self::getDBocs($line_links["plugin_ocsinventoryng_ocsservers_id"]);

      $computer = $ocsClient->getComputer($line_links["ocsid"]);

      if ($computer) {
         $data_ocs = Toolbox::addslashes_deep($computer["META"]);

         if (isset($data_ocs["TAG"])
            && isset($line_links["tag"])
            && $data_ocs["TAG"] != $line_links["tag"]
         ) {
            $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                      SET `tag` = '" . $data_ocs["TAG"] . "'
                      WHERE `id` = '" . $line_links["id"] . "'";

            if ($DB->query($query)) {
               $changes[0] = '0';
               $changes[1] = $line_links["tag"];
               $changes[2] = $data_ocs["TAG"];

               PluginOcsinventoryngOcslink::history($line_links["computers_id"], $changes, PluginOcsinventoryngOcslink::HISTORY_OCS_TAGCHANGED);
               return $data_ocs["TAG"];
            }
         }
      }
   }

   /**
    * For other plugins, add a type to the linkable types
    *
    *
    * @param $type string class name
    * */
   static function registerType($type)
   {
      if (!in_array($type, self::$types)) {
         self::$types[] = $type;
      }
   }

   /**
    * Type than could be linked to a Store
    *
    * @param $all boolean, all type, or only allowed ones
    *
    * @return array of types
    * */
   static function getTypes($all = false)
   {

      if ($all) {
         return self::$types;
      }

      // Only allowed types
      $types = self::$types;

      foreach ($types as $key => $type) {
         if (!($item = getItemForItemtype($type))) {
            continue;
         }

         if (!$item->canView()) {
            unset($types[$key]);
         }
      }
      return $types;
   }

   /**
    * @return array
    */
   static function getLockableFields()
   {

      return array("name"                            => __('Name'),
                   "computertypes_id"                => __('Type'),
                   "manufacturers_id"                => __('Manufacturer'),
                   "computermodels_id"               => __('Model'),
                   "serial"                          => __('Serial number'),
                   "otherserial"                     => __('Inventory number'),
                   "comment"                         => __('Comments'),
                   "contact"                         => __('Alternate username'),
                   "contact_num"                     => __('Alternate username number'),
                   "domains_id"                      => __('Domain'),
                   "networks_id"                     => __('Network'),
                   "operatingsystems_id"             => __('Operating system'),
                   "operatingsystemservicepacks_id"  => __('Service pack'),
                   "operatingsystemversions_id"      => __('Version of the operating system'),
                   'operatingsystemarchitectures_id' => __('Operating system architecture'),//Enable 9.1
                   "os_license_number"               => __('Serial of the operating system'),
                   "os_licenseid"                    => __('Product ID of the operating system'),
                   "users_id"                        => __('User'),
                   "locations_id"                    => __('Location'),
                   "use_date"                        => __('Startup date'),
                   "groups_id"                       => __('Group'));
   }

   /**
    * @param $id
    */
   static function showOcsReportsConsole($id)
   {

      $ocsconfig = self::getConfig($id);

      echo "<div class='center'>";
      if ($ocsconfig["ocs_url"] != '') {
         echo "<iframe src='" . $ocsconfig["ocs_url"] . "/index.php?multi=4' width='95%' height='650'>";
      }
      echo "</div>";
   }

   /**
    * @since version 0.84
    *
    * @param $target
    * */
   static function checkBox($target)
   {

      echo "<a href='" . $target . "?check=all' " .
         "onclick= \"if (markCheckboxes('ocsng_form')) return false;\">" . __('Check all') .
         "</a>&nbsp;/&nbsp;\n";
      echo "<a href='" . $target . "?check=none' " .
         "onclick= \"if ( unMarkCheckboxes('ocsng_form') ) return false;\">" .
         __('Uncheck all') . "</a>\n";
   }

   /**
    * @return int|Value
    */
   static function getFirstServer()
   {
      global $DB;

      $query = "SELECT `glpi_plugin_ocsinventoryng_ocsservers`.`id`
                FROM `glpi_plugin_ocsinventoryng_ocsservers_profiles`
                LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers`
                ON `glpi_plugin_ocsinventoryng_ocsservers`.`id` = `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id`
                WHERE `glpi_plugin_ocsinventoryng_ocsservers`.`is_active`='1'
                      AND`profiles_id`= " . $_SESSION["glpiactiveprofile"]['id'] . "
                ORDER BY `glpi_plugin_ocsinventoryng_ocsservers`.`id` ASC LIMIT 1 ";
      $results = $DB->query($query);
      if ($DB->numrows($results) > 0) {
         return $DB->result($results, 0, 'id');
      }
      return -1;
   }

   /**
    *
    * Import monitors from OCS
    * @since 1.0
    * @param $cfg_ocs OCSNG mode configuration
    * @param $computers_id computer's id in GLPI
    * @param $ocsservers_id OCS server id
    * @param $ocsComputer
    * @param the $entity
    * @internal param computer $ocsid 's id in OCS
    * @internal param the $entity entity in which the monitor will be created
    */
   static function importMonitor($cfg_ocs, $computers_id, $ocsservers_id, $ocsComputer, $entity)
   {
      global $DB, $CFG_GLPI;

      $already_processed = array();
      $do_clean = true;
      $m = new Monitor();
      $conn = new Computer_Item();

      $monitors = array();
      $checkserial = true;

      // First pass - check if all serial present

      foreach ($ocsComputer as $monitor) {
         // Config says import monitor with serial number only
         // Restrict SQL query ony for monitors with serial present
         if ($cfg_ocs["import_monitor"] > 2 && empty($monitor["SERIAL"])) {
            unset($monitor);
         } else {
            $monitors[] = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($monitor));
         }
      }

      if (count($monitors) > 0 && $cfg_ocs["import_monitor"] > 0
         // && ($cfg_ocs["import_monitor"] <= 2 || $checkserial)
      ) {

         foreach ($monitors as $monitor) {

            $mon = array();
            if (!empty($monitor["CAPTION"])) {
               $mon["name"] = self::encodeOcsDataInUtf8($cfg_ocs["ocs_db_utf8"], $monitor["CAPTION"]);
               $mon["monitormodels_id"] = Dropdown::importExternal('MonitorModel', $monitor["CAPTION"]);
            }
            if (empty($monitor["CAPTION"]) && !empty($monitor["MANUFACTURER"])) {
               $mon["name"] = $monitor["MANUFACTURER"];
            }
            if (empty($monitor["CAPTION"]) && !empty($monitor["TYPE"])) {
               if (!empty($monitor["MANUFACTURER"])) {
                  $mon["name"] .= " ";
               }
               $mon["name"] .= $monitor["TYPE"];
            }
            if (!empty($monitor["TYPE"])) {
               $mon["monitortypes_id"] = Dropdown::importExternal('MonitorType', $monitor["TYPE"]);
            }
            $mon["serial"] = $monitor["SERIAL"];
            //Look for a monitor with the same name (and serial if possible) already connected
            //to this computer
            $query = "SELECT `m`.`id`, `gci`.`is_deleted`
                         FROM `glpi_monitors` as `m`, `glpi_computers_items` as `gci`
                         WHERE `m`.`id` = `gci`.`items_id`
                            AND `gci`.`is_dynamic`
                            AND `computers_id`='$computers_id'
                            AND `itemtype`='Monitor'
                            AND `m`.`name`='" . $mon["name"] . "'";
            if ($cfg_ocs["import_monitor"] > 2 && !empty($mon["serial"])) {
               $query .= " AND `m`.`serial`='" . $mon["serial"] . "'";
            }
            $results = $DB->query($query);
            $id = false;
            $lock = false;
            if ($DB->numrows($results) == 1) {
               $id = $DB->result($results, 0, 'id');
               $lock = $DB->result($results, 0, 'is_deleted');
            }

            if ($id == false) {
               // Clean monitor object
               $m->reset();
               $mon["manufacturers_id"] = Dropdown::importExternal('Manufacturer', $monitor["MANUFACTURER"]);
               if ($cfg_ocs["import_monitor_comment"]) {
                  $mon["comment"] = $monitor["DESCRIPTION"];
               }
               $id_monitor = 0;

               if ($cfg_ocs["import_monitor"] == 1) {
                  //Config says : manage monitors as global
                  //check if monitors already exists in GLPI
                  $mon["is_global"] = 1;
                  $query = "SELECT `id`
                               FROM `glpi_monitors`
                               WHERE `name` = '" . $mon["name"] . "'
                                  AND `is_global` = '1' ";
                  if ($CFG_GLPI['transfers_id_auto'] < 1) {
                     $query .= " AND `entities_id` = '$entity'";
                  }
                  $result_search = $DB->query($query);

                  if ($DB->numrows($result_search) > 0) {
                     //Periph is already in GLPI
                     //Do not import anything just get periph ID for link
                     $id_monitor = $DB->result($result_search, 0, "id");
                  } else {
                     $input = $mon;
                     if ($cfg_ocs["states_id_default"] > 0) {
                        $input["states_id"] = $cfg_ocs["states_id_default"];
                     }
                     $input["entities_id"] = $entity;
                     $id_monitor = $m->add($input, array(), $cfg_ocs['history_monitor']);
                  }
               } else if ($cfg_ocs["import_monitor"] >= 2) {
                  //Config says : manage monitors as single units
                  //Import all monitors as non global.
                  $mon["is_global"] = 0;

                  // Try to find a monitor with the same serial.
                  if (!empty($mon["serial"])) {
                     $query = "SELECT `id`
                                  FROM `glpi_monitors`
                                  WHERE `serial` LIKE '%" . $mon["serial"] . "%'
                                     AND `is_global` = '0' ";
                     if ($CFG_GLPI['transfers_id_auto'] < 1) {
                        $query .= " AND `entities_id` = '$entity'";
                     }
                     $result_search = $DB->query($query);
                     if ($DB->numrows($result_search) == 1) {
                        //Monitor founded
                        $id_monitor = $DB->result($result_search, 0, "id");
                     }
                  }

                  //Search by serial failed, search by name
                  if ($cfg_ocs["import_monitor"] == 2
                     && !$id_monitor
                  ) {
                     //Try to find a monitor with no serial, the same name and not already connected.
                     if (!empty($mon["name"])) {
                        $query = "SELECT `glpi_monitors`.`id`
                                           FROM `glpi_monitors`
                                           LEFT JOIN `glpi_computers_items`
                                                ON (`glpi_computers_items`.`itemtype`='Monitor'
                                                    AND `glpi_computers_items`.`items_id`
                                                            =`glpi_monitors`.`id`)
                                           WHERE `serial` = ''
                                                 AND `name` = '" . $mon["name"] . "'
                                                       AND `is_global` = '0'
                                                       AND `glpi_computers_items`.`computers_id` IS NULL";
                        if ($CFG_GLPI['transfers_id_auto'] < 1) {
                           $query .= " AND `entities_id` = '$entity'";
                        }
                        $result_search = $DB->query($query);
                        if ($DB->numrows($result_search) == 1) {
                           $id_monitor = $DB->result($result_search, 0, "id");
                        }
                     }
                  }


                  if (!$id_monitor) {
                     $input = $mon;
                     if ($cfg_ocs["states_id_default"] > 0) {
                        $input["states_id"] = $cfg_ocs["states_id_default"];
                     }
                     $input["entities_id"] = $entity;
                     $id_monitor = $m->add($input, array(), $cfg_ocs['history_monitor']);
                  }
               } // ($cfg_ocs["import_monitor"] >= 2)

               if ($id_monitor) {
                  //Import unique : Disconnect monitor on other computer done in Connect function
                  $connID = $conn->add(array('computers_id' => $computers_id,
                                             'itemtype'     => 'Monitor',
                                             'items_id'     => $id_monitor,
                                             'is_dynamic'   => 1,
                                             'is_deleted'   => 0), array(), $cfg_ocs['history_monitor']);
                  $already_processed[] = $id_monitor;

                  //Update column "is_deleted" set value to 0 and set status to default
                  $input = array();
                  $old = new Monitor();
                  if ($old->getFromDB($id_monitor)) {
                     if ($old->fields["is_deleted"]) {
                        $input["is_deleted"] = 0;
                     }
                     if ($cfg_ocs["states_id_default"] > 0
                        && $old->fields["states_id"] != $cfg_ocs["states_id_default"]
                     ) {
                        $input["states_id"] = $cfg_ocs["states_id_default"];
                     }
                     if (empty($old->fields["name"])
                        && !empty($mon["name"])
                     ) {
                        $input["name"] = $mon["name"];
                     }
                     if (empty($old->fields["serial"])
                        && !empty($mon["serial"])
                     ) {
                        $input["serial"] = $mon["serial"];
                     }
                     $input["id"] = $id_monitor;
                     if (count($input)) {
                        $input['entities_id'] = $entity;
                        $m->update($input, $cfg_ocs['history_monitor']);
                     }
                  }
               }
            } else {
               $already_processed[] = $id;
            }
            // } // end foreach
            // if ($cfg_ocs["import_monitor"]<=2 || $checkserial){
            //Look for all monitors, not locked, not linked to the computer anymore
            $query = "SELECT `id`
                         FROM `glpi_computers_items`
                         WHERE `itemtype`='Monitor'
                            AND `computers_id`='$computers_id'
                            AND `is_dynamic`
                            AND `is_deleted`='0'";
            if (!empty($already_processed)) {
               $query .= "AND `items_id` NOT IN (" . implode(',', $already_processed) . ")";
            }

            foreach ($DB->request($query) as $data) {
               // Delete all connexions
               //Get OCS configuration
               $ocs_config = self::getConfig($ocsservers_id);

               //Get the management mode for this device
               $mode = self::getDevicesManagementMode($ocs_config, 'Monitor');
               $decoConf = $ocs_config["deconnection_behavior"];

               //Change status if :
               // 1 : the management mode IS NOT global
               // 2 : a deconnection's status have been defined
               // 3 : unique with serial
               if (($mode >= 2) && (strlen($decoConf) > 0)) {

                  //Delete periph from glpi
                  if ($decoConf == "delete") {
                     $query = "DELETE
                         FROM `glpi_computers_items`
                         WHERE `id`='" . $data['id'] . "'";
                     $result = $DB->query($query);
                     //Put periph in dustbin
                  } else if ($decoConf == "trash") {
                     $query = "UPDATE
                         `glpi_computers_items`
                        SET `is_deleted` = 1
                         WHERE `id`='" . $data['id'] . "'";
                     $result = $DB->query($query);
                  }
               }
            }
         }
      }
   }

   /**
    *
    * Import printers from OCS
    * @since 1.0
    * @param $cfg_ocs OCSNG mode configuration
    * @param $computers_id computer's id in GLPI
    * @param $ocsservers_id OCS server id
    * @param $ocsComputer
    * @param $entity the entity in which the printer will be created
    * @internal param computer $ocsid 's id in OCS
    */
   static function importPrinter($cfg_ocs, $computers_id, $ocsservers_id, $ocsComputer, $entity)
   {
      global $DB, $CFG_GLPI;

      $already_processed = array();

      $conn = new Computer_Item();
      $p = new Printer();

      foreach ($ocsComputer as $printer) {
         $printer = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($printer));
         $print = array();
         // TO TEST : PARSE NAME to have real name.
         $print['name'] = self::encodeOcsDataInUtf8($cfg_ocs["ocs_db_utf8"], $printer['NAME']);

         if (empty($print["name"])) {
            $print["name"] = $printer["DRIVER"];
         }

         $management_process = $cfg_ocs["import_printer"];

         //Params for the dictionnary
         $params['name'] = $print['name'];
         $params['manufacturer'] = "";
         $params['DRIVER'] = $printer['DRIVER'];
         $params['PORT'] = $printer['PORT'];

         if (!empty($print["name"])) {
            $rulecollection = new RuleDictionnaryPrinterCollection();
            $res_rule = Toolbox::addslashes_deep($rulecollection->processAllRules(Toolbox::stripslashes_deep($params), array(), array()));

            if (!isset($res_rule["_ignore_import"]) || !$res_rule["_ignore_import"]) {

               foreach ($res_rule as $key => $value) {
                  if ($value != '' && $value[0] != '_') {
                     $print[$key] = $value;
                  }
               }

               if (isset($res_rule['is_global'])) {
                  if (!$res_rule['is_global']) {
                     $management_process = 2;
                  } else {
                     $management_process = 1;
                  }
               }

               //Look for a printer with the same name (and serial if possible) already connected
               //to this computer
               $query = "SELECT `p`.`id`, `gci`.`is_deleted`
                                  FROM `glpi_printers` as `p`, `glpi_computers_items` as `gci`
                                  WHERE `p`.`id` = `gci`.`items_id`
                                     AND `gci`.`is_dynamic`
                                     AND `computers_id`='$computers_id'
                                     AND `itemtype`='Printer'
                                     AND `p`.`name`='" . $print["name"] . "'";
               $results = $DB->query($query);
               $id = false;
               $lock = false;
               if ($DB->numrows($results) > 0) {
                  $id = $DB->result($results, 0, 'id');
                  $lock = $DB->result($results, 0, 'is_deleted');
               }

               if (!$id) {
                  // Clean printer object
                  $p->reset();
                  $print["comment"] = $printer["PORT"] . "\r\n" . $printer["DRIVER"];
                  self::analizePrinterPorts($print, $printer["PORT"]);
                  $id_printer = 0;

                  if ($management_process == 1) {
                     //Config says : manage printers as global
                     //check if printers already exists in GLPI
                     $print["is_global"] = MANAGEMENT_GLOBAL;
                     $query = "SELECT `id`
                                         FROM `glpi_printers`
                                         WHERE `name` = '" . $print["name"] . "'
                                            AND `is_global` = '1' ";
                     if ($CFG_GLPI['transfers_id_auto'] < 1) {
                        $query .= " AND `entities_id` = '$entity'";
                     }
                     $result_search = $DB->query($query);

                     if ($DB->numrows($result_search) > 0) {
                        //Periph is already in GLPI
                        //Do not import anything just get periph ID for link
                        $id_printer = $DB->result($result_search, 0, "id");
                        $already_processed[] = $id_printer;
                     } else {
                        $input = $print;

                        if ($cfg_ocs["states_id_default"] > 0) {
                           $input["states_id"] = $cfg_ocs["states_id_default"];
                        }
                        $input["entities_id"] = $entity;
                        $id_printer = $p->add($input, array(), $cfg_ocs['history_printer']);
                     }
                  } else if ($management_process == 2) {
                     //Config says : manage printers as single units
                     //Import all printers as non global.
                     $input = $print;
                     $input["is_global"] = MANAGEMENT_UNITARY;

                     if ($cfg_ocs["states_id_default"] > 0) {
                        $input["states_id"] = $cfg_ocs["states_id_default"];
                     }
                     $input["entities_id"] = $entity;
                     $input['is_dynamic'] = 1;
                     $id_printer = $p->add($input, array(), $cfg_ocs['history_printer']);
                  }

                  if ($id_printer) {
                     $already_processed[] = $id_printer;
                     $conn = new Computer_Item();
                     $connID = $conn->add(array('computers_id' => $computers_id,
                                                'itemtype'     => 'Printer',
                                                'items_id'     => $id_printer,
                                                'is_dynamic'   => 1), array(), $cfg_ocs['history_printer']);
                     //Update column "is_deleted" set value to 0 and set status to default
                     $input = array();
                     $input["id"] = $id_printer;
                     $input["is_deleted"] = 0;
                     $input["entities_id"] = $entity;

                     if ($cfg_ocs["states_id_default"] > 0) {
                        $input["states_id"] = $cfg_ocs["states_id_default"];
                     }
                     $p->update($input, $cfg_ocs['history_printer']);
                  }
               } else {
                  $already_processed[] = $id;
               }
            }
         }
      }

      //Look for all printers, not locked, not linked to the computer anymore
      $query = "SELECT `id`
                    FROM `glpi_computers_items`
                    WHERE `itemtype`='Printer'
                       AND `computers_id`='$computers_id'
                       AND `is_dynamic`
                       AND `is_deleted`='0'";
      if (!empty($already_processed)) {
         $query .= "AND `items_id` NOT IN (" . implode(',', $already_processed) . ")";
      }
      foreach ($DB->request($query) as $data) {
         // Delete all connexions
         //Get OCS configuration
         $ocs_config = self::getConfig($ocsservers_id);

         //Get the management mode for this device
         $mode = self::getDevicesManagementMode($ocs_config, 'Printer');
         $decoConf = $ocs_config["deconnection_behavior"];

         //Change status if :
         // 1 : the management mode IS NOT global
         // 2 : a deconnection's status have been defined
         // 3 : unique with serial
         if (($mode >= 2) && (strlen($decoConf) > 0)) {

            //Delete periph from glpi
            if ($decoConf == "delete") {
               $query = "DELETE
                FROM `glpi_computers_items`
                WHERE `id`='" . $data['id'] . "'";
               $DB->query($query);
               //Put periph in dustbin
            } else if ($decoConf == "trash") {
               $query = "UPDATE
                `glpi_computers_items`
                SET `is_deleted` = 1
                WHERE `id`='" . $data['id'] . "'";
               $DB->query($query);
            }
         }
         // foreach ($DB->request($query) as $data){
         // Delete all connexions
         // $conn->delete(array('id'             => $data['id'],
         // '_ocsservers_id' => $ocsservers_id), true);
      }
   }

   /**
    *
    * Import peripherals from OCS
    * @since 1.0
    * @param $cfg_ocs OCSNG mode configuration
    * @param $computers_id computer's id in GLPI
    * @param $ocsservers_id OCS server id
    * @param $ocsComputer
    * @param $entity the entity in which the peripheral will be created
    * @internal param computer $ocsid 's id in OCS
    */
   static function importPeripheral($cfg_ocs, $computers_id, $ocsservers_id, $ocsComputer, $entity)
   {
      global $DB, $CFG_GLPI;

      $already_processed = array();
      $p = new Peripheral();
      $conn = new Computer_Item();

      $peripherals = array();

      foreach ($ocsComputer as $peripheral) {

         if ($peripheral["CAPTION"] !== '') {
            $peripherals[] = $peripheral;
         }
      }
      if (count($peripherals) > 0) {
         foreach ($peripherals as $peripheral) {
            $peripheral = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($peripheral));
            $periph = array();
            $periph["name"] = self::encodeOcsDataInUtf8($cfg_ocs["ocs_db_utf8"], $peripheral["CAPTION"]);
            //Look for a monitor with the same name (and serial if possible) already connected
            //to this computer
            $query = "SELECT `p`.`id`, `gci`.`is_deleted`
                                       FROM `glpi_printers` as `p`, `glpi_computers_items` as `gci`
                                       WHERE `p`.`id` = `gci`.`items_id`
                                       AND `gci`.`is_dynamic`
                                       AND `computers_id`='$computers_id'
                                       AND `itemtype`='Peripheral'
                                       AND `p`.`name`='" . $periph["name"] . "'";
            $results = $DB->query($query);
            $id = false;
            $lock = false;
            if ($DB->numrows($results) > 0) {
               $id = $DB->result($results, 0, 'id');
               $lock = $DB->result($results, 0, 'is_deleted');
            }
            if (!$id) {
               // Clean peripheral object
               $p->reset();
               if ($peripheral["MANUFACTURER"] != "NULL") {
                  $periph["brand"] = self::encodeOcsDataInUtf8($cfg_ocs["ocs_db_utf8"], $peripheral["MANUFACTURER"]);
               }
               if ($peripheral["INTERFACE"] != "NULL") {
                  $periph["comment"] = self::encodeOcsDataInUtf8($cfg_ocs["ocs_db_utf8"], $peripheral["INTERFACE"]);
               }
               $periph["peripheraltypes_id"] = Dropdown::importExternal('PeripheralType', $peripheral["TYPE"]);
               $id_periph = 0;
               if ($cfg_ocs["import_periph"] == 1) {
                  //Config says : manage peripherals as global
                  //check if peripherals already exists in GLPI
                  $periph["is_global"] = 1;
                  $query = "SELECT `id`
                                           FROM `glpi_peripherals`
                                           WHERE `name` = '" . $periph["name"] . "'
                                           AND `is_global` = '1' ";
                  if ($CFG_GLPI['transfers_id_auto'] < 1) {
                     $query .= " AND `entities_id` = '$entity'";
                  }
                  $result_search = $DB->query($query);
                  if ($DB->numrows($result_search) > 0) {
                     //Periph is already in GLPI
                     //Do not import anything just get periph ID for link
                     $id_periph = $DB->result($result_search, 0, "id");
                  } else {
                     $input = $periph;
                     if ($cfg_ocs["states_id_default"] > 0) {
                        $input["states_id"] = $cfg_ocs["states_id_default"];
                     }
                     $input["entities_id"] = $entity;
                     $id_periph = $p->add($input, array(), $cfg_ocs['history_peripheral']);
                  }
               } else if ($cfg_ocs["import_periph"] == 2) {
                  //Config says : manage peripherals as single units
                  //Import all peripherals as non global.
                  $input = $periph;
                  $input["is_global"] = 0;
                  if ($cfg_ocs["states_id_default"] > 0) {
                     $input["states_id"] = $cfg_ocs["states_id_default"];
                  }
                  $input["entities_id"] = $entity;
                  $id_periph = $p->add($input, array(), $cfg_ocs['history_peripheral']);
               }

               if ($id_periph) {
                  $already_processed[] = $id_periph;
                  $conn = new Computer_Item();
                  if ($connID = $conn->add(array('computers_id' => $computers_id,
                                                 'itemtype'     => 'Peripheral',
                                                 'items_id'     => $id_periph,
                                                 'is_dynamic'   => 1), array(), $cfg_ocs['history_peripheral'])
                  ) {
                     //Update column "is_deleted" set value to 0 and set status to default
                     $input = array();
                     $input["id"] = $id_periph;
                     $input["is_deleted"] = 0;
                     $input["entities_id"] = $entity;
                     if ($cfg_ocs["states_id_default"] > 0) {
                        $input["states_id"] = $cfg_ocs["states_id_default"];
                     }
                     $p->update($input, $cfg_ocs['history_peripheral']);
                  }
               }
            } else {
               $already_processed[] = $id;
            }
         }
      }

      //Look for all peripherals, not locked, not linked to the computer anymore
      $query = "SELECT `id`
                      FROM `glpi_computers_items`
                      WHERE `itemtype`='Peripheral'
                         AND `computers_id`='$computers_id'
                         AND `is_dynamic`
                         AND `is_deleted`='0'";
      if (!empty($already_processed)) {
         $query .= "AND `items_id` NOT IN (" . implode(',', $already_processed) . ")";
      }
      foreach ($DB->request($query) as $data) {
         // Delete all connexions
         //Get OCS configuration
         $ocs_config = self::getConfig($ocsservers_id);

         //Get the management mode for this device
         $mode = self::getDevicesManagementMode($ocs_config, 'Peripheral');
         $decoConf = $ocs_config["deconnection_behavior"];

         //Change status if :
         // 1 : the management mode IS NOT global
         // 2 : a deconnection's status have been defined
         // 3 : unique with serial
         if (($mode >= 2) && (strlen($decoConf) > 0)) {

            //Delete periph from glpi
            if ($decoConf == "delete") {
               $query = "DELETE
             FROM `glpi_computers_items`
             WHERE `id`='" . $data['id'] . "'";
               $DB->query($query);
               //Put periph in dustbin
            } else if ($decoConf == "trash") {
               $query = "UPDATE
             `glpi_computers_items`
             SET `is_deleted` = 1
             WHERE `id`='" . $data['id'] . "'";
               $DB->query($query);
            }
         }
         // foreach ($DB->request($query) as $data){
         // Delete all connexions
         // $conn->delete(array('id'             => $data['id'],
         // '_ocsservers_id' => $ocsservers_id), true);
      }
   }

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::getSpecificMassiveActions()
    * @param null $checkitem
    * @return an
    */
   function getSpecificMassiveActions($checkitem = NULL)
   {

      $actions = parent::getSpecificMassiveActions($checkitem);

      return $actions;
   }

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
    * @param MassiveAction $ma
    * @return bool|false
    */
   static function showMassiveActionsSubForm(MassiveAction $ma)
   {

      switch ($ma->getAction()) {
         case 'plugin_ocsinventoryng_force_ocsng_update':
            echo "&nbsp;" .
               Html::submit(_x('button', 'Post'), array('name' => 'massiveaction'));
            return true;
         case 'plugin_ocsinventoryng_lock_ocsng_field':
            $fields['all'] = __('All');
            $fields += self::getLockableFields();
            Dropdown::showFromArray("field", $fields);
            echo "&nbsp;" .
               Html::submit(_x('button', 'Post'), array('name' => 'massiveaction'));
            return true;
         case 'plugin_ocsinventoryng_unlock_ocsng_field':
            $fields['all'] = __('All');
            $fields += self::getLockableFields();
            Dropdown::showFromArray("field", $fields);
            echo "&nbsp;" .
               Html::submit(_x('button', 'Post'), array('name' => 'massiveaction'));
            return true;
      }
      return parent::showMassiveActionsSubForm($ma);
   }

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
    * @param MassiveAction $ma
    * @param CommonDBTM $item
    * @param array $ids
    * @return nothing|void
    */
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
   {
      global $DB;

      switch ($ma->getAction()) {
         case "plugin_ocsinventoryng_force_ocsng_update":
            $input = $ma->getInput();

            foreach ($ids as $id) {

               $query = "SELECT `plugin_ocsinventoryng_ocsservers_id`, `id`
                               FROM `glpi_plugin_ocsinventoryng_ocslinks`
                               WHERE `computers_id` = '" . $id . "'";
               $result = $DB->query($query);
               if ($DB->numrows($result) == 1) {
                  $data = $DB->fetch_assoc($result);

                  $cfg_ocs = self::getConfig($data['plugin_ocsinventoryng_ocsservers_id']);
                  $dohistory = (isset($cfg_ocs['dohistory']) ? $cfg_ocs['dohistory'] : false);

                  if (self::updateComputer($data['id'], $data['plugin_ocsinventoryng_ocsservers_id'], $dohistory, 1)) {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                  }
               } else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
               }
            }

            return;

         case "plugin_ocsinventoryng_lock_ocsng_field" :
            $input = $ma->getInput();
            $fields = self::getLockableFields();

            if ($_POST['field'] == 'all' || isset($fields[$_POST['field']])) {
               foreach ($ids as $id) {

                  if ($_POST['field'] == 'all') {
                     if (self::addToOcsArray($id, array_flip($fields), "computer_update")) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     }
                  } else {
                     if (self::addToOcsArray($id, array($_POST['field']), "computer_update")) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     }
                  }
               }
            }

            return;

         case "plugin_ocsinventoryng_unlock_ocsng_field" :
            $input = $ma->getInput();
            $fields = self::getLockableFields();
            if ($_POST['field'] == 'all' || isset($fields[$_POST['field']])) {
               foreach ($ids as $id) {

                  if ($_POST['field'] == 'all') {
                     if (self::replaceOcsArray($id, array(), "computer_update")) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     }
                  } else {
                     if (self::deleteInOcsArray($id, $_POST['field'], "computer_update", true)) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     }
                  }
               }
            }

            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }

   /**
    * @internal param $width
    */
   function showSystemInformations()
   {

      $ocsServers = getAllDatasFromTable('glpi_plugin_ocsinventoryng_ocsservers', "`is_active`='1'");
      if (!empty($ocsServers)) {
         echo "\n<tr class='tab_bg_2'><th>OCS Inventory NG</th></tr>\n";

         $msg = '';
         foreach ($ocsServers as $ocsServer) {

            echo "<tr class='tab_bg_1'><td>";
            $msg = __('Host', 'ocsinventoryng') . ": " . $ocsServer['ocs_db_host'] . "";
            $msg .= "<br>" . __('Connection') . ": " . (self::checkOCSconnection($ocsServer['id']) ? "Ok" : "KO");
            $msg .= "<br>" . __('Use the OCSNG software dictionary', 'ocsinventoryng') . ": " .
               ($ocsServer['use_soft_dict'] ? 'Yes' : 'No');
            echo $msg;
            echo "</td></tr>";
         }
      }
   }

}
