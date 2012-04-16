<?php
/*
 * @version $Id: HEADER 14684 2011-06-11 06:32:40Z remi $
 -------------------------------------------------------------------------
 ocinventoryng - TreeView browser plugin for GLPI
 Copyright (C) 2012 by the ocinventoryng Development Team.

 https://forge.indepnet.net/projects/ocinventoryng
 -------------------------------------------------------------------------

 LICENSE

 This file is part of ocinventoryng.

 ocinventoryng is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 ocinventoryng is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with ocinventoryng; If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
*/

class PluginOcsinventoryngConfig extends CommonDBTM {


   static function getTypeName($nb=0) {
      return __("Automatic synchronization's configuration");
   }


   function canCreate() {
      return Session::haveRight('config', 'w');
   }


   function canView() {
      return Session::haveRight('config', 'r');
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               //If connection to the OCS DB  is ok, and all rights are ok too
               return array('1' => self::getTypeName());

            case 'PluginOcsinventoryngOcsServer' :
               return array('1' => __('web address of the OCS console'));
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case __CLASS__ :
            $item->showScriptLock();
            break;

         case 'PluginOcsinventoryngOcsServer' :
            self::showOcsReportsConsole($item->getID());
            break;
      }
      return true;
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addStandardTab(__CLASS__, $ong, $options);
      return $ong;
   }


   function showConfigForm($target) {

      $this->getFromDB(1);
      $this->showTabs();
      $this->showFormHeader();

      echo "<tr class='tab_bg_1'>";
      echo "<td class='right' colspan='2'> " .__('Default OCS server')."</td>";
      echo "<td colspan='2'>&nbsp;&nbsp;&nbsp;";
      Dropdown::showFromArray("plugin_ocsinventoryng_ocsservers_id", $this->getAllOcsServers(),
                              array('value' => $this->fields["plugin_ocsinventoryng_ocsservers_id"]));
      echo "</td></tr>";

      echo "<tr><th colspan='4'>" . __('Display')."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td> " .__('Show processes where nothing was changed') . " </td><td>";
      Dropdown::showYesNo("is_displayempty", $this->fields["is_displayempty"]);
      echo "</td>";
      echo "<td rowspan='3' class='middle right'> " .__('Comments')."</td>";
      echo "<td class='center middle' rowspan='3'>";
      echo "<textarea cols='40' rows='5' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td> " .__('Authorize the OCS update') . " </td><td>";
      Dropdown::showYesNo('allow_ocs_update',$this->fields['allow_ocs_update']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td> " .__('Refresh information of a process every') . " </td><td>";
      Html::autocompletionTextField($this,"delay_refresh", array('size' => 5));
      echo "&nbsp;"._x('second', 'seconds', 2)."</td>";
      echo "</tr>";

      $this->showFormButtons(array('canedit' => true,
                                   'candel'  => false));
      $this->addDivForTabs();
      return true;
   }


   function showScriptLock() {

      echo "<div class='center'>";
      echo "<form name='lock' action=\"".$_SERVER['HTTP_REFERER']."\" method='post'>";
      echo "<input type='hidden' name='id' value='1'>";
      echo "<table class='tab_cadre'>";
      echo "<tr class='tab_bg_2'>";
      echo "<th>&nbsp;" . __('Check OCS import script')."&nbsp;</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>";
      $status = $this->isScriptLocked();
      if (!$status) {
         echo __('Lock not activated')."&nbsp;<img src='../pics/export.png'>";
      } else {
         echo __('Lock activated')."&nbsp;<img src='../pics/ok2.png'>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td colspan='2' class='center'>";
      echo "<input type='submit' name='".(!$status?"soft_lock":"soft_unlock")."' class='submit' ".
            "value='".(!$status?_sx('button','Lock')
                               :_sx('button', 'Unlock'))."'>";
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
      global $DB;

      $servers[-1] = __('All servers');

      $sql     = "SELECT `id`, `name`
                  FROM `glpi_plugin_ocsinventoryng_ocsservers`";
      $result  = $DB->query($sql);

      while ($conf = $DB->fetch_array($result)) {
         $servers[$conf["id"]] = $conf["name"];
      }

      return $servers;
   }


   static function showOcsReportsConsole($id) {

      $ocsconfig = PluginOcsinventoryngOcsServer::getConfig($id);

      echo "<div class='center'>";
      if ($ocsconfig["ocs_url"] != '') {
         echo "<iframe src='".$ocsconfig["ocs_url"]."/index.php?multi=4' width='95%' height='650'>";
      }
      echo "</div>";
   }


   static function canUpdateOCS() {

      $config = new PluginOcsinventoryngConfig();
      $config->getFromDB(1);
      return $config->fields['allow_ocs_update'];
   }


   /**
    * Display debug information for current object
   **/
   function showDebug() {

      NotificationEvent::debugEvent(new PluginOcsinventoryngNotimported(),
                                    array('entities_id' => 0 ,
                                          'notimported' => array()));
   }

}
?>