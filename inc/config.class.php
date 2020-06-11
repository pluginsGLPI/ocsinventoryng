<?php

/*
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

/**
 * Class PluginOcsinventoryngConfig
 */
class PluginOcsinventoryngConfig extends CommonDBTM {

   /**
    * @var string
    */
   static $rightname = "plugin_ocsinventoryng";

   /**
    * @param int $nb
    *
    * @return string|translated
    */
   static function getTypeName($nb = 0) {
      return __("Plugin setup", 'ocsinventoryng');
   }


   /**
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return array|string
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               $tab['1'] = __('Alerts', 'ocsinventoryng');
               if (PluginOcsinventoryngOcsServer::useMassImport()) {
                  //If connection to the OCS DB  is ok, and all rights are ok too
                  $tab['2'] = __("Automatic synchronization's configuration", 'ocsinventoryng');
                  $tab['3'] = __('Check OCSNG import script', 'ocsinventoryng');
               }
               return $tab;
         }
      }
      return '';
   }


   /**
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case __CLASS__ :
            switch ($tabnum) {
               case 1 :
                  $item->displayAlerts();
                  break;
               case 2 :
                  $item->showFormAutomaticSynchronization();
                  break;
               case 3 :
                  $item->showScriptLock();
                  break;
            }

            break;

      }
      return true;
   }


   /**
    * @param array $options
    *
    * @return array
    */
   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      return $ong;
   }

   public static function getConfig() {
      static $config = null;

      if (is_null($config)) {
         $config = new self();
      }
      $config->getFromDB(1);

      return $config;
   }

   /**
    *
    */
   static function showMenu() {
      global $CFG_GLPI;

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th>" . __('Configuration') . "</th></tr>";
      echo "<tr class='tab_bg_1'><td class='center b'>";
      echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/ocsserver.php'>" .
           _n('OCSNG server', 'OCSNG servers', 2, 'ocsinventoryng') . "</a>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td class='center b'>";
      echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/config.form.php'>" .
           self::getTypeName() . "</a>";
      echo "</td></tr>";
      echo "</table>";
   }

   /**
    * @param array $options
    *
    * @return bool
    */
   function showForm($options = []) {

      $this->getFromDB(1);

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_2'>";
      echo "<th colspan='3'>" . __('OCS-NG Synchronization alerts', 'ocsinventoryng');
      echo "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='2'>" . __('New imported computers from OCS-NG', 'ocsinventoryng') . "</td><td>";
      Alert::dropdownIntegerNever('use_newocs_alert',
                                  $this->fields["use_newocs_alert"],
                                  ['max' => 99]);
      echo "&nbsp;" . _n('Day', 'Days', 2);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td colspan='2'>" . __('Computers not synchronized with OCS-NG since more', 'ocsinventoryng') . "</td><td>";
      Alert::dropdownIntegerNever('delay_ocs',
                                  $this->fields["delay_ocs"],
                                  ['max' => 99]);
      echo "&nbsp;" . _n('Day', 'Days', 2);
      echo Html::hidden('id', ['value' => 1]);
      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;
   }

   static function displayAlerts() {
      global $DB;

      $CronTask = new CronTask();

      $config = self::getConfig();

      $ocsalert = new PluginOcsinventoryngOcsAlert();
      $ocsalert->getFromDBbyEntity($_SESSION["glpiactive_entity"]);
      if (isset($ocsalert->fields["use_newocs_alert"])
          && $ocsalert->fields["use_newocs_alert"] > 0) {
         $use_newocs_alert = $ocsalert->fields["use_newocs_alert"];
      } else {
         $use_newocs_alert = $config->useNewocsAlert();
      }

      if (isset($ocsalert->fields["delay_ocs"])
          && $ocsalert->fields["delay_ocs"] > 0) {
         $delay_ocs = $ocsalert->fields["delay_ocs"];
      } else {
         $delay_ocs = $config->getDelayOcs();
      }
      $synchro_ocs = 0;
      if ($CronTask->getFromDBbyName("PluginOcsinventoryngOcsAlert", "SynchroAlert")) {
         if ($CronTask->fields["state"] != CronTask::STATE_DISABLE && $delay_ocs > 0) {
            $synchro_ocs = 1;
         }
      }
      $new_ocs = 0;
      if ($CronTask->getFromDBbyName("PluginOcsinventoryngOcsAlert", "AlertNewComputers")) {
         if ($CronTask->fields["state"] != CronTask::STATE_DISABLE && $use_newocs_alert > 0) {
            $new_ocs = 1;
         }
      }

      if ($synchro_ocs == 0
          && $new_ocs == 0) {
         echo "<div align='center'><b>" . __('No used alerts, please activate the automatic actions', 'ocsinventoryng') . "</b></div>";
      }

      if ($new_ocs != 0) {

         foreach ($DB->request("glpi_plugin_ocsinventoryng_ocsservers", "`is_active` = 1") as $config) {

            $query  = PluginOcsinventoryngOcsAlert::queryNew($delay_ocs, $config,
                                                             $_SESSION["glpiactive_entity"]);
            $result = $DB->query($query);

            if ($DB->numrows($result) > 0) {

               if (Session::isMultiEntitiesMode()) {
                  $nbcol = 9;
               } else {
                  $nbcol = 8;
               }

               echo "<div align='center'><table class='tab_cadre' cellspacing='2' cellpadding='3'>";
               echo "<tr><th colspan='$nbcol'>";
               echo __('New imported computers from OCS-NG', 'ocsinventoryng') . " - " . $delay_ocs . " " . _n('Day', 'Days', 2) . "</th></tr>";
               echo "<tr><th>" . __('Name') . "</th>";
               if (Session::isMultiEntitiesMode()) {
                  echo "<th>" . __('Entity') . "</th>";
               }
               echo "<th>" . __('Operating system') . "</th>";
               echo "<th>" . __('Status') . "</th>";
               echo "<th>" . __('Location') . "</th>";
               echo "<th>" . __('User') . " / " . __('Group') . " / " . __('Alternate username') . "</th>";
               echo "<th>" . __('Last OCSNG inventory date', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('Import date in GLPI', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('OCSNG server', 'ocsinventoryng') . "</th></tr>";

               while ($data = $DB->fetchArray($result)) {
                  echo PluginOcsinventoryngOcsAlert::displayBody($data);
               }
               echo "</table></div>";
            } else {
               echo "<br><div align='center'><b>" . __('No new imported computer from OCS-NG', 'ocsinventoryng') . "</b></div>";
            }
         }
         echo "<br>";

      }

      if ($synchro_ocs != 0) {

         foreach ($DB->request("glpi_plugin_ocsinventoryng_ocsservers", "`is_active` = 1") as $config) {

            $query  = PluginOcsinventoryngOcsAlert::query($delay_ocs, $config, $_SESSION["glpiactive_entity"]);

            $result = $DB->query($query);

            if ($DB->numrows($result) > 0) {

               if (Session::isMultiEntitiesMode()) {
                  $nbcol = 9;
               } else {
                  $nbcol = 8;
               }
               echo "<div align='center'><table class='tab_cadre' cellspacing='2' cellpadding='3'>";
               echo "<tr><th colspan='$nbcol'>";
               echo __('Computers not synchronized with OCS-NG since more', 'ocsinventoryng') . " " . $delay_ocs . " " . _n('Day', 'Days', 2) . "</th></tr>";
               echo "<tr><th>" . __('Name') . "</th>";
               if (Session::isMultiEntitiesMode()) {
                  echo "<th>" . __('Entity') . "</th>";
               }
               echo "<th>" . __('Operating system') . "</th>";
               echo "<th>" . __('Status') . "</th>";
               echo "<th>" . __('Location') . "</th>";
               echo "<th>" . __('User') . " / " . __('Group') . " / " . __('Alternate username') . "</th>";
               echo "<th>" . __('Last OCSNG inventory date', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('Import date in GLPI', 'ocsinventoryng') . "</th>";
               echo "<th>" . __('OCSNG server', 'ocsinventoryng') . "</th></tr>";

               while ($data = $DB->fetchArray($result)) {

                  echo PluginOcsinventoryngOcsAlert::displayBody($data);
               }
               echo "</table></div>";
            } else {
               echo "<br><div align='center'><b>" . __('No computer not synchronized since more', 'ocsinventoryng') . " " . $delay_ocs . " " . _n('Day', 'Days', 2) . "</b></div>";
            }
         }
         echo "<br>";

      }
   }


   /**
    * @return bool
    * @internal param $target
    */
   function showFormAutomaticSynchronization() {

      if (!Session::haveRight("plugin_ocsinventoryng_sync", READ)) {
         return false;
      }
      $canedit = Session::haveRight("plugin_ocsinventoryng_sync", UPDATE);
      $this->getFromDB(1);
      $this->showFormHeader();

      echo "<tr class='tab_bg_1'>";
      echo "<td> " . __('Show processes where nothing was changed', 'ocsinventoryng') . " </td><td>";
      Dropdown::showYesNo("is_displayempty", $this->fields["is_displayempty"]);
      echo "</td>";
      echo "<td rowspan='4' class='middle right'> " . __('Comments') . "</td>";
      echo "<td class='center middle' rowspan='4'>";
      echo "<textarea cols='40' rows='5' name='comment' >" . $this->fields["comment"] . "</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td> " . __('Authorize the OCSNG update (purge agents when purge GLPI computers or from Automatic actions)', 'ocsinventoryng') . " </td><td>";
      Dropdown::showYesNo('allow_ocs_update', $this->fields['allow_ocs_update']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td> " . __('Log imported computers', 'ocsinventoryng') . " </td><td>";
      Dropdown::showYesNo('log_imported_computers', $this->fields['log_imported_computers']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td> " . __('Refresh information of a process every', 'ocsinventoryng') . " </td><td>";
      Html::autocompletionTextField($this, "delay_refresh", ['size' => 5]);
      echo "&nbsp;" . _n('second', 'seconds', 2, 'ocsinventoryng') . "</td>";
      echo "</tr>";

      $this->showFormButtons(['canedit' => $canedit,
                              'candel'  => false]);

      return true;
   }


   /**
    *
    */
   function showScriptLock() {

      echo "<div class='center'>";
      echo "<form name='lock' action=\"" . $_SERVER['HTTP_REFERER'] . "\" method='post'>";
      echo Html::hidden('id', ['value' => 1]);
      echo "<table class='tab_cadre'>";
      echo "<tr class='tab_bg_2'>";
      echo "<th>&nbsp;" . __('Check OCSNG import script', 'ocsinventoryng') . "&nbsp;</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>";
      $status = $this->isScriptLocked();
      if (!$status) {
         echo __('Lock not activated', 'ocsinventoryng');
         echo "&nbsp;<i style='color:darkgreen' class='fas fa-unlock'></i>";
      } else {
         echo __('Lock activated', 'ocsinventoryng');
         echo "&nbsp;<i style='color:firebrick' class='fas fa-lock'></i>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td colspan='2' class='center'>";
      echo Html::submit((!$status ? _sx('button', 'Lock', 'ocsinventoryng') : _sx('button', 'Unlock')),
         ['name' => !$status ? "soft_lock" : "soft_unlock"]);
      echo "</td/></tr/></table><br>";
      Html::closeForm();
      echo "</div>";

   }


   /**
    * @return bool
    */
   static function isScriptLocked() {
      return file_exists(PLUGIN_OCSINVENTORYNG_LOCKFILE);
   }


   /**
    *
    */
   function setScriptLock() {

      $fp = fopen(PLUGIN_OCSINVENTORYNG_LOCKFILE, "w+");
      fclose($fp);
   }


   /**
    *
    */
   function removeScriptLock() {

      if (file_exists(PLUGIN_OCSINVENTORYNG_LOCKFILE)) {
         unlink(PLUGIN_OCSINVENTORYNG_LOCKFILE);
      }
   }


   /**
    * @return mixed
    */
   function getAllOcsServers() {
      global $DB;

      $servers[-1] = __('All servers', 'ocsinventoryng');

      $sql    = "SELECT `id`, `name`
                  FROM `glpi_plugin_ocsinventoryng_ocsservers`";
      $result = $DB->query($sql);

      while ($conf = $DB->fetchArray($result)) {
         $servers[$conf["id"]] = $conf["name"];
      }

      return $servers;
   }


   /**
    * @return mixed
    */
   static function canUpdateOCS() {

      $config = new PluginOcsinventoryngConfig();
      $config->getFromDB(1);
      return $config->fields['allow_ocs_update'];
   }

   /**
    * @return mixed
    */
   static function logProcessedComputers() {

      $config = new PluginOcsinventoryngConfig();
      $config->getFromDB(1);
      return $config->fields['log_imported_computers'];
   }

   /**
    * Display debug information for current object
    **/
   function showDebug() {

      NotificationEvent::debugEvent(new PluginOcsinventoryngNotimportedcomputer(),
                                    ['entities_id' => 0,
                                     'notimported' => []]);
   }

   //----------------- Getters and setters -------------------//

   public function useNewocsAlert() {
      return $this->fields['use_newocs_alert'];
   }

   public function getDelayOcs() {
      return $this->fields['delay_ocs'];
   }

}
