### Dump table glpi_plugin_ocsinventoryng_ocsadmininfoslinks

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_ocsadmininfoslinks`;
CREATE TABLE `glpi_plugin_ocsinventoryng_ocsadmininfoslinks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `glpi_column` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ocs_column` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `plugin_ocsinventoryng_ocsservers_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `plugin_ocsinventoryng_ocsservers_id` (`plugin_ocsinventoryng_ocsservers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_plugin_ocsinventoryng_ocslinks

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_ocslinks`;
CREATE TABLE `glpi_plugin_ocsinventoryng_ocslinks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computers_id` int(11) NOT NULL DEFAULT '0',
  `ocsid` int(11) NOT NULL DEFAULT '0',
  `ocs_deviceid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `use_auto_update` tinyint(1) NOT NULL DEFAULT '1',
  `last_update` datetime DEFAULT NULL,
  `last_ocs_update` datetime DEFAULT NULL,
  `last_ocs_conn` datetime default NULL,
  `ip_src` varchar(255) collate utf8_unicode_ci default NULL,
  `computer_update` longtext COLLATE utf8_unicode_ci,
  `plugin_ocsinventoryng_ocsservers_id` int(11) NOT NULL DEFAULT '0',
  `ocs_agent_version` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT '0',
  `tag` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `uptime` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_ocsinventoryng_ocsservers_id`,`ocsid`),
  KEY `last_update` (`last_update`),
  KEY `ocs_deviceid` (`ocs_deviceid`),
  KEY `last_ocs_update` (`plugin_ocsinventoryng_ocsservers_id`,`last_ocs_update`),
  KEY `computers_id` (`computers_id`),
  KEY `entities_id` (`entities_id`),
  KEY `use_auto_update` (`use_auto_update`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_plugin_ocsinventoryng_ocsservers

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_ocsservers`;
CREATE TABLE `glpi_plugin_ocsinventoryng_ocsservers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ocs_db_user` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ocs_db_passwd` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ocs_db_host` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ocs_db_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ocs_db_utf8` tinyint(1) NOT NULL DEFAULT '0',
  `checksum` int(11) NOT NULL DEFAULT '0',
  `import_periph` tinyint(1) NOT NULL DEFAULT '0',
  `import_monitor` tinyint(1) NOT NULL DEFAULT '0',
  `import_software` tinyint(1) NOT NULL DEFAULT '0',
  `import_printer` tinyint(1) NOT NULL DEFAULT '0',
  `import_general_name` tinyint(1) NOT NULL DEFAULT '1',
  `import_general_os` tinyint(1) NOT NULL DEFAULT '1',
  `import_general_serial` tinyint(1) NOT NULL DEFAULT '1',
  `import_general_model` tinyint(1) NOT NULL DEFAULT '1',
  `import_general_manufacturer` tinyint(1) NOT NULL DEFAULT '1',
  `import_general_type` tinyint(1) NOT NULL DEFAULT '1',
  `import_general_domain` tinyint(1) NOT NULL DEFAULT '1',
  `import_general_contact` tinyint(1) NOT NULL DEFAULT '1',
  `import_user` tinyint(1) NOT NULL DEFAULT '1',
  `import_user_location` tinyint(1) NOT NULL DEFAULT '1',
  `import_user_group` tinyint(1) NOT NULL DEFAULT '1',
  `import_general_comment` tinyint(1) NOT NULL DEFAULT '1',
  `import_device_processor` tinyint(1) NOT NULL DEFAULT '1',
  `import_device_memory` tinyint(1) NOT NULL DEFAULT '1',
  `import_device_hdd` tinyint(1) NOT NULL DEFAULT '1',
  `import_device_iface` tinyint(1) NOT NULL DEFAULT '1',
  `import_device_gfxcard` tinyint(1) NOT NULL DEFAULT '1',
  `import_device_sound` tinyint(1) NOT NULL DEFAULT '1',
  `import_device_drive` tinyint(1) NOT NULL DEFAULT '1',
  `import_device_port` tinyint(1) NOT NULL DEFAULT '1',
  `import_device_modem` tinyint(1) NOT NULL DEFAULT '1',
  `import_device_bios` tinyint(1) NOT NULL DEFAULT '1',
  `import_device_motherboard` tinyint(1) NOT NULL DEFAULT '1',
  `import_registry` tinyint(1) NOT NULL DEFAULT '1',
  `import_antivirus` tinyint(1) NOT NULL DEFAULT '0',
  `import_officepack` tinyint(1) NOT NULL DEFAULT '0',
  `import_winupdatestate` tinyint(1) NOT NULL DEFAULT '0',
  `import_proxysetting` tinyint(1) NOT NULL DEFAULT '0',
  `import_winusers` tinyint(1) NOT NULL DEFAULT '0',
  `import_teamviewer` tinyint(1) NOT NULL DEFAULT '0',
  `import_osinstall` TINYINT(1) NOT NULL DEFAULT '0',
  `import_networkshare` TINYINT(1) NOT NULL DEFAULT '0',
  `import_os_serial` tinyint(1) NOT NULL DEFAULT '1',
  `import_ip` tinyint(1) NOT NULL DEFAULT '1',
  `import_disk` tinyint(1) NOT NULL DEFAULT '1',
  `import_monitor_comment` tinyint(1) NOT NULL DEFAULT '0',
  `states_id_default` int(11) NOT NULL DEFAULT '0',
  `tag_limit` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tag_exclude` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `use_soft_dict` tinyint(1) NOT NULL DEFAULT '0',
  `cron_sync_number` int(11) DEFAULT '1',
  `deconnection_behavior` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ocs_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_mod` datetime DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `use_massimport` tinyint(1) NOT NULL DEFAULT '0',
  `use_locks` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_behavior` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `import_vms` tinyint(1) NOT NULL DEFAULT '1',
  `import_general_uuid` tinyint(1) NOT NULL DEFAULT '1',
  `import_device_controller` tinyint(1) NOT NULL DEFAULT '1',
  `import_device_slot` tinyint(1) NOT NULL DEFAULT '1',
  `ocs_version` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `conn_type` tinyint(1) NOT NULL DEFAULT '0',
  `use_cleancron` tinyint(1) NOT NULL DEFAULT '0',
  `action_cleancron` tinyint(1) NOT NULL DEFAULT '0',
  `use_restorationcron` tinyint(1) NOT NULL DEFAULT '0',
  `delay_restorationcron` int(11) NOT NULL DEFAULT '0',
  `use_checkruleimportentity` tinyint(1) NOT NULL DEFAULT '0',
  `importsnmp_name` tinyint(1) NOT NULL DEFAULT '0',
  `importsnmp_serial` tinyint(1) NOT NULL DEFAULT '0',
  `importsnmp_comment` tinyint(1) NOT NULL DEFAULT '0',
  `importsnmp_contact` tinyint(1) NOT NULL DEFAULT '0',
  `importsnmp_location` tinyint(1) NOT NULL DEFAULT '0',
  `importsnmp_domain` tinyint(1) NOT NULL DEFAULT '0',
  `importsnmp_manufacturer` tinyint(1) NOT NULL DEFAULT '0',
  `importsnmp_createport` tinyint(1) NOT NULL DEFAULT '0',
  `importsnmp_last_pages_counter` tinyint(1) NOT NULL DEFAULT '0',
  `importsnmp_firmware` tinyint(1) NOT NULL DEFAULT '0',
  `importsnmp_power` tinyint(1) NOT NULL DEFAULT '0',
  `importsnmp_fan` tinyint(1) NOT NULL DEFAULT '0',
  `importsnmp_printermemory` tinyint(1) NOT NULL DEFAULT '0',
  `importsnmp_computernetworkcards` tinyint(1) NOT NULL DEFAULT '0',
  `importsnmp_computermemory` tinyint(1) NOT NULL DEFAULT '0',
  `importsnmp_computerprocessors` tinyint(1) NOT NULL DEFAULT '0',
  `importsnmp_computersoftwares` tinyint(1) NOT NULL DEFAULT '0',
  `importsnmp_computervm` tinyint(1) NOT NULL DEFAULT '0',
  `importsnmp_computerdisks` tinyint(1) NOT NULL DEFAULT '0',
  `import_runningprocess` tinyint(1) NOT NULL DEFAULT '0',
  `import_service` tinyint(1) NOT NULL DEFAULT '0',
  `import_uptime` tinyint(1) NOT NULL DEFAULT '0',
  `linksnmp_name` tinyint(1) NOT NULL DEFAULT '0',
  `linksnmp_serial` tinyint(1) NOT NULL DEFAULT '0',
  `linksnmp_comment` tinyint(1) NOT NULL DEFAULT '0',
  `linksnmp_contact` tinyint(1) NOT NULL DEFAULT '0',
  `linksnmp_location` tinyint(1) NOT NULL DEFAULT '0',
  `linksnmp_domain` tinyint(1) NOT NULL DEFAULT '0',
  `linksnmp_manufacturer` tinyint(1) NOT NULL DEFAULT '0',
  `linksnmp_createport` tinyint(1) NOT NULL DEFAULT '0',
  `linksnmp_last_pages_counter` tinyint(1) NOT NULL DEFAULT '0',
  `linksnmp_firmware` tinyint(1) NOT NULL DEFAULT '0',
  `linksnmp_power` tinyint(1) NOT NULL DEFAULT '0',
  `linksnmp_fan` tinyint(1) NOT NULL DEFAULT '0',
  `linksnmp_printermemory` tinyint(1) NOT NULL DEFAULT '0',
  `linksnmp_computernetworkcards` tinyint(1) NOT NULL DEFAULT '0',
  `linksnmp_computermemory` tinyint(1) NOT NULL DEFAULT '0',
  `linksnmp_computerprocessors` tinyint(1) NOT NULL DEFAULT '0',
  `linksnmp_computersoftwares` tinyint(1) NOT NULL DEFAULT '0',
  `linksnmp_computervm` tinyint(1) NOT NULL DEFAULT '0',
  `linksnmp_computerdisks` tinyint(1) NOT NULL DEFAULT '0',
  `dohistory` tinyint(1) NOT NULL DEFAULT '1',
  `history_hardware` tinyint(1) NOT NULL DEFAULT '1',
  `history_bios` tinyint(1) NOT NULL DEFAULT '1',
  `history_drives` tinyint(1) NOT NULL DEFAULT '1',
  `history_network` tinyint(1) NOT NULL DEFAULT '1',
  `history_devices` tinyint(1) NOT NULL DEFAULT '1',
  `history_monitor` tinyint(1) NOT NULL DEFAULT '1',
  `history_printer` tinyint(1) NOT NULL DEFAULT '1',
  `history_peripheral` tinyint(1) NOT NULL DEFAULT '1',
  `history_software` tinyint(1) NOT NULL DEFAULT '1',
  `history_vm` tinyint(1) NOT NULL DEFAULT '1',
  `history_admininfos` tinyint(1) NOT NULL DEFAULT '1',
  `history_plugins` tinyint(1) NOT NULL DEFAULT '1',
  `history_os` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `is_active` (`is_active`),
  KEY `use_massimport` (`use_massimport`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_plugin_ocsinventoryng_registrykeys

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_registrykeys`;
CREATE TABLE `glpi_plugin_ocsinventoryng_registrykeys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computers_id` int(11) NOT NULL DEFAULT '0',
  `hive` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ocs_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_plugin_ocsinventoryng_computerwinupdates

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_winupdates`;
CREATE TABLE `glpi_plugin_ocsinventoryng_winupdates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computers_id` int(11) NOT NULL DEFAULT '0',
  `auoptions` int(11) NOT NULL DEFAULT '0',
  `scheduleinstalldate` datetime DEFAULT NULL,
  `lastsuccesstime` datetime DEFAULT NULL,
  `detectsuccesstime` datetime DEFAULT NULL,
  `downloadsuccesstime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_plugin_ocsinventoryng_osinstalls

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_osinstalls`;
CREATE TABLE `glpi_plugin_ocsinventoryng_osinstalls` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `computers_id` INT(11) NOT NULL DEFAULT '0',
  `build_version` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `install_date` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `codeset` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `countrycode` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `oslanguage` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `curtimezone` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locale` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_plugin_ocsinventoryng_proxysettings

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_proxysettings`;
CREATE TABLE `glpi_plugin_ocsinventoryng_proxysettings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computers_id` int(11) NOT NULL DEFAULT '0',
  `user` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `enabled` int(11) NOT NULL DEFAULT '0',
  `autoconfigurl` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `address` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `override` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_plugin_ocsinventoryng_networkshares

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_networkshares`;
CREATE TABLE `glpi_plugin_ocsinventoryng_networkshares` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `computers_id` INT(11) NOT NULL DEFAULT '0',
  `drive` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `path` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `size` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `freespace` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `quota` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_plugin_ocsinventoryng_winusers

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_winusers`;
CREATE TABLE `glpi_plugin_ocsinventoryng_winusers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computers_id` int(11) NOT NULL DEFAULT '0',
  `name` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `disabled` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sid` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_plugin_ocsinventoryng_teamviewers
DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_teamviewers`;
CREATE TABLE `glpi_plugin_ocsinventoryng_teamviewers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computers_id` int(11) NOT NULL DEFAULT '0',
  `twid` varchar(255) DEFAULT NULL,
  `version` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_plugin_ocsinventoryng_networkports
DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_networkports`;
CREATE TABLE `glpi_plugin_ocsinventoryng_networkports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `networkports_id` int(11) NOT NULL DEFAULT '0',
  `TYPE` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `TYPEMIB` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `items_devicenetworkcards_id` int(11) NOT NULL DEFAULT '0',
  `speed` varchar(255) COLLATE utf8_unicode_ci DEFAULT '10mb/s',
  `comment` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `networkports_id` (`networkports_id`),
  KEY `TYPE` (`TYPE`),
  KEY `TYPEMIB` (`TYPEMIB`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


### Dump table glpi_plugin_ocsinventoryng_networkporttypes

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_networkporttypes`;
CREATE TABLE `glpi_plugin_ocsinventoryng_networkporttypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `OCS_TYPE` varchar(255) NOT NULL DEFAULT '',
  `OCS_TYPEMIB` varchar(255) NOT NULL DEFAULT '',
  `instantiation_type` varchar(255) DEFAULT NULL,
  `type` varchar(10) COLLATE utf8_unicode_ci DEFAULT '' COMMENT 'T, LX, SX',
  `speed` int(11) NULL DEFAULT '10' COMMENT 'Mbit/s: 10, 100, 1000, 10000',
  `version` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'a, a/b, a/b/g, a/b/g/n, a/b/g/n/y',
  `comment` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `OCS_TYPE` (`OCS_TYPE`),
  KEY `OCS_TYPEMIB` (`OCS_TYPEMIB`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_plugin_ocsinventoryng_ocsservers_profiles

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_ocsservers_profiles`;
CREATE TABLE `glpi_plugin_ocsinventoryng_ocsservers_profiles` (
  `id` int(11) NOT NULL auto_increment,
  `plugin_ocsinventoryng_ocsservers_id` int(11) NOT NULL default '0',
  `profiles_id` int(11) NOT NULL default '0',
  PRIMARY KEY (`id`),
  KEY `plugin_ocsinventoryng_ocsservers_id` (`plugin_ocsinventoryng_ocsservers_id`),
  KEY `profiles_id` (`profiles_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_threads`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_threads` (
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
   `entities_id` int(11) NOT NULL DEFAULT 0,
   `rules_id` int(11) NOT NULL DEFAULT 0,
   PRIMARY KEY  (`id`),
   KEY `end_time` (`end_time`),
   KEY `process_thread` (`processid`,`threadid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_configs`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_configs` (
   `id` int(11) NOT NULL auto_increment,
   `thread_log_frequency` int(11) NOT NULL default '10',
   `is_displayempty` int(1) NOT NULL default '1',
   `import_limit` int(11) NOT NULL default '0',
   `delay_refresh` int(11) NOT NULL default '0',
   `allow_ocs_update` tinyint(1) NOT NULL default '0',
   `log_imported_computers` tinyint(1) NOT NULL default '0',
   `comment` text,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_details`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_details` (
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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_notimportedcomputers`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_notimportedcomputers` (
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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_servers`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_ocsinventoryng_servers` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `plugin_ocsinventoryng_ocsservers_id` int(11) NOT NULL DEFAULT '0',
   `max_ocsid` int(11) DEFAULT NULL,
   `max_glpidate` datetime DEFAULT NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY `plugin_ocsinventoryng_ocsservers_id` (`plugin_ocsinventoryng_ocsservers_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_plugin_ocsinventoryng_snmpocslinks

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_snmpocslinks`;
CREATE TABLE `glpi_plugin_ocsinventoryng_snmpocslinks` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `items_id` int(11) NOT NULL DEFAULT '0',
   `ocs_id` int(11) NOT NULL DEFAULT '0',
   `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
   `last_update` DATETIME COLLATE utf8_unicode_ci DEFAULT NULL,
   `plugin_ocsinventoryng_ocsservers_id` int(11) NOT NULL DEFAULT '0',
   `linked` tinyint(1) NOT NULL DEFAULT '0',
   PRIMARY KEY (`id`),
  KEY `plugin_ocsinventoryng_ocsservers_id` (`plugin_ocsinventoryng_ocsservers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_plugin_ocsinventoryng_ipdiscoverlinks

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_ipdiscoverocslinks`;
CREATE TABLE `glpi_plugin_ocsinventoryng_ipdiscoverocslinks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items_id` int(11) NOT NULL,
  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `macaddress` varchar(255) COLLATE utf8_unicode_ci NOT NULL UNIQUE,
  `last_update` DATETIME COLLATE utf8_unicode_ci DEFAULT NULL,
  `subnet` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `plugin_ocsinventoryng_ocsservers_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `plugin_ocsinventoryng_ocsservers_id` (`plugin_ocsinventoryng_ocsservers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_plugin_ocsinventoryng_ruleimportentities

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_ruleimportentities`;
CREATE TABLE `glpi_plugin_ocsinventoryng_ruleimportentities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_runningprocesses`;
CREATE TABLE `glpi_plugin_ocsinventoryng_runningprocesses` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `computers_id` INT(11) NOT NULL DEFAULT '0',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_services`;
CREATE TABLE `glpi_plugin_ocsinventoryng_services` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `computers_id` INT(11) NOT NULL DEFAULT '0',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_plugin_ocsinventoryng_configs`(`id`,`thread_log_frequency`,`is_displayempty`,`import_limit`) VALUES (1, 2, 1, 0);

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
