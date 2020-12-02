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

define("PLUGIN_OCSINVENTORYNG_STATE_STARTED", 1);
define("PLUGIN_OCSINVENTORYNG_STATE_RUNNING", 2);
define("PLUGIN_OCSINVENTORYNG_STATE_FINISHED", 3);

define("PLUGIN_OCSINVENTORYNG_LOCKFILE", GLPI_LOCK_DIR . "/ocsinventoryng.lock");
define('PLUGIN_OCS_VERSION', '1.7.3');

/**
 * Init the hooks of the plugins -Needed
 **/
function plugin_init_ocsinventoryng() {
   global $PLUGIN_HOOKS, $CFG_GLPI, $DB;

   $PLUGIN_HOOKS['csrf_compliant']['ocsinventoryng'] = true;
   $PLUGIN_HOOKS['use_rules']['ocsinventoryng']      = ['RuleImportEntity', 'RuleImportComputer'];

   $PLUGIN_HOOKS['change_profile']['ocsinventoryng'] = ['PluginOcsinventoryngProfile',
                                                        'initProfile'];

   $PLUGIN_HOOKS['import_item']['ocsinventoryng'] = ['Computer'];

   $PLUGIN_HOOKS['autoinventory_information']['ocsinventoryng']
      = ['Computer'               => ['PluginOcsinventoryngOcslink', 'showSimpleForItem'],
         'ComputerDisk'           => ['PluginOcsinventoryngOcslink', 'showSimpleForChild'],
         'ComputerVirtualMachine' => ['PluginOcsinventoryngOcslink', 'showSimpleForChild'],
         'Printer'                => ['PluginOcsinventoryngSnmpOcslink', 'showSimpleForItem'],
         'NetworkEquipment'       => ['PluginOcsinventoryngSnmpOcslink', 'showSimpleForItem'],
         'Peripheral'             => ['PluginOcsinventoryngSnmpOcslink', 'showSimpleForItem'],
         'Phone'                  => ['PluginOcsinventoryngSnmpOcslink', 'showSimpleForItem']];

   //Locks management
   $PLUGIN_HOOKS['display_locked_fields']['ocsinventoryng'] = 'plugin_ocsinventoryng_showLocksForItem';
   $PLUGIN_HOOKS['unlock_fields']['ocsinventoryng']         = 'plugin_ocsinventoryng_unlockFields';

   Plugin::registerClass('PluginOcsinventoryngOcslink',
                         ['forwardentityfrom' => 'Computer',
                          'addtabon'          => 'Computer']);

   //plugins
   Plugin::registerClass('PluginOcsinventoryngRegistryKey',
                         ['addtabon' => 'Computer']);

   if ($DB->tableExists('glpi_plugin_ocsinventoryng_winupdates')) {
      Plugin::registerClass('PluginOcsinventoryngWinupdate',
                            ['addtabon' => 'Computer']);
   }
   if ($DB->tableExists('glpi_plugin_ocsinventoryng_proxysettings')) {
      Plugin::registerClass('PluginOcsinventoryngProxysetting',
                            ['addtabon' => 'Computer']);
   }

   if ($DB->tableExists('glpi_plugin_ocsinventoryng_winusers')) {
      Plugin::registerClass('PluginOcsinventoryngWinuser',
                            ['addtabon' => 'Computer']);
   }
   if ($DB->tableExists('glpi_plugin_ocsinventoryng_customapps')) {
      Plugin::registerClass('PluginOcsinventoryngCustomapp',
                            ['addtabon' => 'Computer']);
   }

   if ($DB->tableExists('glpi_plugin_ocsinventoryng_networkshares')) {
      Plugin::registerClass('PluginOcsinventoryngNetworkshare',
                            ['addtabon' => 'Computer']);
   }

   if ($DB->tableExists('glpi_plugin_ocsinventoryng_runningprocesses')) {
      Plugin::registerClass('PluginOcsinventoryngRunningprocess',
                            ['addtabon' => 'Computer']);
   }

   if ($DB->tableExists('glpi_plugin_ocsinventoryng_services')) {
      Plugin::registerClass('PluginOcsinventoryngService',
                            ['addtabon' => 'Computer']);
   }

   if ($DB->tableExists('glpi_plugin_ocsinventoryng_teamviewers')) {
      Plugin::registerClass('PluginOcsinventoryngTeamviewer',
                            ['addtabon'   => 'Computer',
                             'link_types' => true]);

      if (class_exists('PluginOcsinventoryngTeamviewer')) {
         Link::registerTag(PluginOcsinventoryngTeamviewer::$tags);
      }
   }

   if ($DB->tableExists('glpi_plugin_ocsinventoryng_osinstalls')) {
      $PLUGIN_HOOKS['post_item_form']['ocsinventoryng'] = ['PluginOcsinventoryngOsinstall',
                                                           'showForItem_OperatingSystem'];
   }

   Plugin::registerClass('PluginOcsinventoryngOcsServer',
                         ['massiveaction_noupdate_types' => true,
                          'systeminformations_types'     => true]);

   Plugin::registerClass('PluginOcsinventoryngProfile',
                         ['addtabon' => 'Profile']);

   Plugin::registerClass('PluginOcsinventoryngNotimportedcomputer',
                         ['massiveaction_noupdate_types' => true,
                          'massiveaction_nodelete_types' => true,
                          'notificationtemplates_types'  => true]);

   Plugin::registerClass('PluginOcsinventoryngRuleImportEntity',
                         ['massiveaction_noupdate_types' => true,
                          'massiveaction_nodelete_types' => true,
                          'notificationtemplates_types'  => true]);

   Plugin::registerClass('PluginOcsinventoryngDetail',
                         ['massiveaction_noupdate_types' => true,
                          'massiveaction_nodelete_types' => true]);

   Plugin::registerClass('PluginOcsinventoryngNetworkPort',
                         ['networkport_instantiations' => true]);

   Plugin::registerClass('PluginOcsinventoryngSnmpOcslink',
                         ['addtabon' => ['Computer', 'Printer', 'NetworkEquipment', 'Peripheral', 'Phone']]);

   Plugin::registerClass('PluginOcsinventoryngOcsAlert',
                         ['addtabon'                    => ['Entity', 'CronTask'],
                          'notificationtemplates_types' => true]);

   // transfer
   $PLUGIN_HOOKS['item_transfer']['ocsinventoryng'] = "plugin_ocsinventoryng_item_transfer";

   // Css file
   $PLUGIN_HOOKS['add_css']['ocsinventoryng'] = 'css/ocsinventoryng.css';

   if (Session::getLoginUserID()) {
      $ocsserver = new PluginOcsinventoryngOcsServer();
      // Display a menu entry ?
      if (Session::haveRight("plugin_ocsinventoryng", READ)) {
         $PLUGIN_HOOKS['menu_toadd']['ocsinventoryng'] = ['tools' => 'PluginOcsinventoryngMenu'];
      }

      if (Session::haveRight("plugin_ocsinventoryng", UPDATE)
          || Session::haveRight("config", UPDATE)) {
         $PLUGIN_HOOKS['use_massive_action']['ocsinventoryng'] = 1;
         //$PLUGIN_HOOKS['redirect_page']['ocsinventoryng']      = "front/ocsng.php";
         $PLUGIN_HOOKS['redirect_page']['ocsinventoryng'] = "front/notimportedcomputer.form.php";

         //TODO Change for menu
         $PLUGIN_HOOKS['config_page']['ocsinventoryng'] = 'front/config.php';
      }

      $PLUGIN_HOOKS['post_init']['ocsinventoryng'] = 'plugin_ocsinventoryng_postinit';

      if (class_exists('PluginMydashboardMenu')) {
         $PLUGIN_HOOKS['mydashboard']['ocsinventoryng'] = ["PluginOcsinventoryngDashboard"];
      }

      if($ocsserver->getField('import_bitlocker')){
         $PLUGIN_HOOKS['post_item_form']['ocsinventoryng'] = [PluginOcsinventoryngBitlockerstatus::class,'showForDisk'];
      }
   }

   $CFG_GLPI['ocsinventoryng_devices_index'] = [1  => 'Item_DeviceMotherboard',
                                                2  => 'Item_DeviceProcessor',
                                                3  => 'Item_DeviceMemory',
                                                4  => 'Item_DeviceHardDrive',
                                                5  => 'Item_DeviceNetworkCard',
                                                6  => 'Item_DeviceDrive',
                                                7  => 'Item_DeviceControl',
                                                8  => 'Item_DeviceGraphicCard',
                                                9  => 'Item_DeviceSoundCard',
                                                10 => 'Item_DevicePci',
                                                11 => 'Item_DeviceCase',
                                                12 => 'Item_DevicePowerSupply',
                                                13 => 'Item_DeviceFirmware'];
}


/**
 * Get the name and the version of the plugin - Needed
 **/
function plugin_version_ocsinventoryng() {

   return ['name'         => "OCS Inventory NG",
           'version'      => PLUGIN_OCS_VERSION,
           'author'       => 'Gilles Dubois, Remi Collet, Nelly Mahu-Lasson, David Durieux, Xavier Caillaud, Walid Nouh, Arthur Jaouen',
           'license'      => 'GPLv2+',
           'homepage'     => 'https://github.com/pluginsGLPI/ocsinventoryng',
           'requirements' => [
              'glpi' => [
                 'min' => '9.5',
                 'dev' => false
              ]
           ]
   ];

}


/**
 * Optional : check prerequisites before install : may print errors or add to message after redirect
 **/
function plugin_ocsinventoryng_check_prerequisites() {

   if (version_compare(GLPI_VERSION, '9.5', 'lt')
       || version_compare(GLPI_VERSION, '9.6', 'ge')) {
      if (method_exists('Plugin', 'messageIncompatible')) {
         echo Plugin::messageIncompatible('core', '9.5');
      }
      return false;
   }
   return true;
}


// Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
/**
 * @return bool
 */
function plugin_ocsinventoryng_check_config() {
   return true;
}
