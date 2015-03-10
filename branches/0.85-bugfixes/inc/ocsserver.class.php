<?php
/*
 * @version $Id: HEADER 15930 2012-12-15 11:10:55Z tsmr $
 -------------------------------------------------------------------------
 Ocsinventoryng plugin for GLPI
 Copyright (C) 2012-2013 by the ocsinventoryng plugin Development Team.

 https://forge.indepnet.net/projects/ocsinventoryng
 -------------------------------------------------------------------------

 LICENSE

 This file is part of ocsinventoryng.

 Ocsinventoryng plugin is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Ocsinventoryng plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with ocsinventoryng. If not, see <http://www.gnu.org/licenses/>.
 -------------------------------------------------------------------------- */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


/// OCS config class
class PluginOcsinventoryngOcsServer extends CommonDBTM {

   static $types = array('Computer');
   
   static $rightname = "plugin_ocsinventoryng";
   
   // From CommonDBTM
   public $dohistory = true;

   // Connection types
   const CONN_TYPE_DB = 0;
   const CONN_TYPE_SOAP = 1;

   const OCS_VERSION_LIMIT    = 4020;
   const OCS1_3_VERSION_LIMIT = 5004;
   const OCS2_VERSION_LIMIT   = 6000;

   // Class constants - import_ management
   const FIELD_SEPARATOR = '$$$$$';
   const IMPORT_TAG_070  = '_version_070_';
   const IMPORT_TAG_072  = '_version_072_';
   const IMPORT_TAG_078  = '_version_078_';

   // TODO use PluginOcsinventoryngOcsClient constants
   // Class constants - OCSNG Flags on Checksum
   const HARDWARE_FL          = 0;
   const BIOS_FL              = 1;
   const MEMORIES_FL          = 2;
   // not used const SLOTS_FL       = 3;
   const REGISTRY_FL          = 4;
   // not used const CONTROLLERS_FL = 5;
   const MONITORS_FL          = 6;
   const PORTS_FL             = 7;
   const STORAGES_FL          = 8;
   const DRIVES_FL            = 9;
   const INPUTS_FL            = 10;
   const MODEMS_FL            = 11;
   const NETWORKS_FL          = 12;
   const PRINTERS_FL          = 13;
   const SOUNDS_FL            = 14;
   const VIDEOS_FL            = 15;
   const SOFTWARES_FL         = 16;
   const VIRTUALMACHINES_FL   = 17;
   const MAX_CHECKSUM         = 262143;

   // Class constants - Update result
   const COMPUTER_IMPORTED       = 0; //Computer is imported in GLPI
   const COMPUTER_SYNCHRONIZED   = 1; //Computer is synchronized
   const COMPUTER_LINKED         = 2; //Computer is linked to another computer already in GLPI
   const COMPUTER_FAILED_IMPORT  = 3; //Computer cannot be imported because it matches none of the rules
   const COMPUTER_NOTUPDATED     = 4; //Computer should not be updated, nothing to do
   const COMPUTER_NOT_UNIQUE     = 5; //Computer import is refused because it's not unique
   const COMPUTER_LINK_REFUSED   = 6; //Computer cannot be imported because a rule denies its import

   const LINK_RESULT_IMPORT    = 0;
   const LINK_RESULT_NO_IMPORT = 1;
   const LINK_RESULT_LINK      = 2;


   static function getTypeName($nb=0) {
      return _n('OCSNG server', 'OCSNG servers', $nb,'ocsinventoryng');
   }



   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               //If connection to the OCS DB  is ok, and all rights are ok too
               $ong[1] = __('Test');
               if (self::checkOCSconnection($item->getID())
               && self::checkVersion($item->getID())
               && self::checkTraceDeleted($item->getID())) {
                  $ong[2] = __('Import options', 'ocsinventoryng');
                  $ong[3] = __('General information', 'ocsinventoryng');
               }
               if ($item->getField('ocs_url')) {
                  $ong[4] = __('OCSNG console', 'ocsinventoryng');
               }

               return $ong;
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      
      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1 :
               $item->showDBConnectionStatus($item->getID());
               break;

            case 2 :
               $item->ocsFormImportOptions($item->getID());
               break;

            case 3 :
               $item->ocsFormConfig($item->getID());
               break;

            case 4 :
               self::showOcsReportsConsole($item->getID());
               break;

         }
      }
      return true;
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('PluginOcsinventoryngConfig', $ong, $options);
      $this->addStandardTab('PluginOcsinventoryngOcslink', $ong, $options);
      $this->addStandardTab('PluginOcsinventoryngRegistryKey', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);
      return $ong;
   }


   function getSearchOptions() {

      $tab                       = array();

      $tab['common']             = self::getTypeName();

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Name');
      $tab[1]['datatype']        = 'itemlink';
      $tab[1]['itemlink_type']   = $this->getType();
      $tab[1]['massiveaction']   = false;

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'id';
      $tab[2]['name']            = __('ID');
      $tab[2]['massiveaction']   = false;

      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'ocs_db_host';
      $tab[3]['name']            = __('Server');

      $tab[6]['table']           = $this->getTable();
      $tab[6]['field']           = 'is_active';
      $tab[6]['name']            = __('Active');
      $tab[6]['datatype']        = 'bool';

      $tab[19]['table']          = $this->getTable();
      $tab[19]['field']          = 'date_mod';
      $tab[19]['name']           = __('Last update');
      $tab[19]['datatype']       = 'datetime';
      $tab[19]['massiveaction']  = false;

      $tab[16]['table']          = $this->getTable();
      $tab[16]['field']          = 'comment';
      $tab[16]['name']           = __('Comments');
      $tab[16]['datatype']       = 'text';

      $tab[17]['table']          = $this->getTable();
      $tab[17]['field']          = 'use_massimport';
      $tab[17]['name']           = __('Expert sync mode', 'ocsinventoryng');
      $tab[17]['datatype']       = 'bool';

      $tab[18]['table']          = $this->getTable();
      $tab[18]['field']          = 'ocs_db_utf8';
      $tab[18]['name']           = __('Database in UTF8', 'ocsinventoryng');
      $tab[18]['datatype']       = 'bool';

      return $tab;
   }

   /**
    * Print ocs menu
    *
    * @param $plugin_ocsinventoryng_ocsservers_id Integer : Id of the ocs config
    *
    * @return Nothing (display)
    **/
   static function ocsMenu($plugin_ocsinventoryng_ocsservers_id) {
      global $CFG_GLPI, $DB;

      $name = "";
      $ocsservers = array();
      echo "<div class='center'>";
      echo "<img src='" . $CFG_GLPI["root_doc"]."/plugins/ocsinventoryng/pics/ocsinventoryng.png' ".
      "alt='OCS Inventory NG' title='OCS Inventory NG'>";
      echo "</div>";
      $numberActiveServers = countElementsInTable('glpi_plugin_ocsinventoryng_ocsservers',
         "`is_active`='1'");
      if ($numberActiveServers > 0) {
         echo "<form action=\"".$CFG_GLPI['root_doc']."/plugins/ocsinventoryng/front/ocsng.php\"
                method='post'>";
         echo "<div class='center'><table class='tab_cadre' width='40%'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Choice of an OCSNG server', 'ocsinventoryng').
         "</th></tr>\n";

         echo "<tr class='tab_bg_2'><td class='center'>" . __('Name'). "</td>";
         echo "<td class='center'>";
         $query = "SELECT `glpi_plugin_ocsinventoryng_ocsservers`.`id`
                   FROM `glpi_plugin_ocsinventoryng_ocsservers_profiles`
                   LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers`
                      ON `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id` = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
                   WHERE `profiles_id`= ".$_SESSION["glpiactiveprofile"]['id']."
                   ORDER BY `name` ASC";
         foreach($DB->request($query) as $data) {
            $ocsservers[] = $data['id'];
         }
         Dropdown::show('PluginOcsinventoryngOcsServer',
                        array("condition"           => "`id` IN ('".implode("','",$ocsservers)."')",
                              "value"               => $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
                              "on_change"           => "this.form.submit()",
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
              WHERE `glpi_plugin_ocsinventoryng_ocsservers`.`id` = '".$plugin_ocsinventoryng_ocsservers_id."' AND `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`profiles_id`= ".$_SESSION["glpiactiveprofile"]['id']."";
      $result = $DB->query($sql);
      $isactive = 0;
      if ($DB->numrows($result) > 0) {
         $datas = $DB->fetch_array($result);
         $name  = " : " . $datas["name"];
         $isactive = $datas["is_active"];
      }

      $usemassimport = self::useMassImport();
      
      echo "<div class='center'><table class='tab_cadre' width='40%'>";
      echo "<tr><th colspan='".($usemassimport?4:2)."'>";
      printf(__('%1$s %2$s'), __('OCSNG server', 'ocsinventoryng'), $name);
      echo "</th></tr>";


      if (Session::haveRight("plugin_ocsinventoryng", UPDATE)) {

         //config server
         
         if ($isactive) {
            echo "<tr class='tab_bg_1'><td class='center b' colspan='2'>
                  <a href='ocsserver.form.php?id=$plugin_ocsinventoryng_ocsservers_id'>
                   <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/ocsserver.png' ".
            "alt='".__s("Configuration of OCSNG server", 'ocsinventoryng')."' ".
            "title=\"".__s("Configuration of OCSNG server", 'ocsinventoryng')."\">
                   <br>".sprintf(__('Configuration of OCSNG server %s', 'ocsinventoryng'),
            $name)."
                  </a></td>";

            if ($usemassimport) {
               //config massimport
               echo "<td class='center b' colspan='2'>
                     <a href='config.form.php'>
                      <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/synchro.png' ".
               "alt='".__s("Automatic synchronization's configuration", 'ocsinventoryng')."' ".
               "title=\"".__s("Automatic synchronization's configuration", 'ocsinventoryng')."\">
                        <br>".__("Automatic synchronization's configuration", 'ocsinventoryng')."
                     </a></td>";
            }
            echo "</tr>\n";

            //manual import
            echo "<tr class='tab_bg_1'><td class='center b' colspan='2'>
                  <a href='ocsng.import.php'>
                   <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/import.png' ".
            "alt='".__s('Import new computers', 'ocsinventoryng')."' ".
            "title=\"".__s('Import new computers', 'ocsinventoryng')."\">
                     <br>".__('Import new computers', 'ocsinventoryng')."
                  </a></td>";
            if ($usemassimport) {
               //threads
               echo "<td class='center b' colspan='2'>
                     <a href='thread.php'>
                      <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/thread.png' ".
               "alt='".__s('Scripts execution of automatic actions', 'ocsinventoryng'). "' ".
               "title=\"" . __s('Scripts execution of automatic actions', 'ocsinventoryng') . "\">
                        <br>".__('Scripts execution of automatic actions', 'ocsinventoryng')."
                     </a></td>";
            }
            echo "</tr>\n";

            //manual synchro
            echo "<tr class='tab_bg_1'><td class='center b' colspan='2'>
                  <a href='ocsng.sync.php'>
                   <img src='" . $CFG_GLPI["root_doc"]."/plugins/ocsinventoryng/pics/synchro1.png' ".
            "alt='" .__s('Synchronize computers already imported', 'ocsinventoryng'). "' ".
            "title=\"" .__s('Synchronize computers already imported', 'ocsinventoryng'). "\">
                     <br>".__('Synchronize computers already imported', 'ocsinventoryng')."
                  </a></td>";
            if ($usemassimport) {
               //host imported by thread
               echo "<td class='center b' colspan='2'>
                     <a href='detail.php'>
                      <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/detail.png' ".
               "alt='" .__s('Computers imported by automatic actions', 'ocsinventoryng'). "' ".
               "title=\"" .__s('Computers imported by automatic actions', 'ocsinventoryng'). "\">
                        <br>".__('Computers imported by automatic actions', 'ocsinventoryng')."
                     </a></td>";
            }
            echo "</tr>\n";

            //link
            echo "<tr class='tab_bg_1'><td class='center b' colspan='2'>
                  <a href='ocsng.link.php'>
                   <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/link.png' ".
            "alt='" .__s('Link new OCSNG computers to existing GLPI computers',
               'ocsinventoryng'). "' ".
            "title=\"" .__s('Link new OCSNG computers to existing GLPI computers',
               'ocsinventoryng'). "\">
                     <br>".__('Link new OCSNG computers to existing GLPI computers', 'ocsinventoryng')."
                  </a></td>";
               
               
            if ($usemassimport) {
               //host not imported by thread
               echo "<td class='center b' colspan='2'>
                     <a href='notimportedcomputer.php'>
                      <img src='" . $CFG_GLPI["root_doc"]."/plugins/ocsinventoryng/pics/notimported.png' ".
               "alt='" .__s('Computers not imported by automatic actions', 'ocsinventoryng'). "' ".
               "title=\"" . __s('Computers not imported by automatic actions', 'ocsinventoryng'). "\" >
                        <br>".__('Computers not imported by automatic actions', 'ocsinventoryng')."
                     </a></td>";
            }
            echo "</tr>\n";
         } else {
            echo "<tr class='tab_bg_2'><td class='center red' colspan='2'>";
            _e('The selected server is not active. Import and synchronisation is not available', 'ocsinventoryng');
            echo "</td></tr>\n";
         }
      }

      if (Session::haveRight("plugin_ocsinventoryng_clean", READ) && $isactive) {
         echo "<tr class='tab_bg_1'><td class='center b' colspan='".($usemassimport?4:2)."'>
            <a href='deleted_equiv.php'>
                <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/trash.png' ".
                            "alt='" .__s('Clean OCSNG deleted computers', 'ocsinventoryng'). "' ".
                            "title=\"" .__s('Clean OCSNG deleted computers', 'ocsinventoryng'). "\" >
                  <br>".__('Clean OCSNG deleted computers', 'ocsinventoryng')."
               </a></td>";
         echo "<tr class='tab_bg_1'><td class='center b' colspan='".($usemassimport?4:2)."'>
               <a href='ocsng.clean.php'>
                <img src='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/pics/clean.png' ".
         "alt='" .__s('Clean links between GLPI and OCSNG', 'ocsinventoryng'). "' ".
         "title=\"" .__s('Clean links between GLPI and OCSNG', 'ocsinventoryng'). "\" >
                  <br>".__('Clean links between GLPI and OCSNG', 'ocsinventoryng')."
               </a></td><tr>";
      }
      echo "</table></div>";
   }


   /**
    * Print ocs config form
    *
    * @param $target form target
    * @param $ID Integer : Id of the ocs config
    *
    * @return Nothing (display)
    **/
   function ocsFormConfig($ID) {

      if (!Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
         return false;
      }
      $this->getFromDB($ID);
      echo "<div class='center'>";
      echo "<form name='formconfig' id='formconfig' action='".Toolbox::getItemTypeFormURL("PluginOcsinventoryngOcsServer")."' method='post'>";
      echo "<table class='tab_cadre_fixe'>\n";
      echo "<tr><th colspan ='2'>";
      _e('All');
      
      echo $JS = <<<JAVASCRIPT
         <script type='text/javascript'>
            function form_init_all(form, value) {
               var selects = $("form[id='formconfig'] select");

               $.each(selects, function(index, select){
                  if (select.name != "import_otherserial"
                        && select.name != "import_location"
                           && select.name != "import_group"
                              && select.name != "import_contact_num"
                                 && select.name != "import_network") {
                    $(select).select2('val', value);
                  }
               });
            }
         </script>
JAVASCRIPT;
      Dropdown::showYesNo('init_all', 0, -1, array(
            'width' => '10%',
            'on_change' => "form_init_all(this.form, this.selectedIndex);"
            ));
      echo "</th><th></th></tr>";
      echo "<tr>
      <th><input type='hidden' name='id' value='$ID'>".__('General information', 'ocsinventoryng').
      "<br><span style='color:red;'>".__('Warning : the import entity rules depends on selected fields', 'ocsinventoryng')."</span></th>\n";
      echo "<th>"._n('Component', 'Components', 2) ."</th>\n";
      echo "<th>" . __('OCSNG administrative information', 'ocsinventoryng') . "</th></tr>\n";

      echo "<tr class='tab_bg_2'>\n";
      echo "<td class='top'>\n";

      echo "<table width='100%'>";
      echo "<tr class='tab_bg_2'><td class='center'>" . __('Name') . "</td>\n<td width='25%'>";
      Dropdown::showYesNo("import_general_name", $this->fields["import_general_name"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Operating system') . "</td>\n<td>";
      Dropdown::showYesNo("import_general_os", $this->fields["import_general_os"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>".__('Serial of the operating system')."</td>\n<td>";
      Dropdown::showYesNo("import_os_serial", $this->fields["import_os_serial"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Serial number') . "</td>\n<td>";
      Dropdown::showYesNo("import_general_serial", $this->fields["import_general_serial"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Depends on Bios import', 'ocsinventoryng')));
      echo "&nbsp;</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Model') . "</td>\n<td>";
      Dropdown::showYesNo("import_general_model", $this->fields["import_general_model"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Depends on Bios import', 'ocsinventoryng')));
      echo "&nbsp;</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>" . _n('Manufacturer', 'Manufacturers', 1) . "</td>\n<td>";
      Dropdown::showYesNo("import_general_manufacturer",
      $this->fields["import_general_manufacturer"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Depends on Bios import', 'ocsinventoryng')));
      echo "&nbsp;</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Type') . "</td>\n<td>";
      Dropdown::showYesNo("import_general_type", $this->fields["import_general_type"]);
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Depends on Bios import', 'ocsinventoryng')));
      echo "&nbsp;</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Domain') . "</td>\n<td>";
      Dropdown::showYesNo("import_general_domain", $this->fields["import_general_domain"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Alternate username') . "</td>\n<td>";
      Dropdown::showYesNo("import_general_contact", $this->fields["import_general_contact"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Comments') . "</td>\n<td>";
      Dropdown::showYesNo("import_general_comment", $this->fields["import_general_comment"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('IP') . "</td>\n<td>";
      Dropdown::showYesNo("import_ip", $this->fields["import_ip"]);
      echo "</td></tr>\n";
      if (self::checkOCSconnection($ID) && self::checkVersion($ID)) {
         echo "<tr class='tab_bg_2'><td class='center'>" . __('UUID') . "</td>\n<td>";
         Dropdown::showYesNo("import_general_uuid", $this->fields["import_general_uuid"]);
         echo "</td></tr>\n";
      } else {
         echo "<tr class='tab_bg_2'><td class='center'>";
         echo "<input type='hidden' name='import_general_uuid' value='0'>";
         echo "</td></tr>\n";
      }

      echo "</table>";

      echo "</td>\n";

      echo "<td class='tab_bg_2 top'>\n";

      echo "<table width='100%'>";
      echo "<tr class='tab_bg_2'><td class='center'>" . __('Processor') . "</td>\n<td width='55%'>";
      Dropdown::showYesNo("import_device_processor", $this->fields["import_device_processor"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Memory') . "</td>\n<td>";
      Dropdown::showYesNo("import_device_memory", $this->fields["import_device_memory"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Hard drive') . "</td>\n<td>";
      Dropdown::showYesNo("import_device_hdd", $this->fields["import_device_hdd"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Network card') . "</td>\n<td>";
      Dropdown::showYesNo("import_device_iface", $this->fields["import_device_iface"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Graphics card') . "</td>\n<td>";
      Dropdown::showYesNo("import_device_gfxcard", $this->fields["import_device_gfxcard"]);
      echo "&nbsp;&nbsp;</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Soundcard') . "</td>\n<td>";
      Dropdown::showYesNo("import_device_sound", $this->fields["import_device_sound"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . _n('Drive', 'Drives', 2) . "</td>\n<td>";
      Dropdown::showYesNo("import_device_drive", $this->fields["import_device_drive"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>".__('Modems') ."</td>\n<td>";
      Dropdown::showYesNo("import_device_modem", $this->fields["import_device_modem"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>"._n('Port', 'Ports', 2)."</td>\n<td>";
      Dropdown::showYesNo("import_device_port", $this->fields["import_device_port"]);
      echo "</td></tr>\n";
      
      echo "<tr class='tab_bg_2'><td class='center'>".__('Bios')."</td>\n<td>";
      Dropdown::showYesNo("import_device_bios", $this->fields["import_device_bios"]);
      echo "</td></tr>\n";
      echo "</table>";

      echo "</td>\n";
      echo "<td class='tab_bg_2 top'>\n";

      $opt = self::getColumnListFromAccountInfoTable($ID);
      
      echo "<table width='100%'>";
      echo "<tr class='tab_bg_2'><td class='center'>" . __('Inventory number'). " </td>\n";
      echo "<td>";
      $link = new PluginOcsinventoryngOcsAdminInfosLink();
      $link->getFromDBbyOcsServerIDAndGlpiColumn($ID, "otherserial");
      
      $value = (isset($link->fields["ocs_column"])?$link->fields["ocs_column"]:"");
      Dropdown::showFromArray("import_otherserial", $opt, array('value' => $value,
                                                                  'width' => '100%'));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Location') . " </td>\n";
      echo "<td>";
      $link = new PluginOcsinventoryngOcsAdminInfosLink();
      $link->getFromDBbyOcsServerIDAndGlpiColumn($ID, "locations_id");
      
      $value = (isset($link->fields["ocs_column"])?$link->fields["ocs_column"]:"");
      Dropdown::showFromArray("import_location", $opt, array('value' => $value,
                                                               'width' => '100%'));
      
      echo "</td></tr>\n";
      
      echo "<tr class='tab_bg_2'><td class='center'>" . __('Group') . " </td>\n";
      echo "<td>";
      $link = new PluginOcsinventoryngOcsAdminInfosLink();
      $link->getFromDBbyOcsServerIDAndGlpiColumn($ID, "groups_id");
      
      $value = (isset($link->fields["ocs_column"])?$link->fields["ocs_column"]:"");
      Dropdown::showFromArray("import_group", $opt, array('value' => $value,
                                                            'width' => '100%'));
      
      echo "</td></tr>\n";
      
      
      echo "<tr class='tab_bg_2'><td class='center'>" . __('Alternate username number') . " </td>\n";
      echo "<td>";
      $link = new PluginOcsinventoryngOcsAdminInfosLink();
      $link->getFromDBbyOcsServerIDAndGlpiColumn($ID, "contact_num");
      
      $value = (isset($link->fields["ocs_column"])?$link->fields["ocs_column"]:"");
      Dropdown::showFromArray("import_contact_num", $opt, array('value' => $value,
                                                            'width' => '100%'));
      
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . __('Network') . " </td>\n";
      echo "<td>";
      $link = new PluginOcsinventoryngOcsAdminInfosLink();
      $link->getFromDBbyOcsServerIDAndGlpiColumn($ID, "networks_id");
      
      $value = (isset($link->fields["ocs_column"])?$link->fields["ocs_column"]:"");
      Dropdown::showFromArray("import_network", $opt, array('value' => $value,
                                                            'width' => '100%'));
      
      echo "</td></tr>\n";

      echo "</table>";

      echo "</td></tr>\n";

      echo "<tr><th>". _n('Monitor', 'Monitors', 2)."</th>\n";
      echo "<th colspan='2'>&nbsp;</th></tr>\n";

      echo "<tr class='tab_bg_2'>\n";
      echo "<td class='top'>\n";

      echo "<table width='100%'>";
      echo "<tr class='tab_bg_2'><td class='center'>" . __('Comments') . " </td>\n<td>";
      Dropdown::showYesNo("import_monitor_comment", $this->fields["import_monitor_comment"]);
      echo "</td></tr>\n";
      echo "</table>";

      echo "</td>\n";
      echo "<td colspan='2'>&nbsp;</td></tr>\n";
      echo "<tr class='tab_bg_2 center'><td colspan='3'>";
      echo "<input type='submit' name='update' class='submit' value=\"".
      _sx('button', 'Save')."\">";
      echo "</td></tr>\n";
      echo "</table>\n";
      Html::closeForm();
      echo "</div>\n";
   }


   /**
    * @param $ID
    * @param $withtemplate    (default '')
    * @param $templateid      (default '')
    **/
   function ocsFormImportOptions($ID, $withtemplate='', $templateid='') {

      $this->getFromDB($ID);
      echo "<div class='center'>";
      echo "<form name='formconfig' action='".Toolbox::getItemTypeFormURL("PluginOcsinventoryngOcsServer")."' method='post'>";
      echo "<table class='tab_cadre_fixe'>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" .__('Web address of the OCSNG console',
         'ocsinventoryng');
      echo "<input type='hidden' name='id' value='$ID'>" . " </td>\n";
      echo "<td><input type='text' size='30' name='ocs_url' value=\"".$this->fields["ocs_url"]."\">";
      echo "</td></tr>\n";

      echo "<tr><th colspan='2'>" . __('Import options', 'ocsinventoryng'). "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>".
      __('Limit the import to the following tags (separator $, nothing for all)',
         'ocsinventoryng')."</td>\n";
      echo "<td><input type='text' size='30' name='tag_limit' value='".$this->fields["tag_limit"]."'>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>".
      __('Exclude the following tags (separator $, nothing for all)', 'ocsinventoryng').
      "</td>\n";
      echo "<td><input type='text' size='30' name='tag_exclude' value='".
      $this->fields["tag_exclude"]."'></td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>".__('Default status', 'ocsinventoryng').
      "</td>\n<td>";
      State::dropdown(array('name'   => 'states_id_default',
                           'value'  => $this->fields["states_id_default"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>".__('Behavior when disconnecting', 'ocsinventoryng')."</td>\n<td>";
      Dropdown::showFromArray("deconnection_behavior",
      array(''       => __('Preserve'),
            "trash"  => _x('button', 'Put in dustbin'),
            "delete" => _x('button', 'Delete permanently')),
      array('value' => $this->fields["deconnection_behavior"]));
      echo "</td></tr>\n";

      $import_array  = array("0" => __('No import'),
         "1" => __('Global import', 'ocsinventoryng'),
         "2" => __('Unit import', 'ocsinventoryng'));

      $import_array2 = array("0" => __('No import'),
         "1" => __('Global import', 'ocsinventoryng'),
         "2" => __('Unit import', 'ocsinventoryng'),
         "3" => __('Unit import on serial number', 'ocsinventoryng'),
         "4" => __('Unit import serial number only', 'ocsinventoryng'));

      $periph   = $this->fields["import_periph"];
      $monitor  = $this->fields["import_monitor"];
      $printer  = $this->fields["import_printer"];
      $software = $this->fields["import_software"];
      echo "<tr class='tab_bg_2'><td class='center'>" ._n('Device', 'Devices', 2). " </td>\n<td>";
      Dropdown::showFromArray("import_periph", $import_array, array('value' => $periph));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" ._n('Monitor', 'Monitors', 2). "</td>\n<td>";
      Dropdown::showFromArray("import_monitor", $import_array2, array('value' => $monitor));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" ._n('Printer', 'Printers', 2). "</td>\n<td>";
      Dropdown::showFromArray("import_printer", $import_array, array('value' => $printer));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>". _n('Software', 'Software', 2)."</td>\n<td>";
      $import_array = array("0" => __('No import'),
                             "1" => __('Unit import', 'ocsinventoryng'));
      Dropdown::showFromArray("import_software", $import_array, array('value' => $software));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . _n('Volume', 'Volumes', 2) . "</td>\n<td>";
      Dropdown::showYesNo("import_disk", $this->fields["import_disk"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>".__('Use the OCSNG software dictionary',
         'ocsinventoryng')."</td>\n<td>";
      Dropdown::showYesNo("use_soft_dict", $this->fields["use_soft_dict"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>".__('Registry', 'ocsinventoryng')."</td>\n<td>";
      Dropdown::showYesNo("import_registry", $this->fields["import_registry"]);
      echo "</td></tr>\n";

      //check version
      if ($this->fields['ocs_version'] > self::OCS1_3_VERSION_LIMIT) {
         echo "<tr class='tab_bg_2'><td class='center'>".
         _n('Virtual machine', 'Virtual machines', 2) . "</td>\n<td>";
         Dropdown::showYesNo("import_vms", $this->fields["import_vms"]);
         echo "</td></tr>\n";
      } else {
         echo "<tr class='tab_bg_2'><td class='center'>";
         echo "<input type='hidden' name='import_vms' value='0'>";
         echo "</td></tr>\n";
      }
      echo "<tr class='tab_bg_2'><td class='center'>".
      __('Number of items to synchronize via the automatic OCSNG action',  'ocsinventoryng').
      "</td>\n<td>";
      Dropdown::showNumber('cron_sync_number',
                           array('value' => $this->fields['cron_sync_number'],
                                 'min'   => 1,
                                 'toadd' => array(0 => __('None'))));
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>".
      __('Behavior to the deletion of a computer in OCSNG', 'ocsinventoryng')."</td>";
      echo "<td>";
      $actions[0] = Dropdown::EMPTY_VALUE;
      $actions[1] = __('Put in dustbin');
      foreach (getAllDatasFromTable('glpi_states') as $state) {
         $actions['STATE_'.$state['id']] = sprintf(__('Change to state %s', 'ocsinventoryng'),
         $state['name']);
      }
      Dropdown::showFromArray('deleted_behavior', $actions,
      array('value' => $this->fields['deleted_behavior']));
      echo "</td></tr>";
      
      echo "<tr class='tab_bg_2'><td class='center b red' colspan='2'>";
      echo __('No import: the plugin will not import these elements', 'ocsinventoryng');
      echo "<br>".__('Global import: everything is imported but the material is globally managed (without duplicate)',
         'ocsinventoryng');
      echo "<br>".__("Unit import: everything is imported as it is", 'ocsinventoryng');
      echo "</td></tr>";
      
      echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
      echo "<input type='submit' name='update' class='submit' value='" .
      _sx('button', 'Save') . "'>";
      echo "</td></tr>";
      
      echo "</table>\n";
      Html::closeForm();
      echo "</div>";
   }


   /**
    * Print simple ocs config form (database part)
    *
    * @param $ID        integer : Id of the ocs config
    * @param $options   array
    *     - target form target
    *
    * @return Nothing (display)
    **/
   function showForm($ID, $options=array()) {

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
      echo "<td class='center'>".__('Connection type', 'ocsinventoryng')."</td>";
      
      echo "<script type='text/javascript'>
            function form_init_all(form, value) {
               var hideIfSoapElems = document.getElementsByClassName('hide_if_soap');
               for (var i = 0; i < hideIfSoapElems.length; i++) {
                  if (value == '0') {
                     // if DB
                     hideIfSoapElems[i].style.display = '';
                  } else {
                     // if SOAP
                     hideIfSoapElems[i].style.display = 'none';
                  }
               }
            }
   
         </script>";

            
      echo "<td id='conn_type_container'>";
      Dropdown::showFromArray('conn_type', $conn_type_values, array('value' => $this->fields['conn_type'],
                                                                     'on_change' => "form_init_all(this.form, this.selectedIndex);"));
      echo "</td>";
      echo "<td colspan='2'>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>". __("Name")."</td>";
      echo "<td><input type='text' name='name' value='".$this->fields["name"]."'></td>";
      echo "<td class='center'>"._n("Version", "Versions", 1)."</td>";
      echo "<td>".$this->fields["ocs_version"]."</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>".__("Host", "ocsinventoryng")."</td>";
      echo "<td><input type='text' name='ocs_db_host' value='".$this->fields["ocs_db_host"]."'>";
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Like http://127.0.0.1 for SOAP method', 'ocsinventoryng')));
      echo "</td>";
      echo "<td class='center'>".__("Synchronisation method", "ocsinventoryng")."</td>";
      echo "<td>";
      Dropdown::showFromArray('use_massimport', $sync_method_values, array('value' => $this->fields['use_massimport']));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1 hide_if_soap' ";
      if ($this->fields["conn_type"] == PluginOcsinventoryngOcsServer::CONN_TYPE_SOAP) {
         echo "style='display:none'";
      }
      echo "><td class='center'>".__("Database")."</td>";
      echo "<td><input type='text' name='ocs_db_name' value='".$this->fields["ocs_db_name"]."'></td>";
      echo "<td class='center'>".__("Database in UTF8", "ocsinventoryng")."</td>";
      echo "<td>";
      Dropdown::showYesNo("ocs_db_utf8", $this->fields["ocs_db_utf8"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>"._n("User", "Users", 1)."</td>";
      echo "<td><input type='text' name='ocs_db_user' value='".$this->fields["ocs_db_user"]."'></td>";
      echo "<td class='center' rowspan='2'>".__("Comments")."</td>";
      echo "<td rowspan='2'><textarea cols='45' rows='6' name='comment'>".$this->fields["comment"]."</textarea></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>".__("Password")."</td>";
      echo "<td><input type='password' name='ocs_db_passwd' value='' autocomplete='off'>";
      if ($ID) {
         echo "<input type='checkbox' name='_blank_passwd'>&nbsp;".__("Clear");
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td class='center'>".__("Active")."</td>";
      echo "<td>";
      Dropdown::showYesNo("is_active",$this->fields["is_active"]);
      echo "</td>";

      echo "<td>". __("Last update")."</td>";
      echo "<td>".Html::convDateTime($this->fields["date_mod"])."</td>";
      echo "</tr>";

      $this->showFormButtons($options);
   }

   /**
    * check is one of the servers use_mass_import sync mode
    *
    * @return boolean
    **/
   static function useMassImport() {
      return countElementsInTable('glpi_plugin_ocsinventoryng_ocsservers', 'use_massimport');
   }

   /**
    * @param $ID
    **/
   function showDBConnectionStatus($ID) {

      $out="<br><div class='center'>\n";
      $out.="<table class='tab_cadre_fixe'>";
      $out.="<tr><th>" .__('Connecting to the database', 'ocsinventoryng'). "</th></tr>\n";
      $out.="<tr class='tab_bg_2'>";
      if ($ID != -1) {
         if (!self::checkOCSconnection($ID)) {
            $out .= "<td class='center red'>".__('Connection to the database failed', 'ocsinventoryng');
         } else if (!self::checkVersion($ID)) {
            $out .= "<td class='center red'>".__('Invalid OCSNG Version: RC3 is required', 'ocsinventoryng');
         } else if (!self::checkTraceDeleted($ID)) {
            $out .= "<td class='center red'>".__('Invalid OCSNG configuration (TRACE_DELETED must be active)', 'ocsinventoryng');
            // TODO
            /*} else if (!self::checkConfig(4)) {
            $out .= __('Access denied on database (Need write rights on hardware.CHECKSUM necessary)',
            'ocsinventoryng');
            } else if (!self::checkConfig(8)) {
            $out .= __('Access denied on database (Delete rights in deleted_equiv table necessary)',
            'ocsinventoryng');*/
         } else {
            $out .= "<td class='center'>".__('Connection to database successful', 'ocsinventoryng');
            $out .= "</td></tr>\n<tr class='tab_bg_2'>".
            "<td class='center'>".__('Valid OCSNG configuration and version', 'ocsinventoryng');
         }
      }
      $out .= "</td></tr>\n";
      $out .= "</table></div>";
      echo $out;
   }


   function prepareInputForUpdate($input) {

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


   function pre_updateInDB() {

      // Update checksum
      $checksum = 0;

      if ($this->fields["import_printer"]) {
         $checksum |= pow(2,self::PRINTERS_FL);
      }
      if ($this->fields["import_software"]) {
         $checksum |= pow(2,self::SOFTWARES_FL);
      }
      if ($this->fields["import_monitor"]) {
         $checksum |= pow(2,self::MONITORS_FL);
      }
      if ($this->fields["import_periph"]) {
         $checksum |= pow(2,self::INPUTS_FL);
      }
      if ($this->fields["import_registry"]) {
         $checksum |= pow(2,self::REGISTRY_FL);
      }
      if ($this->fields["import_disk"]) {
         $checksum |= pow(2,self::DRIVES_FL);
      }
      if ($this->fields["import_ip"]) {
         $checksum |= pow(2,self::NETWORKS_FL);
      }
      if ($this->fields["import_device_port"]) {
         $checksum |= pow(2,self::PORTS_FL);
      }
      if ($this->fields["import_device_modem"]) {
         $checksum |= pow(2,self::MODEMS_FL);
      }
      if ($this->fields["import_device_bios"]) {
         $checksum |= pow(2,self::BIOS_FL);
      }
      if ($this->fields["import_device_drive"]) {
         $checksum |= pow(2,self::STORAGES_FL);
      }
      if ($this->fields["import_device_sound"]) {
         $checksum |= pow(2,self::SOUNDS_FL);
      }
      if ($this->fields["import_device_gfxcard"]) {
         $checksum |= pow(2,self::VIDEOS_FL);
      }
      if ($this->fields["import_device_iface"]) {
         $checksum |= pow(2,self::NETWORKS_FL);
      }
      if ($this->fields["import_device_hdd"]) {
         $checksum |= pow(2,self::STORAGES_FL);
      }
      if ($this->fields["import_device_memory"]) {
         $checksum |= pow(2,self::MEMORIES_FL);
      }

      if ($this->fields["import_device_processor"]
      || $this->fields["import_general_contact"]
      || $this->fields["import_general_comment"]
      || $this->fields["import_general_domain"]
      || $this->fields["import_general_os"]
      || $this->fields["import_general_name"]) {

         $checksum |= pow(2,self::HARDWARE_FL);
      }

     /* if ($this->fields["import_general_manufacturer"]
      || $this->fields["import_general_type"]
      || $this->fields["import_general_model"]
      || $this->fields["import_general_serial"]) {

         $checksum |= pow(2,self::BIOS_FL);
      }*/

      if ($this->fields["import_vms"]) {
         $checksum |= pow(2,self::VIRTUALMACHINES_FL);
      }

      $this->updates[] = "checksum";
      $this->fields["checksum"] = $checksum;
   }


   function prepareInputForAdd($input) {
      global $DB;

      // Check if server config does not exists
      $query = "SELECT *
                FROM `" . $this->getTable() . "`
                WHERE `name` = '".$input['name']."';";
      $result = $DB->query($query);
      if ($DB->numrows($result)>0) {
         Session::addMessageAfterRedirect(__('Unable to add. The OCSNG server already exists.',
               'ocsinventoryng'),
         false, ERROR);
         return false;
      }

      if (isset($input["ocs_db_passwd"]) && !empty($input["ocs_db_passwd"])) {
         $input["ocs_db_passwd"] = rawurlencode(stripslashes($input["ocs_db_passwd"]));
      } else {
         unset($input["ocs_db_passwd"]);
      }
      return $input;
   }

   function post_addItem() {
      global $DB;
      
      $query = "INSERT INTO  `glpi_plugin_ocsinventoryng_ocsservers_profiles` (`id` ,`plugin_ocsinventoryng_ocsservers_id` , `profiles_id`)
                                    VALUES (NULL ,  '".$this->fields['id']."',  '".$_SESSION["glpiactiveprofile"]['id']."');";
      $result = $DB->query($query);
   }
   
   function cleanDBonPurge() {

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
    **/
   function updateAdminInfo($tab) {

      if (isset($tab["import_location"])
      || isset ($tab["import_otherserial"])
      || isset ($tab["import_group"])
      || isset ($tab["import_network"])
      || isset ($tab["import_contact_num"])) {

         $adm = new PluginOcsinventoryngOcsAdminInfosLink();
         $adm->cleanForOcsServer($tab["id"]);

         if (isset ($tab["import_location"])) {
            if ($tab["import_location"]!="") {
               $adm = new PluginOcsinventoryngOcsAdminInfosLink();
               $adm->fields["plugin_ocsinventoryng_ocsservers_id"] = $tab["id"];
               $adm->fields["glpi_column"]                         = "locations_id";
               $adm->fields["ocs_column"]                          = $tab["import_location"];
               $isNewAdm = $adm->addToDB();
            }
         }

         if (isset ($tab["import_otherserial"])) {
            if ($tab["import_otherserial"]!="") {
               $adm = new PluginOcsinventoryngOcsAdminInfosLink();
               $adm->fields["plugin_ocsinventoryng_ocsservers_id"] =  $tab["id"];
               $adm->fields["glpi_column"]                         = "otherserial";
               $adm->fields["ocs_column"]                          = $tab["import_otherserial"];
               $isNewAdm = $adm->addToDB();
            }
         }

         if (isset ($tab["import_group"])) {
            if ($tab["import_group"]!="") {
               $adm = new PluginOcsinventoryngOcsAdminInfosLink();
               $adm->fields["plugin_ocsinventoryng_ocsservers_id"] = $tab["id"];
               $adm->fields["glpi_column"]                         = "groups_id";
               $adm->fields["ocs_column"]                          = $tab["import_group"];
               $isNewAdm = $adm->addToDB();
            }
         }

         if (isset ($tab["import_network"])) {
            if ($tab["import_network"]!="") {
               $adm = new PluginOcsinventoryngOcsAdminInfosLink();
               $adm->fields["plugin_ocsinventoryng_ocsservers_id"] = $tab["id"];
               $adm->fields["glpi_column"]                         = "networks_id";
               $adm->fields["ocs_column"]                          = $tab["import_network"];
               $isNewAdm = $adm->addToDB();
            }
         }

         if (isset ($tab["import_contact_num"])) {
            if ($tab["import_contact_num"]!="") {
               $adm = new PluginOcsinventoryngOcsAdminInfosLink();
               $adm->fields["plugin_ocsinventoryng_ocsservers_id"] = $tab["id"];
               $adm->fields["glpi_column"]                         = "contact_num";
               $adm->fields["ocs_column"]                          = $tab["import_contact_num"];
               $isNewAdm = $adm->addToDB();
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
   static function encodeOcsDataInUtf8($is_ocsdb_utf8, $value) {
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
    * @return the ocs server id of the machine
    **/
   static function getByMachineID($ID) {
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
    **/
   static function getServerNameByID($ID) {

      $plugin_ocsinventoryng_ocsservers_id = self::getByMachineID($ID);
      $conf                                = self::getConfig($plugin_ocsinventoryng_ocsservers_id);
      return $conf["name"];
   }


   /**
    * Get a random plugin_ocsinventoryng_ocsservers_id
    * use for standard sync server selection
    *
    * @return an ocs server id
    **/
   static function getRandomServerID() {
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
    **/
   static function getConfig($id) {
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

   static function getTagLimit($cfg_ocs) {

      $WHERE = "";
      if (!empty ($cfg_ocs["tag_limit"])) {
         $splitter = explode("$", trim($cfg_ocs["tag_limit"]));
         if (count($splitter)) {
            $WHERE = " `accountinfo`.`TAG` = '" . $splitter[0] . "' ";
            for ($i = 1; $i < count($splitter); $i++) {
               $WHERE .= " OR `accountinfo`.`TAG` = '" .$splitter[$i] . "' ";
            }
         }
      }

      if (!empty ($cfg_ocs["tag_exclude"])) {
         $splitter = explode("$", $cfg_ocs["tag_exclude"]);
         if (count($splitter)) {
            if (!empty($WHERE)) {
               $WHERE .= " AND ";
            }
            $WHERE .= " `accountinfo`.`TAG` <> '" . $splitter[0] . "' ";
            for ($i=1 ; $i<count($splitter) ; $i++) {
               $WHERE .= " AND `accountinfo`.`TAG` <> '" .$splitter[$i] . "' ";
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
    * @return integer : link id.
    **/
   static function ocsLink($ocsid, $plugin_ocsinventoryng_ocsservers_id, $glpi_computers_id) {
      global $DB;

      // Retrieve informations from computer
      $comp = new Computer();
      $comp->getFromDB($glpi_computers_id);

      self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);

      $ocsComputer = $ocsClient->getComputer($ocsid);

      if (is_null($ocsComputer)) {
         return false;
      }

      $query = "INSERT INTO `glpi_plugin_ocsinventoryng_ocslinks`
                       (`computers_id`, `ocsid`, `ocs_deviceid`,
                        `last_update`, `plugin_ocsinventoryng_ocsservers_id`,
                        `entities_id`, `tag`)
                VALUES ('$glpi_computers_id', '$ocsid', '".$ocsComputer['META']['DEVICEID']."',
                        '".$_SESSION["glpi_currenttime"]."', '$plugin_ocsinventoryng_ocsservers_id',
                        '".$comp->fields['entities_id']."', '".$ocsComputer['META']['TAG']."')";
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
    **/
   static function linkComputer($ocsid, $plugin_ocsinventoryng_ocsservers_id, $computers_id) {
      global $DB, $CFG_GLPI;

      self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);

      $query = "SELECT *
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `computers_id` = '$computers_id'";

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
         $ocsConfig = self::getConfig($plugin_ocsinventoryng_ocsservers_id);
         // Set OCS checksum to max value
         $ocsClient->setChecksum(PluginOcsinventoryngOcsClient::CHECKSUM_ALL, $ocsid);

         if ($ocs_id_change
         || ($idlink = self::ocsLink($ocsid, $plugin_ocsinventoryng_ocsservers_id,
         $computers_id))) {

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
                     self::transferComputer($ocsLink->fields, $ocsComputer);
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
                  self::resetDropdown($computers_id, "operatingsystems_id", "glpi_operatingsystems");
               }
               if ($ocsConfig["import_device_processor"]) {
                  self::resetDevices($computers_id, 'DeviceProcessor');
               }
               if ($ocsConfig["import_device_iface"]) {
                  self::resetDevices($computers_id, 'DeviceNetworkCard');
               }
               if ($ocsConfig["import_device_memory"]) {
                  self::resetDevices($computers_id, 'DeviceMemory');
               }
               if ($ocsConfig["import_device_hdd"]) {
                  self::resetDevices($computers_id, 'DeviceHardDrive');
               }
               if ($ocsConfig["import_device_sound"]) {
                  self::resetDevices($computers_id, 'DeviceSoundCard');
               }
               if ($ocsConfig["import_device_gfxcard"]) {
                  self::resetDevices($computers_id, 'DeviceGraphicCard');
               }
               if ($ocsConfig["import_device_drive"]) {
                  self::resetDevices($computers_id, 'DeviceDrive');
               }
               if ($ocsConfig["import_device_modem"] || $ocsConfig["import_device_port"]) {
                  self::resetDevices($computers_id, 'DevicePci');
               }
               if ($ocsConfig["import_device_bios"]) {
                  self::resetDevices($computers_id, 'PluginOcsinventoryngDeviceBiosdata');
               }
               if ($ocsConfig["import_software"]) {
                  self::resetSoftwares($computers_id);
               }
               if ($ocsConfig["import_disk"]) {
                  self::resetDisks($computers_id);
               }
               if ($ocsConfig["import_periph"]) {
                  self::resetPeripherals($computers_id);
               }
               if ($ocsConfig["import_monitor"]==1) { // Only reset monitor as global in unit management
                  self::resetMonitors($computers_id);    // try to link monitor with existing
               }
               if ($ocsConfig["import_printer"]) {
                  self::resetPrinters($computers_id);
               }
               if ($ocsConfig["import_registry"]) {
                  self::resetRegistry($computers_id);
               }
               $changes[0] = '0';
               $changes[1] = "";
               $changes[2] = $ocsid;
               PluginOcsinventoryngOcslink::history($computers_id, $changes,
               PluginOcsinventoryngOcslink::HISTORY_OCS_LINK);
            }

            self::updateComputer($idlink, $plugin_ocsinventoryng_ocsservers_id, 0);
            return true;
         }

      } else {
         //TRANS: %s is the OCS id
         Session::addMessageAfterRedirect(sprintf(__('Unable to import, GLPI computer is already related to an element of OCSNG (%d)',
                  'ocsinventoryng'), $ocsid),
         false, ERROR);
      }
      return false;
   }


   static function processComputer($ocsid, $plugin_ocsinventoryng_ocsservers_id, $lock=0,
   $defaultentity=-1, $defaultlocation=-1) {
      global $DB;

      self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $comp = new Computer();

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
         return self::updateComputer($datas["id"], $plugin_ocsinventoryng_ocsservers_id, 1, 0);
      }
      return self::importComputer($ocsid, $plugin_ocsinventoryng_ocsservers_id, $lock,
      $defaultentity, $defaultlocation);
   }


   public static function checkVersion($ID) {
      $client = self::getDBocs($ID);
      $version = $client->getTextConfig('GUI_VERSION');

      if ($version) {
         $server = new PluginOcsinventoryngOcsServer();
         $server->update(array(
               'id' => $ID,
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

   public static function checkTraceDeleted($ID) {
      $client = self::getDBocs($ID);
      return $client->getIntConfig('TRACE_DELETED');
   }


   static function manageDeleted($plugin_ocsinventoryng_ocsservers_id) {
      global $DB, $CFG_GLPI, $PLUGIN_HOOKS;
      
      if (!(self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id) 
            && self::checkVersion($plugin_ocsinventoryng_ocsservers_id))) {
         return false;
      }
      
      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $deleted = $ocsClient->getDeletedComputers();

      //if (strpos($_SERVER['PHP_SELF'], "deleted_equiv.php") == true){
         if (count($deleted)) {
         
            foreach ($deleted as $del => $equiv) {
               if (!empty($equiv) && !is_null($equiv)) { // New name

                  // Get hardware due to bug of duplicates management of OCS
                  if (strpos($equiv,"-") !== false) {
                     $res = $ocsClient->searchComputers('DEVICEID', $equiv);
                     if (count($res['COMPUTERS'])) {
                        $data = end($res['COMPUTERS']['META']);
                        $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                                     SET `ocsid` = '" . $data["ID"] . "',
                                         `ocs_deviceid` = '" . $data["DEVICEID"] . "'
                                     WHERE `ocs_deviceid` = '$del'
                                           AND `plugin_ocsinventoryng_ocsservers_id`
                                                   = '$plugin_ocsinventoryng_ocsservers_id'";
                        $DB->query($query);
                        $ocsClient->setChecksum($data['CHECKSUM'] | PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE, $data['ID']);
                        // } else {
                        // We're damned ! no way to find new ID
                        // TODO : delete ocslinks ?
                     }
                  } else {
                     $res = $ocsClient->searchComputers('ID', $equiv);
                     if (isset($res['COMPUTERS']) 
                           && count($res['COMPUTERS'])) {

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
                  if ($data) {
                     $sql_id = "SELECT `computers_id`
                                   FROM `glpi_plugin_ocsinventoryng_ocslinks`
                                   WHERE `ocsid` = '".$data["ID"]."'
                                         AND `plugin_ocsinventoryng_ocsservers_id`
                                                = '$plugin_ocsinventoryng_ocsservers_id'";
                     if ($res_id = $DB->query($sql_id)) {
                        if ($DB->numrows($res_id)>0) {
                           //Add history to indicates that the ocsid changed
                           $changes[0] = '0';
                           //Old ocsid
                           $changes[1] = $del;
                           //New ocsid
                           $changes[2] = $data["ID"];
                           PluginOcsinventoryngOcslink::history($DB->result($res_id, 0, "computers_id"), $changes,
                              PluginOcsinventoryngOcslink::HISTORY_OCS_IDCHANGED);
                        }
                     }
                  }
   
               } else { // Deleted
   
                  $ocslinks_toclean = array();
                  if (strstr($del,"-")) {
                     $link = "ocs_deviceid";
                  } else {
                     $link = "ocsid";
                  }
                  $query = "SELECT *
                               FROM `glpi_plugin_ocsinventoryng_ocslinks`
                               WHERE `". $link."` = '$del'
                                     AND `plugin_ocsinventoryng_ocsservers_id`
                                                = '$plugin_ocsinventoryng_ocsservers_id'";
   
                  if ($result = $DB->query($query)) {
                     if ($DB->numrows($result)>0) {
                        $data                          = $DB->fetch_array($result);
                        $ocslinks_toclean[$data['id']] = $data['id'];
                     }
                  }
                  self::cleanLinksFromList($plugin_ocsinventoryng_ocsservers_id, $ocslinks_toclean);
               }
               
               if (!empty($equiv)){
                  $ocsClient->removeDeletedComputers($del, $equiv);
               }
               else{
                  $to_del[]=$del;
               }
               
            }
            if(!empty($to_del)){
               $ocsClient->removeDeletedComputers($to_del);
            }
            if ($_SERVER['QUERY_STRING'] != "") {
               $redirection = $_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING'];
            }
            else {
               $redirection = $_SERVER['PHP_SELF'];
            }
            Html::redirect($redirection);
         }
         else{
            $_SESSION['ocs_deleted_equiv']['computers_to_del']=false;
            $_SESSION['ocs_deleted_equiv']['computers_deleted']=0;
            
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
    * Return field matching between OCS and GLPI
    *
    * @return array of glpifield => ocsfield
    **/
   static function getOcsFieldsMatching() {

      // Manufacturer and Model both as text (for rules) and as id (for import)
      return array('manufacturer'                     => array('BIOS', 'SMANUFACTURER'),
                     'manufacturers_id'                 => array('BIOS', 'SMANUFACTURER'),
                     'os_license_number'                => array('HARDWARE', 'WINPRODKEY'),
                     'os_licenseid'                     => array('HARDWARE', 'WINPRODID'),
                     'operatingsystems_id'              => array('HARDWARE', 'OSNAME'),
                     'operatingsystemversions_id'       => array('HARDWARE', 'OSVERSION'),
                     'operatingsystemservicepacks_id'   => array('HARDWARE', 'OSCOMMENTS'),
                     'domains_id'                       => array('HARDWARE', 'WORKGROUP'),
                     'contact'                          => array('HARDWARE', 'USERID'),
                     'name'                             => array('META', 'NAME'),
                     'comment'                          => array('HARDWARE', 'DESCRIPTION'),
                     'serial'                           => array('BIOS', 'SSN'),
                     'model'                            => array('BIOS', 'SMODEL'),
                     'computermodels_id'                => array('BIOS', 'SMODEL'),
                     'TAG'                              => array('ACCOUNTINFO', 'TAG')
      );
   }


   static function getComputerInformations($ocs_fields=array(), $cfg_ocs, $entities_id, $locations_id=0) {
      $input = array();
      $input["is_dynamic"] = 1;

      if ($cfg_ocs["states_id_default"] > 0) {
         $input["states_id"] = $cfg_ocs["states_id_default"];
      }

      $input["entities_id"] = $entities_id;

      if ($locations_id) {
         $input["locations_id"] = $locations_id;
      }

      $input['ocsid'] = $ocs_fields['META']['ID'];
      $ocs_fields_matching = self::getOcsFieldsMatching();
      foreach ($ocs_fields_matching as $glpi_field => $ocs_field) {
         $ocs_section = $ocs_field[0];
         $ocs_field = $ocs_field[1];

         $table = getTableNameForForeignKeyField($glpi_field);

         $ocs_val = null;
         if (array_key_exists($ocs_field, $ocs_fields[$ocs_section])) {
            $ocs_val = $ocs_fields[$ocs_section][$ocs_field];
         } else if(array_key_exists($ocs_field, $ocs_fields[$ocs_section][0])) {
            $ocs_val = $ocs_fields[$ocs_section][0][$ocs_field];
         }

         if (!is_null($ocs_val)) {
            $ocs_field = Toolbox::encodeInUtf8($ocs_field);

            //Field is a foreing key
            if ($table != '') {
               $itemtype         = getItemTypeForTable($table);
               $item             = new $itemtype();
               $external_params  = array();

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
                     $input[$glpi_field] .= addslashes(sprintf(__('%1$s %2$s'), $input[$glpi_field],
                     sprintf(__('%1$s: %2$s'),
                     __('Swap', 'ocsinventoryng'),
                     $ocs_fields['HARDWARE']['SWAP'])));
                     break;

                  default :
                     $input[$glpi_field] = $ocs_val;
                     break;
               }
            }
         }
      }
      if (intval($cfg_ocs["import_general_name"]) == 0){
         unset($input["name"]);
      }

      if (intval($cfg_ocs["import_general_os"]) == 0){
         unset($input["operatingsystems_id"]);
         unset($input["operatingsystemversions_id"]);
         unset($input["operatingsystemservicepacks_id"]);
      }

      if (intval($cfg_ocs["import_os_serial"]) == 0){
         unset($input["os_license_number"]);
         unset($input["os_licenseid"]);
      }

      if (intval($cfg_ocs["import_general_serial"]) == 0){
         unset($input["serial"]);
      }

      if (intval($cfg_ocs["import_general_model"]) == 0){
         unset($input["model"]);
         unset($input["computermodels_id"]);
      }

      if (intval($cfg_ocs["import_general_manufacturer"]) == 0){
         unset($input["manufacturer"]);
         unset($input["manufacturers_id"]);
      }

      if (intval($cfg_ocs["import_general_type"]) == 0){
         unset($input["computertypes_id"]);
      }

      if (intval($cfg_ocs["import_general_comment"]) == 0){
         unset($input["comment"]);
      }

      if (intval($cfg_ocs["import_general_contact"]) == 0){
         unset($input["contact"]);
      }

      if (intval($cfg_ocs["import_general_domain"]) == 0) {
         unset($input["domains_id"]);
      }
      return $input;
   }


   static function importComputer($ocsid, $plugin_ocsinventoryng_ocsservers_id, $lock=0, $defaultentity=-1, $defaultlocation=-1) {
      self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $comp = new Computer();

      $rules_matched = array();
      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $ocsClient->setChecksum(PluginOcsinventoryngOcsClient::CHECKSUM_ALL, $ocsid);
      
      
      //No entity or location predefined, check rules
      if ($defaultentity == -1 && ($defaultlocation == -1 || $defaultlocation == 0)) {
         //Try to affect computer to an entity
         $rule = new RuleImportEntityCollection();
         $data = array();
         $data = $rule->processAllRules(array('ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id,
               '_source'       => 'ocsinventoryng'),
         array(), array('ocsid' => $ocsid));

         if (isset($data['_ignore_import']) && $data['_ignore_import'] == 1) {
            //ELSE Return code to indicates that the machine was not imported because it doesn't matched rules
            return array('status'       => self::COMPUTER_LINK_REFUSED,
               'rule_matched' => $data['_ruleid']);
         }
      } else {
         //An entity or a location has already been defined via the web interface
         $data['entities_id']  = $defaultentity;
         $data['locations_id'] = $defaultlocation;
      }
      //Try to match all the rules, return the first good one, or null if not rules matched
      if (isset ($data['entities_id']) && $data['entities_id']>=0) {
         if ($lock) {
            while (!$fp = self::setEntityLock($data['entities_id'])) {
               sleep(1);
            }
         }

         //Store rule that matched
         if (isset($data['_ruleid'])) {
            $rules_matched['RuleImportEntity'] = $data['_ruleid'];
         }

         $ocsComputer = $ocsClient->getComputer($ocsid, array(
               'DISPLAY' => array(
                  'CHECKSUM' => PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE | PluginOcsinventoryngOcsClient::CHECKSUM_BIOS,
                  'WANTED' => PluginOcsinventoryngOcsClient::WANTED_ACCOUNTINFO
            )
         ));
         
         if (!is_null($ocsComputer)) {
            $computer = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($ocsComputer));

            $locations_id = (isset($data['locations_id'])?$data['locations_id']:0);
            $input   = self::getComputerInformations($computer,
            self::getConfig($plugin_ocsinventoryng_ocsservers_id),
            $data['entities_id'], $locations_id);
            //Check if machine could be linked with another one already in DB
            $rulelink         = new RuleImportComputerCollection();
            $rulelink_results = array();
            $params           = array('entities_id'   => $data['entities_id'],
               'plugin_ocsinventoryng_ocsservers_id'
               => $plugin_ocsinventoryng_ocsservers_id,
               'ocsid'        => $ocsid);
               $rulelink_results = $rulelink->processAllRules(Toolbox::stripslashes_deep($input),
               array(), $params);

               //If at least one rule matched
               //else do import as usual
               if (isset($rulelink_results['action'])) {
                  $rules_matched['RuleImportComputer'] = $rulelink_results['_ruleid'];

                  switch ($rulelink_results['action']) {
                     case self::LINK_RESULT_NO_IMPORT :
                        return array('status'     => self::COMPUTER_LINK_REFUSED,
                        'entities_id'  => $data['entities_id'],
                        'rule_matched' => $rules_matched);

                     case self::LINK_RESULT_LINK :
                        if (is_array($rulelink_results['found_computers'])
                        && count($rulelink_results['found_computers']) > 0) {

                           foreach ($rulelink_results['found_computers'] as $tmp => $computers_id) {
                              if (self::linkComputer($ocsid, $plugin_ocsinventoryng_ocsservers_id,
                              $computers_id)) {
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
               $computers_id = $comp->add($input, array('unicity_error_message' => false));
               if ($computers_id) {
                  $ocsid      = $computer['META']['ID'];
                  $changes[0] = '0';
                  $changes[1] = "";
                  $changes[2] = $ocsid;
                  PluginOcsinventoryngOcslink::history($computers_id, $changes,
                  PluginOcsinventoryngOcslink::HISTORY_OCS_IMPORT);

                  if ($idlink = self::ocsLink($computer['META']['ID'], $plugin_ocsinventoryng_ocsservers_id, $computers_id)) {
                     self::updateComputer($idlink, $plugin_ocsinventoryng_ocsservers_id, 0);
                  }

                  //Return code to indicates that the machine was imported
                  return array('status'       => self::COMPUTER_IMPORTED,
                  'entities_id'  => $data['entities_id'],
                  'rule_matched' => $rules_matched,
                  'computers_id' => $computers_id);
               }
               return array('status'       => self::COMPUTER_NOT_UNIQUE,
               'entities_id'  => $data['entities_id'],
               'rule_matched' => $rules_matched) ;
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
    * @param $force bool : force update ?
    *
    * @return action done
    **/
   static function updateComputer($ID, $plugin_ocsinventoryng_ocsservers_id, $dohistory, $force=0) {
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
            "DISPLAY"=> array(
               "CHECKSUM"=> PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE,
         )
         );
         $computer = $ocsClient->getComputer($line['ocsid'],$options);
         $data_ocs = $computer;

         // Need do history to be 2 not to lock fields
         if ($dohistory) {
            $dohistory = 2;
         }

         if ($computer) {
            // automatic transfer computer
            if ($CFG_GLPI['transfers_id_auto']>0 && Session::isMultiEntitiesMode()) {
               self::transferComputer($line, $data_ocs);
               $comp->getFromDB($line["computers_id"]);
            }

            // update last_update and and last_ocs_update
            $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                             SET `last_update` = '" . $_SESSION["glpi_currenttime"] . "',
                             `last_ocs_update` = '" . $data_ocs["META"]["LASTDATE"] . "',
                             `ocs_agent_version` = '".$data_ocs["HARDWARE"]["USERAGENT"]." ',
                             `last_ocs_conn` = '".$data_ocs["HARDWARE"]["LASTCOME"]." ',
                             `ip_src` = '".$data_ocs["HARDWARE"]["IPSRC"]." '
                             WHERE `id` = '$ID'";
            $DB->query($query);
            //Add  || $data_ocs["META"]["CHECKSUM"] > self::MAX_CHECKSUM for bug of checksum 18446744073689088230
            if ($force  || $data_ocs["META"]["CHECKSUM"] > self::MAX_CHECKSUM) {
               $ocs_checksum = self::MAX_CHECKSUM;
               self::getDBocs($plugin_ocsinventoryng_ocsservers_id)->setChecksum($ocs_checksum, $line['ocsid']);
            } else {
               $ocs_checksum = $data_ocs["META"]["CHECKSUM"];
            }
            $mixed_checksum = intval($ocs_checksum) & intval($cfg_ocs["checksum"]);
            //By default log history
            $loghistory["history"] = 1;
            // Is an update to do ?
            $bios   = false;
            $memories   = false;
            $storages   = array();
            $hardware   = false;
            $videos   = false;
            $sounds   = false;
            $networks   = false;
            $modems   = false;
            $monitors   = false;
            $printers   = false;
            $inputs   = false;
            $softwares    = false;
            $drives   = false;
            $registry   = false;
            $virtualmachines = false;
               
            if ($mixed_checksum) {

               // Get updates on computers :
               $computer_updates = importArrayFromDB($line["computer_update"]);
               if (!in_array(self::IMPORT_TAG_078, $computer_updates)) {
                  $computer_updates = self::migrateComputerUpdates($line["computers_id"],
                  $computer_updates);
               }

               $ocsCheck = array();
               if ($mixed_checksum & pow(2, self::HARDWARE_FL)) {
                  $ocsCheck[]=  PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE;
               }
               if ($mixed_checksum & pow(2, self::BIOS_FL)) {
                  $bios = true;
                  $ocsCheck[]= PluginOcsinventoryngOcsClient::CHECKSUM_BIOS;

               }
               if ($mixed_checksum & pow(2, self::MEMORIES_FL)) {

                  if ($cfg_ocs["import_device_memory"]) {
                     $memories=true;
                     $ocsCheck[]=  PluginOcsinventoryngOcsClient::CHECKSUM_MEMORY_SLOTS;
                  }
               }
               if ($mixed_checksum & pow(2, self::STORAGES_FL)) {

                  if ($cfg_ocs["import_device_hdd"]){
                     $storages["hdd"] = true;
                     $ocsCheck[]=  PluginOcsinventoryngOcsClient::CHECKSUM_STORAGE_PERIPHERALS;
                  }
                  if ($cfg_ocs["import_device_drive"]){
                     $storages["drive"] = true;
                     $ocsCheck[]= PluginOcsinventoryngOcsClient::CHECKSUM_STORAGE_PERIPHERALS;
                  }
               }
               if ($mixed_checksum & pow(2, self::HARDWARE_FL)) {

                  if ($cfg_ocs["import_device_processor"]){
                     $hardware=true;
                     $ocsCheck[]=  PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE;
                  }
               }
               if ($mixed_checksum & pow(2, self::VIDEOS_FL)) {
                  if ($cfg_ocs["import_device_gfxcard"]){
                     $videos=true;
                     $ocsCheck[]=  PluginOcsinventoryngOcsClient::CHECKSUM_VIDEO_ADAPTERS;
                  }
               }
               if ($mixed_checksum & pow(2, self::SOUNDS_FL)) {

                  if ($cfg_ocs["import_device_sound"]){
                     $sounds=true;
                     $ocsCheck[]=  PluginOcsinventoryngOcsClient::CHECKSUM_SOUND_ADAPTERS;
                  }
               }
               if ($mixed_checksum & pow(2, self::NETWORKS_FL)) {


                  if ($cfg_ocs["import_device_iface"] || $cfg_ocs["import_ip"]){
                     $networks=true;
                     $ocsCheck[]=  PluginOcsinventoryngOcsClient::CHECKSUM_NETWORK_ADAPTERS;
                  }
               }
               if ($mixed_checksum & pow(2, self::MODEMS_FL)
               || $mixed_checksum & pow(2, self::PORTS_FL)) {

                  if ($cfg_ocs["import_device_modem"]) {
                     $modems=true;
                     $ocsCheck[]=  PluginOcsinventoryngOcsClient::CHECKSUM_MODEMS;
                  }
               }
               if ($mixed_checksum & pow(2, self::MONITORS_FL)) {

                  if ($cfg_ocs["import_monitor"]) {
                     $monitors=true;
                     $ocsCheck[]=  PluginOcsinventoryngOcsClient::CHECKSUM_MONITORS;
                  }
               }
               if ($mixed_checksum & pow(2, self::PRINTERS_FL)) {

                  if ($cfg_ocs["import_printer"]){
                     $printers=true;
                     $ocsCheck[]=  PluginOcsinventoryngOcsClient::CHECKSUM_PRINTERS;
                  }
               }
               if ($mixed_checksum & pow(2, self::INPUTS_FL)){

                  if ($cfg_ocs["import_periph"]){
                     $inputs=true;
                     $ocsCheck[]=  PluginOcsinventoryngOcsClient::CHECKSUM_INPUT_DEVICES;
                  }

               }
               if ($mixed_checksum & pow(2, self::SOFTWARES_FL)){
                  if ($cfg_ocs["import_software"]) {
                     $softwares=true;
                     $ocsCheck[]=  PluginOcsinventoryngOcsClient::CHECKSUM_SOFTWARE;
                     if ($cfg_ocs["use_soft_dict"]) {
                        $ocsWanted =  PluginOcsinventoryngOcsClient::WANTED_DICO_SOFT;
                     }

                  }
               }
               if ($mixed_checksum & pow(2, self::DRIVES_FL)){
                  $drives=true;
                  $ocsCheck[]=  PluginOcsinventoryngOcsClient::CHECKSUM_LOGICAL_DRIVES;

               }
               if ($mixed_checksum & pow(2, self::REGISTRY_FL)){
                  if ($cfg_ocs["import_registry"]){
                     $registry=true;
                     $ocsCheck[]=  PluginOcsinventoryngOcsClient::CHECKSUM_REGISTRY;
                  }
               }
               if ($mixed_checksum & pow(2, self::VIRTUALMACHINES_FL)){
                  //no vm in ocs before 1.3
                  if (!($cfg_ocs['ocs_version'] < self::OCS1_3_VERSION_LIMIT)){
                     $virtualmachines=true;
                     $ocsCheck[]=  PluginOcsinventoryngOcsClient::CHECKSUM_VIRTUAL_MACHINES;
                  }
               }

               if ($ocsCheck) {
                  $ocsCheckResult = $ocsCheck[0];
                  foreach ($ocsCheck as $ocsChecksum) {
                     $ocsCheckResult = $ocsCheckResult | $ocsChecksum;
                  }
               }else{
                  $ocsCheckResult = 0;
               }
               if (!isset($ocsWanted)) {
                  $ocsWanted = 0;
               }
               $options = array(
                  'DISPLAY'=>array(
                     'CHECKSUM'=>$ocsCheckResult,
                     'WANTED'=>$ocsWanted
               ),
               );
               $ocsComputer = $ocsClient->getComputer($line['ocsid'],$options);
               // Update Administrative informations
               self::updateAdministrativeInfo($line['computers_id'], $line['ocsid'],
               $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs,
               $computer_updates, $comp->fields['entities_id'],
               $dohistory);
               if ($mixed_checksum & pow(2, self::HARDWARE_FL)) {
                  $p = array('computers_id'      => $line['computers_id'],
                     'ocs_id'            => $line['ocsid'],
                     'plugin_ocsinventoryng_ocsservers_id'
                     => $plugin_ocsinventoryng_ocsservers_id,
                     'cfg_ocs'           => $cfg_ocs,
                     'computers_updates' => $computer_updates,
                     'dohistory'         => $dohistory,
                     'check_history'     => true,
                     'entities_id'       => $comp->fields['entities_id']);
                     $loghistory = self::updateHardware($p);
               }
               if ($bios) {
                  self::updateBios($line['computers_id'], $ocsComputer,
                  $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs,
                  $computer_updates, $dohistory, $comp->fields['entities_id']);
               }
               // Get import devices
               $import_device = array();
               $types         = $CFG_GLPI['ocsinventoryng_devices_index'];
               foreach ($types as $old => $type) {
                  $associated_type  = str_replace('Item_', '', $type);
                  $associated_table = getTableForItemType($associated_type);
                  $fk               = getForeignKeyFieldForTable($associated_table);

                  $query = "SELECT `i`.`id`, `t`.`designation` as `name`
                        FROM `".getTableForItemType($type)."` as i
                        LEFT JOIN `$associated_table` as t ON (`t`.`id`=`i`.`$fk`)
                        WHERE `itemtype`='Computer'
                        AND `items_id`='".$line['computers_id']."'
                        AND `is_dynamic`";

                  $prevalue = $type. self::FIELD_SEPARATOR;
                  foreach ($DB->request($query) as $data) {

                     $import_device[$prevalue.$data['id']] = $prevalue.$data["name"];

                     // TODO voir si il ne serait pas plus simple propre
                     // en adaptant updateDevices
                     // $import_device[$type][$data['id']] = $data["name"];
                  }
               }
               if ($memories) {
                  self::updateDevices("Item_DeviceMemory", $line['computers_id'], $ocsComputer,
                  $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs,
                  $import_device, '', $dohistory);
               }
               if ($storages) {
                  if($storages["hdd"]){
                     self::updateDevices("Item_DeviceHardDrive", $line['computers_id'], $ocsComputer,
                     $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs,
                     $import_device, '', $dohistory);
                  }
                  if($storages["drive"]){}
                  self::updateDevices("Item_DeviceDrive", $line['computers_id'], $ocsComputer,
                  $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs,
                  $import_device, '', $dohistory);
               }
            }
            if ($hardware) {
               self::updateDevices("Item_DeviceProcessor", $line['computers_id'], $ocsComputer,
               $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs,
               $import_device, '', $dohistory);
            }
            if ($videos) {
               self::updateDevices("Item_DeviceGraphicCard", $line['computers_id'], $ocsComputer,
               $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs,
               $import_device, '', $dohistory);
            }
            if ($bios) {
               self::updateDevices("PluginOcsinventoryngItem_DeviceBiosdata", $line['computers_id'], $ocsComputer,
               $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs,
               $import_device, '', $dohistory);
            }
            if ($sounds) {
               self::updateDevices("Item_DeviceSoundCard", $line['computers_id'], $ocsComputer,
               $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs,
               $import_device, '', $dohistory);
            }
            if ($networks) {
               self::updateDevices("Item_DeviceNetworkCard", $line['computers_id'], $ocsComputer,
               $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs,
               $import_device, array(),
               $dohistory);
            }
            if ($modems) {
               self::updateDevices("Item_DevicePci", $line['computers_id'], $ocsComputer,
               $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs,
               $import_device, '', $dohistory);
            }
            if ($monitors) {
               self::importMonitor($cfg_ocs, $line['computers_id'],
               $plugin_ocsinventoryng_ocsservers_id, $ocsComputer,
               $comp->fields["entities_id"], $dohistory);
            }
            if ($printers) {
               self::importPrinter($cfg_ocs, $line['computers_id'],
               $plugin_ocsinventoryng_ocsservers_id, $ocsComputer,
               $comp->fields["entities_id"], $dohistory);
            }
            if ($inputs){
               self::importPeripheral($cfg_ocs, $line['computers_id'],
               $plugin_ocsinventoryng_ocsservers_id, $ocsComputer,
               $comp->fields["entities_id"], $dohistory);
            }
            if ($softwares){
               // Get import software
               self::updateSoftware($line['computers_id'], $comp->fields["entities_id"],
               $ocsComputer, $plugin_ocsinventoryng_ocsservers_id,
               $cfg_ocs,
               (!$loghistory["history"]?0:$dohistory));
            }
            if ($drives){
               // Get import drives
               self::updateDisk($line['computers_id'], $ocsComputer,
               $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs, $dohistory);
            }
            if ($registry){
               //import registry entries not needed
               self::updateRegistry($line['computers_id'], $ocsComputer,
               $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs);
            }
            if ($virtualmachines){
               // Get import vm
               self::updateVirtualMachines($line['computers_id'], $ocsComputer,
               $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs,
               $dohistory);
            }
            //Update TAG
            self::updateTag($line, $data_ocs);
            // Update OCS Cheksum
            $oldchecksum = self::getDBocs($plugin_ocsinventoryng_ocsservers_id)->getChecksum($line['ocsid']);
            self::getDBocs($plugin_ocsinventoryng_ocsservers_id)->setChecksum($oldchecksum - $mixed_checksum, $line['ocsid']);
            //Return code to indicate that computer was synchronized
            return array('status'       => self::COMPUTER_SYNCHRONIZED,
                  'entitites_id' => $comp->fields["entities_id"],
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


   static function getComputerHardware($params = array()) {
      global $DB;

      $options['computers_id']                        = 0;
      $options['ocs_id']                              = 0;
      $options['plugin_ocsinventoryng_ocsservers_id'] = 0;
      $options['cfg_ocs']                             = array();
      $options['computers_update']                    = array();
      $options['check_history']                       = true;
      $options['do_history']                          = 2;

      foreach ($params as $key => $value) {
         $options[$key] = $value;
      }

      $is_utf8 = $options['cfg_ocs']["ocs_db_utf8"];
      $ocsServerId = $options['plugin_ocsinventoryng_ocsservers_id'];
      self::checkOCSconnection($ocsServerId);
      $ocsClient = self::getDBocs($ocsServerId);

      $ocsComputer = $ocsClient->getComputer($options['ocs_id'], array(
            'DISPLAY' => array(
               'CHECKSUM' => PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE
      )
      ));

      $logHistory = 1;

      if ($ocsComputer) {
         $hardware = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($ocsComputer['HARDWARE']));
         $compupdate = array();

         if (intval($options['cfg_ocs']["import_os_serial"]) > 0 && !in_array("os_license_number", $options['computers_updates'])) {

            if (!empty ($hardware["WINPRODKEY"])) {
               $compupdate["os_license_number"] = self::encodeOcsDataInUtf8($is_utf8, $hardware["WINPRODKEY"]);
            }
            if (!empty ($hardware["WINPRODID"])) {
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
                                          = '".$options['ocs_id']."'
                                   AND `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id`
                                          = '".$ocsServerId."'";

            $res_computer = $DB->query($sql_computer);

            if ($DB->numrows($res_computer) ==  1) {
               $data_computer = $DB->fetch_array($res_computer);
               $computerOS    = $data_computer["os_name"];
               $computerOSSP  = $data_computer["os_sp"];

               //Do not log software history in case of OS or Service Pack change
               if (!$options['do_history']
               || $computerOS != $hardware["OSNAME"]
               || $computerOSSP != $hardware["OSCOMMENTS"]) {
                  $logHistory = 0;
               }
            }
         }

         if (intval($options['cfg_ocs']["import_general_os"]) > 0) {
            if (!in_array("operatingsystems_id", $options['computers_updates'])) {
               $osname = self::encodeOcsDataInUtf8($is_utf8, $hardware['OSNAME']);
               $compupdate["operatingsystems_id"] = Dropdown::importExternal('OperatingSystem',
               $osname);
            }

            if (!in_array("operatingsystemversions_id", $options['computers_updates'])) {
               $compupdate["operatingsystemversions_id"]
               = Dropdown::importExternal('OperatingSystemVersion',
               self::encodeOcsDataInUtf8($is_utf8,
               $hardware["OSVERSION"]));
            }

            if (!strpos($hardware["OSCOMMENTS"], "CEST")
            && !in_array("operatingsystemservicepacks_id", $options['computers_updates'])) {// Not linux comment

               $compupdate["operatingsystemservicepacks_id"]
               = Dropdown::importExternal('OperatingSystemServicePack',
               self::encodeOcsDataInUtf8($is_utf8,
               $hardware["OSCOMMENTS"]));
            }
         }

         if (intval($options['cfg_ocs']["import_general_domain"]) > 0
         && !in_array("domains_id", $options['computers_updates'])){
            $compupdate["domains_id"] = Dropdown::importExternal('Domain',
            self::encodeOcsDataInUtf8($is_utf8,
            $hardware["WORKGROUP"]));
         }

         if (intval($options['cfg_ocs']["import_general_contact"]) > 0
         && !in_array("contact", $options['computers_updates'])){

            $compupdate["contact"] = self::encodeOcsDataInUtf8($is_utf8, $hardware["USERID"]);
            $query = "SELECT `id`
                      FROM `glpi_users`
                      WHERE `name` = '" . $hardware["USERID"] . "';";
            $result = $DB->query($query);

            if ($DB->numrows($result) == 1 && !in_array("users_id", $options['computers_updates'])){
               $compupdate["users_id"] = $DB->result($result, 0, 0);
            }
         }

         if (intval($options['cfg_ocs']["import_general_name"]) > 0
         && !in_array("name", $options['computers_updates'])){
            $compupdate["name"] = self::encodeOcsDataInUtf8($is_utf8, $hardware["NAME"]);
         }

         if (intval($options['cfg_ocs']["import_general_comment"]) > 0
         && !in_array("comment", $options['computers_updates'])){

            $compupdate["comment"] = "";
            if (!empty ($hardware["DESCRIPTION"]) && $hardware["DESCRIPTION"] != NOT_AVAILABLE){
               $compupdate["comment"] .= self::encodeOcsDataInUtf8($is_utf8, $hardware["DESCRIPTION"])
               . "\r\n";
            }
            $compupdate["comment"] .= sprintf(__('%1$s: %2$s'), __('Swap', 'ocsinventoryng'),
            self::encodeOcsDataInUtf8($is_utf8,$hardware["SWAP"]));
         }

         if ($options['cfg_ocs']['ocs_version'] >= self::OCS1_3_VERSION_LIMIT
         && intval($options['cfg_ocs']["import_general_uuid"]) > 0
         && !in_array("uuid", $options['computers_updates'])){
            $compupdate["uuid"] = $hardware["UUID"];
         }

         return array('logHistory' => $logHistory, 'fields'     => $compupdate);
      }
   }


   /**
    * Update the computer hardware configuration
    *
    * @param $params array
    *
    * @return nothing.
    **/
   static function updateHardware($params=array()){
      global $DB;

      $p = array('computers_id'                          => 0,
                 'ocs_id'                                => 0,
                 'plugin_ocsinventoryng_ocsservers_id'   => 0,
                 'cfg_ocs'                               => array(),
                 'computers_updates'                     => array(),
                 'dohistory'                             => true,
                 'check_history'                         => true,
                 'entities_id'                           => 0);
      foreach ($params as $key => $value){
         $p[$key] = $value;
      }

      self::checkOCSconnection($p['plugin_ocsinventoryng_ocsservers_id']);
      $results = self::getComputerHardware($params);

      if (count($results['fields'])){
         $results['fields']["id"]          = $p['computers_id'];
         $results['fields']["entities_id"] = $p['entities_id'];
         $results['fields']["_nolock"]     = true;
         $comp                             = new Computer();
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
    * @param $ocsid integer : glpi computer id
    * @param $plugin_ocsinventoryng_ocsservers_id integer : ocs server id
    * @param $cfg_ocs array : ocs config
    * @param $computer_updates array : already updated fields of the computer
    * @param $dohistory boolean : log changes?
    * @param entities_id the entity in which the computer is imported
    *
    * @return nothing.
    **/
   static function updateBios($computers_id, $ocsComputer, $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs,
   $computer_updates, $dohistory=2, $entities_id=0){

      $compupdate = array();
      $computer = $ocsComputer;
      if (!is_null($computer)) {
         $compudate  = array();
         $bios = $computer['BIOS'];

         if ($cfg_ocs["import_general_serial"]
               && $cfg_ocs["import_general_serial"] > 0
                  && intval($cfg_ocs["import_device_bios"]) > 0
                     && !in_array("serial", $computer_updates)){
            $compupdate["serial"] = self::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'],
            $bios["SSN"]);
         }

         if (intval($cfg_ocs["import_general_model"]) > 0
               && intval($cfg_ocs["import_device_bios"]) > 0
                  && !in_array("computermodels_id", $computer_updates)) {

            $compupdate["computermodels_id"]
            = Dropdown::importExternal('ComputerModel',
            self::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'],
            $bios["SMODEL"]),
            -1,
            (isset($bios["SMANUFACTURER"])
            ?array("manufacturer" => $bios["SMANUFACTURER"])
            :array()));
         }

         if (intval($cfg_ocs["import_general_manufacturer"]) > 0
               && intval($cfg_ocs["import_device_bios"]) > 0
                  && !in_array("manufacturers_id", $computer_updates)) {

            $compupdate["manufacturers_id"]
            = Dropdown::importExternal('Manufacturer',
            self::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'],
            $bios["SMANUFACTURER"]));
         }

         if (intval($cfg_ocs["import_general_type"]) > 0
               && intval($cfg_ocs["import_device_bios"]) > 0
                  && !empty ($bios["TYPE"])
                     && !in_array("computertypes_id", $computer_updates)) {

            $compupdate["computertypes_id"]
            = Dropdown::importExternal('ComputerType',
            self::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'],
            $bios["TYPE"]));
         }

         if (count($compupdate)) {
            $compupdate["id"]          = $computers_id;
            $compupdate["entities_id"] = $entities_id;
            $compupdate["_nolock"]     = true;
            $comp                      = new Computer();
            $comp->update($compupdate, $dohistory);
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
    **/
   static function importGroup($value, $entities_id){
      global $DB;

      if (empty ($value)){
         return 0;
      }

      $query2 = "SELECT `id`
                 FROM `glpi_groups`
                 WHERE `name` = '$value'
                       AND `entities_id` = '$entities_id'";
      $result2 = $DB->query($query2);

      if ($DB->numrows($result2) == 0){
         $group                = new Group();
         $input["name"]        = $value;
         $input["entities_id"] = $entities_id;
         return $group->add($input);
      }
      $line2 = $DB->fetch_array($result2);
      return $line2["id"];
   }


   /**
    * Displays a list of computers that can be cleaned.
    *
    * @param $plugin_ocsinventoryng_ocsservers_id int : id of ocs server in GLPI
    * @param $check string : parameter for HTML input checkbox
    * @param $start int : parameter for Html::printPager method
    *
    * @return nothing
    **/
   static function showComputersToClean($plugin_ocsinventoryng_ocsservers_id, $check, $start){
      global $DB, $CFG_GLPI;

      self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);

      if (!Session::haveRight("plugin_ocsinventoryng_clean", READ)){
         return false;
      }
      $canedit = Session::haveRight("plugin_ocsinventoryng_clean", UPDATE);

      // Select unexisting OCS hardware
      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $computers = $ocsClient->getComputers(array());

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
      if ($DB->numrows($result) > 0){
         while ($data = $DB->fetch_array($result)){
            $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));
            if (!isset ($hardware[$data["ocsid"]])){
               $ocs_missing[$data["ocsid"]] = $data["ocsid"];
            }
         }
      }

      $sql_ocs_missing = "";
      if (count($ocs_missing)){
         $sql_ocs_missing = " OR `ocsid` IN ('".implode("','",$ocs_missing)."')";
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
                                    = '$plugin_ocsinventoryng_ocsservers_id')".
      $sql_ocs_missing.")".
      getEntitiesRestrictRequest(" AND", "glpi_plugin_ocsinventoryng_ocslinks");

      $result_glpi = $DB->query($query_glpi);

      // fetch all links missing between glpi and OCS
      $already_linked = array();
      if ($DB->numrows($result_glpi) > 0){
         while ($data = $DB->fetch_assoc($result_glpi)){
            $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));

            $already_linked[$data["ocsid"]]["entities_id"]  = $data["entities_id"];
            if (Toolbox::strlen($data["ocs_deviceid"])>20) { // Strip datetime tag
               $already_linked[$data["ocsid"]]["ocs_deviceid"] = substr($data["ocs_deviceid"], 0,
               -20);
            } else{
               $already_linked[$data["ocsid"]]["ocs_deviceid"] = $data["ocs_deviceid"];
            }
            $already_linked[$data["ocsid"]]["date"]         = $data["last_update"];
            $already_linked[$data["ocsid"]]["id"]           = $data["id"];
            $already_linked[$data["ocsid"]]["in_ocs"]       = isset($hardware[$data["ocsid"]]);

            if ($data["name"] == null){
               $already_linked[$data["ocsid"]]["in_glpi"] = 0;
            } else{
               $already_linked[$data["ocsid"]]["in_glpi"] = 1;
            }
         }
      }

      echo "<div class='center'>";
      echo "<h2>" . __('Clean links between GLPI and OCSNG', 'ocsinventoryng') . "</h2>";

      $target = $CFG_GLPI['root_doc'].'/plugins/ocsinventoryng/front/ocsng.clean.php';
      if (($numrows = count($already_linked)) > 0){
         $parameters = "check=$check";
         Html::printPager($start, $numrows, $target, $parameters);

         // delete end
         array_splice($already_linked, $start + $_SESSION['glpilist_limit']);

         // delete begin
         if ($start > 0){
            array_splice($already_linked, 0, $start);
         }

         echo "<form method='post' id='ocsng_form' name='ocsng_form' action='".$target."'>";
         if ($canedit){
            self::checkBox($target);
         }
         echo "<table class='tab_cadre'>";
         echo "<tr><th>".__('Item')."</th><th>".__('Import date in GLPI', 'ocsinventoryng')."</th>";
         echo "<th>" . __('Existing in GLPI', 'ocsinventoryng') . "</th>";
         echo "<th>" . __('Existing in OCSNG', 'ocsinventoryng') . "</th>";
         if (Session::isMultiEntitiesMode()){
            echo "<th>" . __('Entity'). "</th>";
         }
         if ($canedit){
            echo "<th>&nbsp;</th>";
         }
         echo "</tr>\n";

         echo "<tr class='tab_bg_1'><td colspan='6' class='center'>";
         if ($canedit){
            echo "<input class='submit' type='submit' name='clean_ok' value=\"".
            _sx('button','Clean')."\">";
         }
         echo "</td></tr>\n";

         foreach ($already_linked as $ID => $tab){
            echo "<tr class='tab_bg_2 center'>";
            echo "<td>" . $tab["ocs_deviceid"] . "</td>\n";
            echo "<td>" . Html::convDateTime($tab["date"]) . "</td>\n";
            echo "<td>" . Dropdown::getYesNo($tab["in_glpi"]) . "</td>\n";
            echo "<td>" . Dropdown::getYesNo($tab["in_ocs"]) . "</td>\n";
            if (Session::isMultiEntitiesMode()){
               echo "<td>".Dropdown::getDropdownName('glpi_entities', $tab['entities_id'])."</td>\n";
            }
            if ($canedit){
               echo "<td><input type='checkbox' name='toclean[" . $tab["id"] . "]' ".
               (($check == "all") ? "checked" : "") . "></td>";
            }
            echo "</tr>\n";
         }

         echo "<tr class='tab_bg_1'><td colspan='6' class='center'>";
         if ($canedit){
            echo "<input class='submit' type='submit' name='clean_ok' value=\"".
            _sx('button','Clean')."\">";
         }
         echo "</td></tr>";
         echo "</table>\n";
         Html::closeForm();
         Html::printPager($start, $numrows, $target, $parameters);

      } else{
         echo "<div class='center b '>" . __('No item to clean', 'ocsinventoryng') . "</div>";
         Html::displayBackLink();
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
    **/
   static function cleanLinksFromList($plugin_ocsinventoryng_ocsservers_id, $ocslinks_id){
      global $DB;

      $cfg_ocs = self::getConfig($plugin_ocsinventoryng_ocsservers_id);

      foreach ($ocslinks_id as $key => $val){

         $query = "SELECT*
                   FROM `glpi_plugin_ocsinventoryng_ocslinks`
                   WHERE `id` = '$key'
                         AND `plugin_ocsinventoryng_ocsservers_id`
                                 = '$plugin_ocsinventoryng_ocsservers_id'";

         if ($result = $DB->query($query)){
            if ($DB->numrows($result)>0){
               $data = $DB->fetch_array($result);

               $comp = new Computer();
               if ($cfg_ocs['deleted_behavior']){
                  if ($cfg_ocs['deleted_behavior'] == 1){
                     $comp->delete( array("id" => $data["computers_id"]), 0);
                  } else{
                     if (preg_match('/STATE_(.*)/',$cfg_ocs['deleted_behavior'],$results)){
                        $tmp['id']          = $data["computers_id"];
                        $tmp['states_id']   = $results[1];
                        $tmp['entities_id'] = $data['entities_id'];
                        $tmp["_nolock"]     = true;
                        $comp->update($tmp);
                     }
                  }
               }

               //Add history to indicates that the machine was deleted from OCS
               $changes[0] = '0';
               $changes[1] = $data["ocsid"];
               $changes[2] = "";
               PluginOcsinventoryngOcslink::history($data["computers_id"], $changes,
               PluginOcsinventoryngOcslink::HISTORY_OCS_DELETE);

               $query = "DELETE
                         FROM `glpi_plugin_ocsinventoryng_ocslinks`
                         WHERE `id` = '" . $data["id"] . "'";
               $DB->query($query);
            }
         }
      }
   }


   static function showComputersToUpdate($plugin_ocsinventoryng_ocsservers_id, $check, $start){
      global $DB, $CFG_GLPI;

      self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      if (!Session::haveRight("plugin_ocsinventoryng", UPDATE)){
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
         echo "<div class='center b'>".__('No new computer to be updated', 'ocsinventoryng')."</div>";
         return;
      }

      $already_linked_ids = array();
      while ($data = $DB->fetch_assoc($already_linked_result)) {
         $already_linked_ids []= $data['ocsid'];
      }

      // Fetch linked computers from ocs
      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $ocsResult = $ocsClient->getComputers(array(
            'OFFSET' => $start,
            'MAX_RECORDS' => $_SESSION['glpilist_limit'],
            'ORDER' => 'LASTDATE',
            'FILTER' => array(
                'IDS' => $already_linked_ids,
                'CHECKSUM' => $cfg_ocs["checksum"]
      )
      ));
      
      if (isset($ocsResult['COMPUTERS'])) {
         if (count($ocsResult['COMPUTERS']) > 0) {
            // Get all ids of the returned computers
            $ocs_computer_ids = array();
            $hardware = array();
            foreach ($ocsResult['COMPUTERS'] as $computer) {
               $ID = $computer['META']['ID'];
               $ocs_computer_ids []= $ID;

               $hardware[$ID]["date"] = $computer['META']["LASTDATE"];
               $hardware[$ID]["name"] = addslashes($computer['META']["NAME"]);
            }

            // Fetch all linked computers from GLPI that were returned from OCS
            $query_glpi = "SELECT `glpi_plugin_ocsinventoryng_ocslinks`.`last_update` AS last_update,
                                  `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id` AS computers_id,
                                  `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid` AS ocsid,
                                  `glpi_computers`.`name` AS name,
                                  `glpi_plugin_ocsinventoryng_ocslinks`.`use_auto_update`,
                                  `glpi_plugin_ocsinventoryng_ocslinks`.`id`
                           FROM `glpi_plugin_ocsinventoryng_ocslinks`
                           LEFT JOIN `glpi_computers` ON (`glpi_computers`.`id`=computers_id)
                           WHERE `glpi_plugin_ocsinventoryng_ocslinks`.`plugin_ocsinventoryng_ocsservers_id`
                                       = '$plugin_ocsinventoryng_ocsservers_id'
                                  AND `glpi_plugin_ocsinventoryng_ocslinks`.`ocsid` IN (".implode(',', $ocs_computer_ids).")
                           ORDER BY `glpi_plugin_ocsinventoryng_ocslinks`.`use_auto_update` DESC,
                                    last_update,
                                    name";
            $result_glpi = $DB->query($query_glpi);

            // Get all links between glpi and OCS
            $already_linked = array();
            if ($DB->numrows($result_glpi) > 0){
               while ($data = $DB->fetch_assoc($result_glpi)){
                  $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));
                  if (isset ($hardware[$data["ocsid"]])){
                     $already_linked[$data["ocsid"]]["date"]            = $data["last_update"];
                     $already_linked[$data["ocsid"]]["name"]            = $data["name"];
                     $already_linked[$data["ocsid"]]["id"]              = $data["id"];
                     $already_linked[$data["ocsid"]]["computers_id"]    = $data["computers_id"];
                     $already_linked[$data["ocsid"]]["ocsid"]           = $data["ocsid"];
                     $already_linked[$data["ocsid"]]["use_auto_update"] = $data["use_auto_update"];
                  }
               }
            }
            echo "<div class='center'>";
            echo "<h2>" . __('Computers updated in OCSNG', 'ocsinventoryng') . "</h2>";

            $target = $CFG_GLPI['root_doc'].'/plugins/ocsinventoryng/front/ocsng.sync.php';
            if (($numrows = $ocsResult['TOTAL_COUNT']) > 0){
               $parameters = "check=$check";
               Html::printPager($start, $numrows, $target, $parameters);

               echo "<form method='post' id='ocsng_form' name='ocsng_form' action='".$target."'>";
               self::checkBox($target);

               echo "<table class='tab_cadre_fixe'>";
               echo "<tr class='tab_bg_1'><td colspan='5' class='center'>";
               echo "<input class='submit' type='submit' name='update_ok' value=\"".
               _sx('button','Synchronize', 'ocsinventoryng')."\">";
               echo "</td></tr>\n";

               echo "<tr><th>". __('Update computers', 'ocsinventoryng')."</th>";
               echo "<th>".__('Import date in GLPI', 'ocsinventoryng')."</th>";
               echo "<th>" . __('Last OCSNG inventory date', 'ocsinventoryng')."</th>";
               echo "<th>". __('Auto update', 'ocsinventoryng')."</th>";
               echo "<th>&nbsp;</th></tr>\n";

               foreach ($already_linked as $ID => $tab){
                  echo "<tr class='tab_bg_2 center'>";
                  echo "<td><a href='" . $CFG_GLPI["root_doc"] . "/front/computer.form.php?id=".
                  $tab["computers_id"] . "'>" . $tab["name"] . "</a></td>\n";
                  echo "<td>" . Html::convDateTime($tab["date"]) . "</td>\n";
                  echo "<td>" . Html::convDateTime($hardware[$tab["ocsid"]]["date"]) . "</td>\n";
                  echo "<td>" . Dropdown::getYesNo($tab["use_auto_update"]) . "</td>\n";
                  echo "<td><input type='checkbox' name='toupdate[" . $tab["id"] . "]' ".
                  (($check == "all") ? "checked" : "") . "></td></tr>\n";
               }

               echo "<tr class='tab_bg_1'><td colspan='5' class='center'>";
               echo "<input class='submit' type='submit' name='update_ok' value=\"".
               _sx('button','Synchronize', 'ocsinventoryng')."\">";
               echo "<input type=hidden name='plugin_ocsinventoryng_ocsservers_id' ".
                      "value='$plugin_ocsinventoryng_ocsservers_id'>";
               echo "</td></tr>";

               echo "<tr class='tab_bg_1'><td colspan='5' class='center'>";
               self::checkBox($target);
               echo "</table>\n";
               Html::closeForm();
               Html::printPager($start, $numrows, $target, $parameters);

            } else{
               echo "<br><span class='b'>" . __('Update computers', 'ocsinventoryng') . "</span>";
            }
            echo "</div>";

         } else{
            echo "<div class='center b'>".__('No new computer to be updated', 'ocsinventoryng')."</div>";
         }
      } else{
         echo "<div class='center b'>".__('No new computer to be updated', 'ocsinventoryng')."</div>";
      }
   }


   static function mergeOcsArray($computers_id, $tomerge, $field){
      global $DB;

      $query = "SELECT `$field`
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `computers_id` = '$computers_id'";

      if ($result = $DB->query($query)){
         if ($DB->numrows($result)){
            $tab    = importArrayFromDB($DB->result($result, 0, 0));
            $newtab = array_merge($tomerge, $tab);
            $newtab = array_unique($newtab);

            $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                      SET `$field` = '" . addslashes(exportArrayToDB($newtab)) . "'
                      WHERE `computers_id` = '$computers_id'";
            if ($DB->query($query)){
               return true;
            }
         }
      }
      return false;
   }


   static function deleteInOcsArray($computers_id, $todel, $field, $is_value_to_del=false){
      global $DB;

      $query = "SELECT `$field`
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `computers_id` = '$computers_id'";

      if ($result = $DB->query($query)){
         if ($DB->numrows($result)){
            $tab = importArrayFromDB($DB->result($result, 0, 0));

            if ($is_value_to_del){
               $todel = array_search($todel, $tab);
            }
            if (isset($tab[$todel])){
               unset ($tab[$todel]);
               $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                         SET `$field` = '" . addslashes(exportArrayToDB($tab)) . "'
                         WHERE `computers_id` = '$computers_id'";
               if ($DB->query($query)){
                  return true;
               }
            }
         }
      }
      return false;
   }


   static function replaceOcsArray($computers_id, $newArray, $field){
      global $DB;

      $newArray = addslashes(exportArrayToDB($newArray));

      $query = "SELECT `$field`
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `computers_id` = '$computers_id'";

      if ($result = $DB->query($query)){
         if ($DB->numrows($result)){
            $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                      SET `$field` = '" . $newArray . "'
                      WHERE `computers_id` = '$computers_id'";
            $DB->query($query);
         }
      }
   }


   static function addToOcsArray($computers_id, $toadd, $field){
      global $DB;

      $query = "SELECT `$field`
                FROM `glpi_plugin_ocsinventoryng_ocslinks`
                WHERE `computers_id` = '$computers_id'";

      if ($result = $DB->query($query)){
         if ($DB->numrows($result)){
            $tab = importArrayFromDB($DB->result($result, 0, 0));

            // Stripslashes because importArray get clean array
            foreach ($toadd as $key => $val){
               $tab[] = stripslashes($val);
            }

            $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                      SET `$field` = '" . addslashes(exportArrayToDB($tab)) . "'
                      WHERE `computers_id` = '$computers_id'";
            $DB->query($query);
            
            return true;
         }
      }
      return false;
   }


   /**
    * Display a list of computers to add or to link
    *
    * @param plugin_ocsinventoryng_ocsservers_id the ID of the ocs server
    * @param advanced display detail about the computer import or not (target entity, matched rules, etc.)
    * @param check indicates if checkboxes are checked or not
    * @param start display a list of computers starting at rowX
    * @param entity a list of entities in which computers can be added or linked
    * @param tolinked false for an import, true for a link
    *
    * @return nothing
    **/
   static function showComputersToAdd($serverId, $advanced, $check, $start, $entity=0, $tolinked=false){
      global $DB, $CFG_GLPI;

      if (!Session::haveRight("plugin_ocsinventoryng", UPDATE)){
         return false;
      }
      
      $title = __('Import new computers', 'ocsinventoryng');
      if ($tolinked) {
         $title = __('Link new OCSNG computers to existing GLPI computers',
               'ocsinventoryng');
      }
      $target = $CFG_GLPI['root_doc'].'/plugins/ocsinventoryng/front/ocsng.import.php';
      if ($tolinked){
         $target = $CFG_GLPI['root_doc'].'/plugins/ocsinventoryng/front/ocsng.link.php';
      }

      // Get all links between glpi and OCS
      $query_glpi = "SELECT ocsid
                     FROM `glpi_plugin_ocsinventoryng_ocslinks`
                     WHERE `plugin_ocsinventoryng_ocsservers_id` = '$serverId'";
      $result_glpi = $DB->query($query_glpi);
      $already_linked = array();
      if ($DB->numrows($result_glpi) > 0){
         while ($data = $DB->fetch_array($result_glpi)){
            $already_linked []= $data["ocsid"];
         }
      }

      $cfg_ocs = self::getConfig($serverId);

      $computerOptions = array(
            'OFFSET' => $start,
            'MAX_RECORDS' => $_SESSION['glpilist_limit'],
            'ORDER' => 'LASTDATE',
            'FILTER' => array(
                'EXCLUDE_IDS' => $already_linked
            ),
            'DISPLAY' => array(
                'CHECKSUM' => PluginOcsinventoryngOcsClient::CHECKSUM_BIOS
            ),
            'ORDER' => 'NAME'
            );

      if ($cfg_ocs["tag_limit"] and $tag_limit = explode("$", trim($cfg_ocs["tag_limit"]))) {
         $computerOptions['FILTER']['TAGS'] = $tag_limit;
      }

      if ($cfg_ocs["tag_exclude"] and $tag_exclude = explode("$", trim($cfg_ocs["tag_exclude"]))) {
         $computerOptions['FILTER']['EXCLUDE_TAGS'] = $tag_exclude;
      }
      $ocsClient = self::getDBocs($serverId);
      $ocsResult = $ocsClient->getComputers($computerOptions);
      
      
      if (isset($ocsResult['COMPUTERS'])) {
         $computers = $ocsResult['COMPUTERS'];
         if (count($computers)) {
            // Get all hardware from OCS DB
            $hardware = array();
            foreach ($computers as $data) {
               $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));
               $id = $data['META']['ID'];
               $hardware[$id]["date"]         = $data['META']["LASTDATE"];
               $hardware[$id]["name"]         = $data['META']["NAME"];
               $hardware[$id]["TAG"]          = $data['META']["TAG"];
               $hardware[$id]["id"]           = $data['META']["ID"];

               if (isset($data['BIOS']) && count($data['BIOS'])) {
                  $hardware[$id]["serial"]       = $data['BIOS']["SSN"];
                  $hardware[$id]["model"]        = $data['BIOS']["SMODEL"];
                  $hardware[$id]["manufacturer"] = $data['BIOS']["SMANUFACTURER"];
               } else {
                  $hardware[$id]["serial"]       = '';
                  $hardware[$id]["model"]        = '';
                  $hardware[$id]["manufacturer"] = '';
               }
            }

            if ($tolinked && count($hardware)){
               echo "<div class='center b'>".
               __('Caution! The imported data (see your configuration) will overwrite the existing one',
                  'ocsinventoryng')."</div>";
            }
            echo "<div class='center'>";

            if ($numrows = $ocsResult['TOTAL_COUNT']) {
               $parameters = "check=$check";
               Html::printPager($start, $numrows, $target, $parameters);

               //Show preview form only in import even in multi-entity mode because computer import
               //can be refused by a rule
               if (!$tolinked){
                  echo "<div class='firstbloc'>";
                  echo "<form method='post' name='ocsng_import_mode' id='ocsng_import_mode'
                         action='$target'>\n";
                  echo "<table class='tab_cadre_fixe'>";
                  echo "<tr><th>". __('Manual import mode', 'ocsinventoryng'). "</th></tr>\n";
                  echo "<tr class='tab_bg_1'><td class='center'>";
                  if ($advanced){
                     Html::showSimpleForm($target, 'change_import_mode',
                     __('Disable preview', 'ocsinventoryng'),
                     array('id' => 'false'));
                  } else{
                     Html::showSimpleForm($target, 'change_import_mode',
                     __('Enable preview', 'ocsinventoryng'),
                     array('id' => 'true'));
                  }
                  echo "</td></tr>";
                  echo "<tr class='tab_bg_1'><td class='center b'>".
                  __('Check first that duplicates have been correctly managed in OCSNG',
                           'ocsinventoryng')."</td>";
                  echo "</tr></table>";
                  Html::closeForm();
                  echo "</div>";
               }

               echo "<form method='post' name='ocsng_form' id='ocsng_form' action='$target'>";
               if (!$tolinked){
                  self::checkBox($target);
               }
               echo "<table class='tab_cadre_fixe'>";

               echo "<tr class='tab_bg_1'><td colspan='" . (($advanced || $tolinked) ? 10 : 7) . "' class='center'>";
               echo "<input class='submit' type='submit' name='import_ok' value=\"".
               _sx('button', 'Import', 'ocsinventoryng')."\">";
               echo "</td></tr>\n";

               echo "<tr><th>".__('Name'). "</th>\n";
               echo "<th>".__('Manufacturer')."</th>\n";
               echo "<th>" .__('Model')."</th>\n";
               echo "<th>".__('Serial number')."</th>\n";
               echo "<th>" . __('Date')."</th>\n";
               echo "<th>".__('OCSNG TAG', 'ocsinventoryng')."</th>\n";
               if ($advanced && !$tolinked){
                  echo "<th>" . __('Match the rule ?', 'ocsinventoryng') . "</th>\n";
                  echo "<th>" . __('Destination entity') . "</th>\n";
                  echo "<th>" . __('Target location', 'ocsinventoryng') . "</th>\n";
               }
               echo "<th width='20%'>&nbsp;</th></tr>\n";

               $rule = new RuleImportEntityCollection();
               foreach ($hardware as $ID => $tab){
                     $comp = new Computer();
                     $comp->fields["id"] = $tab["id"];
                     $data = array();

                  if ($advanced && !$tolinked){
                     $data = $rule->processAllRules(array('ocsservers_id' => $serverId,
                                                          '_source'       => 'ocsinventoryng'),
                     array(), array('ocsid' =>$tab["id"]));
                  }
                  echo "<tr class='tab_bg_2'><td>". $tab["name"] . "</td>\n";
                  echo "<td>".$tab["manufacturer"]."</td><td>".$tab["model"]."</td>";
                  echo "<td>".$tab["serial"]."</td>\n";
                  echo "<td>" . Html::convDateTime($tab["date"]) . "</td>\n";
                  echo "<td>" . $tab["TAG"] . "</td>\n";
                  if ($advanced && !$tolinked){
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
                     echo "<td width='30%'>";
                     if (!isset($data['locations_id'])){
                        $data['locations_id'] = 0;
                     }
                     $loc = "toimport_locations[".$tab["id"]."]";
                     Location::dropdown(array('name'     => $loc,
                                              'value'    => $data['locations_id'],
                                              'comments' => 0));
                     echo "</td>\n";
                  }
                  echo "<td>";
                  if (!$tolinked) {
                     echo "<input type='checkbox' name='toimport[" . $tab["id"] . "]' ".
                     ($check == "all" ? "checked" : "") . ">";
                  } else {

                     $tab['entities_id'] = $entity;
                     $rulelink         = new RuleImportComputerCollection();
                     $rulelink_results = array();
                     $params           = array('entities_id' => $entity,
                                               'plugin_ocsinventoryng_ocsservers_id'
                                               => $serverId);
                                               $rulelink_results = $rulelink->processAllRules(Toolbox::stripslashes_deep($tab),
                                               array(), $params);

                                               //Look for the computer using automatic link criterias as defined in OCSNG configuration
                                               $options       = array('name' => "tolink[".$tab["id"]."]");
                                               $show_dropdown = true;
                                               //If the computer is not explicitly refused by a rule
                                               if (!isset($rulelink_results['action'])
                                               || $rulelink_results['action'] != self::LINK_RESULT_NO_IMPORT){

                                                if (!empty($rulelink_results['found_computers'])){
                                                   $options['value']  = $rulelink_results['found_computers'][0];
                                                   $options['entity'] = $entity;
                                                }
                                                $options['width'] = "100%";
                                                Computer::dropdown($options);
                                               } else{
                                                echo "<img src='".$CFG_GLPI['root_doc']. "/pics/redbutton.png'>";
                                               }
                  }
                  echo "</td></tr>\n";
               }

               echo "<tr class='tab_bg_1'><td colspan='" . (($advanced || $tolinked) ? 10 : 7) . "' class='center'>";
               echo "<input class='submit' type='submit' name='import_ok' value=\"".
               _sx('button', 'Import', 'ocsinventoryng')."\">\n";
               echo "<input type=hidden name='plugin_ocsinventoryng_ocsservers_id' ".
                "value='$serverId'>";
               echo "</td></tr>";
               echo "</table>\n";
               Html::closeForm();

               if (!$tolinked){
                  self::checkBox($target);
               }

               Html::printPager($start, $numrows, $target, $parameters);

            } else {
               echo "<table class='tab_cadre_fixe'>";
               echo "<tr><th>" . $title . "</th></tr>\n";
               echo "<tr class='tab_bg_1'>";
               echo "<td class='center b'>".__('No new computer to be imported', 'ocsinventoryng').
              "</td></tr>\n";
               echo "</table>";
            }
            echo "</div>";

         } else {
            echo "<div class='center'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" .$title . "</th></tr>\n";
            echo "<tr class='tab_bg_1'>";
            echo "<td class='center b'>" .__('No new computer to be imported', 'ocsinventoryng').
           "</td></tr>\n";
            echo "</table></div>";
         }
      } else {
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>" .$title . "</th></tr>\n";
         echo "<tr class='tab_bg_1'>";
         echo "<td class='center b'>" .__('No new computer to be imported', 'ocsinventoryng').
        "</td></tr>\n";
         echo "</table></div>";
      }
   }


   static function migrateImportDevice($computers_id, $import_device){

      $new_import_device = array(self::IMPORT_TAG_078);
      if (count($import_device)){
         foreach ($import_device as $key=>$val){
            $tmp = explode(self::FIELD_SEPARATOR, $val);

            if (isset($tmp[1])) { // Except for old IMPORT_TAG
               $tmp2                     = explode(self::FIELD_SEPARATOR, $key);
               // Index Could be 1330395 (from glpi 0.72)
               // Index Could be 5$$$$$5$$$$$5$$$$$5$$$$$5$$$$$1330395 (glpi 0.78 bug)
               // So take the last part of the index
               $key2                     = $tmp[0].self::FIELD_SEPARATOR.array_pop($tmp2);
               $new_import_device[$key2] = $val;
            }

         }
      }
      //Add the new tag as the first occurence in the array
      //self::replaceOcsArray($computers_id, $new_import_device, "import_device");
      return $new_import_device;
   }


   static function migrateComputerUpdates($computers_id, $computer_update){

      $new_computer_update = array(self::IMPORT_TAG_078);

      $updates = array('ID'                  => 'id',
                       'FK_entities'         => 'entities_id',
                       'tech_num'            => 'users_id_tech',
                       'comments'            => 'comment',
                       'os'                  => 'operatingsystems_id',
                       'os_version'          => 'operatingsystemversions_id',
                       'os_sp'               => 'operatingsystemservicepacks_id',
                       'os_license_id'       => 'os_licenseid',
                       'auto_update'         => 'autoupdatesystems_id',
                       'location'            => 'locations_id',
                       'domain'              => 'domains_id',
                       'network'             => 'networks_id',
                       'model'               => 'computermodels_id',
                       'type'                => 'computertypes_id',
                       'tplname'             => 'template_name',
                       'FK_glpi_enterprise'  => 'manufacturers_id',
                       'deleted'             => 'is_deleted',
                       'notes'               => 'notepad',
                       'ocs_import'          => 'is_dynamic',
                       'FK_users'            => 'users_id',
                       'FK_groups'           => 'groups_id',
                       'state'               => 'states_id');

      if (count($computer_update)){
         foreach ($computer_update as $field){
            if (isset($updates[$field])){
               $new_computer_update[] = $updates[$field];
            } else{
               $new_computer_update[] = $field;
            }
         }
      }

      //Add the new tag as the first occurence in the array
      self::replaceOcsArray($computers_id, $new_computer_update, "computer_update");
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
    * @param $ocsid integer : ocs computer id (ID).
    * @param $plugin_ocsinventoryng_ocsservers_id integer : ocs server id
    * @param $cfg_ocs array : ocs config
    * @param $import_device array : already imported devices
    * @param $import_ip array : already imported ip
    * @param $dohistory boolean : log changes?
    *
    * @return Nothing (void).
    **/
   static function updateDevices($devicetype, $computers_id, $ocsComputer, $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs,
   $import_device, $import_ip, $dohistory){
      global $DB;
      $prevalue = $devicetype.self::FIELD_SEPARATOR;
      $do_clean   = false;
      $comp = new Computer();
      $comp->getFromDB($computers_id);
      $entities_id = $comp->fields['entities_id'];
      switch ($devicetype){
         case "Item_DeviceMemory":
            $CompDevice = new $devicetype();
            //Memoire
            $do_clean = true;

            if ($ocsComputer) {
               // TODO a revoir
               // pourquoi supprimer tous les importés ?
               // En 0.83 cette suppression était lié à la présence du tag
               // IMPORT_TAG_078, et donc exécuté 1 seule fois pour redressement
               // Cela pete, je pense, tous les lock
               //if (count($import_device)){
               //   $dohistory = false;
               //   foreach ($import_device as $key => $val) {
               //      $tmp = explode(self::FIELD_SEPARATOR,$key);
               //      if (isset($tmp[1]) && $tmp[0] == "Item_DeviceMemory") {
               //         $CompDevice->delete(array('id'          => $tmp[1],
               //                                   '_no_history' => true), 1);
               //         unset($import_device[$key]);
               //      }
               //   }
               //}
               if (isset($ocsComputer['MEMORIES'])) {
                  foreach ($ocsComputer['MEMORIES'] as $line2) {
                     $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
                     if (isset($line2["CAPACITY"]) && $line2["CAPACITY"]!="No"){
                        $ram["designation"] = "";
                        if ($line2["TYPE"]!="Empty Slot" && $line2["TYPE"]!="Unknown"){
                           $ram["designation"] = $line2["TYPE"];
                        }
                        if ($line2["DESCRIPTION"]){
                           if (!empty($ram["designation"])){
                              $ram["designation"] .= " - ";
                           }
                           $ram["designation"] .= $line2["DESCRIPTION"];
                        }
                        if (!is_numeric($line2["CAPACITY"])){
                           $line2["CAPACITY"] = 0;
                        }
                        $ram["size_default"] = $line2["CAPACITY"];
                        $ram["entities_id"] = $entities_id;
                        if (!in_array(stripslashes($prevalue.$ram["designation"]), $import_device)){

                           $ram["frequence"]            = $line2["SPEED"];
                           $ram["devicememorytypes_id"] = Dropdown::importExternal('DeviceMemoryType',
                           $line2["TYPE"]);

                           $DeviceMemory = new DeviceMemory();
                           $ram_id = $DeviceMemory->import($ram);
                           if ($ram_id){
                              $devID = $CompDevice->add(array('items_id'               => $computers_id,
                                                                 'itemtype'            => 'Computer',
                                                                 'entities_id'         => $entities_id,
                                                                 'devicememories_id'   => $ram_id,
                                                                 'size'                => $line2["CAPACITY"],
                                                                 'is_dynamic'          => 1,
                                                                 '_no_history'         => !$dohistory));
                           }
                        } else {
                           $tmp = array_search(stripslashes($prevalue . $ram["designation"]),
                           $import_device);
                           list($type,$id) = explode(self::FIELD_SEPARATOR, $tmp);

                           $CompDevice->update(array('id'  => $id,
                                                        'size' => $line2["CAPACITY"]));
                           unset ($import_device[$tmp]);
                        }
                     }
                  }
               }
            }
            break;

         case "Item_DeviceHardDrive":
            $CompDevice = new $devicetype();
            //Disque Dur

            $do_clean = true;

            if ($ocsComputer) {
               if (isset($ocsComputer['STORAGES'])) {
                  foreach ($ocsComputer['STORAGES'] as $line2) {
                     $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
                     if (!empty ($line2["DISKSIZE"]) && preg_match("/disk|spare\sdrive/i", $line2["TYPE"])){
                        if ($line2["NAME"]){
                           $dd["designation"] = $line2["NAME"];
                        } else{
                           if ($line2["MODEL"]){
                              $dd["designation"] = $line2["MODEL"];
                           } else{
                              $dd["designation"] = "Unknown";
                           }
                        }
                        if (!is_numeric($line2["DISKSIZE"])){
                           $line2["DISKSIZE"] = 0;
                        }
                        $dd["entities_id"] = $entities_id;
                        if (!in_array(stripslashes($prevalue.$dd["designation"]), $import_device)){
                           $dd["capacity_default"] = $line2["DISKSIZE"];
                           $DeviceHardDrive = new DeviceHardDrive();
                           $dd_id = $DeviceHardDrive->import($dd);
                           if ($dd_id){
                              $devID = $CompDevice->add(array('items_id'               => $computers_id,
                                                                 'itemtype'            => 'Computer',
                                                                 'entities_id'         => $entities_id,
                                                                 'deviceharddrives_id' => $dd_id,
                                                                 'serial'            => $line2["SERIALNUMBER"],
                                                                 'capacity'            => $line2["DISKSIZE"],
                                                                 'is_dynamic'          => 1,
                                                                 '_no_history'         => !$dohistory));
                           }
                        } else{
                           $tmp = array_search(stripslashes($prevalue . $dd["designation"]),
                           $import_device);
                           list($type,$id) = explode(self::FIELD_SEPARATOR, $tmp);
                           $CompDevice->update(array('id'          => $id,
                                                        'capacity' => $line2["DISKSIZE"],
                                                        'serial'            => $line2["SERIALNUMBER"]));
                           unset ($import_device[$tmp]);
                        }
                     }
                  }
               }
            }
            break;

         case "Item_DeviceDrive":
            $CompDevice = new $devicetype();
            //lecteurs
            $do_clean = true;
            if ($ocsComputer) {
               if (isset($ocsComputer['STORAGES'])) {
                  foreach ($ocsComputer['STORAGES'] as $line2) {
                     $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
                     if (empty ($line2["DISKSIZE"]) || !preg_match("/disk/i", $line2["TYPE"])){
                        if ($line2["NAME"]){
                           $stor["designation"] = $line2["NAME"];
                        } else{
                           if ($line2["MODEL"]){
                              $stor["designation"] = $line2["MODEL"];
                           } else{
                              $stor["designation"] = "Unknown";
                           }
                        }
                        $stor["entities_id"] = $entities_id;
                        if (!in_array(stripslashes($prevalue.$stor["designation"]),
                        $import_device)){
                           $DeviceDrive = new DeviceDrive();
                           $stor_id = $DeviceDrive->import($stor);
                           if ($stor_id){
                              $devID = $CompDevice->add(array('items_id'            => $computers_id,
                                                                 'itemtype'         => 'Computer',
                                                                 'entities_id'      => $entities_id,
                                                                 'devicedrives_id'  => $stor_id,
                                                                 'is_dynamic'       => 1,
                                                                 '_no_history'      => !$dohistory));
                           }
                        } else{
                           $tmp = array_search(stripslashes($prevalue.$stor["designation"]),
                           $import_device);
                           unset ($import_device[$tmp]);
                        }
                     }
                  }
               }
            }

            break;

         case "Item_DevicePci":
            $CompDevice = new $devicetype();
            //Modems
            $do_clean = true;
            if ($ocsComputer) {
               if (isset($ocsComputer['MODEMS'])) {
                  foreach ($ocsComputer['MODEMS'] as $line2) {
                     $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
                     $mdm["designation"] = $line2["NAME"];
                     $mdm["entities_id"] = $entities_id;
                     if (!in_array(stripslashes($prevalue.$mdm["designation"]), $import_device)){
                        if (!empty ($line2["DESCRIPTION"])){
                           $mdm["comment"] = $line2["TYPE"] . "\r\n" . $line2["DESCRIPTION"];
                        }
                        $DevicePci = new DevicePci();
                        $mdm_id = $DevicePci->import($mdm);
                        if ($mdm_id){
                           $devID = $CompDevice->add(array('items_id'            => $computers_id,
                                                               'itemtype'        => 'Computer',
                                                               'entities_id'     => $entities_id,
                                                               'devicepcis_id'   => $mdm_id,
                                                               'is_dynamic'      => 1,
                                                               '_no_history'     => !$dohistory));
                        }
                     } else{
                        $tmp = array_search(stripslashes($prevalue.$mdm["designation"]),
                        $import_device);
                        unset ($import_device[$tmp]);
                     }
                  }
               }
            }
            //Ports
            if ($ocsComputer) {
               if (isset($ocsComputer['PORTS'])) {
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
                     if (!empty ($port["designation"])) {
                        if (!in_array(stripslashes($prevalue.$port["designation"]),
                        $import_device)){
                           if (!empty ($line2["DESCRIPTION"]) && $line2["DESCRIPTION"] != "None") {
                              $port["comment"] = $line2["DESCRIPTION"];
                           }
                           $DevicePci = new DevicePci();
                           $port_id   = $DevicePci->import($port);
                           if ($port_id) {
                              $devID = $CompDevice->add(array('items_id'      => $computers_id,
                                                              'itemtype'      => 'Computer',
                                                              'entities_id'   => $entities_id,
                                                              'devicepcis_id' => $port_id,
                                                              'is_dynamic'    => 1,
                                                              '_no_history'   => !$dohistory));
                           }
                        } else {
                           $tmp = array_search(stripslashes($prevalue.$port["designation"]),
                           $import_device);
                           unset ($import_device[$tmp]);
                        }
                     }
                  }
               }
            }
            break;
         case "Item_DeviceProcessor":
            $CompDevice = new $devicetype();
            //Processeurs:
            $do_clean = true;
            if ($ocsComputer) {
               if (isset($ocsComputer['HARDWARE'])) {
                  $line = $ocsComputer['HARDWARE'];
                  $line = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line));
                  for ($i=0 ; $i<$line["PROCESSORN"] ; $i++){
                     $processor = array();
                     $processor["designation"] = $line["PROCESSORT"];
                     if (!is_numeric($line["PROCESSORS"])){
                        $line["PROCESSORS"] = 0;
                     }
                     $processor["frequency_default"] = $line["PROCESSORS"];
                     $processor["frequence"] = $line["PROCESSORS"];
                     $processor["entities_id"] = $entities_id;
                     if (!in_array(stripslashes($prevalue.$processor["designation"]),
                     $import_device)){
                        $DeviceProcessor = new DeviceProcessor();
                        $proc_id         = $DeviceProcessor->import($processor);
                        if ($proc_id){
                           $devID = $CompDevice->add(array('items_id'            => $computers_id,
                                                               'itemtype'            => 'Computer',
                                                               'entities_id'         => $entities_id,
                                                               'deviceprocessors_id' => $proc_id,
                                                               'frequency'           => $line["PROCESSORS"],
                                                               'is_dynamic'          => 1,
                                                               '_no_history'         => !$dohistory));
                        }
                     } else {
                        $tmp = array_search(stripslashes($prevalue.$processor["designation"]),
                        $import_device);
                        list($type,$id) = explode(self::FIELD_SEPARATOR,$tmp);
                        $CompDevice->update(array('id'          => $id,
                                                     'frequency' => $line["PROCESSORS"]));
                        unset ($import_device[$tmp]);
                     }
                  }
               }
            }
            break;

         case "Item_DeviceNetworkCard":
            //Carte reseau
            PluginOcsinventoryngNetworkPort::importNetwork($plugin_ocsinventoryng_ocsservers_id, $cfg_ocs,
            $ocsComputer, $computers_id, $dohistory, $entities_id);
            break;

         case "Item_DeviceGraphicCard":
            $CompDevice = new $devicetype();
            //carte graphique
            $do_clean = true;
            if ($ocsComputer) {
               if (isset($ocsComputer['VIDEOS'])) {
                  foreach ($ocsComputer['VIDEOS'] as $line2) {
                     $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
                     if ($line2['NAME']) {
                        $video["designation"] = $line2["NAME"];
                        $video["entities_id"] = $entities_id;
                        if (!is_numeric($line2["MEMORY"])){
                           $line2["MEMORY"] = 0;
                        }
                        if (!in_array(stripslashes($prevalue.$video["designation"]), $import_device)){
                           $video["memory_default"] = $line2["MEMORY"];
                           $DeviceGraphicCard = new DeviceGraphicCard();
                           $video_id = $DeviceGraphicCard->import($video);
                           if ($video_id){
                              $devID = $CompDevice->add(array('items_id'                 => $computers_id,
                                                                 'itemtype'               => 'Computer',
                                                                 'entities_id'            => $entities_id,
                                                                 'devicegraphiccards_id'  => $video_id,
                                                                 'memory'                 => $line2["MEMORY"],
                                                                 'is_dynamic'             => 1,
                                                                 '_no_history'            => !$dohistory));
                           }
                        } else{
                           $tmp = array_search(stripslashes($prevalue.$video["designation"]),
                           $import_device);
                           list($type,$id) = explode(self::FIELD_SEPARATOR,$tmp);
                           $CompDevice->update(array('id'          => $id,
                                                        'memory' => $line2["MEMORY"]));
                           unset ($import_device[$tmp]);
                        }
                     }
                  }
               }
            }


            break;

         case "Item_DeviceSoundCard":
            $CompDevice = new $devicetype();
            //carte son
            $do_clean = true;
            if ($ocsComputer) {
               if (isset($ocsComputer['SOUNDS'])) {
                  foreach ($ocsComputer['SOUNDS'] as $line2) {
                     $line2 = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($line2));
                     if ($line2['NAME']) {
                        if (!$cfg_ocs["ocs_db_utf8"] && !Toolbox::seems_utf8($line2["NAME"])){
                           $line2["NAME"] = Toolbox::encodeInUtf8($line2["NAME"]);
                        }
                        $snd["entities_id"] = $entities_id;
                        $snd["designation"] = $line2["NAME"];
                        if (!in_array(stripslashes($prevalue.$snd["designation"]), $import_device)){
                           if (!empty ($line2["DESCRIPTION"])){
                              $snd["comment"] = $line2["DESCRIPTION"];
                           }
                           $DeviceSoundCard = new DeviceSoundCard();
                           $snd_id          = $DeviceSoundCard->import($snd);
                           if ($snd_id){
                              $devID = $CompDevice->add(array('items_id'           => $computers_id,
                                                                 'itemtype'            => 'Computer',
                                                                 'entities_id'     => $entities_id,
                                                                 'devicesoundcards_id' => $snd_id,
                                                                 'is_dynamic'          => 1,
                                                                 '_no_history'         => !$dohistory));
                           }
                        } else{
                           $id = array_search(stripslashes($prevalue.$snd["designation"]),
                           $import_device);
                           unset ($import_device[$id]);
                        }
                     }
                  }
               }
            }
            break;
         
         case "PluginOcsinventoryngItem_DeviceBiosdata":
            $CompDevice = new $devicetype();
            //Bios
            $do_clean = true;
            if ($ocsComputer) {

               if (isset($ocsComputer['BIOS'])) {
                  $bios["designation"] = $ocsComputer['BIOS']["BVERSION"];
                  $bios["assettag"] = $ocsComputer['BIOS']["ASSETTAG"];
                  $bios["entities_id"] = $entities_id;
                  //$date = str_replace("/", "-", $ocsComputer['BIOS']["BDATE"]);
                  //$date = date("Y-m-d", strtotime($date));
                  $bios["date"] = $ocsComputer['BIOS']["BDATE"];
                  $bios["manufacturers_id"]= Dropdown::importExternal('Manufacturer',
                                                                           self::encodeOcsDataInUtf8($cfg_ocs['ocs_db_utf8'],
                                                                           $ocsComputer['BIOS']["SMANUFACTURER"]));
                  if (!in_array(stripslashes($prevalue.$bios["designation"]), $import_device)){

                     $DeviceBios = new PluginOcsinventoryngDeviceBiosdata();
                     $bios_id = $DeviceBios->import($bios);
                     if ($bios_id){
                        $devID = $CompDevice->add(array('items_id'        => $computers_id,
                                                            'itemtype'        => 'Computer',
                                                            'plugin_ocsinventoryng_devicebiosdatas_id'   => $bios_id,
                                                            'is_dynamic'      => 1,
                                                            'entities_id'     => $entities_id,
                                                            '_no_history'     => !$dohistory));
                     }
                  } else{
                     $tmp = array_search(stripslashes($prevalue.$bios["designation"]),
                     $import_device);
                     unset ($import_device[$tmp]);
                  }
               }
            }
            break;
      }

      // Delete Unexisting Items not found in OCS
      if ($do_clean && count($import_device)){
         foreach ($import_device as $key => $val){
            if (!(strpos($key, $devicetype . '$$') === false)){
               list($type,$id) = explode(self::FIELD_SEPARATOR, $key);
               $CompDevice->delete(array('id'          => $id,
                                         '_no_history' => !$dohistory, 1), true);
            }
         }
      }

      //TODO Import IP
      if ($do_clean
      && count($import_ip)
      && $devicetype == "Item_DeviceNetworkCard"){
         foreach ($import_ip as $key => $val){
            if ($key>0){
               $netport = new NetworkPort();
               $netport->delete(array('id' => $key));
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
    * @return the html link to the computer in ocs console
    **/
   static function getComputerLinkToOcsConsole ($plugin_ocsinventoryng_ocsservers_id, $ocsid, $todisplay, $only_url=false){

      $ocs_config = self::getConfig($plugin_ocsinventoryng_ocsservers_id);
      $url        = '';

      if ($ocs_config["ocs_url"] != ''){
         //Display direct link to the computer in ocsreports
         $url = $ocs_config["ocs_url"];
         if (!preg_match("/\/$/i",$ocs_config["ocs_url"])){
            $url .= '/';
         }
         if ($ocs_config['ocs_version'] > self::OCS2_VERSION_LIMIT){
            $url = $url."index.php?function=computer&amp;head=1&amp;systemid=$ocsid";
         } else{
            $url = $url."machine.php?systemid=$ocsid";
         }

         if ($only_url){
            return $url;
         }
         return "<a href='$url'>".$todisplay."</a>";
      }
      return $url;
   }

   /**
    * Get IP address from OCS hardware table
    *
    * @param plugin_ocsinventoryng_ocsservers_id the ID of the OCS server
    * @param computers_id ID of the computer in OCS hardware table
    *
    * @return the ip address or ''
    **/
   static function getGeneralIpAddress($plugin_ocsinventoryng_ocsservers_id, $computers_id){
      self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      $options=array(
                  'DISPLAY' => array(
                  'CHECKSUM' => PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE
      )
      );
      $computer = $ocsClient->getComputer($computers_id,$options);
      $ipaddress = $computer["HARDWARE"]["IPADDR"];
      if ($ipaddress) {
         return $ipaddress;
      }


      return '';
   }


   static function getDevicesManagementMode($ocs_config, $itemtype){

      switch ($itemtype){
         case 'Monitor':
            return $ocs_config["import_monitor"];

         case 'Printer':
            return $ocs_config["import_printer"];

         case 'Peripheral':
            return $ocs_config["import_periph"];
      }
   }


   static function setEntityLock($entity){

      $fp = fopen(GLPI_LOCK_DIR . "/lock_entity_" . $entity, "w+");
      if (flock($fp, LOCK_EX)){
         return $fp;
      }
      fclose($fp);
      return false;
   }


   static function removeEntityLock($entity, $fp){

      flock($fp, LOCK_UN);
      fclose($fp);

      //Test if the lock file still exists before removing it
      // (sometimes another thread already removed the file)
      clearstatcache();
      if (file_exists(GLPI_LOCK_DIR . "/lock_entity_" . $entity)){
         @unlink(GLPI_LOCK_DIR . "/lock_entity_" . $entity);
      }
   }


   static function getFormServerAction($ID, $templateid){

      $action = "";
      if (!isset($withtemplate) || $withtemplate == ""){
         $action = "edit_server";

      } else if (isset($withtemplate) && $withtemplate == 1){
         if ($ID == -1 && $templateid == ''){
            $action = "add_template";
         } else{
            $action = "update_template";
         }

      } else if (isset($withtemplate) && $withtemplate == 2){
         if ($templateid== ''){
            $action = "edit_server";
         } else if ($ID == -1){
            $action = "add_server_with_template";
         } else{
            $action = "update_server_with_template";
         }
      }

      return $action;
   }


   static function getColumnListFromAccountInfoTable($ID){
      global $DB;

      $listColumn = array("0" => __('No import'));
      if ($ID != -1){
         if (self::checkOCSconnection($ID)){
            $ocsClient = self::getDBocs($ID);
            $AccountInfoColumns = $ocsClient->getAccountInfoColumns();
            if (count($AccountInfoColumns) > 0){
               foreach ($AccountInfoColumns as $id=>$name) {
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
    * @param $plugin_ocsinventoryng_ocsservers_id the ocs server id
    *
    * @return boolean
    */
   static function checkOCSconnection($serverId) {
      return self::getDBocs($serverId)->checkConnection();
   }


   /**
    * Get a connection to the OCS server
    *
    * @param $serverId the ocs server id
    *
    * @return PluginOcsinventoryngOcsClient the ocs client (database or soap)
    */
   static function getDBocs($serverId) {
      $config = self::getConfig($serverId);

      if ($config['conn_type'] == self::CONN_TYPE_DB) {
         return new PluginOcsinventoryngOcsDbClient(
         $serverId,
         $config['ocs_db_host'],
         $config['ocs_db_user'],
         $config['ocs_db_passwd'],
         $config['ocs_db_name']
         );
      } else {
         return new PluginOcsinventoryngOcsSoapClient(
         $serverId,
         $config['ocs_db_host'],
         $config['ocs_db_user'],
         $config['ocs_db_passwd']
         );
      }
   }


   /**
    * Choose an ocs server
    *
    * @return nothing.
    **/
   static function showFormServerChoice(){
      global $DB, $CFG_GLPI;

      $query = "SELECT*
                FROM `glpi_plugin_ocsinventoryng_ocsservers`
                WHERE `is_active`='1'
                ORDER BY `name` ASC";
      $result = $DB->query($query);

      if ($DB->numrows($result) > 1){
         echo "<form action=\"".$CFG_GLPI['root_doc']."/plugins/ocsinventoryng/front/ocsng.php\" method='post'>";
         echo "<div class='center'><table class='tab_cadre'>";
         echo "<tr class='tab_bg_2'>";
         echo "<th colspan='2'>".__('Choice of an OCSNG server', 'ocsinventoryng')."</th></tr>\n";

         echo "<tr class='tab_bg_2'><td class='center'>" .  __('Name'). "</td>";
         echo "<td class='center'>";
         echo "<select name='plugin_ocsinventoryng_ocsservers_id'>";
         while ($ocs = $DB->fetch_array($result)){
            echo "<option value='" . $ocs["id"] . "'>" . $ocs["name"] . "</option>";
         }
         echo "</select></td></tr>\n";

         echo "<tr class='tab_bg_2'><td class='center' colspan=2>";
         echo "<input class='submit' type='submit' name='ocs_showservers' value=\"".
         _sx('button','Post')."\"></td></tr>";
         echo "</table></div>\n";
         Html::closeForm();

      } else if ($DB->numrows($result) == 1){
         $ocs = $DB->fetch_array($result);
         Html::redirect($CFG_GLPI['root_doc']."/plugins/ocsinventoryng/front/ocsng.php?plugin_ocsinventoryng_ocsservers_id=" . $ocs["id"]);

      } else{
         echo "<div class='center'><table class='tab_cadre'>";
         echo "<tr class='tab_bg_2'>";
         echo "<th colspan='2'>".__('Choice of an OCSNG server', 'ocsinventoryng')."</th></tr>\n";

         echo "<tr class='tab_bg_2'>";
         echo "<td class='center' colspan=2>".__('No OCSNG server defined', 'ocsinventoryng').
              "</td></tr>";
         echo "</table></div>\n";
      }
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
    **/
   static function resetDropdown($glpi_computers_id, $field, $table){
      global $DB;

      $query = "SELECT `$field` AS val
                FROM `glpi_computers`
                WHERE `id` = '$glpi_computers_id'";
      $result = $DB->query($query);

      if ($DB->numrows($result) == 1){
         $value = $DB->result($result, 0, "val");
         $query = "SELECT COUNT(*) AS cpt
                   FROM `glpi_computers`
                   WHERE `$field` = '$value'";
         $result = $DB->query($query);

         if ($DB->result($result, 0, "cpt") == 1){
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
    **/
   static function resetRegistry($glpi_computers_id){
      global $DB;

      $query = "SELECT *
                FROM `glpi_plugin_ocsinventoryng_registrykeys`
                WHERE `computers_id` = '$glpi_computers_id'";
      $result = $DB->query($query);

      if ($DB->numrows($result) > 0){
         while ($data = $DB->fetch_assoc($result)){
            $query2 = "SELECT COUNT(*)
                       FROM `glpi_plugin_ocsinventoryng_registrykeys`
                       WHERE `computers_id` = '" . $data['computers_id'] . "'";
            $result2 = $DB->query($query2);

            $registry = new PluginOcsinventoryngRegistryKey();
            if ($DB->result($result2, 0, 0) == 1){
               $registry->delete(array('id' => $data['computers_id']), 1);
            }
         }
      }
   }


   /**
    * Delete all old printers of a computer.
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @return nothing.
    **/
   static function resetPrinters($glpi_computers_id){
      global $DB;

      $query = "SELECT*
                FROM `glpi_computers_items`
                WHERE `computers_id` = '$glpi_computers_id'
                      AND `itemtype` = 'Printer'";
      $result = $DB->query($query);

      if ($DB->numrows($result) > 0){
         $conn = new Computer_Item();

         while ($data = $DB->fetch_assoc($result)){
            $conn->delete(array('id' => $data['id']));

            $query2 = "SELECT COUNT(*)
                       FROM `glpi_computers_items`
                       WHERE `items_id` = '" . $data['items_id'] . "'
                             AND `itemtype` = 'Printer'";
            $result2 = $DB->query($query2);

            $printer = new Printer();
            if ($DB->result($result2, 0, 0) == 1){
               $printer->delete(array('id' => $data['items_id']), 1);
            }
         }
      }
   }


   /**
    * Delete all old monitors of a computer.
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @return nothing.
    **/
   static function resetMonitors($glpi_computers_id){
      global $DB;

      $query = "SELECT*
                FROM `glpi_computers_items`
                WHERE `computers_id` = '$glpi_computers_id'
                      AND `itemtype` = 'Monitor'";
      $result = $DB->query($query);

      $mon = new Monitor();
      if ($DB->numrows($result) > 0){
         $conn = new Computer_Item();

         while ($data = $DB->fetch_assoc($result)){
            $conn->delete(array('id' => $data['id']));

            $query2 = "SELECT COUNT(*)
                       FROM `glpi_computers_items`
                       WHERE `items_id` = '" . $data['items_id'] . "'
                             AND `itemtype` = 'Monitor'";
            $result2 = $DB->query($query2);

            if ($DB->result($result2, 0, 0) == 1){
               $mon->delete(array('id' => $data['items_id']), 1);
            }
         }
      }
   }


   /**
    * Delete all old periphs for a computer.
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @return nothing.
    **/
   static function resetPeripherals($glpi_computers_id){
      global $DB;

      $query = "SELECT*
                FROM `glpi_computers_items`
                WHERE `computers_id` = '$glpi_computers_id'
                      AND `itemtype` = 'Peripheral'";
      $result = $DB->query($query);

      $per = new Peripheral();
      if ($DB->numrows($result) > 0){
         $conn = new Computer_Item();
         while ($data = $DB->fetch_assoc($result)){
            $conn->delete(array('id' => $data['id']));

            $query2 = "SELECT COUNT(*)
                       FROM `glpi_computers_items`
                       WHERE `items_id` = '" . $data['items_id'] . "'
                             AND `itemtype` = 'Peripheral'";
            $result2 = $DB->query($query2);

            if ($DB->result($result2, 0, 0) == 1){
               $per->delete(array('id' => $data['items_id']), 1);
            }
         }
      }
   }


   /**
    * Delete all old softwares of a computer.
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @return nothing.
    **/
   static function resetSoftwares($glpi_computers_id){
      global $DB;

      $query = "SELECT*
                FROM `glpi_computers_softwareversions`
                WHERE `computers_id` = '$glpi_computers_id'";
      $result = $DB->query($query);

      if ($DB->numrows($result) > 0){
         while ($data = $DB->fetch_assoc($result)){
            $query2 = "SELECT COUNT(*)
                       FROM `glpi_computers_softwareversions`
                       WHERE `softwareversions_id` = '" . $data['softwareversions_id'] . "'";
            $result2 = $DB->query($query2);

            if ($DB->result($result2, 0, 0) == 1){
               $vers = new SoftwareVersion();
               $vers->getFromDB($data['softwareversions_id']);
               $query3 = "SELECT COUNT(*)
                          FROM `glpi_softwareversions`
                          WHERE `softwares_id`='" . $vers->fields['softwares_id'] . "'";
               $result3 = $DB->query($query3);

               if ($DB->result($result3, 0, 0) == 1){
                  $soft = new Software();
                  $soft->delete(array('id' => $vers->fields['softwares_id']), 1);
               }
               $vers->delete(array("id" => $data['softwareversions_id']));
            }
         }

         $query = "DELETE
                   FROM `glpi_computers_softwareversions`
                   WHERE `computers_id` = '$glpi_computers_id'";
         $DB->query($query);
      }
   }


   /**
    * Delete all old disks of a computer.
    *
    * @param $glpi_computers_id integer : glpi computer id.
    *
    * @return nothing.
    **/
   static function resetDisks($glpi_computers_id){
      global $DB;

      $query = "DELETE
                FROM `glpi_computerdisks`
                WHERE `computers_id` = '$glpi_computers_id'";
      $DB->query($query);
   }

   
   /**
    * Update config of a new version
    *
    * This function create a new software in GLPI with some general datas.
    *
    * @param $software : id of a software.
    * @param $version : version of the software
    *
    * @return integer : inserted version id.
    **/
   static function updateVersion($software, $version, $comments){
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_softwareversions`
                WHERE `softwares_id` = '$software'
                      AND `name` = '$version'";
      $result = $DB->query($query);

      if ($DB->numrows($result) > 0){
         $data = $DB->fetch_array($result);
         $input["id"]         = $data["id"];
         $input["comment"]   = $comments;
         $vers = new SoftwareVersion();
         $vers->update($input);
      }

      return;
   }
   
   /**
    * Import config of a new version
    *
    * This function create a new software in GLPI with some general datas.
    *
    * @param $software : id of a software.
    * @param $version : version of the software
    *
    * @return integer : inserted version id.
    **/
   static function importVersion($software, $version, $comments){
      global $DB;

      $isNewVers = 0;
      $query = "SELECT `id`
                FROM `glpi_softwareversions`
                WHERE `softwares_id` = '$software'
                      AND `name` = '$version'";
      $result = $DB->query($query);

      if ($DB->numrows($result) > 0){
         $data = $DB->fetch_array($result);
         $isNewVers = $data["id"];
      }

      if (!$isNewVers){
         $vers = new SoftwareVersion();
         // TODO : define a default state ? Need a new option in config
         // Use $cfg_ocs["states_id_default"] or create a specific one?
         $input["softwares_id"] = $software;
         $input["name"]         = $version;
         $input["comment"]      = $comments;
         $isNewVers             = $vers->add($input);
      }

      return ($isNewVers);
   }


   /**
    *
    * Synchronize virtual machines
    *
    * @param unknown $computers_id
    * @param unknown $ocsid
    * @param unknown $ocsservers_id
    * @param unknown $cfg_ocs
    * @param unknown $dohistory
    * @return boolean
    */
   static function updateVirtualMachines($computers_id, $ocsComuter, $ocsservers_id,
   $cfg_ocs, $dohistory){
      global  $DB;
      $already_processed = array();
      
      $virtualmachine = new ComputerVirtualMachine();
      if (isset($ocsComuter["VIRTUALMACHINES"])) {
         $ocsVirtualmachines = $ocsComuter["VIRTUALMACHINES"];
         if (count($ocsVirtualmachines) > 0){
            foreach ($ocsVirtualmachines as $ocsVirtualmachine) {
               $ocsVirtualmachine = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($ocsVirtualmachine));
               $vm                  = array();
               $vm['name']          = $ocsVirtualmachine['NAME'];
               $vm['vcpu']          = $ocsVirtualmachine['VCPU'];
               $vm['ram']           = $ocsVirtualmachine['MEMORY'];
               $vm['uuid']          = $ocsVirtualmachine['UUID'];
               $vm['computers_id']  = $computers_id;
               $vm['is_dynamic']    = 1;

               $vm['virtualmachinestates_id']  = Dropdown::importExternal('VirtualMachineState',
               $ocsVirtualmachine['STATUS']);
               $vm['virtualmachinetypes_id']   = Dropdown::importExternal('VirtualMachineType',
               $ocsVirtualmachine['VMTYPE']);
               $vm['virtualmachinesystems_id'] = Dropdown::importExternal('VirtualMachineType',
               $ocsVirtualmachine['SUBSYSTEM']);

               $query = "SELECT `id`
                         FROM `glpi_computervirtualmachines`
                         WHERE `computers_id`='$computers_id'
                            AND `is_dynamic`";
               if ($ocsVirtualmachine['UUID']) {
                  $query .= " AND `uuid`='".$ocsVirtualmachine['UUID']."'";
               } else {
                  // Failback on name
                  $query .= " AND `name`='".$ocsVirtualmachine['NAME']."'";
               }

               $results = $DB->query($query);
               if ($DB->numrows($results) > 0){
                  $id = $DB->result($results, 0, 'id');
               } else {
                  $id = 0;
               }
               if (!$id){
                  $virtualmachine->reset();
                  if (!$dohistory){
                     $vm['_no_history'] = true;
                  }
                  $id_vm = $virtualmachine->add($vm);
                  if ($id_vm){
                     $already_processed[] = $id_vm;
                  }
               } else{
                  if ($virtualmachine->getFromDB($id)){
                     $vm['id'] = $id;
                     $virtualmachine->update($vm);
                  }
                  $already_processed[] = $id;
               }
            }
         }
      }
      // Delete Unexisting Items not found in OCS
      //Look for all ununsed virtual machines
      $query = "SELECT `id`
                FROM `glpi_computervirtualmachines`
                WHERE `computers_id`='$computers_id'
                   AND `is_dynamic`";
      if (!empty($already_processed)){
         $query .= "AND `id` NOT IN (".implode(',', $already_processed).")";
      }
      foreach ($DB->request($query) as $data){
         //Delete all connexions
         $virtualmachine->delete(array('id'             => $data['id'],
                                         '_ocsservers_id' => $ocsservers_id), true);
      }
   }


   /**
    * Update config of a new software
    *
    * This function create a new software in GLPI with some general datas.
    *
    * @param $computers_id integer : glpi computer id.
    * @param $ocsid integer : ocs computer id (ID).
    * @param $ocsservers_id integer : ocs server id
    * @param $cfg_ocs array : ocs config
    * @param $dohistory array:
    *
    *@return Nothing (void).
    **/
   static function updateDisk($computers_id, $ocsComputer, $ocsservers_id,
   $cfg_ocs, $dohistory){
      global $DB;
      $already_processed = array();
      $drives = array();
      $logical_drives = $ocsComputer["DRIVES"];
      $d = new ComputerDisk();
      if (count($logical_drives)> 0){
         foreach ($logical_drives as $logical_drive) {
            $logical_drive = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($logical_drive));

            // Only not empty disk
            if ($logical_drive['TOTAL']>0){
               $disk                 = array();
               $disk['computers_id'] = $computers_id;
               $disk['is_dynamic']   = 1;

               // TYPE : vxfs / ufs  : VOLUMN = mount / FILESYSTEM = device
               if (in_array($logical_drive['TYPE'], array("vxfs", "ufs")) ){
                  $disk['name']           = $logical_drive['VOLUMN'];
                  $disk['mountpoint']     = $logical_drive['VOLUMN'];
                  $disk['device']         = $logical_drive['FILESYSTEM'];
                  $disk['filesystems_id'] = Dropdown::importExternal('Filesystem', $logical_drive["TYPE"]);

               } else if (in_array($logical_drive['FILESYSTEM'], array('ext2', 'ext3', 'ext4', 'ffs',
                                                              'fuseblk', 'fusefs', 'hfs', 'jfs',
                                                              'jfs2', 'Journaled HFS+', 'nfs',
                                                              'smbfs', 'reiserfs', 'vmfs', 'VxFS',
                                                              'ufs', 'xfs', 'zfs'))){
               // Try to detect mount point : OCS database is dirty
               $disk['mountpoint'] = $logical_drive['VOLUMN'];
               $disk['device']     = $logical_drive['TYPE'];

               // Found /dev in VOLUMN : invert datas
               if (strstr($logical_drive['VOLUMN'],'/dev/')){
                  $disk['mountpoint'] = $logical_drive['TYPE'];
                  $disk['device']     = $logical_drive['VOLUMN'];
               }

               if ($logical_drive['FILESYSTEM'] == "vmfs"){
                  $disk['name'] = basename($logical_drive['TYPE']);
               } else{
                  $disk['name']  = $disk['mountpoint'];
               }
               $disk['filesystems_id'] = Dropdown::importExternal('Filesystem',
               $logical_drive["FILESYSTEM"]);

                                                              } else if (in_array($logical_drive['FILESYSTEM'], array('FAT', 'FAT32', 'NTFS'))){
                                                               if (!empty($logical_drive['VOLUMN'])){
                                                                  $disk['name'] = $logical_drive['VOLUMN'];
                                                               } else{
                                                                  $disk['name'] = $logical_drive['LETTER'];
                                                               }
                                                               $disk['mountpoint']     = $logical_drive['LETTER'];
                                                               $disk['filesystems_id'] = Dropdown::importExternal('Filesystem',
                                                               $logical_drive["FILESYSTEM"]);
                                                              }

                                                              // Ok import disk
                                                              if (isset($disk['name']) && !empty($disk["name"])){
                                                               $disk['totalsize'] = $logical_drive['TOTAL'];
                                                               $disk['freesize']  = $logical_drive['FREE'];

                                                               $query = "SELECT `id`
                            FROM `glpi_computerdisks`
                            WHERE `computers_id`='$computers_id'
                               AND `name`='".$disk['name']."'
                               AND `is_dynamic`";
                                                               $results = $DB->query($query);
                                                               if ($DB->numrows($results) == 1){
                                                                  $id = $DB->result($results, 0, 'id');
                                                               } else {
                                                                  $id = false;
                                                               }

                                                               if (!$id){
                                                                  $d->reset();
                                                                  if (!$dohistory){
                                                                     $disk['_no_history'] = true;
                                                                  }
                                                                  $disk['is_dynamic'] = 1;
                                                                  $id_disk = $d->add($disk);
                                                                  $already_processed[] = $id_disk;
                                                               } else{
                                                                  // Only update if needed
                                                                  if ($d->getFromDB($id)){

                                                                     // Update on type, total size change or variation of 5%
                                                                     if ($d->fields['totalsize']!=$disk['totalsize']
                                                                     || ($d->fields['filesystems_id'] != $disk['filesystems_id'])
                                                                     || ((abs($disk['freesize']-$d->fields['freesize'])
                                                                     /$disk['totalsize']) > 0.05)){

                                                                        $toupdate['id']              = $id;
                                                                        $toupdate['totalsize']       = $disk['totalsize'];
                                                                        $toupdate['freesize']        = $disk['freesize'];
                                                                        $toupdate['filesystems_id']  = $disk['filesystems_id'];
                                                                        $d->update($toupdate);
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
      if (!empty($already_processed)){
         $query .= "AND `id` NOT IN (".implode(',', $already_processed).")";
      }
      foreach ($DB->request($query) as $data){
         //Delete all connexions
         $d->delete(array('id'             => $data['id'],
                           '_ocsservers_id' => $ocsservers_id), true);
      }
   }


   /**
    * Install a software on a computer - check if not already installed
    *
    * @param $computers_id ID of the computer where to install a software
    * @param $softwareversions_id ID of the version to install
    * @param $dohistory Do history?
    *
    * @return nothing
    **/
   static function installSoftwareVersion($computers_id, $softwareversions_id, $dohistory=1){
      global $DB;
      if (!empty ($softwareversions_id) && $softwareversions_id > 0) {
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
                                 '_no_history'         => !$dohistory,
                                 'is_dynamic'          => 1,
                                 'is_deleted'          => 0));
      }
      return 0;
   }


   /**
    * Update config of a new software
    *
    * This function create a new software in GLPI with some general data.
    *
    * @param $computers_id                         integer : glpi computer id.
    * @param $entity                               integer : entity of the computer
    * @param $ocsid                                integer : ocs computer id (ID).
    * @param $plugin_ocsinventoryng_ocsservers_id  integer : ocs server id
    * @param $cfg_ocs                              array   : ocs config
    * @param $import_software                      array   : already imported softwares
    * @param $dohistory                            boolean : log changes?
    *
    * @return Nothing (void).
    **/
   static function updateSoftware($computers_id, $entity, $ocsComputer,
   $plugin_ocsinventoryng_ocsservers_id, array $cfg_ocs, $dohistory){
      global $DB;
      $alread_processed         = array();
      $is_utf8                  = $cfg_ocs["ocs_db_utf8"];
      $computer_softwareversion = new Computer_SoftwareVersion();
      $softwares = array();
      //---- Get all the softwares for this machine from OCS -----//
      
      if (isset($ocsComputer['SOFTWARES'])) {
         $softwares=$ocsComputer["SOFTWARES"];
         $soft                = new Software();
         
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
            $imported[$data['id']] = strtolower($data['sname'].self::FIELD_SEPARATOR.$data['vname']);
         }

         if (count($softwares) > 0) {
            foreach ($softwares as $software) {
               $software    = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($software));

               //As we cannot be sure that data coming from OCS are in utf8, let's try to encode them
               //if possible
               foreach (array('NAME', 'PUBLISHER', 'VERSION') as $field) {
                  $software[$field] = self::encodeOcsDataInUtf8($is_utf8, $software[$field]);
               }

               //Replay dictionnary on manufacturer
               $manufacturer = Manufacturer::processName($software["PUBLISHER"]);
               $version      = $software['VERSION'];
               $name         = $software['NAME'];

               //Software might be created in another entity, depending on the entity's configuration
               $target_entity = Entity::getUsedConfig('entities_id_software', $entity);
               //Do not change software's entity except if the dictionnary explicity changes it
               if ($target_entity < 0) {
                  $target_entity = $entity;
               }
               $modified_name       = $name;
               $modified_version    = $version;
               $version_comments    = $software['COMMENTS'];
               $is_helpdesk_visible = NULL;
               if (!$cfg_ocs["use_soft_dict"]) {
                  //Software dictionnary
                  $params = array("name" => $name, "manufacturer" => $manufacturer,
                                     "old_version"  => $version, "entities_id"  => $entity);
                  $rulecollection = new RuleDictionnarySoftwareCollection();
                  $res_rule
                  = $rulecollection->processAllRules(Toolbox::stripslashes_deep($params),
                  array(),
                  Toolbox::stripslashes_deep(array('version' => $version)));

                  if (isset($res_rule["name"]) && $res_rule["name"]) {
                     $modified_name = $res_rule["name"];
                  }

                  if (isset($res_rule["version"]) && $res_rule["version"]) {
                     $modified_version = $res_rule["version"];
                  }

                  if (isset($res_rule["is_helpdesk_visible"])
                  && strlen($res_rule["is_helpdesk_visible"])) {

                     $is_helpdesk_visible = $res_rule["is_helpdesk_visible"];
                  }

                  if (isset($res_rule['manufacturer']) && $res_rule['manufacturer']) {
                     $manufacturer = Toolbox::addslashes_deep($res_rule["manufacturer"]);
                  }

                  //If software dictionnary returns an entity, it overrides the one that may have
                  //been defined in the entity's configuration
                  if (isset($res_rule["new_entities_id"])
                  && strlen($res_rule["new_entities_id"])) {
                     $target_entity = $res_rule["new_entities_id"];
                  }
               }

               //If software must be imported
               if (!isset($res_rule["_ignore_import"]) || !$res_rule["_ignore_import"]) {
                  // Clean software object
                  $soft->reset();

                  // EXPLANATION About dictionnaries
                  // OCS dictionnary : if software name change, as we don't store INITNAME
                  //     GLPI will detect an uninstall (oldname) + install (newname)
                  // GLPI dictionnary : is rule have change
                  //     if rule have been replayed, modifiedname will be found => ok
                  //     if not, GLPI will detect an uninstall (oldname) + install (newname)

                  $id = array_search(strtolower(stripslashes($modified_name.self::FIELD_SEPARATOR.$modified_version)),
                  $imported);

                  if ($id) {
                     //-------------------------------------------------------------------------//
                     //---- The software exists in this version for this computer - Update comments --------------//
                     //---------------------------------------------------- --------------------//
                     $isNewSoft = $soft->addOrRestoreFromTrash($modified_name, $manufacturer,
                     $target_entity, '',
                     ($entity != $target_entity),
                     $is_helpdesk_visible);
                     self::updateVersion($isNewSoft, $modified_version, $version_comments);
                     unset($isNewSoft);
                     unset($imported[$id]);
                  } else {
                     //------------------------------------------------------------------------//
                     //---- The software doesn't exists in this version for this computer -----//
                     //------------------------------------------------------------------------//
                     $isNewSoft = $soft->addOrRestoreFromTrash($modified_name, $manufacturer,
                     $target_entity, '',
                     ($entity != $target_entity),
                     $is_helpdesk_visible);
                     //Import version for this software
                     $versionID = self::importVersion($isNewSoft, $modified_version, $version_comments);
                     //Install license for this machine
                     $instID = self::installSoftwareVersion($computers_id, $versionID, $dohistory);
                  }
               }
            }
         }

         foreach ($imported as $id => $unused) {
            $computer_softwareversion->delete(array('id' => $id, '_no_history' => !$dohistory),
            true);
            // delete cause a getFromDB, so fields contains values
            $verid = $computer_softwareversion->getField('softwareversions_id');

            if (countElementsInTable('glpi_computers_softwareversions',
                     "softwareversions_id = '$verid'") ==0
            && countElementsInTable('glpi_softwarelicenses',
                           "softwareversions_id_buy = '$verid'") == 0) {

               $vers = new SoftwareVersion();
               if ($vers->getFromDB($verid)
               && countElementsInTable('glpi_softwarelicenses',
                                 "softwares_id = '".$vers->fields['softwares_id']."'") ==0
               && countElementsInTable('glpi_softwareversions',
                                 "softwares_id = '".$vers->fields['softwares_id']."'") == 1) {
                  // 1 is the current to be removed
                  $soft->putInTrash($vers->fields['softwares_id'],
                  __('Software deleted by OCSNG synchronization'));
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
    * @param $ocsid integer : ocs computer id (ID).
    * @param $plugin_ocsinventoryng_ocsservers_id integer : ocs server id
    * @param $cfg_ocs array : ocs config
    *
    * @return Nothing (void).
    **/
   static function updateRegistry($computers_id, $ocsComputer, $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs){
      global $DB;
      //before update, delete all entries about $computers_id
      $query_delete = "DELETE
                          FROM `glpi_plugin_ocsinventoryng_registrykeys`
                          WHERE `computers_id` = '$computers_id'";
      $DB->query($query_delete);
      
      if (isset($ocsComputer['REGISTRY'])) {
         if (count($ocsComputer["REGISTRY"]) > 0){
            $reg = new PluginOcsinventoryngRegistryKey();
            //update data
            foreach ($ocsComputer["REGISTRY"] as $registry) {
               $input                 = array();
               $input["computers_id"] = $computers_id;
               $input["hive"]         = $registry["regtree"];
               $input["value"]        = $registry["regvalue"];
               $input["path"]         = $registry["regkey"];
               $input["ocs_name"]     = $registry["name"];
               $isNewReg              = $reg->add($input, array('disable_unicity_check' => true));
               unset($reg->fields);
            }
         }
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
    * @param $entity integer : entity of the computer
    * @param $dohistory boolean : log changes?
    *
    * @return Nothing (void).
    **/
   static function updateAdministrativeInfo($computers_id, $ocsid, $plugin_ocsinventoryng_ocsservers_id, $cfg_ocs, $computer_updates, $entity, $dohistory){
      global $DB;
      self::checkOCSconnection($plugin_ocsinventoryng_ocsservers_id);
      $ocsClient = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);
      //check link between ocs and glpi column
      $queryListUpdate = "SELECT*
                          FROM `glpi_plugin_ocsinventoryng_ocsadmininfoslinks`
                          WHERE `plugin_ocsinventoryng_ocsservers_id` = '$plugin_ocsinventoryng_ocsservers_id' ";
      $result = $DB->query($queryListUpdate);

      if ($DB->numrows($result) > 0){
         $options = array(
                       "DISPLAY"=> array(
                        "CHECKSUM"=>PluginOcsinventoryngOcsClient::CHECKSUM_BIOS,
                       "WANTED"=> PluginOcsinventoryngOcsClient::WANTED_ACCOUNTINFO,
         )
         );
         $computer = $ocsClient->getComputer($ocsid,$options);
      
         if ($computer){
            $accountinfos = $computer["ACCOUNTINFO"];
            foreach ($accountinfos as $key=>$accountinfo){
               $comp = new Computer();
               //update data
               while ($links_glpi_ocs = $DB->fetch_array($result)){
                  //get info from ocs
                  $ocs_column  = $links_glpi_ocs['ocs_column'];
                  $glpi_column = $links_glpi_ocs['glpi_column'];
                  if (array_key_exists ($ocs_column,$accountinfo) && !in_array($glpi_column, $computer_updates)){
                     if(isset($accountinfo[$ocs_column])){
                        $var = addslashes($accountinfo[$ocs_column]);
                     }else{
                        $var = "";
                     }
                     switch ($glpi_column){
                        case "groups_id":
                           $var = self::importGroup($var, $entity);
                           break;
   
                        case "locations_id":
                           $var = Dropdown::importExternal("Location", $var, $entity);
                           break;
   
                        case "networks_id":
                           $var = Dropdown::importExternal("Network", $var);
                           break;
                     }
   
                     $input                = array();
                     $input[$glpi_column]  = $var;
                     $input["id"]          = $computers_id;
                     $input["entities_id"] = $entity;
                     $input["_nolock"]     = true;
                     $comp->update($input, $dohistory);
                  }
               }
               
            }
         }
      }
   }

   static function cronInfo($name){
      // no translation for the name of the project
      return array('description' => __('OCSNG', 'ocsinventoryng')." - ".__('Check OCSNG import script', 'ocsinventoryng'));
   }


   static function cronOcsng($task){
      global $DB, $CFG_GLPI;

      //Get a randon server id
      $plugin_ocsinventoryng_ocsservers_id = self::getRandomServerID();
      if ($plugin_ocsinventoryng_ocsservers_id > 0){
         //Initialize the server connection
         $PluginOcsinventoryngDBocs = self::getDBocs($plugin_ocsinventoryng_ocsservers_id);
         
         $cfg_ocs = self::getConfig($plugin_ocsinventoryng_ocsservers_id);
         $task->log(__('Check updates from server', 'ocsinventoryng')." " . $cfg_ocs['name'] . "\n");

         if (!$cfg_ocs["cron_sync_number"]){
            return 0;
         }
         self::manageDeleted($plugin_ocsinventoryng_ocsservers_id);

         $query = "SELECT MAX(`last_ocs_update`)
                   FROM `glpi_plugin_ocsinventoryng_ocslinks`
                   WHERE `plugin_ocsinventoryng_ocsservers_id`='$plugin_ocsinventoryng_ocsservers_id'";
         $max_date="0000-00-00 00:00:00";
         if ($result=$DB->query($query)){
            if ($DB->numrows($result)>0){
               $max_date = $DB->result($result,0,0);
            }
         }

         $res = $PluginOcsinventoryngDBocs->getComputersToUpdate($cfg_ocs, $max_date);

         $task->setVolume(0);
         if (count($res) > 0){
            
            foreach ($res as $k => $data) {
               if (count($data) > 0){
                  $task->addVolume(1);
                  $task->log(sprintf(__('%1$s: %2$s'), _n('Computer', 'Computer', 1),
                  sprintf(__('%1$s (%2$s)'), $data["DEVICEID"], $data["ID"])));

                  self::processComputer($data["ID"], $plugin_ocsinventoryng_ocsservers_id, 0);
               }
            }
         } else{
            return 0;
         }
      }
      return 1;
   }


   static function analizePrinterPorts(&$printer_infos, $port=''){

      if (preg_match("/USB[0-9]*/i",$port)){
         $printer_infos['have_usb'] = 1;

      } else if (preg_match("/IP_/i",$port)){
         $printer_infos['have_ethernet'] = 1;

      } else if (preg_match("/LPT[0-9]:/i",$port)){
         $printer_infos['have_parallel'] = 1;
      }
   }


   static function getAvailableStatistics(){

      $stats = array('imported_machines_number'     => __('Computers imported', 'ocsinventoryng'),
                     'synchronized_machines_number' => __('Computers synchronized', 'ocsinventoryng'),
                     'linked_machines_number'       => __('Computers linked', 'ocsinventoryng'),
                     'notupdated_machines_number'   => __('Computers not updated', 'ocsinventoryng'),
                     'failed_rules_machines_number' => __("Computers don't check any rule",
                                                          'ocsinventoryng'),
                     'not_unique_machines_number'   => __('Duplicate computers', 'ocsinventoryng'),
                     'link_refused_machines_number' => __('Computers whose import is refused by a rule',
                                                          'ocsinventoryng'));
      return $stats;
   }


   static function manageImportStatistics(&$statistics=array(), $action= false){

      if(empty($statistics)){
         foreach (self::getAvailableStatistics() as $field => $label){
            $statistics[$field] = 0;
         }
      }

      switch ($action){
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
      }
   }


   static function showStatistics($statistics=array(), $finished=false){

      echo "<div class='center b'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<th colspan='2'>".__('Statistics of the OCSNG link', 'ocsinventoryng');
      if ($finished){
         echo "&nbsp;-&nbsp;";
         _e('Task completed.');
      }
      echo "</th>";

      foreach (self::getAvailableStatistics() as $field => $label){
         echo "<tr class='tab_bg_1'><td>".$label."</td><td>".$statistics[$field]."</td></tr>";
      }
      echo "</table></div>";
   }


   /**
    * Do automatic transfer if option is enable
    *
    * @param $line_links array : data from glpi_plugin_ocsinventoryng_ocslinks table
    * @param $line_ocs array : data from ocs tables
    *
    * @return nothing
    **/
   static function transferComputer($line_links, $line_ocs){
      global $DB, $CFG_GLPI;

      // Get all rules for the current plugin_ocsinventoryng_ocsservers_id
      $rule = new RuleImportEntityCollection();

      $data = array();
      $data = $rule->processAllRules(array('ocsservers_id'
      => $line_links["plugin_ocsinventoryng_ocsservers_id"],
                                            '_source'       => 'ocsinventoryng'),
      array(), array('ocsid' => $line_links["ocsid"]));

      // If entity is changing move items to the new entities_id
      if (isset($data['entities_id'])
      && $data['entities_id'] != $line_links['entities_id']){

         if (!isCommandLine() && !Session::haveAccessToEntity($data['entities_id'])){
            Html::displayRightError();
         }

         $transfer = new Transfer();
         $transfer->getFromDB($CFG_GLPI['transfers_id_auto']);

         $item_to_transfer = array("Computer" => array($line_links['computers_id']
         =>$line_links['computers_id']));

         $transfer->moveItems($item_to_transfer, $data['entities_id'], $transfer->fields);
      }

      //If location is update by a rule
      self::updateLocation($line_links, $data);
   }


   /**
    * Update location for a computer if needed after rule processing
    *
    * @param line_links
    * @param data
    *
    * @return nothing
    */
   static function updateLocation($line_links, $data){

      //If there's a location to update
      if (isset($data['locations_id'])){
         $computer  = new Computer();
         $computer->getFromDB($line_links['computers_id']);
         $ancestors = getAncestorsOf('glpi_entities', $computer->fields['entities_id']);

         $location  = new Location();
         if ($location->getFromDB($data['locations_id'])){
            //If location is in the same entity as the computer, or if the location is
            //defined in a parent entity, but recursive
            if ($location->fields['entities_id'] == $computer->fields['entities_id']
            || (in_array($location->fields['entities_id'], $ancestors)
            && $location->fields['is_recursive'])){

               $tmp['locations_id'] = $data['locations_id'];
               $tmp['id']           = $line_links['computers_id'];
               $computer->update($tmp);
            }
         }
      }
   }


   /**
    * Update TAG information in glpi_plugin_ocsinventoryng_ocslinks table
    *
    * @param $line_links array : data from glpi_plugin_ocsinventoryng_ocslinks table
    * @param $line_ocs array : data from ocs tables
    *
    * @return string : current tag of computer on update
    **/
   static function updateTag($line_links, $line_ocs){
      global $DB;
      $ocsClient = self::getDBocs($line_links["plugin_ocsinventoryng_ocsservers_id"]);
      $options = array();
      $computer = $ocsClient->getComputer($line_links["ocsid"],$options);

      if ($computer){
         $data_ocs = Toolbox::addslashes_deep($computer["META"]);
         
         if (isset($data_ocs["TAG"]) 
               && isset($line_links["tag"]) 
                  && $data_ocs["TAG"]!=$line_links["tag"]) {
            $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                      SET `tag` = '" . $data_ocs["TAG"] . "'
                      WHERE `id` = '" . $line_links["id"] . "'";

            if ($DB->query($query)){
               $changes[0] = '0';
               $changes[1] = $line_links["tag"];
               $changes[2] = $data_ocs["TAG"];

               PluginOcsinventoryngOcslink::history($line_links["computers_id"], $changes,
               PluginOcsinventoryngOcslink::HISTORY_OCS_TAGCHANGED);
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
    **/
   static function registerType($type){
      if (!in_array($type, self::$types)){
         self::$types[] = $type;
      }
   }


   /**
    * Type than could be linked to a Store
    *
    * @param $all boolean, all type, or only allowed ones
    *
    * @return array of types
    **/
   static function getTypes($all=false){

      if ($all){
         return self::$types;
      }

      // Only allowed types
      $types = self::$types;

      foreach ($types as $key => $type){
         if (!($item = getItemForItemtype($type))){
            continue;
         }

         if (!$item->canView()){
            unset($types[$key]);
         }
      }
      return $types;
   }


   static function getLockableFields(){

      return array("name"                            => __('Name'),
                   "computertypes_id"               => __('Type'),
                   "manufacturers_id"               => __('Manufacturer'),
                   "computermodels_id"              => __('Model'),
                   "serial"                         => __('Serial number'),
                   "otherserial"                    => __('Inventory number'),
                   "comment"                        => __('Comments'),
                   "contact"                        => __('Alternate username'),
                   "contact_num"                    => __('Alternate username number'),
                   "domains_id"                     => __('Domain'),
                   "networks_id"                    => __('Network'),
                   "operatingsystems_id"            => __('Operating system'),
                   "operatingsystemservicepacks_id" => __('Service pack'),
                   "operatingsystemversions_id"     => __('Version of the operating system'),
                   "os_license_number"              => __('Serial of the operating system'),
                   "os_licenseid"                   => __('Product ID of the operating system'),
                   "users_id"                       => __('User'),
                   "locations_id"                   => __('Location'),
                   "groups_id"                      => __('Group'));
   }


   static function showOcsReportsConsole($id){

      $ocsconfig = self::getConfig($id);

      echo "<div class='center'>";
      if ($ocsconfig["ocs_url"] != ''){
         echo "<iframe src='".$ocsconfig["ocs_url"]."/index.php?multi=4' width='95%' height='650'>";
      }
      echo "</div>";
   }


   /**
    * @since version 0.84
    *
    * @param $target
    **/
   static function checkBox($target){

      echo "<a href='".$target."?check=all' ".
             "onclick= \"if (markCheckboxes('ocsng_form')) return false;\">".__('Check all').
             "</a>&nbsp;/&nbsp;\n";
      echo "<a href='".$target."?check=none' ".
             "onclick= \"if ( unMarkCheckboxes('ocsng_form') ) return false;\">".
      __('Uncheck all') . "</a>\n";
   }


   static function getFirstServer(){
      global $DB;

      $query = "SELECT `glpi_plugin_ocsinventoryng_ocsservers`.`id`
                FROM `glpi_plugin_ocsinventoryng_ocsservers_profiles`
                LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers`
                ON `glpi_plugin_ocsinventoryng_ocsservers`.`id` = `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id`
                ORDER BY `glpi_plugin_ocsinventoryng_ocsservers`.`id` ASC LIMIT 1 ";
      $results = $DB->query($query);
      if ($DB->numrows($results) > 0){
         return $DB->result($results, 0, 'id');
      }
      return -1;
   }


   /**
    * Delete old devices settings
    *
    * @param $glpi_computers_id integer : glpi computer id.
    * @param $itemtype integer : device type identifier.
    *
    * @return nothing.
    **/
   static function resetDevices($glpi_computers_id, $itemtype){
      global $DB;
      
      $linktable = getTableForItemType('Item_'.$itemtype);
      if ($itemtype == "PluginOcsinventoryngDeviceBiosdata") {
         $linktable = getTableForItemType('PluginOcsinventoryngItem_DeviceBiosdata');
      }
      
      $query = "DELETE
                FROM `$linktable`
                WHERE `items_id` = '$glpi_computers_id'
                     AND `itemtype` = 'Computer'";
      $DB->query($query);
   }

   /**
    *
    * Import monitors from OCS
    * @since 1.0
    * @param $cfg_ocs OCSNG mode configuration
    * @param $computers_id computer's id in GLPI
    * @param $ocsservers_id OCS server id
    * @param $ocsid computer's id in OCS
    * @param entity the entity in which the monitor will be created
    * @param dohistory record in history link between monitor and computer
    */
   static function importMonitor($cfg_ocs, $computers_id, $ocsservers_id, $ocsComputer, $entity,
   $dohistory){
      global $DB;
      $already_processed = array();
      $do_clean          = true;
      $m                 = new Monitor();
      $conn              = new Computer_Item();





      $monitors       = array();
      $checkserial = true;

      // First pass - check if all serial present
      if ($ocsComputer) {
         if (isset($ocsComputer["MONITORS"])) {
            foreach ($ocsComputer["MONITORS"] as $monitor) {
               // Config says import monitor with serial number only
               // Restrict SQL query ony for monitors with serial present
               if ($cfg_ocs["import_monitor"] == 4) {
                  if (!empty($monitor["SERIAL"])){
                     $monitors[] = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($monitor));
                  }
               }
               else{
                  $monitors[] = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($monitor));
                  $checkserial = false;
               }
            }
         }
      }

      if (count($monitors)>0
      && ($cfg_ocs["import_monitor"] <= 2 || $checkserial)) {

         foreach ($monitors as $monitor) {

            $mon         = array();
            $mon["name"] = $monitor["CAPTION"];
            if (empty ($monitor["CAPTION"]) && !empty ($monitor["MANUFACTURER"])) {
               $mon["name"] = $monitor["MANUFACTURER"];
            }
            if (empty ($monitor["CAPTION"]) && !empty ($monitor["TYPE"])){
               if (!empty ($monitor["MANUFACTURER"])){
                  $mon["name"] .= " ";
               }
               $mon["name"] .= $monitor["TYPE"];
            }
            $mon["serial"] = $monitor["SERIAL"];
            //Look for a monitor with the same name (and serial if possible) already connected
            //to this computer
            $query = "SELECT `m`.`id`, `gci`.`is_deleted`
                         FROM `glpi_monitors` as `m`, `glpi_computers_items` as `gci`
                         WHERE `m`.`id` = `gci`.`items_id`
                            AND `gci`.`is_dynamic`='1'
                            AND `computers_id`='$computers_id'
                            AND `itemtype`='Monitor'
                            AND `m`.`name`='".$mon["name"]."'";
            if (!empty ($mon["serial"])) {
               $query.= " AND `m`.`serial`='".$mon["serial"]."'";
            }
            $results = $DB->query($query);
            $id      = false;
            $lock    = false;
            if ($DB->numrows($results) == 1) {
               $id   = $DB->result($results, 0, 'id');
               $lock = $DB->result($results, 0, 'is_deleted');
            }

            if ($id == false) {
               // Clean monitor object
               $m->reset();
               $mon["manufacturers_id"] = Dropdown::importExternal('Manufacturer',
               $monitor["MANUFACTURER"]);
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
                                  AND `is_global` = '1'
                                  AND `entities_id` = '$entity'";
                  $result_search = $DB->query($query);

                  if ($DB->numrows($result_search) > 0) {
                     //Periph is already in GLPI
                     //Do not import anything just get periph ID for link
                     $id_monitor = $DB->result($result_search, 0, "id");
                  } else{
                     $input = $mon;
                     if ($cfg_ocs["states_id_default"]>0) {
                        $input["states_id"] = $cfg_ocs["states_id_default"];
                     }
                     $input["entities_id"] = $entity;
                     $id_monitor = $m->add($input);
                  }

               } else if ($cfg_ocs["import_monitor"] >= 2) {
                  //Config says : manage monitors as single units
                  //Import all monitors as non global.
                  $mon["is_global"] = 0;

                  // Try to find a monitor with the same serial.
                  if (!empty ($mon["serial"])){
                     $query = "SELECT `id`
                                  FROM `glpi_monitors`
                                  WHERE `serial` LIKE '%" . $mon["serial"] . "%'
                                     AND `is_global` = '0'
                                     AND `entities_id` = '$entity'";
                     $result_search = $DB->query($query);
                     if ($DB->numrows($result_search) == 1) {
                        //Monitor founded
                        $id_monitor = $DB->result($result_search, 0, "id");
                     }
                  }

                  //Search by serial failed, search by name
                  if ($cfg_ocs["import_monitor"] == 2 && !$id_monitor) {
                     //Try to find a monitor with no serial, the same name and not already connected.
                     if (!empty ($mon["name"])){
                        $query = "SELECT `glpi_monitors`.`id`
                                           FROM `glpi_monitors`
                                           LEFT JOIN `glpi_computers_items`
                                                ON (`glpi_computers_items`.`itemtype`='Monitor'
                                                    AND `glpi_computers_items`.`items_id`
                                                            =`glpi_monitors`.`id`)
                                           WHERE `serial` = ''
                                                 AND `name` = '" . $mon["name"] . "'
                                                       AND `is_global` = '0'
                                                       AND `entities_id` = '$entity'
                                                       AND `glpi_computers_items`.`computers_id` IS NULL";
                        $result_search = $DB->query($query);
                        if ($DB->numrows($result_search) == 1) {
                           $id_monitor = $DB->result($result_search, 0, "id");
                        }
                     }
                  }


                  if (!$id_monitor) {
                     $input = $mon;
                     if ($cfg_ocs["states_id_default"]>0){
                        $input["states_id"] = $cfg_ocs["states_id_default"];
                     }
                     $input["entities_id"] = $entity;
                     $id_monitor = $m->add($input);
                  }
               } // ($cfg_ocs["import_monitor"] >= 2)

               if ($id_monitor){
                  //Import unique : Disconnect monitor on other computer done in Connect function
                  $connID = $conn->add(array('computers_id' => $computers_id,
                                                'itemtype'     => 'Monitor',
                                                'items_id'     => $id_monitor,
                                                '_no_history'  => !$dohistory,
                                                'is_dynamic'   => 1,
                                                'is_deleted'  => 0));
                  $already_processed[] = $id_monitor;

                  //Update column "is_deleted" set value to 0 and set status to default
                  $input = array();
                  $old   = new Monitor();
                  if ($old->getFromDB($id_monitor)){
                     if ($old->fields["is_deleted"]){
                        $input["is_deleted"] = 0;
                     }
                     if ($cfg_ocs["states_id_default"] >0
                     && $old->fields["states_id"] != $cfg_ocs["states_id_default"]){
                        $input["states_id"] = $cfg_ocs["states_id_default"];
                     }
                     if (empty($old->fields["name"]) && !empty($mon["name"])){
                        $input["name"] = $mon["name"];
                     }
                     if (empty($old->fields["serial"]) && !empty($mon["serial"])){
                        $input["serial"] = $mon["serial"];
                     }
                     if (count($input)){
                        $input["id"]          = $id_monitor;
                        $input['entities_id'] = $entity;
                        $m->update($input);
                     }
                  }
               }

            } else{
               $already_processed[] = $id;
            }
         } // end foreach

         if ($cfg_ocs["import_monitor"]<=2 || $checkserial){
            //Look for all monitors, not locked, not linked to the computer anymore
            $query = "SELECT `id`
                         FROM `glpi_computers_items`
                         WHERE `itemtype`='Monitor'
                            AND `computers_id`='$computers_id'
                            AND `is_dynamic`='1'
                            AND `is_deleted`='0'";
            if (!empty($already_processed)){
               $query .= "AND `items_id` NOT IN (".implode(',', $already_processed).")";
            }
            foreach ($DB->request($query) as $data){
               //Delete all connexions
               $conn->delete(array('id'             => $data['id'],
                                       '_ocsservers_id' => $ocsservers_id), true);
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
    * @param $ocsid computer's id in OCS
    * @param $ocsservers_id OCS server id
    * @param $entity the entity in which the printer will be created
    * @param $dohistory record in history link between printer and computer
    */
   static function importPrinter($cfg_ocs, $computers_id, $ocsservers_id, $ocsComputer, $entity,
   $dohistory){
      global  $DB;

      $already_processed = array();

      $conn              = new Computer_Item();
      $p      = new Printer();

      if (isset($ocsComputer["PRINTERS"])) {
         if (count($ocsComputer["PRINTERS"])>0){
            foreach ($ocsComputer["PRINTERS"] as $printer){
               $printer  = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($printer));
               $print = array();
               // TO TEST : PARSE NAME to have real name.
               $print['name'] = self::encodeOcsDataInutf8($cfg_ocs["ocs_db_utf8"], $printer['NAME']);

               if (empty ($print["name"])){
                  $print["name"] = $printer["DRIVER"];
               }

               $management_process = $cfg_ocs["import_printer"];

               //Params for the dictionnary
               $params['name']         = $print['name'];
               $params['manufacturer'] = "";
               $params['DRIVER']       = $printer['DRIVER'];
               $params['PORT']         = $printer['PORT'];

               if (!empty ($print["name"])){
                  $rulecollection = new RuleDictionnaryPrinterCollection();
                  $res_rule
                  = Toolbox::addslashes_deep($rulecollection->processAllRules(Toolbox::stripslashes_deep($params),
                  array(), array()));

                  if (!isset($res_rule["_ignore_import"])
                  || !$res_rule["_ignore_import"]){

                     foreach ($res_rule as $key => $value){
                        if ($value != '' && $value[0] != '_'){
                           $print[$key] = $value;
                        }
                     }

                     if (isset($res_rule['is_global'])){
                        if (!$res_rule['is_global']){
                           $management_process = 2;
                        } else{
                           $management_process = 1;
                        }
                     }

                     //Look for a printer with the same name (and serial if possible) already connected
                     //to this computer
                     $query = "SELECT `p`.`id`, `gci`.`is_deleted`
                                  FROM `glpi_printers` as `p`, `glpi_computers_items` as `gci`
                                  WHERE `p`.`id` = `gci`.`items_id`
                                     AND `gci`.`is_dynamic`='1'
                                     AND `computers_id`='$computers_id'
                                     AND `itemtype`='Printer'
                                     AND `p`.`name`='".$print["name"]."'";
                     $results = $DB->query($query);
                     $id      = false;
                     $lock    = false;
                     if ($DB->numrows($results) > 0){
                        $id   = $DB->result($results, 0, 'id');
                        $lock = $DB->result($results, 0, 'is_deleted');
                     }

                     if (!$id){
                        // Clean printer object
                        $p->reset();
                        $print["comment"] = $printer["PORT"] . "\r\n" . $printer["DRIVER"];
                        self::analizePrinterPorts($print, $printer["PORT"]);
                        $id_printer = 0;

                        if ($management_process == 1){
                           //Config says : manage printers as global
                           //check if printers already exists in GLPI
                           $print["is_global"] = MANAGEMENT_GLOBAL;
                           $query = "SELECT `id`
                                         FROM `glpi_printers`
                                         WHERE `name` = '" . $print["name"] . "'
                                            AND `is_global` = '1'
                                            AND `entities_id` = '$entity'";
                           $result_search = $DB->query($query);

                           if ($DB->numrows($result_search) > 0){
                              //Periph is already in GLPI
                              //Do not import anything just get periph ID for link
                              $id_printer        = $DB->result($result_search, 0, "id");
                              $already_processed[] = $id_printer;
                           } else{
                              $input = $print;

                              if ($cfg_ocs["states_id_default"]>0){
                                 $input["states_id"] = $cfg_ocs["states_id_default"];
                              }
                              $input["entities_id"] = $entity;
                              $id_printer           = $p->add($input);
                           }

                        } else if ($management_process == 2){
                           //Config says : manage printers as single units
                           //Import all printers as non global.
                           $input              = $print;
                           $input["is_global"] = MANAGEMENT_UNITARY;

                           if ($cfg_ocs["states_id_default"]>0){
                              $input["states_id"] = $cfg_ocs["states_id_default"];
                           }
                           $input["entities_id"] = $entity;
                           $input['is_dynamic']  = 1;
                           $id_printer           = $p->add($input);
                        }

                        if ($id_printer){
                           $already_processed[] = $id_printer;
                           $conn   = new Computer_Item();
                           $connID = $conn->add(array('computers_id' => $computers_id,
                                                          'itemtype'     => 'Printer',
                                                          'items_id'     => $id_printer,
                                                          '_no_history'  => !$dohistory,
                                                          'is_dynamic' => 1));
                           //Update column "is_deleted" set value to 0 and set status to default
                           $input                = array();
                           $input["id"]          = $id_printer;
                           $input["is_deleted"]  = 0;
                           $input["entities_id"] = $entity;

                           if ($cfg_ocs["states_id_default"]>0){
                              $input["states_id"] = $cfg_ocs["states_id_default"];
                           }
                           $p->update($input);
                        }

                     } else{
                        $already_processed[] = $id;
                     }
                  }
               }
            }
         }
      }

      //Look for all printers, not locked, not linked to the computer anymore
      $query = "SELECT `id`
                    FROM `glpi_computers_items`
                    WHERE `itemtype`='Printer'
                       AND `computers_id`='$computers_id'
                       AND `is_dynamic`='1'
                       AND `is_deleted`='0'";
      if (!empty($already_processed)){
         $query .= "AND `items_id` NOT IN (".implode(',', $already_processed).")";
      }
      foreach ($DB->request($query) as $data){
         //Delete all connexions
         $conn->delete(array('id'             => $data['id'],
                                  '_ocsservers_id' => $ocsservers_id), true);
      }

   }

   /**
    *
    * Import peripherals from OCS
    * @since 1.0
    * @param $cfg_ocs OCSNG mode configuration
    * @param $computers_id computer's id in GLPI
    * @param $ocsid computer's id in OCS
    * @param $ocsservers_id OCS server id
    * @param $entity the entity in which the peripheral will be created
    * @param $dohistory record in history link between peripheral and computer
    */
   static function importPeripheral($cfg_ocs, $computers_id, $ocsservers_id, $ocsComputer, $entity,
   $dohistory){
      global $DB;
      $already_processed = array();
      $p                 = new Peripheral();
      $conn              = new Computer_Item();
      
      if (isset($ocsComputer["INPUTS"])) {
         if (count($ocsComputer["INPUTS"]) > 0){
            foreach ($ocsComputer["INPUTS"] as $peripheral) {
               if($peripheral["CAPTION"]!==''){
                  $peripherals[]=$peripheral;
               }
            }
            if (count($peripherals) > 0){
               foreach ($peripherals as $peripheral) {
                  $peripheral   = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($peripheral));
                  $periph = array();
                  $periph["name"] = self::encodeOcsDataInUtf8($cfg_ocs["ocs_db_utf8"], $peripheral["CAPTION"]);
                  //Look for a monitor with the same name (and serial if possible) already connected
                  //to this computer
                  $query = "SELECT `p`.`id`, `gci`.`is_deleted`
                                       FROM `glpi_printers` as `p`, `glpi_computers_items` as `gci`
                                       WHERE `p`.`id` = `gci`.`items_id`
                                       AND `gci`.`is_dynamic`='1'
                                       AND `computers_id`='$computers_id'
                                       AND `itemtype`='Peripheral'
                                       AND `p`.`name`='".$periph["name"]."'";
                  $results = $DB->query($query);
                  $id      = false;
                  $lock    = false;
                  if ($DB->numrows($results) > 0){
                     $id   = $DB->result($results, 0, 'id');
                     $lock = $DB->result($results, 0, 'is_deleted');
                  }
                  if (!$id){
                     // Clean peripheral object
                     $p->reset();
                     if ($peripheral["MANUFACTURER"] != "NULL"){
                        $periph["brand"] = self::encodeOcsDataInUtf8($cfg_ocs["ocs_db_utf8"],
                        $peripheral["MANUFACTURER"]);
                     }
                     if ($peripheral["INTERFACE"] != "NULL"){
                        $periph["comment"] = self::encodeOcsDataInUtf8($cfg_ocs["ocs_db_utf8"],
                        $peripheral["INTERFACE"]);
                     }
                     $periph["peripheraltypes_id"] = Dropdown::importExternal('PeripheralType',
                     $peripheral["TYPE"]);
                     $id_periph = 0;
                     if ($cfg_ocs["import_periph"] == 1){
                        //Config says : manage peripherals as global
                        //check if peripherals already exists in GLPI
                        $periph["is_global"] = 1;
                        $query = "SELECT `id`
                                           FROM `glpi_peripherals`
                                           WHERE `name` = '" . $periph["name"] . "'
                                           AND `is_global` = '1'
                                           AND `entities_id` = '$entity'";
                        $result_search = $DB->query($query);
                        if ($DB->numrows($result_search) > 0){
                           //Periph is already in GLPI
                           //Do not import anything just get periph ID for link
                           $id_periph = $DB->result($result_search, 0, "id");
                        }
                        else{
                           $input = $periph;
                           if ($cfg_ocs["states_id_default"]>0){
                              $input["states_id"] = $cfg_ocs["states_id_default"];
                           }
                           $input["entities_id"] = $entity;
                           $id_periph = $p->add($input);
                        }
                     }
                     else if ($cfg_ocs["import_periph"] == 2){
                        //Config says : manage peripherals as single units
                        //Import all peripherals as non global.
                        $input = $periph;
                        $input["is_global"] = 0;
                        if ($cfg_ocs["states_id_default"]>0){
                           $input["states_id"] = $cfg_ocs["states_id_default"];
                        }
                        $input["entities_id"] = $entity;
                        $id_periph = $p->add($input);
                     }

                     if ($id_periph){
                        $already_processed[] = $id_periph;
                        $conn                = new Computer_Item();
                        if ($connID = $conn->add(array('computers_id' => $computers_id,
                                                                     'itemtype'     => 'Peripheral',
                                                                     'items_id'     => $id_periph,
                                                                     '_no_history'  => !$dohistory,
                                                                     'is_dynamic' => 1))){
                        //Update column "is_deleted" set value to 0 and set status to default
                        $input                = array();
                        $input["id"]          = $id_periph;
                        $input["is_deleted"]  = 0;
                        $input["entities_id"] = $entity;
                        if ($cfg_ocs["states_id_default"]>0){
                           $input["states_id"] = $cfg_ocs["states_id_default"];
                        }
                        $p->update($input);
                                                                     }
                     }
                  }
                  else{
                     $already_processed[] = $id;
                  }
               }
            }
         }
      }
     //Look for all monitors, not locked, not linked to the computer anymore
      $query = "SELECT `id`
                      FROM `glpi_computers_items`
                      WHERE `itemtype`='Peripheral'
                         AND `computers_id`='$computers_id'
                         AND `is_dynamic`='1'
                         AND `is_deleted`='0'";
      if (!empty($already_processed)){
         $query .= "AND `items_id` NOT IN (".implode(',', $already_processed).")";
      }
      foreach ($DB->request($query) as $data){
         //Delete all connexions
         $conn->delete(array('id'             => $data['id'],
                                    '_ocsservers_id' => $ocsservers_id), true);
      }
   }
   
   /**
    * @since version 0.85
    *
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $actions = parent::getSpecificMassiveActions($checkitem);

      return $actions;
   }
   
   /**
    * @since version 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'plugin_ocsinventoryng_force_ocsng_update':
            echo "&nbsp;".
                 Html::submit(_x('button','Post'), array('name' => 'massiveaction'));
            return true;
         case 'plugin_ocsinventoryng_lock_ocsng_field':
            $fields['all'] = __('All');
            $fields       += self::getLockableFields();
            Dropdown::showFromArray("field", $fields);
            echo "&nbsp;".
                 Html::submit(_x('button','Post'), array('name' => 'massiveaction'));
            return true;
         case 'plugin_ocsinventoryng_unlock_ocsng_field':
            $fields['all'] = __('All');
            $fields       += self::getLockableFields();
            Dropdown::showFromArray("field", $fields);
            echo "&nbsp;".
                 Html::submit(_x('button','Post'), array('name' => 'massiveaction'));
            return true;
    }
      return parent::showMassiveActionsSubForm($ma);
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {
      global $DB, $REDIRECT;
      
      switch ($ma->getAction()) {
         case "plugin_ocsinventoryng_force_ocsng_update":
            $input = $ma->getInput();

            foreach ($ids as $id) {
               
               $query = "SELECT `plugin_ocsinventoryng_ocsservers_id`, `id`
                               FROM `glpi_plugin_ocsinventoryng_ocslinks`
                               WHERE `computers_id` = '".$id."'";
               $result = $DB->query($query);
               if ($DB->numrows($result) == 1) {
                  $data = $DB->fetch_assoc($result);
                        
                  if (self::updateComputer($data['id'],$data['plugin_ocsinventoryng_ocsservers_id'],
                                                                            1, 1)) {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                  }
               } else {
                  $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
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
                        $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                     }
                  } else {
                     if (self::addToOcsArray($id, array($_POST['field']), "computer_update")) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
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
                        $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                     }
                  } else {
                     if (self::deleteInOcsArray($id, $_POST['field'], "computer_update", true)) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                     }
                  }
               }
            }

            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }
}
