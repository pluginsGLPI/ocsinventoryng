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

class PluginOcsinventoryngConfig extends CommonDBTM {

   static $rightname = "plugin_ocsinventoryng";
   
   static function getTypeName($nb=0) {
      return __("Automatic synchronization's configuration", 'ocsinventoryng');
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               //If connection to the OCS DB  is ok, and all rights are ok too
               return array('1' => __('Check OCSNG import script', 'ocsinventoryng'));

         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case __CLASS__ :
            $item->showScriptLock();
            break;

      }
      return true;
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      return $ong;
   }

   static function showMenu() {
      global $CFG_GLPI;

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th>".__('Configuration')."</th></tr>";
      echo "<tr class='tab_bg_1'><td class='center b'>";
      echo "<a href='".$CFG_GLPI['root_doc']."/plugins/ocsinventoryng/front/ocsserver.php'>".
             _n('OCSNG server', 'OCSNG servers', 2,'ocsinventoryng')."</a>";
      echo "</td></tr>";

      if (PluginOcsinventoryngOcsServer::useMassImport()) {
         echo "<tr class='tab_bg_1'><td class='center b'>";
         echo "<a href='".$CFG_GLPI['root_doc']."/plugins/ocsinventoryng/front/config.form.php'>".
                __("Automatic synchronization's configuration", 'ocsinventoryng')."</a>";
         echo "</td></tr>";
      }
      echo "</table>";
   }

   function showForm($target) {

      $this->getFromDB(1);
      $this->showFormHeader();

      echo "<tr class='tab_bg_1'>";
      echo "<td> " .__('Show processes where nothing was changed', 'ocsinventoryng') . " </td><td>";
      Dropdown::showYesNo("is_displayempty", $this->fields["is_displayempty"]);
      echo "</td>";
      echo "<td rowspan='3' class='middle right'> " .__('Comments')."</td>";
      echo "<td class='center middle' rowspan='3'>";
      echo "<textarea cols='40' rows='5' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td> " .__('Authorize the OCSNG update', 'ocsinventoryng') . " </td><td>";
      Dropdown::showYesNo('allow_ocs_update',$this->fields['allow_ocs_update']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td> " .__('Refresh information of a process every', 'ocsinventoryng') . " </td><td>";
      Html::autocompletionTextField($this,"delay_refresh", array('size' => 5));
      echo "&nbsp;"._n('second', 'seconds', 2, 'ocsinventoryng')."</td>";
      echo "</tr>";

      $this->showFormButtons(array('canedit' => true,
                                   'candel'  => false));

      return true;
   }


   function showScriptLock() {

      echo "<div class='center'>";
      echo "<form name='lock' action=\"".$_SERVER['HTTP_REFERER']."\" method='post'>";
      echo "<input type='hidden' name='id' value='1'>";
      echo "<table class='tab_cadre'>";
      echo "<tr class='tab_bg_2'>";
      echo "<th>&nbsp;" . __('Check OCSNG import script', 'ocsinventoryng')."&nbsp;</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>";
      $status = $this->isScriptLocked();
      if (!$status) {
         echo __('Lock not activated', 'ocsinventoryng')."&nbsp;<img src='../pics/export.png'>";
      } else {
         echo __('Lock activated', 'ocsinventoryng')."&nbsp;<img src='../pics/ok2.png'>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td colspan='2' class='center'>";
      echo "<input type='submit' name='".(!$status?"soft_lock":"soft_unlock")."' class='submit' ".
            "value='".(!$status?_sx('button', 'Lock', 'ocsinventoryng')
                               :_sx('button', 'Unlock', 'ocsinventoryng'))."'>";
      echo "</td/></tr/></table><br>";
      Html::closeForm();
      echo "</div>";

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

      $servers[-1] = __('All servers', 'ocsinventoryng');

      $sql     = "SELECT `id`, `name`
                  FROM `glpi_plugin_ocsinventoryng_ocsservers`";
      $result  = $DB->query($sql);

      while ($conf = $DB->fetch_array($result)) {
         $servers[$conf["id"]] = $conf["name"];
      }

      return $servers;
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

      NotificationEvent::debugEvent(new PluginOcsinventoryngNotimportedcomputer(),
                                    array('entities_id' => 0 ,
                                          'notimported' => array()));
   }

}
?>