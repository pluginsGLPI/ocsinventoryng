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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// CLASSES PluginOcsinventoryngOcslink
class PluginOcsinventoryngOcslink extends CommonDBTM {
   const HISTORY_OCS_IMPORT         = 8;
   const HISTORY_OCS_DELETE         = 9;
   const HISTORY_OCS_IDCHANGED      = 10;
   const HISTORY_OCS_LINK           = 11;
   const HISTORY_OCS_TAGCHANGED     = 12;

   static $rightname = "plugin_ocsinventoryng";
   
   static function getTypeName($nb=0) {
      return _n('OCSNG link', 'OCSNG links', $nb, 'ocsinventoryng');
   }

   /**
    * Show simple inventory information of an computer child item
    *
    * @param $item                   CommonDBTM object
    *
    * @return nothing
   **/
   static function showSimpleForChild(CommonDBTM $item) {

      if ($item->isDynamic()
          && $item->isField('computers_id')
          && countElementsInTable('glpi_plugin_ocsinventoryng_ocslinks',
                                  "`computers_id`='".$item->getField('computers_id')."'")>0) {
         _e('OCS Inventory NG');
      }
   }

   /**
   * Show simple inventory information of an item
   *
   * @param $item                   CommonDBTM object
   *
   * @return nothing
   **/
   static function showSimpleForItem(CommonDBTM $item) {
      global $DB, $CFG_GLPI;

      $target = Toolbox::getItemTypeFormURL(__CLASS__);

      if (in_array($item->getType(), array('Computer'))) {
         $items_id = $item->getField('id');

         if (!empty($items_id)
             && $item->fields["is_dynamic"]
             && Session::haveRight("plugin_ocsinventoryng_view", READ)) {
            $query = "SELECT *
                      FROM `glpi_plugin_ocsinventoryng_ocslinks`
                      WHERE `computers_id` = '$items_id' ".
                            getEntitiesRestrictRequest("AND", "glpi_plugin_ocsinventoryng_ocslinks");

            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               $data = $DB->fetch_assoc($result);

               if (count($data)) {
                  $ocs_config = PluginOcsinventoryngOcsServer::getConfig($data['plugin_ocsinventoryng_ocsservers_id']);
                  echo "<table class='tab_glpi'>";
                  echo "<tr class='tab_bg_1'><th colspan='2'>".__('OCS Inventory NG')."</th>";
                  if (isset($data["last_ocs_conn"])) {
                     echo "<tr class='tab_bg_1'><td>".__('Last OCSNG connection date', 'ocsinventoryng');
                     echo "</td><td>".Html::convDateTime($data["last_ocs_conn"]).'</td></tr>';
                  }
                  echo "<tr class='tab_bg_1'><td>".__('Last OCSNG inventory date', 'ocsinventoryng');
                  echo "</td><td>".Html::convDateTime($data["last_ocs_update"]).'</td></tr>';
                  echo "<tr class='tab_bg_1'><td>".__('GLPI import date',  'ocsinventoryng');
                  echo "</td><td>".Html::convDateTime($data["last_update"]).'</td></tr>';
                  echo "<tr class='tab_bg_1'><td>".__('Inventory agent',  'ocsinventoryng');
                  echo "</td><td>".$data["ocs_agent_version"].'</td></tr>';
                  if (isset($data["ip_src"])) {
                     echo "<tr class='tab_bg_1'><td>".__('IP Source',  'ocsinventoryng');
                     echo "</td><td>".$data["ip_src"].'</td></tr>';
                  }
                  echo "<tr class='tab_bg_1'><td>".__('Server');
                  echo "</td><td>";
                  if (Session::haveRight("plugin_ocsinventoryng", READ)) {
                     echo "<a href='".$CFG_GLPI["root_doc"]."/plugins/ocsinventoryng/front/ocsserver.form.php?id="
                           .$ocs_config['id']."'>".$ocs_config['name']."</a>";
                  } else {
                     echo $ocs_config['name'];
                  }
                  echo '</td></tr>';
                 //If have write right on OCS and ocsreports url is not empty in OCS config
                  if (Session::haveRight("plugin_ocsinventoryng", UPDATE)
                      && ($ocs_config["ocs_url"] != '')) {
                     echo "<tr class='tab_bg_1'><td colspan='2' class='center'>";
                     echo PluginOcsinventoryngOcsServer::getComputerLinkToOcsConsole($ocs_config['id'],
                                                                                    $data["ocsid"],
                                                                                    __('OCS NG Interface','ocsinventoryng'));
                     echo "</td></tr>";
                  }
                  
                  echo "<tr class='tab_bg_1'><td>".__('OCSNG TAG', 'ocsinventoryng').
                       "</td>";
                  echo "<td>";
                  echo $data["tag"];
                  echo "</td></tr>";
                  
                  if (Session::haveRight("plugin_ocsinventoryng_view", READ)
                      && Session::haveRight("plugin_ocsinventoryng_sync", UPDATE)) {
                     echo "<tr class='tab_bg_1'><td>".__('Automatic update OCSNG', 'ocsinventoryng').
                          "</td>";
                     echo "<td>";
                     echo Dropdown::getYesNo($data["use_auto_update"]);
                     echo "</td></tr>";
                  }

                  
                  if (Session::haveRight("plugin_ocsinventoryng_sync", UPDATE)) {
                     echo "<tr class='tab_bg_1'><td class='center' colspan='2'>";
                     Html::showSimpleForm($target, 'force_ocs_resynch',
                                          _sx('button', 'Force synchronization', 'ocsinventoryng'),
                                          array('id' => $items_id,
                                                  'resynch_id' => $data["id"]));
                     echo "</td></tr>";
                     
                  }
                  echo '</table>';
               }
            }
         }
      }
   }


   /**
    * Read ocslink for a given computer
    *
    * @param $ID   Integer   ID of the computer
    *
    * @return boolean
   **/
   function getFromDBforComputer ($ID) {

      if ($this->getFromDBByQuery("WHERE `".$this->getTable()."`.`computers_id` = '$ID'")) {
            return true;
      }
      return false;
   }


   /**
   * Show OcsLink of an item
   *
   * @param $item                   CommonDBTM object
   * @param $withtemplate  integer  withtemplate param (default '')
   *
   * @return nothing
   **/
   static function showForItem(CommonDBTM $item, $withtemplate='') {
      global $DB, $CFG_GLPI;

      $target = Toolbox::getItemTypeFormURL(__CLASS__);

      if (in_array($item->getType(), array('Computer'))) {
         $items_id = $item->getField('id');

         if (!empty($items_id )
             && $item->fields["is_dynamic"]
             && Session::haveRight("plugin_ocsinventoryng_view", READ)) {

            $query = "SELECT *
                      FROM `glpi_plugin_ocsinventoryng_ocslinks`
                      WHERE `computers_id` = '$items_id' ".
                            getEntitiesRestrictRequest("AND", "glpi_plugin_ocsinventoryng_ocslinks");

            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               $data = $DB->fetch_assoc($result);
               $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));

               if (count($data)) {
                  $ocs_config
                     = PluginOcsinventoryngOcsServer::getConfig(PluginOcsinventoryngOcsServer::getByMachineID($items_id));

                  echo "<div class='center'>";
                  echo "<form method='post' action=\"$target\">";
                  echo "<input type='hidden' name='id' value='$items_id'>";
                  echo "<table class='tab_cadre_fixe'>";
                  echo "<tr><th colspan = '4'>OCS Inventory NG</th>";

                  echo "<tr class='tab_bg_1'>";

                  $colspan = 4;
                  if (Session::haveRight("plugin_ocsinventoryng_view", READ)
                      && Session::haveRight("plugin_ocsinventoryng_sync", UPDATE)) {

                     $colspan = 2;
                     echo "<td class='center'>".__('Automatic update OCSNG', 'ocsinventoryng').
                          "</td>";
                     echo "<td class='left'>";
                     Dropdown::showYesNo("use_auto_update", $data["use_auto_update"]);
                     echo "</td>";
                  }
                  echo "<td class='center' colspan='".$colspan."'>";
                  printf(__('%1$s: %2$s'), __('OCSNG TAG', 'ocsinventoryng'), $data['tag']);
                  echo "</td></tr>";

                  if (Session::haveRight("plugin_ocsinventoryng_sync", UPDATE)) {
                     echo "<tr class='tab_bg_1'>";
                     $colspan=4;
                     echo "<td class='center' colspan='2'>";
                     echo "<input type='hidden' name='resynch_id' value='" . $data["id"] . "'>";
                     echo "<input class=submit type='submit' name='force_ocs_resynch' value=\"" .
                           _sx('button', 'Force synchronization', 'ocsinventoryng'). "\">";
                     echo "</td>";

                     //echo "<tr class='tab_bg_1'>";
                     echo "<td class='center' colspan='2'>";
                     echo "<input type='hidden' name='link_id' value='" . $data["id"] . "'>";
                     echo "<input class=submit type='submit' name='update' value=\"" .
                            _sx('button', 'Save')."\">";
                     echo "</td></tr>";
                  }

                  echo "</table>\n";
                  Html::closeForm();
                  echo "</div>";
               }
            }
         }
      }
   }


   /**
    * Update lockable fields of an item
    *
    * @param $item                     CommonDBTM object
    * @param $withtemplate    integer  withtemplate param (default '')
    *
    * @return nothing
   **/
   static function updateComputer(CommonDBTM $item, $withtemplate='') {
      global $DB;
      // Manage changes for OCS if more than 1 element (date_mod)
      // Need dohistory==1 if dohistory==2 no locking fields
      if ($item->fields["is_dynamic"]
            && countElementsInTable('glpi_plugin_ocsinventoryng_ocslinks', "`computers_id`='".$item->getID()."'")
            && ($item->dohistory == 1)
            && (count($item->updates) > 1)
            && (!isset($item->input["_nolock"]))) {

         PluginOcsinventoryngOcsServer::mergeOcsArray($item->fields["id"], $item->updates,
                                                      "computer_update");
      }

      if (isset($item->input["_auto_update_ocs"])) {
         $query = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                   SET `use_auto_update` = '".$item->input["_auto_update_ocs"]."'
                   WHERE `computers_id` = '".$item->input["id"]."'";
         $DB->query($query);
      }
   }


   /**
    * Update lockable linked items of an item
    *
    * @param $item                     CommonDBTM object
    * @param $withtemplate    integer  withtemplate param (default '')
    *
    * @return nothing
   **/
   static function addComputer_Item(CommonDBTM $item, $withtemplate='') {
      global $DB;

      $link = new $item->input['itemtype'];
      if (!$link->getFromDB($item->input['items_id'])) {
         return false;
      }
      if (!$link->getField('is_global') ) {
         // Handle case where already used, should never happen (except from OCS sync)
         $query = "SELECT `id`, `computers_id`
                   FROM `glpi_computers_items`
                   WHERE `glpi_computers_items`.`items_id` = '".$item->input['items_id']."'
                         AND `glpi_computers_items`.`itemtype` = '".$item->input['itemtype']."'";
         $result = $DB->query($query);

         while ($data = $DB->fetch_assoc($result)) {
            $temp = clone $item;
            $temp->delete($data, true);
         }
      }
   }


   /**
    * if Computer deleted
    *
    * @param $comp   Computer object
   **/
   static function purgeComputer(Computer $comp) {
      $link = new self();
      $link->deleteByCriteria(array('computers_id' => $comp->getField("id")));

      $reg = new PluginOcsinventoryngRegistryKey();
      $reg->deleteByCriteria(array('computers_id' => $comp->getField("id")));
   }


   /**
    * if Computer_Item deleted
    *
    * @param $comp   Computer_Item object
   **/
   static function purgeComputer_Item(Computer_Item $comp) {
	  Global $DB;
      if ($device = getItemForItemtype($comp->fields['itemtype'])) {
         if ($device->getFromDB($comp->fields['items_id'])) {

            if (isset($comp->input['_ocsservers_id'])) {
               $ocsservers_id = $comp->input['_ocsservers_id'];
            } else {
               $ocsservers_id = PluginOcsinventoryngOcsServer::getByMachineID($comp->fields['computers_id']);
            }

            if ($ocsservers_id > 0) {
               //Get OCS configuration
               $ocs_config = PluginOcsinventoryngOcsServer::getConfig($ocsservers_id);

               //Get the management mode for this device
               $mode = PluginOcsinventoryngOcsServer::getDevicesManagementMode($ocs_config,
                                                                              $comp->fields['itemtype']);
               $decoConf = $ocs_config["deconnection_behavior"];

               //Change status if :
               // 1 : the management mode IS NOT global
               // 2 : a deconnection's status have been defined
               // 3 : unique with serial
			   

               if (($mode >= 2)
               && (strlen($decoConf) > 0)) {

                  //Delete periph from glpi
                  // if ($decoConf == "delete") {
                     // $tmp["id"] = $comp->fields['items_id'];
                     // $device->delete(array('id'  => $tmp['id']), 1);

                  // Put periph in dustbin
                  // } else if ($decoConf == "trash") {
                     // $tmp["id"] = $comp->fields['items_id'];
                     // $device->delete(array('id'  => $tmp['id']), 0);
                  // }
				  
				  if ($decoConf == "delete") {
					$tmp["id"] = $comp->getID();
                     $query = "DELETE
                         FROM `glpi_computers_items`
                         WHERE `id`='".$tmp['id']."'";
				  $result = $DB->query($query);
                  //Put periph in dustbin
                  } else if ($decoConf == "trash") {
					$tmp["id"] = $comp->getID();
                     $query = "UPDATE
                         `glpi_computers_items`
						 SET `is_deleted` = 1
                         WHERE `id`='".$tmp['id']."'";
					$result = $DB->query($query);
                  }
               }
            } // $ocsservers_id>0
         }
      }
   }

   /**
    * @see inc/CommonGLPI::getTabNameForItem()
    *
    * @param $item               CommonGLPI object
    * @param$withtemplate        (default 0)
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (in_array($item->getType(), PluginOcsinventoryngOcsServer::getTypes(true))
          && $this->canView()) {

         switch ($item->getType()) {
            case 'Computer' :
               return array('1' => _n('OCSNG link', 'OCSNG links', 1, 'ocsinventoryng'));
         }
      }
      return '';
   }


   /**
    * @param $item            CommonGLPI object
    * @param $tabnum          (default 1)
    * @param $withtemplate    (default 0)
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if (in_array($item->getType(), PluginOcsinventoryngOcsServer::getTypes(true))) {
         switch ($item->getType()) {
            case 'Computer' :
               self::showForItem($item, $withtemplate);
               break;
         }
      }
      return true;
   }


   /**
    * Add an history entry to a computer
    *
    * @param $computers_id Integer, ID of the computer
    * @param $changes      Array, see Log::history
    * @param $action       Integer in PluginOcsinventoryngOcslink::HISTORY_OCS_*
    *
    * @return Integer id of the inserted entry
   **/
   static function history($computers_id, $changes, $action) {

      return Log::history($computers_id, 'Computer', $changes, __CLASS__,
                          Log::HISTORY_PLUGIN+$action);
   }

   /**
    * Get an history entry message
    *
    * @param $data Array from glpi_logs table
    *
    * @return string
   **/
   static function getHistoryEntry($data) {

      if (Session::haveRight("plugin_ocsinventoryng", READ)) {
         switch($data['linked_action'] - Log::HISTORY_PLUGIN) {
            case self::HISTORY_OCS_IMPORT :
               return sprintf(__('%1$s: %2$s'), __('Imported from OCSNG', 'ocsinventoryng'),
                              $data['new_value']);

            case self::HISTORY_OCS_DELETE :
               return sprintf(__('%1$s: %2$s'), __('Deleted in OCSNG', 'ocsinventoryng'),
                              $data['old_value']);

            case self::HISTORY_OCS_LINK :
               return sprintf(__('%1$s: %2$s'), __('Linked with an OCSNG computer', 'ocsinventoryng'),
                              $data['new_value']);

            case self::HISTORY_OCS_IDCHANGED :
               return  sprintf(__('The OCSNG ID of the computer changed from %1$s to %2$s',
                                  'ocsinventoryng'),
                               $data['old_value'], $data['new_value']);

            case self::HISTORY_OCS_TAGCHANGED :
               return  sprintf(__('The OCSNG TAG of the computer changed from %1$s to %2$s',
                                  'ocsinventoryng'),
                               $data['old_value'], $data['new_value']);
         }
      }
      return '';
   }

}
?>