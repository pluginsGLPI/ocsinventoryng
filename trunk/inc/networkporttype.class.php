<?php
/*
 * @version $Id: location.class.php 20129 2013-02-04 16:53:59Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief 
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Location class
class PluginOcsinventoryngNetworkPortType extends CommonDropdown {

   static function getTypeName($nb=0) {
      return _n('Network port type','Network port types',$nb);
   }


   function canUpdateItem() {
      if ((isset($this->fields['OCS_TYPE'])) && ($this->fields['OCS_TYPE'] == '*')
          && (isset($this->fields['OCS_TYPEMIB'])) && ($this->fields['OCS_TYPEMIB'] == '*')) {
         return false;
      }
      return $this->canCreateItem();
   }


   function canDeleteItem() {
      if ((isset($this->fields['OCS_TYPE'])) && ($this->fields['OCS_TYPE'] == '*')
          && (isset($this->fields['OCS_TYPEMIB'])) && ($this->fields['OCS_TYPEMIB'] == '*')) {
         return false;
      }
      return $this->canCreateItem();
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
                         'label' => __('OCS TYPE'),
                         'type'  => 'text'),
                   array('name'  => 'OCS_TYPEMIB',
                         'label' => __('OCS TYPE MIB'),
                         'type'  => 'text'),
                   array('name'  => 'instantiation_type',
                         'label' => __('Corresponding Network Port type'),
                         'type'  => 'instantiation_type'),
                   array('name'  => 'type',
                         'label' => __('Ethernet medium type'),
                         'type'  => 'type'),
                   array('name'  => 'speed',
                         'label' => __('Ethernet medium speed'),
                         'type'  => 'speed'),
                   array('name'  => 'version',
                         'label' => __('Wifi card Version'),
                         'type'  => 'version'));
   }


}
?>