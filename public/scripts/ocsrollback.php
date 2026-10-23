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

// Command line scripts: GLPI serves the public directory over HTTP, never run them from there
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit(1);
}

/** @file
 * @brief Rollback OCS events (after a resto)
 */


use GlpiPlugin\Ocsinventoryng\DBocs;
use GlpiPlugin\Ocsinventoryng\OcsServer;

define("GLPI_DIR_ROOT", "../../../..");
require_once GLPI_DIR_ROOT . '/src/Glpi/Application/ResourcesChecker.php';
(new \Glpi\Application\ResourcesChecker(GLPI_DIR_ROOT))->checkResources();

include GLPI_DIR_ROOT . '/vendor/autoload.php';
$kernel = new \Glpi\Kernel\Kernel($options['env'] ?? null);
$application = new \Glpi\Console\Application($kernel);

ini_set("memory_limit", "-1");
ini_set("max_execution_time", "0");

if ($argv) {
    for ($i = 1; $i < count($argv); $i++) {
        //To be able to use = in search filters, enter \= instead in command line
        //Replace the \= by ° not to match the split function
        $arg = str_replace('\=', '°', $argv[$i]);
        $it = explode("=", $arg);
        $it[0] = preg_replace('/^--/', '', $it[0]);

        //Replace the ° by = the find the good filter
        $it = str_replace('°', '=', $it);
        $_GET[$it[0]] = $it[1];
    }
}



$CFG_GLPI["debug"] = 0;


if (!isset($_GET["server"])) {

    echo "*******************************************\n";
    echo " This script kill babies : don't use it !! \n";
    echo "*******************************************\n";

    die("\nUsage : php -q -f rollbackocs.php --server=# [ --run=1 ]\n");

}
$DBocs = new DBocs($_GET["server"]);
echo "Connecting to " . $DBocs->dbhost . "\n";

if (!OcsServer::checkOCSconnection($_GET["server"])) {
    die("Failed connexion to OCS\n");
}
$run = (isset($_GET["run"]) && $_GET["run"] > 0);
$debug = (isset($_GET["debug"]) && $_GET["debug"] > 0);

// Find Last Machine ID + time
$data = $DBocs->request([
    'FROM'  => 'hardware',
    'ORDER' => 'ID DESC',
    'LIMIT' => 1,
])->current();

if ($data === null) {
    die("No data from OCS\n");
}

$maxid = $data["ID"];
$maxti = $data["LASTCOME"];

echo "Last new computer : " . $data["DEVICEID"] . " ($maxid, $maxti)\n";
if (!$maxid) {
    die("Bad value\n");
}
// Computer from OCS : New, ID Changed, Linked
$res = $DB->request([
    'FROM'  => 'glpi_logs',
    'WHERE' => [
        'date_mod'      => ['>=', $maxti],
        'itemtype'      => 1,
        'linked_action' => [8, 10, 11],
    ],
    'ORDER' => 'id DESC',
]);

$comp = new Computer();

echo "Start\n";
$tabres = [];
$nb = $nbupd = 0;

foreach ($res as $event) {

    if ($event["new_value"] > $maxid
       && OcsServer::getServerByComputerID($event["items_id"]) == $_GET["server"]
       && $comp->getFromDB($event["items_id"])
    ) {

        $nb++;
        printf(
            "+ %5d : %s : %s (%s > %s)\n",
            $nb,
            $event["date_mod"],
            $comp->fields["name"],
            $event["old_value"],
            $event["new_value"],
        );

        if (!isset($tabres[$comp->fields["entities_id"]])) {
            $tabres[$comp->fields["entities_id"]] = [];
        }

        if ($event["linked_action"] == 10) {// ID Changed
            $tabres[$comp->fields["entities_id"]][] = "ID:" . $comp->fields["id"] . " - " .
               $comp->fields["name"] .
               " (" . $comp->fields["serial"] . ") => rollback lien";

            // Search the old Device_ID in OCS
            $hard = $DBocs->request([
                'SELECT' => 'DEVICEID',
                'FROM'   => 'hardware',
                'WHERE'  => ['ID' => $event["old_value"]],
                'LIMIT'  => 1,
            ])->current();

            $olddevid = "";
            if ($hard !== null) {
                $olddevid = $hard["DEVICEID"];
            }

            // Rollback the change in ocs_link
            $params = ['ocsid' => $event["old_value"]];

            if (!empty($olddevid)) {
                $params['ocs_deviceid'] = $olddevid;
            }

            $where = ['computers_id' => (int) $event["items_id"]];

            if ($debug) {
                echo "DEBUG: " . $DB->buildUpdate('glpi_ocslinks', $params, $where)->getQuery() . " \n";
            }

            if ($run) {
                // A failing query throws: no error branch
                $DB->update('glpi_ocslinks', $params, $where);
                $nbupd += $DB->getAffectedRows();

                $changes[0] = 0;
                $changes[2] = "Rollback: restauration lien du $maxti";
                $changes[1] = "";
                Log::history(
                    $event["items_id"],
                    'Computer',
                    $changes,
                    0,
                    Log::HISTORY_LOG_SIMPLE_MESSAGE,
                );
            }

        } else { // $event["linked_action"]==8 (New) or 11 (linked)
            $tabres[$comp->fields["entities_id"]][] = "ID:" . $comp->fields["id"] . " - " .
               $comp->fields["name"] .
               " (" . $comp->fields["serial"] . ") => retour stock";

            // TODO: to be done according to automatic link configuration
            $input["id"] = $event["items_id"];
            $input["name"] = null;  // No name
            $input["is_dynamic"] = 0;     // No Ocs link
            $input["state"] = 5;     // Available

            // Unlink the computer
            $where = ['computers_id' => (int) $event["items_id"]];

            if ($debug) {
                echo "DEBUG: " . $DB->buildDelete('glpi_ocslinks', $where)->getQuery() . " \n";
            }

            if ($run) {
                // Restore previous state
                $comp->update($input);

                // Unlink the computer
                // A failing query throws: no error branch
                $DB->delete('glpi_ocslinks', $where);
                $nbupd += $DB->getAffectedRows();

                $changes[0] = 0;
                $changes[2] = "Rollback: restauration statut au $maxti";
                $changes[1] = "";
                Log::history(
                    $event["items_id"],
                    'Computer',
                    $changes,
                    0,
                    Log::HISTORY_LOG_SIMPLE_MESSAGE,
                );
            }
        } // Else
    } // If PC
} // foreach event

printf("=> %d computers, %d updates\n", $nb, $nbupd);

echo "Saving reports in " . GLPI_LOG_DIR . "\n";
$nbc = 0;
foreach ($tabres as $ent => $comps) {
    $name = Dropdown::getDropdownName("glpi_entities", $ent);
    printf("+ %4d : %s\n", $ent, $name);
    file_put_contents(
        GLPI_LOG_DIR . "/rollback-$ent.log",
        "Rollbak for $name\n\n" . implode("\n", $comps) . "\n\n",
    );
    $nbc += count($comps);
}
printf("=> %d reports for %d computers\n", count($tabres), $nbc);
echo "End\n";
