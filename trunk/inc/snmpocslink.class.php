<?php
/*
 * @version $Id: HEADER 15930 2012-12-15 11:10:55Z tsmr $
-------------------------------------------------------------------------
Ocsinventoryng plugin for GLPI
Copyright (C) 2012-2016 by the ocsinventoryng plugin Development Team.

https://forge.glpi-project.org/projects/ocsinventoryng
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

class PluginOcsinventoryngSnmpOcslink extends CommonDBTM {

   /**
    * if Printer purged
    *
    * @param $print   Printer object
   **/
   static function purgePrinter(Printer $print) {
      $snmp = new self();
      $snmp->deleteByCriteria(array('items_id' => $print->getField("id"),
                                     'itemtype' => $print->getType()));

   }
   
   /**
    * if Printer purged
    *
    * @param $per   Peripheral object
   **/
   static function purgePeripheral(Peripheral $per) {
      $snmp = new self();
      $snmp->deleteByCriteria(array('items_id' => $per->getField("id"),
                                     'itemtype' => $per->getType()));

   }
   
   /**
    * if NetworkEquipment purged
    *
    * @param $comp   NetworkEquipment object
   **/
   static function purgeNetworkEquipment(NetworkEquipment $net) {
      $snmp = new self();
      $snmp->deleteByCriteria(array('items_id' => $net->getField("id"),
                                     'itemtype' => $net->getType()));

   }
   
   /**
    * if Computer purged
    *
    * @param $comp   Computer object
   **/
   static function purgeComputer(Computer $comp) {
      $snmp = new self();
      $snmp->deleteByCriteria(array('items_id' => $comp->getField("id"),
                                     'itemtype' => $comp->getType()));

   }
}
?>