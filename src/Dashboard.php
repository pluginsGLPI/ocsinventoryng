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

use AllowDynamicProperties;
use GlpiPlugin\Mydashboard\Chart;
use GlpiPlugin\Mydashboard\Charts\BarChart;
use GlpiPlugin\Mydashboard\Charts\PieChart;
use GlpiPlugin\Mydashboard\Datatable;
use GlpiPlugin\Mydashboard\Helper;
use GlpiPlugin\Mydashboard\Html as MydashboardHtml;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Widget;
use Plugin;
use Toolbox;

/**
 * Class Dashboard
 */
#[AllowDynamicProperties]
class Dashboard extends MydashboardHtml
{
    public $widgets = [];
    private $options;
    private $form;

    /**
     * Dashboard constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->options    = $options;
        $this->interfaces = ["central"];
    }

    public function init()
    {
    }


     /**
      * @return \array[][]
      */
    public static function getWidgetsForItem()
    {
        $widgets = [
            Menu::$INVENTORY => [
                self::getType() . "1" => ["id" => 1,
                                           "title"   => __("Last synchronization of computers by month", "ocsinventoryng"),
                                           "type"    => Widget::$BAR,
                                           "comment" => __("Display synchronization of computers by month", "ocsinventoryng")],
                self::getType() . "2" => ["id" => 2,
                                           "title"   => __("Detail of imported computers", "ocsinventoryng"),
                                           "type"    => Widget::$PIE,
                                           "comment" => __("Number of OCSNG computers, Fusion Inventory computer, without agent computers", "ocsinventoryng")],
            ],
        ];

        return $widgets;
    }

    /**
     * @param $widgetId
     *
     * @return Datatable|HBarChart|Html|LineChart|PieChart|VBarChart
     */
    public function getWidgetContentForItem($widgetId, $opt = [])
    {
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
                $options = Helper::manageCriterias($params);

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
                $result       = $DB->doQuery($query);
                $nb           = $DB->numrows($result);

                $nbcomputers     = __('Computers number', 'ocsinventoryng');

                $tabdata      = [];
                $tabnames     = [];
                $tabsyncdates = [];
                if ($nb) {
                    while ($data = $DB->fetchAssoc($result)) {
                        $tabdata['data'][] = $data['nb'];
                        $tabdata['type']   = 'bar';
                        $tabdata['name']   = $nbcomputers;
                        $tabnames[]     = $data['periodsync_name'];
                        $tabsyncdates[] = $data['periodsync'];
                    }
                }

                $widget = new parent();
                $widgets = self::getWidgetsForItem();
                $title   = __("Last synchronization of computers by month", "ocsinventoryng");
                $comment = __("Display synchronization of computers by month", "ocsinventoryng");
                $widget->setWidgetTitle($title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();


                $dataBarset = json_encode($tabdata);
                $labelsBar  = json_encode($tabnames);
                $tabsyncset = json_encode($tabsyncdates);

                $graph_datas = ['title'   => $title,
                                'comment' => $comment,
                                'name'            => $name,
                                'ids'             => $tabsyncset,
                                'data'            => $dataBarset,
                                'labels'          => $labelsBar];

                $graph = BarChart::launchGraph($graph_datas, []);

                $params = ["widgetId"  => $widgetId,
                           "name"      => $name,
                           "onsubmit"  => false,
                           "opt"       => $opt,
                           "criterias" => $criterias,
                           "export"    => true,
                           "canvas"    => true,
                           "nb"        => $nb];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));
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
                $options = Helper::manageCriterias($params);

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

                $result = $DB->doQuery($query);
                $nb     = $DB->numrows($result);

                if ($nb) {
                    while ($data = $DB->fetchAssoc($result)) {
//                  $counts[]     = $data["nb"];
                        $counts[] = ['value' => $data['nb'],
                                     'name' =>  __('OCS Inventory NG', 'ocsinventoryng')];
                        $name_agent[] = __('OCS Inventory NG', 'ocsinventoryng');
                    }
                }

//                if (Plugin::isPluginActive("fusioninventory")) {
//                    $query = "SELECT DISTINCT `glpi_computers`.`id`, COUNT(`glpi_computers`.`id`) AS nb
//                                 FROM `glpi_computers`
//                                 LEFT JOIN `glpi_plugin_fusioninventory_inventorycomputercomputers`
//                                 ON (`glpi_computers`.`id` = `glpi_plugin_fusioninventory_inventorycomputercomputers`.`computers_id` )
//                                 WHERE `glpi_computers`.`is_deleted` = 0
//                                 AND `glpi_computers`.`is_template` = 0
//                                 AND `glpi_computers`.`entities_id` = " . $_SESSION["glpiactive_entity"];
//
//                    //$query .= getEntitiesRestrictRequest("AND", Computer::getTable())
//                    $query .= " AND ( `glpi_plugin_fusioninventory_inventorycomputercomputers`.`last_fusioninventory_update` NOT LIKE '' )";
//
//                    $result = $DB->doQuery($query);
//                    $nb     = $DB->numrows($result);
//
//                    if ($nb) {
//                        while ($data = $DB->fetchAssoc($result)) {
////                     $counts[]     = $data["nb"];
//                            $name_agent[] = __('Fusion Inventory', 'ocsinventoryng');
//                            $counts[] = ['value' => $data['nb'],
//                                        'name' =>  __('Fusion Inventory', 'ocsinventoryng')];
//                        }
//                    }
//                }
//                if (Plugin::isPluginActive("fusioninventory")) {
//                    $query = "SELECT DISTINCT `glpi_computers`.`id`, COUNT(`glpi_computers`.`id`) AS nb
//                              FROM `glpi_computers`
//                              LEFT JOIN `glpi_plugin_ocsinventoryng_ocslinks`
//                              ON (`glpi_computers`.`id` = `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id` )
//                              LEFT JOIN `glpi_plugin_fusioninventory_inventorycomputercomputers`
//                              ON (`glpi_computers`.`id` = `glpi_plugin_fusioninventory_inventorycomputercomputers`.`computers_id` )
//                              WHERE `glpi_computers`.`is_deleted` = 0
//                              AND `glpi_computers`.`is_template` = 0
//                              AND `glpi_computers`.`entities_id` = " . $_SESSION["glpiactive_entity"];
//
//                    //$query .= getEntitiesRestrictRequest("AND", Computer::getTable())
//                    $query .= " AND ( (`glpi_plugin_ocsinventoryng_ocslinks`.`last_update` LIKE '' OR `glpi_plugin_ocsinventoryng_ocslinks`.`last_update` IS NULL) AND (`glpi_plugin_fusioninventory_inventorycomputercomputers`.`last_fusioninventory_update` LIKE '' OR `glpi_plugin_fusioninventory_inventorycomputercomputers`.`last_fusioninventory_update` IS NULL) )";
//                } else {
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
//                }
                $result = $DB->doQuery($query);
                $nb     = $DB->numrows($result);

                if ($nb) {
                    while ($data = $DB->fetchAssoc($result)) {
//                  $counts[]     = $data["nb"];
                        $name_agent[] = __('Without agent', 'ocsinventoryng');
                        $counts[] = ['value' => $data['nb'],
                                     'name' =>  __('Without agent', 'ocsinventoryng')];
                    }
                }

                $widget = new parent();
                $widgets = self::getWidgetsForItem();
                $title   = __("Detail of imported computers", "ocsinventoryng");
                $comment = __("Number of OCSNG computers, Fusion Inventory computer, without agent computers", "ocsinventoryng");
                $widget->setWidgetTitle($title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $dataPieset         = json_encode($counts);
                $labelsPie          = json_encode($name_agent);

                $graph_datas = ['title'   => $title,
                                'comment' => $comment,
                                'name'            => $name,
                                'ids'             => json_encode([]),
                                'data'            => $dataPieset,
                                'labels'          => $labelsPie,
                                'label'           => $title];

            //            if ($onclick == 1) {
                $graph_criterias = ['widget' => $widgetId];
            //            }

                $graph = PieChart::launchPieGraph($graph_datas, $graph_criterias);

                $params = ["widgetId"  => $widgetId,
                           "name"      => $name,
                           "onsubmit"  => false,
                           "opt"       => [],
                           "criterias" => [],
                           "export"    => true,
                           "canvas"    => true,
                           "nb"        => $nb];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;
                break;
        }
    }

    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginOcsinventoryngDashboard1link($params)
    {
        global $CFG_GLPI;

        $options['reset'][] = 'reset';

        $options = Chart::addCriteria(10002, 'contains', $params["params"]["dateinv"], 'AND');

        return  $CFG_GLPI["root_doc"] . '/front/computer.php?is_deleted=0&' .
                Toolbox::append_params($options, "&");

    }
}
