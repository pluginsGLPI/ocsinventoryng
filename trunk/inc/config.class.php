<?php
/*
 * @version $Id: HEADER 14684 2011-06-11 06:32:40Z remi $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
*/

// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------

class PluginOcsinventoryngConfig extends CommonDBTM {


   static function getTypeName() {
      global $LANG;
      return $LANG['plugin_ocsinventoryng'][25];
   }
   
   function canCreate() {
      return Session::haveRight('config', 'w');
   }

   function canView() {
      return Session::haveRight('config', 'r');
   }
   
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               //If connection to the OCS DB  is ok, and all rights are ok too
               $ong = array();
               $ong[1] = self::getTypeName(1);
               return $ong;
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      
      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1 :
               $item->showScriptLock();
               break;
         }
      }
      return true;
   }
   
   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      $this->addStandardTab(__CLASS__, $ong, $options);

      return $ong;
   }
   
   function showConfigForm($target) {
      global $LANG;

      $this->getFromDB(1);
      $this->showTabs();
      $this->showFormHeader();
      
      echo "<tr class='tab_bg_1'>";
      echo "<td class='right' colspan='2'> " .$LANG["plugin_ocsinventoryng"]["config"][121]. " </td>";
      echo "<td colspan='2'>&nbsp;&nbsp;&nbsp;";
      Dropdown::showFromArray("plugin_ocsinventoryng_ocsservers_id",$this->getAllOcsServers(),
                              array('value' => $this->fields["plugin_ocsinventoryng_ocsservers_id"]));
      echo "</td></tr>";

      echo "<tr><th colspan='4'>" . $LANG["plugin_ocsinventoryng"]["config"][116]."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td> " .$LANG["plugin_ocsinventoryng"]["config"][105] . " </td><td>";
      Dropdown::showYesNo("is_displayempty", $this->fields["is_displayempty"]);
      echo "</td>";
      echo "<td rowspan='3' class='middle right'> " .$LANG['common'][25]."</td>";
      echo "<td class='center middle' rowspan='3'>";
      echo "<textarea cols='40' rows='5' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td> " .$LANG["plugin_ocsinventoryng"]["setup"][3] . " </td><td>";
      Dropdown::showYesNo('allow_ocs_update',$this->fields['allow_ocs_update']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td> " .$LANG["plugin_ocsinventoryng"]["config"][114] . " </td><td>";
      Html::autocompletionTextField($this,"delay_refresh", array('size' => 5));
      echo "&nbsp;".$LANG["plugin_ocsinventoryng"]["time"][3]."</td>";
      echo "</tr>";

      $this->showFormButtons(array('canedit' => true, 'candel' => false));
      $this->addDivForTabs();
      return true;
   }

   function showScriptLock() {
      global $LANG; 

      echo "<div class='center'>";
      echo "<form name='lock' action=\"".$_SERVER['HTTP_REFERER']."\" method='post'>";
      echo "<input type='hidden' name='id' value='1'>";
      echo "<table class='tab_cadre'>";
      echo "<tr class='tab_bg_2'>";
      echo "<th>&nbsp;" . $LANG["plugin_ocsinventoryng"]["config"][107] ." ".
             $LANG["plugin_ocsinventoryng"]["config"][108]."&nbsp;</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>";
      $status = $this->isScriptLocked();
      if (!$status) {
         echo $LANG["plugin_ocsinventoryng"]["config"][109]."&nbsp;<img src='../pics/export.png'>";
      } else {
         echo $LANG["plugin_ocsinventoryng"]["config"][110]."&nbsp;<img src='../pics/ok2.png'>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td colspan='2' class='center'>";
      echo "<input type='submit' name='".(!$status?"soft_lock":"soft_unlock")."' class='submit' ".
            "value='".(!$status?$LANG["plugin_ocsinventoryng"]["config"][117]:$LANG["plugin_ocsinventoryng"]["config"][118])."'>";
      echo "</td/></tr/></table><br>";
      echo "</form></div>";
      
   }

   static function isScriptLocked() {
      return file_exists(PLUGIN_OCSINVENTORYNG_LOCKFILE);
   }


   function setScriptLock() {
      $fp = fopen(PLUGIN_OCSINVENTORYNG_LOCKFILE, "w+");
      fclose($fp);
   }


   function removeScriptLock() {

      if (file_exists(PLUGIN_OCSINVENTORYNG_LOCKFILE)) {
         unlink(PLUGIN_OCSINVENTORYNG_LOCKFILE);
      }
   }


   function getAllOcsServers() {
      global $DB, $LANG;

      $servers[-1] = $LANG["plugin_ocsinventoryng"]["config"][122];
      $sql = "SELECT `id`, `name`
              FROM `glpi_plugin_ocsinventoryng_ocsservers`";
      $result = $DB->query($sql);

      while ($conf = $DB->fetch_array($result)) {
         $servers[$conf["id"]] = $conf["name"];
      }

      return $servers;
   }


   static function showOcsReportsConsole($id) {
      global $LANG;

      $ocsconfig = PluginOcsinventoryngOcsServer::getConfig($id);

      echo "<div class='center'>";
      if ($ocsconfig["ocs_url"] != '') {
         echo "<iframe src='" . $ocsconfig["ocs_url"] . "/index.php?multi=4' width='95%' height='650' >";
      }
      echo "</div>";
   }

   static function canUpdateOCS() {
      $config = new PluginOcsinventoryngConfig;
      $config->getFromDB(1);
      return $config->fields['allow_ocs_update'];
   }

   /**
    * Display debug information for current object
   **/
   function showDebug() {
      NotificationEvent::debugEvent(new PluginOcsinventoryngNotimported(), 
                                    array('entities_id' =>0 ,'notimported' => array()));
   }
}

?>