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
 *  OCS Administration Information management class
 */
class PluginOcsinventoryngOcsAdminInfosLink extends CommonDBTM
{

   /**
    * @param $ID
    */
   function cleanForOcsServer($ID)
   {

      $temp = new self();
      $temp->deleteByCriteria(array('plugin_ocsinventoryng_ocsservers_id' => $ID));

   }

   /**
    * @param $plugin_ocsinventoryng_ocsservers_id
    * @param $glpi_column
    * @return true
    */
   function getFromDBbyOcsServerIDAndGlpiColumn($plugin_ocsinventoryng_ocsservers_id, $glpi_column)
   {

      return $this->getFromDBByQuery("WHERE `" . $this->getTable() . "`.`plugin_ocsinventoryng_ocsservers_id` = '$plugin_ocsinventoryng_ocsservers_id' AND `" . $this->getTable() . "`.`glpi_column` = '$glpi_column'");

   }

   /**
    * @param $computers_id
    * @param $date
    * @param $computer_updates
    * @return array
    */
   static function addInfocomsForComputer($computers_id, $date, $computer_updates)
   {
      global $DB;

      $infocom = new Infocom();
      $use_date = substr($date, 0, 10);
      if ($infocom->getFromDBByQuery("WHERE `items_id` = $computers_id AND `itemtype` = 'Computer'")) {
         if (empty($infocom->fields['use_date'])
            || $infocom->fields['use_date'] == 'NULL'
         ) {
            //add use_date
            $infocom->update(array('id' => $infocom->fields['id'], 'use_date' => $use_date));
         }
      } else {
         //add infocom
         $infocom->add(array('items_id' => $computers_id, 'itemtype' => 'Computer', 'use_date' => $use_date));

      }

      //Add lock
      $ocslink = new PluginOcsinventoryngOcslink();
      if ($ocslink->getFromDBforComputer($computers_id)) {
         $cfg_ocs = self::getConfig($ocslink->fields["plugin_ocsinventoryng_ocsservers_id"]);
         if ($cfg_ocs["use_locks"]) {
            $computer_updates[] = "use_date";
            $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                         SET `computer_update` = '" . addslashes(exportArrayToDB($computer_updates)) . "'
                         WHERE `computers_id` = '$computers_id'";
            $DB->query($query);
         }
      }
      return $computer_updates;

   }
}