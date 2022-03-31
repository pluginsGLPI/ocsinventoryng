<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2022 by the ocsinventoryng Development Team.

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
 * Class PluginOcsinventoryngOfficepack
 */
class PluginOcsinventoryngOfficepack extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Computer';
   static public $items_id = 'computers_id';

   static $rightname = "plugin_ocsinventoryng";

   /**
    *
    * Update config of a new software office
    *
    * This function create a officepack in GLPI with some general data.
    *
    * @param type $computers_id
    * @param      $softwares_id
    * @param      $softwares_name
    * @param      $softwareversions_id
    * @param      $entity
    * @param      $ocsOfficePacks
    * @param type $cfg_ocs
    *
    * @param      $imported_licences
    *
    * @internal param \type $ocsservers_id
    */
   static function updateOfficePack($computers_id, $softwares_id, $softwares_name, $softwareversions_id,
                                    $entity, $ocsOfficePacks, $cfg_ocs, &$imported_licences) {

      $install_plugins_history = 0;
      if ($cfg_ocs['dohistory'] == 1 && ($cfg_ocs['history_plugins'] == 1 || $cfg_ocs['history_plugins'] == 2)) {
         $install_plugins_history = 1;
      }

      foreach ($ocsOfficePacks as $ocsOfficePack) {
         $ocsOfficePack = Glpi\Toolbox\Sanitizer::sanitize($ocsOfficePack);

         if ($ocsOfficePack['PRODUCT'] == $softwares_name) {

            $soft_l['softwares_id']            = $softwares_id;
            $soft_l['softwareversions_id_use'] = $softwareversions_id;
            $soft_l['entities_id']             = $entity;
            $soft_l['name']                    = $ocsOfficePack['OFFICEKEY'];
            $soft_l['serial']                  = $ocsOfficePack['OFFICEKEY'];
            $soft_l['comment']                 = $ocsOfficePack['NOTE'];

            $id = array_search($softwareversions_id, $imported_licences);

            $software_licenses     = new SoftwareLicense();
            $item_softwarelicenses = new Item_SoftwareLicense();
            if ($id) {
               //-------------------------------------------------------------------------//
               //---- The software exists in this license for this computer --------------//
               //---------------------------- Update comments ----------------------------//
               //---------------------------------------------------- --------------------//
               if (!empty($ocsOfficePack['OFFICEKEY'])) {

                  if ($software_licenses->getFromDBByCrit(['softwares_id'            => $softwares_id,
                                                           'serial'                  => $ocsOfficePack['OFFICEKEY'],
                                                           'softwareversions_id_use' => $softwareversions_id])) {

                     $software_licenses->update(['id'      => $software_licenses->getID(),
                                                 'comment' => $ocsOfficePack['NOTE']]);
                     if (!$item_softwarelicenses->getFromDBByCrit(['items_id'            => $computers_id,
                                                                   'itemtype'            => 'Computer',
                                                                   'softwarelicenses_id' => $software_licenses->getID()])) {

                        $computer_soft_l['items_id']            = $computers_id;
                        $computer_soft_l['itemtype']            = 'Computer';
                        $computer_soft_l['softwarelicenses_id'] = $software_licenses->getID();
                        $computer_soft_l['is_dynamic']          = -1;
                        $item_softwarelicenses->add($computer_soft_l);
                        //Update for validity
                        $software_licenses->update(['id'       => $software_licenses->getID(),
                                                    'is_valid' => 1]);
                     }
                  }
               }

               unset($imported_licences[$id]);
            } else {
               //------------------------------------------------------------------------//
               //---- The software doesn't exists in this license for this computer -----//
               //------------------------------------------------------------------------//
               if (!empty($ocsOfficePack['OFFICEKEY'])) {
                  if ($software_licenses->getFromDBByCrit(['softwares_id'            => $softwares_id,
                                                           'serial'                  => $ocsOfficePack['OFFICEKEY'],
                                                           'softwareversions_id_use' => $softwareversions_id])) {
                     $id_software_licenses = $software_licenses->getID();
                  } else {
                     $software_licenses->fields['softwares_id'] = $softwares_id;
                     $id_software_licenses                      = $software_licenses->add($soft_l, [], $install_plugins_history);
                  }

                  if ($id_software_licenses) {
                     $computer_soft_l['items_id']            = $computers_id;
                     $computer_soft_l['itemtype']            = 'Computer';
                     $computer_soft_l['softwarelicenses_id'] = $id_software_licenses;
                     $computer_soft_l['is_dynamic']          = 1;
                     $computer_soft_l['number']              = -1;

                     if (!$item_softwarelicenses->getFromDBByCrit(['items_id'            => $computers_id,
                                                                   'itemtype'            => 'Computer',
                                                                   'softwarelicenses_id' => $id_software_licenses])) {
                        $item_softwarelicenses->add($computer_soft_l);
                     }
                     //Update for validity
                     $software_licenses->update(['id'       => $id_software_licenses,
                                                 'is_valid' => 1]);
                  }
               }
            }
         }
      }
   }

   /**
    * Delete old licenses software entries
    *
    * @param $glpi_computers_id integer : glpi computer id.
    * @param $uninstall_plugins_history boolean
    */
   static function resetOfficePack($glpi_computers_id, $uninstall_plugins_history) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_items_softwarelicenses`
                WHERE `items_id` = $glpi_computers_id 
                AND `itemtype` = 'Computer' 
                AND`is_dynamic` = 1";

      $result = $DB->query($query);

      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetchAssoc($result)) {
            $query2  = "SELECT COUNT(*)
                       FROM `glpi_items_softwarelicenses`
                       WHERE `softwarelicenses_id` = " . $data['softwarelicenses_id'];
            $result2 = $DB->query($query2);

            if ($DB->result($result2, 0, 0) == 1) {
               $license = new SoftwareLicense();
               $license->getFromDB($data['softwarelicenses_id']);
               $query3  = "SELECT COUNT(*)
                          FROM `glpi_softwarelicenses`
                          WHERE `softwares_id`=" . $license->fields['softwares_id'];
               $result3 = $DB->query($query3);

               if ($DB->result($result3, 0, 0) == 1) {
                  $soft = new Software();
                  $soft->delete(['id' => $license->fields['softwares_id']], 1, $uninstall_plugins_history);
               }
               $license->delete(["id" => $data['softwarelicenses_id']], 0, $uninstall_plugins_history);
            }
         }

         $item_softwarelicenses = new Item_SoftwareLicense();
         $item_softwarelicenses->deleteByCriteria(['items_id' => $glpi_computers_id,
                                                   'itemtype' => 'Computer'],
                                                  0, $uninstall_plugins_history);
      }
   }
}
