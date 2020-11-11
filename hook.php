<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 ocsinventoryng plugin for GLPI
 Copyright (C) 2015-2016 by the ocsinventoryng Development Team.

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

/**
 * @return bool
 */
function plugin_ocsinventoryng_install() {
   global $DB;

   include_once(GLPI_ROOT . "/plugins/ocsinventoryng/inc/profile.class.php");

   $migration = new Migration(150);
   $dbu       = new DbUtils();

   if (!$DB->tableExists("glpi_plugin_ocsinventoryng_bitlockerstatuses")
       && !$DB->tableExists("glpi_plugin_ocsinventoryng_ocsservers")
       && !$DB->tableExists("ocs_glpi_ocsservers")) {
      //INSTALL
      $DB->runFile(GLPI_ROOT . "/plugins/ocsinventoryng/install/mysql/1.7.1-empty.sql");

      $migration->createRule(['sub_type'     => 'RuleImportComputer',
                              'entities_id'  => 0,
                              'is_recursive' => 0,
                              'is_active'    => 1,
                              'match'        => 'AND',
                              'name'         => 'RootComputerOcs'],
                             [['criteria'  => 'serial',
                               'condition' => Rule::PATTERN_FIND,
                               'pattern'   => '1']],
                             [['field'       => '_fusion',
                               'action_type' => 'assign',
                               'value'       => 0]]);

      $migration->createRule(['sub_type'     => 'RuleImportEntity',
                              'entities_id'  => 0,
                              'is_recursive' => 1,
                              'is_active'    => 1,
                              'match'        => 'AND',
                              'name'         => 'RootEntityOcs'],
                             [['criteria'  => 'TAG',
                               'condition' => Rule::PATTERN_IS,
                               'pattern'   => '*'],
                              ['criteria'  => 'OCS_SERVER',
                               'condition' => Rule::PATTERN_IS,
                               'pattern'   => 1]],
                             [['field'       => 'entities_id',
                               'action_type' => 'assign',
                               'value'       => 0]]);

   } else {

      //UPDATE
      if (!$DB->tableExists("glpi_plugin_ocsinventoryng_ocsservers")
          && !$DB->tableExists("ocs_glpi_ocsservers")) {

         CronTask::Register('PluginOcsinventoryngOcsServer', 'ocsng', MINUTE_TIMESTAMP * 5);

         $migration->createRule(['sub_type'     => 'RuleImportComputer',
                                 'entities_id'  => 0,
                                 'is_recursive' => 0,
                                 'is_active'    => 1,
                                 'match'        => 'AND',
                                 'name'         => 'RootComputerOcs'],
                                [['criteria'  => 'serial',
                                  'condition' => Rule::PATTERN_FIND,
                                  'pattern'   => '1']],
                                [['field'       => '_fusion',
                                  'action_type' => 'assign',
                                  'value'       => 0]]);

         $migration->createRule(['sub_type'     => 'RuleImportEntity',
                                 'entities_id'  => 0,
                                 'is_recursive' => 1,
                                 'is_active'    => 1,
                                 'match'        => 'AND',
                                 'name'         => 'RootEntityOcs'],
                                [['criteria'  => 'TAG',
                                  'condition' => Rule::PATTERN_IS,
                                  'pattern'   => '*'],
                                 ['criteria'  => 'OCS_SERVER',
                                  'condition' => Rule::PATTERN_IS,
                                  'pattern'   => 1]],
                                [['field'       => 'entities_id',
                                  'action_type' => 'assign',
                                  'value'       => 0]]);

      } else if (!$DB->tableExists("glpi_plugin_ocsinventoryng_ocsservers")
                 && $DB->tableExists("ocs_glpi_ocsservers")) {

         $DB->runFile(GLPI_ROOT . "/plugins/ocsinventoryng/install/mysql/1.0.0-update.sql");

         // recuperation des droits du core
         // creation de la table glpi_plugin_ocsinventoryng_profiles vide
         if ($DB->tableExists("ocs_glpi_profiles")
             && ($DB->tableExists('ocs_glpi_ocsservers')
                 && $dbu->countElementsInTable('ocs_glpi_ocsservers') > 0)) {

            $query = "INSERT INTO `glpi_plugin_ocsinventoryng_profiles`
                          (`profiles_id`, `ocsng`, `sync_ocsng`, `view_ocsng`, `clean_ocsng`,
                           `rule_ocs`)
                           SELECT `id`, `ocsng`, `sync_ocsng`, `view_ocsng`, `clean_ocsng`,
                                  `rule_ocs`
                           FROM `ocs_glpi_profiles`";
            $DB->queryOrDie($query, "1.0.0 insert profiles for OCS in plugin");
         }

         // recuperation des paramètres du core
         if ($DB->tableExists("ocs_glpi_crontasks")) {

            $query = "INSERT INTO `glpi_crontasks` (`itemtype`, `name`, `frequency`, `param`, `state`,
            `mode`, `allowmode`, `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`)
                          SELECT `itemtype`, `name`, `frequency`, `param`, `state`,`mode`, `allowmode`,
                          `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`
                          FROM `ocs_glpi_crontasks`
                          WHERE `itemtype` = 'OcsServer'";

            $DB->queryOrDie($query, "1.0.0 insert crontasks for plugin ocsinventoryng");

            $query = "UPDATE `glpi_crontasks`
                   SET `itemtype` = 'PluginOcsinventoryngOcsServer'
                   WHERE `itemtype` = 'OcsServer'";
            $DB->queryOrDie($query, "1.0.0 update ocsinventoryng crontask");
         }

         if ($DB->tableExists("ocs_glpi_displaypreferences")) {
            $query = "INSERT INTO `glpi_displaypreferences`
                          SELECT *
                          FROM `ocs_glpi_displaypreferences`
                          WHERE `itemtype` = 'OcsServer'";

            $DB->queryOrDie($query, "1.0.0 insert displaypreferences for plugin ocsinventoryng");

            $query = "UPDATE `glpi_displaypreferences`
                   SET `itemtype` = 'PluginOcsinventoryngOcsServer'
                   WHERE `itemtype` = 'OcsServer'";
            $DB->queryOrDie($query, "1.0.0 update ocsinventoryng displaypreferences");
         }
         plugin_ocsinventoryng_migrateComputerLocks($migration);

      }

      //Update 1.0.3
      if ($DB->tableExists("glpi_plugin_ocsinventoryng_networkports")
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_networkports', 'speed')) {

         $query = "ALTER TABLE `glpi_plugin_ocsinventoryng_networkports` 
               ADD `speed` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT '10mb/s';";
         $DB->queryOrDie($query, "1.0.3 update table glpi_plugin_ocsinventoryng_networkports");
      }

      // Update 1.0.4
      if ($DB->tableExists("glpi_plugin_ocsinventoryng_ocsservers")
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocsservers', 'conn_type')) {

         $query = "ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` 
               ADD `conn_type` TINYINT(1) NOT NULL DEFAULT '0';";
         $DB->queryOrDie($query, "1.0.4 update table glpi_plugin_ocsinventoryng_ocsservers");
      }

      //Update 1.1.0
      if (!$DB->tableExists("glpi_plugin_ocsinventoryng_ocsservers_profiles")) {
         $query = "CREATE TABLE `glpi_plugin_ocsinventoryng_ocsservers_profiles` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `plugin_ocsinventoryng_ocsservers_id` INT(11) NOT NULL DEFAULT '0',
                  `profiles_id` INT(11) NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `plugin_ocsinventoryng_ocsservers_id` (`plugin_ocsinventoryng_ocsservers_id`),
                KEY `profiles_id` (`profiles_id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->queryOrDie($query,
                         'Creating glpi_plugin_ocsinventoryng_ocsservers_profiles' . "<br>" . $DB->error());

      }

      if ($DB->tableExists("glpi_plugin_ocsinventoryng_ocslinks")
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocslinks', 'last_ocs_conn')) {

         $query = "ALTER TABLE `glpi_plugin_ocsinventoryng_ocslinks` 
               ADD `last_ocs_conn` DATETIME DEFAULT NULL;";
         $DB->queryOrDie($query, "1.1.0 update table glpi_plugin_ocsinventoryng_ocslinks");
      }

      if ($DB->tableExists("glpi_plugin_ocsinventoryng_ocslinks")
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocslinks', 'ip_src')) {

         $query = "ALTER TABLE `glpi_plugin_ocsinventoryng_ocslinks` 
               ADD `ip_src` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL;";
         $DB->queryOrDie($query, "1.1.0 update table glpi_plugin_ocsinventoryng_ocslinks");
      }

      if ($DB->tableExists("glpi_plugin_ocsinventoryng_ocsservers")
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocsservers', 'import_device_bios')) {

         $query = "ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` 
                     ADD `import_device_bios` TINYINT(1) NOT NULL DEFAULT '1';";
         $DB->queryOrDie($query, "1.1.0 update table glpi_plugin_ocsinventoryng_ocsservers");
      }

      if (!$DB->tableExists("glpi_plugin_ocsinventoryng_devicebiosdatas")) {

         $query = "CREATE TABLE `glpi_plugin_ocsinventoryng_devicebiosdatas` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `designation` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                    `comment` TEXT COLLATE utf8_unicode_ci,
                    `date` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                    `assettag` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                    `manufacturers_id` INT(11) NOT NULL DEFAULT '0',
                    `entities_id` INT(11) NOT NULL DEFAULT '0',
                    `is_recursive` TINYINT(1) NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`),
                    KEY `manufacturers_id` (`manufacturers_id`),
                    KEY `entities_id` (`entities_id`),
                    KEY `is_recursive` (`is_recursive`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $DB->queryOrDie($query, "1.1.0 add table glpi_plugin_ocsinventoryng_devicebiosdatas");
      }

      if (!$DB->tableExists("glpi_plugin_ocsinventoryng_items_devicebiosdatas")) {

         $query = "CREATE TABLE `glpi_plugin_ocsinventoryng_items_devicebiosdatas` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `items_id` INT(11) NOT NULL DEFAULT '0',
                    `itemtype` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                    `plugin_ocsinventoryng_devicebiosdatas_id` INT(11) NOT NULL DEFAULT '0',
                    `is_deleted` TINYINT(1) NOT NULL DEFAULT '0',
                    `is_dynamic` TINYINT(1) NOT NULL DEFAULT '0',
                    `entities_id` INT(11) NOT NULL DEFAULT '0',
                    `is_recursive` TINYINT(1) NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`),
                    KEY `computers_id` (`items_id`),
                    KEY `plugin_ocsinventoryng_devicebiosdatas_id` (`plugin_ocsinventoryng_devicebiosdatas_id`),
                    KEY `is_deleted` (`is_deleted`),
                    KEY `is_dynamic` (`is_dynamic`),
                    KEY `entities_id` (`entities_id`),
                    KEY `is_recursive` (`is_recursive`),
                    KEY `item` (`itemtype`,`items_id`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $DB->queryOrDie($query, "1.1.0 add table glpi_plugin_ocsinventoryng_items_devicebiosdatas");
      }

      if ($DB->tableExists("glpi_plugin_ocsinventoryng_ocsservers")
          && $DB->tableExists("glpi_plugin_ocsinventoryng_profiles")
          && ($dbu->countElementsInTable("glpi_plugin_ocsinventoryng_ocsservers", ["is_active" => 1]) == 1)) {

         foreach ($DB->request("glpi_plugin_ocsinventoryng_ocsservers") as $server) {
            foreach ($DB->request("glpi_plugin_ocsinventoryng_profiles",
                                  "`ocsng` IS NOT NULL") as $rights) {

               $query = "INSERT INTO `glpi_plugin_ocsinventoryng_ocsservers_profiles`
                       SET `profiles_id` = '" . $rights['profiles_id'] . "',
                           `plugin_ocsinventoryng_ocsservers_id` = '" . $server['id'] . "'";
               $DB->queryOrDie($query, "insert glpi_plugin_ocsinventoryng_ocsservers_profiles");
            }
         }
      }

      $migration->dropTable('glpi_plugin_ocsinventoryng_profiles');

      //Update 1.2.2
      if ($DB->tableExists("glpi_plugin_ocsinventoryng_ocsservers")
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocsservers', 'import_device_motherboard')) {

         $query = "ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` 
               ADD `import_device_motherboard` TINYINT(1) NOT NULL DEFAULT '0';";
         $DB->queryOrDie($query, "1.2.2 update table glpi_plugin_ocsinventoryng_ocsservers");
      }

      //Update 1.2.3
      if (!$DB->tableExists("glpi_plugin_ocsinventoryng_ipdiscoverocslinks")) {

         $query = "CREATE TABLE `glpi_plugin_ocsinventoryng_ipdiscoverocslinks` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `items_id` INT(11) NOT NULL,
                `itemtype` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                `macaddress` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL UNIQUE,
                `last_update` DATETIME COLLATE utf8_unicode_ci DEFAULT NULL,
                `subnet` VARCHAR(40) COLLATE utf8_unicode_ci DEFAULT NULL,
                `plugin_ocsinventoryng_ocsservers_id` INT(11) NOT NULL DEFAULT '0',
                 PRIMARY KEY (`id`)
                 ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $DB->queryOrDie($query, "1.2.3 add table glpi_plugin_ocsinventoryng_ipdiscoverocslinks");
      }

      // Si massocsimport import est installe, on verifie qu'il soit bien dans la dernière version
      if ($DB->tableExists("glpi_plugin_mass_ocs_import")) { //1.1 ou 1.2
         if (!$DB->fieldExists('glpi_plugin_mass_ocs_import_config', 'warn_if_not_imported')) { //1.1
            plugin_ocsinventoryng_upgrademassocsimport11to12();
         }
      }
      if ($DB->tableExists("glpi_plugin_mass_ocs_import")) { //1.2 because if before
         plugin_ocsinventoryng_upgrademassocsimport121to13();
      }
      if ($DB->tableExists("glpi_plugin_massocsimport")) { //1.3 ou 1.4
         if ($DB->fieldExists('glpi_plugin_massocsimport', 'ID')) { //1.3
            plugin_ocsinventoryng_upgrademassocsimport13to14();
         }
      }

      //Tables from massocsimport
      if (!$DB->tableExists('glpi_plugin_ocsinventoryng_threads')
          && !$DB->tableExists('glpi_plugin_massocsimport_threads')) { //not installed

         plugin_ocsinventoryng_update110();

      } else if (!$DB->tableExists('glpi_plugin_ocsinventoryng_threads')
                 && $DB->tableExists('glpi_plugin_massocsimport_threads')) {

         plugin_ocsinventoryng_updatemassocsimport($migration);
      }

      /******************* Migration 1.2.2 *******************/
      if ($DB->tableExists('glpi_plugin_ocsinventoryng_ocsservers')
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocsservers', 'use_cleancron')) {
         $query = "ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` 
               ADD `use_cleancron` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `importsnmp_name` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `importsnmp_serial` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `importsnmp_comment` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `importsnmp_contact` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `importsnmp_location` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `importsnmp_domain` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `importsnmp_manufacturer` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `importsnmp_createport` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `importsnmp_last_pages_counter` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `importsnmp_firmware` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `importsnmp_power` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `importsnmp_fan` TINYINT(1) NOT NULL DEFAULT '0';";
         $DB->queryOrDie($query, "1.2.2 update table glpi_plugin_ocsinventoryng_ocsservers add use_cleancron");
      }

      if (!$DB->tableExists("glpi_plugin_ocsinventoryng_snmpocslinks")) {

         $query = "CREATE TABLE `glpi_plugin_ocsinventoryng_snmpocslinks` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `items_id` INT(11) NOT NULL DEFAULT '0',
                  `ocs_id` INT(11) NOT NULL DEFAULT '0',
                  `itemtype` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `last_update` DATETIME COLLATE utf8_unicode_ci DEFAULT NULL,
                  `plugin_ocsinventoryng_ocsservers_id` INT(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $DB->queryOrDie($query, "add table for snmp");
      }/*1.2.2*/

      /******************* Migration 1.2.3 *******************/
      if ($DB->tableExists('glpi_plugin_ocsinventoryng_ocsservers')
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocsservers', 'dohistory')) {
         $query = "ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` 
               ADD `dohistory` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `history_hardware` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `history_bios` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `history_drives` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `history_network` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `history_devices` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `history_monitor` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `history_printer` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `history_peripheral` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `history_software` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `history_vm` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `history_admininfos` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `import_device_controller` TINYINT(1) NOT NULL DEFAULT '0',
               ADD `import_device_slot` TINYINT(1) NOT NULL DEFAULT '0';";
         $DB->queryOrDie($query, "1.2.3 update table glpi_plugin_ocsinventoryng_ocsservers add history");
      }/*1.2.3*/

      /******************* Migration 1.3.0 *******************/
      if ($DB->tableExists('glpi_plugin_ocsinventoryng_ocsservers')
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocsservers', 'import_antivirus')
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocsservers', 'linksnmp_name')) {

         $DB->runFile(GLPI_ROOT . "/plugins/ocsinventoryng/install/mysql/1.3.0-update.sql");
      }/*1.3.0*/

      /******************* Migration 1.3.2 *******************/
      if ($DB->tableExists('glpi_plugin_ocsinventoryng_ocslinks')
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocslinks', 'uptime')
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocsservers', 'import_officepack')) {

         $DB->runFile(GLPI_ROOT . "/plugins/ocsinventoryng/install/mysql/1.3.2-update.sql");
      }/*1.3.2*/

      /******************* Migration 1.3.3 *******************/
      if ($DB->tableExists('glpi_plugin_ocsinventoryng_ocsservers')
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocsservers', 'use_checkruleimportentity')
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocsservers', 'import_teamviewer')) {

         $DB->runFile(GLPI_ROOT . "/plugins/ocsinventoryng/install/mysql/1.3.3-update.sql");
      }/*1.3.3*/

      /******************* Migration 1.3.4 *******************/
      if ($DB->tableExists('glpi_plugin_ocsinventoryng_ocsservers')
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocsservers', 'import_proxysetting')
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocsservers', 'import_winusers')) {

         $DB->runFile(GLPI_ROOT . "/plugins/ocsinventoryng/install/mysql/1.3.4-update.sql");
      }/*1.3.4 */

      /******************* Migration 1.4.0 *******************/
      include_once(GLPI_ROOT . "/inc/devicefirmware.class.php");
      include_once(GLPI_ROOT . "/inc/item_devicefirmware.class.php");
      foreach ($dbu->getAllDataFromTable('glpi_plugin_ocsinventoryng_devicebiosdatas') as $ocsbios) {

         $DeviceBios               = new DeviceFirmware();
         $bios["designation"]      = addslashes($ocsbios["designation"]);
         $bios["comment"]          = addslashes($ocsbios["comment"]);
         $bios["entities_id"]      = $ocsbios["entities_id"];
         $bios["is_recursive"]     = $ocsbios["is_recursive"];
         $bios["manufacturers_id"] = $ocsbios["manufacturers_id"];
         $bios["version"]          = addslashes($ocsbios["assettag"]);
         $date                     = str_replace("/", "-", $ocsbios["date"]);
         $date                     = date("Y-m-d", strtotime($date));
         $bios["date"]             = $date;

         $bios_id = $DeviceBios->import($bios);

         $condition = ["plugin_ocsinventoryng_devicebiosdatas_id" => $ocsbios["id"]];
         foreach ($dbu->getAllDataFromTable('glpi_plugin_ocsinventoryng_items_devicebiosdatas', $condition) as $item_bios) {
            $CompDevice = new Item_DeviceFirmware();
            $CompDevice->add(['items_id'           => $item_bios['items_id'],
                              'itemtype'           => $item_bios['itemtype'],
                              'devicefirmwares_id' => $bios_id,
                              'is_dynamic'         => 1,
                              'entities_id'        => $item_bios['entities_id']], [], false);

         }
      }

      $migration->dropTable("glpi_plugin_ocsinventoryng_devicebiosdatas");
      $migration->dropTable("glpi_plugin_ocsinventoryng_items_devicebiosdatas");
      /*1.4.0*/

      /******************* Migration 1.4.3 *******************/
      if ($DB->tableExists('glpi_plugin_ocsinventoryng_ocsservers')
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocsservers', 'import_osinstall')
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocsservers', 'import_networkshare')) {

         $DB->runFile(GLPI_ROOT . "/plugins/ocsinventoryng/install/mysql/1.4.3-update.sql");
      }/*1.4.3*/

      /******************* Migration 1.4.4 *******************/
      if ($DB->tableExists('glpi_plugin_ocsinventoryng_ocsservers')
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocsservers', 'import_runningprocess')
          && !$DB->fieldExists('glpi_plugin_ocsinventoryng_ocsservers', 'import_service')) {

         $DB->runFile(GLPI_ROOT . "/plugins/ocsinventoryng/install/mysql/1.4.4-update.sql");
      }/*1.4.4*/

      /******************* Migration 1.5.0 *******************/

      if (!$DB->fieldExists('glpi_plugin_ocsinventoryng_ocsservers', 'history_plugins')) {
         $query = "ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` 
               ADD `history_plugins` TINYINT(1) NOT NULL DEFAULT '0';";
         $DB->queryOrDie($query, "1.5.0 add history_plugins in glpi_plugin_ocsinventoryng_ocsservers");
      }/*1.5.0*/

      /******************* Migration 1.5.1 *******************/
      if (!$DB->fieldExists('glpi_plugin_ocsinventoryng_configs', 'log_imported_computers')) {
         $query = "ALTER TABLE `glpi_plugin_ocsinventoryng_configs` 
               ADD `log_imported_computers` TINYINT(1) NOT NULL DEFAULT '0';";
         $DB->queryOrDie($query, "1.5.1 add log_imported_computers in glpi_plugin_ocsinventoryng_configs");
      }/*1.5.1*/

      /******************* Migration 1.5.2 *******************/
      if (!$DB->fieldExists('glpi_plugin_ocsinventoryng_ocsservers', 'history_os')) {
         $query = "ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` 
               ADD `history_os` tinyint(1) NOT NULL DEFAULT '1';";
         $DB->queryOrDie($query, "1.5.2 add history_os in glpi_plugin_ocsinventoryng_ocsservers");
      }/*1.5.2*/

      /******************* Migration 1.5.5 *******************/
      if (!$DB->tableExists('glpi_plugin_ocsinventoryng_ocsalerts')) {
         plugin_ocsinventoryng_migration_additionnalalerts();

         //crypt mdp
         $ocsserver = new PluginOcsinventoryngOcsServer();
         foreach ($dbu->getAllDataFromTable('glpi_plugin_ocsinventoryng_ocsservers') as $ocs) {
            $ocsserver->update(['id'            => $ocs['id'],
                                'ocs_db_passwd' => $ocs["ocs_db_passwd"]]);

         }
      }/*1.5.5*/

      /******************* Migration 1.6.0 *******************/
      if ($DB->fieldExists('glpi_plugin_ocsinventoryng_ocsservers', 'states_id_default')) {
         $DB->runFile(GLPI_ROOT . "/plugins/ocsinventoryng/install/mysql/1.6.0-update.sql");
      }
      if (!$DB->tableExists('glpi_plugin_ocsinventoryng_customapps')) {
         $DB->runFile(GLPI_ROOT . "/plugins/ocsinventoryng/install/mysql/1.6.1-update.sql");
      }



      /******************* Migration 1.7.0 *******************/

      if (!$DB->tableExists('glpi_plugin_ocsinventoryng_bitlockerstatuses')) {
         $DB->runFile(GLPI_ROOT . "/plugins/ocsinventoryng/install/mysql/1.7.1-update.sql");
      }/*1.7.1*/

      $migration->executeMigration();

      /******************* Migration 1.7.2 *******************/
      // encrypt existing keys if not yet encrypted
      // if it can be base64 decoded then json decoded, we can consider that it was not encrypted
      $ocsserver = new PluginOcsinventoryngOcsServer();
      $dbu    = new DbUtils();
      foreach ($dbu->getAllDataFromTable('glpi_plugin_ocsinventoryng_ocsservers') as $ocs) {
         if (($b64_decoded = base64_decode($ocs["ocs_db_passwd"], true)) !== false
             && json_decode($b64_decoded, true) !== null) {
            $ocsserver->update(['id'            => $ocs['id'],
                                'ocs_db_passwd' => Toolbox::sodiumEncrypt($ocs["ocs_db_passwd"])]);
         }
      }

   }
   //Notifications
   addNotifications();

   $cron = new CronTask();
   if (!$cron->getFromDBbyName('PluginOcsinventoryngThread', 'CleanOldThreads')) {
      CronTask::Register('PluginOcsinventoryngThread', 'CleanOldThreads', HOUR_TIMESTAMP,
                         ['param' => 24]);
   }
   if (!$cron->getFromDBbyName('PluginOcsinventoryngOcsServer', 'ocsng')) {
      CronTask::Register('PluginOcsinventoryngOcsServer', 'ocsng', MINUTE_TIMESTAMP * 5);
   }
   if (!$cron->getFromDBbyName('PluginOcsinventoryngNotimportedcomputer', 'SendAlerts')) {
      // creation du cron - param = duree de conservation
      CronTask::Register('PluginOcsinventoryngNotimportedcomputer', 'SendAlerts', 10 * MINUTE_TIMESTAMP,
                         ['param' => 24]);
   }
   if (!$cron->getFromDBbyName('PluginOcsinventoryngOcsServer', 'CleanOldAgents')) {
      CronTask::Register('PluginOcsinventoryngOcsServer', 'CleanOldAgents', DAY_TIMESTAMP,
                         ['state' => CronTask::STATE_DISABLE]);
   }
   /*1.3.2*/
   if (!$cron->getFromDBbyName('PluginOcsinventoryngOcsServer', 'RestoreOldAgents')) {
      CronTask::Register('PluginOcsinventoryngOcsServer', 'RestoreOldAgents', DAY_TIMESTAMP,
                         ['state' => CronTask::STATE_DISABLE]);
   }
   if (!$cron->getFromDBbyName('PluginOcsinventoryngRuleImportEntity', 'CheckRuleImportEntity')) {
      CronTask::Register('PluginOcsinventoryngRuleImportEntity', 'CheckRuleImportEntity', DAY_TIMESTAMP,
                         ['state' => CronTask::STATE_DISABLE]);
   }

   if (!$cron->getFromDBbyName('PluginOcsinventoryngOcsAlert', 'SynchroAlert')) {
      CronTask::Register('PluginOcsinventoryngOcsAlert', 'SynchroAlert', DAY_TIMESTAMP,
                         ['state' => CronTask::STATE_DISABLE]);
   }
   if (!$cron->getFromDBbyName('PluginOcsinventoryngOcsAlert', 'AlertNewComputers')) {
      CronTask::Register('PluginOcsinventoryngOcsAlert', 'AlertNewComputers', HOUR_TIMESTAMP,
                         ['state' => CronTask::STATE_DISABLE]);
   }

   /*Now delete old tables*/
   $tables_ocs = ["ocs_glpi_crontasks", "ocs_glpi_displaypreferences",
                  "ocs_glpi_ocsadmininfoslinks", "ocs_glpi_ocslinks",
                  "ocs_glpi_ocsservers", "ocs_glpi_registrykeys", "ocs_glpi_profiles"];

   foreach ($tables_ocs as $table_ocs) {
      $DB->query("DROP TABLE IF EXISTS `$table_ocs`;");
   }
   $tables_mass = ["backup_glpi_plugin_massocsimport_configs", "backup_glpi_plugin_massocsimport_details",
                   "backup_glpi_plugin_massocsimport_notimported", "backup_glpi_plugin_massocsimport_servers",
                   "backup_glpi_plugin_massocsimport_threads"];

   foreach ($tables_mass as $table_mass) {
      $DB->query("DROP TABLE IF EXISTS `$table_mass`;");
   }

   PluginOcsinventoryngProfile::initProfile();
   PluginOcsinventoryngProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);

   return true;
}


/**
 * @return bool
 */
function plugin_ocsinventoryng_uninstall() {
   global $DB;

   include_once(GLPI_ROOT . "/plugins/ocsinventoryng/inc/profile.class.php");
   include_once(GLPI_ROOT . "/plugins/ocsinventoryng/inc/menu.class.php");

   $dbu    = new DbUtils();
   $tables = ["glpi_plugin_ocsinventoryng_ocsservers",
              "glpi_plugin_ocsinventoryng_ocslinks",
              "glpi_plugin_ocsinventoryng_ocsadmininfoslinks",
              "glpi_plugin_ocsinventoryng_threads",
              "glpi_plugin_ocsinventoryng_snmpocslinks",
              "glpi_plugin_ocsinventoryng_ipdiscoverocslinks",
              "glpi_plugin_ocsinventoryng_servers",
              "glpi_plugin_ocsinventoryng_configs",
              "glpi_plugin_ocsinventoryng_notimportedcomputers",
              "glpi_plugin_ocsinventoryng_details",
              "glpi_plugin_ocsinventoryng_registrykeys",
              "glpi_plugin_ocsinventoryng_winupdates",
              "glpi_plugin_ocsinventoryng_proxysettings",
              "glpi_plugin_ocsinventoryng_winusers",
              "glpi_plugin_ocsinventoryng_networkports",
              "glpi_plugin_ocsinventoryng_networkporttypes",
              "glpi_plugin_ocsinventoryng_ocsservers_profiles",
              "glpi_plugin_ocsinventoryng_ruleimportentities",
              "glpi_plugin_ocsinventoryng_osinstalls",
              "glpi_plugin_ocsinventoryng_networkshares",
              "glpi_plugin_ocsinventoryng_runningprocesses",
              "glpi_plugin_ocsinventoryng_services",
              "glpi_plugin_ocsinventoryng_customapps",
              "glpi_plugin_ocsinventoryng_bitlockerstatuses",
              "glpi_plugin_ocsinventoryng_teamviewers",
              "glpi_plugin_ocsinventoryng_notificationstates",
              "glpi_plugin_ocsinventoryng_ocsalerts"];

   foreach ($tables as $table) {
      $DB->query("DROP TABLE IF EXISTS `$table`;");
   }

   $old_tables = ["glpi_plugin_ocsinventoryng_profiles",
                  "glpi_plugin_ocsinventoryng_devicebiosdatas",
                  "glpi_plugin_ocsinventoryng_items_devicebiosdatas"];

   foreach ($old_tables as $table) {
      $DB->query("DROP TABLE IF EXISTS `$table`;");
   }

   $tables_glpi = ["glpi_savedsearches", "glpi_displaypreferences", "glpi_logs"];

   foreach ($tables_glpi as $table_glpi) {
      $DB->query("DELETE
                  FROM `" . $table_glpi . "`
                  WHERE `itemtype` IN ('PluginMassocsimportNotimported',
                                       'PluginMassocsimportDetail',
                                       'PluginOcsinventoryngOcsServer',
                                       'PluginOcsinventoryngNotimportedcomputer',
                                       'PluginOcsinventoryngDetail',
                                       'PluginOcsinventoryngRuleImportEntity')");
   }

   $tables_ocs = ["ocs_glpi_crontasks", "ocs_glpi_displaypreferences",
                  "ocs_glpi_ocsadmininfoslinks", "ocs_glpi_ocslinks",
                  "ocs_glpi_ocsservers", "ocs_glpi_registrykeys", "ocs_glpi_profiles"];

   foreach ($tables_ocs as $table_ocs) {
      $DB->query("DROP TABLE IF EXISTS `$table_ocs`;");
   }
   $tables_mass = ["backup_glpi_plugin_massocsimport_configs",
                   "backup_glpi_plugin_massocsimport_details",
                   "backup_glpi_plugin_massocsimport_notimported",
                   "backup_glpi_plugin_massocsimport_servers",
                   "backup_glpi_plugin_massocsimport_threads"];

   foreach ($tables_mass as $table_mass) {
      $DB->query("DROP TABLE IF EXISTS `$table_mass`;");
   }

   $query = "DELETE
             FROM `glpi_alerts`
             WHERE `itemtype` IN ('PluginMassocsimportNotimported',
                                  'PluginOcsinventoryngNotimportedcomputer',
                                  'PluginOcsinventoryngRuleImportEntity')";
   $DB->queryOrDie($query, $DB->error());

   // clean rules
   $rule = new RuleImportEntity();
   foreach ($DB->request("glpi_rules", ['sub_type' => 'RuleImportEntity']) as $data) {
      $rule->delete($data);
   }
   $rule = new RuleImportComputer();
   foreach ($DB->request("glpi_rules", ['sub_type' => 'RuleImportComputer']) as $data) {
      $rule->delete($data);
   }

   $notification = new Notification();
   $itemtypes    = ['PluginMassocsimportNotimported',
                    'PluginOcsinventoryngNotimportedcomputer',
                    'PluginOcsinventoryngRuleImportEntity',
                    'PluginOcsinventoryngOcsAlert'];
   foreach ($dbu->getAllDataFromTable($notification->getTable(),
                                      ["itemtype" => $itemtypes]) as $data) {
      $notification->delete($data);
   }
   $template = new NotificationTemplate();
   foreach ($dbu->getAllDataFromTable($template->getTable(),
                                      ["itemtype" => $itemtypes]) as $data) {
      $template->delete($data);
   }

   $cron = new CronTask;
   if ($cron->getFromDBbyName('PluginMassocsimportThread', 'CleanOldThreads')) {
      CronTask::Unregister('massocsimport');
      CronTask::Unregister('CleanOldThreads');
   }
   if ($cron->getFromDBbyName('PluginOcsinventoryngOcsServer', 'ocsng')) {
      CronTask::Unregister('ocsinventoryng');
      CronTask::Unregister('ocsng');
   }
   if ($cron->getFromDBbyName('PluginOcsinventoryngNotimportedcomputer', 'SendAlerts')) {
      CronTask::Unregister('SendAlerts');
   }
   if ($cron->getFromDBbyName('PluginOcsinventoryngOcsServer', 'CleanOldAgents')) {
      CronTask::Unregister('CleanOldAgents');
   }
   if ($cron->getFromDBbyName('PluginOcsinventoryngOcsServer', 'RestoreOldAgents')) {
      CronTask::Unregister('RestoreOldAgents');
   }
   if ($cron->getFromDBbyName('PluginOcsinventoryngRuleImportEntity', 'CheckRuleImportEntity')) {
      CronTask::Unregister('CheckRuleImportEntity');
   }

   if (!$cron->getFromDBbyName('PluginOcsinventoryngOcsAlert', 'SynchroAlert')) {
      CronTask::Unregister('SynchroAlert');
   }
   if (!$cron->getFromDBbyName('PluginOcsinventoryngOcsAlert', 'AlertNewComputers')) {
      CronTask::Unregister('AlertNewComputers');
   }

   //Delete rights associated with the plugin
   $profileRight = new ProfileRight();
   foreach (PluginOcsinventoryngProfile::getAllRights() as $right) {
      $profileRight->deleteByCriteria(['name' => $right['field']]);
   }
   PluginOcsinventoryngMenu::removeRightsFromSession();

   PluginOcsinventoryngProfile::removeRightsFromSession();

   return true;
}


/**
 * @return array
 */
function plugin_ocsinventoryng_getDropdown() {
   // Table => Name
   return ['PluginOcsinventoryngNetworkPortType'     => PluginOcsinventoryngNetworkPortType::getTypeName(2),
           'PluginOcsinventoryngNetworkPort'         => PluginOcsinventoryngNetworkPort::getTypeName(2),
           "PluginOcsinventoryngNotimportedcomputer" => __('Computers not imported by automatic actions', 'ocsinventoryng')];
}

/**
 * Define dropdown relations
 **/
function plugin_ocsinventoryng_getDatabaseRelations() {

   $plugin = new Plugin();

   if ($plugin->isActivated("ocsinventoryng")) {
      return ["glpi_plugin_ocsinventoryng_ocsservers"
              => ["glpi_plugin_ocsinventoryng_ocslinks"             => "plugin_ocsinventoryng_ocsservers_id",
                  "glpi_plugin_ocsinventoryng_ocsadmininfoslinks"   => "plugin_ocsinventoryng_ocsservers_id",
                  "glpi_plugin_ocsinventoryng_threads"              => "plugin_ocsinventoryng_ocsservers_id",
                  "glpi_plugin_ocsinventoryng_details"              => "plugin_ocsinventoryng_ocsservers_id",
                  "glpi_plugin_ocsinventoryng_notimportedcomputers" => "plugin_ocsinventoryng_ocsservers_id",
                  "glpi_plugin_ocsinventoryng_servers"              => "plugin_ocsinventoryng_ocsservers_id",
                  "glpi_plugin_ocsinventoryng_snmpocslinks"         => "plugin_ocsinventoryng_ocsservers_id",
                  "glpi_plugin_ocsinventoryng_ipdiscoverocslinks"   => "plugin_ocsinventoryng_ocsservers_id",
                  "glpi_plugin_ocsinventoryng_ocsservers_profiles"  => "plugin_ocsinventoryng_ocsservers_id"],

              "glpi_entities"
              => ["glpi_plugin_ocsinventoryng_ocslinks"             => "entities_id",
                  "glpi_plugin_ocsinventoryng_threads"              => "entities_id",
                  "glpi_plugin_ocsinventoryng_details"              => "entities_id",
                  "glpi_plugin_ocsinventoryng_notimportedcomputers" => "entities_id"],

              "glpi_computers"
              => ["glpi_plugin_ocsinventoryng_ocslinks"     => "computers_id",
                  "glpi_plugin_ocsinventoryng_registrykeys" => "computers_id",
                  "glpi_plugin_ocsinventoryng_details"      => "computers_id"],

              "glpi_networkports"
              => ["glpi_plugin_ocsinventoryng_networkports" => "networkports_id"],

              "glpi_profiles"
              => ["glpi_plugin_ocsinventoryng_ocsservers_profiles" => "profiles_id"]];
   }
   return [];
}


function plugin_ocsinventoryng_postinit() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['pre_item_add']['ocsinventoryng'] = [];
   $PLUGIN_HOOKS['item_update']['ocsinventoryng']  = [];

   $PLUGIN_HOOKS['pre_item_add']['ocsinventoryng']
      = ['Computer_Item' => ['PluginOcsinventoryngOcslink', 'addComputer_Item']];

   $PLUGIN_HOOKS['pre_item_update']['ocsinventoryng'] = [
      'Infocom' => 'plugin_ocsinventoryng_pre_item_update',
   ];

   $PLUGIN_HOOKS['item_update']['ocsinventoryng']
      = ['Computer'             => ['PluginOcsinventoryngHardware', 'updateLockforComputer'],
         'Infocom'              => 'plugin_ocsinventoryng_item_update',
         'Item_OperatingSystem' => ['PluginOcsinventoryngOS', 'updateLockforOS'],
   ];

   $PLUGIN_HOOKS['pre_item_purge']['ocsinventoryng']
      = ['Computer'      => ['PluginOcsinventoryngOcslink', 'purgeComputer'],
         'Computer_Item' => ['PluginOcsinventoryngOcslink', 'purgeComputer_Item']];

   $PLUGIN_HOOKS['item_purge']['ocsinventoryng']
      = ['Printer'          => ['PluginOcsinventoryngSnmpOcslink', 'purgePrinter'],
         'NetworkEquipment' => ['PluginOcsinventoryngSnmpOcslink', 'purgeNetworkEquipment'],
         'Computer'         => ['PluginOcsinventoryngSnmpOcslink', 'purgeComputer'],
         'Peripheral'       => ['PluginOcsinventoryngSnmpOcslink', 'purgePeripheral'],
         'Phone'            => ['PluginOcsinventoryngSnmpOcslink', 'purgePhone']];

   if (Session::haveRight("plugin_ocsinventoryng", UPDATE)
       || Session::haveRight("plugin_ocsinventoryng_view", READ)
       || Session::haveRight("plugin_ocsinventoryng_sync", UPDATE)
   ) {
      foreach (PluginOcsinventoryngOcsServer::getTypes(true) as $type) {

         CommonGLPI::registerStandardTab($type, 'PluginOcsinventoryngOcsServer');
      }
   }
}

/**
 * @param $type
 *
 * @return array
 */
//TODO && use right for rules
function plugin_ocsinventoryng_MassiveActions($type) {

   switch ($type) {
      case 'PluginOcsinventoryngNotimportedcomputer' :
         $actions                                      = [];
         $actions['PluginOcsinventoryngNotimportedcomputer' . MassiveAction::CLASS_ACTION_SEPARATOR .
                  "plugin_ocsinventoryng_replayrules"] = __("Restart import", 'ocsinventoryng');
         $actions['PluginOcsinventoryngNotimportedcomputer' . MassiveAction::CLASS_ACTION_SEPARATOR .
                  "plugin_ocsinventoryng_import"]      = __("Import in the entity",
                                                            'ocsinventoryng');

         $actions['PluginOcsinventoryngNotimportedcomputer' . MassiveAction::CLASS_ACTION_SEPARATOR .
                  "plugin_ocsinventoryng_delete"] = __('Delete computer in OCSNG',
                                                       'ocsinventoryng');
         return $actions;

      case 'Computer' :
         if (Session::haveRight("plugin_ocsinventoryng", UPDATE)
             || Session::haveRight("plugin_ocsinventoryng_sync", UPDATE)) {

            return [// Specific one
                    'PluginOcsinventoryngOcsProcess' . MassiveAction::CLASS_ACTION_SEPARATOR .
                    "plugin_ocsinventoryng_launch_ocsng_update"
                                                               => _sx('button', 'Launch synchronization', 'ocsinventoryng'),
                    'PluginOcsinventoryngOcsProcess' . MassiveAction::CLASS_ACTION_SEPARATOR .
                    "plugin_ocsinventoryng_force_ocsng_update"
                                                               => _sx('button', 'Force full import', 'ocsinventoryng'),
                    'PluginOcsinventoryngOcsProcess' . MassiveAction::CLASS_ACTION_SEPARATOR .
                    "plugin_ocsinventoryng_lock_ocsng_field"   => __('Lock fields',
                                                                     'ocsinventoryng'),
                    'PluginOcsinventoryngOcsProcess' . MassiveAction::CLASS_ACTION_SEPARATOR .
                    "plugin_ocsinventoryng_unlock_ocsng_field" => __('Unlock fields',
                                                                     'ocsinventoryng')];

         }
         break;

      case 'NetworkPort':
         if (Session::haveRight("plugin_ocsinventoryng", UPDATE)
             && Session::haveRight('networking', UPDATE)) {
            return ['PluginOcsinventoryngNetworkPort' . MassiveAction::CLASS_ACTION_SEPARATOR . 'plugin_ocsinventoryng_update_networkport_type'
                    => __('Update networkport types',
                          'ocsinventoryng')];
         }

   }
   return [];
}


/**
 * @param $itemtype
 *
 * @return array
 */
function plugin_ocsinventoryng_getAddSearchOptions($itemtype) {

   $sopt   = [];
   $plugin = new Plugin();

   if ($plugin->isActivated("ocsinventoryng")) {
      if ($itemtype == 'Computer') {
         if (Session::haveRight("plugin_ocsinventoryng_view", READ)) {

            $sopt[10002]['table']         = 'glpi_plugin_ocsinventoryng_ocslinks';
            $sopt[10002]['field']         = 'last_update';
            $sopt[10002]['name']          = __('OCSNG', 'ocsinventoryng') . " - " . __('GLPI import date', 'ocsinventoryng');
            $sopt[10002]['datatype']      = 'datetime';
            $sopt[10002]['massiveaction'] = false;
            $sopt[10002]['joinparams']    = ['jointype' => 'child'];

            $sopt[10003]['table']         = 'glpi_plugin_ocsinventoryng_ocslinks';
            $sopt[10003]['field']         = 'last_ocs_update';
            $sopt[10003]['name']          = __('OCSNG', 'ocsinventoryng') . " - " . __('Last OCSNG inventory date', 'ocsinventoryng');
            $sopt[10003]['datatype']      = 'datetime';
            $sopt[10003]['massiveaction'] = false;
            $sopt[10003]['joinparams']    = ['jointype' => 'child'];

            $sopt[10001]['table']      = 'glpi_plugin_ocsinventoryng_ocslinks';
            $sopt[10001]['field']      = 'use_auto_update';
            $sopt[10001]['linkfield']  = '_auto_update_ocs'; // update through compter update process
            $sopt[10001]['name']       = __('OCSNG', 'ocsinventoryng') . " - " . __('Automatic update OCSNG', 'ocsinventoryng');
            $sopt[10001]['datatype']   = 'bool';
            $sopt[10001]['joinparams'] = ['jointype' => 'child'];

            $sopt[10004]['table']         = 'glpi_plugin_ocsinventoryng_ocslinks';
            $sopt[10004]['field']         = 'ocs_agent_version';
            $sopt[10004]['name']          = __('OCSNG', 'ocsinventoryng') . " - " . __('Inventory agent', 'ocsinventoryng');
            $sopt[10004]['massiveaction'] = false;
            $sopt[10004]['joinparams']    = ['jointype' => 'child'];

            $sopt[10005]['table']         = 'glpi_plugin_ocsinventoryng_ocslinks';
            $sopt[10005]['field']         = 'tag';
            $sopt[10005]['name']          = __('OCSNG', 'ocsinventoryng') . " - " . __('OCSNG TAG', 'ocsinventoryng');
            $sopt[10005]['datatype']      = 'string';
            $sopt[10005]['massiveaction'] = false;
            $sopt[10005]['joinparams']    = ['jointype' => 'child'];

            $sopt[10006]['table']         = 'glpi_plugin_ocsinventoryng_ocslinks';
            $sopt[10006]['field']         = 'ocsid';
            $sopt[10006]['name']          = __('OCSNG', 'ocsinventoryng') . " - " . __('OCSNG ID', 'ocsinventoryng');
            $sopt[10006]['datatype']      = 'number';
            $sopt[10006]['massiveaction'] = false;
            $sopt[10006]['joinparams']    = ['jointype' => 'child'];

            $sopt[10007]['table']         = 'glpi_plugin_ocsinventoryng_ocslinks';
            $sopt[10007]['field']         = 'last_ocs_conn';
            $sopt[10007]['name']          = __('OCSNG', 'ocsinventoryng') . " - " . __('Last OCSNG connection date', 'ocsinventoryng');
            $sopt[10007]['datatype']      = 'date';
            $sopt[10007]['massiveaction'] = false;
            $sopt[10007]['joinparams']    = ['jointype' => 'child'];

            $sopt[10008]['table']         = 'glpi_plugin_ocsinventoryng_ocslinks';
            $sopt[10008]['field']         = 'ip_src';
            $sopt[10008]['name']          = __('OCSNG', 'ocsinventoryng') . " - " . __('IP Source', 'ocsinventoryng');
            $sopt[10008]['datatype']      = 'string';
            $sopt[10008]['massiveaction'] = false;
            $sopt[10008]['joinparams']    = ['jointype' => 'child'];

            $sopt[10009]['table']         = 'glpi_plugin_ocsinventoryng_ocslinks';
            $sopt[10009]['field']         = 'uptime';
            $sopt[10009]['name']          = __('OCSNG', 'ocsinventoryng') . " - " . __('Uptime', 'ocsinventoryng');
            $sopt[10009]['datatype']      = 'string';
            $sopt[10009]['massiveaction'] = false;
            $sopt[10009]['joinparams']    = ['jointype' => 'child'];

            $sopt[10018]['table']         = 'glpi_plugin_ocsinventoryng_ocslinks';
            $sopt[10018]['field']         = 'computer_update';
            $sopt[10018]['name']          = __('OCSNG', 'ocsinventoryng') . " - " . _n('Lock', 'Locks', 2);
            $sopt[10018]['datatype']      = 'string';
            $sopt[10018]['massiveaction'] = false;
            $sopt[10018]['joinparams']    = ['jointype' => 'child'];

            //$sopt['registry']           = __('Registry', 'ocsinventoryng');

            $sopt[10010]['table']         = 'glpi_plugin_ocsinventoryng_registrykeys';
            $sopt[10010]['field']         = 'value';
            $sopt[10010]['name']          = sprintf(__('%1$s: %2$s'), __('OCSNG', 'ocsinventoryng') . " - " . __('Registry',
                                                                                                                 'ocsinventoryng'), __('Key/Value',
                                                                                                                                       'ocsinventoryng'));
            $sopt[10010]['forcegroupby']  = true;
            $sopt[10010]['massiveaction'] = false;
            $sopt[10010]['joinparams']    = ['jointype' => 'child'];

            $sopt[10011]['table']         = 'glpi_plugin_ocsinventoryng_registrykeys';
            $sopt[10011]['field']         = 'ocs_name';
            $sopt[10011]['name']          = sprintf(__('%1$s: %2$s'), __('OCSNG', 'ocsinventoryng') . " - " . __('Registry',
                                                                                                                 'ocsinventoryng'), __('OCSNG name',
                                                                                                                                       'ocsinventoryng'));
            $sopt[10011]['forcegroupby']  = true;
            $sopt[10011]['massiveaction'] = false;
            $sopt[10011]['joinparams']    = ['jointype' => 'child'];

            $sopt[10012]['table']         = 'glpi_plugin_ocsinventoryng_ocsservers';
            $sopt[10012]['field']         = 'name';
            $sopt[10012]['name']          = __('OCSNG', 'ocsinventoryng') . " - " . __('OCSNG server', 'ocsinventoryng');
            $sopt[10012]['forcegroupby']  = true;
            $sopt[10012]['massiveaction'] = false;
            $sopt[10012]['datatype']      = 'dropdown';
            $sopt[10012]['joinparams']    = ['beforejoin'
                                             => ['table'      => 'glpi_plugin_ocsinventoryng_ocslinks',
                                                 'joinparams' => ['jointype' => 'child']]];

            $sopt[10014]['table']         = 'glpi_plugin_ocsinventoryng_proxysettings';
            $sopt[10014]['field']         = 'enabled';
            $sopt[10014]['name']          = __('OCSNG', 'ocsinventoryng') . " - " . __('Proxy enabled', 'ocsinventoryng');
            $sopt[10014]['forcegroupby']  = true;
            $sopt[10014]['massiveaction'] = false;
            //$sopt[10014]['datatype']      = 'dropdown';
            $sopt[10014]['joinparams'] = ['jointype' => 'child'];

            $sopt[10015]['table']         = 'glpi_plugin_ocsinventoryng_proxysettings';
            $sopt[10015]['field']         = 'address';
            $sopt[10015]['name']          = __('OCSNG', 'ocsinventoryng') . " - " . __('Proxy address', 'ocsinventoryng');
            $sopt[10015]['forcegroupby']  = true;
            $sopt[10015]['massiveaction'] = false;
            $sopt[10015]['joinparams']    = ['jointype' => 'child'];

            $sopt[10016]['table']         = 'glpi_plugin_ocsinventoryng_services';
            $sopt[10016]['field']         = 'svcname';
            $sopt[10016]['name']          = __('OCSNG', 'ocsinventoryng') . " - " . __('Name of the service', 'ocsinventoryng');
            $sopt[10016]['forcegroupby']  = true;
            $sopt[10016]['massiveaction'] = false;
            $sopt[10016]['joinparams']    = ['jointype' => 'child'];

            $sopt[10017]['table']         = 'glpi_plugin_ocsinventoryng_runningprocesses';
            $sopt[10017]['field']         = 'processname';
            $sopt[10017]['name']          = __('OCSNG', 'ocsinventoryng') . " - " . __('Process name', 'ocsinventoryng');
            $sopt[10017]['forcegroupby']  = true;
            $sopt[10017]['massiveaction'] = false;
            $sopt[10017]['joinparams']    = ['jointype' => 'child'];
         }
      }
      if (in_array($itemtype, PluginOcsinventoryngSnmpOcslink::$snmptypes)) {
         if (Session::haveRight("plugin_ocsinventoryng_view", READ)) {

            $sopt[10013]['table']         = 'glpi_plugin_ocsinventoryng_snmpocslinks';
            $sopt[10013]['field']         = 'last_update';
            $sopt[10013]['name']          = __('OCSNG', 'ocsinventoryng') . " - " . __('SNMP Import', 'ocsinventoryng') . " - " . __('GLPI import date', 'ocsinventoryng');
            $sopt[10013]['datatype']      = 'datetime';
            $sopt[10013]['massiveaction'] = false;
            $sopt[10013]['joinparams']    = ['jointype' => 'itemtype_item'];
         }
      }
   }
   return $sopt;
}


/**
 * @param $type
 * @param $ID
 * @param $data
 * @param $num
 *
 * @return string
 */
function plugin_ocsinventoryng_displayConfigItem($type, $ID, $data, $num) {

   $searchopt = &Search::getOptions($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

   switch ($table . '.' . $field) {
      case "glpi_plugin_ocsinventoryng_ocslinks.last_update" :
      case "glpi_plugin_ocsinventoryng_ocslinks.last_ocs_update" :
         return " class='center'";
   }
   return "";
}


/**
 * @param $type
 * @param $id
 * @param $num
 *
 * @return string
 */
function plugin_ocsinventoryng_addSelect($type, $ID, $num) {

   $searchopt = &Search::getOptions($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

   switch ($type) {
      case 'PluginOcsinventoryngNotimportedcomputer' :
         $out = "`$table`.`$field`  AS `ITEM_$num`,
           `glpi_plugin_ocsinventoryng_notimportedcomputers`.`ocsid` AS ocsid,
           `glpi_plugin_ocsinventoryng_notimportedcomputers`.`plugin_ocsinventoryng_ocsservers_id` AS plugin_ocsinventoryng_ocsservers_id, ";
         return $out;

      case 'PluginOcsinventoryngDetail' :
         $out = "`$table`.`$field`  AS `ITEM_$num`,
           `glpi_plugin_ocsinventoryng_details`.`ocsid` AS ocsid,
           `glpi_plugin_ocsinventoryng_details`.`plugin_ocsinventoryng_ocsservers_id` AS plugin_ocsinventoryng_ocsservers_id, 
           `glpi_plugin_ocsinventoryng_details`.`plugin_ocsinventoryng_threads_id`,
                  `glpi_plugin_ocsinventoryng_details`.`threadid`, ";
         return $out;
   }
   return "";
}


/**
 * @param $link
 * @param $nott
 * @param $type
 * @param $ID
 * @param $val
 *
 * @return string
 */
function plugin_ocsinventoryng_addWhere($link, $nott, $type, $ID, $val) {

   $searchopt = &Search::getOptions($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

   Search::makeTextSearch($val, $nott);
   switch ($table . "." . $field) {
      case "glpi_plugin_ocsinventoryng_details.action" :
         return $link . " `$table`.`$field` = '$val' ";
   }
   return "";
}


/**
 * @param $type
 * @param $id
 * @param $data
 * @param $num
 *
 * @return string|translated
 */
function plugin_ocsinventoryng_giveItem($type, $id, $data, $num) {

   $searchopt = &Search::getOptions($type);
   $table     = $searchopt[$id]["table"];
   $field     = $searchopt[$id]["field"];

   switch ("$table.$field") {
      case "glpi_plugin_ocsinventoryng_details.action" :
         $detail = new PluginOcsinventoryngDetail();
         return $detail->giveActionNameByActionID($data[$num][0]['name']);

      case "glpi_plugin_ocsinventoryng_details.computers_id" :
         $comp = new Computer();
         $comp->getFromDB($data[$num][0]['name']);
         return "<a href='" . Toolbox::getItemTypeFormURL('Computer') . "?id=" . $data[$num][0]['name'] . "'>" .
                $comp->getName() . "</a>";

      case "glpi_plugin_ocsinventoryng_details.plugin_ocsinventoryng_ocsservers_id" :
         $ocs = new PluginOcsinventoryngOcsServer();
         $ocs->getFromDB($data[$num][0]['name']);
         return "<a href='" . Toolbox::getItemTypeFormURL('PluginOcsinventoryngOcsServer') . "?id=" .
                $data[$num][0]['name'] . "'>" . $ocs->getName() . "</a>";

      case "glpi_plugin_ocsinventoryng_details.rules_id" :
         $detail = new PluginOcsinventoryngDetail();
         $detail->getFromDB($data['id']);
         return PluginOcsinventoryngNotimportedcomputer::getRuleMatchedMessage($detail->fields['rules_id']);

      case "glpi_plugin_ocsinventoryng_notimportedcomputers.reason" :
         return PluginOcsinventoryngNotimportedcomputer::getReason($data[$num][0]['name']);

      case "glpi_plugin_ocsinventoryng_ocslinks.computer_update" :
         $locks           = PluginOcsinventoryngOcslink::getLocksForComputer($data['id'], 0);
         $lockable_fields = PluginOcsinventoryngOcslink::getLockableFields(0, 0);
         $listlocks       = " ";
         if (is_array($locks) && count($locks)) {
            foreach ($locks as $key => $val) {
               $listlocks .= $lockable_fields[$val] . "<br>";
            }
         }
         return $listlocks;

   }
   return '';
}


/**
 * @param $params array
 *
 * @return bool
 */
function plugin_ocsinventoryng_searchOptionsValues($params = []) {

   switch ($params['searchoption']['field']) {
      case "action":
         PluginOcsinventoryngDetail::showActions($params['name'], $params['value']);
         return true;
   }
   return false;
}

/**
 *
 * Criteria for rules
 *
 * @param $params           input data
 *
 * @return array array of criteria
 * @since 0.84
 *
 */
function plugin_ocsinventoryng_getRuleCriteria($params) {
   $criteria = [];

   switch ($params['rule_itemtype']) {
      case 'RuleImportEntity':
         $criteria['TAG']['table']     = 'accountinfo';
         $criteria['TAG']['field']     = 'TAG';
         $criteria['TAG']['name']      = __('OCSNG TAG', 'ocsinventoryng');
         $criteria['TAG']['linkfield'] = 'HARDWARE_ID';

         $criteria['DOMAIN']['table']     = 'hardware';
         $criteria['DOMAIN']['field']     = 'WORKGROUP';
         $criteria['DOMAIN']['name']      = __('Domain');
         $criteria['DOMAIN']['linkfield'] = '';

         $criteria['OCS_SERVER']['table']     = 'glpi_plugin_ocsinventoryng_ocsservers';
         $criteria['OCS_SERVER']['field']     = 'name';
         $criteria['OCS_SERVER']['name']      = _n('OCSNG server', 'OCSNG servers', 1,
                                                   'ocsinventoryng');
         $criteria['OCS_SERVER']['linkfield'] = '';
         $criteria['OCS_SERVER']['type']      = 'dropdown';
         $criteria['OCS_SERVER']['virtual']   = true;
         $criteria['OCS_SERVER']['id']        = 'ocs_server';

         $criteria['IPSUBNET']['table']     = 'networks';
         $criteria['IPSUBNET']['field']     = 'IPSUBNET';
         $criteria['IPSUBNET']['name']      = __('Subnet');
         $criteria['IPSUBNET']['linkfield'] = 'HARDWARE_ID';

         $criteria['IPADDRESS']['table']     = 'networks';
         $criteria['IPADDRESS']['field']     = 'IPADDRESS';
         $criteria['IPADDRESS']['name']      = __('IP address');
         $criteria['IPADDRESS']['linkfield'] = 'HARDWARE_ID';

         $criteria['MACHINE_NAME']['table']     = 'hardware';
         $criteria['MACHINE_NAME']['field']     = 'NAME';
         $criteria['MACHINE_NAME']['name']      = __("Computer's name");
         $criteria['MACHINE_NAME']['linkfield'] = '';

         $criteria['DESCRIPTION']['table']     = 'hardware';
         $criteria['DESCRIPTION']['field']     = 'DESCRIPTION';
         $criteria['DESCRIPTION']['name']      = __('Description');
         $criteria['DESCRIPTION']['linkfield'] = '';

         $criteria['SSN']['table']     = 'bios';
         $criteria['SSN']['field']     = 'SSN';
         $criteria['SSN']['name']      = __('Serial number');
         $criteria['SSN']['linkfield'] = 'HARDWARE_ID';
         break;

      case 'RuleImportComputer':
         $criteria['ocsservers_id']['table']     = 'glpi_plugin_ocsinventoryng_ocsservers';
         $criteria['ocsservers_id']['field']     = 'name';
         $criteria['ocsservers_id']['name']      = _n('OCSNG server', 'OCSNG servers', 1,
                                                      'ocsinventoryng');
         $criteria['ocsservers_id']['linkfield'] = '';
         $criteria['ocsservers_id']['type']      = 'dropdown';

         $criteria['TAG']['table']     = 'accountinfo';
         $criteria['TAG']['field']     = 'TAG';
         $criteria['TAG']['name']      = __('OCSNG TAG', 'ocsinventoryng');
         $criteria['TAG']['linkfield'] = 'HARDWARE_ID';

         break;
   }

   return $criteria;
}

/**
 *
 * Actions for rules
 *
 * @param $params           input data
 *
 * @return array array of actions
 * @since 0.84
 *
 */
function plugin_ocsinventoryng_getRuleActions($params) {
   //
   $actions = [];

   switch ($params['rule_itemtype']) {
      case 'RuleImportEntity':
         $actions['_affect_entity_by_tag']['name']          = __('Entity from TAG');
         $actions['_affect_entity_by_tag']['type']          = 'text';
         $actions['_affect_entity_by_tag']['force_actions'] = ['regex_result'];

         /*$actions['locations_id']['name']     = __('Location');
         $actions['locations_id']['type']     = 'dropdown';
         $actions['locations_id']['table']    = 'glpi_locations';
         $actions['locations_id']['force_actions'] = array('assign','fromuser');*/

         break;
      case 'RuleImportComputer':
         $actions['_fusion']['name'] = _n('OCSNG link', 'OCSNG links', 1, 'ocsinventoryng');
         $actions['_fusion']['type'] = 'fusion_type';
         break;
   }

   return $actions;
}

/**
 * @param $params           input data
 *
 * @return array array of criteria value to add for processing
 * @see inc/RuleCollection::prepareInputDataForProcess()
 * @since 0.84
 *
 */
function plugin_ocsinventoryng_ruleCollectionPrepareInputDataForProcess($params) {
   switch ($params['rule_itemtype']) {
      case 'RuleImportEntity':
      case 'RuleImportComputer':

         if ($params['rule_itemtype'] == 'RuleImportEntity') {
            $ocsservers_id = $params['values']['input']['ocsservers_id'];
         } else {
            $ocsservers_id = $params['values']['params']['plugin_ocsinventoryng_ocsservers_id'];
         }

         $rule_parameters = [
            'ocsservers_id' => $ocsservers_id,
            'OCS_SERVER'    => $ocsservers_id
         ];

         if (isset($params['values']['params']['ocsid'])) {
            $ocsid = $params['values']['params']['ocsid'];
         } else if ($params['values']['input']['id']) {
            $ocsid = $params['values']['input']['id'];
         }

         $ocsClient = PluginOcsinventoryngOcsServer::getDBocs($ocsservers_id);

         $tables = array_keys(plugin_ocsinventoryng_getTablesForQuery($params['rule_itemtype']));
         $fields = plugin_ocsinventoryng_getFieldsForQuery($params['rule_itemtype']);

         $ocsComputer = $ocsClient->getOcsComputer($ocsid, $tables);

         if (!is_null($ocsComputer) && count($ocsComputer) > 0) {
            if (isset($ocsComputer['NETWORKS'])) {
               $networks = $ocsComputer['NETWORKS'];

               $ipblacklist  = Blacklist::getIPs();
               $macblacklist = Blacklist::getMACs();

               foreach ($networks as $data) {
                  if (isset($data['IPSUBNET'])) {
                     $rule_parameters['IPSUBNET'][] = $data['IPSUBNET'];
                  }
                  if (isset($data['MACADDR']) && !in_array($data['MACADDR'], $macblacklist)) {
                     $rule_parameters['MACADDRESS'][] = $data['MACADDR'];
                  }
                  if (isset($data['IPADDRESS']) && !in_array($data['IPADDRESS'], $ipblacklist)) {
                     $rule_parameters['IPADDRESS'][] = $data['IPADDRESS'];
                  }
               }
            }
            $ocs_data = [];

            foreach ($fields as $field) {
               // TODO cleaner way of getting fields
               $field = explode('.', $field);
               if (count($field) < 2) {
                  continue;
               }

               $table = strtoupper($field[0]);

               $fieldSql  = explode(' ', $field[1]);
               $ocsField  = $fieldSql[0];
               $glpiField = $fieldSql[count($fieldSql) - 1];

               $section = [];
               if (isset($ocsComputer[$table])) {
                  $section = $ocsComputer[$table];
               }
               if (array_key_exists($ocsField, $section)) {
                  // Not multi
                  $ocs_data[$glpiField][] = $section[$ocsField];
               } else {
                  foreach ($section as $sectionLine) {
                     $ocs_data[$glpiField][] = $sectionLine[$ocsField];
                  }
               }
            }

            //This case should never happend but...
            //Sometimes OCS can't find network ports but fill the right ip in hardware table...
            //So let's use the ip to proceed rules (if IP is a criteria of course)
            if (in_array("IPADDRESS", $fields) && !isset($ocs_data['IPADDRESS'])) {
               $ocs_data['IPADDRESS']
                  = PluginOcsinventoryngOcsProcess::getGeneralIpAddress($ocsservers_id, $ocsid);
            }
            return array_merge($rule_parameters, $ocs_data);

         }
   }
   return [];
}

/**
 *
 * Actions for rules
 *
 * @param $params           input data
 *
 * @return an array of actions
 * @since 0.84
 *
 */
function plugin_ocsinventoryng_executeActions($params) {

   $action = $params['action'];
   $output = $params['output'];
   switch ($params['params']['rule_itemtype']) {
      /*case 'RuleImportEntity':
         switch ($action->fields["action_type"]) {
            case 'fromuser' :
              if (($action->fields['field'] == 'locations_id')
                  &&  isset($output['users_locations'])
                  ) {
                 $output['locations_id'] = $output['users_locations'];
              }
              break;
         }*/
      case 'RuleImportComputer':
         if ($action->fields['field'] == '_fusion') {
            if ($action->fields["value"] == RuleImportComputer::RULE_ACTION_LINK_OR_IMPORT) {
               if (isset($params['params']['criterias_results']['found_computers'])) {
                  $output['found_computers'] = $params['params']['criterias_results']['found_computers'];
                  $output['action']          = PluginOcsinventoryngOcsProcess::LINK_RESULT_LINK;
               } else {
                  $output['action'] = PluginOcsinventoryngOcsProcess::LINK_RESULT_IMPORT;
               }

            } else if ($action->fields["value"] == RuleImportComputer::RULE_ACTION_LINK_OR_NO_IMPORT) {
               if (isset($params['params']['criterias_results']['found_computers'])) {
                  $output['found_computers'] = $params['params']['criterias_results']['found_computers'];;
                  $output['action'] = PluginOcsinventoryngOcsProcess::LINK_RESULT_LINK;
               } else {
                  $output['action'] = PluginOcsinventoryngOcsProcess::LINK_RESULT_NO_IMPORT;
               }
            }
         } else {
            $output['action'] = PluginOcsinventoryngOcsProcess::LINK_RESULT_NO_IMPORT;
         }
         break;
   }
   return $output;
}

/**
 *
 * Preview for test a Rule
 *
 * @param $params           input data
 *
 * @return  $output array
 * @since 0.84
 *
 */
function plugin_ocsinventoryng_preProcessRulePreviewResults($params) {
   $output = $params['output'];

   switch ($params['params']['rule_itemtype']) {
      case 'RuleImportComputer':

         //If ticket is assign to an object, display this information first
         if (isset($output["action"])) {
            echo "<tr class='tab_bg_2'>";
            echo "<td>" . __('Action type') . "</td>";
            echo "<td>";

            switch ($output["action"]) {
               case PluginOcsinventoryngOcsProcess::LINK_RESULT_LINK:
                  echo __('Link possible', 'ocsinventoryng');
                  break;

               case PluginOcsinventoryngOcsProcess::LINK_RESULT_NO_IMPORT:
                  echo __('Import refused', 'ocsinventoryng');
                  break;

               case PluginOcsinventoryngOcsProcess::LINK_RESULT_IMPORT:
                  echo __('New computer created in GLPI', 'ocsinventoryng');
                  break;
            }

            echo "</td>";
            echo "</tr>";
            if ($output["action"] != PluginOcsinventoryngOcsProcess::LINK_RESULT_NO_IMPORT
                && isset($output["found_computers"])
            ) {
               echo "<tr class='tab_bg_2'>";
               $item = new Computer;
               if ($item->getFromDB($output["found_computers"][0])) {
                  echo "<td>" . __('Link with computer', 'ocsinventoryng') . "</td>";
                  echo "<td>" . $item->getLink(['comments' => true]) . "</td>";
               }
               echo "</tr>";
            }
         }
         break;
   }
   return $output;
}

/**
 *
 * Preview for test a RuleCoolection
 *
 * @param $params           input data
 *
 * @return  $output array
 * @since 0.84
 *
 */
function plugin_ocsinventoryng_preProcessRuleCollectionPreviewResults($params) {
   return plugin_ocsinventoryng_preProcessRulePreviewResults($params);
}

/**
 * Get the list of all tables to include in the query
 *
 * @param $rule_itemtype
 *
 * @return array array of table names
 */
function plugin_ocsinventoryng_getTablesForQuery($rule_itemtype) {

   $tables = [];
   $crits  = plugin_ocsinventoryng_getRuleCriteria(['rule_itemtype' => $rule_itemtype]);

   foreach ($crits as $criteria) {
      if ((!isset($criteria['virtual'])
           || !$criteria['virtual'])
          && $criteria['table'] != ''
          && !isset($tables[$criteria["table"]])
      ) {

         $tables[$criteria['table']] = $criteria['linkfield'];
      }
   }
   return $tables;
}


/**
 *  * Get fields needed to process criterias
 *
 * @param            $rule_itemtype
 * @param fields|int $withouttable fields without tablename ? (default 0)
 *
 * @return array array of needed fields
 */
function plugin_ocsinventoryng_getFieldsForQuery($rule_itemtype, $withouttable = 0) {

   $fields = [];
   foreach (plugin_ocsinventoryng_getRuleCriteria(['rule_itemtype' => $rule_itemtype]) as $key => $criteria) {
      if ($withouttable) {
         if (strcasecmp($key, $criteria['field']) != 0) {
            $fields[] = $key;
         } else {
            $fields[] = $criteria['field'];
         }

      } else {
         //If the field is different from the key
         if (strcasecmp($key, $criteria['field']) != 0) {
            $as = " AS " . $key;
         } else {
            $as = "";
         }

         //If the field name is not null AND a table name is provided
         if (($criteria['field'] != ''
              && (!isset($criteria['virtual']) || !$criteria['virtual']))
         ) {
            if ($criteria['table'] != '') {
               $fields[] = $criteria['table'] . "." . $criteria['field'] . $as;
            } else {
               $fields[] = $criteria['field'] . $as;
            }
         } else {
            $fields[] = $criteria['id'];
         }
      }
   }
   return $fields;
}


/**
 * Get foreign fields needed to process criterias
 *
 * @return array array of needed fields
 */
function plugin_ocsinventoryng_getFKFieldsForQuery() {

   $fields = [];
   foreach (plugin_ocsinventoryng_getRuleCriteria(['rule_itemtype'
                                                   => 'RuleImportEntity']) as $criteria) {
      //If the field name is not null AND a table name is provided
      if ((!isset($criteria['virtual']) || !$criteria['virtual'])
          && $criteria['linkfield'] != ''
      ) {
         $fields[] = $criteria['table'] . "." . $criteria['linkfield'];
      }
   }
   return $fields;
}

/**
 *
 * Add global criteria for ruleImportComputer rules engine
 *
 * @param $global_criteria an array of global criteria for this rule engine
 *
 * @return array array including plugin's criteria
 * @since 1.0
 *
 */
function plugin_ocsinventoryng_ruleImportComputer_addGlobalCriteria($global_criteria) {
   return array_merge($global_criteria, ['IPADDRESS', 'IPSUBNET', 'MACADDRESS']);
}

/**
 *
 * Get SQL restriction for ruleImportComputer
 *
 * @param array|necessary $params
 *
 * @return array|\necessary array with SQL restrict resquests
 * @internal param necessary $params parameters to build SQL restrict requests
 * @since 1.0
 */
function plugin_ocsinventoryng_ruleImportComputer_getSqlRestriction($params = []) {
   global $CFG_GLPI;
   // Search computer, in entity, not already linked

   //resolve The rule result no preview : Drop this restriction `glpi_plugin_ocsinventoryng_ocslinks`.`computers_id` IS NULL
   $params['sql_where'][] = ["glpi_computers.is_template" => '0'];

   if ($CFG_GLPI['transfers_id_auto'] < 1) {
      $params['sql_where'][] = ["glpi_computers.entities_id" => $params['where_entity']];
   }
   $params['sql_from']     = "glpi_computers";
   $params['sql_leftjoin'] = [
      'glpi_plugin_ocsinventoryng_ocslinks' => [
         'ON' => [
            'glpi_computers'                      => 'id',
            'glpi_plugin_ocsinventoryng_ocslinks' => 'computers_id',
         ]
      ]
   ];
   $needport               = false;
   $needip                 = false;
   foreach ($params['criteria'] as $criteria) {
      switch ($criteria->fields['criteria']) {
         case 'IPADDRESS' :
            $ips = $params['input']["IPADDRESS"];
            if (!is_array($ips)) {
               $ips = [$params['input']["IPADDRESS"]];
            }
            if (count($ips)) {
               $needport              = true;
               $needip                = true;
               $params['sql_where'][] = ["`glpi_ipaddresses`.`name`" => $ips];
            }
            break;

         case 'MACADDRESS' :
            $macs = $params['input']["MACADDRESS"];
            if (!is_array($macs)) {
               $macs = [$params['input']["MACADDRESS"]];
            }
            if (count($macs)) {
               $needport              = true;
               $params['sql_where'][] = ["`glpi_networkports`.`mac`" => $macs];
            }
            break;
      }
   }

   if ($needport) {
      $params['sql_leftjoin'] = [
         'glpi_networkports' => [
            'ON' => [
               'glpi_computers'    => 'id',
               'glpi_networkports' => 'items_id', [
                  'AND' => [
                     "glpi_networkports.itemtype" => 'Computer'
                  ]
               ]
            ]
         ]
      ];
   }
   if ($needip) {
      $params['sql_leftjoin'] = [
         'glpi_networknames' => [
            'ON' => [
               'glpi_networkports' => 'id',
               'glpi_networknames' => 'items_id', [
                  'AND' => [
                     "glpi_networknames.itemtype" => 'NetworkPort'
                  ]
               ]
            ]
         ]
      ];
      $params['sql_leftjoin'] = [
         'glpi_ipaddresses' => [
            'ON' => [
               'glpi_ipaddresses'  => 'items_id',
               'glpi_networknames' => 'id',
            ]
         ]
      ];
   }
   return $params;
}

/**
 *
 * Display plugin's entries in unlock fields form
 *
 * @param an|array $params an array which contains the item and the header boolean
 *
 * @return an array
 * @since 1.0
 *
 */
function plugin_ocsinventoryng_showLocksForItem($params = []) {
   $comp   = $params['item'];
   $header = $params['header'];
   $ID     = $comp->getID();

   $locks = PluginOcsinventoryngOcslink::getLocksForComputer($ID);

   if (!Session::haveRight("computer", UPDATE)) {
      return $params;
   }
   $lockable_fields = PluginOcsinventoryngOcslink::getLockableFields();
   if (is_array($locks) && count($locks)) {
      $header = true;
      echo "<tr><th colspan='2'>" . _n('Locked field', 'Locked fields', 2, 'ocsinventoryng') .
           "</th></tr>\n";

      foreach ($locks as $key => $val) {
         echo "<tr class='tab_bg_1'>";

         echo "<td class='center' width='10'>";
         echo Html::input('lockfield[' . $val . ']', ['type' => 'checkbox']);
         echo "</td>";
         echo "<td class='left' width='95%'>" . $lockable_fields[$val] . "</td>";
         echo "</tr>\n";
      }
   }
   if (!is_array($locks)) {
      echo "<tr class='tab_bg_1'><td class='center red' colspan='2'>" . __("You don't use locks - See setup for activate them", 'ocsinventoryng') .
           "</td></tr>\n";
   }
   $params['header'] = $header;
   return $params;
}

/**
 *
 * Unlock fields managed by the plugin
 *
 * @param array $params
 *
 * @since 1.0
 *
 * @internal param array $_POST
 */
function plugin_ocsinventoryng_unlockFields($params = []) {
   $computer = new Computer();
   $computer->check($_POST['id'], UPDATE);
   if (isset($_POST["lockfield"]) && count($_POST["lockfield"])) {
      foreach ($_POST["lockfield"] as $key => $val) {
         PluginOcsinventoryngOcslink::deleteInOcsArray($_POST["id"], $key, true);
      }
   }
}

/**
 * Update plugin with new computers_id and new entities_id
 *
 * @param $options    array of possible options
 * - itemtype
 * - ID            old ID
 * - newID         new ID
 * - entities_id   new entities_id
 *
 * @return bool
 */
function plugin_ocsinventoryng_item_transfer($options = []) {
   global $DB;

   if ($options['type'] == 'Computer') {

      $query = "UPDATE glpi_plugin_ocsinventoryng_ocslinks
                SET `computers_id` = '" . $options['newID'] . "',
                    `entities_id` = '" . $options['entities_id'] . "'
                WHERE `computers_id` = '" . $options['id'] . "'";

      $DB->query($query);

      Session::addMessageAfterRedirect("Transfer Computer Hook " . $options['type'] . " " .
                                       $options['id'] . "->" . $options['newID']);

   }
   return false;
}


//------------------- Locks migration -------------------

/**
 * Move locks from ocslink.import_* to is_dynamic in related tables
 *
 * @param $migration
 **/
function plugin_ocsinventoryng_migrateComputerLocks(Migration $migration) {
   global $DB, $CFG_GLPI;

   ini_set("memory_limit", "-1");
   ini_set("max_execution_time", "0");
   $import = ['import_printer'    => 'Printer',
              'import_monitor'    => 'Monitor',
              'import_peripheral' => 'Peripheral'];

   foreach ($import as $field => $itemtype) {
      foreach ($DB->request('ocs_glpi_ocslinks', ['FIELDS' => ['computers_id', $field]]) as $data) {
         if ($DB->fieldExists('ocs_glpi_ocslinks', $field)) {
            $dbu          = new DbUtils();
            $import_field = $dbu->importArrayFromDB($data[$field]);

            //If array is not empty
            if (!empty($import_field)) {
               $query_update = "UPDATE `glpi_computers_items`
                                SET `is_dynamic`='1'
                                WHERE `id` IN (" . implode(',', array_keys($import_field)) . ")
                                      AND `itemtype`='$itemtype'";
               $DB->query($query_update);
            }
         }
      }
      $migration->dropField('ocs_glpi_ocslinks', $field);
   }
   //Migration disks and vms
   $import = ['import_disk'     => 'glpi_computerdisks',
              'import_vm'       => 'glpi_computervirtualmachines',
              'import_software' => 'glpi_items_softwareversions',
              'import_ip'       => 'glpi_networkports'];

   foreach ($import as $field => $table) {
      if ($DB->fieldExists('ocs_glpi_ocslinks', $field)) {
         foreach ($DB->request('ocs_glpi_ocslinks', ['FIELDS' => ['computers_id', $field]]) as $data) {
            $dbu          = new DbUtils();
            $import_field = $dbu->importArrayFromDB($data[$field]);

            //If array is not empty
            if (!empty($import_field)) {
               $in_where     = "(" . implode(',', array_keys($import_field)) . ")";
               $query_update = "UPDATE `$table`
                                SET `is_dynamic`='1'
                                WHERE `id` IN $in_where";
               $DB->query($query_update);
               if ($table == 'glpi_networkports') {
                  $query_update = "UPDATE `glpi_networkports` AS PORT,
                                          `glpi_networknames` AS NAME
                                   SET NAME.`is_dynamic` = 1
                                   WHERE PORT.`id` IN $in_where
                                     AND NAME.`itemtype` = 'NetworkPort'
                                     AND NAME.`items_id` = PORT.`id`";
                  $DB->query($query_update);
                  $query_update = "UPDATE `glpi_networkports` AS PORT,
                                          `glpi_networknames` AS NAME,
                                          `glpi_ipaddresses` AS ADDR
                                   SET ADDR.`is_dynamic` = 1
                                   WHERE PORT.`id` IN $in_where
                                     AND NAME.`itemtype` = 'NetworkPort'
                                     AND NAME.`items_id` = PORT.`id`
                                     AND ADDR.`itemtype` = 'NetworkName'
                                     AND ADDR.`items_id` = NAME.`id`";
                  $DB->query($query_update);
               }
            }
         }
         $migration->dropField('ocs_glpi_ocslinks', $field);
      }
   }

   if ($DB->fieldExists('ocs_glpi_ocslinks', 'import_device')) {
      foreach ($DB->request('ocs_glpi_ocslinks', ['FIELDS' => ['computers_id', 'import_device']])
               as $data) {
         $dbu           = new DbUtils();
         $import_device = $dbu->importArrayFromDB($data['import_device']);
         if (!in_array('_version_078_', $import_device)) {
            $import_device = plugin_ocsinventoryng_migrateImportDevice($import_device);
         }

         $devices = [];
         $types   = $CFG_GLPI['ocsinventoryng_devices_index'];
         foreach ($import_device as $key => $val) {
            if (!$key) { // PluginOcsinventoryngOcsProcess::IMPORT_TAG_078
               continue;
            }

            if (strstr($val, '$$$$$') !== false) {
               list($type, $nomdev) = explode('$$$$$', $val);
            }
            if (strstr($key, '$$$$$') !== false) {
               list($type, $iddev) = explode('$$$$$', $key);
            }
            if (!isset($types[$type])) { // should never happen
               continue;
            }
            $devices[$types[$type]][] = $iddev;
         }
         foreach ($devices as $type => $tmp) {
            //If array is not empty
            $query_update = "UPDATE `" . $dbu->getTableForItemType($type) . "`
                             SET `is_dynamic`=1
                             WHERE `id` IN (" . implode(',', $tmp) . ")";
            $DB->query($query_update);
         }
      }
      $migration->dropField('ocs_glpi_ocslinks', 'import_device');
   }
   $migration->migrationOneTable('ocs_glpi_ocslinks');
}


/**
 * Migration import_device field if GLPI version is not 0.78
 *
 * @param $import_device     array
 *
 * @return array array migrated in post 0.78 scheme
 */
function plugin_ocsinventoryng_migrateImportDevice($import_device = []) {

   $new_import_device = ['_version_078_'];
   if (count($import_device)) {
      foreach ($import_device as $key => $val) {
         $tmp = explode('$$$$$', $val);

         if (isset($tmp[1])) { // Except for old IMPORT_TAG
            $tmp2 = explode('$$$$$', $key);
            // Index Could be 1330395 (from glpi 0.72)
            // Index Could be 5$$$$$5$$$$$5$$$$$5$$$$$5$$$$$1330395 (glpi 0.78 bug)
            // So take the last part of the index
            $key2                     = $tmp[0] . '$$$$$' . array_pop($tmp2);
            $new_import_device[$key2] = $val;
         }

      }
   }
   return $new_import_device;
}

/**
 * Checking locks before updating
 *
 * @param type $item
 */
function plugin_ocsinventoryng_pre_item_update($item) {

   if ($item->fields['itemtype'] == "Computer") {
      $ocslink = new PluginOcsinventoryngOcslink();
      if ($ocslink->getFromDBforComputer($item->fields['items_id'])) {

         $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($ocslink->fields["plugin_ocsinventoryng_ocsservers_id"]);
         if ($cfg_ocs["use_locks"]) {
            $field_set        = false;
            $dbu              = new DbUtils();
            $computers_update = $dbu->importArrayFromDB($ocslink->fields['computer_update']);
            if (in_array('use_date', $computers_update)) {
               if (isset ($item->input["use_date"])
                   && $item->input["use_date"] != $item->fields["use_date"]
               ) {
                  $field_set               = true;
                  $item->input["use_date"] = $item->fields["use_date"];
               }
            }
            if ($field_set) {
               Session::addMessageAfterRedirect(__("The startup date field is locked by ocsinventoryng please unlock it before update.", "ocsinventoryng"), true, ERROR);
            }
         }
      }
   }
}

/**
 * @param $item
 */
function plugin_ocsinventoryng_item_update($item) {
   global $DB;

   if ($item->fields['itemtype'] == "Computer") {

      if (in_array('use_date', $item->updates)) {
         $query  = "SELECT *
                   FROM `glpi_plugin_ocsinventoryng_ocslinks`
                   WHERE `computers_id` = '" . $item->fields['items_id'] . "'";
         $result = $DB->query($query);

         if ($DB->numrows($result) == 1) {
            $line    = $DB->fetchAssoc($result);
            $cfg_ocs = PluginOcsinventoryngOcsServer::getConfig($line["plugin_ocsinventoryng_ocsservers_id"]);
            if ($cfg_ocs["use_locks"]) {
               $dbu              = new DbUtils();
               $computer_updates = $dbu->importArrayFromDB($line["computer_update"]);
               //Add lock
               $computer_updates[] = "use_date";
               $dbu                = new DbUtils();
               $query              = "UPDATE `glpi_plugin_ocsinventoryng_ocslinks`
                            SET `computer_update` = '" . addslashes($dbu->exportArrayToDB($computer_updates)) . "'
                            WHERE `computers_id` = '" . $item->fields['items_id'] . "'";
               $DB->query($query);
            }
         }
      }
   }
}

/*Old Upgrade functions*/


function plugin_ocsinventoryng_upgrademassocsimport11to12() {
   global $DB;

   $migration = new Migration(12);

   if (!$DB->tableExists("glpi_plugin_mass_ocs_import_config")) {
      $query = "CREATE TABLE `glpi_plugin_mass_ocs_import_config` (
                  `ID` INT(11) NOT NULL,
                  `enable_logging` INT(1) NOT NULL DEFAULT '1',
                  `thread_log_frequency` INT(4) NOT NULL DEFAULT '10',
                  `display_empty` INT(1) NOT NULL DEFAULT '1',
                  `delete_frequency` INT(4) NOT NULL DEFAULT '0',
                  `import_limit` INT(11) NOT NULL DEFAULT '0',
                  `default_ocs_server` INT(11) NOT NULL DEFAULT '-1',
                  `delay_refresh` VARCHAR(4) NOT NULL DEFAULT '0',
                  `delete_empty_frequency` INT(4) NOT NULL DEFAULT '0',
                  PRIMARY KEY  (`ID`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";

      $DB->queryOrDie($query, "1.1 to 1.2 " . $DB->error());

      $query = "INSERT INTO `glpi_plugin_mass_ocs_import_config`
                     (`ID`, `enable_logging`, `thread_log_frequency`, `display_empty`,
                      `delete_frequency`, `delete_empty_frequency`, `import_limit`,
                      `default_ocs_server` )
                VALUES (1, 1, 5, 1, 2, 2, 0,-1)";

      $DB->queryOrDie($query, "1.1 to 1.2 " . $DB->error());
   }

   $migration->addField("glpi_plugin_mass_ocs_import_config", "warn_if_not_imported", 'integer');
   $migration->addField("glpi_plugin_mass_ocs_import_config", "not_imported_threshold", 'integer');

   $migration->executeMigration();
}


function plugin_ocsinventoryng_upgrademassocsimport121to13() {
   global $DB;

   $migration = new Migration(13);

   if ($DB->tableExists("glpi_plugin_mass_ocs_import_config")) {
      $tables = ["glpi_plugin_massocsimport_servers" => "glpi_plugin_mass_ocs_import_servers",
                 "glpi_plugin_massocsimport"         => "glpi_plugin_mass_ocs_import",
                 "glpi_plugin_massocsimport_config"  => "glpi_plugin_mass_ocs_import_config",
                 "glpi_plugin_massocsimport_not_imported"
                                                     => "glpi_plugin_mass_ocs_import_not_imported"];

      foreach ($tables as $new => $old) {
         $migration->renameTable($old, $new);
      }

      $migration->changeField("glpi_plugin_massocsimport", "process_id", "process_id",
                              "BIGINT(20) NOT NULL DEFAULT '0'");

      $migration->addField("glpi_plugin_massocsimport_config", "comments", 'text');

      $migration->addField("glpi_plugin_massocsimport", "noupdate_machines_number", 'integer');

      if (!$DB->tableExists("glpi_plugin_massocsimport_details")) {
         $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_massocsimport_details` (
                     `ID` INT(11) NOT NULL AUTO_INCREMENT,
                     `process_id` BIGINT(10) NOT NULL DEFAULT '0',
                     `thread_id` INT(4) NOT NULL DEFAULT '0',
                     `ocs_id` INT(11) NOT NULL DEFAULT '0',
                     `glpi_id` INT(11) NOT NULL DEFAULT '0',
                     `action` INT(11) NOT NULL DEFAULT '0',
                     `process_time` DATETIME DEFAULT NULL,
                     `ocs_server_id` INT(4) NOT NULL DEFAULT '1',
                     PRIMARY KEY  (`ID`),
                     KEY `end_time` (`process_time`)
                   ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->queryOrDie($query, "1.2.1 to 1.3 " . $DB->error());
      }

      $drop_fields = [//Was not used, debug only...
                      "glpi_plugin_massocsimport_config" => "warn_if_not_imported",
                      "glpi_plugin_massocsimport_config" => "not_imported_threshold",
                      //Logging must always be enable !
                      "glpi_plugin_massocsimport_config" => "enable_logging",
                      "glpi_plugin_massocsimport_config" => "delete_empty_frequency"];

      foreach ($drop_fields as $table => $field) {
         $migration->dropField($table, $field);
      }
   }
   $migration->executeMigration();
}


function plugin_ocsinventoryng_upgrademassocsimport13to14() {

   $migration = new Migration(14);

   $migration->renameTable("glpi_plugin_massocsimport", "glpi_plugin_massocsimport_threads");

   $migration->changeField("glpi_plugin_massocsimport_threads", "ID", "id", 'autoincrement');
   $migration->changeField("glpi_plugin_massocsimport_threads", "thread_id", "threadid", 'integer');
   $migration->changeField("glpi_plugin_massocsimport_threads", "status", "status", 'integer');
   $migration->changeField("glpi_plugin_massocsimport_threads", "ocs_server_id", "ocsservers_id",
                           'integer', ['value' => 1]);
   $migration->changeField("glpi_plugin_massocsimport_threads", "process_id", "processid",
                           'integer');
   $migration->changeField("glpi_plugin_massocsimport_threads", "noupdate_machines_number",
                           "notupdated_machines_number", 'integer');

   $migration->migrationOneTable("glpi_plugin_massocsimport_threads");

   $migration->addKey("glpi_plugin_massocsimport_threads", ["processid", "threadid"],
                      "process_thread");

   $migration->renameTable("glpi_plugin_massocsimport_config", "glpi_plugin_massocsimport_configs");

   $migration->dropField("glpi_plugin_massocsimport_configs", "delete_frequency");
   $migration->dropField("glpi_plugin_massocsimport_configs", "enable_logging");
   $migration->dropField("glpi_plugin_massocsimport_configs", "delete_empty_frequency");
   $migration->dropField("glpi_plugin_massocsimport_configs", "warn_if_not_imported");
   $migration->dropField("glpi_plugin_massocsimport_configs", "not_imported_threshold");

   $migration->changeField("glpi_plugin_massocsimport_configs", "ID", "id", 'autoincrement');
   $migration->changeField("glpi_plugin_massocsimport_configs", "thread_log_frequency",
                           "thread_log_frequency", 'integer', ['value' => 10]);
   $migration->changeField("glpi_plugin_massocsimport_configs", "display_empty", "is_displayempty",
                           'int(1) NOT NULL default 1');
   $migration->changeField("glpi_plugin_massocsimport_configs", "default_ocs_server",
                           "ocsservers_id", 'integer', ['value' => -1]);
   $migration->changeField("glpi_plugin_massocsimport_configs", "delay_refresh", "delay_refresh",
                           'integer');
   $migration->changeField("glpi_plugin_massocsimport_configs", "comments", "comment", 'text');

   $migration->changeField("glpi_plugin_massocsimport_details", "ID", "id", 'autoincrement');
   $migration->changeField("glpi_plugin_massocsimport_details", "process_id",
                           "plugin_massocsimport_threads_id", 'integer');
   $migration->changeField("glpi_plugin_massocsimport_details", "thread_id", "threadid", 'integer');
   $migration->changeField("glpi_plugin_massocsimport_details", "ocs_id", "ocsid", 'integer');
   $migration->changeField("glpi_plugin_massocsimport_details", "glpi_id", "computers_id",
                           'integer');
   $migration->changeField("glpi_plugin_massocsimport_details", "ocs_server_id",
                           "ocsservers_id", 'integer', ['value' => 1]);

   $migration->migrationOneTable('glpi_plugin_massocsimport_details');
   $migration->addKey("glpi_plugin_massocsimport_details",
                      ["plugin_massocsimport_threads_id", "threadid"], "process_thread");

   $migration->renameTable("glpi_plugin_massocsimport_not_imported",
                           "glpi_plugin_massocsimport_notimported");

   $migration->changeField("glpi_plugin_massocsimport_notimported", "ID", "id", 'autoincrement');
   $migration->changeField("glpi_plugin_massocsimport_notimported", "ocs_id", "ocsid", 'integer');
   $migration->changeField("glpi_plugin_massocsimport_notimported", "ocs_server_id", "ocsservers_id",
                           'integer');
   $migration->changeField("glpi_plugin_massocsimport_notimported", "deviceid", "ocs_deviceid",
                           'string');

   $migration->changeField("glpi_plugin_massocsimport_servers", "ID", "id", 'autoincrement');
   $migration->changeField("glpi_plugin_massocsimport_servers", "ocs_server_id", "ocsservers_id",
                           'integer');
   $migration->changeField("glpi_plugin_massocsimport_servers", "max_ocs_id", "max_ocsid",
                           'int(11) DEFAULT NULL');
   $migration->changeField("glpi_plugin_massocsimport_servers", "max_glpi_date", "max_glpidate",
                           'datetime DEFAULT NULL');

   $migration->executeMigration();
}

function addNotifications() {
   global $DB;

   $query  = "SELECT `id`
                FROM `glpi_notificationtemplates`
                WHERE `itemtype` = 'PluginOcsinventoryngNotimportedcomputer'";
   $result = $DB->query($query);

   if (!$DB->numrows($result)) {
      //Add template
      $query = "INSERT INTO `glpi_notificationtemplates`
                VALUES (NULL, 'Computers not imported', 'PluginOcsinventoryngNotimportedcomputer',
                        NOW(), '', NULL,
                        NOW());";
      $DB->queryOrDie($query, $DB->error());
      $templates_id = $DB->insertId();
      $query        = "INSERT INTO `glpi_notificationtemplatetranslations`
                VALUES (NULL, $templates_id, '',
                        '##lang.notimported.action## : ##notimported.entity##',
                '\r\n\n##lang.notimported.action## :&#160;##notimported.entity##\n\n" .
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
      $DB->queryOrDie($query, $DB->error());

      $query = "INSERT INTO `glpi_notifications` (`name`, `entities_id`, `itemtype`, `event`, `is_recursive`, `is_active`)
                VALUES ('Computers not imported', 0, 'PluginOcsinventoryngNotimportedcomputer', 'not_imported',  1, 1);";
      $DB->queryOrDie($query, $DB->error());

      $query_id = "SELECT `id` FROM `glpi_notifications`
               WHERE `name` = 'Computers not imported' AND `itemtype` = 'PluginOcsinventoryngNotimportedcomputer' AND `event` = 'not_imported'";
      $result = $DB->query($query_id) or die ($DB->error());
      $notification = $DB->result($result, 0, 'id');

      $query = "INSERT INTO `glpi_notifications_notificationtemplates` (`notifications_id`, `mode`, `notificationtemplates_id`) 
               VALUES (" . $notification . ", 'mailing', " . $templates_id . ");";
      $DB->query($query);

   }

   //add notification
   $query  = "SELECT `id`
             FROM `glpi_notificationtemplates`
             WHERE `itemtype` = 'PluginOcsinventoryngRuleImportEntity'";
   $result = $DB->query($query);

   if (!$DB->numrows($result)) {
      //Add template
      $query = "INSERT INTO `glpi_notificationtemplates`
             (`name`, `itemtype`)
             VALUES ('Check rule import entity', 'PluginOcsinventoryngRuleImportEntity');";
      $DB->queryOrDie($query, $DB->error());
      $templates_id = $DB->insertId();
      //Add translations
      $query = "INSERT INTO `glpi_notificationtemplatetranslations`
               (`notificationtemplates_id`, `subject`, `content_text`, `content_html`)
               VALUES ($templates_id,
         '[##checkruleimportentity.date##] ##checkruleimportentity.title## : ##checkruleimportentity.entity##',
         '##FOREACHcheckruleimportentityitems##
##lang.checkruleimportentity.entity## : ##checkruleimportentity.entity##
##lang.checkruleimportentity.computer## : ##checkruleimportentity.computer##
##lang.checkruleimportentity.location## : ##checkruleimportentity.location##
##lang.checkruleimportentity.error## : ##checkruleimportentity.error##
##lang.checkruleimportentity.dataerror## : ##checkruleimportentity.dataerror##
##lang.checkruleimportentity.name_rule## ##checkruleimportentity.name_rule##
##ENDFOREACHcheckruleimportentityitems##',
'&lt;table class=\"tab_cadre\" border=\"1\" cellspacing=\"2\" cellpadding=\"3\"&gt;
&lt;tbody&gt;
&lt;tr&gt;
&lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.checkruleimportentity.entity##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.checkruleimportentity.computer##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text - align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.checkruleimportentity.location##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.checkruleimportentity.error##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.checkruleimportentity.dataerror##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.checkruleimportentity.name_rule##&lt;/span&gt;&lt;/td&gt;
&lt;/tr&gt;
##FOREACHcheckruleimportentityitems##
&lt;tr&gt;
&lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##checkruleimportentity.entity##&lt;/span&gt;&lt;/td&gt;
&lt;td&gt;&lt;a href=\"##checkruleimportentity.url##\" target=\"_blank\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##checkruleimportentity.computer##&lt;/span&gt;&lt;/a&gt;&lt;/td&gt;
&lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##checkruleimportentity.location##&lt;/span&gt;&lt;/td&gt;
&lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##checkruleimportentity.error##&lt;/span&gt;&lt;/td&gt;
&lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##checkruleimportentity.dataerror##&lt;/span&gt;&lt;/td&gt;
&lt;td&gt;&lt;a href=\"##checkruleimportentity.url_rule##\" target=\"_blank\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##checkruleimportentity.name_rule##&lt;/span&gt;&lt;/a&gt;&lt;/td&gt;
&lt;/tr&gt;
##ENDFOREACHcheckruleimportentityitems##
&lt;/tbody&gt;
&lt;/table&gt;');";
      $DB->queryOrDie($query, $DB->error());
      //Add notification
      $query = "INSERT INTO `glpi_notifications` (`name`, `entities_id`, `itemtype`, `event`, `is_recursive`, `is_active`)
             VALUES ('Check rule import entity', 0, 'PluginOcsinventoryngRuleImportEntity','checkruleimportentity', 1, 1);";
      $DB->queryOrDie($query, $DB->error());

      $query_id = "SELECT `id` FROM `glpi_notifications`
               WHERE `name` = 'Check rule import entity' AND `itemtype` = 'PluginOcsinventoryngRuleImportEntity' AND `event` = 'checkruleimportentity'";
      $result = $DB->query($query_id) or die ($DB->error());
      $notification = $DB->result($result, 0, 'id');

      $query = "INSERT INTO `glpi_notifications_notificationtemplates` (`notifications_id`, `mode`, `notificationtemplates_id`) 
               VALUES (" . $notification . ", 'mailing', " . $templates_id . ");";
      $DB->query($query);
   }

   plugin_ocsinventoryng_add_notifications_alerts();

}

function plugin_ocsinventoryng_update110() {
   global $DB;

   $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_threads` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `threadid` INT(11) NOT NULL DEFAULT '0',
                  `start_time` DATETIME DEFAULT NULL,
                  `end_time` DATETIME DEFAULT NULL,
                  `status` INT(11) NOT NULL DEFAULT '0',
                  `error_msg` TEXT NOT NULL,
                  `imported_machines_number` INT(11) NOT NULL DEFAULT '0',
                  `synchronized_machines_number` INT(11) NOT NULL DEFAULT '0',
                  `failed_rules_machines_number` INT(11) NOT NULL DEFAULT '0',
                  `linked_machines_number` INT(11) NOT NULL DEFAULT '0',
                  `notupdated_machines_number` INT(11) NOT NULL DEFAULT '0',
                  `not_unique_machines_number` INT(11) NOT NULL DEFAULT '0',
                  `link_refused_machines_number` INT(11) NOT NULL DEFAULT '0',
                  `total_number_machines` INT(11) NOT NULL DEFAULT '0',
                  `plugin_ocsinventoryng_ocsservers_id` INT(11) NOT NULL DEFAULT '1',
                  `processid` INT(11) NOT NULL DEFAULT '0',
                  `entities_id` INT(11) NOT NULL DEFAULT 0,
                  `rules_id` INT(11) NOT NULL DEFAULT 0,
                  PRIMARY KEY  (`id`),
                  KEY `end_time` (`end_time`),
                  KEY `process_thread` (`processid`,`threadid`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";

   $DB->queryOrDie($query, $DB->error());

   $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_configs` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `thread_log_frequency` INT(11) NOT NULL DEFAULT '10',
                  `is_displayempty` INT(1) NOT NULL DEFAULT '1',
                  `import_limit` INT(11) NOT NULL DEFAULT '0',
                  `delay_refresh` INT(11) NOT NULL DEFAULT '0',
                  `allow_ocs_update` TINYINT(1) NOT NULL DEFAULT '0',
                  `comment` TEXT,
                  PRIMARY KEY (`id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";

   $DB->queryOrDie($query, $DB->error());

   $query = "INSERT INTO `glpi_plugin_ocsinventoryng_configs`
                       (`id`,`thread_log_frequency`,`is_displayempty`,`import_limit`)
                VALUES (1, 2, 1, 0);";
   $DB->queryOrDie($query, $DB->error());

   $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_details` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `entities_id` INT(11) NOT NULL DEFAULT '0',
                  `plugin_ocsinventoryng_threads_id` INT(11) NOT NULL DEFAULT '0',
                  `rules_id` TEXT,
                  `threadid` INT(11) NOT NULL DEFAULT '0',
                  `ocsid` INT(11) NOT NULL DEFAULT '0',
                  `computers_id` INT(11) NOT NULL DEFAULT '0',
                  `action` INT(11) NOT NULL DEFAULT '0',
                  `process_time` DATETIME DEFAULT NULL,
                  `plugin_ocsinventoryng_ocsservers_id` INT(11) NOT NULL DEFAULT '1',
                  PRIMARY KEY (`id`),
                  KEY `end_time` (`process_time`),
                  KEY `process_thread` (`plugin_ocsinventoryng_threads_id`,`threadid`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

   $DB->queryOrDie($query, $DB->error());

   $query = "INSERT INTO `glpi_displaypreferences` (`itemtype`, `num`, `rank`, `users_id`)
                VALUES ('PluginOcsinventoryngNotimportedcomputer', 2, 1, 0),
                       ('PluginOcsinventoryngNotimportedcomputer', 3, 2, 0),
                       ('PluginOcsinventoryngNotimportedcomputer', 4, 3, 0),
                       ('PluginOcsinventoryngNotimportedcomputer', 5, 4, 0),
                       ('PluginOcsinventoryngNotimportedcomputer', 6, 5, 0),
                       ('PluginOcsinventoryngNotimportedcomputer', 7, 6, 0),
                       ('PluginOcsinventoryngNotimportedcomputer', 8, 7, 0),
                       ('PluginOcsinventoryngNotimportedcomputer', 9, 8, 0),
                       ('PluginOcsinventoryngNotimportedcomputer', 10, 9, 0),
                       ('PluginOcsinventoryngDetail', 5, 1, 0),
                       ('PluginOcsinventoryngDetail', 2, 2, 0),
                       ('PluginOcsinventoryngDetail', 3, 3, 0),
                       ('PluginOcsinventoryngDetail', 4, 4, 0),
                       ('PluginOcsinventoryngDetail', 6, 5, 0),
                       ('PluginOcsinventoryngDetail', 80, 6, 0)";
   $DB->queryOrDie($query, $DB->error());

   $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_notimportedcomputers` (
                  `id` INT( 11 ) NOT NULL  AUTO_INCREMENT,
                  `entities_id` INT(11) NOT NULL DEFAULT '0',
                  `rules_id` TEXT,
                  `comment` TEXT NULL,
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

   $DB->queryOrDie($query, $DB->error());

   $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_servers` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `plugin_ocsinventoryng_ocsservers_id` INT(11) NOT NULL DEFAULT '0',
                  `max_ocsid` INT(11) DEFAULT NULL,
                  `max_glpidate` DATETIME DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `plugin_ocsinventoryng_ocsservers_id` (`plugin_ocsinventoryng_ocsservers_id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";

   $DB->queryOrDie($query, $DB->error());
}

function plugin_ocsinventoryng_updatemassocsimport(&$migration) {
   global $DB;

   $dbu = new DbUtils();

   if ($DB->tableExists('glpi_plugin_massocsimport_threads')
       && !$DB->fieldExists('glpi_plugin_massocsimport_threads', 'not_unique_machines_number')) {
      plugin_ocsinventoryng_upgrademassocsimport14to15();
   }
   $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_threads` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `threadid` INT(11) NOT NULL DEFAULT '0',
                  `start_time` DATETIME DEFAULT NULL,
                  `end_time` DATETIME DEFAULT NULL,
                  `status` INT(11) NOT NULL DEFAULT '0',
                  `error_msg` TEXT NOT NULL,
                  `imported_machines_number` INT(11) NOT NULL DEFAULT '0',
                  `synchronized_machines_number` INT(11) NOT NULL DEFAULT '0',
                  `failed_rules_machines_number` INT(11) NOT NULL DEFAULT '0',
                  `linked_machines_number` INT(11) NOT NULL DEFAULT '0',
                  `notupdated_machines_number` INT(11) NOT NULL DEFAULT '0',
                  `not_unique_machines_number` INT(11) NOT NULL DEFAULT '0',
                  `link_refused_machines_number` INT(11) NOT NULL DEFAULT '0',
                  `total_number_machines` INT(11) NOT NULL DEFAULT '0',
                  `ocsservers_id` INT(11) NOT NULL DEFAULT '1',
                  `processid` INT(11) NOT NULL DEFAULT '0',
                  `entities_id` INT(11) NOT NULL DEFAULT 0,
                  `rules_id` INT(11) NOT NULL DEFAULT 0,
                  PRIMARY KEY  (`id`),
                  KEY `end_time` (`end_time`),
                  KEY `process_thread` (`processid`,`threadid`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";

   $DB->queryOrDie($query, $DB->error());

   //error of massocsimport 1.5.0 installaton
   $migration->addField("glpi_plugin_massocsimport_threads", "entities_id", 'integer');
   $migration->addField("glpi_plugin_massocsimport_threads", "rules_id", 'integer');

   foreach ($dbu->getAllDataFromTable('glpi_plugin_massocsimport_threads') as $thread) {
      if (is_null($thread['rules_id']) || $thread['rules_id'] == '') {
         $rules_id = 0;
      } else {
         $rules_id = $thread['rules_id'];
      }
      $query = "INSERT INTO `glpi_plugin_ocsinventoryng_threads`
                   VALUES ('" . $thread['id'] . "',
                           '" . $thread['threadid'] . "',
                           '" . $thread['start_time'] . "',
                           '" . $thread['end_time'] . "',
                           '" . $thread['status'] . "',
                           '" . $thread['error_msg'] . "',
                           '" . $thread['imported_machines_number'] . "',
                           '" . $thread['synchronized_machines_number'] . "',
                           '" . $thread['failed_rules_machines_number'] . "',
                           '" . $thread['linked_machines_number'] . "',
                           '" . $thread['notupdated_machines_number'] . "',
                           '" . $thread['not_unique_machines_number'] . "',
                           '" . $thread['link_refused_machines_number'] . "',
                           '" . $thread['total_number_machines'] . "',
                           '" . $thread['ocsservers_id'] . "',
                           '" . $thread['processid'] . "',
                           '" . $thread['entities_id'] . "',
                           '" . $rules_id . "');";
      $DB->queryOrDie($query, $DB->error());
   }

   $migration->renameTable("glpi_plugin_massocsimport_threads",
                           "backup_glpi_plugin_massocsimport_threads");

   $migration->changeField("glpi_plugin_ocsinventoryng_threads", "ocsservers_id",
                           "plugin_ocsinventoryng_ocsservers_id", 'integer');

   $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_configs` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `thread_log_frequency` INT(11) NOT NULL DEFAULT '10',
                  `is_displayempty` INT(1) NOT NULL DEFAULT '1',
                  `import_limit` INT(11) NOT NULL DEFAULT '0',
                  `ocsservers_id` INT(11) NOT NULL DEFAULT '-1',
                  `delay_refresh` INT(11) NOT NULL DEFAULT '0',
                  `allow_ocs_update` TINYINT(1) NOT NULL DEFAULT '0',
                  `comment` TEXT,
                  PRIMARY KEY (`id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";

   $DB->query($query) or die($DB->error());

   foreach ($dbu->getAllDataFromTable('glpi_plugin_massocsimport_configs') as $thread) {

      $query = "INSERT INTO `glpi_plugin_ocsinventoryng_configs`
                   VALUES('" . $thread['id'] . "',
                          '" . $thread['thread_log_frequency'] . "',
                          '" . $thread['is_displayempty'] . "',
                          '" . $thread['import_limit'] . "',
                          '" . $thread['ocsservers_id'] . "',
                          '" . $thread['delay_refresh'] . "',
                          '" . $thread['allow_ocs_update'] . "',
                          '" . $thread['comment'] . "');";
      $DB->queryOrDie($query, $DB->error());
   }

   $migration->renameTable("glpi_plugin_massocsimport_configs",
                           "backup_glpi_plugin_massocsimport_configs");

   $migration->dropField("glpi_plugin_ocsinventoryng_configs", "ocsservers_id");

   $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_details` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `entities_id` INT(11) NOT NULL DEFAULT '0',
                  `plugin_massocsimport_threads_id` INT(11) NOT NULL DEFAULT '0',
                  `rules_id` TEXT,
                  `threadid` INT(11) NOT NULL DEFAULT '0',
                  `ocsid` INT(11) NOT NULL DEFAULT '0',
                  `computers_id` INT(11) NOT NULL DEFAULT '0',
                  `action` INT(11) NOT NULL DEFAULT '0',
                  `process_time` DATETIME DEFAULT NULL,
                  `ocsservers_id` INT(11) NOT NULL DEFAULT '1',
                  PRIMARY KEY (`id`),
                  KEY `end_time` (`process_time`),
                  KEY `process_thread` (`ocsservers_id`,`threadid`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

   $DB->queryOrDie($query, $DB->error());

   foreach ($dbu->getAllDataFromTable('glpi_plugin_massocsimport_details') as $thread) {

      $query = "INSERT INTO `glpi_plugin_ocsinventoryng_details`
                   VALUES ('" . $thread['id'] . "',
                           '" . $thread['entities_id'] . "',
                           '" . $thread['plugin_massocsimport_threads_id'] . "',
                           '" . $thread['rules_id'] . "',
                           '" . $thread['threadid'] . "',
                           '" . $thread['ocsid'] . "',
                           '" . $thread['computers_id'] . "',
                           '" . $thread['action'] . "',
                           '" . $thread['process_time'] . "',
                           '" . $thread['ocsservers_id'] . "');";
      $DB->query($query) or die($DB->error());
   }

   $migration->renameTable("glpi_plugin_massocsimport_details",
                           "backup_glpi_plugin_massocsimport_details");

   $migration->changeField("glpi_plugin_ocsinventoryng_details",
                           "plugin_massocsimport_threads_id", "plugin_ocsinventoryng_threads_id",
                           'integer');

   $migration->changeField("glpi_plugin_ocsinventoryng_details", "ocsservers_id",
                           "plugin_ocsinventoryng_ocsservers_id", 'integer');

   $query = "UPDATE `glpi_displaypreferences`
                SET `itemtype` = 'PluginOcsinventoryngNotimportedcomputer'
                WHERE `itemtype` = 'PluginMassocsimportNotimported'";

   $DB->queryOrDie($query, $DB->error());

   $query = "UPDATE `glpi_displaypreferences`
                SET `itemtype` = 'PluginOcsinventoryngDetail'
                WHERE `itemtype` = 'PluginMassocsimportDetail';";

   $DB->queryOrDie($query, $DB->error());

   $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_notimportedcomputers` (
                  `id` INT( 11 ) NOT NULL  AUTO_INCREMENT,
                  `entities_id` INT(11) NOT NULL DEFAULT '0',
                  `rules_id` TEXT,
                  `comment` TEXT NULL,
                  `ocsid` INT( 11 ) NOT NULL DEFAULT '0',
                  `ocsservers_id` INT( 11 ) NOT NULL ,
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
                  UNIQUE KEY `ocs_id` (`ocsservers_id`,`ocsid`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";

   $DB->queryOrDie($query, $DB->error());

   if ($DB->tableExists("glpi_plugin_massocsimport_notimported")) {
      foreach ($dbu->getAllDataFromTable('glpi_plugin_massocsimport_notimported') as $thread) {

         $query = "INSERT INTO `glpi_plugin_ocsinventoryng_notimportedcomputers`
                      VALUES ('" . $thread['id'] . "', '" . $thread['entities_id'] . "',
                              '" . $thread['rules_id'] . "', '" . $thread['comment'] . "',
                              '" . $thread['ocsid'] . "', '" . $thread['ocsservers_id'] . "',
                              '" . $thread['ocs_deviceid'] . "', '" . $thread['useragent'] . "',
                              '" . $thread['tag'] . "', '" . $thread['serial'] . "', '" . $thread['name'] . "',
                              '" . $thread['ipaddr'] . "', '" . $thread['domain'] . "',
                              '" . $thread['last_inventory'] . "', '" . $thread['reason'] . "')";
         $DB->queryOrDie($query, $DB->error());
      }

      $migration->renameTable("glpi_plugin_massocsimport_notimported",
                              "backup_glpi_plugin_massocsimport_notimported");
   }

   $migration->changeField("glpi_plugin_ocsinventoryng_notimportedcomputers", "ocsservers_id",
                           "plugin_ocsinventoryng_ocsservers_id", 'integer');

   $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_servers` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `ocsservers_id` INT(11) NOT NULL DEFAULT '0',
                  `max_ocsid` INT(11) DEFAULT NULL,
                  `max_glpidate` DATETIME DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `ocsservers_id` (`ocsservers_id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";

   $DB->query($query) or die($DB->error());

   foreach ($dbu->getAllDataFromTable('glpi_plugin_massocsimport_servers') as $thread) {

      $query = "INSERT INTO `glpi_plugin_ocsinventoryng_servers`
                          (`id` ,`ocsservers_id` ,`max_ocsid` ,`max_glpidate`)
                   VALUES ('" . $thread['id'] . "',
                           '" . $thread['ocsservers_id'] . "',
                           '" . $thread['max_ocsid'] . "',
                           '" . $thread['max_glpidate'] . "');";
      $DB->queryOrDie($query, $DB->error());
   }

   $migration->renameTable("glpi_plugin_massocsimport_servers",
                           "backup_glpi_plugin_massocsimport_servers");

   $migration->changeField("glpi_plugin_ocsinventoryng_servers", "ocsservers_id",
                           "plugin_ocsinventoryng_ocsservers_id", 'integer');

   $query = "UPDATE `glpi_notificationtemplates`
                SET `itemtype` = 'PluginOcsinventoryngNotimportedcomputer'
                WHERE `itemtype` = 'PluginMassocsimportNotimported'";

   $DB->queryOrDie($query, $DB->error());

   $query = "UPDATE `glpi_notifications`
                SET `itemtype` = 'PluginOcsinventoryngNotimportedcomputer'
                WHERE `itemtype` = 'PluginMassocsimportNotimported'";

   $DB->queryOrDie($query, $DB->error());

   $query = "UPDATE `glpi_crontasks`
                SET `itemtype` = 'PluginOcsinventoryngThread'
                WHERE `itemtype` = 'PluginMassocsimportThread';";
   $DB->queryOrDie($query, $DB->error());

   $query = "UPDATE `glpi_alerts`
                SET `itemtype` = 'PluginOcsinventoryngNotimportedcomputer'
                WHERE `itemtype` IN ('PluginMassocsimportNotimported')";

   $DB->queryOrDie($query, $DB->error());
}

function plugin_ocsinventoryng_upgrademassocsimport14to15() {
   global $DB;

   $dbu       = new DbUtils();
   $migration = new Migration(15);

   $migration->addField("glpi_plugin_massocsimport_threads", "not_unique_machines_number",
                        'integer');
   $migration->addField("glpi_plugin_massocsimport_threads", "link_refused_machines_number",
                        'integer');
   $migration->addField("glpi_plugin_massocsimport_threads", "entities_id", 'integer');
   $migration->addField("glpi_plugin_massocsimport_threads", "rules_id", 'text');

   $migration->addField("glpi_plugin_massocsimport_configs", "allow_ocs_update", 'bool');

   $migration->addField("glpi_plugin_massocsimport_notimported", "reason", 'integer');

   if (!$dbu->countElementsInTable('glpi_displaypreferences',
                                   ["itemtype" => 'PluginMassocsimportNotimported',
                                    "num"      => 10,
                                    "users_id" => 0])
   ) {
      $query = "INSERT INTO `glpi_displaypreferences`
                (`itemtype`, `num`, `rank`, `users_id`)
                VALUES ('PluginMassocsimportNotimported', 10, 9, 0)";
      $DB->queryOrDie($query, "1.5 insert into glpi_displaypreferences " . $DB->error());
   }

   $migration->addField("glpi_plugin_massocsimport_notimported", "serial", 'string',
                        ['value' => '']);
   $migration->addField("glpi_plugin_massocsimport_notimported", "comment", "TEXT NOT NULL");
   $migration->addField("glpi_plugin_massocsimport_notimported", "rules_id", 'text');
   $migration->addField("glpi_plugin_massocsimport_notimported", "entities_id", 'integer');

   $migration->addField("glpi_plugin_massocsimport_details", "entities_id", 'integer');
   $migration->addField("glpi_plugin_massocsimport_details", "rules_id", 'text');

   $query  = "SELECT id " .
             "FROM `glpi_notificationtemplates` " .
             "WHERE `itemtype`='PluginMassocsimportNotimported'";
   $result = $DB->query($query);
   if (!$DB->numrows($result)) {

      //Add template
      $query = "INSERT INTO `glpi_notificationtemplates` " .
               "VALUES (NULL, 'Computers not imported', 'PluginMassocsimportNotimported',
                        NOW(), '', '', NOW());";
      $DB->queryOrDie($query, $DB->error());
      $templates_id = $DB->insertId();
      $query        = "INSERT INTO `glpi_notificationtemplatetranslations` " .
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
      $DB->queryOrDie($query, $DB->error());

      $query = "INSERT INTO `glpi_notifications` (`name`, `entities_id`, `itemtype`, `event`, `is_recursive`, `is_active`)
                VALUES ('Computers not imported', 0, 'PluginMassocsimportNotimported', 'not_imported', 1, 1);";
      $DB->queryOrDie($query, $DB->error());

      $query_id = "SELECT `id` FROM `glpi_notifications`
               WHERE `name` = 'Computers not imported' AND `itemtype` = 'PluginMassocsimportNotimported' AND `event` = 'not_imported'";
      $result = $DB->query($query_id) or die ($DB->error());
      $notification = $DB->result($result, 0, 'id');

      $query = "INSERT INTO `glpi_notifications_notificationtemplates` (`notifications_id`, `mode`, `notificationtemplates_id`) 
               VALUES (" . $notification . ", 'mailing', " . $templates_id . ");";
      $DB->query($query);
   }
   $migration->executeMigration();
}

function plugin_ocsinventoryng_migration_additionnalalerts() {
   global $DB;

   $query = "ALTER TABLE `glpi_plugin_ocsinventoryng_configs` 
            ADD `delay_ocs` int(11) NOT NULL default '-1',
            ADD `use_newocs_alert` TINYINT( 1 ) NOT NULL DEFAULT '-1';";
   $DB->query($query);

   $query = "CREATE TABLE `glpi_plugin_ocsinventoryng_ocsalerts` (
              `id` int(11) NOT NULL auto_increment,
              `entities_id` int(11) NOT NULL default '0',
              `delay_ocs` int(11) NOT NULL default '-1',
              `use_newocs_alert` TINYINT( 1 ) NOT NULL DEFAULT '-1',
              PRIMARY KEY  (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
   $DB->queryOrDie($query, "1.5.5 add glpi_plugin_ocsinventoryng_ocsalerts");

   $query = "CREATE TABLE `glpi_plugin_ocsinventoryng_notificationstates` (
           `id` int(11) NOT NULL auto_increment,
           `states_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_states (id)',
           PRIMARY KEY  (`id`)
         ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
   $DB->queryOrDie($query, "1.5.5 add glpi_plugin_ocsinventoryng_notificationstates");

   //if plugin additionalalerts
   if ($DB->tableExists('glpi_plugin_additionalalerts_ocsalerts')) {
      $query = "UPDATE `glpi_plugin_ocsinventoryng_configs` 
                INNER JOIN `glpi_plugin_additionalalerts_configs` 
                ON `glpi_plugin_additionalalerts_configs`.`id` = `glpi_plugin_ocsinventoryng_configs`.`id`
                SET `glpi_plugin_ocsinventoryng_configs`.`delay_ocs` = `glpi_plugin_additionalalerts_configs`.`delay_ocs`,
                `glpi_plugin_ocsinventoryng_configs`.`use_newocs_alert` = `glpi_plugin_additionalalerts_configs`.`use_newocs_alert`";
      $DB->query($query);

      $query = "INSERT INTO `glpi_plugin_ocsinventoryng_ocsalerts`
                SELECT * FROM `glpi_plugin_additionalalerts_ocsalerts`";
      $DB->queryOrDie($query, "1.5.5 migration glpi_plugin_ocsinventoryng_ocsalerts");

      $query = "INSERT INTO `glpi_plugin_ocsinventoryng_notificationstates`
                SELECT * FROM `glpi_plugin_additionalalerts_notificationstates`";
      $DB->queryOrDie($query, "1.5.5 migration glpi_plugin_ocsinventoryng_notificationstates");

      //disabled cron
      $query = "INSERT INTO `glpi_crontasks`
                SELECT NULL, 'PluginOcsinventoryngOcsAlert', 'AlertNewComputers', `frequency`, `param`, 
                `state`, `mode`, `allowmode`, `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, 
                `comment`, `date_mod`, `date_creation`
                 FROM `glpi_crontasks`
                WHERE `name` LIKE 'AdditionalalertsNewOcs'";
      $DB->query($query);

      $query = "UPDATE `glpi_crontasks` SET `state` = 0
                WHERE `name` LIKE 'AdditionalalertsNewOcs'";
      $DB->query($query);

      $query = "INSERT INTO `glpi_crontasks`
                SELECT NULL, 'PluginOcsinventoryngOcsAlert', 'SynchroAlert', `frequency`, `param`, `state`, 
                `mode`, `allowmode`, `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, 
                `comment`, `date_mod`, `date_creation`
                 FROM `glpi_crontasks`
                WHERE `name` LIKE 'AdditionalalertsOcs'";
      $DB->query($query);

      $query = "UPDATE `glpi_crontasks` SET `state` = 0
                WHERE `name` LIKE 'AdditionalalertsOcs'";
      $DB->query($query);


      //migration notif
      $query = "UPDATE `glpi_notifications` SET `itemtype` = 'PluginOcsinventoryngOcsAlert' 
                WHERE `itemtype` LIKE 'PluginAdditionalalertsOcsAlert'";
      $DB->query($query);

      //notification_template
      $query = "UPDATE `glpi_notificationtemplates` SET `itemtype` = 'PluginOcsinventoryngOcsAlert' 
                WHERE `itemtype` LIKE 'PluginAdditionalalertsOcsAlert'";
      $DB->query($query);

   }
}

function plugin_ocsinventoryng_add_notifications_alerts() {
   global $DB;

   //add alert synchro & new computer AlertNewComputers & SynchroAlert
   $query_id = "SELECT `id` 
                FROM `glpi_notificationtemplates` 
                WHERE `itemtype`='PluginOcsinventoryngOcsAlert'";
   $result   = $DB->query($query_id);

   if (!$DB->numrows($result)) {

      //Add template
      $query = "INSERT INTO `glpi_notificationtemplates`
             (`name`, `itemtype`)
             VALUES ('Alert machines ocs', 'PluginOcsinventoryngOcsAlert');";
      $DB->queryOrDie($query, $DB->error());
      $templates_id = $DB->insertId();

      $query = "INSERT INTO `glpi_notificationtemplatetranslations`
                (`notificationtemplates_id`, `subject`, `content_text`, `content_html`)
                                 VALUES($templates_id, '##lang.ocsmachine.title## : ##ocsmachine.entity##',
                        '##FOREACHocsmachines##
   ##lang.ocsmachine.name## : ##ocsmachine.name##
   ##lang.ocsmachine.operatingsystem## : ##ocsmachine.operatingsystem##
   ##lang.ocsmachine.state## : ##ocsmachine.state##
   ##lang.ocsmachine.location## : ##ocsmachine.location##
   ##lang.ocsmachine.user## : ##ocsmachine.user## / ##lang.ocsmachine.group## : ##ocsmachine.group## / ##lang.ocsmachine.contact## : ##ocsmachine.contact##
   ##lang.ocsmachine.lastocsupdate## : ##ocsmachine.lastocsupdate##
   ##lang.ocsmachine.lastupdate## : ##ocsmachine.lastupdate##
   ##lang.ocsmachine.ocsserver## : ##ocsmachine.ocsserver##
   ##ENDFOREACHocsmachines##',
                        '&lt;table class=\"tab_cadre\" border=\"1\" cellspacing=\"2\" cellpadding=\"3\"&gt;
   &lt;tbody&gt;
   &lt;tr&gt;
   &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ocsmachine.name##&lt;/span&gt;&lt;/td&gt;
   &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ocsmachine.operatingsystem##&lt;/span&gt;&lt;/td&gt;
   &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ocsmachine.state##&lt;/span&gt;&lt;/td&gt;
   &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ocsmachine.location##&lt;/span&gt;&lt;/td&gt;
   &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ocsmachine.user##&lt;/span&gt;&lt;/td&gt;
   &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ocsmachine.lastocsupdate##&lt;/span&gt;&lt;/td&gt;
   &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ocsmachine.lastupdate##&lt;/span&gt;&lt;/td&gt;
   &lt;td style=\"text-align: left;\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##lang.ocsmachine.ocsserver##&lt;/span&gt;&lt;/td&gt;
   &lt;/tr&gt;
   ##FOREACHocsmachines##
   &lt;tr&gt;
   &lt;td&gt;&lt;a href=\"##ocsmachine.urlname##\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ocsmachine.name##&lt;/span&gt;&lt;/a&gt;&lt;/td&gt;
   &lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ocsmachine.operatingsystem##&lt;/span&gt;&lt;/td&gt;
   &lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ocsmachine.state##&lt;/span&gt;&lt;/td&gt;
   &lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ocsmachine.location##&lt;/span&gt;&lt;/td&gt;
   &lt;td&gt;##IFocsmachine.user##&lt;a href=\"##ocsmachine.urluser##\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ocsmachine.user##&lt;/span&gt;&lt;/a&gt; / ##ENDIFocsmachine.user####IFocsmachine.group##&lt;a href=\"##ocsmachine.urlgroup##\"&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ocsmachine.group##&lt;/span&gt;&lt;/a&gt; / ##ENDIFocsmachine.group####IFocsmachine.contact##&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ocsmachine.contact####ENDIFocsmachine.contact##&lt;/span&gt;&lt;/td&gt;
   &lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ocsmachine.lastocsupdate##&lt;/span&gt;&lt;/td&gt;
   &lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ocsmachine.lastupdate##&lt;/span&gt;&lt;/td&gt;
   &lt;td&gt;&lt;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&gt;##ocsmachine.ocsserver##&lt;/span&gt;&lt;/td&gt;
   &lt;/tr&gt;
   ##ENDFOREACHocsmachines##
   &lt;/tbody&gt;
   &lt;/table&gt;');";
      $DB->query($query);

      $query = "INSERT INTO `glpi_notifications` (`name`, `entities_id`, `itemtype`, `event`, `is_recursive`, `is_active`) 
                VALUES ('Alert new machines ocs', 0, 'PluginOcsinventoryngOcsAlert', 'newocs', 1, 1);";
      $DB->query($query);

      //retrieve notification id
      $query_id = "SELECT `id` FROM `glpi_notifications`
               WHERE `name` = 'Alert new machines ocs' AND `itemtype` = 'PluginOcsinventoryngOcsAlert' AND `event` = 'newocs'";
      $result = $DB->query($query_id) or die ($DB->error());
      $notification = $DB->result($result, 0, 'id');

      $query = "INSERT INTO `glpi_notifications_notificationtemplates` (`notifications_id`, `mode`, `notificationtemplates_id`) 
               VALUES (" . $notification . ", 'mailing', " . $templates_id . ");";
      $DB->query($query);

      $query = "INSERT INTO `glpi_notifications` (`name`, `entities_id`, `itemtype`, `event`, `is_recursive`, `is_active`) 
                  VALUES ('Alert ocs synchronization', 0, 'PluginOcsinventoryngOcsAlert', 'ocs', 1, 1);";
      $DB->query($query);

      //retrieve notification id
      $query_id = "SELECT `id` FROM `glpi_notifications`
               WHERE `name` = 'Alert ocs synchronization' AND `itemtype` = 'PluginOcsinventoryngOcsAlert' AND `event` = 'ocs'";
      $result = $DB->query($query_id) or die ($DB->error());
      $notification = $DB->result($result, 0, 'id');

      $query = "INSERT INTO `glpi_notifications_notificationtemplates` (`notifications_id`, `mode`, `notificationtemplates_id`) 
               VALUES (" . $notification . ", 'mailing', " . $templates_id . ");";
      $DB->query($query);
   }
}
