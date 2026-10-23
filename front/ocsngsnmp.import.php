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

use GlpiPlugin\Ocsinventoryng\Menu;
use GlpiPlugin\Ocsinventoryng\OcsProcess;
use GlpiPlugin\Ocsinventoryng\OcsServer;
use GlpiPlugin\Ocsinventoryng\SnmpOcslink;

Session::checkRight(OcsServer::$rightname, UPDATE);

// The OCS server of the session, chosen on front/ocsng.php: still allowed for the active profile
if (OcsServer::checkSessionServer() <= 0) {
    throw new \Glpi\Exception\Http\AccessDeniedHttpException();
}


// The URL of this page (PHP_SELF is the GLPI 11 entry point)
$self_url = PLUGIN_OCS_WEBDIR . '/front/ocsngsnmp.import.php';

Html::header(Menu::getTypeName(), '', "tools", Menu::class, "importsnmp");

$display_list = true;
//First time this screen is displayed : set the import mode to 'basic'
if (!isset($_SESSION["change_import_mode"])) {
    $_SESSION["change_import_mode"] = false;
}

//Changing the import mode
if (isset($_POST["change_import_mode"])) {
    if ($_POST['id'] == "false") {
        $_SESSION["change_import_mode"] = false;
    } else {
        $_SESSION["change_import_mode"] = true;
    }
}

if (isset($_SESSION["ocs_importsnmp"]["id"])) {
    if ($count = count($_SESSION["ocs_importsnmp"]["id"])) {
        if ((isset($_SESSION["ocs_importsnmp"]["connection"]) && $_SESSION["ocs_importsnmp"]["connection"] == false) || !isset($_SESSION["ocs_importsnmp"]["connection"])) {
            if (!OcsServer::checkOCSconnection($_SESSION["plugin_ocsinventoryng_ocsservers_id"])) {
                OcsProcess::showStatistics($_SESSION["ocs_importsnmp"]['statistics']);
                $_SESSION["ocs_importsnmp"]["id"] = [];

                Html::redirect($self_url);
            } else {
                $_SESSION["ocs_importsnmp"]["connection"] = true;
            }
        }
        $percent = min(
            100,
            round(
                100 * ($_SESSION["ocs_importsnmp_count"] - $count) / $_SESSION["ocs_importsnmp_count"],
                0,
                PHP_ROUND_HALF_UP,
            ),
        );

        $key = array_pop($_SESSION["ocs_importsnmp"]["id"]);

        if (isset($_SESSION["ocs_importsnmp"]["entities_id"][$key])) {
            $params['entity'] = $_SESSION["ocs_importsnmp"]["entities_id"][$key];
        } else {
            $params['entity'] = -1;
        }

        if (isset($_SESSION["ocs_importsnmp"]["itemtype"][$key])) {
            $params['itemtype'] = $_SESSION["ocs_importsnmp"]["itemtype"][$key];
        } else {
            $params['itemtype'] = -1;
        }

        $conf = OcsServer::getConfig($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
        $action = SnmpOcslink::processSnmp(
            $key,
            $_SESSION["plugin_ocsinventoryng_ocsservers_id"],
            $params,
        );
        OcsProcess::manageImportStatistics(
            $_SESSION["ocs_importsnmp"]['statistics'],
            $action['status'],
            true,
        );
        OcsProcess::showStatistics($_SESSION["ocs_importsnmp"]['statistics'], false, true);
        echo Html::getProgressBar($percent);
        Html::redirect($self_url);
    } else {
        //Html::getProgressBar($percent);
        if (isset($_SESSION["ocs_importsnmp"]['statistics'])) {
            OcsProcess::showStatistics($_SESSION["ocs_importsnmp"]['statistics'], false, true);
        } else {
            echo "<div class='center b red'>";
            echo __('No import: the plugin will not import these elements', 'ocsinventoryng');
            echo "</div>";
        }
        unset($_SESSION["ocs_importsnmp"]);

        echo "<div class='center b'><br>";
        echo "<a href='" . $self_url . "'>" . __('Back') . "</a></div>";
        $display_list = false;
    }
}

if (!isset($_POST["import_ok"])) {
    if (!isset($_GET['check'])) {
        $_GET['check'] = 'all';
    }
    if (!isset($_GET['start'])) {
        $_GET['start'] = 0;
    }
    if (isset($_SESSION["ocs_importsnmp"])) {
        unset($_SESSION["ocs_importsnmp"]);
    }
    //OcsProcess::manageDeleted($_SESSION["plugin_ocsinventoryng_ocsservers_id"]);
    if ($display_list) {
        $values = $_GET;
        if (isset($_POST["search"])) {
            $values = $_POST;
        }
        $values['plugin_ocsinventoryng_ocsservers_id'] = $_SESSION["plugin_ocsinventoryng_ocsservers_id"];
        //$values['change_import_mode'] = $_SESSION["change_import_mode"];
        //$values['glpiactiveentities'] = $_SESSION["glpiactiveentities"];
        $values['tolinked'] = 0;
        SnmpOcslink::searchForm($values);
        SnmpOcslink::showSnmpDeviceToAdd($values);
    }

} else {
    if (isset($_POST["toimport"]) && (count($_POST['toimport']) > 0)) {
        $_SESSION["ocs_importsnmp_count"] = 0;

        // Queued for the processing above: an OCS id, an entity of the user and a SNMP type
        foreach ($_POST['toimport'] as $key => $val) {
            if ($val == "on") {
                $key = (int) $key;
                $_SESSION["ocs_importsnmp"]["id"][] = $key;

                if (isset($_POST['toimport_entities'][$key])) {
                    $entity = (int) $_POST['toimport_entities'][$key];
                    if ($entity >= 0 && !Session::haveAccessToEntity($entity)) {
                        throw new \Glpi\Exception\Http\AccessDeniedHttpException();
                    }
                    $_SESSION["ocs_importsnmp"]["entities_id"][$key] = $entity;
                }

                if (isset($_POST['toimport_itemtype'][$key])) {
                    $itemtype = (string) $_POST['toimport_itemtype'][$key];
                    if ($itemtype !== '' && $itemtype !== '0'
                        && !in_array($itemtype, SnmpOcslink::$snmptypes, true)) {
                        throw new \Glpi\Exception\Http\BadRequestHttpException();
                    }
                    $_SESSION["ocs_importsnmp"]["itemtype"][$key] = $itemtype;
                }
                $_SESSION["ocs_importsnmp_count"]++;
            }
        }
    }
    Html::redirect($self_url);
}

Html::footer();
