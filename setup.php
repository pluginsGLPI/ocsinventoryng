<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
-------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2025 by the ocsinventoryng Development Team.

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

use Glpi\Plugin\Hooks;
use GlpiPlugin\Ocsinventoryng\Components\Bitlockerstatus;
use GlpiPlugin\Ocsinventoryng\Components\Customapp;
use GlpiPlugin\Ocsinventoryng\Components\NetworkPort;
use GlpiPlugin\Ocsinventoryng\Components\Networkshare;
use GlpiPlugin\Ocsinventoryng\Components\Osinstall;
use GlpiPlugin\Ocsinventoryng\Components\Proxysetting;
use GlpiPlugin\Ocsinventoryng\Components\RegistryKey;
use GlpiPlugin\Ocsinventoryng\Components\Runningprocess;
use GlpiPlugin\Ocsinventoryng\Components\Service;
use GlpiPlugin\Ocsinventoryng\Components\Teamviewer;
use GlpiPlugin\Ocsinventoryng\Components\Winupdate;
use GlpiPlugin\Ocsinventoryng\Components\Winuser;
use GlpiPlugin\Ocsinventoryng\Dashboard;
use GlpiPlugin\Ocsinventoryng\Detail;
use GlpiPlugin\Ocsinventoryng\Menu;
use GlpiPlugin\Ocsinventoryng\Notimportedcomputer;
use GlpiPlugin\Ocsinventoryng\OcsAlert;
use GlpiPlugin\Ocsinventoryng\Ocslink;
use GlpiPlugin\Ocsinventoryng\OcsServer;
use GlpiPlugin\Ocsinventoryng\Profile;
use GlpiPlugin\Ocsinventoryng\RuleImportEntity;
use GlpiPlugin\Ocsinventoryng\SnmpOcslink;

global $CFG_GLPI;

define('PLUGIN_OCSINVENTORYNG_VERSION', '2.1.2');

define("PLUGIN_OCSINVENTORYNG_STATE_STARTED", 1);
define("PLUGIN_OCSINVENTORYNG_STATE_RUNNING", 2);
define("PLUGIN_OCSINVENTORYNG_STATE_FINISHED", 3);

define("PLUGIN_OCSINVENTORYNG_LOCKFILE", GLPI_LOCK_DIR . "/ocsinventoryng.lock");

if (!defined("PLUGIN_OCS_DIR")) {
    define("PLUGIN_OCS_DIR", Plugin::getPhpDir("ocsinventoryng"));
    $root = $CFG_GLPI['root_doc'] . '/plugins/ocsinventoryng';
    define("PLUGIN_OCS_WEBDIR", $root);
}


/**
 * Init the hooks of the plugins -Needed
 **/
function plugin_init_ocsinventoryng()
{
    global $PLUGIN_HOOKS, $CFG_GLPI, $DB;

    $PLUGIN_HOOKS['csrf_compliant']['ocsinventoryng'] = true;
    $PLUGIN_HOOKS['use_rules']['ocsinventoryng']      = ['RuleImportEntity', 'RuleImportAsset'];

    $PLUGIN_HOOKS['change_profile']['ocsinventoryng'] = [Profile::class,
        'initProfile'];

    $PLUGIN_HOOKS['import_item']['ocsinventoryng'] = ['Computer'];

    $PLUGIN_HOOKS['autoinventory_information']['ocsinventoryng']
       = ['Computer'               => [Ocslink::class, 'showSimpleForItem'],
           'ComputerDisk'           => [Ocslink::class, 'showSimpleForChild'],
           'ComputerVirtualMachine' => [Ocslink::class, 'showSimpleForChild'],
           'Printer'                => [SnmpOcslink::class, 'showSimpleForItem'],
           'NetworkEquipment'       => [SnmpOcslink::class, 'showSimpleForItem'],
           'Peripheral'             => [SnmpOcslink::class, 'showSimpleForItem'],
           'Phone'                  => [SnmpOcslink::class, 'showSimpleForItem']];

    //Locks management
    $PLUGIN_HOOKS['display_locked_fields']['ocsinventoryng'] = 'plugin_ocsinventoryng_showLocksForItem';
    $PLUGIN_HOOKS['unlock_fields']['ocsinventoryng']         = 'plugin_ocsinventoryng_unlockFields';

    Plugin::registerClass(
        Ocslink::class,
        ['forwardentityfrom' => 'Computer',
            'addtabon'          => 'Computer']
    );

    Plugin::registerClass(
        RegistryKey::class,
        ['addtabon' => 'Computer']
    );

    if ($DB->tableExists('glpi_plugin_ocsinventoryng_winupdates')) {
        Plugin::registerClass(
            Winupdate::class,
            ['addtabon' => 'Computer']
        );
    }
    if ($DB->tableExists('glpi_plugin_ocsinventoryng_proxysettings')) {
        Plugin::registerClass(
            Proxysetting::class,
            ['addtabon' => 'Computer']
        );
    }

    if ($DB->tableExists('glpi_plugin_ocsinventoryng_winusers')) {
        Plugin::registerClass(
            Winuser::class,
            ['addtabon' => 'Computer']
        );
    }
    if ($DB->tableExists('glpi_plugin_ocsinventoryng_customapps')) {
        Plugin::registerClass(
            Customapp::class,
            ['addtabon' => 'Computer']
        );
    }

    if ($DB->tableExists('glpi_plugin_ocsinventoryng_networkshares')) {
        Plugin::registerClass(
            Networkshare::class,
            ['addtabon' => 'Computer']
        );
    }

    if ($DB->tableExists('glpi_plugin_ocsinventoryng_runningprocesses')) {
        Plugin::registerClass(
            Runningprocess::class,
            ['addtabon' => 'Computer']
        );
    }

    if ($DB->tableExists('glpi_plugin_ocsinventoryng_services')) {
        Plugin::registerClass(
            Service::class,
            ['addtabon' => 'Computer']
        );
    }

    if ($DB->tableExists('glpi_plugin_ocsinventoryng_teamviewers')) {
        Plugin::registerClass(
            Teamviewer::class,
            ['addtabon'   => 'Computer',
                'link_types' => true]
        );

        if (class_exists(Teamviewer::class)) {
            //TODO v11
            //            Link::registerTag(Teamviewer::$tags);
        }
    }

    if ($DB->tableExists('glpi_plugin_ocsinventoryng_osinstalls')) {
        $PLUGIN_HOOKS['post_item_form']['ocsinventoryng'] = [Osinstall::class,
            'showForItem_OperatingSystem'];
    }

    Plugin::registerClass(
        OcsServer::class,
        [
            //                            'massiveaction_noupdate_types' => true,
            'systeminformations_types'     => true]
    );

    Plugin::registerClass(
        Profile::class,
        ['addtabon' => 'Profile']
    );

    Plugin::registerClass(
        Notimportedcomputer::class,
        [
            //                            'massiveaction_noupdate_types' => true,
            //                          'massiveaction_nodelete_types' => true,
            'notificationtemplates_types'  => true]
    );

    Plugin::registerClass(
        RuleImportEntity::class,
        [
            //                            'massiveaction_noupdate_types' => true,
            //                          'massiveaction_nodelete_types' => true,
            'notificationtemplates_types'  => true]
    );

    Plugin::registerClass(
        Detail::class,
        //                         ['massiveaction_noupdate_types' => true,
        //                          'massiveaction_nodelete_types' => true]
    );

    Plugin::registerClass(
        NetworkPort::class,
        ['networkport_instantiations' => true]
    );

    Plugin::registerClass(
        SnmpOcslink::class,
        ['addtabon' => ['Computer', 'Printer', 'NetworkEquipment', 'Peripheral', 'Phone']]
    );

    Plugin::registerClass(
        OcsAlert::class,
        ['addtabon'                    => ['Entity', 'CronTask'],
            'notificationtemplates_types' => true]
    );

    // transfer
    $PLUGIN_HOOKS['item_transfer']['ocsinventoryng'] = "plugin_ocsinventoryng_item_transfer";

    // Css file
    if (isset($_SESSION['glpiactiveprofile']['interface'])
        && $_SESSION['glpiactiveprofile']['interface'] == 'central') {
        $PLUGIN_HOOKS[Hooks::ADD_CSS]['ocsinventoryng'] = 'css/ocsinventoryng.css';
    }
    if (Session::getLoginUserID()) {
        $ocsserver = new OcsServer();
        // Display a menu entry ?
        if (Session::haveRight("plugin_ocsinventoryng", READ)) {
            $PLUGIN_HOOKS['menu_toadd']['ocsinventoryng'] = ['tools' => Menu::class];
        }

        if (Session::haveRight("plugin_ocsinventoryng", UPDATE)
            || Session::haveRight("config", UPDATE)) {
            $PLUGIN_HOOKS['use_massive_action']['ocsinventoryng'] = 1;
            //$PLUGIN_HOOKS['redirect_page']['ocsinventoryng']      = "front/ocsng.php";
            $PLUGIN_HOOKS['redirect_page']['ocsinventoryng'] = PLUGIN_OCS_WEBDIR . "/front/notimportedcomputer.form.php";

            //TODO Change for menu
            $PLUGIN_HOOKS['config_page']['ocsinventoryng'] = 'front/config.php';
        }

        $PLUGIN_HOOKS['post_init']['ocsinventoryng'] = 'plugin_ocsinventoryng_postinit';

        $PLUGIN_HOOKS['mydashboard']['ocsinventoryng'] = [Dashboard::class];

        if ($ocsserver->getField('import_bitlocker')) {
            $PLUGIN_HOOKS['post_item_form']['ocsinventoryng'] = [Bitlockerstatus::class,'showForDisk'];
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
function plugin_version_ocsinventoryng()
{
    return ['name'         => "OCS Inventory NG",
        'version'      => PLUGIN_OCSINVENTORYNG_VERSION,
        'author'       => 'Gilles Dubois, Remi Collet, Nelly Mahu-Lasson, David Durieux, Xavier Caillaud, Walid Nouh, Arthur Jaouen',
        'license'      => 'GPLv2+',
        'homepage'     => 'https://github.com/pluginsGLPI/ocsinventoryng',
        'requirements' => [
            'glpi' => [
                'min' => '11.0',
                'max' => '12.0',
                'dev' => false,
            ],
        ],
    ];
}
