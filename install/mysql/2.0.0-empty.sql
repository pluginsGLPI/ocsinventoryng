### Dump table glpi_plugin_ocsinventoryng_ocsadmininfoslinks

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_ocsadmininfoslinks`;
CREATE TABLE `glpi_plugin_ocsinventoryng_ocsadmininfoslinks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `glpi_column` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ocs_column` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plugin_ocsinventoryng_ocsservers_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `plugin_ocsinventoryng_ocsservers_id` (`plugin_ocsinventoryng_ocsservers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


### Dump table glpi_plugin_ocsinventoryng_ocslinks

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_ocslinks`;
CREATE TABLE `glpi_plugin_ocsinventoryng_ocslinks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id` int unsigned NOT NULL DEFAULT '0',
  `ocsid` int unsigned NOT NULL DEFAULT '0',
  `ocs_deviceid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `use_auto_update` tinyint NOT NULL DEFAULT '1',
  `last_update` timestamp NULL DEFAULT NULL,
  `last_ocs_update` timestamp NULL DEFAULT NULL,
  `last_ocs_conn` timestamp NULL DEFAULT NULL,
  `ip_src` varchar(255) collate utf8mb4_unicode_ci default NULL,
  `computer_update` longtext COLLATE utf8mb4_unicode_ci,
  `plugin_ocsinventoryng_ocsservers_id` int unsigned NOT NULL DEFAULT '0',
  `ocs_agent_version` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `tag` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uptime` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_ocsinventoryng_ocsservers_id`,`ocsid`),
  KEY `last_update` (`last_update`),
  KEY `ocs_deviceid` (`ocs_deviceid`),
  KEY `last_ocs_update` (`plugin_ocsinventoryng_ocsservers_id`,`last_ocs_update`),
  KEY `computers_id` (`computers_id`),
  KEY `entities_id` (`entities_id`),
  KEY `use_auto_update` (`use_auto_update`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


### Dump table glpi_plugin_ocsinventoryng_ocsservers

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_ocsservers`;
CREATE TABLE `glpi_plugin_ocsinventoryng_ocsservers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ocs_db_user` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ocs_db_passwd` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ocs_db_host` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ocs_db_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ocs_db_utf8` tinyint NOT NULL DEFAULT '0',
  `checksum` int unsigned NOT NULL DEFAULT '0',
  `import_periph` tinyint NOT NULL DEFAULT '0',
  `import_monitor` tinyint NOT NULL DEFAULT '0',
  `import_software` tinyint NOT NULL DEFAULT '0',
  `import_printer` tinyint NOT NULL DEFAULT '0',
  `import_general_name` tinyint NOT NULL DEFAULT '1',
  `import_general_os` tinyint NOT NULL DEFAULT '1',
  `import_general_serial` tinyint NOT NULL DEFAULT '1',
  `import_general_model` tinyint NOT NULL DEFAULT '1',
  `import_general_manufacturer` tinyint NOT NULL DEFAULT '1',
  `import_general_type` tinyint NOT NULL DEFAULT '1',
  `import_general_domain` tinyint NOT NULL DEFAULT '1',
  `import_general_contact` tinyint NOT NULL DEFAULT '1',
  `link_with_user` tinyint NOT NULL DEFAULT '1',
  `import_user_group_default` tinyint NOT NULL DEFAULT '1',
  `import_user_location` tinyint NOT NULL DEFAULT '1',
  `import_user_group` tinyint NOT NULL DEFAULT '1',
  `import_general_comment` tinyint NOT NULL DEFAULT '1',
  `import_device_processor` tinyint NOT NULL DEFAULT '1',
  `import_device_memory` tinyint NOT NULL DEFAULT '1',
  `import_device_hdd` tinyint NOT NULL DEFAULT '1',
  `import_device_iface` tinyint NOT NULL DEFAULT '1',
  `import_device_gfxcard` tinyint NOT NULL DEFAULT '1',
  `import_device_sound` tinyint NOT NULL DEFAULT '1',
  `import_device_drive` tinyint NOT NULL DEFAULT '1',
  `import_device_port` tinyint NOT NULL DEFAULT '1',
  `import_device_modem` tinyint NOT NULL DEFAULT '1',
  `import_device_bios` tinyint NOT NULL DEFAULT '1',
  `import_device_motherboard` tinyint NOT NULL DEFAULT '1',
  `import_registry` tinyint NOT NULL DEFAULT '1',
  `import_antivirus` tinyint NOT NULL DEFAULT '0',
  `import_officepack` tinyint NOT NULL DEFAULT '0',
  `import_winupdatestate` tinyint NOT NULL DEFAULT '0',
  `import_proxysetting` tinyint NOT NULL DEFAULT '0',
  `import_winusers` tinyint NOT NULL DEFAULT '0',
  `import_teamviewer` tinyint NOT NULL DEFAULT '0',
  `import_osinstall` tinyint NOT NULL DEFAULT '0',
  `import_networkshare` tinyint NOT NULL DEFAULT '0',
  `import_os_serial` tinyint NOT NULL DEFAULT '1',
  `import_customapp` tinyint NOT NULL DEFAULT '0',
  `import_bitlocker` tinyint NOT NULL DEFAULT '0',
  `import_ip` tinyint NOT NULL DEFAULT '1',
  `import_disk` tinyint NOT NULL DEFAULT '1',
  `import_monitor_comment` tinyint NOT NULL DEFAULT '0',
  `tag_limit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tag_exclude` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `use_soft_dict` tinyint NOT NULL DEFAULT '0',
  `cron_sync_number` int unsigned DEFAULT '1',
  `deconnection_behavior` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ocs_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint NOT NULL DEFAULT '0',
  `use_massimport` tinyint NOT NULL DEFAULT '0',
  `use_locks` tinyint NOT NULL DEFAULT '1',
  `deleted_behavior` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `import_vms` tinyint NOT NULL DEFAULT '1',
  `import_general_uuid` tinyint NOT NULL DEFAULT '1',
  `import_device_controller` tinyint NOT NULL DEFAULT '1',
  `import_device_slot` tinyint NOT NULL DEFAULT '1',
  `ocs_version` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conn_type` tinyint NOT NULL DEFAULT '0',
  `use_cleancron` tinyint NOT NULL DEFAULT '0',
  `cleancron_nb_days` int unsigned NOT NULL DEFAULT '90',
  `action_cleancron` tinyint NOT NULL DEFAULT '0',
  `use_restorationcron` tinyint NOT NULL DEFAULT '0',
  `delay_restorationcron` int unsigned NOT NULL DEFAULT '0',
  `use_checkruleimportentity` tinyint NOT NULL DEFAULT '0',
  `importsnmp_name` tinyint NOT NULL DEFAULT '0',
  `importsnmp_serial` tinyint NOT NULL DEFAULT '0',
  `importsnmp_comment` tinyint NOT NULL DEFAULT '0',
  `importsnmp_contact` tinyint NOT NULL DEFAULT '0',
  `importsnmp_location` tinyint NOT NULL DEFAULT '0',
  `importsnmp_domain` tinyint NOT NULL DEFAULT '0',
  `importsnmp_manufacturer` tinyint NOT NULL DEFAULT '0',
  `importsnmp_createport` tinyint NOT NULL DEFAULT '0',
  `importsnmp_last_pages_counter` tinyint NOT NULL DEFAULT '0',
  `importsnmp_firmware` tinyint NOT NULL DEFAULT '0',
  `importsnmp_power` tinyint NOT NULL DEFAULT '0',
  `importsnmp_fan` tinyint NOT NULL DEFAULT '0',
  `importsnmp_printermemory` tinyint NOT NULL DEFAULT '0',
  `importsnmp_computernetworkcards` tinyint NOT NULL DEFAULT '0',
  `importsnmp_computermemory` tinyint NOT NULL DEFAULT '0',
  `importsnmp_computerprocessors` tinyint NOT NULL DEFAULT '0',
  `importsnmp_computersoftwares` tinyint NOT NULL DEFAULT '0',
  `importsnmp_computervm` tinyint NOT NULL DEFAULT '0',
  `importsnmp_computerdisks` tinyint NOT NULL DEFAULT '0',
  `import_runningprocess` tinyint NOT NULL DEFAULT '0',
  `import_service` tinyint NOT NULL DEFAULT '0',
  `import_uptime` tinyint NOT NULL DEFAULT '0',
  `linksnmp_name` tinyint NOT NULL DEFAULT '0',
  `linksnmp_serial` tinyint NOT NULL DEFAULT '0',
  `linksnmp_comment` tinyint NOT NULL DEFAULT '0',
  `linksnmp_contact` tinyint NOT NULL DEFAULT '0',
  `linksnmp_location` tinyint NOT NULL DEFAULT '0',
  `linksnmp_domain` tinyint NOT NULL DEFAULT '0',
  `linksnmp_manufacturer` tinyint NOT NULL DEFAULT '0',
  `linksnmp_createport` tinyint NOT NULL DEFAULT '0',
  `linksnmp_last_pages_counter` tinyint NOT NULL DEFAULT '0',
  `linksnmp_firmware` tinyint NOT NULL DEFAULT '0',
  `linksnmp_power` tinyint NOT NULL DEFAULT '0',
  `linksnmp_fan` tinyint NOT NULL DEFAULT '0',
  `linksnmp_printermemory` tinyint NOT NULL DEFAULT '0',
  `linksnmp_computernetworkcards` tinyint NOT NULL DEFAULT '0',
  `linksnmp_computermemory` tinyint NOT NULL DEFAULT '0',
  `linksnmp_computerprocessors` tinyint NOT NULL DEFAULT '0',
  `linksnmp_computersoftwares` tinyint NOT NULL DEFAULT '0',
  `linksnmp_computervm` tinyint NOT NULL DEFAULT '0',
  `linksnmp_computerdisks` tinyint NOT NULL DEFAULT '0',
  `dohistory` tinyint NOT NULL DEFAULT '1',
  `history_hardware` tinyint NOT NULL DEFAULT '1',
  `history_bios` tinyint NOT NULL DEFAULT '1',
  `history_drives` tinyint NOT NULL DEFAULT '1',
  `history_network` tinyint NOT NULL DEFAULT '1',
  `history_devices` tinyint NOT NULL DEFAULT '1',
  `history_monitor` tinyint NOT NULL DEFAULT '1',
  `history_printer` tinyint NOT NULL DEFAULT '1',
  `history_peripheral` tinyint NOT NULL DEFAULT '1',
  `history_software` tinyint NOT NULL DEFAULT '1',
  `history_vm` tinyint NOT NULL DEFAULT '1',
  `history_admininfos` tinyint NOT NULL DEFAULT '1',
  `history_plugins` tinyint NOT NULL DEFAULT '1',
  `history_os` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `is_active` (`is_active`),
  KEY `use_massimport` (`use_massimport`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

### Dump table glpi_plugin_ocsinventoryng_registrykeys

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_registrykeys`;
CREATE TABLE `glpi_plugin_ocsinventoryng_registrykeys` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id` int unsigned NOT NULL DEFAULT '0',
  `hive` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ocs_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

### Dump table glpi_plugin_ocsinventoryng_computerwinupdates

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_winupdates`;
CREATE TABLE `glpi_plugin_ocsinventoryng_winupdates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id` int unsigned NOT NULL DEFAULT '0',
  `auoptions` int unsigned NOT NULL DEFAULT '0',
  `scheduleinstalldate` timestamp NULL DEFAULT NULL,
  `lastsuccesstime` timestamp NULL DEFAULT NULL,
  `detectsuccesstime` timestamp NULL DEFAULT NULL,
  `downloadsuccesstime` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

### Dump table glpi_plugin_ocsinventoryng_osinstalls

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_osinstalls`;
CREATE TABLE `glpi_plugin_ocsinventoryng_osinstalls` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id` int unsigned NOT NULL DEFAULT '0',
  `build_version` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `install_date` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codeset` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `countrycode` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `oslanguage` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `curtimezone` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `locale` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

### Dump table glpi_plugin_ocsinventoryng_proxysettings

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_proxysettings`;
CREATE TABLE `glpi_plugin_ocsinventoryng_proxysettings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id` int unsigned NOT NULL DEFAULT '0',
  `user` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enabled` int unsigned NOT NULL DEFAULT '0',
  `autoconfigurl` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `override` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

### Dump table glpi_plugin_ocsinventoryng_networkshares

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_networkshares`;
CREATE TABLE `glpi_plugin_ocsinventoryng_networkshares` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id` int unsigned NOT NULL DEFAULT '0',
  `drive` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `path` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `freespace` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quota` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

### Dump table glpi_plugin_ocsinventoryng_winusers

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_winusers`;
CREATE TABLE `glpi_plugin_ocsinventoryng_winusers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id` int unsigned NOT NULL DEFAULT '0',
  `name` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disabled` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sid` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

### Dump table glpi_plugin_ocsinventoryng_teamviewers
DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_teamviewers`;
CREATE TABLE `glpi_plugin_ocsinventoryng_teamviewers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id` int unsigned NOT NULL DEFAULT '0',
  `twid` varchar(255) DEFAULT NULL,
  `version` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

### Dump table glpi_plugin_ocsinventoryng_networkports
DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_networkports`;
CREATE TABLE `glpi_plugin_ocsinventoryng_networkports` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `networkports_id` int unsigned NOT NULL DEFAULT '0',
  `TYPE` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `TYPEMIB` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `items_devicenetworkcards_id` int unsigned NOT NULL DEFAULT '0',
  `speed` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '10mb/s',
  `comment` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `networkports_id` (`networkports_id`),
  KEY `TYPE` (`TYPE`),
  KEY `TYPEMIB` (`TYPEMIB`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


### Dump table glpi_plugin_ocsinventoryng_networkporttypes

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_networkporttypes`;
CREATE TABLE `glpi_plugin_ocsinventoryng_networkporttypes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `OCS_TYPE` varchar(255) NOT NULL DEFAULT '',
  `OCS_TYPEMIB` varchar(255) NOT NULL DEFAULT '',
  `instantiation_type` varchar(255) DEFAULT NULL,
  `type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'T, LX, SX',
  `speed` int unsigned NULL DEFAULT '10' COMMENT 'Mbit/s: 10, 100, 1000, 10000',
  `version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'a, a/b, a/b/g, a/b/g/n, a/b/g/n/y',
  `comment` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `OCS_TYPE` (`OCS_TYPE`),
  KEY `OCS_TYPEMIB` (`OCS_TYPEMIB`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

### Dump table glpi_plugin_ocsinventoryng_ocsservers_profiles

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_ocsservers_profiles`;
CREATE TABLE `glpi_plugin_ocsinventoryng_ocsservers_profiles` (
  `id` int unsigned NOT NULL auto_increment,
  `plugin_ocsinventoryng_ocsservers_id` int unsigned NOT NULL default '0',
  `profiles_id` int unsigned NOT NULL default '0',
  PRIMARY KEY (`id`),
  KEY `plugin_ocsinventoryng_ocsservers_id` (`plugin_ocsinventoryng_ocsservers_id`),
  KEY `profiles_id` (`profiles_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_threads`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_threads` (
   `id` int unsigned NOT NULL auto_increment,
   `threadid` int unsigned NOT NULL default '0',
   `start_time` timestamp NULL DEFAULT NULL,
   `end_time` timestamp NULL DEFAULT NULL,
   `status` int unsigned NOT NULL default '0',
   `error_msg` text NOT NULL,
   `imported_machines_number` int unsigned NOT NULL default '0',
   `synchronized_machines_number` int unsigned NOT NULL default '0',
   `failed_rules_machines_number` int unsigned NOT NULL default '0',
   `linked_machines_number` int unsigned NOT NULL default '0',
   `notupdated_machines_number` int unsigned NOT NULL default '0',
   `not_unique_machines_number` int unsigned NOT NULL default '0',
   `link_refused_machines_number` int unsigned NOT NULL default '0',
   `total_number_machines` int unsigned NOT NULL default '0',
   `plugin_ocsinventoryng_ocsservers_id` int unsigned NOT NULL default '1',
   `processid` int unsigned NOT NULL default '0',
   `entities_id` int unsigned NOT NULL DEFAULT 0,
   `rules_id` int unsigned NOT NULL DEFAULT 0,
   PRIMARY KEY  (`id`),
   KEY `end_time` (`end_time`),
   KEY `process_thread` (`processid`,`threadid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_configs`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_configs` (
   `id` int unsigned NOT NULL auto_increment,
   `thread_log_frequency` int unsigned NOT NULL default '10',
   `is_displayempty` int unsigned NOT NULL default '1',
   `import_limit` int unsigned NOT NULL default '0',
   `delay_refresh` int unsigned NOT NULL default '0',
   `allow_ocs_update` tinyint NOT NULL default '0',
   `log_imported_computers` tinyint NOT NULL default '0',
   `comment` text,
   `delay_ocs` int NOT NULL default '-1',
   `use_newocs_alert` int NOT NULL default '-1',
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_details`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_details` (
   `id` int unsigned NOT NULL auto_increment,
   `entities_id` int unsigned NOT NULL default '0',
   `plugin_ocsinventoryng_threads_id` int unsigned NOT NULL default '0',
   `rules_id` TEXT,
   `threadid` int unsigned NOT NULL default '0',
   `ocsid` int unsigned NOT NULL default '0',
   `computers_id` int unsigned NOT NULL default '0',
   `action` int unsigned NOT NULL default '0',
   `process_time` timestamp NULL DEFAULT NULL,
   `plugin_ocsinventoryng_ocsservers_id` int unsigned NOT NULL default '1',
   PRIMARY KEY (`id`),
   KEY `end_time` (`process_time`),
   KEY `process_thread` (`plugin_ocsinventoryng_threads_id`,`threadid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_notimportedcomputers`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_notimportedcomputers` (
   `id` int unsigned NOT NULL  auto_increment,
   `entities_id` int unsigned NOT NULL default '0',
   `rules_id` TEXT,
   `comment` text NULL,
   `ocsid` int unsigned NOT NULL DEFAULT '0',
   `plugin_ocsinventoryng_ocsservers_id` int unsigned NOT NULL ,
   `ocs_deviceid` VARCHAR( 255 ) NOT NULL ,
   `useragent` VARCHAR( 255 ) NOT NULL ,
   `tag` VARCHAR( 255 ) NOT NULL ,
   `serial` VARCHAR( 255 ) NOT NULL ,
   `name` VARCHAR( 255 ) NOT NULL ,
   `ipaddr` VARCHAR( 255 ) NOT NULL ,
   `domain` VARCHAR( 255 ) NOT NULL ,
   `last_inventory` timestamp NULL DEFAULT NULL,
   `reason` int unsigned NOT NULL ,
   PRIMARY KEY ( `id` ),
   UNIQUE KEY `ocs_id` (`plugin_ocsinventoryng_ocsservers_id`,`ocsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_servers`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_servers` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `plugin_ocsinventoryng_ocsservers_id` int unsigned NOT NULL DEFAULT '0',
   `max_ocsid` int unsigned DEFAULT NULL,
   `max_glpidate` timestamp NULL DEFAULT NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY `plugin_ocsinventoryng_ocsservers_id` (`plugin_ocsinventoryng_ocsservers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

### Dump table glpi_plugin_ocsinventoryng_snmpocslinks

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_snmpocslinks`;
CREATE TABLE `glpi_plugin_ocsinventoryng_snmpocslinks` (
   `id` int unsigned NOT NULL AUTO_INCREMENT,
   `items_id` int unsigned NOT NULL DEFAULT '0',
   `ocs_id` int unsigned NOT NULL DEFAULT '0',
   `itemtype` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
   `last_update` timestamp NULL DEFAULT NULL,
   `plugin_ocsinventoryng_ocsservers_id` int unsigned NOT NULL DEFAULT '0',
   `linked` tinyint NOT NULL DEFAULT '0',
   PRIMARY KEY (`id`),
  KEY `plugin_ocsinventoryng_ocsservers_id` (`plugin_ocsinventoryng_ocsservers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

### Dump table glpi_plugin_ocsinventoryng_ipdiscoverlinks

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`;
CREATE TABLE `glpi_plugin_ocsinventoryng_ipdiscoverocslinks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `items_id` int unsigned NOT NULL,
  `itemtype` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `macaddress` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
  `last_update` timestamp NULL DEFAULT NULL,
  `subnet` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plugin_ocsinventoryng_ocsservers_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `plugin_ocsinventoryng_ocsservers_id` (`plugin_ocsinventoryng_ocsservers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

### Dump table glpi_plugin_ocsinventoryng_ruleimportentities

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_ruleimportentities`;
CREATE TABLE `glpi_plugin_ocsinventoryng_ruleimportentities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_runningprocesses`;
CREATE TABLE `glpi_plugin_ocsinventoryng_runningprocesses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id` int unsigned NOT NULL DEFAULT '0',
  `cpuusage` VARCHAR(255) DEFAULT NULL,
  `tty` VARCHAR(255) DEFAULT NULL,
  `started` VARCHAR(15) DEFAULT NULL,
  `virtualmemory` VARCHAR(255) DEFAULT NULL,
  `processname` VARCHAR(255) DEFAULT NULL,
  `processid` VARCHAR(255) DEFAULT NULL,
  `username` VARCHAR(255) DEFAULT NULL,
  `processmemory` VARCHAR(255) DEFAULT NULL,
  `commandline` VARCHAR(255) DEFAULT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `company` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_services`;
CREATE TABLE `glpi_plugin_ocsinventoryng_services` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id` int unsigned NOT NULL DEFAULT '0',
  `svcname` VARCHAR(128) NOT NULL,
  `svcdn` VARCHAR(255) NOT NULL,
  `svcstate` VARCHAR(32) DEFAULT NULL,
  `svcdesc` VARCHAR(1536) DEFAULT NULL,
  `svcstartmode` VARCHAR(32) DEFAULT NULL,
  `svcpath` VARCHAR(512) DEFAULT NULL,
  `svcstartname` VARCHAR(128) DEFAULT NULL,
  `svcexitcode` INTEGER DEFAULT NULL,
  `svcspecexitcode` INTEGER DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_ocsalerts`;
CREATE TABLE `glpi_plugin_ocsinventoryng_ocsalerts` (
  `id` int unsigned NOT NULL auto_increment,
  `entities_id` int unsigned NOT NULL default '0',
  `delay_ocs` int NOT NULL default '-1',
  `use_newocs_alert` int NOT NULL default '-1',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_notificationstates`;
CREATE TABLE `glpi_plugin_ocsinventoryng_notificationstates` (
  `id` int unsigned NOT NULL auto_increment,
  `states_id` int unsigned NOT NULL default '0' COMMENT 'RELATION to glpi_states (id)',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

### Dump table glpi_plugin_ocsinventoryng_customapps
DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_customapps`;
CREATE TABLE `glpi_plugin_ocsinventoryng_customapps` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id` int unsigned NOT NULL DEFAULT '0',
  `path` varchar(255) DEFAULT NULL,
  `text` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

### Dump table glpi_plugin_ocsinventoryng_bitlockerstatuses
DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_bitlockerstatuses`;
CREATE TABLE `glpi_plugin_ocsinventoryng_bitlockerstatuses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id` int unsigned NOT NULL DEFAULT '0',
  `item_disks_id` int unsigned NOT NULL DEFAULT '0',
  `volume_type` varchar(255) DEFAULT NULL,
  `protection_status` varchar(255) DEFAULT NULL,
  `init_project` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


INSERT INTO `glpi_plugin_ocsinventoryng_configs`(`id`,`thread_log_frequency`,`is_displayempty`,`import_limit`, `allow_ocs_update`, `delay_refresh`) VALUES (1, 2, 1, 0, 1, 5);

INSERT INTO `glpi_plugin_ocsinventoryng_networkporttypes` VALUES (NULL, 'Unkown port', '*', '*', 'PluginOcsinventoryngNetworkPort', NULL, NULL,NULL, NULL);
INSERT INTO `glpi_plugin_ocsinventoryng_networkporttypes` VALUES (NULL, 'Ethernet port', 'Ethernet', '*', 'NetworkPortEthernet', 'T', 10,NULL, NULL);
INSERT INTO `glpi_plugin_ocsinventoryng_networkporttypes` VALUES (NULL, 'Wifi port', 'Wifi', '*', 'NetworkPortWifi', NULL, NULL, 'a', NULL);
INSERT INTO `glpi_plugin_ocsinventoryng_networkporttypes` VALUES (NULL, 'Loopback port', 'Local', '*', 'NetworkPortLocal', NULL, NULL, NULL, NULL);

INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginOcsinventoryngOcsServer','3','1','0');
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginOcsinventoryngOcsServer','19','2','0');
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginOcsinventoryngOcsServer','6','3','0');
INSERT INTO `glpi_displaypreferences` (`itemtype`, `num`, `rank`, `users_id`)
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
                       ('PluginOcsinventoryngDetail', 80, 6, 0);
