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

use CommonDBTM;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Html;
use Session;
use Toolbox;

/**
 * Class Thread
 */
class Thread extends CommonDBTM
{
    public static $rightname = "plugin_ocsinventoryng";

    /**
     * @param int $nb
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return _n('OCSNG server', 'OCSNG servers', $nb, 'ocsinventoryng');
    }

    /**
     * @param $processid
     **/
    public function deleteThreadsByProcessId($processid)
    {
        global $DB;

        foreach ($DB->request([
            'FROM' => $this->getTable(),
            'WHERE' => ['processid' => $processid]]) as $data) {
            // Requires to clean details
            $this->delete(['id' => $data['id']], true);
        }
    }



    /**
     * @param $pid
     * @internal param array $options
     */
    public function showThreadForm($pid)
    {
        global $DB;

        // A process id from the request: an integer, never in the SQL or the page as posted
        $pid = (int) $pid;

        $config = new Config();
        $config->getFromDB(1);

        $finished = true;
        $total    = 0;
        $rows     = [];
        foreach ($DB->request([
            'SELECT' => [
                'id', 'threadid', 'status', 'total_number_machines', 'processid',
                'start_time AS starting_date', 'end_time AS ending_date',
                new QueryExpression('TIME_TO_SEC(' . $DB->quoteName('end_time') . ') - TIME_TO_SEC(' . $DB->quoteName('start_time') . ') AS ' . $DB->quoteName('duree')),
                'imported_machines_number AS imported_machines',
                'synchronized_machines_number AS synchronized_machines',
                'failed_rules_machines_number AS failed_rules_machines',
                'linked_machines_number AS linked_machines',
                'notupdated_machines_number AS notupdated_machines',
                'not_unique_machines_number',
                'link_refused_machines_number',
            ],
            'FROM'   => $this->getTable(),
            'WHERE'  => ['processid' => $pid],
            'ORDER'  => 'threadid ASC',
        ]) as $thread) {
            $thread_finished = ($thread["status"] == PLUGIN_OCSINVENTORYNG_STATE_FINISHED);
            if (!$thread_finished) {
                $finished = false;
            }

            if ($thread["total_number_machines"] == 0) {
                // No machines to process: the thread is done, or it just started
                $percent = $thread_finished ? 100 : 0;
            } else {
                $percent = (100 * ($thread["imported_machines"] + $thread["synchronized_machines"]))
                    / $thread["total_number_machines"];
            }
            $percent = min(100, max(0, (int) round($percent)));

            $rows[] = [
                'threadid'                     => (int) $thread["threadid"],
                'status'                       => $this->getProcessStatusIcon($thread["status"]),
                'starting_date'                => Html::convDateTime($thread["starting_date"]),
                'ending_date'                  => Html::convDateTime($thread["ending_date"]),
                'imported_machines'            => (int) $thread["imported_machines"],
                'synchronized_machines'        => (int) $thread["synchronized_machines"],
                'linked_machines'              => (int) $thread["linked_machines"],
                'failed_rules_machines'        => (int) $thread["failed_rules_machines"],
                'notupdated_machines'          => (int) $thread["notupdated_machines"],
                'not_unique_machines_number'   => (int) $thread["not_unique_machines_number"],
                'link_refused_machines_number' => (int) $thread["link_refused_machines_number"],
                'duree'                        => $thread_finished ? self::timestampToStringShort($thread["duree"]) : Dropdown::EMPTY_VALUE,
                'total_number_machines'        => (int) $thread["total_number_machines"],
                'percent'                      => "<div class='progress' style='min-width: 6rem'>"
                    . "<div class='progress-bar' style='width: " . $percent . "%'>" . $percent . " %</div></div>",
            ];

            $total += $thread["imported_machines"] + $thread["synchronized_machines"]
                + $thread["linked_machines"];
        }

        if (($config->fields["delay_refresh"] > 0) && !$finished) {
            echo "<meta http-equiv='refresh' content='" . (int) $config->fields["delay_refresh"] . "' />";
        }

        TemplateRenderer::getInstance()->display('@ocsinventoryng/table.html.twig', [
            'title'         => sprintf(__('%1$s: %2$s'), __('Process information', 'ocsinventoryng'), $pid),
            'icon'          => 'ti ti-player-play',
            'header_link'   => [
                'title' => __('Processes execution of automatic actions', 'ocsinventoryng'),
                'url'   => PLUGIN_OCS_WEBDIR . "/front/thread.php",
            ],
            'columns'       => [
                'threadid'                     => __('Thread', 'ocsinventoryng'),
                'status'                       => __('Status'),
                'starting_date'                => __('Beginning date of execution', 'ocsinventoryng'),
                'ending_date'                  => __('Ending date of execution', 'ocsinventoryng'),
                'imported_machines'            => __('Computers imported by automatic actions', 'ocsinventoryng'),
                'synchronized_machines'        => __('Computers synchronized', 'ocsinventoryng'),
                'linked_machines'              => __('Computers linked', 'ocsinventoryng'),
                'failed_rules_machines'        => __('Computers not imported by automatic actions', 'ocsinventoryng'),
                'notupdated_machines'          => __('Computers not updated', 'ocsinventoryng'),
                'not_unique_machines_number'   => __('Computers not unique', 'ocsinventoryng'),
                'link_refused_machines_number' => __('Computers refused', 'ocsinventoryng'),
                'duree'                        => __('Process time execution', 'ocsinventoryng'),
                'total_number_machines'        => __('Total of computers to be treated', 'ocsinventoryng'),
                'percent'                      => __('performed percentage', 'ocsinventoryng'),
            ],
            // Built in PHP from integers and translated strings
            'raw_columns'   => ['status', 'percent'],
            'rows'          => $rows,
            'footers'       => count($rows) ? [[
                'threadid' => sprintf(__('%1$s: %2$s'), __('Total of treated computers', 'ocsinventoryng'), $total),
            ]] : [],
            'empty_message' => __('No results found'),
        ]);
    }


    /**
     * @param $pid
     *
     * @return int
     */
    public function getProcessStatus($pid)
    {
        global $DB;

        $pid = (int) $pid;

        $result = $DB->request([
            'SELECT' => 'status',
            'FROM'   => $this->getTable(),
            'WHERE'  => ['processid' => $pid],
        ]);
        $status        = 0;
        $thread_number = count($result);

        foreach ($result as $thread) {
            $status += $thread["status"];
        }

        if ($status < $thread_number * PLUGIN_OCSINVENTORYNG_STATE_FINISHED) {
            return PLUGIN_OCSINVENTORYNG_STATE_RUNNING;
        }
        return PLUGIN_OCSINVENTORYNG_STATE_FINISHED;
    }


    /**
     * @param $delete_frequency
     *
     * @return bool
     */
    public function deleteOldProcesses($delete_frequency)
    {
        global $DB;

        $delete_frequency = (int) $delete_frequency;

        $nbdel = 0;

        if ($delete_frequency > 0) {
            $DB->delete($this->getTable(), [
                'OR' => [
                    'end_time' => null,
                    ['end_time' => ['<', new QueryExpression('DATE_ADD(NOW(), INTERVAL -' . $delete_frequency . ' HOUR)')]],
                ],
            ]);
            $nbdel = $DB->affectedRows();
        }
        return $nbdel;
    }


    /**
     * @param $target
     * @param int $plugin_ocsinventoryng_ocsservers_id
     */
    public function showProcesses($target, $plugin_ocsinventoryng_ocsservers_id = 0)
    {
        global $DB;

        // The server comes from the request: an integer
        $plugin_ocsinventoryng_ocsservers_id = (int) $plugin_ocsinventoryng_ocsservers_id;

        $canedit = Session::haveRight("plugin_ocsinventoryng", UPDATE);
        $log     = Config::logProcessedComputers();

        $config = new Config();
        $config->getFromDB(1);

        $counters = [
            'imported_machines'            => new MiniStat(),
            'synchronized_machines'        => new MiniStat(),
            'linked_machines'              => new MiniStat(),
            'failed_rules_machines'        => new MiniStat(),
            'notupdated_machines'          => new MiniStat(),
            'not_unique_machines_number'   => new MiniStat(),
            'link_refused_machines_number' => new MiniStat(),
            'duree'                        => new MiniStat(),
        ];

        $criteria = [
            'SELECT'  => [
                'processid', 'plugin_ocsinventoryng_ocsservers_id', 'status',
                'SUM' => [
                    'total_number_machines AS total_machines',
                    'imported_machines_number AS imported_machines',
                    'synchronized_machines_number AS synchronized_machines',
                    'linked_machines_number AS linked_machines',
                    'failed_rules_machines_number AS failed_rules_machines',
                    'notupdated_machines_number AS notupdated_machines',
                    'not_unique_machines_number AS not_unique_machines_number',
                    'link_refused_machines_number AS link_refused_machines_number',
                ],
                'MIN'     => 'start_time AS starting_date',
                'MAX'     => ['id AS id', 'end_time AS ending_date'],
                'COUNT'   => '* AS threads_number',
                new QueryExpression('TIMESTAMPDIFF(SECOND, MIN(' . $DB->quoteName('start_time') . '), MAX('
                    . $DB->quoteName('end_time') . ')) AS ' . $DB->quoteName('duree')),
            ],
            'FROM'    => $this->getTable(),
            'GROUPBY' => 'processid',
            'ORDER'   => 'id DESC',
            'LIMIT'   => 50,
        ];
        if ($plugin_ocsinventoryng_ocsservers_id > 0) {
            $criteria['WHERE'] = ['plugin_ocsinventoryng_ocsservers_id' => $plugin_ocsinventoryng_ocsservers_id];
        }

        $servers = [];
        $rows    = [];
        foreach ($DB->request($criteria) as $thread) {
            $finished = ($thread["status"] == PLUGIN_OCSINVENTORYNG_STATE_FINISHED);
            if (!$config->fields["is_displayempty"] && $finished && $thread["total_machines"] == 0) {
                continue;
            }
            if ($finished) {
                foreach ($counters as $key => $counter) {
                    $counter->AddValue($thread[$key]);
                }
            }

            $processid = (int) $thread["processid"];
            $server    = __('All servers', 'ocsinventoryng');
            $servers_id = (int) $thread["plugin_ocsinventoryng_ocsservers_id"];
            if ($servers_id != -1) {
                if (!isset($servers[$servers_id])) {
                    $ocsConfig            = OcsServer::getConfig($servers_id);
                    $servers[$servers_id] = isset($ocsConfig["id"])
                        ? "<a href=\"" . htmlescape(OcsServer::getFormURLWithID($servers_id)) . "\">"
                            . htmlescape((string) $ocsConfig["name"]) . "</a>"
                        : '';
                }
                $server = $servers[$servers_id];
            } else {
                $server = htmlescape($server);
            }

            $row = [
                '_checkbox'                    => $processid,
                'processid'                    => "<a href=\"" . htmlescape(PLUGIN_OCS_WEBDIR . "/front/thread.form.php?pid=" . $processid) . "\">"
                    . $processid . "</a>",
                'status'                       => $this->getProcessStatusIcon($this->getProcessStatus($processid)),
                'threads_number'               => (int) $thread["threads_number"],
                'starting_date'                => Html::convDateTime($thread["starting_date"]),
                'ending_date'                  => Html::convDateTime($thread["ending_date"]),
                'imported_machines'            => (int) $thread["imported_machines"],
                'synchronized_machines'        => (int) $thread["synchronized_machines"],
                'linked_machines'              => (int) $thread["linked_machines"],
                'failed_rules_machines'        => (int) $thread["failed_rules_machines"],
                'notupdated_machines'          => (int) $thread["notupdated_machines"],
                'not_unique_machines_number'   => (int) $thread["not_unique_machines_number"],
                'link_refused_machines_number' => (int) $thread["link_refused_machines_number"],
                'duree'                        => $finished ? self::timestampToStringShort($thread["duree"]) : Dropdown::EMPTY_VALUE,
                'server'                       => $server,
            ];
            if ($log) {
                $row['detail'] = "<a href=\"" . htmlescape(PLUGIN_OCS_WEBDIR . "/front/detail.php?"
                    . http_build_query(['criteria' => [['field' => 5, 'searchtype' => 'contains', 'value' => '^' . $processid . '$']]]))
                    . "\" title=\"" . htmlescape(__('Details')) . "\"><i class='ti ti-zoom-in'></i></a>";
            }
            $rows[] = $row;
        }

        $columns = [
            'processid'                    => __('Process', 'ocsinventoryng'),
            'status'                       => __('Status'),
            'threads_number'               => __('Number of threads', 'ocsinventoryng'),
            'starting_date'                => __('Beginning date of execution', 'ocsinventoryng'),
            'ending_date'                  => __('Ending date of execution', 'ocsinventoryng'),
            'imported_machines'            => __('Computers imported by automatic actions', 'ocsinventoryng'),
            'synchronized_machines'        => __('Computers synchronized', 'ocsinventoryng'),
            'linked_machines'              => __('Computers linked', 'ocsinventoryng'),
            'failed_rules_machines'        => __('Computers not imported by automatic actions', 'ocsinventoryng'),
            'notupdated_machines'          => __('Computers not updated', 'ocsinventoryng'),
            'not_unique_machines_number'   => __('Computers not unique', 'ocsinventoryng'),
            'link_refused_machines_number' => __('Computers refused', 'ocsinventoryng'),
            'duree'                        => __('Process time execution', 'ocsinventoryng'),
            'server'                       => __('Server'),
        ];
        if ($log) {
            $columns['detail'] = '';
        }

        TemplateRenderer::getInstance()->display('@ocsinventoryng/table.html.twig', [
            'title'         => __('Processes execution of automatic actions', 'ocsinventoryng'),
            'icon'          => 'ti ti-player-play',
            'header_link'   => Session::haveRecursiveAccessToEntity(0) ? [
                'title' => __('See all servers', 'ocsinventoryng'),
                'url'   => PLUGIN_OCS_WEBDIR . "/front/thread.php?plugin_ocsinventoryng_ocsservers_id=0",
            ] : null,
            'columns'       => $columns,
            'raw_columns'   => ['processid', 'status', 'server', 'detail'],
            'rows'          => $rows,
            'footers'       => $this->getStatFooters($counters),
            'empty_message' => __('No results found'),
            'form_action'   => $canedit ? $target : null,
            'checkbox_name' => $canedit ? 'item' : null,
            'buttons'       => [
                [
                    'name'    => 'delete_processes',
                    'title'   => _x('button', 'Delete permanently'),
                    'icon'    => 'ti-trash',
                    'class'   => 'btn-danger',
                    'confirm' => __('Confirm the final deletion?'),
                ],
            ],
        ]);
    }


    /**
     * Statistics of the finished processes: minimum, maximum, average and total of each counter
     *
     * @param MiniStat[] $counters counters by column
     *
     * @return array footer rows of the processes table
     */
    private function getStatFooters(array $counters)
    {
        if ($counters['duree']->GetCount() == 0) {
            return [];
        }

        $footers = [];
        foreach ([
            __('Minimum')               => fn(MiniStat $stat) => $stat->GetMinimum(),
            __('Maximum', 'ocsinventoryng') => fn(MiniStat $stat) => $stat->GetMaximum(),
            __('Average')               => fn(MiniStat $stat) => round($stat->GetAverage(), 2, PHP_ROUND_HALF_UP),
            __('Total')                 => fn(MiniStat $stat) => $stat->GetTotal(),
        ] as $label => $value) {
            $footer = ['processid' => __('Statistics'), 'status' => $label];
            foreach ($counters as $key => $counter) {
                $footer[$key] = ($key == 'duree')
                    ? self::timestampToStringShort($value($counter))
                    : $value($counter);
            }
            $footers[] = $footer;
        }

        $time = $counters['duree']->GetTotal();
        if ($time > 0) {
            $computers = $counters['imported_machines']->GetTotal() + $counters['synchronized_machines']->GetTotal()
                + $counters['linked_machines']->GetTotal() + $counters['failed_rules_machines']->GetTotal()
                + $counters['not_unique_machines_number']->GetTotal();
            $footers[] = [
                'processid' => __('Speed'),
                //TRANS: means computers by second
                'duree'     => sprintf(__('%1$s %2$s'), round($computers / $time, 2, PHP_ROUND_HALF_UP), __('pc/s', 'ocsinventoryng')),
            ];
        }

        return $footers;
    }


    /**
     * @return bool
     */
    public function showErrorLog()
    {
        $title = sprintf(__('%1$s: %2$s'), __('Log file', 'ocsinventoryng'), 'ocsng_fullsync.log');
        $fic   = GLPI_LOG_DIR . "/ocsng_fullsync.log";
        $events = [];
        if (is_file($fic)) {
            $size = filesize($fic);
            if ($size > 20000) {
                $logfile = file_get_contents($fic, false, null, $size - 20000, 20000);
                $events  = explode("\n", $logfile);
                // Remove fist partial event
                array_shift($events);
            } else {
                $logfile = file_get_contents($fic);
                $events  = explode("\n\n", $logfile);
            }
            // Remove last empty event
            array_pop($events);
        }

        $number = count($events);
        $limit  = (int) $_SESSION['glpilist_limit'];
        $start  = min($number, max(0, (int) ($_GET["start"] ?? 0)));

        $rows = [];
        foreach (array_slice($events, $start, $limit) as $event) {
            // The log holds names reported by the agents: plain text, escaped by the template
            $rows[] = ['message' => explode("\n", $event)[0]];
        }

        TemplateRenderer::getInstance()->display('@ocsinventoryng/table.html.twig', [
            'title'         => $title,
            'icon'          => 'ti ti-file-text',
            'columns'       => ['message' => __('Message')],
            'rows'          => $rows,
            'empty_message' => __('No results found'),
            'pager'         => $number > $limit ? [
                'start' => $start,
                'count' => $number,
                'limit' => $limit,
                'href'  => PLUGIN_OCS_WEBDIR . "/front/logs.php",
            ] : null,
        ]);

        return $number > 0;
    }

    /**
     * Badge of a process status
     *
     * @param $status
     *
     * @return string HTML
     **/
    public function getProcessStatusIcon($status)
    {
        $badges = [
            PLUGIN_OCSINVENTORYNG_STATE_FINISHED => ['bg-green-lt', 'ti-circle-check', __('Finished state')],
            PLUGIN_OCSINVENTORYNG_STATE_RUNNING  => ['bg-blue-lt', 'ti-hourglass', __('Running')],
            PLUGIN_OCSINVENTORYNG_STATE_STARTED  => ['bg-secondary-lt', 'ti-hourglass-high', __('Scheduled')],
        ];
        if (!isset($badges[$status])) {
            return '';
        }
        [$color, $icon, $label] = $badges[$status];

        return "<span class='badge " . $color . "'><i class='ti " . $icon . " me-1'></i>" . htmlescape($label) . "</span>";
    }



    /**
     * @param $name
     *
     * @return array
     */
    public static function cronInfo($name)
    {

        switch ($name) {
            case "CleanOldThreads":
                return ['description' => __('OCSNG', 'ocsinventoryng') . " - " . __('Clean processes', 'ocsinventoryng'),
                    'parameter' => __('Delete processes after', 'ocsinventoryng')];
        }
        return [];
    }


    /**
     * Run for cleaning logs (old processes)
     *
     * @param $task : object of crontask
     *
     * @return integer : 0 (nothing to do)
     *                   >0 (endded)
     **/
    public static function cronCleanOldThreads($task)
    {

        $thread = new self();
        $nb = $thread->deleteOldProcesses($task->fields['param']);
        $task->setVolume($nb);

        return $nb;
    }

    /**
     * Make a short string from the unix timestamp $sec
     * derived from html::timestampToString
     *
     * @param $time         integer  timestamp
     *
     * @return string
     **/
    public static function timestampToStringShort($time)
    {

        $sign = '';
        if ($time < 0) {
            $sign = '- ';
            $time = abs($time);
        }
        $time = floor($time);

        $units = Toolbox::getTimestampTimeUnits($time);
        // if more than 24 hours
        if ($units['day'] > 0) {
            $units['hour'] += 24 * $units['day'];
        }
        //TRANS:  %1$s is the sign (-or empty), %2$d number of hours, %3$d number of minutes,
        //        %4$d number of seconds
        return sprintf('%1$s%2$02d:%3$02d:%4$02d', $sign, $units['hour'], $units['minute'], $units['second']);
    }
}
