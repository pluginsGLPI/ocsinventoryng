<?php
/*
 * @version $Id: HEADER 2011-03-12 18:01:26 tsmr $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
// ----------------------------------------------------------------------
// Original Author of file: CAILLAUD Xavier
// Purpose of file: plugin ocsinventoryng v 1.0.0 - GLPI 0.83
// ----------------------------------------------------------------------
 */

define("PLUGIN_OCSINVENTORYNG_STATE_STARTED", 1);
define("PLUGIN_OCSINVENTORYNG_STATE_RUNNING", 2);
define("PLUGIN_OCSINVENTORYNG_STATE_FINISHED", 3);

define("PLUGIN_OCSINVENTORYNG_LOCKFILE", GLPI_LOCK_DIR . "/ocsinventoryng.lock");

/**
 * Init the hooks of the plugins -Needed
**/
function plugin_init_ocsinventoryng() {
   global $PLUGIN_HOOKS, $CFG_GLPI;

   $PLUGIN_HOOKS['csrf_compliant']['ocsinventoryng'] = true;
   $PLUGIN_HOOKS['use_rules']['ocsinventoryng']      = true;

   $PLUGIN_HOOKS['change_profile']['ocsinventoryng'] = array('PluginOcsinventoryngProfile',
                                                             'changeProfile');

   $PLUGIN_HOOKS['import_item']['ocsinventoryng'] = array('Computer');

   Plugin::registerClass('PluginOcsinventoryngOcslink',
                         array('forwardentityfrom' => 'Computer',
                               'addtabon'          => 'Computer'));

   Plugin::registerClass('PluginOcsinventoryngRegistryKey',
                         array('addtabon'          => 'Computer'));

   Plugin::registerClass('PluginOcsinventoryngOcsServer',
                         array('massiveaction_noupdate_types' => true,
                               'systeminformations_types'     => true));

   Plugin::registerClass('PluginOcsinventoryngRuleImportComputerCollection',
                         array('rulecollections_types' => true));

   Plugin::registerClass('PluginOcsinventoryngProfile',
                         array('addtabon' => 'Profile'));

   Plugin::registerClass('PluginOcsinventoryngNotimported',
                         array ('massiveaction_noupdate_types' => true,
                                'massiveaction_nodelete_types' => true,
                                'notificationtemplates_types'  => true));

   Plugin::registerClass('PluginOcsinventoryngDetail',
                         array ('massiveaction_noupdate_types' => true,
                                'massiveaction_nodelete_types' => true));
                         
   if (Session::getLoginUserID()) {
      
      // Display a menu entry ?
      if (plugin_ocsinventoryng_haveRight("ocsng","r")) {
         $PLUGIN_HOOKS['menu_entry']['ocsinventoryng']               = 'front/ocsng.php';
         $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['search']  = 'front/ocsng.php';
      }

      if (plugin_ocsinventoryng_haveRight("ocsng","w") || Session::haveRight("config","w")) {
         $PLUGIN_HOOKS['use_massive_action']['ocsinventoryng'] = 1;
         $PLUGIN_HOOKS['redirect_page']['ocsinventoryng']      = "front/notimported.form.php";
      }

      if (plugin_ocsinventoryng_haveRight("ocsng", "w") || Session::haveRight("config", "w")) {
         $PLUGIN_HOOKS['config_page']['ocsinventoryng']              = 'front/config.php';
         $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['config']  = 'front/config.php';
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['ocsserver']['title']
               = __s("OCSNG server 's configuration");
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['ocsserver']['page']
               = '/plugins/ocsinventoryng/front/ocsserver.php';
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['config']['title']
               = __s("Automatic synchronization's configuration");
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['config']['page']
               = '/plugins/ocsinventoryng/front/config.form.php';
               
         if ($_SERVER['PHP_SELF']
                  == $CFG_GLPI["root_doc"]."/plugins/ocsinventoryng/front/ocsserver.php"
             || $_SERVER['PHP_SELF']
                  == $CFG_GLPI["root_doc"]."/plugins/ocsinventoryng/front/ocsserver.form.php") {
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['search']  = 'front/ocsserver.php';
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['add']     = 'front/ocsserver.form.php';
         }

         
         if (plugin_ocsinventoryng_haveRight("ocsng", "w")) {
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['import']['title']
               = __s('Import new computers');
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['import']['page']
               = '/plugins/ocsinventoryng/front/ocsng.import.php';
   
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['sync']['title']
               = __s('Synchronize computers already imported');
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['sync']['page']
               = '/plugins/ocsinventoryng/front/ocsng.sync.php';
   
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['link']['title']
               = __s('Link new OCSNG computers to existing GLPI computers');
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['link']['page']
               = '/plugins/ocsinventoryng/front/ocsng.link.php';

            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['thread']['title']
               = __s('Scripts execution of automatic task');
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['thread']['page']
               = '/plugins/ocsinventoryng/front/thread.php';
               
            if (plugin_ocsinventoryng_haveRight('clean_ocsng','r')) {
               $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['clean']['title']
                  = __s('Clean links between GLPI and OCSNG');
               $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['options']['clean']['page']
                  = '/plugins/ocsinventoryng/front/ocsng.clean.php';
            }
         }
         
        /*if (Session::haveRecursiveAccessToEntity(0)) {
         $image = "<img src='".$CFG_GLPI["root_doc"]."/pics/stats_item.png' title='".
                   $LANG["plugin_ocsinventoryng"]["common"][1]."' alt='".$LANG["plugin_ocsinventoryng"]["common"][1]."'>";
            $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng'][$image] = 'front/thread.php';
         }
         $image = "<img src='".$CFG_GLPI["root_doc"]."/pics/rdv.png' title='".
                   $LANG["plugin_ocsinventoryng"]["common"][21]."' alt='".$LANG["plugin_ocsinventoryng"]["common"][21]."'>";
         $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng'][$image]
                      = 'front/detail.php';
         $image = "<img src='".$CFG_GLPI["root_doc"]."/pics/puce-delete2.png' title='".
                   $LANG["plugin_ocsinventoryng"]["common"][18]."' alt='".$LANG["plugin_ocsinventoryng"]["common"][18]."'>";
         $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng'][$image]
                      = 'front/notimported.php';


         if (haveRight("logs", "r")) {
            //TODO
            if (Session::haveRecursiveAccessToEntity(0)) {
               $PLUGIN_HOOKS['submenu_entry']['ocsinventoryng']['config']
                            = 'front/config.form.php';
            }
         }*/
         $PLUGIN_HOOKS['post_init']['ocsinventoryng'] = 'plugin_ocsinventoryng_postinit';
      }
   }
}


/**
 * Get the name and the version of the plugin - Needed
**/
function plugin_version_ocsinventoryng() {

   return array('name'           => "OCS Inventory NG",
                'version'        => '1.0.0',
                'author'         => 'Remi Colet, Nelly Mahu-Lasson, David Durieux, Xavier Caillaud',
                'license'        => 'GPLv2+',
                'homepage'       => 'https://forge.indepnet.net/repositories/show/ocsinventoryng',
                'minGlpiVersion' => '0.84');// For compatibility / no install in version < 0.80

}


/**
 * Optional : check prerequisites before install : may print errors or add to message after redirect
**/
function plugin_ocsinventoryng_check_prerequisites() {

   if (version_compare(GLPI_VERSION,'0.84','lt') || version_compare(GLPI_VERSION,'0.85','ge')) {
      echo "This plugin requires GLPI = 0.84";
      return false;
   }
   return true;
}


/**
 * Uninstall process for plugin : need to return true if succeeded.
 * Can display a message only if failure and $verbose is true
 *
 * @param $verbose   boolean  (default false)
**/
function plugin_ocsinventoryng_check_config($verbose=false) {

   if (true) { // Your configuration check
      return true;
   }

   if ($verbose) {
      _e('Installed / not configured');
   }
   return false;
}


/**
 * @param $module
 * @param $right
**/
function plugin_ocsinventoryng_haveRight($module, $right) {

   $matches = array(""  => array("", "r", "w"), // ne doit pas arriver normalement
                    "r" => array("r", "w"),
                    "w" => array("w"),
                    "1" => array("1"),
                    "0" => array("0", "1")); // ne doit pas arriver non plus

   if (isset($_SESSION["glpi_plugin_ocsinventoryng_profile"][$module])
       && in_array($_SESSION["glpi_plugin_ocsinventoryng_profile"][$module],$matches[$right])) {
      return true;
   }
   return false;
}


/**
 * Check if I have the right $right to module $module (conpare to session variable)
 *
 * @param $module Module to check
 * @param $right Right to check
 *
 * @return Nothing : display error if not permit
**/
function plugin_ocsinventoryng_checkRight($module, $right) {
   global $CFG_GLPI;

   if (!plugin_ocsinventoryng_haveRight($module, $right)) {
      // Gestion timeout session
      if (!Session::getLoginUserID()) {
         Html::redirect($CFG_GLPI["root_doc"] . "/index.php");
         exit ();
      }
      Html::displayRightError();
   }
}


/**
 * Check if I have one of the right specified
 *
 * @param $modules array of modules where keys are modules and value are right
 *
 * @return Nothing : display error if not permit
**/
function plugin_ocsinventoryng_checkSeveralRightsOr($modules) {
   global $CFG_GLPI;

   $valid = false;
   if (count($modules)) {
      foreach ($modules as $mod => $right) {
         // Itemtype
         if (preg_match('/[A-Z]/', $mod[0])) {
            if ($item = getItemForItemtype($mod)) {
               if ($item->canGlobal($right)) {
                  $valid = true;
               }
            }
         } else if (plugin_ocsinventoryng_haveRight($mod, $right)) {
            $valid = true;
         }
      }
   }

   if (!$valid) {
      // Gestion timeout session
      if (!Session::getLoginUserID()) {
         Html::redirect($CFG_GLPI["root_doc"] . "/index.php");
         exit ();
      }
      displayRightError();
   }
}
?>