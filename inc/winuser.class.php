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

class PluginOcsinventoryngWinuser extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Computer';
   static public $items_id = 'computers_id';

   static $rightname = "plugin_ocsinventoryng";

   static function getTypeName($nb=0) {
      return __('Windows Users', 'ocsinventoryng');
   }

   function cleanDBonPurge()
   {

      $self = new self();
      $self->deleteByCriteria(array('computers_id' => $this->fields['id']));

   }
   
   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      // can exists for template
      if (($item->getType() == 'Computer')
          && Computer::canView()) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = countElementsInTable('glpi_plugin_ocsinventoryng_winusers',
                                       "computers_id = '".$item->getID()."'");
         }
         return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }


   /**
    * @param $item            CommonGLPI object
    * @param $tabnum (default 1)
    * @param $withtemplate (default 0)
    *
    * @return bool|true
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      self::showForComputer($item, $withtemplate);
      return true;
   }

   /**
    * Print the computers windows update states
    *
    * @param $comp                  Computer object
    * @param bool|string $withtemplate boolean  Template or basic item (default '')
    * @return Nothing
    */
   static function showForComputer(Computer $comp, $withtemplate='') {
      global $DB;

      $ID = $comp->fields['id'];

      if (!$comp->getFromDB($ID)
          || !$comp->can($ID, READ)) {
         return false;
      }
      $canedit = $comp->canEdit($ID);

      echo "<div class='spaced center'>";

      if ($result = $DB->request('glpi_plugin_ocsinventoryng_winusers', array('computers_id' => $ID, 'ORDER' => 'type'))) {
         echo "<table class='tab_cadre_fixehov'>";
         $colspan = 5;
         echo "<tr class='noHover'><th colspan='$colspan'>".self::getTypeName($result->numrows()).
              "</th></tr>";

         if ($result->numrows() != 0) {

            $header = "<tr><th>".__('Name')."</th>";
            $header .= "<th>".__('Type')."</th>";
            $header .= "<th>".__('Description')."</th>";
            $header .= "<th>".__('Disabled', 'ocsinventoryng')."</th>";
            $header .= "<th>".__('SID', 'ocsinventoryng')."</th>";
            $header .= "</tr>";
            echo $header;

            Session::initNavigateListItems(__CLASS__,
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                           sprintf(__('%1$s = %2$s'),
                                                   Computer::getTypeName(1), $comp->getName()));

            foreach($result as $data) {
               echo "<tr class='tab_bg_2'>";
               echo "<td>".$data['name']."</td>";
               echo "<td>".$data['type']."</td>";
               echo "<td>".$data['description']."</td>";
               echo "<td>".$data['disabled']."</td>";
               echo "<td>".$data['sid']."</td>";
               echo "</tr>";
               Session::addToNavigateListItems(__CLASS__, $data['id']);
            }
            echo $header;
         } else {
            echo "<tr class='tab_bg_2'><th colspan='$colspan'>".__('No item found')."</th></tr>";
         }

         echo "</table>";
      }
      echo "</div>";
   }
}