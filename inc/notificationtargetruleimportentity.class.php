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
 * Class PluginOcsinventoryngNotificationRuleImportEntity
 */
class PluginOcsinventoryngNotificationTargetRuleImportEntity extends NotificationTarget {

   /**
    * @param int $nb
    * @return translated
    */
   static function getTypeName($nb = 0) {
      return __('Elements not match with the rule', 'ocsinventoryng');
   }

   /**
    * @see NotificationTarget::getEvents()
    */
   function getEvents() {
      return ['checkruleimportentity' => __('Elements not match with the rule by automatic actions', 'ocsinventoryng')];
   }


   /**
    * @see NotificationTarget::addDataForTemplate()
    *
    * @param       $event
    * @param array $options
    */
   function addDataForTemplate($event, $options = []) {
      global $CFG_GLPI;

      $this->data['##checkruleimportentity.date##']   = Html::convDateTime(date('Y-m-d H:i:s'));
      $this->data['##checkruleimportentity.title##']  = __('Verification of assignment rules for entities and locations', 'ocsinventoryng');
      $this->data['##checkruleimportentity.entity##'] = Dropdown::getDropdownName('glpi_entities',
                                                                                  $options['entities_id']);

      foreach ($options['items'] as $id => $item) {
         if (!empty($item)) {
            $tmp = [];
            $tmp['##checkruleimportentity.entity##']   = Dropdown::getDropdownName('glpi_entities',
                                                                                   $item['entities_id']);
            $tmp['##checkruleimportentity.computer##'] = $item['name'];
            $url                                       = $CFG_GLPI["url_base"] . "/index.php?redirect=Computer_" . $item['id'];
            $tmp['##checkruleimportentity.url##']      = urldecode($url);
            $tmp['##checkruleimportentity.location##'] = Dropdown::getDropdownName('glpi_locations',
                                                                                   $item['locations_id']);

            $tmp['##checkruleimportentity.error##']     = "";
            $tmp['##checkruleimportentity.dataerror##'] = "";
            $tmp['##checkruleimportentity.url_rule##']  = "";
            $tmp['##checkruleimportentity.name_rule##'] = "";

            foreach ($item['error'] as $key => $data) {

               if ($data === PluginOcsinventoryngRuleImportEntity::NO_RULE) {
                  $tmp['##checkruleimportentity.error##'] .= __('No rules match', 'ocsinventoryng');
               } else {
                  $tmp['##checkruleimportentity.error##'] .= __($data) . "\n";
                  if ('Entity' == $data) {
                     $tmp['##checkruleimportentity.dataerror##'] .= Dropdown::getDropdownName('glpi_entities',
                                                                                              $item['dataerror'][$key]) . "\n";
                  } else {
                     $tmp['##checkruleimportentity.dataerror##'] .= Dropdown::getDropdownName('glpi_locations',
                                                                                              $item['dataerror'][$key]) . "\n";
                  }

                  if (isset($item['ruleid'])) {
                     $url_rule                                   = $CFG_GLPI["url_base"] . "/index.php?redirect=RuleImportEntity_" . $item['ruleid'];
                     $tmp['##checkruleimportentity.url_rule##']  = $url_rule;
                     $tmp['##checkruleimportentity.name_rule##'] = $item['rule_name'];
                  }
               }
            }

            $this->data['checkruleimportentityitems'][] = $tmp;
         }
      }

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->data[$tag])) {
            $this->data[$tag] = $values['label'];
         }
      }
   }

   /**
    * @see NotificationTarget::getTags()
    */
   function getTags() {

      $tags = ['checkruleimportentity.date'           => __('Date'),
               'checkruleimportentity.url'            => __('Link', 'ocsinventoryng'),
               'checkruleimportentity.entity'         => __('Entity'),
               'checkruleimportentity.computer'       => __('Computer'),
               'checkruleimportentity.location'       => __('Location'),
               'checkruleimportentity.error'          => __('Error'),
               'checkruleimportentity.name_rule'      => __('Rule'),
               'checkruleimportentity.dataerror'      => __('Data error', 'ocsinventoryng')];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                                   'label' => $label,
                                   'value' => true]);
      }

      $this->addTagToList(['tag'     => 'checkruleimportentityitems',
                                'label'   => _n('Item', 'Items', 2),
                                'value'   => false,
                                'foreach' => true,
                                'events'  => ['checkruleimportentity']]);
      asort($this->tag_descriptions);
   }
}
