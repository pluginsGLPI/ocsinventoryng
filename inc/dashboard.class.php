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
class PluginOcsinventoryngDashboard extends CommonGLPI
{

   public $widgets = array();
   private $options;
   private $form;

   /**
    * PluginOcsinventoryngDashboard constructor.
    * @param array $options
    */
   function __construct($options = array())
   {
      $this->options = $options;
   }

   function init()
   {


   }

   /**
    * @return array
    */
   function getWidgetsForItem()
   {
      return array(
         $this->getType() . "1" => __("Last synchronization of computers by month", "ocsinventoryng"),
         $this->getType() . "2" => __("Detail of imported computers", "ocsinventoryng"),
      );
   }

   /**
    * @param $widgetId
    * @return PluginMydashboardDatatable|PluginMydashboardHBarChart|PluginMydashboardHtml|PluginMydashboardLineChart|PluginMydashboardPieChart|PluginMydashboardVBarChart
    */
   function getWidgetContentForItem($widgetId)
   {
      global $DB;

      if (empty($this->form))
         $this->init();
      switch ($widgetId) {
         case $this->getType() . "1":
            $plugin = new Plugin();
            if ($plugin->isActivated("ocsinventoryng")) {

               $query = "SELECT DISTINCT
                           DATE_FORMAT(`glpi_plugin_ocsinventoryng_ocslinks`.`last_update`, '%b %Y') AS period_name,
                           COUNT(`glpi_plugin_ocsinventoryng_ocslinks`.`id`) AS nb,
                           DATE_FORMAT(`glpi_plugin_ocsinventoryng_ocslinks`.`last_update`, '%y%m') AS period
                        FROM `glpi_plugin_ocsinventoryng_ocslinks`
                        LEFT JOIN `glpi_computers`
                           ON `glpi_computers`.`id`=`glpi_plugin_ocsinventoryng_ocslinks`.`computers_id`
                        WHERE `glpi_computers`.`is_deleted` = '0' AND `glpi_computers`.`entities_id` = '" . $_SESSION["glpiactive_entity"] . "'";

               //$query .= getEntitiesRestrictRequest("AND", Computer::getTable())
               $query .= " GROUP BY period_name ORDER BY period ASC";

               $widget = PluginMydashboardHelper::getWidgetsFromDBQuery('vbarchart', $query);

               $datas = $widget->getTabDatas();

               $widget->setWidgetTitle(__("Last synchronization of computers by month", "ocsinventoryng"));
               $widget->setOption("xaxis", array("ticks" => PluginMydashboardBarChart::getTicksFromLabels($datas)));
               $widget->setOption("markers", array("show" => true, "position" => "ct", "labelFormatter" => PluginMydashboardBarChart::getLabelFormatter(2)));
               $widget->setOption('legend', array('show' => false));
               $widget->toggleWidgetRefresh();

               return $widget;
            } else {
               $widget = new PluginMydashboardDatatable();
               $widget->setWidgetTitle(__("Last synchronization of computers by month", "ocsinventoryng"));
               return $widget;
            }
            break;

         case $this->getType() . "2":
            $plugin = new Plugin();
            if ($plugin->isActivated("ocsinventoryng")) {

               $counts = array();

               $query = "SELECT DISTINCT `glpi_computers`.`id`, COUNT(`glpi_computers`.`id`) as nb
                              FROM `glpi_computers`
                              LEFT JOIN `glpi_plugin_ocsinventoryng_ocslinks` ON (`glpi_computers`.`id` = `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id` ) 
                              WHERE `glpi_computers`.`is_deleted` = '0' AND `glpi_computers`.`is_template` = '0' AND `glpi_computers`.`entities_id` = '" . $_SESSION["glpiactive_entity"] . "' ";

               //$query .= getEntitiesRestrictRequest("AND", Computer::getTable())
               $query .= " AND ( (`glpi_plugin_ocsinventoryng_ocslinks`.`use_auto_update` = 1) )";

               $result = $DB->query($query);
               $nb = $DB->numrows($result);

               if ($nb) {
                  while ($data = $DB->fetch_assoc($result)) {
                     $counts[__('OCS Inventory NG', 'ocsinventoryng')] = $data["nb"];
                  }
               }

               $query = "SELECT DISTINCT `glpi_computers`.`id`, COUNT(`glpi_computers`.`id`) AS nb
                              FROM `glpi_computers`
                              LEFT JOIN `glpi_plugin_fusioninventory_inventorycomputercomputers` ON (`glpi_computers`.`id` = `glpi_plugin_fusioninventory_inventorycomputercomputers`.`computers_id` ) 
                              WHERE `glpi_computers`.`is_deleted` = '0' AND `glpi_computers`.`is_template` = '0' AND `glpi_computers`.`entities_id` = '" . $_SESSION["glpiactive_entity"] . "'";

               //$query .= getEntitiesRestrictRequest("AND", Computer::getTable())
               $query .= " AND ( `glpi_plugin_fusioninventory_inventorycomputercomputers`.`last_fusioninventory_update` NOT LIKE '' )";


               $result = $DB->query($query);
               $nb = $DB->numrows($result);

               if ($nb) {
                  while ($data = $DB->fetch_assoc($result)) {
                     $counts[__('Fusion Inventory', 'ocsinventoryng')] = $data["nb"];
                  }
               }
               $query = "SELECT DISTINCT `glpi_computers`.`id`, COUNT(`glpi_computers`.`id`) AS nb
                              FROM `glpi_computers`
                              LEFT JOIN `glpi_plugin_ocsinventoryng_ocslinks` ON (`glpi_computers`.`id` = `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id` ) 
                              LEFT JOIN `glpi_plugin_fusioninventory_inventorycomputercomputers` ON (`glpi_computers`.`id` = `glpi_plugin_fusioninventory_inventorycomputercomputers`.`computers_id` ) 
                              WHERE `glpi_computers`.`is_deleted` = '0' AND `glpi_computers`.`is_template` = '0' AND `glpi_computers`.`entities_id` = '" . $_SESSION["glpiactive_entity"] . "'";

               //$query .= getEntitiesRestrictRequest("AND", Computer::getTable())
               $query .= " AND ( (`glpi_plugin_ocsinventoryng_ocslinks`.`last_update` LIKE '' OR `glpi_plugin_ocsinventoryng_ocslinks`.`last_update` IS NULL) AND (`glpi_plugin_fusioninventory_inventorycomputercomputers`.`last_fusioninventory_update` LIKE '' OR `glpi_plugin_fusioninventory_inventorycomputercomputers`.`last_fusioninventory_update` IS NULL) )";

               $result = $DB->query($query);
               $nb = $DB->numrows($result);

               if ($nb) {
                  while ($data = $DB->fetch_assoc($result)) {
                     $counts[__('Without agent', 'ocsinventoryng')] = $data["nb"];
                  }
               }

               $widget = PluginMydashboardHelper::getWidgetsFromDBQuery('piechart', $query);
               $datas = $widget->getTabDatas();
               $widget->setWidgetTitle(__("Detail of imported computers", "ocsinventoryng"));
               //$widget->setOption("spreadsheet", array("tickFormatter" => PluginMydashboardPieChart::getTickFormatter($widget->getWidgetTitle())));

               $widget->setTabDatas($counts);
               $widget->toggleWidgetRefresh();

               return $widget;
            } else {
               $widget = new PluginMydashboardDatatable();
               $widget->setWidgetTitle(__("Detail of imported computers", "ocsinventoryng"));
               return $widget;
            }
            break;
      }
   }
}