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


   static function getTypeName($nb=0) {
      return __('Rights management', 'ocsinventoryng');
   }


   static function canCreate() {
      return Session::haveRight('profile', 'w');
   }


   static function canView() {
      return Session::haveRight('profile', 'r');
   }


   /**
    * if profile deleted
    *
    * @param $prof   Profile object
   **/
   static function purgeProfiles(Profile $prof) {

      $plugprof = new self();
      $plugprof->deleteByCriteria(array('profiles_id' => $prof->getField("id")));
   }


   /**
    * @see inc/CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->getType()=='Profile' && $item->getField('interface')!='helpdesk') {
         return __('OCSNG link', 'ocsinventoryng');
      }
      return '';
   }


   /**
    * @see inc/CommonGLPI::displayTabContentForItem()
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      if ($item->getType() == 'Profile') {
         $ID   = $item->getField('id');
         $prof = new self();

         if (!$prof->getFromDBByProfile($item->getField('id'))) {
            $prof->createAccess($item->getField('id'));
         }
         $prof->showForm($item->getField('id'),
         array('target' => $CFG_GLPI["root_doc"]."/plugins/ocsinventoryng/front/profile.form.php"));
      }
      return true;
   }


   /**
    * @param $profiles_id
    */
   function getFromDBByProfile($profiles_id) {
      global $DB;

      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `profiles_id` = '" . $profiles_id . "' ";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 1) {
            return false;
         }
         $this->fields = $DB->fetch_assoc($result);

         if (is_array($this->fields) && count($this->fields)) {
            return true;
         }
         return false;
      }
      return false;
   }


   /**
    * @param $ID
   **/
   static function createFirstAccess($ID) {

      $myProf = new self();
      if (!$myProf->getFromDBByProfile($ID)) {

         $myProf->add(array('profiles_id' => $ID,
                            'ocsng'       => 'w',
                            'sync_ocsng'  => 'w',
                            'view_ocsng'  => 'r',
                            'clean_ocsng' => 'w',
                            'rule_ocs'    => 'w'));
      }
   }


   /**
    * @param $ID
   **/
   function createAccess($ID) {

      $this->add(array('profiles_id' => $ID));
   }


   static function changeProfile() {

      $prof = new self();
      if ($prof->getFromDBByProfile($_SESSION['glpiactiveprofile']['id'])) {
         $_SESSION["glpi_plugin_ocsinventoryng_profile"] = $prof->fields;
      } else {
         unset($_SESSION["glpi_plugin_ocsinventoryng_profile"]);
      }
   }


   /**
    * profiles modification
    *
    * @param $ID
    * @param $options   array
   **/
   function showForm($ID, $options=array()) {
      global $DB;

      if (!Session::haveRight("profile", "r")) {
         return false;
      }

      $target = $this->getFormURL();
      if (isset($options['target'])) {
         $target = $options['target'];
      }

      $prof = new Profile();
      if ($ID) {
         $this->getFromDBByProfile($ID);
         $prof->getFromDB($ID);
      }

      $canedit = PluginOcsinventoryngOcsServer::canCreate();
      echo "<form action='".$target."' method='post'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_2'>";
      //TRANS: %$ is a profile name
      echo "<th colspan='4' class='center b'>".sprintf(__('%1$s - %2$s'), 'OcsinventoryNG',
                                                       $prof->fields["name"])."</th>";
      echo "</tr>";

      echo "<tr><th colspan='4'>"._n('OCSNG server', 'OCSNG servers', 2, 'ocsinventoryng')."</th></tr>";

      $used = array();
      echo "<tr class='tab_bg_1'>";
      echo "<td>".sprintf(__('%1$s : %2$s'),
                          _n('OCSNG server', 'OCSNG servers', 2, 'ocsinventoryng'), "&nbsp;");
      $profile= $this->fields['profiles_id'];
      $crit =  array('profiles_id' => $prof->fields['id']);
      foreach ($DB->request("glpi_plugin_ocsinventoryng_ocsservers_profiles", $crit) as $data) {
         $used[$data['ocsservers_id']] = $data['ocsservers_id'];
         $configid[$data['ocsservers_id']] = $data['id'];
      }
      if (Session::haveRight("profile", "w")) {
         Dropdown::show('PluginOcsinventoryngOcsServer', array('used'  => $used,
                                                               'value' => ''));
         echo "&nbsp;&nbsp;<input type='hidden' name='profile' value=$profile>";
         echo "&nbsp;&nbsp;<input type='submit' name='addocsserver' value='Ajouter' class='submit' >";
      }
      $nbservers = countElementsInTable('glpi_plugin_ocsinventoryng_ocsservers_profiles',
                                        "`profiles_id` = ".$prof->fields['id']);

      $query = "SELECT `glpi_plugin_ocsinventoryng_ocsservers`.`id`,
                       `glpi_plugin_ocsinventoryng_ocsservers`.`name`
                FROM `glpi_plugin_ocsinventoryng_ocsservers_profiles`
                LEFT JOIN `glpi_plugin_ocsinventoryng_ocsservers`
                   ON `glpi_plugin_ocsinventoryng_ocsservers_profiles`.`ocsservers_id` = `glpi_plugin_ocsinventoryng_ocsservers`.`id`
                WHERE `profiles_id`= ".$_SESSION["glpiactiveprofile"]['id']."
                ORDER BY `name` ASC";
      $result = $DB->query($query);
      if ($data = $DB->fetch_assoc($result)) {

         $ocsserver = new PluginOcsinventoryngOcsServer();
         foreach ($used as $id) {
            if ($ocsserver->getFromDB($id)) {
               echo "<br>";
               if (Session::haveRight("profile", "w")) {
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
      echo "</td>";
      echo "<td>".__('Rights assignment')."</td><td>";
      Profile::dropdownNoneReadWrite("ocsng", $this->fields["ocsng"], 1, 0, 1);
      echo "</td></tr>";

      if ($nbservers && Session::haveRight("profile", "w")) {
         echo "<tr><td class='tab_bg_2' colspan='4'>";
         echo "<input type='submit' name='delete' value='Supprimer' class='submit' >";
         echo "</td></tr>";
      }
      echo "<tr class='tab_bg_2'>";
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

   }

}
?>