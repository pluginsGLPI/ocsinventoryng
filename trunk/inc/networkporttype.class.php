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

      if ($this->isNewItem() && isset($_GET['plugin_ocsinventoryng_networkports_id'])) {
         $network_port = new PluginOcsinventoryngNetworkPort();
         if ($network_port->getFromDB($_GET['plugin_ocsinventoryng_networkports_id'])) {
            $this->fields['OCS_TYPE'] = $network_port->fields['TYPE'];
            $this->fields['OCS_TYPEMIB'] = $network_port->fields['TYPEMIB'];
         }
      }

      return array(array('name'  => 'OCS_TYPE',
                         'label' => __('OCS TYPE', 'ocsinventoryng'),
                         'type'  => 'text'),
                   array('name'  => 'OCS_TYPEMIB',
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
   }


}
?>