<?php
/*
 * @version $Id: ruleimportcomputer.class.php 14685 2011-06-11 06:40:30Z remi $
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

// ----------------------------------------------------------------------
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// OCS Rules class
class PluginOcsinventoryngRuleImportComputer extends Rule {

   const PATTERN_IS_EMPTY              = 30;
   const RULE_ACTION_LINK_OR_IMPORT    = 0;
   const RULE_ACTION_LINK_OR_NO_IMPORT = 1;

   var $restrict_matching = Rule::AND_MATCHING;


   // From Rule
   //TODO : how change this ?
   public $right    = 'rule_ocs';
   public $can_sort = true;


   function canCreate() {
      return plugin_ocsinventoryng_haveRight('rule_ocs', 'w');
   }


   function canView() {
      return plugin_ocsinventoryng_haveRight('rule_ocs', 'r');
   }


   function getTitle() {

      return __('Rules for import and link computers');
   }


   function maxActionsCount() {
      // Unlimited
      return 1;
   }


   function getCriterias() {

      $criterias = array();
      $criterias['entities_id']['table']         = 'glpi_entities';
      $criterias['entities_id']['field']         = 'entities_id';
      $criterias['entities_id']['name']          = sprintf(__('%1$s: %2$s'), __('Computer to import'),
                                                           __('target entity for the computer'));
      $criterias['entities_id']['linkfield']     = 'entities_id';
      $criterias['entities_id']['type']          = 'dropdown';

      $criterias['states_id']['table']           = 'glpi_states';
      $criterias['states_id']['field']           = 'name';
      $criterias['states_id']['name']            = __('Find computers in GLPI having the status');
      $criterias['states_id']['linkfield']       = 'state';
      $criterias['states_id']['type']            = 'dropdown';
      //Means that this criterion can only be used in a global search query
      $criterias['states_id']['is_global']       = true;
      $criterias['states_id']['allow_condition'] = array(Rule::PATTERN_IS, Rule::PATTERN_IS_NOT);

      $criterias['ocsservers_id']['table']       = 'glpi_plugin_ocsinventoryng_ocsservers';
      $criterias['ocsservers_id']['field']       = 'name';
      $criterias['ocsservers_id']['name']        = __('OCSNG server');
      $criterias['ocsservers_id']['linkfield']   = '';
      $criterias['ocsservers_id']['type']        = 'dropdown';

      $criterias['TAG']['name']              = sprintf(__('%1$s: %2$s'), __('Computer to import'),
                                                       __('OCSNG TAG'));

      $criterias['DOMAIN']['name']           = sprintf(__('%1$s: %2$s'), __('Computer to import'),
                                                       __('Domain'));

      $criterias['IPSUBNET']['name']         = sprintf(__('%1$s: %2$s'), __('Computer to import'),
                                                       __('Subnet'));

      $criterias['MACADDRESS']['name']       = sprintf(__('%1$s: %2$s'), __('Computer to import'),
                                                       __('Mac address'));

      $criterias['IPADDRESS']['name']        = sprintf(__('%1$s: %2$s'), __('Computer to import'),
                                                       __('IP Address'));

      $criterias['name']['name']             = sprintf(__('%1$s: %2$s'), __('Computer to import'),
                                                       __("Computer's name"));
      $criterias['name']['allow_condition'] = array(Rule::PATTERN_IS, Rule::PATTERN_IS_NOT,
                                                    self::PATTERN_IS_EMPTY, Rule::PATTERN_FIND);

      $criterias['DESCRIPTION']['name']      = sprintf(__('%1$s: %2$s'), __('Computer to import'),
                                                       __('Description'));

      $criterias['serial']['name']           = sprintf(__('%1$s: %2$s'), __('Computer to import'),
                                                       __('Serial number'));

      // Model as Text to allow text criteria (contains, regex, ...)
      $criterias['model']['name']            = sprintf(__('%1$s: %2$s'), __('Computer to import'),
                                                       __('Model'));

      // Manufacturer as Text to allow text criteria (contains, regex, ...)
      $criterias['manufacturer']['name']     = sprintf(__('%1$s: %2$s'), __('Computer to import'),
                                                       __('Manufacturer'));

      return $criterias;
   }


   function getActions() {

      $actions = array();
      $actions['_fusion']['name']        = __('OCSNG link');
      $actions['_fusion']['type']        = 'fusion_type';

      $actions['_ignore_import']['name'] = __('To be unaware of import');
      $actions['_ignore_import']['type'] = 'yesonly';

      return $actions;
   }


   static function getRuleActionValues() {

      return array(self::RULE_ACTION_LINK_OR_IMPORT    => __('Link if possible'),
                   self::RULE_ACTION_LINK_OR_NO_IMPORT => __('Link if possible, otherwise imports declined'));
   }


   /**
    * Add more action values specific to this type of rule
    *
    * @param value the value for this action
    *
    * @return the label's value or ''
   **/
   function displayAdditionRuleActionValue($value) {

      $values = self::getRuleActionValues();
      if (isset($values[$value])) {
         return $values[$value];
      }
      return '';
   }


   function manageSpecificCriteriaValues($criteria, $name, $value) {

      switch ($criteria['type']) {
         case "state" :
            $link_array = array("0" => __('No'),
                                "1" => sprintf(__('%1$s: %2$s'), __('Yes'), __('equal')),
                                "2" => sprintf(__('%1$s: %2$s'), __('Yes'), __('empty')));

            Dropdown::showFromArray($name, $link_array, array('value' => $value));
      }
      return false;
   }


   /**
    * Add more criteria specific to this type of rule
   **/
   static function addMoreCriteria($criterion='') {

      return array(Rule::PATTERN_FIND     => __('is already present in GLPI'),
                   self::PATTERN_IS_EMPTY => __('is empty in GLPI'));
   }


   function getAdditionalCriteriaDisplayPattern($ID, $condition, $pattern) {

      if ($condition == self::PATTERN_IS_EMPTY) {
          return __('Yes');
      }
      return false;
   }


   function displayAdditionalRuleCondition($condition, $criteria, $name, $value, $test=false) {

      if ($test) {
         return false;
      }

      switch ($condition) {
         case Rule::PATTERN_FIND :
         case self::PATTERN_IS_EMPTY :
            Dropdown::showYesNo($name, 0, 0);
            return true;
      }

      return false;
   }


   function displayAdditionalRuleAction(array $action) {

      switch ($action['type']) {
         case 'fusion_type' :
            Dropdown::showFromArray('value', self::getRuleActionValues());
            break;

         default :
            break;
      }
      return true;
   }


   function getCriteriaByID($ID) {

      $criteria = array();
      foreach ($this->criterias as $criterion) {
         if ($ID == $criterion->fields['criteria']) {
            $criteria[] = $criterion;
         }
      }
      return $criteria;
   }


   function findWithGlobalCriteria($input) {
      global $DB;

      $complex_criterias = array();
      $sql_where         = '';
      $sql_from          = '';
      $continue          = true;
      $global_criteria   = array('IPADDRESS', 'IPSUBNET', 'MACADDRESS', 'manufacturer', 'model',
                                 'name', 'serial');

      foreach ($global_criteria as $criterion) {
         $criteria = $this->getCriteriaByID($criterion);
         if (!empty($criteria)) {
            foreach ($criteria as $crit) {
               if (!isset($input[$criterion]) || $input[$criterion] == '') {
                  $continue = false;
               } else if ($crit->fields["condition"] == Rule::PATTERN_FIND) {
                  $complex_criterias[] = $crit;
               }
            }
         }
      }

      foreach ($this->getCriteriaByID('states_id') as $crit) {
         $complex_criterias[] = $crit;
      }

      //If a value is missing, then there's a problem !
      if (!$continue) {
         return false;
      }

      //No complex criteria
      if (empty($complex_criterias)) {
         return true;
      }

      //Build the request to check if the machine exists in GLPI
      if (is_array($input['entities_id'])) {
         $where_entity = implode($input['entities_id'],',');
      } else {
         $where_entity = $input['entities_id'];
      }

      $sql_where = " `glpi_computers`.`entities_id` IN ($where_entity)
                    AND `glpi_computers`.`is_template` = '0' ";
      $sql_from = "`glpi_computers`";

      foreach ($complex_criterias as $criteria) {
         switch ($criteria->fields['criteria']) {
            case 'IPADDRESS' :
               $sql_from .= " LEFT JOIN `glpi_networkports`
                                 ON (`glpi_computers`.`id` = `glpi_networkports`.`items_id`
                                     AND `glpi_networkports`.`itemtype` = 'Computer') ";
               $sql_where .= " AND `glpi_networkports`.`ip` IN ";
               for ($i=0 ; $i<count($input["IPADDRESS"]) ; $i++) {
                  $sql_where .= ($i>0 ? ',"' : '("').$input["IPADDRESS"][$i].'"';
               }
               $sql_where .= ")";
               break;

            case 'MACADDRESS' :
               $sql_where .= " AND `glpi_networkports`.`mac` IN (";
               $sql_where .= implode(',',$input['MACADDRESS']);
               $sql_where .= ")";
               break;

            case 'name' :
               if ($criteria->fields['condition'] == self::PATTERN_IS_EMPTY) {
                  $sql_where .= " AND (`glpi_computers`.`name`=''
                                       OR `glpi_computers`.`name` IS NULL) ";
               } else {
                  $sql_where .= " AND (`glpi_computers`.`name`='".$input['name']."') ";
               }
               break;

            case 'serial' :
               $sql_where .= " AND `glpi_computers`.`serial`='".$input["serial"]."'";
               break;

            case 'model' :
               // search for model, don't create it if not found
               $options    = array('manufacturer' => $input['manufacturer']);
               $mid        = Dropdown::importExternal('ComputerModel', $input['model'], -1,
                                                      $options, '', false);
               $sql_where .= " AND `glpi_computers`.`computermodels_id` = '$mid'";
               break;

            case 'manufacturer' :
               // search for manufacturer, don't create it if not found
               $mid        = Dropdown::importExternal('Manufacturer', $input['manufacturer'], -1,
                                                      array(), '', false);
               $sql_where .= " AND `glpi_computers`.`manufacturers_id` = '$mid'";
               break;

            case 'states_id' :
               if ($criteria->fields['condition'] == Rule::PATTERN_IS) {
                  $condition = " IN ";
               } else {
                  $conditin = " NOT IN ";
               }
               $sql_where .= " AND `glpi_computers`.`states_id`
                                 $condition ('".$criteria->fields['pattern']."')";
               break;
         }
      }

      $sql_glpi = "SELECT `glpi_computers`.`id`
                   FROM $sql_from
                   WHERE $sql_where
                   ORDER BY `glpi_computers`.`is_deleted` ASC";
      $result_glpi = $DB->query($sql_glpi);

      if ($DB->numrows($result_glpi) > 0) {
         while ($data=$DB->fetch_array($result_glpi)) {
            $this->criterias_results['found_computers'][] = $data['id'];
         }
         return true;
      }

      if (count($this->actions)) {
         foreach ($this->actions as $action) {
            if ($action->fields['field'] == '_fusion') {
               if ($action->fields["value"] == self::RULE_ACTION_LINK_OR_NO_IMPORT) {
                  return true;
               }
            }
         }
      }
      return false;

   }


   /**
    * Execute the actions as defined in the rule
    *
    * @param $output the fields to manipulate
    * @param $params parameters
    *
    * @return the $output array modified
   **/
   function executeActions($output, $params) {

      if (count($this->actions)) {
         foreach ($this->actions as $action) {
            if ($action->fields['field'] == '_fusion') {
               if ($action->fields["value"] == self::RULE_ACTION_LINK_OR_IMPORT) {
                  if (isset($this->criterias_results['found_computers'])) {
                     $output['found_computers'] = $this->criterias_results['found_computers'];
                     $output['action']          = PluginOcsinventoryngOcsServer::LINK_RESULT_LINK;
                  } else {
                     $output['action'] = PluginOcsinventoryngOcsServer::LINK_RESULT_IMPORT;
                  }

               } else if ($action->fields["value"] == self::RULE_ACTION_LINK_OR_NO_IMPORT) {
                  if (isset($this->criterias_results['found_computers'])) {
                     $output['found_computers'] = $this->criterias_results['found_computers'];
                     $output['action']          = PluginOcsinventoryngOcsServer::LINK_RESULT_LINK;
                  } else {
                     $output['action'] = PluginOcsinventoryngOcsServer::LINK_RESULT_NO_IMPORT;
                  }
               }

            } else {
               $output['action'] = PluginOcsinventoryngOcsServer::LINK_RESULT_NO_IMPORT;
            }
         }
      }
      return $output;
   }


   /**
    * Function used to display type specific criterias during rule's preview
    *
    * @param $fields fields values
   **/
   function showSpecificCriteriasForPreview($fields) {

      $entity_as_criteria = false;
      foreach ($this->criterias as $criteria) {
         if ($criteria->fields['criteria'] == 'entities_id') {
            $entity_as_criteria = true;
            break;
         }
      }
      if (!$entity_as_criteria) {
         echo "<input type='hidden' name='entities_id' value='".$_SESSION["glpiactive_entity"]."'>";
      }
   }


   function preProcessPreviewResults($output) {
      return PluginOcsinventoryngOcsServer::previewRuleImportProcess($output);
   }

}

?>
