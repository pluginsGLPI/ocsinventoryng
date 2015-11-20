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

/// Location class
class PluginOcsinventoryngNetworkPortType extends CommonDropdown {
   
   public $refresh_page = true;
   
   static function getTypeName($nb=0) {
      return _n('Network port type','Network port types',$nb, 'ocsinventoryng');
   }


   function canUpdateItem() {
      if ((isset($this->fields['OCS_TYPE'])) && ($this->fields['OCS_TYPE'] == '*')
          && (isset($this->fields['OCS_TYPEMIB'])) && ($this->fields['OCS_TYPEMIB'] == '*')) {
         return false;
      }
      return parent::canUpdateItem();
   }


   function canDeleteItem() {
      if ((isset($this->fields['OCS_TYPE'])) && ($this->fields['OCS_TYPE'] == '*')
          && (isset($this->fields['OCS_TYPEMIB'])) && ($this->fields['OCS_TYPEMIB'] == '*')) {
         return false;
      }
      return parent::canDeleteItem();
   }

   
   // If we add it, then, we may have to update all cards with the same MIB, shouldn't we ?
   function post_addItem() {
      global $DB;
 
      if (isset($this->input['transform_unknown_ports_that_match']) &&
          $this->input['transform_unknown_ports_that_match']) {
         $networkport = new PluginOcsinventoryngNetworkPort();
         $query = "SELECT `id`
                     FROM `".$networkport->getTable()."`
                    WHERE `TYPE` = '".$this->fields['OCS_TYPE']."'";
         if ($this->fields['OCS_TYPEMIB'] != '*') {
            $query .= " AND `TYPEMIB` = '".$this->fields['OCS_TYPEMIB']."'";
         }
         foreach ($DB->request($query) as $line) {
            if ($networkport->getFromDBByQuery("WHERE `id`='".$line['id']."'")) {
               $networkport->transformAccordingTypes();
            }
         }
      }
   }



   function displaySpecificTypeField($ID, $field=array()) {

      switch ($field['type']) {
         case 'instantiation_type' :
            Dropdown::showFromArray($field['name'],
                                    NetworkPort::getNetworkPortInstantiationsWithNames(),
                                    array('value' => $this->fields[$field['name']]));
            break;

         case 'type' :
            Dropdown::showFromArray('type', NetworkPortEthernet::getPortTypeName(),
                                    array('value' => $this->fields[$field['name']]));
            break;
         case 'readonly_text' :
            $value = $this->fields[$field['name']];
            echo "<input type='hidden' name='".$field['name']."' value='$value'>$value";
            break;

         case 'MIB or wildcard':
            $name   = $field['name'];
            $value  = $this->fields[$name];
            $values = array($value => $value,
                            '*'    => __('Any kind', 'ocsinventoryng'));
            Dropdown::showFromArray($name, $values, array('value' => $value));
            break;
         case 'speed' :
            $standard_speeds = NetworkPortEthernet::getPortSpeed();
            if (!isset($standard_speeds[$this->fields['speed']])
                && !empty($this->fields['speed'])) {
               $speed = NetworkPortEthernet::transformPortSpeed($this->fields['speed'], true);
            } else {
               $speed = true;
            }
            Dropdown::showFromArray('speed', $standard_speeds,
                                    array('value' => $this->fields['speed'],
                                          'other' => $speed));
            break;

         case 'version' :
            Dropdown::showFromArray('version', WifiNetwork::getWifiCardVersion(),
                                    array('value' => $this->fields['version']));
            break;

      }
   }


   function getAdditionalFields() {

      $result = array('TYPE'    => array('name'  => 'OCS_TYPE',
                                         'label' => __('OCS TYPE', 'ocsinventoryng'),
                                         'type'  => 'text'),
                      'TYPEMIB' => array('name'  => 'OCS_TYPEMIB',
                                         'label' => __('OCS TYPE MIB', 'ocsinventoryng'),
                                         'type'  => 'text'),
                      array('name'  => 'instantiation_type',
                            'label' => __('Corresponding Network Port type', 'ocsinventoryng'),
                            'type'  => 'instantiation_type'),
                      array('name'  => 'type',
                            'label' => __('Ethernet medium type', 'ocsinventoryng'),
                            'type'  => 'type'),
                      array('name'  => 'speed',
                            'label' => __('Ethernet medium speed', 'ocsinventoryng'),
                            'type'  => 'speed'),
                      array('name'  => 'version',
                            'label' => __('Wifi card Version', 'ocsinventoryng'),
                            'type'  => 'version'));

      if ($this->isNewItem()) {
         $this->fields['transform_unknown_ports_that_match'] = 1;
         $result[] = array('name'  => 'transform_unknown_ports_that_match',
                           'label' => __('Transform unknown ports that match', 'ocsinventoryng'),
                           'type'  => 'bool');

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

   static function getLinkToCreateFromTypeAndTypeMIB(array $fields = array()) {
      $link = static::getFormURL().'?TYPE='.$fields['TYPE'].'&TYPEMIB='.$fields['TYPEMIB'];
      if (!empty($fields['speed'])) {
         $speed = NetworkPortEthernet::transformPortSpeed($fields['speed'], false);
         if (!empty($speed)) {
            $link .= '&SPEED='.$speed;
         }
      }
      $link .= '&rand=1'; // To reload main window
      
      Ajax::createIframeModalWindow('create_network', $link, array('title' => __('Create', 'ocsinventoryng')));
      
      return "<a href='#' onClick=\"$('#create_network').dialog('open');\">" . __('Create', 'ocsinventoryng') . "</a>";
   }


   function getFromTypeAndTypeMIB(array $fields = array()) {
      $TYPEMIB = (empty($fields['TYPEMIB']) ? '' : $fields['TYPEMIB']);
      $TYPE    = (empty($fields['TYPE']) ? '' : $fields['TYPE']);

      // First, try with TYPE AND TYPE MIB
      if ($this->getFromDBByQuery("WHERE `OCS_TYPE`='$TYPE' AND `OCS_TYPEMIB`='$TYPEMIB'")) {
         return True;
      }

      // Else, try with TYPE and wildcard as Type MIB
      if ($this->getFromDBByQuery("WHERE `OCS_TYPE`='$TYPE' AND `OCS_TYPEMIB`='*'")) {
         return True;
      }

      // Endly, return the default element
      return $this->getFromDBByQuery("WHERE `OCS_TYPE`='*' AND `OCS_TYPEMIB`='*'");
   }
}
?>