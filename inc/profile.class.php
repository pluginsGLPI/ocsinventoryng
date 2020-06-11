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
 * Class PluginOcsinventoryngProfile
 */
class PluginOcsinventoryngProfile extends CommonDBTM {


   static $rightname = "profile";

   /**
    * @see inc/CommonGLPI::getTabNameForItem()
    *
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return string|translated
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == 'Profile'
          && $item->getField('interface') != 'helpdesk') {
         return __('OCSNG', 'ocsinventoryng');
      }
      return '';
   }


   /**
    * @see inc/CommonGLPI::displayTabContentForItem()
    *
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool|true
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == 'Profile') {
         $ID   = $item->getID();
         $prof = new self();

         self::addDefaultProfileInfos($ID,
                                      ['plugin_ocsinventoryng'        => 0,
                                       'plugin_ocsinventoryng_sync'   => 0,
                                       'plugin_ocsinventoryng_view'   => 0,
                                       'plugin_ocsinventoryng_import' => 0,
                                       'plugin_ocsinventoryng_link'   => 0,
                                       'plugin_ocsinventoryng_clean'  => 0,
                                       'plugin_ocsinventoryng_rule'   => 0
                                      ]);
         $prof->showForm($ID);
      }
      return true;
   }


   /**
    * @param $ID
    */
   static function createFirstAccess($ID) {
      //85
      self::addDefaultProfileInfos($ID,
                                   ['plugin_ocsinventoryng'        => READ + CREATE + UPDATE + PURGE,
                                    'plugin_ocsinventoryng_sync'   => READ + UPDATE,
                                    'plugin_ocsinventoryng_view'   => READ,
                                    'plugin_ocsinventoryng_import' => READ + UPDATE,
                                    'plugin_ocsinventoryng_link'   => READ + UPDATE,
                                    'plugin_ocsinventoryng_clean'  => READ + UPDATE,
                                    'plugin_ocsinventoryng_rule'   => READ + UPDATE], true);
   }


   /**
    * @param      $profiles_id
    * @param      $rights
    * @param bool $drop_existing
    *
    * @internal param $profile
    */
   static function addDefaultProfileInfos($profiles_id, $rights, $drop_existing = false) {

      $profileRight = new ProfileRight();
      $dbu = new DbUtils();
      foreach ($rights as $right => $value) {
         if ($dbu->countElementsInTable('glpi_profilerights',["profiles_id" => $profiles_id, "name" => $right]) && $drop_existing) {
            $profileRight->deleteByCriteria(['profiles_id' => $profiles_id, 'name' => $right]);
         }
         if (!$dbu->countElementsInTable('glpi_profilerights',
                                         ["profiles_id" => $profiles_id, "name" => $right])) {
            $myright['profiles_id'] = $profiles_id;
            $myright['name']        = $right;
            $myright['rights']      = $value;
            $profileRight->add($myright);

            //Add right to the current session
            $_SESSION['glpiactiveprofile'][$right] = $value;
         }
      }
   }

   /**
    * Show profile form
    *
    * @param int  $profiles_id
    * @param bool $openform
    * @param bool $closeform
    *
    * @return void
    * @throws \GlpitestSQLError
    * @internal param int $items_id id of the profile
    * @internal param value $target url of target
    */
   function showForm($profiles_id = 0, $openform = true, $closeform = true) {
      global $DB, $CFG_GLPI;

      $profile = new Profile();
      $profile->getFromDB($profiles_id);

      echo "<div class='firstbloc'>";

      if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
          && $openform) {
         echo "<form action='" . $CFG_GLPI['root_doc'] . "/plugins/ocsinventoryng/front/profile.form.php' method='post'>";
      }
      //Delegating
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='4' class='center b'>" . sprintf(__('%1$s - %2$s'), 'OcsinventoryNG',
                                                             $profile->fields["name"]) . "</th>";
      echo "</tr>";

      $used = [];
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . sprintf(__('%1$s : %2$s'),
                            _n('Allowed OCSNG server', 'Allowed OCSNG servers', 2, 'ocsinventoryng'), "&nbsp;");
      //$profile = $this->fields['id'];
      $crit = ['profiles_id' => $profiles_id];
      foreach ($DB->request("glpi_plugin_ocsinventoryng_ocsservers_profiles", $crit) as $data) {
         $used[$data['plugin_ocsinventoryng_ocsservers_id']]     = $data['plugin_ocsinventoryng_ocsservers_id'];
         $configid[$data['plugin_ocsinventoryng_ocsservers_id']] = $data['id'];
      }
      if (Session::haveRight("profile", UPDATE)) {
         Dropdown::show('PluginOcsinventoryngOcsServer', ['width'     => '50%',
                                                          'used'      => $used,
                                                          'value'     => '',
                                                          'condition' => ["is_active" => 1],
                                                          'toadd'     => ['-1' => __('All')]]);
         echo Html::hidden('profile', ['value' => $profiles_id]);
         echo Html::submit(_sx('button', 'Add'), ['name' => 'addocsserver']);
      }

      echo "</td><td>";

      echo "<table width='100%'><tr class='tab_bg_1'><td>";
      $dbu = new DbUtils();
      $nbservers = $dbu->countElementsInTable('glpi_plugin_ocsinventoryng_ocsservers_profiles',
                                        ["profiles_id" => $profiles_id]);

      $query  = "SELECT `glpi_plugin_ocsinventoryng_ocsservers`.`id`,
                       `glpi_plugin_ocsinventoryng_ocsservers`.`name`
                FROM `glpi_plugin_ocsinventoryng_ocsservers_profiles`
                LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers`
                   ON `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id` = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
                WHERE `profiles_id`= " . $_SESSION["glpiactiveprofile"]['id'] . "
                ORDER BY `name` ASC";
      $result = $DB->query($query);
      if ($data = $DB->fetchAssoc($result)) {

         $ocsserver = new PluginOcsinventoryngOcsServer();
         foreach ($used as $id) {
            if ($ocsserver->getFromDB($id)) {
               echo "<br>";
               if (Session::haveRight("profile", UPDATE)) {
                  echo Html::input('item[' . $configid[$id] . ']', ['type'  => 'checkbox',
                                                                    'value' => 1]);
               }
               echo $ocsserver->getLink();
            }
         }
      }
      if (!$nbservers) {
         echo __('None');
      }
      echo "</td></tr>";
      if ($nbservers && Session::haveRight("profile", UPDATE)) {
         echo "<tr class='tab_bg_1 center'><td>";
         echo Html::submit(_sx('button', 'Delete'), ['name' => 'delete']);
         echo "</td></tr>";
      }
      echo "</table>";
      echo "</td></tr>";
      echo "</table>";
      Html::closeForm();

      if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
          && $openform
      ) {

         echo "<form method='post' action='" . $profile->getFormURL() . "'>";
      }

      $rights = $this->getAllRights();

      $profile->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
                                                    'default_class' => 'tab_bg_2',
                                                    'title'         => __('General')]);

      if ($canedit
          && $closeform) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $profiles_id]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</div>\n";
         Html::closeForm();
      }

      echo "</div>";
   }

   /**
    * @return array
    */
   static function getAllRights() {
      $rights = [['itemtype' => 'PluginOcsinventoryngOcsServer',
                  'label'    => _n('OCSNG server', 'OCSNG servers', 2, 'ocsinventoryng'),
                  'field'    => 'plugin_ocsinventoryng'],
                 ['itemtype' => 'PluginOcsinventoryngOcsServer',
                  'label'    => __('Manually synchronization', 'ocsinventoryng'),
                  'field'    => 'plugin_ocsinventoryng_sync',
                  'rights'   => [READ   => __('Read'),
                                 UPDATE => __('Update')]],
                 ['itemtype' => 'PluginOcsinventoryngOcsServer',
                  'label'    => __('See information', 'ocsinventoryng'),
                  'field'    => 'plugin_ocsinventoryng_view',
                  'rights'   => [READ => __('Read')]],
                 ['itemtype' => 'PluginOcsinventoryngOcsServer',
                  'label'    => __('Clean links between GLPI and OCSNG', 'ocsinventoryng'),
                  'field'    => 'plugin_ocsinventoryng_clean',
                  'rights'   => [READ   => __('Read'),
                                 UPDATE => __('Update')]],
                 ['itemtype' => 'PluginOcsinventoryngOcsServer',
                  'label'    => __('Import computer', 'ocsinventoryng'),
                  'field'    => 'plugin_ocsinventoryng_import',
                  'rights'   => [READ   => __('Read'),
                                 UPDATE => __('Update')]],
                 ['itemtype' => 'PluginOcsinventoryngOcsServer',
                  'label'    => __('Link computer', 'ocsinventoryng'),
                  'field'    => 'plugin_ocsinventoryng_link',
                  'rights'   => [READ   => __('Read'),
                                 UPDATE => __('Update')]],
                 ['itemtype' => 'PluginOcsinventoryngOcsServer',
                  'label'    => _n('Rule', 'Rules', 2),
                  'field'    => 'plugin_ocsinventoryng_rule',
                  'rights'   => [READ   => __('Read'),
                                 UPDATE => __('Update')]]];
      return $rights;
   }

   /**
    * Init profiles
    *
    * @param $old_right
    *
    * @return int
    */

   static function translateARight($old_right) {
      switch ($old_right) {
         case '':
            return 0;
         case 'r' :
            return READ;
         case 'w':
            return READ + UPDATE;
         case '0':
         case '1':
            return $old_right;

         default :
            return 0;
      }
   }

   /**
    * @since 0.85
    * Migration rights from old system to the new one for one profile
    *
    * @param $profiles_id the profile ID
    *
    * @return bool
    */
   static function migrateOneProfile($profiles_id) {
      global $DB;
      //Cannot launch migration if there's nothing to migrate...
      if (!$DB->tableExists('glpi_plugin_ocsinventoryng_profiles')) {
         return true;
      }

      foreach ($DB->request('glpi_plugin_ocsinventoryng_profiles',
                            "`profiles_id`=$profiles_id") as $profile_data) {

         $matching       = ['ocsng'       => 'plugin_ocsinventoryng',
                            'sync_ocsng'  => 'plugin_ocsinventoryng_sync',
                            'view_ocsng'  => 'plugin_ocsinventoryng_view',
                            'clean_ocsng' => 'plugin_ocsinventoryng_clean',
                            'rule_ocs'    => 'plugin_ocsinventoryng_rule'];
         $current_rights = ProfileRight::getProfileRights($profiles_id, array_values($matching));
         foreach ($matching as $old => $new) {
            if (!isset($current_rights[$old])) {
               $query = "UPDATE `glpi_profilerights` 
                         SET `rights` = '" . self::translateARight($profile_data[$old]) . "' 
                         WHERE `name` = '$new' AND `profiles_id` = $profiles_id";
               $DB->query($query);
            }
         }
      }
   }

   /**
    * Initialize profiles, and migrate it necessary
    */
   static function initProfile() {
      global $DB;
      $profile = new self();
      $dbu = new DbUtils();
      //Add new rights in glpi_profilerights table
      foreach ($profile->getAllRights() as $data) {
         if ($dbu->countElementsInTable("glpi_profilerights",
                                  ["name" => $data['field']]) == 0) {
            ProfileRight::addProfileRights([$data['field']]);
         }
      }

      //Migration old rights in new ones
      foreach ($DB->request("SELECT `id` FROM `glpi_profiles`") as $prof) {
         self::migrateOneProfile($prof['id']);
      }
      foreach ($DB->request("SELECT *
                           FROM `glpi_profilerights` 
                           WHERE `profiles_id`='" . $_SESSION['glpiactiveprofile']['id'] . "' 
                              AND `name` LIKE '%plugin_ocsinventoryng%'") as $prof) {
         $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
      }
   }


   static function removeRightsFromSession() {
      foreach (self::getAllRights() as $right) {
         if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
            unset($_SESSION['glpiactiveprofile'][$right['field']]);
         }
      }
   }

   /**
    * @param $profile
    */
   static function addAllServers($profile) {
      global $DB;

      $profservers = new PluginOcsinventoryngOcsserver_Profile();

      $query = "SELECT `glpi_plugin_ocsinventoryng_ocsservers`.`id`
              FROM `glpi_plugin_ocsinventoryng_ocsservers`
              LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers_profiles`
                ON (`glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id`
                         = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
                     AND `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`profiles_id` = " . $profile . ")
              WHERE `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`id` IS NULL
                    AND `glpi_plugin_ocsinventoryng_ocsservers`.`is_active` = 1";

      foreach ($DB->request($query) as $data) {
         $input['plugin_ocsinventoryng_ocsservers_id'] = $data['id'];
         $input['profiles_id']                         = $profile;
         $profservers->add($input);
      }
   }
}
