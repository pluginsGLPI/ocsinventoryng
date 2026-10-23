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

namespace GlpiPlugin\Ocsinventoryng;

use CommonGLPI;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use ProfileRight;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Profile
 */
class Profile extends \Profile
{
    public static string $rightname = "profile";

    public const RIGHT_SYNC   = 'plugin_ocsinventoryng_sync';
    public const RIGHT_VIEW   = 'plugin_ocsinventoryng_view';
    public const RIGHT_IMPORT = 'plugin_ocsinventoryng_import';
    public const RIGHT_LINK   = 'plugin_ocsinventoryng_link';
    public const RIGHT_CLEAN  = 'plugin_ocsinventoryng_clean';
    public const RIGHT_RULE   = 'plugin_ocsinventoryng_rule';

    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return Menu::getTypeName($nb);
    }
    /**
     * @see inc/CommonGLPI::getTabNameForItem()
     *
     * @param CommonGLPI $item
     * @param int        $withtemplate
     *
     * @return string|translated
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'Profile'
            && $item->getField('interface') != 'helpdesk') {
            return self::createTabEntry(Menu::getTypeName(2));
        }
        return '';
    }

    public static function getIcon()
    {
        return "ti ti-devices";
    }


    /**
     * @see inc/CommonGLPI::displayTabContentForItem()
     *
     * @param CommonGLPI $item
     * @param int        $tabnum
     * @param int        $withtemplate
     *
     * @return bool|true
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == 'Profile') {
            $ID   = $item->getID();
            $prof = new self();

            self::addDefaultProfileInfos(
                $ID,
                ['plugin_ocsinventoryng'        => 0,
                    'plugin_ocsinventoryng_sync'   => 0,
                    'plugin_ocsinventoryng_view'   => 0,
                    'plugin_ocsinventoryng_import' => 0,
                    'plugin_ocsinventoryng_link'   => 0,
                    'plugin_ocsinventoryng_clean'  => 0,
                    'plugin_ocsinventoryng_rule'   => 0,
                ],
            );
            $prof->showForm($ID);
        }
        return true;
    }


    /**
     * @param $ID
     */
    public static function createFirstAccess($ID)
    {
        //85
        self::addDefaultProfileInfos(
            $ID,
            ['plugin_ocsinventoryng'        => READ + CREATE + UPDATE + PURGE,
                'plugin_ocsinventoryng_sync'   => READ + UPDATE,
                'plugin_ocsinventoryng_view'   => READ,
                'plugin_ocsinventoryng_import' => READ + UPDATE,
                'plugin_ocsinventoryng_link'   => READ + UPDATE,
                'plugin_ocsinventoryng_clean'  => READ + UPDATE,
                'plugin_ocsinventoryng_rule'   => READ + UPDATE],
            true,
        );
    }


    /**
     * @param      $profiles_id
     * @param      $rights
     * @param bool $drop_existing
     *
     * @internal param $profile
     */
    public static function addDefaultProfileInfos($profiles_id, $rights, $drop_existing = false)
    {
        $profileRight = new ProfileRight();
        $dbu = new DbUtils();
        foreach ($rights as $right => $value) {
            if ($dbu->countElementsInTable('glpi_profilerights', ["profiles_id" => $profiles_id, "name" => $right]) && $drop_existing) {
                $profileRight->deleteByCriteria(['profiles_id' => $profiles_id, 'name' => $right]);
            }
            if (!$dbu->countElementsInTable(
                'glpi_profilerights',
                ["profiles_id" => $profiles_id, "name" => $right],
            )) {
                $myright['profiles_id'] = $profiles_id;
                $myright['name']        = $right;
                $myright['rights']      = $value;
                $profileRight->add($myright);

                //Add right to the current session
                $_SESSION['glpiactiveprofile'][$right] = $value;
            }
        }
    }

    /**
     * Show profile form
     *
     * @param int  $profiles_id
     * @param bool $openform
     * @param bool $closeform
     *
     * @return void
     * @internal param int $items_id id of the profile
     * @internal param value $target url of target
     */
    public function showForm($profiles_id = 0, $openform = true, $closeform = true)
    {
        global $DB;

        $profile = new \Profile();
        $profile->getFromDB($profiles_id);

        // The OCS servers of the profile
        $used    = [];
        $servers = [];
        foreach ($DB->request([
            'SELECT'    => [
                'glpi_plugin_ocsinventoryng_ocsservers_profiles.id AS link_id',
                'glpi_plugin_ocsinventoryng_ocsservers.id',
                'glpi_plugin_ocsinventoryng_ocsservers.name',
            ],
            'FROM'      => 'glpi_plugin_ocsinventoryng_ocsservers_profiles',
            'INNER JOIN' => [
                'glpi_plugin_ocsinventoryng_ocsservers' => [
                    'ON' => [
                        'glpi_plugin_ocsinventoryng_ocsservers_profiles' => 'plugin_ocsinventoryng_ocsservers_id',
                        'glpi_plugin_ocsinventoryng_ocsservers'          => 'id',
                    ],
                ],
            ],
            'WHERE'     => ['glpi_plugin_ocsinventoryng_ocsservers_profiles.profiles_id' => (int) $profiles_id],
            'ORDER'     => 'glpi_plugin_ocsinventoryng_ocsservers.name ASC',
        ]) as $data) {
            $used[$data['id']] = $data['id'];
            $servers[]         = [
                'link_id' => (int) $data['link_id'],
                'name'    => (string) $data['name'],
                'url'     => OcsServer::getFormURLWithID($data['id']),
            ];
        }

        TemplateRenderer::getInstance()->display('@ocsinventoryng/profile_servers.html.twig', [
            'form_url'           => PLUGIN_OCS_WEBDIR . '/front/profile.form.php',
            'profiles_id'        => (int) $profiles_id,
            'ocsserver_itemtype' => OcsServer::class,
            'used'               => $used,
            'servers'            => $servers,
            'can_update_profile' => Session::haveRight(\Profile::$rightname, UPDATE),
        ]);

        // The rights of the plugin: the matrix of the core, which prints itself
        $canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]);
        ob_start();
        $profile->displayRightsChoiceMatrix($this->getAllRights(), ['canedit'       => $canedit,
            'default_class' => 'tab_bg_2',
            'title'         => __('General')]);
        $matrix = ob_get_clean();

        TemplateRenderer::getInstance()->display('@ocsinventoryng/profile_rights.html.twig', [
            'form_url'    => $profile->getFormURL(),
            'profiles_id' => (int) $profiles_id,
            'canedit'     => $canedit,
            'openform'    => $openform,
            'closeform'   => $closeform,
            'matrix'      => $matrix,
        ]);
    }

    /**
     * @return array
     */
    public static function getAllRights()
    {
        $rights = [['itemtype' => OcsServer::class,
            'label'    => _n('OCSNG server', 'OCSNG servers', 2, 'ocsinventoryng'),
            'field'    => 'plugin_ocsinventoryng'],
            ['itemtype' => OcsServer::class,
                'label'    => __('Manually synchronization', 'ocsinventoryng'),
                'field'    => 'plugin_ocsinventoryng_sync',
                'rights'   => [READ   => __('Read'),
                    UPDATE => __('Update')]],
            ['itemtype' => OcsServer::class,
                'label'    => __('See information', 'ocsinventoryng'),
                'field'    => 'plugin_ocsinventoryng_view',
                'rights'   => [READ => __('Read')]],
            ['itemtype' => OcsServer::class,
                'label'    => __('Clean links between GLPI and OCSNG', 'ocsinventoryng'),
                'field'    => 'plugin_ocsinventoryng_clean',
                'rights'   => [READ   => __('Read'),
                    UPDATE => __('Update')]],
            ['itemtype' => OcsServer::class,
                'label'    => __('Import computer', 'ocsinventoryng'),
                'field'    => 'plugin_ocsinventoryng_import',
                'rights'   => [READ   => __('Read'),
                    UPDATE => __('Update')]],
            ['itemtype' => OcsServer::class,
                'label'    => __('Link computer', 'ocsinventoryng'),
                'field'    => 'plugin_ocsinventoryng_link',
                'rights'   => [READ   => __('Read'),
                    UPDATE => __('Update')]],
            ['itemtype' => OcsServer::class,
                'label'    => _n('Rule', 'Rules', 2),
                'field'    => 'plugin_ocsinventoryng_rule',
                'rights'   => [READ   => __('Read'),
                    UPDATE => __('Update')]]];
        return $rights;
    }

    /**
     * Init profiles
     *
     * @param $old_right
     *
     * @return int
     */

    public static function translateARight($old_right)
    {
        switch ($old_right) {
            case '':
                return 0;
            case 'r':
                return READ;
            case 'w':
                return READ + UPDATE;
            case '0':
            case '1':
                return $old_right;

            default:
                return 0;
        }
    }

    /**
     * @since 0.85
     * Migration rights from old system to the new one for one profile
     *
     * @param $profiles_id the profile ID
     *
     * @return bool
     */
    public static function migrateOneProfile($profiles_id)
    {
        global $DB;
        //Cannot launch migration if there's nothing to migrate...
        if (!$DB->tableExists('glpi_plugin_ocsinventoryng_profiles')) {
            return true;
        }

        $it = $DB->request([
            'FROM' => 'glpi_plugin_ocsinventoryng_profiles',
            'WHERE' => ['profiles_id' => $profiles_id],
        ]);
        foreach ($it as $profile_data) {
            $matching       = ['ocsng'       => 'plugin_ocsinventoryng',
                'sync_ocsng'  => 'plugin_ocsinventoryng_sync',
                'view_ocsng'  => 'plugin_ocsinventoryng_view',
                'clean_ocsng' => 'plugin_ocsinventoryng_clean',
                'rule_ocs'    => 'plugin_ocsinventoryng_rule'];
            $current_rights = ProfileRight::getProfileRights($profiles_id, array_values($matching));
            foreach ($matching as $old => $new) {
                if (!isset($current_rights[$old])) {
                    $DB->update('glpi_profilerights', ['rights' => self::translateARight($profile_data[$old])], [
                        'name'        => $new,
                        'profiles_id' => $profiles_id,
                    ]);
                }
            }
        }
    }

    /**
     * Initialize profiles, and migrate it necessary
     */
    public static function initProfile()
    {
        global $DB;
        $profile = new self();
        $dbu = new DbUtils();
        //Add new rights in glpi_profilerights table
        foreach ($profile->getAllRights() as $data) {
            if ($dbu->countElementsInTable(
                "glpi_profilerights",
                ["name" => $data['field']],
            ) == 0) {
                ProfileRight::addProfileRights([$data['field']]);
            }
        }

        //Migration old rights in new ones
        $it = $DB->request([
            'SELECT' => ['id'],
            'FROM' => 'glpi_profiles',
        ]);
        foreach ($it as $prof) {
            self::migrateOneProfile($prof['id']);
        }
        $it = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE' => [
                'profiles_id' => $_SESSION['glpiactiveprofile']['id'],
                'name' => ['LIKE', '%plugin_ocsinventoryng%'],
            ],
        ]);
        foreach ($it as $prof) {
            if (isset($_SESSION['glpiactiveprofile'])) {
                $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
            }
        }
    }


    public static function removeRightsFromSession()
    {
        foreach (self::getAllRights() as $right) {
            if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
                unset($_SESSION['glpiactiveprofile'][$right['field']]);
            }
        }
    }

    /**
     * @param $profile
     */
    public static function addAllServers($profile)
    {
        global $DB;

        $profservers = new Ocsserver_Profile();

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_plugin_ocsinventoryng_ocsservers.id',
            ],
            'FROM'      => 'glpi_plugin_ocsinventoryng_ocsservers',
            'LEFT JOIN'       => [
                'glpi_plugin_ocsinventoryng_ocsservers_profiles' => [
                    'ON' => [
                        'glpi_plugin_ocsinventoryng_ocsservers_profiles'   => 'plugin_ocsinventoryng_ocsservers_id',
                        'glpi_plugin_ocsinventoryng_ocsservers'                  => 'id', [
                            'AND' => [
                                'glpi_plugin_ocsinventoryng_ocsservers_profiles.profiles_id' => $profile,
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_plugin_ocsinventoryng_ocsservers_profiles.id'  => null,
                'glpi_plugin_ocsinventoryng_ocsservers.is_active' => 1,
            ],
        ]);

        foreach ($iterator as $data) {
            $input['plugin_ocsinventoryng_ocsservers_id'] = $data['id'];
            $input['profiles_id']                         = $profile;
            $profservers->add($input);
        }
    }
}
