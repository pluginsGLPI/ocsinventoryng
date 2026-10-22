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
use GlpiPlugin\Ocsinventoryng\OcsServer;
use GlpiPlugin\Ocsinventoryng\Thread;

Session::checkRight("plugin_ocsinventoryng", UPDATE);

// 0 lists the processes of every server, else a server allowed for the active profile
$ocsservers_id = (int) ($_GET["plugin_ocsinventoryng_ocsservers_id"] ?? 0);
if ($ocsservers_id > 0) {
    $ocsservers_id = OcsServer::checkAllowedServer($ocsservers_id);
}

$thread = new Thread();
// The list itself (PHP_SELF is the GLPI 11 entry point)
$target = PLUGIN_OCS_WEBDIR . "/front/thread.php?plugin_ocsinventoryng_ocsservers_id=" . $ocsservers_id;

if (isset($_POST["delete_processes"])) {

    foreach ((array) ($_POST["item"] ?? []) as $key => $val) {
        $thread->deleteThreadsByProcessId((int) $key);
    }
    Html::redirect($target);

}

Html::header(__('Processes execution of automatic actions', 'ocsinventoryng'), '', "tools", Menu::class, "thread");

$thread->showProcesses($target, $ocsservers_id);

Html::footer();
