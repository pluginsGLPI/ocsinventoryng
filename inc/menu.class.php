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

/**
 * Class PluginOcsinventoryngMenu
 */
class PluginOcsinventoryngMenu extends CommonGLPI {

   /**
    * @var string
    */
   static $rightname = 'plugin_ocsinventoryng';

   /**
    * @return string
    */
   static function getMenuName() {
      return 'OCS Inventory NG';
   }

   /**
    * @return array
    */
   static function getMenuContent() {

      $menu                    = [];
      $menu['title']           = self::getMenuName();
      $menu['page']            = "/plugins/ocsinventoryng/front/ocsng.php";
      $menu['links']['search'] = "/plugins/ocsinventoryng/front/ocsng.php";

      if (Session::haveRight(static::$rightname, UPDATE)
          || Session::haveRight("config", UPDATE)) {
         //Entry icon in breadcrumb
         $menu['links']['config'] = PluginOcsinventoryngConfig::getSearchURL(false);
         //Link to config page in admin plugins list
         $menu['config_page'] = PluginOcsinventoryngConfig::getSearchURL(false);
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

      // networkport
      $menu['options']['networkport']['title'] = _n('Unknown imported network port type', 'Unknown imported network ports types', 2, 'ocsinventoryng');
      $menu['options']['networkport']['page']  = '/plugins/ocsinventoryng/front/networkport.php';

      if (Session::haveRight("plugin_ocsinventoryng_clean", UPDATE)
          || Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
         if (Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
            // Deleted_equiv
            $menu['options']['deleted_equiv']['title'] = __s('Clean OCSNG deleted computers', 'ocsinventoryng');
            $menu['options']['deleted_equiv']['page']  = '/plugins/ocsinventoryng/front/deleted_equiv.php';
         }
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
      $menu['options']['importipdiscover']['title'] = __s('IPDiscover Import', 'ocsinventoryng');
      $menu['options']['importipdiscover']['page']  = '/plugins/ocsinventoryng/front/ipdiscover.php';

      //Modify Network
      $menu['options']['modifysubnet']['title'] = __s('Modify Subnet', 'ocsinventoryng');
      $menu['options']['modifysubnet']['page']  = '/plugins/ocsinventoryng/front/ipdiscover.modifynetwork.php';

      $menu['icon'] = self::getIcon();

      return $menu;
   }

   static function getIcon() {
      return "fas fa-download";
   }

   /**
    *
    */
   static function removeRightsFromSession() {
      if (isset($_SESSION['glpimenu']['tools']['types']['PluginOcsinventoryngMenu'])) {
         unset($_SESSION['glpimenu']['tools']['types']['PluginOcsinventoryngMenu']);
      }
      if (isset($_SESSION['glpimenu']['tools']['content']['pluginocsinventoryngmenu'])) {
         unset($_SESSION['glpimenu']['tools']['content']['pluginocsinventoryngmenu']);
      }
   }

   /**
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return array|string
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      global $CFG_GLPI;

      switch ($item->getType()) {
         case __CLASS__ :
            $dbu        = new DbUtils();
            $ocsServers = $dbu->getAllDataFromTable('glpi_plugin_ocsinventoryng_ocsservers',
                                                    ["is_active" => 1]);
            if (!empty($ocsServers)) {

               $ong[0] = __('Server Setup', 'ocsinventoryng');

               $ong[1] = __('Inventory Import', 'ocsinventoryng');

               if (isset($_SESSION["plugin_ocsinventoryng_ocsservers_id"])
                   && $_SESSION["plugin_ocsinventoryng_ocsservers_id"] > 0) {
                  if (PluginOcsinventoryngOcsServer::checkOCSconnection($_SESSION["plugin_ocsinventoryng_ocsservers_id"])) {
                     $ocsClient  = new PluginOcsinventoryngOcsServer();
                     $client     = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
                     $ipdiscover = $client->getIntConfig('IPDISCOVER');
                     if ($ipdiscover) {
                        $ong[2] = __('IPDiscover Import', 'ocsinventoryng');
                     }
                  }
               }


               if (isset($_SESSION["plugin_ocsinventoryng_ocsservers_id"])
                   && $_SESSION["plugin_ocsinventoryng_ocsservers_id"] > 0) {
                  if (PluginOcsinventoryngOcsServer::checkOCSconnection($_SESSION["plugin_ocsinventoryng_ocsservers_id"])) {

                     $ocsClient = new PluginOcsinventoryngOcsServer();
                     $client    = $ocsClient->getDBocs($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
                     $version   = $client->getTextConfig('GUI_VERSION');
                     $snmp    = ($client->getIntConfig('SNMP') > 0)?true:false;
                     if ($version > $ocsClient::OCS2_1_VERSION_LIMIT && $snmp) {
                        $ong[3] = __('SNMP Import', 'ocsinventoryng');
                     }
                  }
               }
            } else {
               $ong = [];
               echo "<div align='center'>";
               echo "<i class='fas fa-exclamation-triangle fa-4x' style='color:orange'></i>";
               echo "<br>";
               echo "<div class='red b'>";
               echo __('No OCSNG server defined', 'ocsinventoryng');
               echo "<br>";
               echo __('You must to configure a OCSNG server', 'ocsinventoryng');
               echo " : <a href='" . $CFG_GLPI["root_doc"] . "/plugins/ocsinventoryng/front/ocsserver.form.php'>";
               echo __('Add a OCSNG server', 'ocsinventoryng');
               echo "</a>";
               echo "</div></div>";
            }
            return $ong;

         default :
            return '';
      }
   }

   /**
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 0, $withtemplate = 0) {

      if ($item->getType() == __CLASS__) {
         $ocs    = new PluginOcsinventoryngOcsServer();
         $ipdisc = new PluginOcsinventoryngIpdiscoverOcslink();
         $snmp   = new PluginOcsinventoryngSnmpOcslink();
         switch ($tabnum) {
            case 0 :
               $ocs->setupMenu($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
               break;

            case 1 :
               $ocs->importMenu($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
               break;

            case 2 :
               $ipdisc->ipDiscoverMenu();
               break;

            case 3 :
               $snmp->snmpMenu($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
               break;

         }
      }
      return true;
   }

   /**
    * @param array $options
    *
    * @return array
    */
   function defineTabs($options = []) {

      $ong = [];

      $this->addStandardTab(__CLASS__, $ong, $options);

      return $ong;
   }
}
