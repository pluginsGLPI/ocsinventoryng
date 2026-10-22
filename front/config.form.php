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

use GlpiPlugin\Ocsinventoryng\Config;
use GlpiPlugin\Ocsinventoryng\Menu;

Session::checkRight("plugin_ocsinventoryng", UPDATE);

// The router of GLPI refuses the pages of an inactive plugin
$config = new Config();

if (isset($_POST["update"])) {
    // A single row of configuration: the posted id is ignored
    $config->update(['id' => 1] + $_POST);
    Html::back();
}
if (isset($_POST["soft_lock"])) {
    $config->setScriptLock();
}
if (isset($_POST["soft_unlock"])) {
    $config->removeScriptLock();
}

Html::header(__("Automatic synchronization's configuration", 'ocsinventoryng'), '', "tools", Menu::class, "config");

$config->display(['id' => 1]);

Html::footer();
