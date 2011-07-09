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
		$DB->runFile(GLPI_ROOT ."/plugins/ocsinventoryng/install/mysql/1.0.0-empty.sql");
	
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
   
   //Tables from massocsimport
   if (!TableExists('glpi_plugin_ocsinventoryng_threads')) { //not installed

      $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_threads` (
                  `id` int(11) NOT NULL auto_increment,
                  `threadid` int(11) NOT NULL default '0',
                  `start_time` datetime default NULL,
                  `end_time` datetime default NULL,
                  `status` int(11) NOT NULL default '0',
                  `error_msg` text NOT NULL,
                  `imported_machines_number` int(11) NOT NULL default '0',
                  `synchronized_machines_number` int(11) NOT NULL default '0',
                  `failed_rules_machines_number` int(11) NOT NULL default '0',
                  `linked_machines_number` int(11) NOT NULL default '0',
                  `notupdated_machines_number` int(11) NOT NULL default '0',
                  `not_unique_machines_number` int(11) NOT NULL default '0',
                  `link_refused_machines_number` int(11) NOT NULL default '0',
                  `total_number_machines` int(11) NOT NULL default '0',
                  `plugin_ocsinventoryng_ocsservers_id` int(11) NOT NULL default '1',
                  `processid` int(11) NOT NULL default '0',
                  PRIMARY KEY  (`id`),
                  KEY `end_time` (`end_time`),
                  KEY `process_thread` (`processid`,`threadid`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";

      $DB->query($query) or die($DB->error());


      $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_configs` (
                  `id` int(11) NOT NULL auto_increment,
                  `thread_log_frequency` int(11) NOT NULL default '10',
                  `is_displayempty` int(1) NOT NULL default '1',
                  `import_limit` int(11) NOT NULL default '0',
                  `plugin_ocsinventoryng_ocsservers_id` int(11) NOT NULL default '-1',
                  `delay_refresh` int(11) NOT NULL default '0',
                  `allow_ocs_update` tinyint(1) NOT NULL default '0',
                  `comment` text,
                  PRIMARY KEY (`id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";

      $DB->query($query) or die($DB->error());

      $query = "INSERT INTO `glpi_plugin_ocsinventoryng_configs`
                     (`id`,`thread_log_frequency`,`is_displayempty`,`import_limit`,`plugin_ocsinventoryng_ocsservers_id`)
                VALUES (1, 2, 1, 0,-1);";
      $DB->query($query) or die($DB->error());


      $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_details` (
                  `id` int(11) NOT NULL auto_increment,
                  `entities_id` int(11) NOT NULL default '0',
                  `plugin_ocsinventoryng_threads_id` int(11) NOT NULL default '0',
                  `rules_id` TEXT,
                  `threadid` int(11) NOT NULL default '0',
                  `ocsid` int(11) NOT NULL default '0',
                  `computers_id` int(11) NOT NULL default '0',
                  `action` int(11) NOT NULL default '0',
                  `process_time` datetime DEFAULT NULL,
                  `plugin_ocsinventoryng_ocsservers_id` int(11) NOT NULL default '1',
                  PRIMARY KEY (`id`),
                  KEY `end_time` (`process_time`),
                  KEY `process_thread` (`plugin_ocsinventoryng_threads_id`,`threadid`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->query($query) or die($DB->error());

      $query = "INSERT INTO `glpi_displaypreferences` (`itemtype`, `num`, `rank`, `users_id`)
                VALUES ('PluginOcsinventoryngNotimported', 2, 1, 0),
                       ('PluginOcsinventoryngNotimported', 3, 2, 0),
                       ('PluginOcsinventoryngNotimported', 4, 3, 0),
                       ('PluginOcsinventoryngNotimported', 5, 4, 0),
                       ('PluginOcsinventoryngNotimported', 6, 5, 0),
                       ('PluginOcsinventoryngNotimported', 7, 6, 0),
                       ('PluginOcsinventoryngNotimported', 8, 7, 0),
                       ('PluginOcsinventoryngNotimported', 9, 8, 0),
                       ('PluginOcsinventoryngNotimported', 10, 9, 0),
                       ('PluginOcsinventoryngDetail', 5, 1, 0),
                       ('PluginOcsinventoryngDetail', 2, 2, 0),
                       ('PluginOcsinventoryngDetail', 3, 3, 0),
                       ('PluginOcsinventoryngDetail', 4, 4, 0),
                       ('PluginOcsinventoryngDetail', 6, 5, 0),
                       ('PluginOcsinventoryngDetail', 80, 6, 0)";
      $DB->query($query) or die($DB->error());


      $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_notimported` (
                  `id` INT( 11 ) NOT NULL  auto_increment,
                  `entities_id` int(11) NOT NULL default '0',
                  `rules_id` TEXT,
                  `comment` text NULL,
                  `ocsid` INT( 11 ) NOT NULL DEFAULT '0',
                  `plugin_ocsinventoryng_ocsservers_id` INT( 11 ) NOT NULL ,
                  `ocs_deviceid` VARCHAR( 255 ) NOT NULL ,
                  `useragent` VARCHAR( 255 ) NOT NULL ,
                  `tag` VARCHAR( 255 ) NOT NULL ,
                  `serial` VARCHAR( 255 ) NOT NULL ,
                  `name` VARCHAR( 255 ) NOT NULL ,
                  `ipaddr` VARCHAR( 255 ) NOT NULL ,
                  `domain` VARCHAR( 255 ) NOT NULL ,
                  `last_inventory` DATETIME ,
                  `reason` INT( 11 ) NOT NULL ,
                  PRIMARY KEY ( `id` ),
                  UNIQUE KEY `ocs_id` (`plugin_ocsinventoryng_ocsservers_id`,`ocsid`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";

      $DB->query($query) or die($DB->error());


      $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_servers` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `plugin_ocsinventoryng_ocsservers_id` int(11) NOT NULL DEFAULT '0',
                  `max_ocsid` int(11) DEFAULT NULL,
                  `max_glpidate` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `plugin_ocsinventoryng_ocsservers_id` (`plugin_ocsinventoryng_ocsservers_id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";

      $DB->query($query) or die($DB->error());

      $query = "SELECT id " .
               "FROM `glpi_notificationtemplates` " .
               "WHERE `itemtype`='PluginOcsinventoryngNotimported'";
      $result=$DB->query($query);
      if (!$DB->numrows($result)) {
   
         //Add template
         $query = "INSERT INTO `glpi_notificationtemplates` " .
                  "VALUES(NULL, 'Computers not imported', 'PluginOcsinventoryngNotimported', NOW(), '', NULL);";
         $DB->query($query) or die($DB->error());
         $templates_id = $DB->insert_id();
         $query = "INSERT INTO `glpi_notificationtemplatetranslations` " .
                  "VALUES(NULL, $templates_id, '', '##lang.notimported.action## : ##notimported.entity##'," .
                  " '\r\n\n##lang.notimported.action## :&#160;##notimported.entity##\n\n" .
                  "##FOREACHnotimported##&#160;\n##lang.notimported.reason## : ##notimported.reason##\n" .
                  "##lang.notimported.name## : ##notimported.name##\n" .
                  "##lang.notimported.deviceid## : ##notimported.deviceid##\n" .
                  "##lang.notimported.tag## : ##notimported.tag##\n##lang.notimported.serial## : ##notimported.serial## \r\n\n" .
                  " ##notimported.url## \n##ENDFOREACHnotimported## \r\n', '&lt;p&gt;##lang.notimported.action## :&#160;##notimported.entity##&lt;br /&gt;&lt;br /&gt;" .
                  "##FOREACHnotimported##&#160;&lt;br /&gt;##lang.notimported.reason## : ##notimported.reason##&lt;br /&gt;" .
                  "##lang.notimported.name## : ##notimported.name##&lt;br /&gt;" .
                  "##lang.notimported.deviceid## : ##notimported.deviceid##&lt;br /&gt;" .
                  "##lang.notimported.tag## : ##notimported.tag##&lt;br /&gt;" .
                  "##lang.notimported.serial## : ##notimported.serial##&lt;/p&gt;\r\n&lt;p&gt;&lt;a href=\"##notimported.url##\"&gt;" .
                  "##notimported.url##&lt;/a&gt;&lt;br /&gt;##ENDFOREACHnotimported##&lt;/p&gt;');";
         $DB->query($query) or die($DB->error());
   
         $query = "INSERT INTO `glpi_notifications` 
                   VALUES (NULL, 'Computers not imported', 0, 'PluginOcsinventoryngNotimported', 'not_imported',
                           'mail',".$templates_id.", '', 1, 1, NOW());";
         $DB->query($query) or die($DB->error());
      }
   }

   $cron = new CronTask;
   if (!$cron->getFromDBbyName('PluginOcsinventoryngThread','CleanOldThreads')) {
      // creation du cron - param = duree de conservation
      CronTask::Register('PluginOcsinventoryngThread', 'CleanOldThreads', HOUR_TIMESTAMP,
                         array('param' => 24));
   }
   
	return true;
}
/*
function plugin_massocsimport_install() {
   global $DB;

   //Upgrade process if needed
   if (TableExists("glpi_plugin_mass_ocs_import")) { //1.1 ou 1.2
      if (!FieldExists('glpi_plugin_mass_ocs_import_config','warn_if_not_imported')) { //1.1
         plugin_massocsimport_upgrade11to12();
      }
   }
   if (TableExists("glpi_plugin_mass_ocs_import")) { //1.2 because if before
      plugin_massocsimport_upgrade121to13();
   }
   if (TableExists("glpi_plugin_massocsimport")) { //1.3 ou 1.4
      if (FieldExists('glpi_plugin_massocsimport','ID')) { //1.3
         plugin_massocsimport_upgrade13to14();
      }
   }
   if (TableExists('glpi_plugin_massocsimport_threads') 
         && !FieldExists('glpi_plugin_massocsimport_threads','not_unique_machines_number')) {
         plugin_massocsimport_upgrade14to15();
   }
   

   return true;
}


function plugin_massocsimport_upgrade11to12() {
   global $DB;

   // plugin tables
   if (!TableExists("glpi_plugin_mass_ocs_import_config")) {
      $query = "CREATE TABLE `glpi_plugin_mass_ocs_import_config` (
                  `ID` int(11) NOT NULL,
                  `enable_logging` int(1) NOT NULL default '1',
                  `thread_log_frequency` int(4) NOT NULL default '10',
                  `display_empty` int(1) NOT NULL default '1',
                  `delete_frequency` int(4) NOT NULL default '0',
                  `import_limit` int(11) NOT NULL default '0',
                  `default_ocs_server` int(11) NOT NULL default '-1',
                  `delay_refresh` varchar(4) NOT NULL default '0',
                  `delete_empty_frequency` int(4) NOT NULL default '0',
                  PRIMARY KEY  (`ID`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";

      $DB->query($query) or die($DB->error());

      $query = "INSERT INTO `glpi_plugin_mass_ocs_import_config`
                     (`ID`, `enable_logging`, `thread_log_frequency`, `display_empty`,
                      `delete_frequency`, `delete_empty_frequency`, `import_limit`,
                      `default_ocs_server` )
                VALUES (1, 1, 5, 1, 2, 2, 0,-1)";

      $DB->query($query) or die($DB->error());
   }

   if (!FieldExists("glpi_plugin_mass_ocs_import_config", "warn_if_not_imported")) {
      $query = "ALTER TABLE `glpi_plugin_mass_ocs_import_config`
                ADD `warn_if_not_imported` int(1) NOT NULL default '0'";
      $DB->query($query) or die($DB->error());
   }

   if (!FieldExists("glpi_plugin_mass_ocs_import_config", "not_imported_threshold")) {
      $query = "ALTER TABLE `glpi_plugin_mass_ocs_import_config`
                ADD `not_imported_threshold` int(11) NOT NULL default '0';";
      $DB->query($query) or die($DB->error());
   }
}


function plugin_massocsimport_upgrade121to13() {
   global $DB;

   if (TableExists("glpi_plugin_mass_ocs_import_config")) {
      $tables = array (
            "glpi_plugin_massocsimport_servers"      => "glpi_plugin_mass_ocs_import_servers",
            "glpi_plugin_massocsimport"              => "glpi_plugin_mass_ocs_import",
            "glpi_plugin_massocsimport_config"       => "glpi_plugin_mass_ocs_import_config",
            "glpi_plugin_massocsimport_not_imported" => "glpi_plugin_mass_ocs_import_not_imported");

      foreach ($tables as $new => $old) {
         if (!TableExists($new)) {
            $query = "RENAME TABLE `$old`
                      TO `$new`;";
            $DB->query($query) or die($DB->error());
         }
      }

      $query = "ALTER TABLE `glpi_plugin_massocsimport`
                CHANGE `process_id` `process_id` BIGINT( 20 ) NOT NULL DEFAULT '0'";
      $DB->query($query) or die($DB->error());

      if (!FieldExists("glpi_plugin_massocsimport_config","comments")) {
         $query = " ALTER TABLE `glpi_plugin_massocsimport_config`
                    ADD `comments` TEXT NULL  ";
         $DB->query($query) or die($DB->error());
      }

      if (!FieldExists("glpi_plugin_massocsimport", "noupdate_machines_number")) {
         $query = " ALTER TABLE `glpi_plugin_massocsimport`
                    ADD `noupdate_machines_number` int(11) NOT NULL default '0'";
         $DB->query($query) or die($DB->error());
      }

      if (!TableExists("glpi_plugin_massocsimport_details")) {
         $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_massocsimport_details` (
                     `ID` int(11) NOT NULL auto_increment,
                     `process_id` bigint(10) NOT NULL default '0',
                     `thread_id` int(4) NOT NULL default '0',
                     `ocs_id` int(11) NOT NULL default '0',
                     `glpi_id` int(11) NOT NULL default '0',
                     `action` int(11) NOT NULL default '0',
                     `process_time` datetime DEFAULT NULL,
                     `ocs_server_id` int(4) NOT NULL default '1',
                     PRIMARY KEY  (`ID`),
                     KEY `end_time` (`process_time`)
                   ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->query($query) or die($DB->error());
      }

      //Add fields to the default view
      $query = "INSERT INTO `glpi_displayprefs` (`itemtype`, `num`, `rank`, `users_id`)
                VALUES (" . PLUGIN_MASSOCSIMPORT_NOTIMPORTED . ", 2, 1, 0),
                       (" . PLUGIN_MASSOCSIMPORT_NOTIMPORTED . ", 3, 2, 0),
                       (" . PLUGIN_MASSOCSIMPORT_NOTIMPORTED . ", 4, 3, 0),
                       (" . PLUGIN_MASSOCSIMPORT_NOTIMPORTED . ", 5, 4, 0),
                       (" . PLUGIN_MASSOCSIMPORT_NOTIMPORTED . ", 6, 5, 0),
                       (" . PLUGIN_MASSOCSIMPORT_NOTIMPORTED . ", 7, 6, 0),
                       (" . PLUGIN_MASSOCSIMPORT_NOTIMPORTED . ", 8, 7, 0),
                       (" . PLUGIN_MASSOCSIMPORT_NOTIMPORTED . ", 8, 7, 0),
                       (" . PLUGIN_MASSOCSIMPORT_NOTIMPORTED . ", 10, 9, 0),
                       (" . PLUGIN_MASSOCSIMPORT_DETAIL . ", 5, 1, 0),
                       (" . PLUGIN_MASSOCSIMPORT_DETAIL . ", 2, 2, 0),
                       (" . PLUGIN_MASSOCSIMPORT_DETAIL . ", 3, 3, 0),
                       (" . PLUGIN_MASSOCSIMPORT_DETAIL . ", 4, 4, 0),
                       (" . PLUGIN_MASSOCSIMPORT_DETAIL . ", 6, 5, 0)";
      $DB->query($query);// or die($DB->error());

      $drop_fields = array (//Was not used, debug only...
                            "glpi_plugin_massocsimport_config" => "warn_if_not_imported",
                            "glpi_plugin_massocsimport_config" => "not_imported_threshold",
                            //Logging must always be enable !
                            "glpi_plugin_massocsimport_config" => "enable_logging",
                            "glpi_plugin_massocsimport_config" => "delete_empty_frequency");

      foreach ($drop_fields as $table => $field) {
         if (FieldExists($table, $field)) {
            $query = "ALTER TABLE `$table`
                      DROP `$field`";
            $DB->query($query) or die($DB->error());
         }
      }
   }
}


function plugin_massocsimport_upgrade13to14() {
   global $DB;

   if (TableExists("glpi_plugin_massocsimport")) {
      $DB->query("RENAME TABLE `glpi_plugin_massocsimport` to `glpi_plugin_massocsimport_threads`");

      $query = "ALTER TABLE `glpi_plugin_massocsimport_threads` ";

      if (FieldExists("glpi_plugin_massocsimport_threads", "ID")) {
         $query .= " CHANGE `ID` `id` int(11) NOT NULL auto_increment,";
      }
      if (FieldExists("glpi_plugin_massocsimport_threads", "thread_id")) {
         $query .= " CHANGE `thread_id` `threadid` int(11) NOT NULL default '0',";
      }
      if (FieldExists("glpi_plugin_massocsimport_threads", "status")) {
         $query .= " CHANGE `status` `status` int(11) NOT NULL default '0',";
      }
      if (FieldExists("glpi_plugin_massocsimport_threads", "ocs_server_id")) {
         $query .= " CHANGE `ocs_server_id` `ocsservers_id` int(11) NOT NULL default '1',";
      }
      if (FieldExists("glpi_plugin_massocsimport_threads", "process_id")) {
         $query .= " CHANGE `process_id` `processid` int(11) NOT NULL,";
      }
      if (FieldExists("glpi_plugin_massocsimport_threads", "noupdate_machines_number")) {
         $query .= " CHANGE `noupdate_machines_number` `notupdated_machines_number` int(11) NOT NULL default '0',";
      }
      $query .= " ADD KEY `process_thread` (`processid`,`threadid`)";
      $DB->query($query) or die($DB->error());
   }

   if (TableExists("glpi_plugin_massocsimport_config")) {
      $DB->query("RENAME TABLE `glpi_plugin_massocsimport_config` to `glpi_plugin_massocsimport_configs`");

      $query = "ALTER TABLE `glpi_plugin_massocsimport_configs` ";

      if (FieldExists("glpi_plugin_massocsimport_configs", "delete_frequency")) {
         $query .= " DROP `delete_frequency`,";
      }
      if (FieldExists("glpi_plugin_massocsimport_configs", "enable_logging")) {
         $query .= " DROP `enable_logging`,";
      }
      if (FieldExists("glpi_plugin_massocsimport_configs", "delete_empty_frequency")) {
         $query .= " DROP `delete_empty_frequency`,";
      }
      if (FieldExists("glpi_plugin_massocsimport_configs", "warn_if_not_imported")) {
         $query .= " DROP `warn_if_not_imported`,";
      }
      if (FieldExists("glpi_plugin_massocsimport_configs", "imported_threshold")) {
         $query .= " DROP `not_imported_threshold`,";
      }

      if (FieldExists("glpi_plugin_massocsimport_configs", "ID")) {
         $query .= " CHANGE `ID` `id` int(11) NOT NULL auto_increment,";
      }
      if (FieldExists("glpi_plugin_massocsimport_configs", "thread_log_frequency")) {
         $query .= " CHANGE `thread_log_frequency` `thread_log_frequency` int(11) NOT NULL default '10',";
      }
      if (FieldExists("glpi_plugin_massocsimport_configs", "display_empty")) {
         $query .= " CHANGE `display_empty` `is_displayempty` int(1) NOT NULL default '1',";
      }
      if (FieldExists("glpi_plugin_massocsimport_configs", "default_ocs_server")) {
         $query .= " CHANGE `default_ocs_server` `ocsservers_id` int(11) NOT NULL default '-1',";
      }
      if (FieldExists("glpi_plugin_massocsimport_configs", "delay_refresh")) {
         $query .= " CHANGE `delay_refresh` `delay_refresh` int(11) NOT NULL default '0',";
      }
      if (FieldExists("glpi_plugin_massocsimport_configs", "comments")) {
         $query .= " CHANGE `comments` `comment` text";
      }
      $DB->query($query) or die($DB->error());
   }


   if (TableExists("glpi_plugin_massocsimport_details")) {
      $query = "ALTER TABLE `glpi_plugin_massocsimport_details` ";

      if (FieldExists("glpi_plugin_massocsimport_details", "ID")) {
         $query .= " CHANGE `ID` `id` int(11) NOT NULL auto_increment,";
      }
       if (FieldExists("glpi_plugin_massocsimport_details", "process_id")) {
         $query .= " CHANGE `process_id` `plugin_massocsimport_threads_id` int(11) NOT NULL default '0',";
      }
       if (FieldExists("glpi_plugin_massocsimport_details", "thread_id")) {
         $query .= " CHANGE `thread_id` `threadid` int(11) NOT NULL default '0',";
      }
       if (FieldExists("glpi_plugin_massocsimport_details", "ocs_id")) {
         $query .= " CHANGE `ocs_id` `ocsid` int(11) NOT NULL default '0',";
      }
       if (FieldExists("glpi_plugin_massocsimport_details", "glpi_id")) {
         $query .= " CHANGE `glpi_id` `computers_id` int(11) NOT NULL default '0',";
      }
       if (FieldExists("glpi_plugin_massocsimport_details", "ocs_server_id")) {
         $query .= " CHANGE `ocs_server_id` `ocsservers_id` int(11) NOT NULL default '1',";
      }
      $query .= " ADD KEY `process_thread` (`plugin_massocsimport_threads_id`,`threadid`)";

      $DB->query($query) or die($DB->error());
   }


   if (TableExists("glpi_plugin_massocsimport_not_imported")) {
      $DB->query("RENAME TABLE `glpi_plugin_massocsimport_not_imported` to `glpi_plugin_massocsimport_notimported`");

      $query = "ALTER TABLE `glpi_plugin_massocsimport_notimported` ";
      $query .= " CHANGE `ID` `id` INT( 11 ) NOT NULL  auto_increment,";

       if (FieldExists("glpi_plugin_massocsimport_notimported", "ocs_id")) {
         $query .= " CHANGE `ocs_id` `ocsid` INT( 11 ) NOT NULL,";
      }
       if (FieldExists("glpi_plugin_massocsimport_notimported", "ocs_server_id")) {
         $query .= " CHANGE `ocs_server_id` `ocsservers_id` INT( 11 ) NOT NULL,";
      }
       if (FieldExists("glpi_plugin_massocsimport_notimported", "deviceid")) {
         $query .= " CHANGE `deviceid` `ocs_deviceid` VARCHAR( 255 ) NOT NULL";
      }

      $DB->query($query) or die($DB->error());
   }


   if (TableExists("glpi_plugin_massocsimport_servers")) {
      $query = "ALTER TABLE `glpi_plugin_massocsimport_servers` ";
      $query .= " CHANGE `ID` `id` INT( 11 ) NOT NULL  auto_increment,";

      if (FieldExists("glpi_plugin_massocsimport_servers", "ocs_server_id")) {
         $query .= " CHANGE `ocs_server_id` `ocsservers_id` INT(11) NOT NULL,";
      }
      if (FieldExists("glpi_plugin_massocsimport_servers", "max_ocs_id")) {
         $query .= " CHANGE `max_ocs_id` `max_ocsid` int(11) DEFAULT NULL,";
      }
      if (FieldExists("glpi_plugin_massocsimport_servers", "max_glpi_date")) {
         $query .= " CHANGE `max_glpi_date` `max_glpidate` datetime DEFAULT NULL";
      }

      $DB->query($query) or die($DB->error());
   }

}

function plugin_massocsimport_upgrade14to15() {
   global $DB;

   if (TableExists('glpi_plugin_massocsimport_threads')) {
      if (!FieldExists("glpi_plugin_massocsimport_threads", "not_unique_machines_number")) {
         $query = "ALTER TABLE `glpi_plugin_massocsimport_threads`  ADD `not_unique_machines_number` int(11) NOT NULL DEFAULT 0";
         $DB->query($query) or die($DB->error());
      }
      if (!FieldExists("glpi_plugin_massocsimport_threads", "link_refused_machines_number")) {
         $query = "ALTER TABLE `glpi_plugin_massocsimport_threads`  ADD `link_refused_machines_number` int(11) NOT NULL DEFAULT 0";
         $DB->query($query) or die($DB->error());
      }

      if (!FieldExists("glpi_plugin_massocsimport_threads", "entities_id")) {
         $query = "ALTER TABLE `glpi_plugin_massocsimport_threads`  ADD `entities_id` int(11) NOT NULL DEFAULT 0";
         $DB->query($query) or die($DB->error());
      }

      if (!FieldExists("glpi_plugin_massocsimport_threads", "rules_id")) {
         $query = "ALTER TABLE `glpi_plugin_massocsimport_threads`  ADD `rules_id` int(11) NOT NULL DEFAULT 0";
         $DB->query($query) or die($DB->error());
      }
   }

   if (!FieldExists('glpi_plugin_massocsimport_configs','allow_ocs_update')) {
      $query = "ALTER TABLE  `glpi_plugin_massocsimport_configs` ADD  `allow_ocs_update` TINYINT( 1 ) NOT NULL DEFAULT  '0'";
      $DB->query($query) or die($DB->error());
   }

   if (!FieldExists('glpi_plugin_massocsimport_notimported','reason')) {
      $query = "ALTER TABLE  `glpi_plugin_massocsimport_notimported` ADD  `reason` INT( 11 ) NOT NULL DEFAULT  '0'";
      $DB->query($query) or die($DB->error());
      $query = "INSERT INTO `glpi_displaypreferences` (`itemtype`, `num`, `rank`, `users_id`)
                VALUES ('PluginOcsinventoryngNotimported', 10, 9, 0)";
      $DB->query($query) or die($DB->error());
   }
   if (!FieldExists('glpi_plugin_massocsimport_notimported','serial')) {
      $query = "ALTER TABLE  `glpi_plugin_massocsimport_notimported` ADD  `serial` VARCHAR( 255 ) NOT NULL DEFAULT ''";
      $DB->query($query) or die($DB->error());
   }
   if (!FieldExists('glpi_plugin_massocsimport_notimported','comment')) {
      $query = "ALTER TABLE  `glpi_plugin_massocsimport_notimported` ADD  `comment` TEXT NOT NULL";
      $DB->query($query) or die($DB->error());
   }
   if (!FieldExists('glpi_plugin_massocsimport_notimported','rules_id')) {
      $query = "ALTER TABLE  `glpi_plugin_massocsimport_notimported` ADD  `rules_id` TEXT";
      $DB->query($query) or die($DB->error());
   }

   if (!FieldExists("glpi_plugin_massocsimport_notimported", "entities_id")) {
      $query = "ALTER TABLE  `glpi_plugin_massocsimport_notimported` ADD `entities_id` int(11) NOT NULL DEFAULT 0";
      $DB->query($query) or die($DB->error());
   }

   if (!FieldExists("glpi_plugin_massocsimport_details", "entities_id")) {
      $query = "ALTER TABLE `glpi_plugin_massocsimport_details`  ADD `entities_id` int(11) NOT NULL DEFAULT 0";
      $DB->query($query) or die($DB->error());
   }

   if (!FieldExists("glpi_plugin_massocsimport_details", "rules_id")) {
      $query = "ALTER TABLE `glpi_plugin_massocsimport_details`  ADD `rules_id` TEXT";
      $DB->query($query) or die($DB->error());
   }

   $query = "SELECT id " .
            "FROM `glpi_notificationtemplates` " .
            "WHERE `itemtype`='PluginOcsinventoryngNotimported'";
   $result=$DB->query($query);
   if (!$DB->numrows($result)) {

      //Add template
      $query = "INSERT INTO `glpi_notificationtemplates` " .
               "VALUES(NULL, 'Computers not imported', 'PluginOcsinventoryngNotimported', NOW(), '', '');";
      $DB->query($query) or die($DB->error());
      $templates_id = $DB->insert_id();
      $query = "INSERT INTO `glpi_notificationtemplatetranslations` " .
               "VALUES(NULL, $templates_id, '', '##lang.notimported.action## : ##notimported.entity##'," .
               " '\r\n\n##lang.notimported.action## :&#160;##notimported.entity##\n\n" .
               "##FOREACHnotimported##&#160;\n##lang.notimported.reason## : ##notimported.reason##\n" .
               "##lang.notimported.name## : ##notimported.name##\n" .
               "##lang.notimported.deviceid## : ##notimported.deviceid##\n" .
               "##lang.notimported.tag## : ##notimported.tag##\n##lang.notimported.serial## : ##notimported.serial## \r\n\n" .
               " ##notimported.url## \n##ENDFOREACHnotimported## \r\n', '&lt;p&gt;##lang.notimported.action## :&#160;##notimported.entity##&lt;br /&gt;&lt;br /&gt;" .
               "##FOREACHnotimported##&#160;&lt;br /&gt;##lang.notimported.reason## : ##notimported.reason##&lt;br /&gt;" .
               "##lang.notimported.name## : ##notimported.name##&lt;br /&gt;" .
               "##lang.notimported.deviceid## : ##notimported.deviceid##&lt;br /&gt;" .
               "##lang.notimported.tag## : ##notimported.tag##&lt;br /&gt;" .
               "##lang.notimported.serial## : ##notimported.serial##&lt;/p&gt;\r\n&lt;p&gt;&lt;a href=\"##infocom.url##\"&gt;" .
               "##notimported.url##&lt;/a&gt;&lt;br /&gt;##ENDFOREACHnotimported##&lt;/p&gt;');";
      $DB->query($query) or die($DB->error());
   
      $query = "INSERT INTO `glpi_notifications` 
                VALUES (NULL, 'Computers not imported', 0, 'PluginOcsinventoryngNotimported', 'not_imported',
                        'mail',".$templates_id.", '', 1, 1, NOW());";
      $DB->query($query) or die($DB->error());
   }
   
}*/

function plugin_ocsinventoryng_uninstall() {
	global $DB;
	
	$tables = array("glpi_plugin_ocsinventoryng_ocsservers",
               "glpi_plugin_ocsinventoryng_ocslinks",
					"glpi_plugin_ocsinventoryng_ocsadmininfoslinks",
					"glpi_plugin_ocsinventoryng_profiles",
					"glpi_plugin_ocsinventoryng_threads",
					"glpi_plugin_ocsinventoryng_servers",
					"glpi_plugin_ocsinventoryng_configs",
					"glpi_plugin_ocsinventoryng_notimported",
					"glpi_plugin_ocsinventoryng_details",
					"glpi_plugin_ocsinventoryng_registrykeys");

   foreach($tables as $table)
		$DB->query("DROP TABLE IF EXISTS `$table`;");
   
   $massoldtables = array ("glpi_plugin_mass_ocs_import",
                    "glpi_plugin_massocsimport",
                    "glpi_plugin_massocsimport_threads",
                    "glpi_plugin_mass_ocs_import_servers",
                    "glpi_plugin_massocsimport_servers",
                    "glpi_plugin_mass_ocs_import_config",
                    "glpi_plugin_massocsimport_config",
                    "glpi_plugin_massocsimport_configs",
                    "glpi_plugin_mass_ocs_import_not_imported",
                    "glpi_plugin_massocsimport_not_imported",
                    "glpi_plugin_massocsimport_notimported",
                    "glpi_plugin_massocsimport_details");

   foreach ($massoldtables as $massoldtable) {
      $query = "DROP TABLE IF EXISTS `$massoldtable`;";
      $DB->query($query) or die($DB->error());
   }
   
   $tables_glpi = array("glpi_displaypreferences",
					"glpi_documents_items",
					"glpi_bookmarks",
					"glpi_logs",
               "glpi_tickets");

	foreach($tables_glpi as $table_glpi)
		$DB->query("DELETE FROM `$table_glpi` WHERE `itemtype` IN ('PluginMassocsimportNotimported',
                                                                  'PluginMassocsimportDetail',
                                                                  'PluginOcsinventoryngOcsServer',
                                                                  'PluginOcsinventoryngNotimported',
                                                                  'PluginOcsinventoryngDetail');");
   
   $query = "DELETE FROM `glpi_alerts` WHERE `itemtype` IN ('PluginMassocsimportNotimported',
                                                            'PluginOcsinventoryngNotimported')";
   $DB->query($query) or die($DB->error());

   $notification = new Notification();
   foreach (getAllDatasFromTable($notification->getTable(),"`itemtype` IN ('PluginMassocsimportNotimported',
                                                                           'PluginOcsinventoryngNotimported')") as $data) {
      $notification->delete($data);
   }
   $template = new NotificationTemplate();
   foreach (getAllDatasFromTable($template->getTable(),"`itemtype` IN ('PluginMassocsimportNotimported',
                                                                        'PluginOcsinventoryngNotimported')") as $data) {
      $template->delete($data);
   }

   $cron = new CronTask;
   if ($cron->getFromDBbyName('PluginMassocsimportThread','CleanOldThreads')) {
      // creation du cron - param = duree de conservation
      CronTask::Unregister('massocsimport');
   }
   if ($cron->getFromDBbyName('PluginOcsinventoryngThread','CleanOldThreads')) {
      // creation du cron - param = duree de conservation
      CronTask::Unregister('ocsinventoryng');
   }
   
	return true;
}

// Define dropdown relations
function plugin_ocsinventoryngs_getDatabaseRelations() {

	$plugin = new Plugin();

	if ($plugin->isActivated("ocsinventoryng"))
		return array (
			"glpi_plugin_ocsinventoryng_ocsservers" => array (
				"glpi_plugin_ocsinventoryng_ocslinks" => "plugin_ocsinventoryng_ocsservers_id",
				"glpi_plugin_ocsinventoryng_ocsadmininfoslinks" => "plugin_ocsinventoryng_ocsservers_id",
			),
			"glpi_entities" => array (
				"glpi_plugin_ocsinventoryng_ocslinks" => "entities_id"
			),
			"glpi_computers" => array (
				"glpi_plugin_ocsinventoryng_ocslinks" => "computers_id",
				"glpi_plugin_ocsinventoryng_registrykeys" => "computers_id"
			),
			"glpi_profiles" => array (
				"glpi_plugin_ocsinventoryng_profiles" => "profiles_id"
			)
		);
	else
		return array ();
}

// Define headings added by the plugin
function plugin_get_headings_ocsinventoryng($item,$withtemplate) {
	global $LANG;
	
	switch (get_class($item)) {
      case 'Profile' :
         if ($item->getField('id')) {
            return array(
				1 => $LANG['plugin_ocsinventoryng']['title'][1],
				);
         }
         break;
      case 'Computer' :
         $array = array ();
         if ($item->getField('id') > 0
                && !$withtemplate) {
            $array[1] = $LANG['plugin_ocsinventoryng']['title'][1];
            $array[2] = $LANG['plugin_ocsinventoryng']['config'][41];
         }
         return $array;
         break;
   }
   
	return false;
	
}

// Define headings actions added by the plugin	 
function plugin_headings_actions_ocsinventoryng($item) {
		
	if (get_class($item)=='Profile') {
		return array(
					1 => "plugin_headings_ocsinventoryng",
					);
	} else if (get_class($item)=='Computer') {
		$array = array ();
      $array[1] = "plugin_headings_ocsinventoryng";
      $array[2] = "plugin_headings_ocsinventoryng_registry";
      return $array;
      break;
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
      case 'Computer' :
         PluginOcsinventoryngOcsLink::showForItem($item);
         PluginOcsinventoryngOcsServer::editLock(getItemTypeFormURL('Computer'), $item->getField('id'));
         break;
   }

}

function plugin_headings_ocsinventoryng_registry($item,$withtemplate=0) {
   
   switch (get_class($item)) {
      case 'Computer' :
         PluginOcsinventoryngRegistryKey::showForComputer($item->getField('id'));
         break;
   }

}

function plugin_ocsinventoryng_MassiveActions($type) {
   global $LANG;
   
   switch ($type) {
      case 'PluginOcsinventoryngNotimported' :
         $actions = array ();
         $actions["plugin_ocsinventoryng_replayrules"] = $LANG["plugin_ocsinventoryng"]["notimported"][3];
         $actions["plugin_ocsinventoryng_import"]      = $LANG["plugin_ocsinventoryng"]["display"][1];
         if (isset ($_POST['target']) 
            && $_POST['target'] == getItemTypeFormURL('PluginOcsinventoryngNotimported')) {
            $actions["plugin_ocsinventoryng_link"]        = $LANG["plugin_ocsinventoryng"]["display"][6];
         }
         $plugin = new Plugin;
         if ($plugin->isActivated("uninstall")) {
            $actions["plugin_ocsinventoryng_delete"]   = $LANG["plugin_ocsinventoryng"]["display"][5];
         }
         
         return $actions;
         break;
      case 'Computer' :
         if (plugin_ocsinventoryng_haveRight("ocsng","w") || plugin_ocsinventoryng_haveRight("sync_ocsng","w")) {
            return array (
                  // Specific one
                  "plugin_ocsinventoryng_force_ocsng_update" => $LANG['plugin_ocsinventoryng']['profile'][1]." - ".
                  $LANG['plugin_ocsinventoryng'][24],
                  "plugin_ocsinventoryng_unlock_ocsng_field" => $LANG['buttons'][38]." ".
                  $LANG['plugin_ocsinventoryng']['profile'][1]." - ".$LANG['plugin_ocsinventoryng'][16],
                  "plugin_ocsinventoryng_unlock_ocsng_monitor" => $LANG['buttons'][38]." ".
                  $LANG['plugin_ocsinventoryng']['profile'][1]." - ".$LANG['plugin_ocsinventoryng'][30],
                  "plugin_ocsinventoryng_unlock_ocsng_peripheral" => $LANG['buttons'][38]." ".
                  $LANG['plugin_ocsinventoryng']['profile'][1]." - ".$LANG['plugin_ocsinventoryng'][32],
                  "plugin_ocsinventoryng_unlock_ocsng_printer" => $LANG['buttons'][38]." ".
                  $LANG['plugin_ocsinventoryng']['profile'][1]." - ".$LANG['plugin_ocsinventoryng'][34],
                  "plugin_ocsinventoryng_unlock_ocsng_software" => $LANG['buttons'][38]." ".
                  $LANG['plugin_ocsinventoryng']['profile'][1]." - ".$LANG['plugin_ocsinventoryng'][52],
                  "plugin_ocsinventoryng_unlock_ocsng_ip" => $LANG['buttons'][38]." ".
                  $LANG['plugin_ocsinventoryng']['profile'][1]." - ".$LANG['plugin_ocsinventoryng'][50],
                  "plugin_ocsinventoryng_unlock_ocsng_disk" => $LANG['buttons'][38]." ".
                  $LANG['plugin_ocsinventoryng']['profile'][1]." - ".$LANG['plugin_ocsinventoryng'][55]


               );
         }
         break;
   }
   return array ();
}

function plugin_ocsinventoryng_MassiveActionsDisplay($options=array()) {
   global $LANG;

   switch ($options['itemtype']) {
      case 'PluginOcsinventoryngNotimported' :
         switch ($options['action']) {
            case "plugin_ocsinventoryng_import" :
               Dropdown::show('Entity', array('name' => 'entity'));
               break;
            
            case "plugin_ocsinventoryng_link" :
               Dropdown::show('Computer', array('name' => 'computers_id'));
               break;
               
            case "plugin_ocsinventoryng_replayrules" :
            case "plugin_ocsinventoryng_delete" :
               break;
         }
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' " .
              "value='".$LANG['buttons'][2]."'>";
         break;

      case 'Computer' :
         switch ($options['action']) {
            case "plugin_ocsinventoryng_force_ocsng_update" :
               echo "<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][2]."'>\n";
               break;
            case "plugin_ocsinventoryng_unlock_ocsng_field" :
               $fields['all'] = $LANG['common'][66];
               $fields       += Computer::getLockableFields();
               Dropdown::showFromArray("field", $fields);
               echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                            $LANG['buttons'][2]."'>";
               break;

            case "plugin_ocsinventoryng_unlock_ocsng_monitor" :
            case "plugin_ocsinventoryng_unlock_ocsng_peripheral" :
            case "plugin_ocsinventoryng_unlock_ocsng_software" :
            case "plugin_ocsinventoryng_unlock_ocsng_printer" :
            case "plugin_ocsinventoryng_unlock_ocsng_disk" :
            case "plugin_ocsinventoryng_unlock_ocsng_ip" :
               echo "<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][2]."'>";
               break;
         }
   }
   return "";
}


function plugin_ocsinventoryng_MassiveActionsProcess($data) {
   global $CFG_GLPI, $LANG,$DB,$REDIRECT;

   $notimport = new PluginOcsinventoryngNotimported();
   switch ($data["action"]) {
      case "plugin_ocsinventoryng_import" :
         foreach ($data["item"] as $key => $val) {
            if ($val == 1) {
               PluginOcsinventoryngNotimported::computerImport(array('id'    => $key,
                                                                    'force' => true,
                                                                    'entity'=>$data['entity']));
            }
         }
         break;

      case "plugin_ocsinventoryng_replayrules" :
         foreach ($data["item"] as $key => $val) {
            if ($val == 1) {
               PluginOcsinventoryngNotimported::computerImport(array('id'=>$key));
            }
         }
         break;

      case "plugin_ocsinventoryng_delete" :
         $plugin = new Plugin();
         if ($plugin->isActivated("uninstall")) {
            foreach ($data["item"] as $key => $val) {
               if ($val == 1) {
                  $notimport->deleteNotImportedComputer($key);
               }
            }
         }
         break;
      case "plugin_ocsinventoryng_unlock_ocsng_field" :
         $fields = Computer::getLockableFields();
         if ($_POST['field'] == 'all' || isset($fields[$_POST['field']])) {
            foreach ($_POST["item"] as $key => $val) {
               if ($val == 1) {
                  if ($_POST['field'] == 'all') {
                     PluginOcsinventoryngOcsServer::replaceOcsArray($key ,array(), "computer_update");
                  } else {
                     PluginOcsinventoryngOcsServer::deleteInOcsArray($key, $_POST['field'], "computer_update", true);
                  }
               }
            }
         }
         break;

      case "plugin_ocsinventoryng_unlock_ocsng_monitor" :
      case "plugin_ocsinventoryng_unlock_ocsng_printer" :
      case "plugin_ocsinventoryng_unlock_ocsng_peripheral" :
      case "plugin_ocsinventoryng_unlock_ocsng_software" :
      case "plugin_ocsinventoryng_unlock_ocsng_ip" :
      case "plugin_ocsinventoryng_unlock_ocsng_disk" :
         foreach ($_POST["item"] as $key => $val) {
            if ($val == 1) {
               switch ($_POST["action"]) {
                  case "plugin_ocsinventoryng_unlock_ocsng_monitor" :
                     PluginOcsinventoryngOcsServer::unlockItems($key, "import_monitor");
                     break;

                  case "plugin_ocsinventoryng_unlock_ocsng_printer" :
                     PluginOcsinventoryngOcsServer::unlockItems($key, "import_printer");
                     break;

                  case "plugin_ocsinventoryng_unlock_ocsng_peripheral" :
                     PluginOcsinventoryngOcsServer::unlockItems($key, "import_peripheral");
                     break;

                  case "plugin_ocsinventoryng_unlock_ocsng_software" :
                     PluginOcsinventoryngOcsServer::unlockItems($key, "import_software");
                     break;

                  case "plugin_ocsinventoryng_unlock_ocsng_ip" :
                     PluginOcsinventoryngOcsServer::unlockItems($key, "import_ip");
                     break;

                  case "plugin_ocsinventoryng_unlock_ocsng_disk" :
                     PluginOcsinventoryngOcsServer::unlockItems($key, "import_disk");
                     break;
               }
            }
         }
         break;

      case "plugin_ocsinventoryng_force_ocsng_update" :
      
         // First time
         if (!isset($_GET['multiple_actions'])) {
            $_SESSION['glpi_massiveaction']['POST']      = $_POST;
            $_SESSION['glpi_massiveaction']['REDIRECT']  = $REDIRECT;
            $_SESSION['glpi_massiveaction']['items']     = array();
            foreach ($_POST["item"] as $key => $val) {
               if ($val == 1) {
                  $_SESSION['glpi_massiveaction']['items'][$key] = $key;
               }
            }
            $_SESSION['glpi_massiveaction']['item_count']
               = count($_SESSION['glpi_massiveaction']['items']);
            glpi_header($_SERVER['PHP_SELF'].'?multiple_actions=1');

         } else {
            if (count($_SESSION['glpi_massiveaction']['items']) >0) {
               $key = array_pop($_SESSION['glpi_massiveaction']['items']);
               //Try to get the OCS server whose machine belongs
               $query = "SELECT `plugin_ocsinventoryng_ocsservers_id`, `id`
                         FROM `glpi_plugin_ocsinventoryng_ocslinks`
                         WHERE `computers_id` = '$key'";
               $result = $DB->query($query);
               if ($DB->numrows($result) == 1) {
                  $data = $DB->fetch_assoc($result);
                  if ($data['plugin_ocsinventoryng_ocsservers_id'] != -1) {
                     //Force update of the machine
                     PluginOcsinventoryngOcsServer::updateComputer($data['id'], $data['plugin_ocsinventoryng_ocsservers_id'], 1, 1);
                  }
               }
               glpi_header($_SERVER['PHP_SELF'].'?multiple_actions=1');
            } else {
               $REDIRECT = $_SESSION['glpi_massiveaction']['REDIRECT'];
               unset($_SESSION['glpi_massiveaction']);
               glpi_header($REDIRECT);
            }
         }
         break;
   }
}

function plugin_ocsinventoryng_getAddSearchOptions($itemtype) {
	global $LANG;

    $sopt=array();

   if ($itemtype=='Computer') {
      if (plugin_ocsinventoryng_haveRight("ocsng","r")) {
         $sopt[103]['table']         ='glpi_plugin_ocsinventoryng_ocslinks';
         $sopt[103]['field']         ='last_update';
         $sopt[103]['name']          = $LANG['plugin_ocsinventoryng'][14];
         $sopt[103]['datatype']      = 'datetime';
         $sopt[103]['massiveaction'] = false;
         $sopt[103]['joinparams']    = array('jointype' => 'child');
      
         $sopt[101]['table']      = 'glpi_plugin_ocsinventoryng_ocslinks';
         $sopt[101]['field']      = 'use_auto_update';
         $sopt[101]['linkfield']  = '_auto_update_ocs'; // update through compter update process
         $sopt[101]['name']       = $LANG['plugin_ocsinventoryng'][6]." ".$LANG['ocsconfig'][0];
         $sopt[101]['datatype']   = 'bool';
         $sopt[101]['joinparams'] = array('jointype' => 'child');

         $sopt[104]['table']         = 'glpi_plugin_ocsinventoryng_ocslinks';
         $sopt[104]['field']         = 'ocs_agent_version';
         $sopt[104]['name']          = $LANG['plugin_ocsinventoryng'][49];
         $sopt[104]['massiveaction'] = false;
         $sopt[104]['joinparams']    = array('jointype' => 'child');

         $sopt[105]['table']         = 'glpi_plugin_ocsinventoryng_ocslinks';
         $sopt[105]['field']         = 'tag';
         $sopt[105]['name']          = $LANG['plugin_ocsinventoryng']['config'][39];
         $sopt[105]['datatype']      = 'string';
         $sopt[105]['massiveaction'] = false;
         $sopt[105]['joinparams']    = array('jointype' => 'child');

         $sopt[110]['table']         = 'glpi_plugin_ocsinventoryng_registrykeys';
         $sopt[110]['field']         = 'value';
         $sopt[110]['name']          = $LANG['plugin_ocsinventoryng']['registry'][0]." : ".$LANG['plugin_ocsinventoryng']['registry'][3];
         $sopt[110]['forcegroupby']  = true;
         $sopt[110]['massiveaction'] = false;
         $sopt[110]['joinparams']    = array('jointype' => 'child');

         $sopt[111]['table']         = 'glpi_plugin_ocsinventoryng_registrykeys';
         $sopt[111]['field']         = 'ocs_name';
         $sopt[111]['name']          = $LANG['plugin_ocsinventoryng']['registry'][0]." : ".$LANG['plugin_ocsinventoryng']['registry'][6];
         $sopt[111]['forcegroupby']  = true;
         $sopt[111]['massiveaction'] = false;
         $sopt[111]['joinparams']    = array('jointype' => 'child');
      }
	}
	return $sopt;
}


function plugin_ocsinventoryng_addSelect($type, $id, $num) {

   $searchopt = &Search::getOptions($type);

   $table = $searchopt[$id]["table"];
   $field = $searchopt[$id]["field"];

   $out = "`$table`.`$field` AS ITEM_$num,
               `$table`.`ocsid` AS ocsid,
               `$table`.`plugin_ocsinventoryng_ocsservers_id` AS plugin_ocsinventoryng_ocsservers_id, ";

   if ($num == 0) {
      switch ($type) {
         case 'PluginOcsinventoryngNotimported' :
            return $out;

         case 'PluginOcsinventoryngDetail' :
            $out .= "`$table`.`plugin_ocsinventoryng_threads_id`,
                     `$table`.`threadid`, ";
            return $out;
      }
      return "";
   }
}

function plugin_ocsinventoryng_addWhere($link,$nott,$type,$ID,$val) {

   $searchopt = &Search::getOptions($type);
   $table = $searchopt[$ID]["table"];
   $field = $searchopt[$ID]["field"];

   $SEARCH = makeTextSearch($val,$nott);
    switch ($table.".".$field) {
         case "glpi_plugin_ocsinventoryng_details.action" :
               return $link." `$table`.`$field` = '$val' ";
         default:
            return "";
    }
   return "";
}

function plugin_ocsinventoryng_giveItem($type, $id, $data, $num) {
   global $CFG_GLPI, $DB, $LANG;

   $searchopt = &Search::getOptions($type);

   $table = $searchopt[$id]["table"];
   $field = $searchopt[$id]["field"];

   switch ("$table.$field") {
      case "glpi_plugin_ocsinventoryng_details.action" :
         $detail = new PluginOcsinventoryngDetail();
         return $detail->giveActionNameByActionID($data["ITEM_$num"]);
      case "glpi_plugin_ocsinventoryng_details.computers_id":
         $comp = new Computer();
         $comp->getFromDB($data["ITEM_$num"]);
         return "<a href='".getItemTypeFormURL('Computer')."?id=".$data["ITEM_$num"]."'>".$comp->getName()."</a>";
      case "glpi_plugin_ocsinventoryng_details.plugin_ocsinventoryng_ocsservers_id":
         $ocs = new PluginOcsinventoryngOcsServer();
         $ocs->getFromDB($data["ITEM_$num"]);
         return "<a href='".getItemTypeFormURL('PluginOcsinventoryngOcsServer')."?id=".$data["ITEM_$num"]."'>".$ocs->getName()."</a>";
      case "glpi_plugin_ocsinventoryng_details.rules_id":
         $detail = new PluginOcsinventoryngDetail();
         $detail->getFromDB($data['id']);
         return PluginOcsinventoryngNotimported::getRuleMatchedMessage($detail->fields['rules_id']);
      case "glpi_plugin_ocsinventoryng_notimported.reason" :
         return PluginOcsinventoryngNotimported::getReason($data["ITEM_$num"]);
      default:
        return "";
   }
   return '';
}

function plugin_ocsinventoryng_searchOptionsValues($params = array()) {
   switch($params['searchoption']['field']) {
      case "action":
         PluginOcsinventoryngDetail::showActions($params['name'],$params['value']);
         return true;
   }
   return false;
}

?>