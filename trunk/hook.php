<?php
/*
 * @version $Id: HEADER 2011-03-12 18:01:26 tsmr $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
// ----------------------------------------------------------------------
// Original Author of file: CAILLAUD Xavier
// Purpose of file: plugin ocsinventoryng v 1.0.0 - GLPI 0.83
// ----------------------------------------------------------------------
 */

function plugin_ocsinventoryng_install() {
	global $DB;
	
	include_once (GLPI_ROOT."/plugins/ocsinventoryng/inc/profile.class.php");
	
	if (!TableExists("glpi_plugin_ocsinventoryng_profiles")) {
      
      $install=true;
		$DB->runFile(GLPI_ROOT ."/plugins/ocsinventoryng/sql/empty-1.0.0.sql");
	
	}
	CronTask::Register('PluginOcsinventoryngOcsServer', 'ocsng', MINUTE_TIMESTAMP*5);
   PluginOcsinventoryngProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
   
   $restrict = "`sub_type`= 'RuleOcs' ";
   $rules = getAllDatasFromTable("glpi_rules",$restrict);

   if (!empty($rules)) {
      $query="UPDATE `glpi_rules`
            SET `sub_type` = 'PluginOcsinventoryngRuleOcs'
            WHERE `sub_type` = 'RuleOcs';";
      $result=$DB->query($query);
   }
	return true;
}

function plugin_ocsinventoryng_uninstall() {
	global $DB;
	
	$tables = array("glpi_plugin_ocsinventoryng_ocsservers",
               "glpi_plugin_ocsinventoryng_ocslinks",
					"glpi_plugin_ocsinventoryng_ocsadmininfoslinks",
					"glpi_plugin_ocsinventoryng_profiles");

   foreach($tables as $table)
		$DB->query("DROP TABLE IF EXISTS `$table`;");
   
   $tables_glpi = array("glpi_displaypreferences",
					"glpi_documents_items",
					"glpi_bookmarks",
					"glpi_logs",
               "glpi_tickets");

	foreach($tables_glpi as $table_glpi)
		$DB->query("DELETE FROM `$table_glpi` WHERE `itemtype` = 'PluginOcsinventoryngOcsServer';");

	return true;
}

// Define headings added by the plugin
function plugin_get_headings_ocsinventoryng($item,$withtemplate) {
	global $LANG;
	
	if (get_class($item)=='Profile') {
		if ($item->getField('id')) {
			return array(
				1 => $LANG['plugin_ocsinventoryng']['title'][1],
				);
		} else {
			return array();			
		}
	}
	return false;
	
}

// Define headings actions added by the plugin	 
function plugin_headings_actions_ocsinventoryng($item) {
		
	if (get_class($item)=='Profile') {
		return array(
					1 => "plugin_headings_ocsinventoryng",
					);
	} else
		return false;
	
}

// action heading
function plugin_headings_ocsinventoryng($item,$withtemplate=0) {
	global $CFG_GLPI;
		
   $PluginOcsinventoryngProfile=new PluginOcsinventoryngProfile();
   
   switch (get_class($item)) {
      case 'Profile' :
         if (!$PluginOcsinventoryngProfile->getFromDBByProfile($item->getField('id')))
            $PluginOcsinventoryngProfile->createAccess($item->getField('id'));
         $PluginOcsinventoryngProfile->showForm($item->getField('id'), array('target' => $CFG_GLPI["root_doc"]."/plugins/ocsinventoryng/front/profile.form.php"));
         break;
   }

}

?>