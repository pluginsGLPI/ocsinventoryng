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

class PluginOcsinventoryngMenu extends CommonGLPI {

   static $rightname = 'plugin_ocsinventoryng';

   static function getMenuName() {
      return 'OCS Inventory NG';
   }

   static function getMenuContent() {
      global $CFG_GLPI;

      $menu                    = array();
      $menu['title']           = self::getMenuName();
      $menu['page']            = "/plugins/ocsinventoryng/front/ocsng.php";
      $menu['links']['search'] = "/plugins/ocsinventoryng/front/ocsng.php";

      if (Session::haveRight(static::$rightname, UPDATE) || Session::haveRight("config", UPDATE)) {
         //Entry icon in breadcrumb
         $menu['links']['config'] = PluginOcsinventoryngConfig::getSearchURL(false);
         //Link to config page in admin plugins list
         $menu['config_page']     = PluginOcsinventoryngConfig::getSearchURL(false);
      }

      // Ocsserver
      $menu['options']['ocsserver']['title']           = __s("Configuration of OCSNG server", 'ocsinventoryng');
      $menu['options']['ocsserver']['page']            = '/plugins/ocsinventoryng/front/ocsserver.php';
      $menu['options']['ocsserver']['links']['add']    = '/plugins/ocsinventoryng/front/ocsserver.form.php';
      $menu['options']['ocsserver']['links']['search'] = '/plugins/ocsinventoryng/front/ocsserver.php';


      // Import
      $menu['options']['import']['title'] = __s('Import new computers', 'ocsinventoryng');
      $menu['options']['import']['page']  = '/plugins/ocsinventoryng/front/ocsng.import.php';

      // Sync
      $menu['options']['sync']['title'] = __s('Synchronize computers already imported', 'ocsinventoryng');
      $menu['options']['sync']['page']  = '/plugins/ocsinventoryng/front/ocsng.sync.php';

      // Link
      $menu['options']['link']['title'] = __s('Link new OCSNG computers to existing GLPI computers', 'ocsinventoryng');
      $menu['options']['link']['page']  = '/plugins/ocsinventoryng/front/ocsng.link.php';

      // Thread
      $menu['options']['thread']['title'] = __s('Scripts execution of automatic actions', 'ocsinventoryng');
      $menu['options']['thread']['page']  = '/plugins/ocsinventoryng/front/thread.php';

      // Detail
      $menu['options']['detail']['title'] = __('Computers imported by automatic actions', 'ocsinventoryng');
      $menu['options']['detail']['page']  = '/plugins/ocsinventoryng/front/detail.php';

      // Notimported
      $menu['options']['notimported']['title'] = __s('Computers not imported by automatic actions', 'ocsinventoryng');
      $menu['options']['notimported']['page']  = '/plugins/ocsinventoryng/front/notimportedcomputer.php';
      
      if (Session::haveRight("plugin_ocsinventoryng_clean", READ)) {
         // Deleted_equiv
         $menu['options']['deleted_equiv']['title'] = __s('Clean OCSNG deleted computers', 'ocsinventoryng');
         $menu['options']['deleted_equiv']['page']  = '/plugins/ocsinventoryng/front/deleted_equiv.php';
         
         // Clean
         $menu['options']['clean']['title'] = __s('Clean links between GLPI and OCSNG', 'ocsinventoryng');
         $menu['options']['clean']['page']  = '/plugins/ocsinventoryng/front/ocsng.clean.php';
      }
      
      // Import
      $menu['options']['importsnmp']['title'] = __s('Import new snmp devices', 'ocsinventoryng');
      $menu['options']['importsnmp']['page']  = '/plugins/ocsinventoryng/front/ocsngsnmp.import.php';

      // Sync
      $menu['options']['syncsnmp']['title'] = __s('Synchronize snmp devices already imported', 'ocsinventoryng');
      $menu['options']['syncsnmp']['page']  = '/plugins/ocsinventoryng/front/ocsngsnmp.sync.php';
      
      // Link
      $menu['options']['synclink']['title'] = __s('Link SNMP devices to existing GLPI objects', 'ocsinventoryng');
      $menu['options']['synclink']['page']  = '/plugins/ocsinventoryng/front/ocsngsnmp.link.php';
      
      //ipdiscover
      $menu['options']['ipdiscover']['title'] = __s('IPDiscover', 'ocsinventoryng');
      $menu['options']['ipdiscover']['page']  = '/plugins/ocsinventoryng/front/ipdiscover.php';
      
      //Modify Network
      $menu['options']['ipdiscmodifynetwork']['title'] = __s('Modifiy Network', 'ocsinventoryng');
      $menu['options']['ipdiscmodifynetwork']['page']  = '/plugins/ocsinventoryng/front/ipdiscover.modifynetwork.php';
      
      //inventoried computers
      $menu['options']['ipdiscoverimport']['title'] = __s('Inventoried Computers', 'ocsinventoryng');
      $menu['options']['ipdiscoverimport']['page']  = '/plugins/ocsinventoryng/front/ipdiscover.import.php';
      
    
      
      return $menu;
   }

   static function removeRightsFromSession() {
      if (isset($_SESSION['glpimenu']['tools']['types']['PluginOcsinventoryngMenu'])) {
         unset($_SESSION['glpimenu']['tools']['types']['PluginOcsinventoryngMenu']);
      }
      if (isset($_SESSION['glpimenu']['tools']['content']['pluginocsinventoryngmenu'])) {
         unset($_SESSION['glpimenu']['tools']['content']['pluginocsinventoryngmenu']);
      }
   }

}
