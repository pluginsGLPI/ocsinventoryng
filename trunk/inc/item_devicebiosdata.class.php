<?php
/*
 * @version $Id: item_devicegraphiccard.class.php 22656 2014-02-12 16:15:25Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/**
 * Relation between item and devices
**/
class PluginOcsinventoryngItem_DeviceBiosdata extends Item_Devices {

   static public $itemtype_2 = 'PluginOcsinventoryngDeviceBiosdata';
   static public $items_id_2 = 'plugin_ocsinventoryng_devicebiosdatas_id';

   static protected $notable = false;
   
   /**
    * @since version 0.85
   **/
   static function itemAffinity() {
      return array('Computer');
   }

}
?>
