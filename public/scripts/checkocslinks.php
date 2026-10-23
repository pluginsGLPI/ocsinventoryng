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

// Ensure current directory when run from crontab
use GlpiPlugin\Ocsinventoryng\OcsProcess;
use GlpiPlugin\Ocsinventoryng\OcsServer;

chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

ini_set('display_errors', 1);
restore_error_handler();

$_GET = [];
if (isset($_SERVER['argv'])) {
    for ($i = 1; $i < $_SERVER['argc']; $i++) {
        $it = explode("=", $_SERVER['argv'][$i], 2);
        $it[0] = preg_replace('/^--/', '', $it[0]);
        $_GET[$it[0]] = (isset($it[1]) ? $it[1] : true);
    }
}

if (isset($_GET['help']) || !count($_GET)) {
    echo "Usage : php checkocslinks.php [ options ]\n";
    echo "Options values :\n";
    echo "\t--glpi   : check missing computer in GLPI\n";
    echo "\t--ocs    : check missing computer in OCS\n";
    echo "\t--dup    : check for duplicate links (n links for 1 computer in GLPI)\n";
    echo "\t--clean  : delete invalid link\n";
    exit(0);
}

$tps = microtime(true);
$nbchk = 0;
$nbdel = 0;
$nbtodo = 0;

global $DB;

foreach ($DB->request([
    'FROM' => 'glpi_plugin_ocsinventoryng_ocsservers',
    'WHERE' => ['is_active' => 1]]) as $serv) {
    $ocsservers_id = $serv ['id'];
    echo "\nServeur: " . $serv['name'] . "\n";

    if (!OcsServer::checkOCSconnection($ocsservers_id)) {
        echo "** no connexion\n";
        continue;
    }

    if (isset($_GET['clean'])) {
        echo "+ Handle ID changes\n";
        OcsProcess::manageDeleted($ocsservers_id);
    }

    if (isset($_GET['glpi'])) {
        echo "+ Search links with no computer in GLPI\n";
        $result = $DB->request([
            'SELECT'    => [
                'glpi_plugin_ocsinventoryng_ocslinks.id',
                'glpi_plugin_ocsinventoryng_ocslinks.ocs_deviceid',
            ],
            'FROM'      => 'glpi_plugin_ocsinventoryng_ocslinks',
            'LEFT JOIN' => [
                'glpi_computers' => [
                    'ON' => [
                        'glpi_computers'                      => 'id',
                        'glpi_plugin_ocsinventoryng_ocslinks' => 'computers_id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_computers.id'                                                       => null,
                'glpi_plugin_ocsinventoryng_ocslinks.plugin_ocsinventoryng_ocsservers_id' => (int) $ocsservers_id,
            ],
        ]);
        foreach ($result as $data) {
            $nbchk++;
            printf("%12d : %s\n", $data['id'], $data['ocs_deviceid']);
            if (isset($_GET['clean'])) {
                if ($DB->delete('glpi_plugin_ocsinventoryng_ocslinks', ['id' => (int) $data['id']])) {
                    $nbdel++;
                }
            } else {
                $nbtodo++;
            }
        }
    }

    if (isset($_GET['ocs'])) {
        $DBocs = OcsServer::getDBocs($ocsservers_id);
        $res[] = $DBocs->getOCSComputers();

        $hardware = [];
        $nb = 0;
        $i = 0;
        if (count($res) > 0) {
            foreach ($res as $k => $data) {
                if (count($data) > 0) {
                    $i++;
                    $nb = count($data);
                    $hardware[$data["ID"]] = $data["DEVICEID"];
                    echo "$i/$nb\r";
                }
            }
            echo "  $nb computers in OCS\n";
        }

        echo "+ Search links with no computer in OCS\n";
        $result = $DB->request([
            'SELECT' => ['id', 'ocsid', 'ocs_deviceid'],
            'FROM'   => 'glpi_plugin_ocsinventoryng_ocslinks',
            'WHERE'  => ['plugin_ocsinventoryng_ocsservers_id' => (int) $ocsservers_id],
        ]);
        $nb = count($result);
        if ($nb > 0) {
            $i = 1;
            foreach ($result as $data) {
                $nbchk++;
                if (isset($hardware[$data["ocsid"]])) {
                    echo "$i/$nb\r";
                } else {
                    printf("%12d : %s\n", $data['id'], $data['ocs_deviceid']);
                    if (isset($_GET['clean'])) {
                        if ($DB->delete('glpi_plugin_ocsinventoryng_ocslinks', ['id' => (int) $data["id"]])) {
                            $nbdel++;
                        }
                    } else {
                        $nbtodo++;
                    }
                }
                $i++;
            }
            echo "  $nb links checked\n";
        }
    }
}

// Link must be unique (for all servers)
if (isset($_GET['dup'])) {
    echo "+ Search duplicate links\n";

    foreach ($DB->request([
        'SELECT'    => [
            'computers_id',
            'COUNT' => '* AS cpt',
        ],
        'FROM' => 'glpi_plugin_ocsinventoryng_ocslinks',
        'GROUPBY'   => 'computers_id',
        'HAVING'    => ['cpt' => ['>', 1]]]) as $data) {
        printf("%4d links for computer #%d\n", $data['cpt'], $data['computers_id']);

        $i = 1;
        foreach ($DB->request([
            'FROM' => 'glpi_plugin_ocsinventoryng_ocslinks',
            'WHERE'     => [
                'computers_id'  => $data['computers_id'],
            ],
            'ORDERBY'    => 'last_update']) as $data2) {
            $del = ($i < $data['cpt']); // Keep the more recent
            printf(
                "%12d : %s (%d-%d, last=%s) : %s\n",
                $data2['id'],
                $data2['ocs_deviceid'],
                $data2['plugin_ocsinventoryng_ocsservers_id'],
                $data2['ocsid'],
                $data2['last_update'],
                ($del ? 'delete' : 'keep'),
            );
            if ($del) {
                if (isset($_GET['clean'])) {
                    if ($DB->delete('glpi_plugin_ocsinventoryng_ocslinks', ['id' => (int) $data2["id"]])) {
                        $nbdel++;
                    }
                } else {
                    $nbtodo++;
                }
            }
            $i++;
        }
    }
}

$tps = microtime(true) - $tps;
printf("\nChecked links : %d\n", $nbchk);
if (isset($_GET['clean'])) {
    printf("Deleted links : %d\n", $nbdel);
} else {
    printf("Corrupt links : %d\n", $nbtodo);
}
printf("Done in %s\n", Html::timestampToString(round($tps, 0, PHP_ROUND_HALF_UP), true));
