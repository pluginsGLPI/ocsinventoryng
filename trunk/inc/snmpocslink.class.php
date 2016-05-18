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
   
   /**
   * Show simple inventory information of an item
   *
   * @param $item                   CommonDBTM object
   *
   * @return nothing
   **/
   static function showSimpleForItem(CommonDBTM $item) {
      global $DB, $CFG_GLPI;

      $target = Toolbox::getItemTypeFormURL(__CLASS__);

      if (in_array($item->getType(), array('Computer', 'Printer', 'NetworkEquipment', 'Peripheral'))) {
         $items_id = $item->getField('id');

         if (!empty($items_id)
             && $item->fields["is_dynamic"]
             && Session::haveRight("plugin_ocsinventoryng_view", READ)) {
            $query = "SELECT *
                      FROM `glpi_plugin_ocsinventoryng_snmpocslinks`
                      WHERE `items_id` = '".$items_id."' AND  `itemtype` = '".$item->getType()."'";

            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               $data = $DB->fetch_assoc($result);

               if (count($data)) {
                  $ocs_config = PluginOcsinventoryngOcsServer::getConfig($data['plugin_ocsinventoryng_ocsservers_id']);
                  echo "<table class='tab_glpi'>";
                  echo "<tr class='tab_bg_1'><th colspan='2'>".__('SNMP informations OCS NG')."</th>";
                  
                  echo "<tr class='tab_bg_1'><td>".__('Import date in GLPI', 'ocsinventoryng');
                  echo "</td><td>".Html::convDateTime($data["last_update"]).'</td></tr>';
                  
                  $linked_ids [] = $data['ocs_id'];
                  $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($data['plugin_ocsinventoryng_ocsservers_id']);
                  $ocsResult = $ocsClient->getSnmp(array(
                     'MAX_RECORDS' => 1,
                     'FILTER'      => array(
                        'IDS' => $linked_ids,
                     )
                  ));
                  if (isset($ocsResult['SNMP'])) {
                     if (count($ocsResult['SNMP']) > 0) {
                        foreach ($ocsResult['SNMP'] as $snmp) {
                           $LASTDATE   = $snmp['META']['LASTDATE'];
                           $UPTIME     = $snmp['META']['UPTIME'];
                  
                           echo "<tr class='tab_bg_1'><td>".__('Last OCSNG SNMP inventory date', 'ocsinventoryng');
                           echo "</td><td>".Html::convDateTime($LASTDATE).'</td></tr>';
                           
                           echo "<tr class='tab_bg_1'><td>".__('Uptime', 'ocsinventoryng');
                           echo "</td><td>".$UPTIME.'</td></tr>';
                        }
                     }
                  }
                  
                  
                  
                  if (Session::haveRight("plugin_ocsinventoryng_sync", UPDATE)) {
                     echo "<tr class='tab_bg_1'><td class='center' colspan='2'>";
                     Html::showSimpleForm($target, 'force_ocssnmp_resynch',
                                          _sx('button', 'Force SNMP synchronization', 'ocsinventoryng'),
                                          array('items_id' => $items_id,
                                                 'itemtype' => $item->getType(),
                                                 'id' => $data["id"],
                                                  'plugin_ocsinventoryng_ocsservers_id' => $data["plugin_ocsinventoryng_ocsservers_id"]));
                     echo "</td></tr>";
                     
                  }
                  echo '</table>';
               }
            }
         }
      }
   }
}
?>