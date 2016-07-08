<?php
/*
 * @version $Id: HEADER 15930 2012-12-15 11:10:55Z tsmr $
-------------------------------------------------------------------------
Ocsinventoryng plugin for GLPI
Copyright (C) 2012-2016 by the ocsinventoryng plugin Development Team.

https://forge.glpi-project.org/projects/ocsinventoryng
-------------------------------------------------------------------------

LICENSE

This file is part of ocsinventoryng.

Ocsinventoryng plugin is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

Ocsinventoryng plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with ocsinventoryng. If not, see <http://www.gnu.org/licenses/>.
-------------------------------------------------------------------------- */

// Ensure current directory when run from crontab
chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

include ('../../../inc/includes.php');

ini_set('display_errors',1);
restore_error_handler();

if (!isset($_SERVER['argv'][1])) {
   die("usage testsync.php <computerid>\n");
}

$link = new PluginOcsinventoryngOcslink();
if (!$link->getFromDBforComputer($_SERVER['argv'][1])) {
   die("unknow computer\n");
}
printf("Device: %s\n", $link->getField('ocs_deviceid'));
$timer = new Timer();
$timer->start();

$prof = new XHProf("OCS sync");
PluginOcsinventoryngOcsServer::updateComputer($link->getID(), $link->getField('plugin_ocsinventoryng_ocsservers_id'), 1, 1);
unset($prof);

printf("Done in %s\n", $timer->getTime());
