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
 * Class PluginOcsinventoryngDashboard
 */
class PluginOcsinventoryngDashboard extends CommonGLPI {

   public  $widgets = [];
   private $options;
   private $form;

   /**
    * PluginOcsinventoryngDashboard constructor.
    *
    * @param array $options
    */
   function __construct($options = []) {
      $this->options    = $options;
      $this->interfaces = ["central"];
   }

   function init() {

   }

   /**
    * @return array
    */
   function getWidgetsForItem() {
      return [
         $this->getType() . "1" => __("Last synchronization of computers by month", "ocsinventoryng") . "&nbsp;<i class='fa fa-bar-chart'></i>",
         $this->getType() . "2" => __("Detail of imported computers", "ocsinventoryng") . "&nbsp;<i class='fa fa-pie-chart'></i>",
      ];
   }

   /**
    * @param $widgetId
    *
    * @return PluginMydashboardDatatable|PluginMydashboardHBarChart|PluginMydashboardHtml|PluginMydashboardLineChart|PluginMydashboardPieChart|PluginMydashboardVBarChart
    */
   function getWidgetContentForItem($widgetId) {
      global $DB, $CFG_GLPI;

      if (empty($this->form)) {
         $this->init();
      }
      switch ($widgetId) {
         case $this->getType() . "1":

            $query = "SELECT DISTINCT
                           DATE_FORMAT(`glpi_plugin_ocsinventoryng_ocslinks`.`last_update`, '%b %Y') AS periodsync_name,
                           COUNT(`glpi_plugin_ocsinventoryng_ocslinks`.`id`) AS nb,
                           DATE_FORMAT(`glpi_plugin_ocsinventoryng_ocslinks`.`last_update`, '%Y-%m') AS periodsync
                        FROM `glpi_plugin_ocsinventoryng_ocslinks`
                        LEFT JOIN `glpi_computers`
                           ON `glpi_computers`.`id`=`glpi_plugin_ocsinventoryng_ocslinks`.`computers_id`
                        WHERE `glpi_computers`.`is_deleted` = 0 
                        AND `glpi_computers`.`entities_id` = " . $_SESSION["glpiactive_entity"];

            //$query .= getEntitiesRestrictRequest("AND", Computer::getTable())
            $query        .= " GROUP BY periodsync_name ORDER BY periodsync ASC";
            $result       = $DB->query($query);
            $nb           = $DB->numrows($result);
            $tabdata      = [];
            $tabnames     = [];
            $tabsyncdates = [];
            if ($nb) {
               while ($data = $DB->fetch_assoc($result)) {
                  $tabdata[]      = $data['nb'];
                  $tabnames[]     = $data['periodsync_name'];
                  $tabsyncdates[] = $data['periodsync'];
               }
            }

            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle(__("Last synchronization of computers by month", "ocsinventoryng"));

            $dataBarset = json_encode($tabdata);
            $labelsBar  = json_encode($tabnames);
            $tabsyncset = json_encode($tabsyncdates);

            $nbcomputers = __('Computers number', 'ocsinventoryng');
            $nbcomputers = addslashes($nbcomputers);
            $graph = "<script type='text/javascript'>
                     var barsynchChartData = {
                             datasets: [{
                               data: $dataBarset,
                               label: '$nbcomputers',
                               backgroundColor: '#1f77b4',
                     //          backgroundColor: '#FFF',
                  //                   fill: false,
                  //                   lineTension: '0.1',
                             }],
                           labels: $labelsBar
                           };
                     var datesyncset = $tabsyncset;
                     $(document).ready(
                        function () {
                            var isChartRendered = false;
                            var canvas = document . getElementById('LastSynchroChart');
                            var ctx = canvas . getContext('2d');
                            ctx.canvas.width = 700;
                            ctx.canvas.height = 400;
                            var LastSynchroChart = new Chart(ctx, {
                                  type: 'bar',
                                  data: barsynchChartData,
                                  options: {
                                      responsive:true,
                                      maintainAspectRatio: true,
                                      title:{
                                          display:false,
                                          text:'LastSynchroChart'
                                      },
                                      tooltips: {
                                          enabled: false,
//                                          mode: 'index',
//                                          intersect: false
                                      },
                                      scales: {
                                          xAxes: [{
                                              stacked: true,
                                          }],
                                          yAxes: [{
                                              stacked: true
                                          }]
                                      },
                                     hover: {
                                        onHover: function(event,elements) {
                                           $('#LastSynchroChart').css('cursor', elements[0] ? 'pointer' : 'default');
                                         }
                                      },
                                      animation: {
                                       onComplete: function() {
                                          
                                          var chartInstance = this.chart,
                                          ctx = chartInstance.ctx;
                                          ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontSize, Chart.defaults.global.defaultFontStyle, Chart.defaults.global.defaultFontFamily);
                                          ctx.textAlign = 'center';
                                          ctx.textBaseline = 'bottom';
                              
                                          this.data.datasets.forEach(function (dataset, i) {
                                              var meta = chartInstance.controller.getDatasetMeta(i);
                                              meta.data.forEach(function (bar, index) {
                                                  var data = dataset.data[index];                            
                                                  ctx.fillText(data, bar._model.x, bar._model.y - 5);
                                              });
                                          });
                                          isChartRendered = true
                                       }
                                     }
                                  }
                              });
                              
                           canvas.onclick = function(evt) {
                              var activeSynchroPoints = LastSynchroChart.getElementsAtEvent(evt);
                              if (activeSynchroPoints[0]) {
                                var chartSyncData = activeSynchroPoints[0]['_chart'].config.data;
                                var idx = activeSynchroPoints[0]['_index'];
                                var label = chartSyncData.labels[idx];
                                var value = chartSyncData.datasets[0].data[idx];
                                var dateinv = datesyncset[idx];
                  //              var url = \"http://example.com/?label=\" + label + \"&value=\" + value;
                                $.ajax({
                                   url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/launchURL.php',
                                   type: 'POST',
                                   data:{dateinv:dateinv, widget:'$widgetId'},
                                   success:function(response) {
                                           window.open(response);
                                         }
                                });
                              }
                            };
                     }
                 );
                      </script>";

            $canvas = true;
            if ($nb < 1) {
               $canvas = false;
               $graph .= __('No data available', 'mydashboard');
            }
            $params    = ["widgetId"  => $widgetId,
                          "name"      => 'LastSynchroChart',
                          "onsubmit"  => false,
                          "opt"       => [],
                          "criterias" => [],
                          "export"    => $canvas,
                          "canvas"    => $canvas,
                          "nb"        => $nb];
            $graph     .= PluginMydashboardHelper::getGraphHeader($params);
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "2":

            $counts = [];
            $name   = [];

            $query = "SELECT DISTINCT `glpi_computers`.`id`, COUNT(`glpi_computers`.`id`) AS nb
                              FROM `glpi_computers`
                              LEFT JOIN `glpi_plugin_ocsinventoryng_ocslinks` 
                              ON (`glpi_computers`.`id` = `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id` ) 
                              WHERE `glpi_computers`.`is_deleted` = 0 
                              AND `glpi_computers`.`is_template` = 0 
                              AND `glpi_computers`.`entities_id` = " . $_SESSION["glpiactive_entity"];

            //$query .= getEntitiesRestrictRequest("AND", Computer::getTable())
            $query .= " AND ( (`glpi_plugin_ocsinventoryng_ocslinks`.`use_auto_update` = 1) )";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            if ($nb) {
               while ($data = $DB->fetch_assoc($result)) {
                  $counts[] = $data["nb"];
                  $name[]   = __('OCS Inventory NG', 'ocsinventoryng');
               }
            }
            $plugin = new Plugin();
            if ($plugin->isActivated("fusioninventory")) {

               $query = "SELECT DISTINCT `glpi_computers`.`id`, COUNT(`glpi_computers`.`id`) AS nb
                                 FROM `glpi_computers`
                                 LEFT JOIN `glpi_plugin_fusioninventory_inventorycomputercomputers` 
                                 ON (`glpi_computers`.`id` = `glpi_plugin_fusioninventory_inventorycomputercomputers`.`computers_id` ) 
                                 WHERE `glpi_computers`.`is_deleted` = 0 
                                 AND `glpi_computers`.`is_template` = 0
                                 AND `glpi_computers`.`entities_id` = " . $_SESSION["glpiactive_entity"];

               //$query .= getEntitiesRestrictRequest("AND", Computer::getTable())
               $query .= " AND ( `glpi_plugin_fusioninventory_inventorycomputercomputers`.`last_fusioninventory_update` NOT LIKE '' )";

               $result = $DB->query($query);
               $nb     = $DB->numrows($result);

               if ($nb) {
                  while ($data = $DB->fetch_assoc($result)) {
                     $counts[] = $data["nb"];
                     $name[]   = __('Fusion Inventory', 'ocsinventoryng');
                  }
               }
            }
            if ($plugin->isActivated("fusioninventory")) {
               $query = "SELECT DISTINCT `glpi_computers`.`id`, COUNT(`glpi_computers`.`id`) AS nb
                              FROM `glpi_computers`
                              LEFT JOIN `glpi_plugin_ocsinventoryng_ocslinks` 
                              ON (`glpi_computers`.`id` = `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id` ) 
                              LEFT JOIN `glpi_plugin_fusioninventory_inventorycomputercomputers` 
                              ON (`glpi_computers`.`id` = `glpi_plugin_fusioninventory_inventorycomputercomputers`.`computers_id` ) 
                              WHERE `glpi_computers`.`is_deleted` = 0
                              AND `glpi_computers`.`is_template` = 0
                              AND `glpi_computers`.`entities_id` = " . $_SESSION["glpiactive_entity"];

               //$query .= getEntitiesRestrictRequest("AND", Computer::getTable())
               $query .= " AND ( (`glpi_plugin_ocsinventoryng_ocslinks`.`last_update` LIKE '' OR `glpi_plugin_ocsinventoryng_ocslinks`.`last_update` IS NULL) AND (`glpi_plugin_fusioninventory_inventorycomputercomputers`.`last_fusioninventory_update` LIKE '' OR `glpi_plugin_fusioninventory_inventorycomputercomputers`.`last_fusioninventory_update` IS NULL) )";
            } else {
               $query = "SELECT DISTINCT `glpi_computers`.`id`, COUNT(`glpi_computers`.`id`) AS nb
                              FROM `glpi_computers`
                              LEFT JOIN `glpi_plugin_ocsinventoryng_ocslinks` 
                              ON (`glpi_computers`.`id` = `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id` ) 
                              WHERE `glpi_computers`.`is_deleted` = 0 
                              AND `glpi_computers`.`is_template` = 0 
                              AND `glpi_computers`.`entities_id` = " . $_SESSION["glpiactive_entity"];

               //$query .= getEntitiesRestrictRequest("AND", Computer::getTable())
               $query .= " AND (`glpi_plugin_ocsinventoryng_ocslinks`.`last_update` LIKE '' 
               OR `glpi_plugin_ocsinventoryng_ocslinks`.`last_update` IS NULL) ";
            }
            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            if ($nb) {
               while ($data = $DB->fetch_assoc($result)) {
                  $counts[] = $data["nb"];
                  $name[]   = __('Without agent', 'ocsinventoryng');
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Detail of imported computers", "ocsinventoryng");
            $widget->setWidgetTitle($title);

            $palette            = PluginMydashboardColor::getColors(2);
            $backgroundPieColor = json_encode($palette);
            $dataPieset         = json_encode($counts);
            $labelsPie          = json_encode($name);

            $graph = "<script type='text/javascript'>
         
            var dataInvPie = {
              datasets: [{
                data: $dataPieset,
                backgroundColor: $backgroundPieColor
              }],
              labels: $labelsPie
            };
            
            $(document).ready(
              function() {
                var canvas = document.getElementById('InventoryTypePieChart');
                var ctx = canvas.getContext('2d');
                ctx.canvas.width = 700;
                ctx.canvas.height = 400;
                var InventoryTypePieChart = new Chart(ctx, {
                  type: 'pie',
                  data: dataInvPie,
                  options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    animation: {
                        onComplete: function() {
                          isChartRendered = true
                        }
                      }
                   }
                });
//                    canvas.onclick = function(evt) {
//                        var activePoints = InventoryTypePieChart.getElementsAtEvent(evt);
//                        if (activePoints[0]) {
//                          var chartData = activePoints[0]['_chart'].config.data;
//                          var idx = activePoints[0]['_index'];
//                          var label = chartData.labels[idx];
//                          var value = chartData.datasets[0].data[idx];
//                          var dateinv = dateset[idx];
//            //              var url = \"http://example.com/?label=\" + label + \"&value=\" + value;
//                          $.ajax({
//                             url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/launchURL.php',
//                             type: 'POST',
//                             data:{dateinv:dateinv, widget:'$widgetId'},
//                             success:function(response) {
//                                     window.open(response);
//                                   }
//                          });
//                        }
//                      };
                   }
                 );
                
             </script>";
            $canvas = true;
            if ($nb < 1) {
               $canvas = false;
               $graph .= __('No data available', 'mydashboard');
            }
            $params    = ["widgetId"  => $widgetId,
                          "name"      => 'InventoryTypePieChart',
                          "onsubmit"  => false,
                          "opt"       => [],
                          "criterias" => [],
                          "export"    => $canvas,
                          "canvas"    => $canvas,
                          "nb"        => $nb];
            $graph     .= PluginMydashboardHelper::getGraphHeader($params);
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;
      }
   }
}
