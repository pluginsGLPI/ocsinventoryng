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
         $this->getType() . "1" => __("Last synchronization of computers by month", "ocsinventoryng") . "&nbsp;<i class='fas fa-bar-chart'></i>",
         $this->getType() . "2" => __("Detail of imported computers", "ocsinventoryng") . "&nbsp;<i class='fas fa-pie-chart'></i>",
      ];
   }

   /**
    * @param $widgetId
    *
    * @return PluginMydashboardDatatable|PluginMydashboardHBarChart|PluginMydashboardHtml|PluginMydashboardLineChart|PluginMydashboardPieChart|PluginMydashboardVBarChart
    */
   function getWidgetContentForItem($widgetId, $opt = []) {
      global $DB;

      if (empty($this->form)) {
         $this->init();
      }
      switch ($widgetId) {
         case $this->getType() . "1":
            $name = 'LastSynchroChart';

            $criterias = [];

            $params  = ["preferences" => [],
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

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
               while ($data = $DB->fetchAssoc($result)) {
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

            $nbcomputers     = __('Computers number', 'ocsinventoryng');
            $nbcomputers     = addslashes($nbcomputers);
            $colors          = PluginMydashboardColor::getColors(1, 0);
            $backgroundColor = json_encode($colors);

            $graph_datas = ['name'            => $name,
                            'ids'             => $tabsyncset,
                            'data'            => $dataBarset,
                            'labels'          => $labelsBar,
                            'label'           => $nbcomputers,
                            'backgroundColor' => $backgroundColor];

            $graph = PluginMydashboardBarChart::launchGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "2":
            $name = 'InventoryTypePieChart';

            $criterias = [];

            $params  = ["preferences" => [],
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $counts     = [];
            $name_agent = [];

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
               while ($data = $DB->fetchAssoc($result)) {
                  $counts[]     = $data["nb"];
                  $name_agent[] = __('OCS Inventory NG', 'ocsinventoryng');
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
                  while ($data = $DB->fetchAssoc($result)) {
                     $counts[]     = $data["nb"];
                     $name_agent[] = __('Fusion Inventory', 'ocsinventoryng');
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
               while ($data = $DB->fetchAssoc($result)) {
                  $counts[]     = $data["nb"];
                  $name_agent[] = __('Without agent', 'ocsinventoryng');
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Detail of imported computers", "ocsinventoryng");
            $widget->setWidgetTitle($title);

            $palette            = PluginMydashboardColor::getColors(2);
            $backgroundPieColor = json_encode($palette);
            $dataPieset         = json_encode($counts);
            $labelsPie          = json_encode($name_agent);

            $graph_datas = ['name'            => $name,
                            'ids'             => json_encode([]),
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];

            //            if ($onclick == 1) {
            $graph_criterias = ['widget' => $widgetId];
            //            }

            $graph = PluginMydashboardPieChart::launchPieGraph($graph_datas, $graph_criterias);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => [],
                       "criterias" => [],
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;
      }
   }
}
