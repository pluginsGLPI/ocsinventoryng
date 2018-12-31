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
 * Class PluginOcsinventoryngNetworkPortType
 */
class PluginOcsinventoryngNetworkPortType extends CommonDropdown {

   public $refresh_page = true;

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {
      return _n('Network port type', 'Network port types', $nb, 'ocsinventoryng');
   }


   /**
    * @return bool|booleen
    */
   function canUpdateItem() {
      if ((isset($this->fields['OCS_TYPE'])) && ($this->fields['OCS_TYPE'] == '*')
          && (isset($this->fields['OCS_TYPEMIB'])) && ($this->fields['OCS_TYPEMIB'] == '*')) {
         return false;
      }
      return parent::canUpdateItem();
   }


   /**
    * @return bool|booleen
    */
   function canDeleteItem() {
      if ((isset($this->fields['OCS_TYPE'])) && ($this->fields['OCS_TYPE'] == '*')
          && (isset($this->fields['OCS_TYPEMIB'])) && ($this->fields['OCS_TYPEMIB'] == '*')) {
         return false;
      }
      return parent::canDeleteItem();
   }


   // If we add it, then, we may have to update all cards with the same MIB, shouldn't we ?

   /**
    *
    */
   function post_addItem() {
      global $DB;

      if (isset($this->input['transform_unknown_ports_that_match']) &&
          $this->input['transform_unknown_ports_that_match']) {
         $networkport = new PluginOcsinventoryngNetworkPort();
         $query       = "SELECT `id`
                     FROM `" . $networkport->getTable() . "`
                    WHERE `TYPE` = '" . $this->fields['OCS_TYPE'] . "'";
         if ($this->fields['OCS_TYPEMIB'] != '*') {
            $query .= " AND `TYPEMIB` = '" . $this->fields['OCS_TYPEMIB'] . "'";
         }
         foreach ($DB->request($query) as $line) {
            if ($networkport->getFromDBByCrit(['id' => $line['id']])) {
               $networkport->transformAccordingTypes();
            }
         }
      }
   }


   /**
    * @param       $ID
    * @param array $field
    */
   function displaySpecificTypeField($ID, $field = []) {

      switch ($field['type']) {
         case 'instantiation_type' :
            Dropdown::showFromArray($field['name'],
                                    NetworkPort::getNetworkPortInstantiationsWithNames(),
                                    ['value' => $this->fields[$field['name']]]);
            break;

         case 'type' :
            Dropdown::showFromArray('type', NetworkPortEthernet::getPortTypeName(),
                                    ['value' => $this->fields[$field['name']]]);
            break;
         case 'readonly_text' :
            $value = $this->fields[$field['name']];
            echo Html::hidden($field['name'], ['value' => $value]);
            break;

         case 'MIB or wildcard':
            $name   = $field['name'];
            $value  = $this->fields[$name];
            $values = [$value => $value,
                       '*'    => __('Any kind', 'ocsinventoryng')];
            Dropdown::showFromArray($name, $values, ['value' => $value]);
            break;
         case 'speed' :
            $standard_speeds = NetworkPortEthernet::getPortSpeed();
            if (!isset($standard_speeds[$this->fields['speed']])
                && !empty($this->fields['speed'])
            ) {
               $speed = NetworkPortEthernet::transformPortSpeed($this->fields['speed'], true);
            } else {
               $speed = true;
            }
            Dropdown::showFromArray('speed', $standard_speeds,
                                    ['value' => $this->fields['speed'],
                                     'other' => $speed]);
            break;

         case 'version' :
            Dropdown::showFromArray('version', WifiNetwork::getWifiCardVersion(),
                                    ['value' => $this->fields['version']]);
            break;

      }
   }


   /**
    * @return array
    */
   function getAdditionalFields() {

      $result = ['TYPE'    => ['name'  => 'OCS_TYPE',
                               'label' => __('OCS TYPE', 'ocsinventoryng'),
                               'type'  => 'text'],
                 'TYPEMIB' => ['name'  => 'OCS_TYPEMIB',
                               'label' => __('OCS TYPE MIB', 'ocsinventoryng'),
                               'type'  => 'text'],
                 ['name'  => 'instantiation_type',
                  'label' => __('Corresponding Network Port type', 'ocsinventoryng'),
                  'type'  => 'instantiation_type'],
                 ['name'  => 'type',
                  'label' => __('Ethernet medium type', 'ocsinventoryng'),
                  'type'  => 'type'],
                 ['name'  => 'speed',
                  'label' => __('Ethernet medium speed', 'ocsinventoryng'),
                  'type'  => 'speed'],
                 ['name'  => 'version',
                  'label' => __('Wifi card Version', 'ocsinventoryng'),
                  'type'  => 'version']];

      if ($this->isNewItem()) {
         $this->fields['transform_unknown_ports_that_match'] = 1;
         $result[]                                           = ['name'  => 'transform_unknown_ports_that_match',
                                                                'label' => __('Transform unknown ports that match', 'ocsinventoryng'),
                                                                'type'  => 'bool'];

         if (isset($_GET['TYPE']) && isset($_GET['TYPEMIB'])) {
            $this->fields['OCS_TYPE']    = $_GET['TYPE'];
            $this->fields['OCS_TYPEMIB'] = $_GET['TYPEMIB'];
            if (!empty($_GET['SPEED'])) {
               $this->fields['speed'] = $_GET['SPEED'];
            }
            $result['TYPE']['type'] = 'readonly_text';
            if ($_GET['TYPEMIB'] == '*') {
               $result['TYPEMIB']['type'] = 'readonly_text';
            } else {
               $result['TYPEMIB']['type'] = 'MIB or wildcard';
            }
         }
      }

      return $result;
   }

   /**
    * @param array $fields
    *
    * @return string
    */
   static function getLinkToCreateFromTypeAndTypeMIB(array $fields = []) {
      $link = static::getFormURL() . '?TYPE=' . $fields['TYPE'] . '&TYPEMIB=' . $fields['TYPEMIB'];
      if (!empty($fields['speed'])) {
         $speed = NetworkPortEthernet::transformPortSpeed($fields['speed'], false);
         if (!empty($speed)) {
            $link .= '&SPEED=' . $speed;
         }
      }
      $link .= '&rand=1'; // To reload main window

      Ajax::createIframeModalWindow('create_network', $link, ['title' => __('Create')]);

      return "<a href='#' onClick=\"$('#create_network').dialog('open');\">" . __('Create') . "</a>";
   }


   /**
    * @param array $fields
    *
    * @return bool|true
    */
   function getFromTypeAndTypeMIB(array $fields = []) {
      $TYPEMIB = (empty($fields['TYPEMIB']) ? '' : $fields['TYPEMIB']);
      $TYPE    = (empty($fields['TYPE']) ? '' : $fields['TYPE']);

      // First, try with TYPE AND TYPE MIB


      if ($this->getFromDBByCrit(['OCS_TYPE'    => $TYPE,
                                  'OCS_TYPEMIB' => $TYPEMIB])) {
         return true;
      }

      // Else, try with TYPE and wildcard as Type MIB
      if ($this->getFromDBByCrit(['OCS_TYPE'    => $TYPE,
                                  'OCS_TYPEMIB' => '*'])) {
         return true;
      }

      // Endly, return the default element
      return $this->getFromDBByCrit(['OCS_TYPE'    => '*',
                                     'OCS_TYPEMIB' => '*']);
   }
}
