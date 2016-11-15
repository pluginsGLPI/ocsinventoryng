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

/// Class DeviceBios
/**
 * Class PluginOcsinventoryngDeviceBiosdata
 */
class PluginOcsinventoryngDeviceBiosdata extends CommonDevice
{

   static protected $forward_entity_to = array('PluginOcsinventoryngItem_DeviceBiosdata', 'Infocom');

   /**
    * @param int $nb
    * @return string|translated
    */
   static function getTypeName($nb = 0)
   {
      return __('Bios');
   }


   /**
    * @return array
    */
   function getAdditionalFields()
   {

      return array_merge(parent::getAdditionalFields(),
         array(array('name' => 'assettag',
            'label' => __('Asset Tag', 'ocsinventoryng'),
            'type' => 'text'),
            array('name' => 'date',
               'label' => __('Date'),
               'type' => 'text')));
   }

   /**
    * @since version 0.84
    *
    * @see CommonDevice::getHTMLTableHeader()
    * @param string $itemtype
    * @param HTMLTableBase $base
    * @param HTMLTableSuperHeader $super
    * @param HTMLTableHeader $father
    * @param array $options
    * @return HTMLTableHeader|nothing
    */
   static function getHTMLTableHeader($itemtype, HTMLTableBase $base,
                                      HTMLTableSuperHeader $super = NULL,
                                      HTMLTableHeader $father = NULL, array $options = array())
   {

      $column = parent::getHTMLTableHeader($itemtype, $base, $super, $father, $options);

      if ($column == $father) {
         return $father;
      }

      switch ($itemtype) {
         case 'Computer' :
            Manufacturer::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
            $base->addHeader('devicebiosdata_tag', __('Asset Tag', 'ocsinventoryng'), $super, $father);
            $base->addHeader('devicebiosdata_date', __('Date'), $super, $father);
            break;
      }

   }


   /**
    * @since version 0.84
    *
    * @see CommonDevice::getHTMLTableCellForItem()
    * @param HTMLTableRow $row
    * @param CommonDBTM $item
    * @param HTMLTableCell $father
    * @param array $options
    * @return HTMLTableCell
    */
   function getHTMLTableCellForItem(HTMLTableRow $row = NULL, CommonDBTM $item = NULL,
                                    HTMLTableCell $father = NULL, array $options = array())
   {

      $column = parent::getHTMLTableCellForItem($row, $item, $father, $options);

      if ($column == $father) {
         return $father;
      }

      switch ($item->getType()) {
         case 'Computer' :
            Manufacturer::getHTMLTableCellsForItem($row, $this, NULL, $options);
            if ($this->fields["assettag"]) {
               $row->addCell($row->getHeaderByName('devicebiosdata_tag'),
                  Dropdown::getYesNo($this->fields["assettag"]), $father);
            }

            if ($this->fields["date"]) {
               $row->addCell($row->getHeaderByName('devicebiosdata_date'),
                  $this->fields["date"], $father);
            }
      }
   }
}