<?php

/*
 * @version $Id: HEADER 14684 2011-06-11 06:32:40Z remi $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
*/

// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------

class PluginOcsinventoryngNotimported extends CommonDropdown {

   // From CommonDBTM
   public $dohistory = true;

   public $first_level_menu = "plugins";
   public $second_level_menu = "ocsinventoryng";


   static function getTypeName() {
      global $LANG;

      return $LANG['plugin_ocsinventoryng']["notimported"][1];
   }

   function getAdditionalFields() {
      global $LANG;
      
      $can_update = PluginOcsinventoryngConfig::canUpdateOCS();
      return array(array('name'  => 'reason',
                         'label' => $LANG['plugin_ocsinventoryng']["common"][34],
                         'type'   => 'reason'),
                   array('name'  => 'rules_id',
                         'label' => $LANG['plugin_ocsinventoryng']["common"][39],
                         'type'  => 'echo_rule'),
                   array('name'  => 'ocsid',
                         'label' => $LANG['ocsng'][45],
                         'type'  => 'echo'),
                   array('name'  => 'plugin_ocsinventoryng_ocsservers_id',
                         'label' => $LANG['ocsng'][29],
                         'type'  => 'echo_dropdown',
                         'table' => 'glpi_plugin_ocsinventoryng_ocsservers'),
                   array('name'  => 'ocs_deviceid',
                         'label' => $LANG['plugin_ocsinventoryng']["common"][22],
                         'type'  => 'echo'),
                   array('name'  => 'serial',
                         'label' => $LANG['common'][19],
                         'type'  => ($can_update?'text':'echo')),
                   array('name'  => 'tag',
                         'label' => $LANG["ocsconfig"][39],
                         'type'  => ($can_update?'text':'echo')),
                   array('name'  => 'useragent',
                         'label' => $LANG['ocsng'][49],
                         'type'  => 'echo'),
                   array('name'  => 'ipaddr',
                         'label' => $LANG['networking'][14],
                        'type'   => 'echo'),
                   array('name'  => 'domain',
                         'label' => $LANG['setup'][89],
                         'type'  => 'echo'),
                   array('name'  => 'last_inventory',
                         'label' => $LANG['plugin_ocsinventoryng']["common"][24],
                         'type'   => 'echo_datetime'));
   }


   /**
    * Add more tabs to display
   **/
   function defineMoreTabs($options=array()) {
      global $LANG;

      $ong = array();
      if (PluginOcsinventoryngOcsServer::getComputerLinkToOcsConsole ($this->fields['plugin_ocsinventoryng_ocsservers_id'], 
                                                     $this->fields['ocsid'],
                                                     '',
                                                     true) != '') {
         $ong[3]  = $LANG['plugin_ocsinventoryng']["notimported"][2];
      }
      $ong[12] = $LANG['title'][38];
      return $ong;
   }

   /**
    * Display fields that are specific to this itemtype
    * @param ID the item's ID
    * @param field the item's fields
    * @return nothing
    */
   function displaySpecificTypeField($ID, $field = array()) {
      global $LANG;
      switch ($field['type']) {
         case 'echo':
            echo $this->fields[$field['name']];
            break;
         case 'reason':
            echo self::getReason($this->fields[$field['name']]);
            break;
         case 'echo_datetime':
            echo convDateTime($this->fields[$field['name']]);
            break;
         case 'echo_dropdown':
            $result = Dropdown::getDropdownName($field['table'],$this->fields[$field['name']]);
            if ($result == '') {
               echo DROPDOWN_EMPTY_VALUE;
            } else {
               echo $result;
            }
            break;
         case 'echo_rule':
           /*
            $message='';
            foreach (json_decode($this->fields[$field['name']],true) as $key => $value) {
               $message.= $LANG['plugin_ocsinventoryng']["common"][40];
               $rule = new $key;
               if ($rule->can($value,'r')) {
                  $url = $rule->getLinkURL();
                  $message.=" : <a href='$url'>".$rule->getName()."</a>";
               }
            }
            echo $message;
            */
            echo self::getRuleMatchedMessage($this->fields[$field['name']]);
            break;
      }
   }

   static function getRuleMatchedMessage($rule_list) {
      global $LANG;
      $message = array();
      if ($rule_list != '') {
         foreach (json_decode($rule_list,true) as $key => $value) {
            $rule = new $key;
            if ($rule->can($value,'r')) {
               $url = $rule->getLinkURL();
                $message[] = "<a href='$url'>".$rule->getName()."</a>";
            }
         }
         return implode(' => ',$message);
      } else {
         return '';
      }
   }
   
   function displayOcsConsole() {
      $url = PluginOcsinventoryngOcsServer::getComputerLinkToOcsConsole ($this->fields['plugin_ocsinventoryng_ocsservers_id'], 
                                                     $this->fields['ocsid'],
                                                     '',
                                                     true);
      echo "<div class='center'>";
      if ($url != '') {
         echo "<iframe src='$url' width='80%' height='60%'>";
      }
      echo "</div>";
   }
   /**
    * Display more tabs
   **/
   function displayMoreTabs($tab) {

      switch ($tab) {
         case 1:
            self::showActions($this);
            break;
         case 3:
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
      return haveRight("ocsng", "w");
   }

   function canView() {
      return haveRight("logs", "r");
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array ();
      $tab['common'] = $LANG['plugin_ocsinventoryng']["common"][23];

      $tab[1]['table']     = $this->getTable();
      $tab[1]['field']     = 'ocsid';
      $tab[1]['linkfield'] = '';
      $tab[1]['name']      = $LANG['ocsng'][45];

      $tab[2]['table']     = $this->getTable();
      $tab[2]['field']     = 'name';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['plugin_ocsinventoryng']['registry'][6];
      $tab[2]['datatype']      = 'itemlink';
      $tab[2]['itemlink_type'] = $this->getType();
      $tab[2]['massiveaction'] = false;

      $tab[3]['table']     = $this->getTable();
      $tab[3]['field']     = 'useragent';
      $tab[3]['linkfield'] = '';
      $tab[3]['name']      = $LANG['ocsng'][49];

      $tab[4]['table']     = $this->getTable();
      $tab[4]['field']     = 'ocs_deviceid';
      $tab[4]['linkfield'] = '';
      $tab[4]['name']      = $LANG['plugin_ocsinventoryng']["common"][22];

      $tab[5]['table']     = 'glpi_plugin_ocsinventoryng_ocsservers';
      $tab[5]['field']     = 'name';
      $tab[5]['linkfield'] = 'plugin_ocsinventoryng_ocsservers_id';
      $tab[5]['name']      = $LANG['ocsng'][29];

      $tab[6]['table']     = $this->getTable();
      $tab[6]['field']     = 'tag';
      $tab[6]['linkfield'] = '';
      $tab[6]['name']      = $LANG['ocsconfig'][39];

      $tab[7]['table']     = $this->getTable();
      $tab[7]['field']     = 'ipaddr';
      $tab[7]['linkfield'] = '';
      $tab[7]['name']      = $LANG['networking'][14];

      $tab[8]['table']     = $this->getTable();
      $tab[8]['field']     = 'domain';
      $tab[8]['linkfield'] = '';
      $tab[8]['name']      = $LANG['setup'][89];

      $tab[9]['table']     = $this->getTable();
      $tab[9]['field']     = 'last_inventory';
      $tab[9]['linkfield'] = '';
      $tab[9]['name']      = $LANG['plugin_ocsinventoryng']["common"][24];
      $tab[9]['datatype']  = 'datetime';

      $tab[10]['table']     = $this->getTable();
      $tab[10]['field']     = 'reason';
      $tab[10]['linkfield'] = '';
      $tab[10]['name']      = $LANG['plugin_ocsinventoryng']["common"][34];

      $tab[11]['table']     = $this->getTable();
      $tab[11]['field']     = 'serial';
      $tab[11]['linkfield'] = '';
      $tab[11]['name']      = $LANG['common'][19];

      $tab[80]['table'] = 'glpi_entities';
      $tab[80]['field'] = 'completename';
      $tab[80]['name']  = $LANG['entity'][0];

      return $tab;
   }


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
         $line = $PluginOcsinventoryngDBocs->fetch_array($result);
         $input["_ocs"] = true;
         $input["name"] = $line["NAME"];
         $input["domain"] = $line["WORKGROUP"];
         $input["tag"] = $line["TAG"];
         $input["ocs_deviceid"] = $line["DEVICEID"];
         $input["ipaddr"] = $line["IPADDR"];
         $input["plugin_ocsinventoryng_ocsservers_id"] = $ocsservers_id;
         $input["ocsid"] = $ocsid;
         $input["last_inventory"] = $line["LASTCOME"];
         $input["useragent"]= $line["USERAGENT"];
         $input["serial"]= $line["SSN"];
         $input["reason"]= $reason['status'];
         $input["comment"]= "";
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


   function cleanNotImported($ocsservers_id = -1, $ocsid = -1) {
      global $DB;

      $first = true;

      $sql = "DELETE
              FROM `".$this->getTable()."`";

      if ($ocsservers_id != -1) {
         $sql .= " WHERE `plugin_ocsinventoryng_ocsservers_id` = '$ocsservers_id'";
         $first = false;
      }
      if ($ocsid != -1) {
         $sql .= ($first?" WHERE":" AND")." `ocsid` = '$ocsid'";
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
    * @param not_imported_id if of the computer that is not imported in GLPI
    * @return nothing
    */
   function deleteNotImportedComputer($not_imported_id) {

      if ($this->getFromDB($not_imported_id)) {
         PluginUninstallUninstall::deleteComputerInOCS($this->fields["ocsid"], $this->fields["plugin_ocsinventoryng_ocsservers_id"]);
         $fields["id"]=$not_imported_id;
         $this->delete($fields);
      }
   }


   static function getReason($reason) {
      global $LANG;
         switch ($reason) {
            case PluginOcsinventoryngOcsServer::COMPUTER_FAILED_IMPORT:
               return $LANG['plugin_ocsinventoryng']["common"][35];
            case PluginOcsinventoryngOcsServer::COMPUTER_NOT_UNIQUE:
               return $LANG['plugin_ocsinventoryng']["common"][36];
            case PluginOcsinventoryngOcsServer::COMPUTER_LINK_REFUSED:
               return $LANG['plugin_ocsinventoryng']["common"][37];
            default:
               return "";
         }
      
   }
   
   static function showActions(PluginOcsinventoryngNotimported $notimported) {
      global $LANG;

      echo "<div class='spaced'>"; 
      echo "<form name='actions' id='actions' method='post' value='".getItemTypeFormURL(__CLASS__)."'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'><th class='center'>".$LANG['plugin_ocsinventoryng']["display"][7]."</th></tr>";
      echo "<tr class='tab_bg_2'><td class='center'>";
      echo "<input type='hidden' name='id' value='".$notimported->fields['id']."'>";
      echo "<input type='hidden' name='action' value='massive'>";
      Dropdown::showForMassiveAction('PluginOcsinventoryngNotimported',0,array('action'=>'massive'));
      echo "</td></tr>";
      echo "</table></form></div>";
   }

   static function computerImport($params = array()) {
      global $LANG;
      
      if (isset($params['id'])) {
         $notimported = new PluginOcsinventoryngNotimported;
         $notimported->getFromDB($params['id']);
         $changes = self::getOcsComputerInfos($notimported->fields);
         if (isset($params['force'])) {
            $result = PluginOcsinventoryngOcsServer::processComputer($notimported->fields['ocsid'],
                                                 $notimported->fields['plugin_ocsinventoryng_ocsservers_id'],0,
                                                 $params['entity'],0);
         } else {
            $result = PluginOcsinventoryngOcsServer::processComputer($notimported->fields['ocsid'],
                                                 $notimported->fields['plugin_ocsinventoryng_ocsservers_id']);
         }

         if (in_array($result['status'],array(PluginOcsinventoryngOcsServer::COMPUTER_IMPORTED,
                                              PluginOcsinventoryngOcsServer::COMPUTER_LINKED,
                                              PluginOcsinventoryngOcsServer::COMPUTER_SYNCHRONIZED))) {
            $notimported->delete(array('id'=>$params['id']));
            
            //If serial has been changed in order to import computer
            if (in_array('serial',$changes)) {
               PluginOcsinventoryngOcsServer::mergeOcsArray($result['computers_id'], array('serial'), "computer_update");
            }
            
            addMessageAfterRedirect($LANG['common'][23]);
            return true;
         } else {
            $tmp = $notimported->fields;
            $tmp['reason'] = $result['status'];
            if (isset($result['entities_id'])) {
               $tmp["entities_id"]= $result['entities_id'];
            } else {
               $tmp['entities_id'] = 0;
            }
            $tmp["rules_id"] = json_encode($result['rule_matched']);
            $notimported->update($tmp);
            return false;
         }
      }
   }

   static function linkComputer($params = array()) {
      if (isset($params['id'])) {
         $notimported = new PluginOcsinventoryngNotimported;
         $notimported->getFromDB($params['id']);
         $changes = self::getOcsComputerInfos($notimported->fields);

         if (PluginOcsinventoryngOcsServer::linkComputer($notimported->fields['ocsid'], 
                                           $notimported->fields['plugin_ocsinventoryng_ocsservers_id'],
                                           $params['computers_id'])) {
            $notimported->delete(array('id'=>$params['id']));
            //If serial has been changed in order to import computer
            if (in_array('serial',$changes)) {
               PluginOcsinventoryngOcsServer::mergeOcsArray($params['id'], array('serial'), "computer_update");
            }
         }
      }
   }
   
   static function getOcsComputerInfos($params = array()) {
      global $PluginOcsinventoryngDBocs;
      PluginOcsinventoryngOcsServer::checkOCSconnection($params['plugin_ocsinventoryng_ocsservers_id']);
      
      $changes = array();
      $query = "SELECT `SSN` FROM `bios` " .
               "WHERE `HARDWARE_ID`='".$params['ocsid']."'";
      $result = $PluginOcsinventoryngDBocs->query($query);
      if ($PluginOcsinventoryngDBocs->numrows($result) > 0) {
         $ocs_serial = $PluginOcsinventoryngDBocs->result($result,0,'SSN');
         if ($ocs_serial != $params['serial']) {
            $query_serial = "UPDATE `bios` SET `SSN`='".$params['serial']."'" .
                  "          WHERE `HARDWARE_ID`='".$params['ocsid']."'";
            $PluginOcsinventoryngDBocs->query($query_serial);
            $changes[] = 'serial';
         }
      }
      $query = "SELECT `TAG` FROM `accountinfo` " .
               "WHERE `HARDWARE_ID`='".$params['ocsid']."'";
      $result = $PluginOcsinventoryngDBocs->query($query);
      if ($PluginOcsinventoryngDBocs->numrows($result) > 0) {
         $ocs_tag = $PluginOcsinventoryngDBocs->result($result,0,'TAG');
         if ($ocs_tag != $params['tag']) {
            $query_serial = "UPDATE `accountinfo` SET `TAG`='".$params['tag']."'" .
                           " WHERE `HARDWARE_ID`='".$params['ocsid']."'";
            $PluginOcsinventoryngDBocs->query($query_serial);
            $changes[] = 'tag';
         }
      }

      return $changes;
   }
   
   static function sendAlert() {
      global $DB,$CFG_GLPI,$LANG;

      if (!$CFG_GLPI["use_mailing"]) {
         return 0;
      }

      $message=array();
      $items_infos = array();

     $query = "SELECT `glpi_plugin_ocsinventoryng_notimported`.*
                   FROM `glpi_plugin_ocsinventoryng_notimported`
                   LEFT JOIN `glpi_alerts` ON (`glpi_plugin_ocsinventoryng_notimported`.`id` = `glpi_alerts`.`items_id`
                                                 AND `glpi_alerts`.`itemtype` = 'PluginOcsinventoryngNotimported'
                                                    AND `glpi_alerts`.`type`='".Alert::END."')
                   WHERE `glpi_alerts`.`date` IS NULL";
      
      foreach ($DB->request($query) as $notimported) {
         $items_infos[$notimported['entities_id']][$notimported['id']] = $notimported;
      }

      foreach ($items_infos as $entity => $items) {

         if (NotificationEvent::raiseEvent('not_imported',new PluginOcsinventoryngNotimported(),
                                           array('entities_id'=>$entity,'notimported'=>$items))) {
            $alert=new Alert();
            $input["itemtype"] = 'PluginOcsinventoryngNotimported';
            $input["type"]=Alert::END;
            foreach ($items as $id => $item) {
               $input["items_id"]=$id;
               $alert->add($input);
               unset($alert->fields['id']);
            }
         } else {
            logDebug(Dropdown::getDropdownName("glpi_entities",$entity).
                     ":  Send OCSNG not imported computers alert failed\n");
         }
      }
   }
   
   function cleanDBonPurge() {
      global $DB;
      $query = "DELETE FROM `glpi_alerts` " .
               "WHERE `itemtype`='PluginOcsinventoryngNotimported' " .
               "   AND `items_id`='".$this->fields['id']."'";
      $DB->query($query);
   }

}

?>