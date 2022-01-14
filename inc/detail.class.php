<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2022 by the ocsinventoryng Development Team.

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
 * Class PluginOcsinventoryngDetail
 */
class PluginOcsinventoryngDetail extends CommonDBTM {


   /**
    * @return array
    */
   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id'   => 'common',
         'name' => _n('Computer not imported', 'Computers not imported', 2,
                      'ocsinventoryng')
      ];

      $tab[] = [
         'id'       => '1',
         'table'    => $this->getTable(),
         'field'    => 'ocsid',
         'name'     => __('OCSNG ID', 'ocsinventoryng'),
         'datatype' => 'integer'
      ];

      $tab[] = [
         'id'         => '2',
         'table'      => 'glpi_plugin_ocsinventoryng_ocsservers',
         'field'      => 'name',
         'name'       => __('Server'),
         'searchtype' => 'equals'
      ];

      $tab[] = [
         'id'       => '3',
         'table'    => $this->getTable(),
         'field'    => 'process_time',
         'name'     => __('Process time', 'ocsinventoryng'),
         'datatype' => 'datetime'
      ];

      $tab[] = [
         'id'         => '4',
         'table'      => $this->getTable(),
         'field'      => 'action',
         'name'       => __('Action type'),
         'searchtype' => 'equals'
      ];

      $tab[] = [
         'id'       => '5',
         'table'    => 'glpi_plugin_ocsinventoryng_threads',
         'field'    => 'processid',
         'name'     => __('Process', 'ocsinventoryng'),
         'datatype' => 'integer'
      ];

      $tab[] = [
         'id'    => '6',
         'table' => $this->getTable(),
         'field' => 'computers_id',
         'name'  => _n('Computer', 'Computers', 1)
      ];

      $tab[] = [
         'id'       => '7',
         'table'    => $this->getTable(),
         'field'    => 'threadid',
         'name'     => __('Thread', 'ocsinventoryng'),
         'datatype' => 'integer'
      ];

      $tab[] = [
         'id'       => '8',
         'table'    => $this->getTable(),
         'field'    => 'rules_id',
         'name'     => __('Rules checked', 'ocsinventoryng'),
         'datatype' => 'text'
      ];

      $tab[] = [
         'id'         => '80',
         'table'      => 'glpi_entities',
         'field'      => 'completename',
         'name'       => __('Entity'),
         'datatype'   => 'dropdown',
         'searchtype' => 'equals'
      ];

      return $tab;
   }


   /**
    * @param $ocsid
    * @param $ocsservers_id
    * @param $action
    * @param $threadid
    * @param $threads_id
    **/
   function logProcessedComputer($ocsid, $ocsservers_id, $action, $threadid, $threads_id) {

      $input["ocsid"] = $ocsid;
      if (isset($action["rule_matched"])) {
         $input["rules_id"] = json_encode($action['rule_matched']);
      } else {
         $input["rules_id"] = "";
      }
      $input["threadid"]                            = $threadid;
      $input["plugin_ocsinventoryng_threads_id"]    = $threads_id;
      $input["plugin_ocsinventoryng_ocsservers_id"] = $ocsservers_id;
      $input["action"]                              = $action['status'];
      if (isset($action["entities_id"])) {
         $input["entities_id"] = $action['entities_id'];
      } else {
         $input['entities_id'] = 0;
      }
      if (isset($action['computers_id'])) {
         $comp = new Computer();
         if ($comp->getFromDB($action['computers_id'])) {
            $input['computers_id'] = $comp->getID();
            $input['entities_id']  = $comp->getEntityID();
         }
      }
      $input["process_time"] = date("Y-m-d H:i:s");

      $this->add($input);
   }


   /**
    * @param $threads_id
    **/
   static function deleteThreadDetailsByProcessID($threads_id) {

      $temp = new self();
      $temp->deleteByCriteria(['plugin_ocsinventoryng_threads_id' => $threads_id]);
   }


   /**
    * @param $action
    *
    * @return mixed|string
    */
   static function giveActionNameByActionID($action) {

      $actions = self::getActions();
      if (isset($actions[$action])) {
         return $actions[$action];
      }
      return '';
   }


   /**
    * @return array
    */
   static function getActions() {

      return [PluginOcsinventoryngOcsProcess::COMPUTER_FAILED_IMPORT
              => _n('Computer not imported',
                    'Computers not imported', 2,
                    'ocsinventoryng'),
              PluginOcsinventoryngOcsProcess::COMPUTER_IMPORTED
              => __('Computers imported',
                    'ocsinventoryng'),
              PluginOcsinventoryngOcsProcess::COMPUTER_LINKED
              => __('Computers linked',
                    'ocsinventoryng'),
              PluginOcsinventoryngOcsProcess::COMPUTER_NOTUPDATED
              => __('Computers not updated',
                    'ocsinventoryng'),
              PluginOcsinventoryngOcsProcess::COMPUTER_SYNCHRONIZED
              => __('Computers synchronized',
                    'ocsinventoryng'),
              PluginOcsinventoryngOcsProcess::COMPUTER_NOT_UNIQUE
              => __('Computers not unique',
                    'ocsinventoryng'),
              PluginOcsinventoryngOcsProcess::COMPUTER_LINK_REFUSED
              => __('Import refused by rule')];
   }


   /**
    * @param $name
    * @param $value (default 0)
    **/
   static function showActions($name, $value = 0) {

      $actions = self::getActions();
      Dropdown::showFromArray($name, $actions, ['value' => $value]);
   }

}
