<?php
/*
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginOcsinventoryngNotificationState
 */
class PluginOcsinventoryngNotificationState extends CommonDBTM {

   static $rightname = "plugin_ocsinventoryng";

   /**
    *
    */
   function configState() {
      global $DB;

      $rand = mt_rand();

      $query = "SELECT *
                 FROM `" . $this->getTable() . "`
                 ORDER BY `states_id` ASC ";
      if ($result = $DB->query($query)) {
         $number = $DB->numrows($result);
         if ($number != 0) {

            echo "<div align='center'>";

            Html::openMassiveActionsForm('mass' . $this->getType() . $rand);
            $massiveactionparams = ['item'      => $this->getType(),
                                    'container' => 'mass' . $this->getType() . $rand];
            Html::showMassiveActions($massiveactionparams);

            echo "<table class='tab_cadre_fixe' cellpadding='5'>";
            echo "<tr>";
            echo "<th width='10'>";
            echo Html::getCheckAllAsCheckbox('mass' . $this->getType() . $rand);
            echo "</th>";
            echo "<th>" . _n('Status', 'Statuses', 2) . "</th>";
            echo "</tr>";
            while ($ligne = $DB->fetchArray($result)) {
               echo "<tr class='tab_bg_1'>";
               echo "<td width='10' class='center'>";
               Html::showMassiveActionCheckBox($this->getType(), $ligne['id'], ['class' => $this]);
               echo "</td>";
               echo "<td>" . Dropdown::getDropdownName("glpi_states", $ligne["states_id"]) . "</td>";
               echo "</tr>";
            }
            echo "</table>";

            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
            echo "</div>";
         }
      }
   }

   /**
    * Get the standard massive actions which are forbidden
    *
    * @since version 0.84
    *
    * @return array of massive actions
    **/
   public function getForbiddenStandardMassiveAction() {

      $forbidden = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }

   /**
    * Get the specific massive actions
    *
    * @since version 0.84
    *
    * @param $checkitem link item to check right   (default NULL)
    *
    * @return an $array of massive actions
    */
   public function getSpecificMassiveActions($checkitem = null) {


      $actions['PluginOcsinventoryngNotificationState' . MassiveAction::CLASS_ACTION_SEPARATOR . 'purge'] = __('Delete');

      return $actions;
   }

   /**
    * @param MassiveAction $ma
    *
    * @return bool|false
    */
   /**
    * @param MassiveAction $ma
    *
    * @return bool|false
    */
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'purge':
            echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction', 'class' => 'btn btn-primary']);
            return true;
      }
      return parent::showMassiveActionsSubForm($ma);
   }

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
    *
    * @param MassiveAction $ma
    * @param CommonDBTM    $item
    * @param array         $ids
    *
    * @return nothing|void
    */
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      switch ($ma->getAction()) {
         case "purge":
            foreach ($ids as $key) {
               if ($item->can($key, UPDATE)) {
                  if ($item->delete(['id' => $key])) {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                  }
               } else {
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                  $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
               }
            }
            break;
      }
   }
}
