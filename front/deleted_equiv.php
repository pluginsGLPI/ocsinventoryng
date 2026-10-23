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


use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Ocsinventoryng\Menu;
use GlpiPlugin\Ocsinventoryng\OcsProcess;
use GlpiPlugin\Ocsinventoryng\OcsServer;
use GlpiPlugin\Ocsinventoryng\OcsSoapClient;
use GlpiPlugin\Ocsinventoryng\Profile;

// Cleaning computers (trashbin or state change): the clean right, as front/ocsng.clean.php
Session::checkRight(Profile::RIGHT_CLEAN, UPDATE);

// The OCS server of the session, chosen on front/ocsng.php: still allowed for the active
// profile, otherwise "no server"
OcsServer::checkSessionServer();

$self_url = PLUGIN_OCS_WEBDIR . '/front/deleted_equiv.php';

// The cleaning is started by a POST of the confirmation form (CSRF protection of GLPI), never by a GET: the
// batches that follow only go on with a cleaning started that way (session flag)
if (isset($_POST['clean_deleted'])) {
    unset($_SESSION["ocs_deleted_equiv"]);
    $_SESSION["ocs_deleted_equiv"]["started"] = true;
    Html::redirect($self_url);
}
$started = !empty($_SESSION["ocs_deleted_equiv"]["started"]);

Html::header(Menu::getTypeName(), '', "tools", Menu::class, "deleted_equiv");

$servers_id = $_SESSION["plugin_ocsinventoryng_ocsservers_id"] ?? -1;
$title      = __('Clean OCSNG deleted computers', 'ocsinventoryng');
$done       = static function (string $line, int $progress = 100) use ($title) {
    unset($_SESSION["ocs_deleted_equiv"]);
    OcsServer::showMessage([
        'title'    => $title,
        'lines'    => [$line],
        'icon'     => 'ti-circle-check',
        'color'    => 'bg-green-lt',
        'progress' => $progress,
        'links'    => [OcsServer::getBackLink()],
    ]);
};

if ($servers_id == -1) {
    OcsServer::showNoServer();
} elseif (!OcsServer::checkOCSconnection($servers_id)) {
    OcsServer::showMessage([
        'title' => __('Connection to the database failed', 'ocsinventoryng'),
        'icon'  => 'ti-plug-connected-x',
        'color' => 'bg-red-lt',
        'links' => [OcsServer::getBackLink()],
    ]);
} elseif (!$started) {
    // Confirmation: the number of deleted computers, and the button starting the cleaning
    TemplateRenderer::getInstance()->display('@ocsinventoryng/confirm_action.html.twig', [
        'title'    => $title,
        'icon'     => 'ti-trash',
        'lines'    => [
            sprintf(
                __('%1$s %2$s'),
                (int) OcsServer::getDBocs($servers_id)->getTotalDeletedComputers(),
                __('deleted computers into OCS Inventory NG', 'ocsinventoryng'),
            ),
            __('Update ID of deleted computers of OCSNG', 'ocsinventoryng'),
        ],
        'form_url' => $self_url,
        'name'     => 'clean_deleted',
        'button'   => _x('button', 'Clean'),
        'back'     => OcsServer::getBackLink(),
    ]);
} elseif (OcsServer::getDBocs($servers_id)->getConnectionType() == OcsSoapClient::class) {
    // Only the computers the user may delete or update are cleaned
    OcsProcess::manageDeleted($servers_id, true, true);
    if ($_SESSION["ocs_deleted_equiv"]['computers_to_del']) {
        Html::redirect($self_url);
    }
    $deleted = (int) $_SESSION["ocs_deleted_equiv"]['computers_deleted'];
    $done($deleted === 0
        ? __('No new computers to delete', 'ocsinventoryng')
        : $deleted . " " . __('deleted computers into OCS Inventory NG', 'ocsinventoryng'));
} else {
    if (empty($_SESSION["ocs_deleted_equiv"]["total"])) {
        // Only the computers the user may delete or update are cleaned
        OcsProcess::manageDeleted($servers_id, true, true);
    }

    $total = (int) ($_SESSION["ocs_deleted_equiv"]["total"] ?? 0);
    $count = (int) ($_SESSION["ocs_deleted_equiv"]["deleted"] ?? 0);
    if ($total != $count && isset($_SESSION["ocs_deleted_equiv"]["last_req"])) {
        // Batch in progress: the page reloads itself until every deleted computer is processed
        OcsServer::showMessage([
            'title'    => $title,
            'lines'    => [$count . " / " . $total],
            'icon'     => 'ti-loader-2',
            'progress' => $total > 0 ? min(100, (int) round(100 * $count / $total, 0, PHP_ROUND_HALF_UP)) : 0,
        ]);

        OcsProcess::manageDeleted($servers_id, true, true);
    } elseif ($total === 0) {
        $done(__('No new computers to delete', 'ocsinventoryng'));
    } else {
        $done($count . " / " . $total . " " . __('deleted computers into OCS Inventory NG', 'ocsinventoryng'));
    }
}
Html::footer();
