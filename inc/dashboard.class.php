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

class PluginOcsinventoryngDashboard extends CommonGLPI {

   public $widgets = array();
   private $options;
   private $datas, $form;

   function __construct($options = array()) {
      $this->options = $options;
   }

   function init() {


   }

   function getWidgetsForItem() {
      return array(
         $this->getType()."1" => __("Last synchronization of computers by month","mydashboard"),
      );
   }

   function getWidgetContentForItem($widgetId) {
      global $CFG_GLPI, $DB;
      
      if (empty($this->form))
         $this->init();
      switch ($widgetId) {
            case $this->getType()."1":
               $plugin = new Plugin();
               if ($plugin->isActivated("ocsinventoryng")) {
                  
                  $query = "SELECT DISTINCT
                           DATE_FORMAT(`glpi_plugin_ocsinventoryng_ocslinks`.`last_update`, '%b %Y') as period_name,
                           COUNT(`glpi_plugin_ocsinventoryng_ocslinks`.`id`) as nb,
                           DATE_FORMAT(`glpi_plugin_ocsinventoryng_ocslinks`.`last_update`, '%y%m') as period
                        FROM `glpi_plugin_ocsinventoryng_ocslinks`
                        LEFT JOIN `glpi_computers`
                           ON `glpi_computers`.`id`=`glpi_plugin_ocsinventoryng_ocslinks`.`computers_id`
                        WHERE glpi_computers.is_deleted = '0'";

                  $query .= getEntitiesRestrictRequest("AND", Computer::getTable())
                           ." GROUP BY period_name ORDER BY period ASC";
                  
                  $widget = PluginMydashboardHelper::getWidgetsFromDBQuery('vbarchart',$query );
                  
                  //$dropdown = PluginMydashboardHelper::getFormHeader($widgetId).Group::dropdown(array('name' => 'groups_id',
                  //                                                                                    'display' => false,
                  //                                                                                    'value' => isset($this->options['groups_id'])? $this->options['groups_id'] : 0,
                  //                                                                                    'entity'    => $_SESSION['glpiactiveentities'],
                  //                                                                                    'condition' => '`is_assign`'))
                              //."</form>";
                  $widget->setWidgetTitle(__("Last synchronization of computers by month","mydashboard"));
                  $widget->setOption("xaxis", array("ticks" => PluginMydashboardBarChart::getTicksFromLabels($widget->getTabDatas())));
                  $widget->setOption("markers", array("show" => true,"position" => "ct" ,"labelFormatter" => PluginMydashboardBarChart::getLabelFormatter(2)));
                  $widget->setOption('legend', array('show' => false));
                  //$widget->appendWidgetHtmlContent($dropdown);
                  $widget->toggleWidgetRefresh();
                  return $widget;
               } else {
                  $widget = new PluginMydashboardDatatable();
                  $widget->setWidgetTitle(__("Last synchronization of computers by month","mydashboard"));
                  return $widget;
               }
             break;
      }
   }
}