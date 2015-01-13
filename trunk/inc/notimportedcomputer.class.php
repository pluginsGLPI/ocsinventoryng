<?php
/*
 * @version $Id: HEADER 15930 2012-12-15 11:10:55Z tsmr $
-------------------------------------------------------------------------
Ocsinventoryng plugin for GLPI
Copyright (C) 2012-2013 by the ocsinventoryng plugin Development Team.

https://forge.indepnet.net/projects/ocsinventoryng
-------------------------------------------------------------------------

LICENSE

This file is part of ocsinventoryng.

Ocsinventoryng plugin is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

Ocsinventoryng plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with ocsinventoryng. If not, see <http://www.gnu.org/licenses/>.
-------------------------------------------------------------------------- */

class PluginOcsinventoryngNotimportedcomputer extends CommonDropdown {

   // From CommonDBTM
   public $dohistory          = true;
   static $rightname = "plugin_ocsinventoryng";
   public $first_level_menu   = "plugins";
   public $second_level_menu  = "ocsinventoryng";


   static function getTypeName($nb=0) {
      return _n('Computer not imported', 'Computers not imported', $nb, 'ocsinventoryng');
   }


   function getAdditionalFields() {

      $can_update = PluginOcsinventoryngConfig::canUpdateOCS();
      return array(array('name'  => 'reason',
                         'label' => __('Reason of rejection'),
                         'type'  => 'reason'),
                   array('name'  => 'rules_id',
                         'label' => __('Verified rules', 'ocsinventoryng'),
                         'type'  => 'echo_rule'),
                   array('name'  => 'ocsid',
                         'label' => __('OCSNG ID', 'ocsinventoryng'),
                         'type'  => 'echo'),
                   array('name'  => 'plugin_ocsinventoryng_ocsservers_id',
                         'label' => __('Server'),
                         'type'  => 'echo_dropdown',
                         'table' => 'glpi_plugin_ocsinventoryng_ocsservers'),
                   array('name'  => 'ocs_deviceid',
                         'label' => __('Device ID', 'ocsinventoryng'),
                         'type'  => 'echo'),
                   array('name'  => 'serial',
                         'label' => __('Serial number'),
                         'type'  => ($can_update?'text':'echo')),
                   array('name'  => 'tag',
                         'label' => __('OCSNG TAG', 'ocsinventoryng'),
                         'type'  => ($can_update?'text':'echo')),
                   array('name'  => 'useragent',
                         'label' => __('Inventory agent', 'ocsinventoryng'),
                         'type'  => 'echo'),
                   array('name'  => 'ipaddr',
                         'label' => __('IP'),
                        'type'   => 'echo'),
                   array('name'  => 'domain',
                         'label' => __('Domain'),
                         'type'  => 'echo'),
                   array('name'  => 'last_inventory',
                         'label' => __('Last OCSNG inventory date', 'ocsinventoryng'),
                         'type'  => 'echo_datetime'));
   }


   /**
    * Add more tabs to display
    *
    * @param $options   array
   **/
   function defineMoreTabs($options=array()) {

      $ong = array();
      if (PluginOcsinventoryngOcsServer::getComputerLinkToOcsConsole($this->fields['plugin_ocsinventoryng_ocsservers_id'],
                                                                     $this->fields['ocsid'], '',
                                                                     true) != '') {
         $ong[3]  = __('OCSNG console');
      }
      $ong[12] = _n('Log', 'Logs', 2);
      return $ong;
   }


   /**
    * Display fields that are specific to this itemtype
    *
    * @param ID               the item's ID
    * @param field   array    the item's fields
    *
    * @return nothing
   **/
   function displaySpecificTypeField($ID, $field=array()) {

      switch ($field['type']) {
         case 'echo' :
            echo $this->fields[$field['name']];
            break;

         case 'reason' :
            echo self::getReason($this->fields[$field['name']]);
            break;

         case 'echo_datetime' :
            echo Html::convDateTime($this->fields[$field['name']]);
            break;

         case 'echo_dropdown' :
            $result = Dropdown::getDropdownName($field['table'],$this->fields[$field['name']]);
            if ($result == '') {
               echo Dropdown::EMPTY_VALUE;
            } else {
               echo $result;
            }
            break;

         case 'echo_rule':
            echo self::getRuleMatchedMessage($this->fields[$field['name']]);
            break;
      }
   }


   /**
    * @param $rule_list
   **/
   static function getRuleMatchedMessage($rule_list) {

      $message = array();
      if ($rule_list != '') {
         foreach (json_decode($rule_list,true) as $key => $value) {
            if ($rule = getItemForItemtype($key)) {
            
               $rule = new $key();
               if ($rule->can($value,READ)) {
                  $url = $rule->getLinkURL();
                  $message[] = "<a href='$url'>".$rule->getName()."</a>";
               }
            }
         }
         return implode(' => ',$message);
      }
      return '';
   }


   function displayOcsConsole() {

      $url = PluginOcsinventoryngOcsServer::getComputerLinkToOcsConsole($this->fields['plugin_ocsinventoryng_ocsservers_id'],
                                                                        $this->fields['ocsid'], '',
                                                     true);
      echo "<div class='center'>";
      if ($url != '') {
         echo "<iframe src='$url' width='80%' height='60%'>";
      }
      echo "</div>";
   }


   /**
    * Display more tabs
    *
    * @param $tab
   **/
   function displayMoreTabs($tab) {

      switch ($tab) {
         case 1 :
            self::showActions($this);
            break;

         case 3 :
            $this->displayOcsConsole();
            break;

         case 12 :
            Log::showForItem($this);
            break;

         case -1 :
            self::showActions($this);
            Log::showForItem($this);
            break;
      }
   }


   function getSearchOptions() {

      $tab                       = array();

      $tab['common']             = _n('Computer not imported', 'Computers not imported', 2,
                                      'ocsinventoryng');

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'ocsid';
      $tab[1]['linkfield']       = '';
      $tab[1]['name']            = __('OCSNG ID', 'ocsinventoryng');

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'name';
      $tab[2]['linkfield']       = '';
      $tab[2]['name']            = __('OCSNG name', 'ocsinventoryng');
      $tab[2]['datatype']        = 'itemlink';
      $tab[2]['itemlink_type']   = $this->getType();
      $tab[2]['massiveaction']   = false;

      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'useragent';
      $tab[3]['linkfield']       = '';
      $tab[3]['name']            = __('Inventory agent', 'ocsinventoryng');

      $tab[4]['table']           = $this->getTable();
      $tab[4]['field']           = 'ocs_deviceid';
      $tab[4]['linkfield']       = '';
      $tab[4]['name']            = __('Device ID', 'ocsinventoryng');

      $tab[5]['table']           = 'glpi_plugin_ocsinventoryng_ocsservers';
      $tab[5]['field']           = 'name';
      $tab[5]['linkfield']       = 'plugin_ocsinventoryng_ocsservers_id';
      $tab[5]['name']            = __('Server');

      $tab[6]['table']           = $this->getTable();
      $tab[6]['field']           = 'tag';
      $tab[6]['linkfield']       = '';
      $tab[6]['name']            = __('OCSNG TAG', 'ocsinventoryng');

      $tab[7]['table']           = $this->getTable();
      $tab[7]['field']           = 'ipaddr';
      $tab[7]['linkfield']       = '';
      $tab[7]['name']            = __('IP');

      $tab[8]['table']           = $this->getTable();
      $tab[8]['field']           = 'domain';
      $tab[8]['linkfield']       = '';
      $tab[8]['name']            = __('Domain');

      $tab[9]['table']           = $this->getTable();
      $tab[9]['field']           = 'last_inventory';
      $tab[9]['linkfield']       = '';
      $tab[9]['name']            = __('Last OCSNG inventory date', 'ocsinventoryng');
      $tab[9]['datatype']        = 'datetime';

      $tab[10]['table']          = $this->getTable();
      $tab[10]['field']          = 'reason';
      $tab[10]['linkfield']      = '';
      $tab[10]['name']           = __('Reason of rejection');

      $tab[11]['table']          = $this->getTable();
      $tab[11]['field']          = 'serial';
      $tab[11]['linkfield']      = '';
      $tab[11]['name']           = __('Serial number');

      $tab[80]['table']          = 'glpi_entities';
      $tab[80]['field']          = 'completename';
      $tab[80]['name']           = __('Entity');

      return $tab;
   }


   /**
    * @param $ocsservers_id
    * @param $ocsid
    * @param $reason
   **/
   function logNotImported($ocsservers_id,$ocsid,$reason) {
      global $DB;

      PluginOcsinventoryngOcsServer::checkOCSconnection($ocsservers_id);
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($ocsservers_id);
               $options = array(
                       "DISPLAY"=> array(
                       "CHECKSUM"=> PluginOcsinventoryngOcsClient::CHECKSUM_HARDWARE 
                               |    PluginOcsinventoryngOcsClient::CHECKSUM_BIOS
                   )
                 );
      $computer = $ocsClient->getComputer($ocsid,$options);


      if ($computer["HARDWARE"] && $computer["BIOS"]) {
         $input["_ocs"]                                  = true;
         $input["name"]                                  = $computer["HARDWARE"]["NAME"];
         $input["domain"]                                = $computer["HARDWARE"]["WORKGROUP"];
         $input["tag"]                                   = $computer["META"]["TAG"];
         $input["ocs_deviceid"]                          = $computer["HARDWARE"]["DEVICEID"];
         $input["ipaddr"]                                = $computer["HARDWARE"]["IPADDR"];
         $input["plugin_ocsinventoryng_ocsservers_id"]   = $ocsservers_id;
         $input["ocsid"]                                 = $ocsid;
         $input["last_inventory"]                        = $computer["HARDWARE"]["LASTCOME"];
         $input["useragent"]                             = $computer["HARDWARE"]["USERAGENT"];
         $input["serial"]                                = $computer["BIOS"]["SSN"];
         $input["reason"]                                = $reason['status'];
         $input["comment"]                               = "";
         if (isset($reason['entities_id'])) {
            $input["entities_id"]= $reason['entities_id'];
         } else {
            $input['entities_id'] = 0;
         }
         $input["rules_id"] = json_encode($reason['rule_matched']);

         $query = "SELECT `id` FROM `".$this->getTable()."` " .
                  "WHERE `ocs_deviceid`='".$input["ocs_deviceid"]."'
                     AND `plugin_ocsinventoryng_ocsservers_id`='$ocsservers_id'";
         $result = $DB->query($query);
         if ($DB->numrows($result) > 0) {
            $input['id'] = $DB->result($result,0,'id');
            $this->update($input);
         } else {
            $this->add($input);
         }

      }
   }


   /**
    * @param $ocsservers_id   (default -1)
    * @param $ocsid           (default -1)
   **/
   function cleanNotImported($ocsservers_id=-1, $ocsid=-1) {
      global $DB;

      $first = true;

      $sql = "DELETE
              FROM `".$this->getTable()."`";

      if ($ocsservers_id != -1) {
         $sql  .= " WHERE `plugin_ocsinventoryng_ocsservers_id` = '$ocsservers_id'";
         $first = false;
      }
      if ($ocsid != -1) {
         $sql  .= ($first?" WHERE":" AND")." `ocsid` = '$ocsid'";
         $first = false;
      }
      if ($first) {
         // Use truncate to reset id
         $sql = "TRUNCATE `".$this->getTable()."`";
      }
      $result = $DB->query($sql);

      return ($result ? $DB->affected_rows() : -1);
   }


   /**
    * Delete a row in the notimported table
    *
    * @param not_imported_id if of the computer that is not imported in GLPI
    *
    * @return nothing
   **/
   function deleteNotImportedComputer($not_imported_id) {

      if ($this->getFromDB($not_imported_id)) {
         PluginUninstallUninstall::deleteComputerInOCS($this->fields["ocsid"],
                                                       $this->fields["plugin_ocsinventoryng_ocsservers_id"]);
         $fields["id"] = $not_imported_id;
         $this->delete($fields);
      }
   }


   /**
    * @param $reason
   *-*/
   static function getReason($reason) {

      switch ($reason) {
         case PluginOcsinventoryngOcsServer::COMPUTER_FAILED_IMPORT :
            return __("Can't affect an entity", 'ocsinventoryng');

         case PluginOcsinventoryngOcsServer::COMPUTER_NOT_UNIQUE :
            return __('Unicity criteria not verified', 'ocsinventoryng');

         case PluginOcsinventoryngOcsServer::COMPUTER_LINK_REFUSED :
            return __('Import refused by rule', 'ocsinventoryng');

         default:
               return "";
      }
   }


   /**
    * @param $notimported  PluginOcsinventoryngNotimportedcomputer object
   **/
   static function showActions(PluginOcsinventoryngNotimportedcomputer $notimported) {

      echo "<div class='spaced'>";
      echo "<form name='actions' id='actions' method='post' value='".getItemTypeFormURL(__CLASS__)."'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'>";
      echo "<th class='center'>".__('Actions to be made on the computer', 'ocsinventoryng').
           "</th></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>";
      echo "<input type='hidden' name='id' value='".$notimported->fields['id']."'>";
      echo "<input type='hidden' name='action' value='massive'>";
      Dropdown::showForMassiveAction('PluginOcsinventoryngNotimportedcomputer', 0,
                                     array('action' => 'massive'));
      echo "</td></tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }


   /**
    * @param $params array
   **/
   static function computerImport($params=array()) {

      if (isset($params['id'])) {
         $notimported = new PluginOcsinventoryngNotimportedcomputer;
         $notimported->getFromDB($params['id']);
         $changes     = self::getOcsComputerInfos($notimported->fields);
         if (isset($params['force'])) {
            $result = PluginOcsinventoryngOcsServer::processComputer($notimported->fields['ocsid'],
                                                                     $notimported->fields['plugin_ocsinventoryng_ocsservers_id'],
                                                                     0, $params['entity'], 0);
         } else {
            $result = PluginOcsinventoryngOcsServer::processComputer($notimported->fields['ocsid'],
                                                                     $notimported->fields['plugin_ocsinventoryng_ocsservers_id'],0,-1,-1);
         }

         if (in_array($result['status'],
                      array(PluginOcsinventoryngOcsServer::COMPUTER_IMPORTED,
                            PluginOcsinventoryngOcsServer::COMPUTER_LINKED,
                            PluginOcsinventoryngOcsServer::COMPUTER_SYNCHRONIZED))) {
            $notimported->delete(array('id' => $params['id']));

            //If serial has been changed in order to import computer
            if (in_array('serial',$changes)) {
               PluginOcsinventoryngOcsServer::mergeOcsArray($result['computers_id'],
                                                            array('serial'), "computer_update");
            }
            
            
            return true;
         } else {
            Session::addMessageAfterRedirect(self::getReason($result['status']),
         false, ERROR);
            return false;
         }

         $tmp           = $notimported->fields;
         $tmp['reason'] = $result['status'];
         if (isset($result['entities_id'])) {
            $tmp["entities_id"] = $result['entities_id'];
         } else {
               $tmp['entities_id'] = 0;
         }
         $tmp["rules_id"] = json_encode($result['rule_matched']);
         $notimported->update($tmp);
         return false;
      }
   }


   /**
    * @param $params array
   **/
   static function linkComputer($params=array()) {

      if (isset($params['id'])) {
         $notimported = new PluginOcsinventoryngNotimportedcomputer;
         $notimported->getFromDB($params['id']);
         $changes     = self::getOcsComputerInfos($notimported->fields);

         if (PluginOcsinventoryngOcsServer::linkComputer($notimported->fields['ocsid'],
                                                         $notimported->fields['plugin_ocsinventoryng_ocsservers_id'],
                                                         $params['computers_id'])) {
            $notimported->delete(array('id' => $params['id']));
            //If serial has been changed in order to import computer
            if (in_array('serial',$changes)) {
               PluginOcsinventoryngOcsServer::mergeOcsArray($params['id'], array('serial'),
                                                            "computer_update");
            }
         }
      }
   }


   /**
    * @param $params array
   **/
   static function getOcsComputerInfos($params=array()) {
      PluginOcsinventoryngOcsServer::checkOCSconnection($params['plugin_ocsinventoryng_ocsservers_id']);
      $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($params['plugin_ocsinventoryng_ocsservers_id']);
               $options = array(
                       "DISPLAY"=> array(
                       "CHECKSUM"=>PluginOcsinventoryngOcsClient::CHECKSUM_BIOS
                   )
                 );
      $computer = $ocsClient->getComputer($params['ocsid'],$options);
      $changes = array();

      if ($computer) {
          $ocs_serial = $computer["BIOS"]["SSN"];
         if ($ocs_serial != $params['serial']) {
            $ocsClient->updateBios($params['serial'],$params['ocsid']);
            $changes[] = 'serial';
         }
         $ocs_tag = $computer["META"]["TAG"];
         if ($ocs_tag != $params['tag']) {
           $ocsClient->updateBios($params['tag'],$params['ocsid']);
           $changes[] = 'tag';
         }
      }

      return $changes;
   }


   static function sendAlert() {
      global $DB,$CFG_GLPI;

      if (!$CFG_GLPI["use_mailing"]) {
         return 0;
      }

      $message     = array();
      $items_infos = array();

     $query = "SELECT `glpi_plugin_ocsinventoryng_notimportedcomputers`.*
               FROM `glpi_plugin_ocsinventoryng_notimportedcomputers`
               LEFT JOIN `glpi_alerts`
                  ON (`glpi_plugin_ocsinventoryng_notimportedcomputers`.`id` = `glpi_alerts`.`items_id`
                      AND `glpi_alerts`.`itemtype` = 'PluginOcsinventoryngNotimportedcomputer'
                      AND `glpi_alerts`.`type` = '".Alert::END."')
               WHERE `glpi_alerts`.`date` IS NULL";

      foreach ($DB->request($query) as $notimported) {
         $items_infos[$notimported['entities_id']][$notimported['id']] = $notimported;
      }

      foreach ($items_infos as $entity => $items) {
         if (NotificationEvent::raiseEvent('not_imported', new PluginOcsinventoryngNotimportedcomputer(),
                                           array('entities_id' => $entity,
                                                 'notimported' => $items))) {
            $alert             = new Alert();
            $input["itemtype"] = 'PluginOcsinventoryngNotimportedcomputer';
            $input["type"]     = Alert::END;
            foreach ($items as $id => $item) {
               $input["items_id"] = $id;
               $alert->add($input);
               unset($alert->fields['id']);
            }
         } else {
            logDebug(__('%1$s: %2$s')."\n", Dropdown::getDropdownName("glpi_entities", $entity),
                     __('Send OCSNG not imported computers alert failed', 'ocsinventoryng'));
         }
      }
   }


   function cleanDBonPurge() {
      global $DB;

      $query = "DELETE
                FROM `glpi_alerts`
                WHERE `itemtype` = 'PluginOcsinventoryngNotimportedcomputer'
                      AND `items_id` = '".$this->fields['id']."'";
      $DB->query($query);
   }

   
   static function cronInfo($name) {
   
      switch ($name) {
         case "SendAlerts" :
            return array('description' => __('OCSNG', 'ocsinventoryng')." - ".__('Not imported computers alert', 'ocsinventoryng'));
        }
   }
        
   static function cronSendAlerts($task) {
      self::sendAlert();
      $task->setVolume(1);
   }
   
   /**
    * @since version 0.85
    *
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $actions = parent::getSpecificMassiveActions($checkitem);

      return $actions;
   }
   
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'plugin_ocsinventoryng_import':
            Entity::dropdown(array('name' => 'entity'));
            echo "&nbsp;".
                 Html::submit(_x('button','Post'), array('name' => 'massiveaction'));
            return true;
         /*case 'plugin_ocsinventoryng_link':
            Computer::dropdown(array('name' => 'computers_id'));
            echo "&nbsp;".
                 Html::submit(_x('button','Post'), array('name' => 'massiveaction'));
            return true;*/
         case 'plugin_ocsinventoryng_replayrules':
         case 'plugin_ocsinventoryng_delete':
            echo "&nbsp;".
                 Html::submit(_x('button','Post'), array('name' => 'massiveaction'));
            return true;
    }
      return parent::showMassiveActionsSubForm($ma);
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {
      global $DB;
      
      $notimport = new PluginOcsinventoryngNotimportedcomputer();
      
      switch ($ma->getAction()) {
         case "plugin_ocsinventoryng_import":
            $input = $ma->getInput();
            
            foreach ($ids as $id) {
               if (PluginOcsinventoryngNotimportedcomputer::computerImport(array('id'     => $id,
                                                                                'force'  => true,
                                                                                'entity' => $input['entity']))) {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
               } else {
                  $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
               }
            }

            return;
         
         case "plugin_ocsinventoryng_replayrules" :
            $input = $ma->getInput();
            foreach ($ids as $id) {
               if (PluginOcsinventoryngNotimportedcomputer::computerImport(array('id' => $id))) {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
               } else {
                  $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
               }
            }
            return;

         case "plugin_ocsinventoryng_delete" :
            $input = $ma->getInput();
            foreach ($ids as $id) {
               if ($notimport->deleteNotImportedComputer($id)) {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
               } else {
                  $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
               }
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }
}
?>