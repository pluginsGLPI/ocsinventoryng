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

class PluginOcsinventoryngProfile extends CommonDBTM {


   static $rightname = "profile";

   /**
    * @see inc/CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->getType()=='Profile' 
            && $item->getField('interface')!='helpdesk') {
         return __('OCSNG', 'ocsinventoryng');
      }
      return '';
   }


   /**
    * @see inc/CommonGLPI::displayTabContentForItem()
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      if ($item->getType() == 'Profile') {
         $ID = $item->getID();
         $prof = new self();

         self::addDefaultProfileInfos($ID, 
                                    array('plugin_ocsinventoryng'       => 0,
                                          'plugin_ocsinventoryng_sync'   => 0,
                                          'plugin_ocsinventoryng_view'   => 0,
                                          'plugin_ocsinventoryng_clean'  => 0,
                                          'plugin_ocsinventoryng_rule'   => 0
                                          ));
         $prof->showForm($ID);
      }
      return true;
   }
   
   
   static function createFirstAccess($ID) {
      //85
      self::addDefaultProfileInfos($ID,
                                    array('plugin_ocsinventoryng'               => READ + CREATE + UPDATE + PURGE,
                                          'plugin_ocsinventoryng_sync'     => READ + UPDATE,
                                          'plugin_ocsinventoryng_view'     => READ,
                                          'plugin_ocsinventoryng_clean'     => READ + UPDATE,
                                          'plugin_ocsinventoryng_rule'     => READ + UPDATE), true);
   }


    /**
    * @param $profile
   **/
   static function addDefaultProfileInfos($profiles_id, $rights, $drop_existing = false) {
      global $DB;
      
      $profileRight = new ProfileRight();
      foreach ($rights as $right => $value) {
         if (countElementsInTable('glpi_profilerights',
                                   "`profiles_id`='$profiles_id' AND `name`='$right'") && $drop_existing) {
            $profileRight->deleteByCriteria(array('profiles_id' => $profiles_id, 'name' => $right));
         }
         if (!countElementsInTable('glpi_profilerights',
                                   "`profiles_id`='$profiles_id' AND `name`='$right'")) {
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
    * profiles modification
    *
    * @param $ID
    * @param $options   array
   **/
   /*function showForm($ID, $options=array()) {

      if (!Session::haveRight("profile", "r")) {
         return false;
      }

      $prof = new Profile();
      if ($ID) {
         $this->getFromDBByProfile($ID);
         $prof->getFromDB($ID);
      }

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_2'>";
      //TRANS: %$ is a profile name
      echo "<th colspan='4' class='center b'>".sprintf(__('%1$s - %2$s'), __('Rights management'),
                                                       $prof->fields["name"])."</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>"._n('OCSNG server', 'OCSNG servers', 2, 'ocsinventoryng')."</td><td>";
      Profile::dropdownNoneReadWrite("ocsng", $this->fields["ocsng"], 1, 0, 1);
      echo "</td>";
      echo "<td>".__('Manually synchronization', 'ocsinventoryng')."</td><td>";
      Profile::dropdownNoneReadWrite("sync_ocsng", $this->fields["sync_ocsng"], 1, 0, 1);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('See information', 'ocsinventoryng')."</td><td>";
      Profile::dropdownNoneReadWrite("view_ocsng", $this->fields["view_ocsng"], 1, 1, 0);
      echo "<td>".__('Clean links between GLPI and OCSNG', 'ocsinventoryng')."</td><td>";
      Profile::dropdownNoneReadWrite("clean_ocsng", $this->fields["clean_ocsng"], 1, 1, 1);
      echo "</td></tr>\n";

      echo "<input type='hidden' name='id' value=".$this->fields["id"].">";

      $options['candel'] = false;
      $this->showFormButtons($options);

   }*/
   
   /**
    * Show profile form
    *
    * @param $items_id integer id of the profile
    * @param $target value url of target
    *
    * @return nothing
    **/
   function showForm($profiles_id=0, $openform=TRUE, $closeform=TRUE) {
      global $DB, $CFG_GLPI;
      
      $profile = new Profile();
      $profile->getFromDB($profiles_id);
      
      echo "<div class='firstbloc'>";
      
      if (($canedit = Session::haveRightsOr(self::$rightname, array(CREATE, UPDATE, PURGE)))
          && $openform) {
         echo "<form action='".$CFG_GLPI['root_doc']."/plugins/ocsinventoryng/front/profile.form.php' method='post'>";
      }
      //Delegating
      $effective_rights = ProfileRight::getProfileRights($profiles_id, array('plugin_ocsinventoryng'));
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='4' class='center b'>".sprintf(__('%1$s - %2$s'), 'OcsinventoryNG',
                                                        $profile->fields["name"])."</th>";
      echo "</tr>";
 
      $used = array();
      echo "<tr class='tab_bg_1'>";
      echo "<td>".sprintf(__('%1$s : %2$s'),
                          _n('Allowed OCSNG server', 'Allowed OCSNG servers', 2, 'ocsinventoryng'), "&nbsp;");
      //$profile = $this->fields['id'];
      $crit    =  array('profiles_id' => $profiles_id);
      foreach ($DB->request("glpi_plugin_ocsinventoryng_ocsservers_profiles", $crit) as $data) {
         $used[$data['plugin_ocsinventoryng_ocsservers_id']]     = $data['plugin_ocsinventoryng_ocsservers_id'];
         $configid[$data['plugin_ocsinventoryng_ocsservers_id']] = $data['id'];
      }
      if (Session::haveRight("profile", UPDATE)) {
         Dropdown::show('PluginOcsinventoryngOcsServer', array('width' => '50%',
                                                               'used'  => $used,
                                                               'value' => '',
                                                               'condition' => "is_active = 1"));
         echo "&nbsp;&nbsp;<input type='hidden' name='profile' value='$profiles_id'>";
         echo "&nbsp;&nbsp;<input type='submit' name='addocsserver' value=\""._sx('button','Add')."\" class='submit' >";
      }
      
      echo "</td><td>";
      
      echo "<table width='100%'><tr class='tab_bg_1'><td>";
      
      $nbservers = countElementsInTable('glpi_plugin_ocsinventoryng_ocsservers_profiles',
                                        "`profiles_id` = ".$profiles_id);

      $query = "SELECT `glpi_plugin_ocsinventoryng_ocsservers`.`id`,
                       `glpi_plugin_ocsinventoryng_ocsservers`.`name`
                FROM `glpi_plugin_ocsinventoryng_ocsservers_profiles`
                LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers`
                   ON `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`plugin_ocsinventoryng_ocsservers_id` = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
                WHERE `profiles_id`= ".$_SESSION["glpiactiveprofile"]['id']."
                ORDER BY `name` ASC";
      $result = $DB->query($query);
      if ($data = $DB->fetch_assoc($result)) {

         $ocsserver = new PluginOcsinventoryngOcsServer();
         foreach ($used as $id) {
            if ($ocsserver->getFromDB($id)) {
               echo "<br>";
               if (Session::haveRight("profile", UPDATE)) {
                  echo "<input type='checkbox' name='item[".$configid[$id]."]' value='1'>";
               }
               if ($data['id'] == $id) {
                  echo $ocsserver->getLink();
               } else {
                  echo $ocsserver->getName();
               }
            }
         }
      }
      if (!$nbservers) {
         _e('None');
      }
      echo "</td></tr>";
      if ($nbservers && Session::haveRight("profile", UPDATE)) {
         echo "<tr class='tab_bg_1 center'><td>";
         echo "<input type='submit' name='delete' value='Supprimer' class='submit' >";
         echo "</td></tr>";
      }
      echo "</table>";
      echo "</td></tr>";
      echo "</table>";
      Html::closeForm();
      
      if (($canedit = Session::haveRightsOr(self::$rightname, array(CREATE, UPDATE, PURGE)))
          && $openform) {
         
         echo "<form method='post' action='".$profile->getFormURL()."'>";
      }

      $rights = $this->getAllRights();

      $profile->displayRightsChoiceMatrix($rights, array('canedit'       => $canedit,
                                                         'default_class' => 'tab_bg_2',
                                                         'title'         => __('General')));

      
      if ($canedit
          && $closeform) {
         echo "<div class='center'>";
         echo Html::hidden('id', array('value' => $profiles_id));
         echo Html::submit(_sx('button', 'Save'), 
                           array('name' => 'update'));
         echo "</div>\n";
         Html::closeForm();
      }
      
      echo "</div>";
   }
   
   static function getAllRights() {
      
      
      $rights = array(
                  array('itemtype' => 'PluginOcsinventoryngOcsServer',
                           'label'    =>  _n('OCSNG server', 'OCSNG servers', 2, 'ocsinventoryng'),
                           'field'    => 'plugin_ocsinventoryng'),
                  array('itemtype' => 'PluginOcsinventoryngOcsServer',
                           'label'    =>  __('Manually synchronization', 'ocsinventoryng'),
                           'field'    => 'plugin_ocsinventoryng_sync',
                   'rights' => array(READ    => __('Read'),UPDATE  => __('Update'))),
                   array('itemtype' => 'PluginOcsinventoryngOcsServer',
                           'label'    =>  __('See information', 'ocsinventoryng'),
                           'field'    => 'plugin_ocsinventoryng_view',
                   'rights' => array(READ    => __('Read'))),
                   array('itemtype' => 'PluginOcsinventoryngOcsServer',
                           'label'    =>  __('Clean links between GLPI and OCSNG', 'ocsinventoryng'),
                           'field'    => 'plugin_ocsinventoryng_clean',
                   'rights' => array(READ    => __('Read'),UPDATE  => __('Update'))),
                   array('itemtype' => 'PluginOcsinventoryngOcsServer',
                           'label'    =>  _n('Rule', 'Rules', 2),
                           'field'    => 'plugin_ocsinventoryng_rule',
                   'rights' => array(READ    => __('Read'),UPDATE  => __('Update')))
                   );

      return $rights;
   }
   
   
   /**
    * Init profiles
    *
    **/
    
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
   * @param $profiles_id the profile ID
   */
   static function migrateOneProfile($profiles_id) {
      global $DB;
      //Cannot launch migration if there's nothing to migrate...
      if (!TableExists('glpi_plugin_ocsinventoryng_profiles')) {
      return true;
      }
      
      foreach ($DB->request('glpi_plugin_ocsinventoryng_profiles', 
                            "`profiles_id`='$profiles_id'") as $profile_data) {

         $matching = array('ocsng'    => 'plugin_ocsinventoryng', 
                           'sync_ocsng' => 'plugin_ocsinventoryng_sync', 
                           'view_ocsng' => 'plugin_ocsinventoryng_view', 
                           'clean_ocsng' => 'plugin_ocsinventoryng_clean', 
                           'rule_ocs' => 'plugin_ocsinventoryng_rule');
         $current_rights = ProfileRight::getProfileRights($profiles_id, array_values($matching));
         foreach ($matching as $old => $new) {
            if (!isset($current_rights[$old])) {
               $query = "UPDATE `glpi_profilerights` 
                         SET `rights`='".self::translateARight($profile_data[$old])."' 
                         WHERE `name`='$new' AND `profiles_id`='$profiles_id'";
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

      //Add new rights in glpi_profilerights table
      foreach ($profile->getAllRights(true) as $data) {
         if (countElementsInTable("glpi_profilerights", 
                                  "`name` = '".$data['field']."'") == 0) {
            ProfileRight::addProfileRights(array($data['field']));
         }
      }
      
      //Migration old rights in new ones
      foreach ($DB->request("SELECT `id` FROM `glpi_profiles`") as $prof) {
         self::migrateOneProfile($prof['id']);
      }
      foreach ($DB->request("SELECT *
                           FROM `glpi_profilerights` 
                           WHERE `profiles_id`='".$_SESSION['glpiactiveprofile']['id']."' 
                              AND `name` LIKE '%plugin_ocsinventoryng%'") as $prof) {
         $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights']; 
      }
   }


   static function removeRightsFromSession() {
      foreach (self::getAllRights(true) as $right) {
         if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
            unset($_SESSION['glpiactiveprofile'][$right['field']]);
         }
      }
   }

}
?>