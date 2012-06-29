<?php
/*
 * @version $Id: HEADER 14684 2011-06-11 06:32:40Z remi $
 -------------------------------------------------------------------------
 ocinventoryng - TreeView browser plugin for GLPI
 Copyright (C) 2012 by the ocinventoryng Development Team.

 https://forge.indepnet.net/projects/ocinventoryng
 -------------------------------------------------------------------------

 LICENSE

 This file is part of ocinventoryng.

 ocinventoryng is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 ocinventoryng is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with ocinventoryng; If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
*/

class PluginOcsinventoryngDetail extends CommonDBTM {


   function getSearchOptions() {

      $tab                    = array ();

      $tab['common']          = _n('Not imported computer', 'Not imported computers', 2);

      $tab[1]['table']        = $this->getTable();
      $tab[1]['field']        = 'ocsid';
      $tab[1]['name']         = __('ID');
      $tab[1]['datatype']     = 'integer';

      $tab[2]['table']        = $this->getTable();
      $tab[2]['field']        = 'plugin_ocsinventoryng_ocsservers_id';
      $tab[2]['name']         = __('Server');
      $tab[2]['searchtype']   = 'equals';

      $tab[3]['table']        = $this->getTable();
      $tab[3]['field']        = 'process_time';
      $tab[3]['name']         = __('Process time');
      $tab[3]['datatype']     = 'datetime';

      $tab[4]['table']        = $this->getTable();
      $tab[4]['field']        = 'action';
      $tab[4]['name']         = __('Action');
      $tab[4]['searchtype']   = 'equals';

      $tab[5]['table']        = 'glpi_plugin_ocsinventoryng_threads';
      $tab[5]['field']        = 'processid';
      $tab[5]['name']         = __('Process');
      $tab[5]['datatype']     = 'integer';

      $tab[6]['table']        = $this->getTable();
      $tab[6]['field']        = 'computers_id';
      $tab[6]['name']         = _n('Computer', 'Computers', 1);

      $tab[7]['table']        = $this->getTable();
      $tab[7]['field']        = 'threadid';
      $tab[7]['name']         = __('Thread');
      $tab[7]['datatype']     = 'integer';

      $tab[8]['table']        = $this->getTable();
      $tab[8]['field']        = 'rules_id';
      $tab[8]['name']         = __('Rules checked');
      $tab[8]['datatype']     = 'text';

      $tab[80]['table']       = 'glpi_entities';
      $tab[80]['field']       = 'completename';
      $tab[80]['name']        = __('Entity');
      $tab[80]['searchtype']  = 'equals';

      return $tab;
   }


   /**
    * @param $ocsid
    * @param $ocsservers_id
    * @param $action
    * @param $threadid
    * @param $threads_id
   **/
   function logProcessedComputer ($ocsid, $ocsservers_id, $action, $threadid, $threads_id) {

      $input["ocsid"] = $ocsid;
      if (isset($action["rule_matched"])) {
         $input["rules_id"] = json_encode($action['rule_matched']);
      } else {
         $input["rules_id"] = "";
      }
      $input["threadid"]                              = $threadid;
      $input["plugin_ocsinventoryng_threads_id"]      = $threads_id;
      $input["plugin_ocsinventoryng_ocsservers_id"]   = $ocsservers_id;
      $input["action"]                                = $action['status'];
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
      $temp->deleteByCriteria(array('plugin_ocsinventoryng_threads_id' => $threads_id));
   }


   /**
    * @param $action
   **/
   static function giveActionNameByActionID($action) {

      $actions = self::getActions();
      if (isset($actions[$action])) {
         return $actions[$action];
      }
      return '';
   }


   static function getActions() {

      return array(PluginOcsinventoryngOcsServer::COMPUTER_FAILED_IMPORT
                                                            => _n('Not imported computer',
                                                                  'Not imported computers', 2),
                   PluginOcsinventoryngOcsServer::COMPUTER_IMPORTED
                                                            => __('Imported computers'),
                   PluginOcsinventoryngOcsServer::COMPUTER_LINKED
                                                            => __('Linked computers'),
                   PluginOcsinventoryngOcsServer::COMPUTER_NOTUPDATED
                                                            => __('Not updated computers'),
                   PluginOcsinventoryngOcsServer::COMPUTER_SYNCHRONIZED
                                                            => __('Synchronized computers'),
                   PluginOcsinventoryngOcsServer::COMPUTER_NOT_UNIQUE
                                                            => __('Not unique computers'),
                   PluginOcsinventoryngOcsServer::COMPUTER_LINK_REFUSED
                                                            => __('Import refused by rule'));
   }


   /**
    * @param $name
    * @param $value     (default 0)
   **/
   static function showActions($name, $value=0) {

      $actions = self::getActions();
      Dropdown::showFromArray($name, $actions, array('value' => $value));
   }

}
?>