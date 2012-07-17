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

class PluginOcsinventoryngNotimported extends CommonDropdown {

   // From CommonDBTM
   public $dohistory          = true;

   public $first_level_menu   = "plugins";
   public $second_level_menu  = "ocsinventoryng";


   static function getTypeName($nb=0) {
      return _n('Not imported computer', 'Not imported computers', $nb);
   }


   function getAdditionalFields() {

      $can_update = PluginOcsinventoryngConfig::canUpdateOCS();
      return array(array('name'  => 'reason',
                         'label' => __('Reason of rejection'),
                         'type'  => 'reason'),
                   array('name'  => 'rules_id',
                         'label' => __('Verified rules'),
                         'type'  => 'echo_rule'),
                   array('name'  => 'ocsid',
                         'label' => __('OCSNG ID'),
                         'type'  => 'echo'),
                   array('name'  => 'plugin_ocsinventoryng_ocsservers_id',
                         'label' => __('Server'),
                         'type'  => 'echo_dropdown',
                         'table' => 'glpi_plugin_ocsinventoryng_ocsservers'),
                   array('name'  => 'ocs_deviceid',
                         'label' => __('Device ID'),
                         'type'  => 'echo'),
                   array('name'  => 'serial',
                         'label' => __('Serial number'),
                         'type'  => ($can_update?'text':'echo')),
                   array('name'  => 'tag',
                         'label' => __('TAG'),
                         'type'  => ($can_update?'text':'echo')),
                   array('name'  => 'useragent',
                         'label' => __('Inventory agent'),
                         'type'  => 'echo'),
                   array('name'  => 'ipaddr',
                         'label' => __('IP'),
                        'type'   => 'echo'),
                   array('name'  => 'domain',
                         'label' => __('Domain'),
                         'type'  => 'echo'),
                   array('name'  => 'last_inventory',
                         'label' => __('Last OCSNG inventory date'),
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
      $ong[12] = __('Logs');
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
            if ($rule = getItemForItemtype($key)
                && $rule->can($value,'r')) {
               $url = $rule->getLinkURL();
               $message[] = "<a href='$url'>".$rule->getName()."</a>";
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


   function canCreate() {
      return plugin_ocsinventoryng_haveRight("ocsng", "w");
   }


   function canView() {
      return Session::haveRight("logs", "r");
   }


   function getSearchOptions() {

      $tab                       = array();

      $tab['common']             = _n('Not imported computer', 'Not imported computers', 2);

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'ocsid';
      $tab[1]['linkfield']       = '';
      $tab[1]['name']            = __('OCSNG ID');

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'name';
      $tab[2]['linkfield']       = '';
      $tab[2]['name']            = __('OCSNG name');
      $tab[2]['datatype']        = 'itemlink';
      $tab[2]['itemlink_type']   = $this->getType();
      $tab[2]['massiveaction']   = false;

      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'useragent';
      $tab[3]['linkfield']       = '';
      $tab[3]['name']            = __('Inventory agent');

      $tab[4]['table']           = $this->getTable();
      $tab[4]['field']           = 'ocs_deviceid';
      $tab[4]['linkfield']       = '';
      $tab[4]['name']            = __('Device ID');

      $tab[5]['table']           = 'glpi_plugin_ocsinventoryng_ocsservers';
      $tab[5]['field']           = 'name';
      $tab[5]['linkfield']       = 'plugin_ocsinventoryng_ocsservers_id';
      $tab[5]['name']            = __('Server');

      $tab[6]['table']           = $this->getTable();
      $tab[6]['field']           = 'tag';
      $tab[6]['linkfield']       = '';
      $tab[6]['name']            = __('TAG');

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
      $tab[9]['name']            = __('Last OCSNG inventory date');
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
      global $PluginOcsinventoryngDBocs,$DB;

      PluginOcsinventoryngOcsServer::checkOCSconnection($ocsservers_id);

      $query = "SELECT *
                FROM `hardware`, `accountinfo`, `bios`
                WHERE (`accountinfo`.`HARDWARE_ID` = `hardware`.`ID`
                         AND `bios`.`HARDWARE_ID` = `hardware`.`ID`
                            AND `hardware`.`ID` = '$ocsid')";
      $result = $PluginOcsinventoryngDBocs->query($query);
      if ($result && $PluginOcsinventoryngDBocs->numrows($result)) {
         $line             = $PluginOcsinventoryngDBocs->fetch_array($result);
         $input["_ocs"]                                  = true;
         $input["name"]                                  = $line["NAME"];
         $input["domain"]                                = $line["WORKGROUP"];
         $input["tag"]                                   = $line["TAG"];
         $input["ocs_deviceid"]                          = $line["DEVICEID"];
         $input["ipaddr"]                                = $line["IPADDR"];
         $input["plugin_ocsinventoryng_ocsservers_id"]   = $ocsservers_id;
         $input["ocsid"]                                 = $ocsid;
         $input["last_inventory"]                        = $line["LASTCOME"];
         $input["useragent"]                             = $line["USERAGENT"];
         $input["serial"]                                = $line["SSN"];
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
            return __("Can't affect an entity");

         case PluginOcsinventoryngOcsServer::COMPUTER_NOT_UNIQUE :
            return __('Unicity criteria not verified');

         case PluginOcsinventoryngOcsServer::COMPUTER_LINK_REFUSED :
            return __('Import refused by rule');

         default:
               return "";
      }
   }


   /**
    * @param $notimported  PluginOcsinventoryngNotimported object
   **/
   static function showActions(PluginOcsinventoryngNotimported $notimported) {

      echo "<div class='spaced'>";
      echo "<form name='actions' id='actions' method='post' value='".getItemTypeFormURL(__CLASS__)."'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'>";
      echo "<th class='center'>".__('Actions to be made on the computer')."</th></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>";
      echo "<input type='hidden' name='id' value='".$notimported->fields['id']."'>";
      echo "<input type='hidden' name='action' value='massive'>";
      Dropdown::showForMassiveAction('PluginOcsinventoryngNotimported', 0,
                                     array('action'=>'massive'));
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
         $notimported = new PluginOcsinventoryngNotimported;
         $notimported->getFromDB($params['id']);
         $changes     = self::getOcsComputerInfos($notimported->fields);
         if (isset($params['force'])) {
            $result = PluginOcsinventoryngOcsServer::processComputer($notimported->fields['ocsid'],
                                                                     $notimported->fields['plugin_ocsinventoryng_ocsservers_id'],
                                                                     0, $params['entity'], 0);
         } else {
            $result = PluginOcsinventoryngOcsServer::processComputer($notimported->fields['ocsid'],
                                                                     $notimported->fields['plugin_ocsinventoryng_ocsservers_id']);
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

            addMessageAfterRedirect(__('Model'));
            return true;
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
         $notimported = new PluginOcsinventoryngNotimported;
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
      global $PluginOcsinventoryngDBocs;

      PluginOcsinventoryngOcsServer::checkOCSconnection($params['plugin_ocsinventoryng_ocsservers_id']);

      $changes = array();
      $query   = "SELECT `SSN` FROM `bios`
                  WHERE `HARDWARE_ID` = '".$params['ocsid']."'";
      $result  = $PluginOcsinventoryngDBocs->query($query);

      if ($PluginOcsinventoryngDBocs->numrows($result) > 0) {
         $ocs_serial = $PluginOcsinventoryngDBocs->result($result,0,'SSN');
         if ($ocs_serial != $params['serial']) {
            $query_serial = "UPDATE `bios`
                             SET `SSN` = '".$params['serial']."'" .
                  "          WHERE `HARDWARE_ID` = '".$params['ocsid']."'";
            $PluginOcsinventoryngDBocs->query($query_serial);
            $changes[] = 'serial';
         }
      }
      $query = "SELECT `TAG`
                FROM `accountinfo`
                WHERE `HARDWARE_ID` = '".$params['ocsid']."'";
      $result = $PluginOcsinventoryngDBocs->query($query);

      if ($PluginOcsinventoryngDBocs->numrows($result) > 0) {
         $ocs_tag = $PluginOcsinventoryngDBocs->result($result,0,'TAG');
         if ($ocs_tag != $params['tag']) {
            $query_serial = "UPDATE `accountinfo`
                             SET `TAG` = '".$params['tag']."'
                            WHERE `HARDWARE_ID` = '".$params['ocsid']."'";
            $PluginOcsinventoryngDBocs->query($query_serial);
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

     $query = "SELECT `glpi_plugin_ocsinventoryng_notimported`.*
               FROM `glpi_plugin_ocsinventoryng_notimported`
               LEFT JOIN `glpi_alerts`
                  ON (`glpi_plugin_ocsinventoryng_notimported`.`id` = `glpi_alerts`.`items_id`
                      AND `glpi_alerts`.`itemtype` = 'PluginOcsinventoryngNotimported'
                      AND `glpi_alerts`.`type` = '".Alert::END."')
               WHERE `glpi_alerts`.`date` IS NULL";

      foreach ($DB->request($query) as $notimported) {
         $items_infos[$notimported['entities_id']][$notimported['id']] = $notimported;
      }

      foreach ($items_infos as $entity => $items) {
         if (NotificationEvent::raiseEvent('not_imported', new PluginOcsinventoryngNotimported(),
                                           array('entities_id' => $entity,
                                                 'notimported' => $items))) {
            $alert             = new Alert();
            $input["itemtype"] = 'PluginOcsinventoryngNotimported';
            $input["type"]     = Alert::END;
            foreach ($items as $id => $item) {
               $input["items_id"] = $id;
               $alert->add($input);
               unset($alert->fields['id']);
            }
         } else {
            logDebug(__('%1$s: %2$s')."\n", Dropdown::getDropdownName("glpi_entities", $entity),
                     __('Send OCSNG not imported computers alert failed'));
         }
      }
   }


   function cleanDBonPurge() {
      global $DB;

      $query = "DELETE
                FROM `glpi_alerts`
                WHERE `itemtype` = 'PluginOcsinventoryngNotimported'
                      AND `items_id` = '".$this->fields['id']."'";
      $DB->query($query);
   }

}
?>