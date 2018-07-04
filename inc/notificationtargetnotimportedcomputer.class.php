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
 * Class PluginOcsinventoryngNotificationTargetNotImportedcomputer
 */
class PluginOcsinventoryngNotificationTargetNotImportedcomputer extends NotificationTarget {

   /**
    * @return array
    */
   function getEvents() {
      return ['not_imported' => __('Computers not imported by automatic actions',
                                   'ocsinventoryng')];
   }


   /**
    * @param       $event
    * @param array $options
    */
   function getDatasForTemplate($event, $options = []) {
      global $CFG_GLPI;

      $this->data['##notimported.entity##'] = Dropdown::getDropdownName('glpi_entities',
                                                                         $options['entities_id']);

      foreach ($options['notimported'] as $id => $item) {
         $tmp = [];

         $tmp['##notimported.name##']      = $item['name'];
         $tmp['##notimported.serial##']    = $item['serial'];
         $tmp['##notimported.entity##']    = Dropdown::getDropdownName('glpi_entities',
                                                                       $options['entities_id']);
         $tmp['##notimported.ocsid##']     = $item['ocsid'];
         $tmp['##notimported.deviceid##']  = $item['ocs_deviceid'];
         $tmp['##notimported.tag##']       = $item['tag'];
         $tmp['##notimported.ocsserver##'] = Dropdown::getDropdownName('glpi_plugin_ocsinventoryng_ocsservers',
                                                                       $item['ocsid']);
         $tmp['##notimported.reason##']    = PluginOcsinventoryngNotimportedcomputer::getReason($item['reason']);

         $url                        = $CFG_GLPI["url_base"] . "/index.php?redirect=plugin_ocsinventoryng_" . $item['id'];
         $tmp['##notimported.url##'] = urldecode($url);

         $this->data['notimported'][] = $tmp;
      }
      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->data[$tag])) {
            $this->data[$tag] = $values['label'];
         }
      }
   }

   /**
    *
    */
   function getTags() {

      $tags = ['notimported.id'       => __('ID'),
               'notimported.url'      => __('Web link'),
               'notimported.tag'      => __('OCSNG TAG', 'ocsinventoryng'),
               'notimported.name'     => __('Name'),
               'notimported.action'   => __('Computers not imported by automatic actions',
                                            'ocsinventoryng'),
               'notimported.ocsid'    => __('OCSNG ID', 'ocsinventoryng'),
               'notimported.deviceid' => __('Device ID', 'ocsinventoryng'),
               'notimported.reason'   => __('Reason of rejection'),
               'notimported.serial'   => __('Serial number')];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag, 'label' => $label,
                              'value' => true]);
      }
      asort($this->tag_descriptions);
   }
}
