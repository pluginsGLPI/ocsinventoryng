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
      $menu['page']            = PLUGIN_OCS_NOTFULL_WEBDIR . "/front/ocsng.php";
      $menu['links']['search'] = PLUGIN_OCS_NOTFULL_WEBDIR . "/front/ocsng.php";

      if (Session::haveRight(static::$rightname, UPDATE)
          || Session::haveRight("config", UPDATE)) {
         //Entry icon in breadcrumb
         $menu['links']['config'] = PluginOcsinventoryngConfig::getSearchURL(false);
         //Link to config page in admin plugins list
         $menu['config_page'] = PluginOcsinventoryngConfig::getSearchURL(false);
      }

      $usemassimport = PluginOcsinventoryngOcsServer::useMassImport();
      // Import
      if (Session::haveRight("plugin_ocsinventoryng_import", READ)) {
         $menu['links']["<i class='fas fa-plus fa-1x' title='" . __s('Import new computers', 'ocsinventoryng') . "'></i>"] = PLUGIN_OCS_NOTFULL_WEBDIR . '/front/ocsng.import.php';
      }
      // Sync
      if (Session::haveRight("plugin_ocsinventoryng_sync", READ)) {
         $menu['links']["<i class='fas fa-sync-alt fa-1x' title='" . __s('Synchronize computers already imported', 'ocsinventoryng') . "'></i>"] = PLUGIN_OCS_NOTFULL_WEBDIR . '/front/ocsng.sync.php';
      }
      // Thread
      if ($usemassimport && Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
         $menu['links']["<i class='fas fa-play fa-1x' title='" . __('Scripts execution of automatic actions', 'ocsinventoryng') . "'></i>"] = PLUGIN_OCS_NOTFULL_WEBDIR . '/front/thread.php';
      }
      // Detail
      $log = PluginOcsinventoryngConfig::logProcessedComputers();
      if ($log && Session::haveRight("plugin_ocsinventoryng_import", READ) && $usemassimport) {
         $menu['links']["<i class='fas fa-check fa-1x' title='" . __('Computers imported by automatic actions', 'ocsinventoryng') . "'></i>"] = PLUGIN_OCS_NOTFULL_WEBDIR . '/front/detail.php';
      }
      // Notimported
      if (Session::haveRight("plugin_ocsinventoryng", UPDATE) && $usemassimport) {
         $menu['links']["<i class='fas fa-times fa-1x' title='" . __s('Computers not imported by automatic actions', 'ocsinventoryng') . "'></i>"] = PLUGIN_OCS_NOTFULL_WEBDIR . '/front/notimportedcomputer.php';
         // networkport
         $menu['links']["<i class='fas fa-network-wired fa-1x' title='" . _n('Unknown imported network port type', 'Unknown imported network ports types', 2, 'ocsinventoryng') . "'></i>"] = PLUGIN_OCS_NOTFULL_WEBDIR . '/front/networkport.php';
      }
      if (Session::haveRight("plugin_ocsinventoryng_clean", UPDATE)
          || Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
         if (Session::haveRight("plugin_ocsinventoryng", UPDATE)) {
            // Deleted_equiv
            $menu['links']["<i class='fas fa-trash fa-1x' title='" . __s('Clean OCSNG deleted computers', 'ocsinventoryng') . "'></i>"] = PLUGIN_OCS_NOTFULL_WEBDIR . '/front/deleted_equiv.php';
         }
         // Clean
         $menu['links']["<i class='fas fa-broom fa-1x' title='" . __s('Clean links between GLPI and OCSNG', 'ocsinventoryng') . "'></i>"] = PLUGIN_OCS_NOTFULL_WEBDIR . '/front/ocsng.clean.php';
      }

      // Import
      //      $menu['options']['importsnmp']['title'] = __s('Import new snmp devices', 'ocsinventoryng');
      //      $menu['options']['importsnmp']['page']  = PLUGIN_OCS_NOTFULL_WEBDIR.'/front/ocsngsnmp.import.php';
      //
      //      // Sync
      //      $menu['options']['syncsnmp']['title'] = __s('Synchronize snmp devices already imported', 'ocsinventoryng');
      //      $menu['options']['syncsnmp']['page']  = PLUGIN_OCS_NOTFULL_WEBDIR.'/front/ocsngsnmp.sync.php';
      //
      //      // Link
      //      $menu['options']['synclink']['title'] = __s('Link SNMP devices to existing GLPI objects', 'ocsinventoryng');
      //      $menu['options']['synclink']['page']  = PLUGIN_OCS_NOTFULL_WEBDIR.'/front/ocsngsnmp.link.php';
      //
      //      //ipdiscover
      //      $menu['options']['importipdiscover']['title'] = __s('IPDiscover Import', 'ocsinventoryng');
      //      $menu['options']['importipdiscover']['page']  = PLUGIN_OCS_NOTFULL_WEBDIR.'/front/ipdiscover.php';
      //
      //      //Modify Network
      //      $menu['options']['modifysubnet']['title'] = __s('Modify Subnet', 'ocsinventoryng');
      //      $menu['options']['modifysubnet']['page']  = PLUGIN_OCS_NOTFULL_WEBDIR.'/front/ipdiscover.modifynetwork.php';

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
                     $snmp      = ($client->getIntConfig('SNMP') > 0) ? true : false;
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
               echo " : <a href='" . PLUGIN_OCS_WEBDIR . "/front/ocsserver.form.php'>";
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
