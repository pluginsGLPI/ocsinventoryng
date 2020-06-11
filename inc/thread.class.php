<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2016 by the ocsinventoryng Development Team.

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

/**
 * Class PluginOcsinventoryngThread
 */
class PluginOcsinventoryngThread extends CommonDBTM {

   static $rightname = "plugin_ocsinventoryng";

   /**
    * @param int $nb
    * @return translated
    */
   static function getTypeName($nb = 0) {
      return _n('OCSNG server', 'OCSNG servers', $nb, 'ocsinventoryng');
   }

   /**
    * @param $processid
    **/
   function deleteThreadsByProcessId($processid) {
      global $DB;

      foreach ($DB->request($this->getTable(), ['processid' => $processid]) as $data) {
         // Requires to clean details
         $this->delete(['id' => $data['id']], true);
      }
   }

   function title() {

      $buttons = [];
      $title = "";
      $buttons["thread.php"] = __('Back to processes list', 'ocsinventoryng');
      Html::displayTitle("", "", $title, $buttons);
      echo "<br>";
   }


   /**
    * @param $pid
    * @internal param array $options
    */
   function showForm($pid) {
      global $DB;

      $config = new PluginOcsinventoryngConfig();
      $config->getFromDB(1);

      $finished = true;
      $total = 0;
      $sql = "SELECT `id`, `threadid`, `status`, `total_number_machines`, `processid`,
                     `start_time` AS starting_date, `end_time` AS ending_date,
                     TIME_TO_SEC(`end_time`) - TIME_TO_SEC(`start_time`) AS duree,
                     `imported_machines_number` AS imported_machines,
                     `synchronized_machines_number` AS synchronized_machines,
                     `failed_rules_machines_number` AS failed_rules_machines,
                     `linked_machines_number` AS linked_machines,
                     `notupdated_machines_number` AS notupdated_machines,
                     `not_unique_machines_number` AS not_unique_machines_number,
                     `link_refused_machines_number` AS link_refused_machines_number
              FROM `" . $this->getTable() . "`
              WHERE `processid` = '$pid'
              ORDER BY `threadid` ASC";
      $result = $DB->query($sql);

      echo "<div class='center' id='tabsbody'>";
      echo "<form name=cas action='' method='post'>";
      echo "<table class='tab_cadre' cellpadding='11'>";
      echo "<tr><th colspan='14'>" . sprintf(__('%1$s: %2$s'), __('Process information', 'ocsinventoryng'), $pid) .
         "</th></tr>";
      echo "<tr>";
      echo "<th>" . __('Thread', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Status') . "</th>";
      echo "<th>" . __('Beginning date of execution', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Ending date of execution', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Computers imported by automatic actions', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Computers synchronized', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Computers linked', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Computers not imported by automatic actions',
            'ocsinventoryng') . "</th>";
      echo "<th>" . __('Computers not updated', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Computers not unique', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Computers refused', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Process time execution', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Total of computers to be treated', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('performed percentage', 'ocsinventoryng') . "</th>";
      echo "</th></tr>";

      if ($DB->numrows($result)) {
         while ($thread = $DB->fetchArray($result)) {
            echo "<tr class='tab_bg_2'>";
            echo "<td class='center'>" . $thread["threadid"] . "</td>";

            echo "<td class='center'>";
            $this->displayProcessStatusIcon($thread["status"]);
            echo "</td>";

            echo "<td class='center'>" . Html::convDateTime($thread["starting_date"]) . "</td>";
            echo "<td class='center'>" . Html::convDateTime($thread["ending_date"]) . "</td>";
            echo "<td class='center'>" . $thread["imported_machines"] . "</td>";
            echo "<td class='center'>" . $thread["synchronized_machines"] . "</td>";
            echo "<td class='center'>" . $thread["linked_machines"] . "</td>";
            echo "<td class='center'>" . $thread["failed_rules_machines"] . "</td>";
            echo "<td class='center'>" . $thread["notupdated_machines"] . "</td>";
            echo "<td class='center'>" . $thread["not_unique_machines_number"] . "</td>";
            echo "<td class='center'>" . $thread["link_refused_machines_number"] . "</td>";
            echo "<td class='center'>";
            if ($thread["status"] == PLUGIN_OCSINVENTORYNG_STATE_FINISHED) {
               echo $this->timestampToStringShort($thread["duree"]);
            } else {
               echo Dropdown::EMPTY_VALUE;
               $finished = false;
            }
            echo "</td>";

            echo "<td class='center'>" . $thread["total_number_machines"] . "</td>";

            if ($thread["total_number_machines"] == 0) {
               //Total number of machines is 0 because the thread had no machines to process
               if ($thread["status"] == PLUGIN_OCSINVENTORYNG_STATE_FINISHED) {
                  $pourcent = 100;
               } else {
                  //Total number of machines is 0 because the thread just started to process
                  $pourcent = 0;
               }
            } else {
               $pourcent = (100 * ($thread["imported_machines"] + $thread["synchronized_machines"]))
                  / $thread["total_number_machines"];
            }
            echo "<td class='center'>";
            printf("%.4s", $pourcent);
            echo "%</td>";

            echo "</tr>";

            $total += $thread["imported_machines"] + $thread["synchronized_machines"]
               + $thread["linked_machines"];
         }
      }
      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='14' class='center'>" . sprintf(__('%1$s: %2$s'),
            __('Total of treated computers', 'ocsinventoryng'),
            $total) .
         "</td></tr>";

      if (($config->fields["delay_refresh"] > 0) && !$finished) {
         echo "<meta http-equiv='refresh' content=\"" .
            $config->fields["delay_refresh"] . "\"; url=\"#\" />";
      }
      echo "</table></div>";

      echo "<div id='tabcontent'></div>";
//      echo "<script type='text/javascript'>loadDefaultTab();</script>";
   }


   /**
    * @param $pid
    *
    * @return int
    */
   function getProcessStatus($pid) {
      global $DB;

      $sql = "SELECT `status`
              FROM `" . $this->getTable() . "`
              WHERE `processid` = '$pid'";
      $result = $DB->query($sql);
      $status = 0;
      $thread_number = 0;

      $thread_number = $DB->numrows($result);

      while ($thread = $DB->fetchArray($result)) {
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
   function deleteOldProcesses($delete_frequency) {
      global $DB;

      $nbdel = 0;

      if ($delete_frequency > 0) {
         $sql = "DELETE
                 FROM `" . $this->getTable() . "`
                 WHERE (`status` = " . PLUGIN_OCSINVENTORYNG_STATE_FINISHED . "
                        AND `end_time` < DATE_ADD(NOW(), INTERVAL -" . $delete_frequency . " HOUR))";
         $DB->query($sql);
         //foreach($DB->request($sql) as $data) {
         // Requires to clean details
         //   $this->delete(array('id'=>$data['id']), true);
         //   $nbdel++;
         //}
      }
      return true;
   }


   /**
    * @param $target
    * @param int $plugin_ocsinventoryng_ocsservers_id
    */
   function showProcesses($target, $plugin_ocsinventoryng_ocsservers_id = 0) {
      global $DB, $CFG_GLPI;

      $canedit = Session::haveRight("plugin_ocsinventoryng", UPDATE);

      $config = new PluginOcsinventoryngConfig();
      $config->getFromDB(1);

      $minfreq = 9999;
      //$task    = new CronTask();
      //if ($task->getFromDBbyName('PluginOcsinventoryngThread', 'CleanOldThreads')) {
      //First of all, deleted old processes
      //   $this->deleteOldProcesses($task->fields['param']);

      //   if ($task->fields['param'] > 0) {
      //      $minfreq=$task->fields['param'];
      //   }
      //}

      $imported_number = new PluginOcsinventoryngMiniStat;
      $synchronized_number = new PluginOcsinventoryngMiniStat;
      $linked_number = new PluginOcsinventoryngMiniStat;
      $failed_number = new PluginOcsinventoryngMiniStat;
      $notupdated_number = new PluginOcsinventoryngMiniStat;
      $notunique_number = new PluginOcsinventoryngMiniStat;
      $linkedrefused_number = new PluginOcsinventoryngMiniStat;
      $process_time = new PluginOcsinventoryngMiniStat;

      $sql = "SELECT `id`, `processid`, SUM(`total_number_machines`) AS total_machines,
                     `plugin_ocsinventoryng_ocsservers_id`, `status`, COUNT(*) AS threads_number,
                     MIN(`start_time`) AS starting_date, MAX(`end_time`) AS ending_date,
                     TIMESTAMPDIFF(SECOND,MIN(start_time),MAX(end_time)) AS duree,
                     SUM(`imported_machines_number`) AS imported_machines,
                     SUM(`synchronized_machines_number`) AS synchronized_machines,
                     SUM(`linked_machines_number`) AS linked_machines,
                     SUM(`failed_rules_machines_number`) AS failed_rules_machines,
                     SUM(`notupdated_machines_number`) AS notupdated_machines,
                     SUM(`not_unique_machines_number`) AS not_unique_machines_number,
                     SUM(`link_refused_machines_number`) AS link_refused_machines_number,
                     `end_time` >= DATE_ADD(NOW(), INTERVAL - " . $minfreq . " HOUR) AS DoStat
              FROM `" . $this->getTable() . "` ";
      if ($plugin_ocsinventoryng_ocsservers_id > 0) {
         $sql .= "WHERE `plugin_ocsinventoryng_ocsservers_id` = " . $plugin_ocsinventoryng_ocsservers_id . "";
      }
      $sql .= " GROUP BY `processid`
              ORDER BY `id` DESC
              LIMIT 50";
      $result = $DB->query($sql);

      echo "<div class='center'>";
      echo "<form name='processes' id='processes' action='$target' method='post'>";
      echo "<table class='tab_cadrehov'>";
      $colspan = '15';
      $log = PluginOcsinventoryngConfig::logProcessedComputers();
      if ($log) {
         $colspan = '16';
      }
      echo "<tr><th colspan='$colspan'>" . __('Processes execution of automatic actions', 'ocsinventoryng');
      if (Session::haveRecursiveAccessToEntity(0)) {
         echo "&nbsp;<a href='thread.php?plugin_ocsinventoryng_ocsservers_id=0'>(" . __('See all servers', 'ocsinventoryng') . ")</a>";
      }
      echo "</th></tr>";
      echo "<tr>";
      echo "<th>&nbsp;</th>";
      echo "<th>&nbsp;</th>";
      echo "<th>" . __('Status') . "</th>";
      echo "<th>" . __('Number of threads', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Beginning date of execution', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Ending date of execution', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Computers imported by automatic actions', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Computers synchronized', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Computers linked', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Computers not imported by automatic actions', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Computers not updated', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Computers not unique', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Computers refused', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Process time execution', 'ocsinventoryng') . "</th>";
      echo "<th>" . __('Server') . "</th>";
      $log = PluginOcsinventoryngConfig::logProcessedComputers();
      if ($log) {
         echo "<th>&nbsp;</th>";
      }
      echo "</tr>\n";

      if ($DB->numrows($result)) {
         while ($thread = $DB->fetchArray($result)) {
            if ($config->fields["is_displayempty"]
               || ($thread["status"] != PLUGIN_OCSINVENTORYNG_STATE_FINISHED)
               || (!$config->fields["is_displayempty"]
                  && ($thread["total_machines"] > 0)
                  && ($thread["status"] == PLUGIN_OCSINVENTORYNG_STATE_FINISHED))) {

               if ($thread["DoStat"]
                  && ($thread["status"] == PLUGIN_OCSINVENTORYNG_STATE_FINISHED)) {
                  $imported_number->AddValue($thread["imported_machines"]);
                  $synchronized_number->AddValue($thread["synchronized_machines"]);
                  $linked_number->AddValue($thread["linked_machines"]);
                  $failed_number->AddValue($thread["failed_rules_machines"]);
                  $notupdated_number->AddValue($thread["notupdated_machines"]);
                  $notunique_number->AddValue($thread["not_unique_machines_number"]);
                  $linkedrefused_number->AddValue($thread["link_refused_machines_number"]);
                  $process_time->AddValue($thread["duree"]);
               } else if ($imported_number->GetCount() > 0) {
                  $this->showStat($minfreq, $imported_number, $synchronized_number,
                     $linked_number, $failed_number, $notupdated_number,
                     $notunique_number, $linkedrefused_number, $process_time, $colspan);
                  $imported_number->Reset();
               }
               echo "<tr class='tab_bg_1'>";
               echo "<td width='10'>";

               if ($canedit) {
                  echo Html::input('item[' . $thread["processid"] . ']', ['type'  => 'checkbox',
                                                                          'value' => 1]);
               } else {
                  echo "&nbsp;";
               }
               echo "</td>";

               echo "<td class='center'>";
               echo "<a href=\"./thread.form.php?pid=" . $thread["processid"] . "\">" .
                  $thread["processid"] . "</a></td>";
               echo "<td class='center'>";
               $this->displayProcessStatusIcon($this->getProcessStatus($thread["processid"]));
               echo "</td>";
               echo "<td class='center'>" . $thread["threads_number"] . "</td>";
               echo "<td class='center'>" . Html::convDateTime($thread["starting_date"]) . "</td>";
               echo "<td class='center'>" . Html::convDateTime($thread["ending_date"]) . "</td>";
               echo "<td class='center'>" . $thread["imported_machines"] . "</td>";
               echo "<td class='center'>" . $thread["synchronized_machines"] . "</td>";
               echo "<td class='center'>" . $thread["linked_machines"] . "</td>";
               echo "<td class='center'>" . $thread["failed_rules_machines"] . "</td>";
               echo "<td class='center'>" . $thread["notupdated_machines"] . "</td>";
               echo "<td class='center'>" . $thread["not_unique_machines_number"] . "</td>";
               echo "<td class='center'>" . $thread["link_refused_machines_number"] . "</td>";

               echo "<td class='center'>";
               if ($thread["status"] == PLUGIN_OCSINVENTORYNG_STATE_FINISHED) {
                  echo $this->timestampToStringShort($thread["duree"]);
               } else {
                  echo Dropdown::EMPTY_VALUE;
               }
               echo "</td>";

               echo "<td class='center'>";
               if ($thread["plugin_ocsinventoryng_ocsservers_id"] != -1) {
                  $ocsConfig = PluginOcsinventoryngOcsServer::getConfig($thread["plugin_ocsinventoryng_ocsservers_id"]);
                  echo "<a href=\"ocsserver.form.php?id=" . $ocsConfig["id"] . "\">" .
                     $ocsConfig["name"] . "</a>";
               } else {
                  echo __('All servers', 'ocsinventoryng');
               }
               echo "</td>";

               if ($log) {
                  echo "<td class='center'>";
                  echo "<a class='fas fa-search-plus' href=\"detail.php?criteria[0][field]=5&" .
                       "criteria[0][searchtype]=contains&criteria[0][value]=^" . $thread["processid"] . '$">' . "</a></td>";
               }
               echo "</tr>\n";

            }
         }
      }

      if ($imported_number->GetCount() > 0) {
         $this->showStat($minfreq, $imported_number, $synchronized_number, $linked_number,
            $failed_number, $notupdated_number, $notunique_number,
            $linkedrefused_number, $process_time, $colspan);
      }
      echo "</table>";

      if ($canedit) {
         echo "<table class='tab_glpi' width='95%'>";
         echo "<tr><td width='30px'><img src=\"" . $CFG_GLPI["root_doc"]
              . "/pics/arrow-left.png\" alt=''></td><td class='center'>";
         echo "<a onclick= \"if ( markCheckboxes('processes') ) "
              . "return false;\" href='#'>" . __("Check all") . "</a></td>";

         echo "<td>/</td><td class='center'>";
         echo "<a onclick= \"if ( unMarkCheckboxes('processes') ) "
              . "return false;\" href='#'>" . __("Uncheck all") . "</a>";
         echo "</td><td align='left' width='80%'>";
         echo Html::submit(_x('button', 'Delete permanently'), ['name' => 'delete_processes']);
         echo "</td>";
         echo "</table>";
      }
      Html::closeForm();
   }


   /**
    * @param $duree
    * @param $imported
    * @param $synchronized
    * @param $linked
    * @param $failed
    * @param $notupdated
    * @param $notunique
    * @param $linkedrefused
    * @param $time
    **/
   function showStat($duree, &$imported, &$synchronized, &$linked, &$failed, &$notupdated,
                         &$notunique, &$linkedrefused, &$time, $colspan) {

      $title = __('Statistics');
      if ($duree < 9999) {
         $title = sprintf(__('%1$s (%2$s)'), $title,
            sprintf(_n('%d hour', '%d hours', $duree), $duree));
      }

      echo "<tr><th colspan='$colspan'>" . $title . "</th></tr>";

      echo "<tr class='tab_bg_1'>";
      $colsp = '5';
      if ($colspan == '16') {
         $colsp = '6';
      }
      echo "<td class='right' colspan='$colsp'>" . __('Minimum') .
         "<br />" . __('Maximum', 'ocsinventoryng') .
         "<br />" . __('Average') .
         "<br />" . __('Total') . "</td>";
      echo "<td class='center'>" . $imported->GetMinimum() .
         "<br />" . $imported->GetMaximum() .
         "<br />" . round($imported->GetAverage(), 2) .
         "<br />" . $imported->GetTotal() . "</td>";
      echo "<td class='center'>" . $synchronized->GetMinimum() .
         "<br />" . $synchronized->GetMaximum() .
         "<br />" . round($synchronized->GetAverage(), 2) .
         "<br />" . $synchronized->GetTotal() . "</td>";
      echo "<td class='center'>" . $linked->GetMinimum() .
         "<br />" . $linked->GetMaximum() .
         "<br />" . round($linked->GetAverage(), 2) .
         "<br />" . $linked->GetTotal() . "</td>";
      echo "<td class='center'>" . $failed->GetMinimum() .
         "<br />" . $failed->GetMaximum() .
         "<br />" . round($failed->GetAverage(), 2) .
         "<br />&nbsp;</td>";
      echo "<td class='center'>" . $notupdated->GetMinimum() .
         "<br />" . $notupdated->GetMaximum() .
         "<br />" . round($notupdated->GetAverage(), 2) .
         "<br />&nbsp;</td>";
      echo "<td class='center'>" . $notunique->GetMinimum() .
         "<br />" . $notunique->GetMaximum() .
         "<br />" . round($notunique->GetAverage(), 2) .
         "<br />&nbsp;</td>";
      echo "<td class='center'>" . $linkedrefused->GetMinimum() .
         "<br />" . $linkedrefused->GetMaximum() .
         "<br />" . round($linkedrefused->GetAverage(), 2) .
         "<br />&nbsp;</td>";
      echo "<td class='center'>" . $this->timestampToStringShort($time->GetMinimum()) .
         "<br />" . $this->timestampToStringShort($time->GetMaximum()) . "<br />" .
         $this->timestampToStringShort(round($time->GetAverage())) .
         "<br />" . $this->timestampToStringShort($time->GetTotal()) . "</td>";
      if ($time->GetTotal() > 0) {
         echo "<td class='center' colspan='2'>" . __('Speed') . "<br />" .
            sprintf(__('%1$s %2$s'),
               round(($imported->GetTotal() + $synchronized->GetTotal()
                     + $linked->GetTotal() + $failed->GetTotal()
                     + $notunique->getTotal())
                  / $time->GetTotal(), 2),
               //TRANS: means computers by second
               __('pc/s', 'ocsinventoryng')) . "</td>";
      } else {
         echo "<td>&nbsp;</td><td>&nbsp;</td>";
      }
      echo "</tr>\n";
   }


   /**
    * @return bool
    */
   function showErrorLog() {

      $fic = GLPI_LOG_DIR . "/ocsng_fullsync.log";
      if (!is_file($fic)) {
         return false;
      }

      $size = filesize($fic);

      if ($size > 20000) {
         $logfile = file_get_contents($fic, 0, null, $size - 20000, 20000);
         $events = explode("\n", $logfile);
         // Remove fist partial event
         array_shift($events);
      } else {
         $logfile = file_get_contents($fic);
         $events = explode("\n\n", $logfile);
      }

      // Remove last empty event
      array_pop($events);
      $number = count($events);

      if (isset($_REQUEST["start"])) {
         $start = $_REQUEST["start"];
      } else {
         $start = 0;
      }

      if ($number < 1) {
         return $this->lognothing();
      }
      if ($start > $number) {
         $start = $number;
      }

      // Display the pager
      Html::printAjaxPager("Logfile: ocsng_fullsync.log", $start, $number);

      // Output events
      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th>Message</th></tr>";

      for ($i = $start; $i < ($start + $_SESSION['glpilist_limit']) && $i < count($events); $i++) {
         $lines = explode("\n", $events[$i]);
         echo "<tr class='tab_bg_2 top'><td>" . $lines[0] . "</td>";
         echo "</tr>";
      }
      echo "</table></div>";
   }


   /**
    * @return bool
    */
   function lognothing() {

      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th>No record found</th></tr>";
      echo "</table>";
      echo "</div><br>";

      return false;
   }


   /**
    * @param $status
    **/
   function displayProcessStatusIcon($status) {

      switch ($status) {
         case PLUGIN_OCSINVENTORYNG_STATE_FINISHED :
            echo "<i style='color:darkgreen' class='fas fa-check-circle' 
            title=\"" . __('Finished state') . "\"></i>";
            break;

         case PLUGIN_OCSINVENTORYNG_STATE_RUNNING :
            echo "<i style='color:grey' class='fas fa-hourglass-half'
               title=\"" . __('Running') . "\"></i>";
            break;

         case PLUGIN_OCSINVENTORYNG_STATE_STARTED :
            echo "<i style='color:grey' class='fas fa-hourglass-start'
            title=\"" . __('Scheduled') . "\"></i>";
            break;
      }
   }


   /**
    * @param $name
    *
    * @return array
    */
   static function cronInfo($name) {

      switch ($name) {
         case "CleanOldThreads" :
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
   static function cronCleanOldThreads($task) {

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
   static function timestampToStringShort($time) {

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
