<?php
/*
 * @version $Id: HEADER 2011-03-12 18:01:26 tsmr $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
// ----------------------------------------------------------------------
// Original Author of file: CAILLAUD Xavier
// Purpose of file: plugin ocsinventoryng v 1.0.0 - GLPI 0.83
// ----------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginOcsinventoryngProfile extends CommonDBTM {


   static function getTypeName($nb=0) {
      return __('Rights management');
   }


   function canCreate() {
      return Session::haveRight('profile', 'w');
   }


   function canView() {
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
         return __('OCSNG Mode');
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
      echo "<td>"._n('Server', 'Servers', 2)."</td><td>";
      Profile::dropdownNoneReadWrite("ocsng", $this->fields["ocsng"], 1, 0, 1);
      echo "</td>";
      echo "<td>".__('Manually synchronization')."</td><td>";
      Profile::dropdownNoneReadWrite("sync_ocsng", $this->fields["sync_ocsng"], 1, 0, 1);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('See information')."</td><td>";
      Profile::dropdownNoneReadWrite("view_ocsng", $this->fields["view_ocsng"], 1, 1, 0);
      echo "<td>".__('Clean links between GLPI and OCSNG')."</td><td>";
      Profile::dropdownNoneReadWrite("clean_ocsng", $this->fields["clean_ocsng"], 1, 1, 1);
      echo "</td></tr>\n";

      echo "<input type='hidden' name='id' value=".$this->fields["id"].">";

      $options['candel'] = false;
      $this->showFormButtons($options);

   }

}
?>