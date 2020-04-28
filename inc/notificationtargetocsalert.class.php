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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginOcsinventoryngNotificationTargetOcsAlert
 */
class PluginOcsinventoryngNotificationTargetOcsAlert extends NotificationTarget {

   static $rightname = "plugin_ocsinventoryng";

   /**
    * @return array
    */
   function getEvents() {
      return ['ocs'    => __('Computers not synchronized with OCS-NG since X days', 'ocsinventoryng'),
              'newocs' => __('New imported computers from OCS-NG', 'ocsinventoryng')];
   }

   /**
    * @param       $event
    * @param array $options
    */
   function addDataForTemplate($event, $options = []) {
      global $CFG_GLPI;

      $this->data['##ocsmachine.entity##']      =
         Dropdown::getDropdownName('glpi_entities',
                                   $options['entities_id']);
      $this->data['##lang.ocsmachine.entity##'] = __('Entity');

      $events = $this->getAllEvents();

      $delay_ocs = $options["delay_ocs"];

      if ($event == "newocs") {
         $this->data['##lang.ocsmachine.title##'] = $events[$event];
      } else {
         $this->data['##lang.ocsmachine.title##'] = __('Computers not synchronized with OCS-NG since more', 'ocsinventoryng') . " " . $delay_ocs . " " . _n('Day', 'Days', 2);
      }
      $this->data['##lang.ocsmachine.name##']            = __('Name');
      $this->data['##lang.ocsmachine.urlname##']         = __('URL');
      $this->data['##lang.ocsmachine.operatingsystem##'] = __('Operating system');
      $this->data['##lang.ocsmachine.state##']           = __('Status');
      $this->data['##lang.ocsmachine.location##']        = __('Location');
      $this->data['##lang.ocsmachine.user##']            = __('User') . " / " . __('Group') . " / " . __('Alternate username');
      $this->data['##lang.ocsmachine.urluser##']         = __('URL');
      $this->data['##lang.ocsmachine.urlgroup##']        = __('URL');
      $this->data['##lang.ocsmachine.lastocsupdate##']   = __('Last OCSNG inventory date', 'ocsinventoryng');
      $this->data['##lang.ocsmachine.lastupdate##']      = __('Import date in GLPI', 'ocsinventoryng');
      $this->data['##lang.ocsmachine.ocsserver##']       = __('OCSNG server', 'ocsinventoryng');

      foreach ($options['ocsmachines'] as $id => $ocsmachine) {
         $tmp = [];

         $tmp['##ocsmachine.urlname##']         = urldecode($CFG_GLPI["url_base"] . "/index.php?redirect=computer_" .
                                                            $ocsmachine['id']);
         $tmp['##ocsmachine.name##']            = $ocsmachine['name'];
         $tmp['##ocsmachine.operatingsystem##'] = Dropdown::getDropdownName("glpi_operatingsystems",
                                                                            $ocsmachine['operatingsystems_id']);
         $tmp['##ocsmachine.state##']           = Dropdown::getDropdownName("glpi_states",
                                                                            $ocsmachine['states_id']);
         $tmp['##ocsmachine.location##']        = Dropdown::getDropdownName("glpi_locations",
                                                                            $ocsmachine['locations_id']);

         $tmp['##ocsmachine.urluser##'] = urldecode($CFG_GLPI["url_base"] . "/index.php?redirect=user_" .
                                                    $ocsmachine['users_id']);

         $tmp['##ocsmachine.urlgroup##'] = urldecode($CFG_GLPI["url_base"] . "/index.php?redirect=group_" .
                                                     $ocsmachine['groups_id']);
         $dbu = new DbUtils();
         $tmp['##ocsmachine.user##']    = $dbu->getUserName($ocsmachine['users_id']);
         $tmp['##ocsmachine.group##']   = Dropdown::getDropdownName("glpi_groups",
                                                                    $ocsmachine['groups_id']);
         $tmp['##ocsmachine.contact##'] = $ocsmachine['contact'];

         $tmp['##ocsmachine.lastocsupdate##'] = Html::convDateTime($ocsmachine['last_ocs_update']);
         $tmp['##ocsmachine.lastupdate##']    = Html::convDateTime($ocsmachine['last_update']);
         $tmp['##ocsmachine.ocsserver##']     = Dropdown::getDropdownName("glpi_plugin_ocsinventoryng_ocsservers",
                                                                          $ocsmachine['plugin_ocsinventoryng_ocsservers_id']);

         $this->data['ocsmachines'][] = $tmp;
      }
   }

   /**
    *
    */
   function getTags() {

      $tags = ['ocsmachine.name'            => __('Name'),
                    'ocsmachine.urlname'         => __('URL') . " " . __('Name'),
                    'ocsmachine.operatingsystem' => __('Operating system'),
                    'ocsmachine.state'           => __('Status'),
                    'ocsmachine.location'        => __('Location'),
                    'ocsmachine.user'            => __('User'),
                    'ocsmachine.urluser'         => __('URL') . " " . __('User'),
                    'ocsmachine.group'           => __('Group'),
                    'ocsmachine.urlgroup'        => __('URL') . " " . __('Group'),
                    'ocsmachine.contact'         => __('Alternate username'),
                    'ocsmachine.lastocsupdate'   => __('Last OCSNG inventory date', 'ocsinventoryng'),
                    'ocsmachine.lastupdate'      => __('Import date in GLPI', 'ocsinventoryng'),
                    'ocsmachine.ocsserver'       => __('OCSNG server', 'ocsinventoryng')];
      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag, 'label' => $label,
                                   'value' => true]);
      }

      $this->addTagToList(['tag'     => 'ocsinventoryng',
                                'label'   => PluginOcsinventoryngOcsAlert::getTypeName(2),
                                'value'   => false,
                                'foreach' => true,
                                'events'  => ['ocs', 'newocs']]);

      asort($this->tag_descriptions);
   }
}
