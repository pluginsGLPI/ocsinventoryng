<?php
/*
 * @version $Id: notificationtargetnotimported.class.php 100 2011-06-11 07:49:18Z remi $
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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// Class NotificationTarget
class PluginOcsinventoryngNotificationTargetNotImported extends NotificationTarget {

   function getEvents() {
      global $LANG;
      return array ('not_imported' => $LANG['plugin_ocsinventoryng']["common"][18]);
   }

   function getDatasForTemplate($event,$options=array()) {
      global $LANG, $CFG_GLPI, $DB;

      $this->datas['##notimported.entity##'] = Dropdown::getDropdownName('glpi_entities',
                                                                         $options['entities_id']);

      foreach($options['notimported'] as $id => $item) {
         $tmp = array();

         $tmp['##notimported.name##']     = $item['name'];
         $tmp['##notimported.serial##']   = $item['serial'];
         $tmp['##notimported.entity##']   = Dropdown::getDropdownName('glpi_entities',
                                                                      $options['entities_id']);
         $tmp['##notimported.ocsid##']    = $item['ocsid'];
         $tmp['##notimported.deviceid##'] = $item['ocs_deviceid'];
         $tmp['##notimported.tag##']      = $item['tag'];
         $tmp['##notimported.ocsserver##'] = Dropdown::getDropdownName('glpi_plugin_ocsinventoryng_ocsservers',
                                                                       $item['ocsid']);
         $tmp['##notimported.reason##'] = PluginOcsinventoryngNotimported::getReason($item['reason']);

         $url = $CFG_GLPI["url_base"]."/index.php?redirect=plugin_ocsinventoryng_".$item['id'];
         $tmp['##notimported.url##'] = urldecode($url);

         $this->datas['notimported'][] = $tmp;
      }
      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->datas[$tag])) {
            $this->datas[$tag] = $values['label'];
         }
      }
   }

   function getTags() {
      global $LANG;

      $tags = array('notimported.id'           => 'ID',
                    'notimported.url'          => $LANG['document'][33],
                    'notimported.tag'          => $LANG['ocsconfig'][39],
                    'notimported.name'         => $LANG['common'][16],
                    'notimported.action'       => $LANG['plugin_ocsinventoryng']["common"][18],
                    'notimported.ocsid'        => 'ID OCSNG',
                    'notimported.deviceid'     => $LANG['plugin_ocsinventoryng']["common"][22],
                    'notimported.reason'       => $LANG['plugin_ocsinventoryng']["common"][34],
                    'notimported.serial'       => $LANG['common'][19]);

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'=>$tag,'label'=>$label,
                                   'value'=>true));
      }
      asort($this->tag_descriptions);
   }
}

?>