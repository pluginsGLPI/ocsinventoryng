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

include('../../../inc/includes.php');

$dropdown = new PluginOcsinventoryngNotimportedcomputer();

if (isset($_POST['action'])) {
   switch ($_POST['action']) {
      case 'plugin_ocsinventoryng_import' :
         $_POST['force'] = true;

      case 'plugin_ocsinventoryng_replayrules' :
         if (PluginOcsinventoryngNotimportedcomputer::computerImport($_POST)) {
            $dropdown->redirectToList();
         } else {
            Html::redirect(Html::getItemTypeFormURL('PluginOcsinventoryngNotimportedcomputer') .
               '?id=' . $_POST['id']);
         }
         break;

      case 'plugin_ocsinventoryng_link' :
         $dropdown->linkComputer($_POST);
         $dropdown->redirectToList();
         break;
   }
}

include(GLPI_ROOT . "/front/dropdown.common.form.php");
