### Dump table glpi_plugin_ocsinventoryng_profiles

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_profiles`;
CREATE TABLE `glpi_plugin_ocsinventoryng_profiles` (
	`id` int(11) NOT NULL auto_increment,
	`profiles_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_profiles (id)',
	`ocsng` char(1) collate utf8_unicode_ci default NULL,
	`sync_ocsng` char(1) collate utf8_unicode_ci default NULL,
	`view_ocsng` char(1) collate utf8_unicode_ci default NULL,
	`clean_ocsng` char(1) collate utf8_unicode_ci default NULL,
	`rule_ocs` char(1) collate utf8_unicode_ci default NULL,
	PRIMARY KEY  (`id`),
	KEY `profiles_id` (`profiles_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_plugin_ocsinventoryng_ocsadmininfoslinks

DROP TABLE IF EXISTS `glpi_plugin_ocsinventoryng_ocsadmininfoslinks`;
CREATE TABLE `glpi_plugin_ocsinventoryng_ocsadmininfoslinks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `glpi_column` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ocs_column` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `plugin_ocsinventoryng_ocsservers_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `plugin_ocsinventoryng_ocsservers_id` (`plugin_ocsinventoryng_ocsservers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


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
  `computer_update` longtext COLLATE utf8_unicode_ci,
  `plugin_ocsinventoryng_ocsservers_id` int(11) NOT NULL DEFAULT '0',
  `ocs_agent_version` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `entities_id` int(11) NOT NULL DEFAULT '0',
  `tag` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_ocsinventoryng_ocsservers_id`,`ocsid`),
  KEY `last_update` (`last_update`),
  KEY `ocs_deviceid` (`ocs_deviceid`),
  KEY `last_ocs_update` (`plugin_ocsinventoryng_ocsservers_id`,`last_ocs_update`),
  KEY `computers_id` (`computers_id`),
  KEY `use_auto_update` (`use_auto_update`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


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
  `import_general_name` tinyint(1) NOT NULL DEFAULT '0',
  `import_general_os` tinyint(1) NOT NULL DEFAULT '0',
  `import_general_serial` tinyint(1) NOT NULL DEFAULT '0',
  `import_general_model` tinyint(1) NOT NULL DEFAULT '0',
  `import_general_manufacturer` tinyint(1) NOT NULL DEFAULT '0',
  `import_general_type` tinyint(1) NOT NULL DEFAULT '0',
  `import_general_domain` tinyint(1) NOT NULL DEFAULT '0',
  `import_general_contact` tinyint(1) NOT NULL DEFAULT '0',
  `import_general_comment` tinyint(1) NOT NULL DEFAULT '0',
  `import_device_processor` tinyint(1) NOT NULL DEFAULT '0',
  `import_device_memory` tinyint(1) NOT NULL DEFAULT '0',
  `import_device_hdd` tinyint(1) NOT NULL DEFAULT '0',
  `import_device_iface` tinyint(1) NOT NULL DEFAULT '0',
  `import_device_gfxcard` tinyint(1) NOT NULL DEFAULT '0',
  `import_device_sound` tinyint(1) NOT NULL DEFAULT '0',
  `import_device_drive` tinyint(1) NOT NULL DEFAULT '0',
  `import_device_port` tinyint(1) NOT NULL DEFAULT '0',
  `import_device_modem` tinyint(1) NOT NULL DEFAULT '0',
  `import_registry` tinyint(1) NOT NULL DEFAULT '0',
  `import_os_serial` tinyint(1) NOT NULL DEFAULT '0',
  `import_ip` tinyint(1) NOT NULL DEFAULT '0',
  `import_disk` tinyint(1) NOT NULL DEFAULT '0',
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
  `deleted_behavior` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `import_vms` tinyint(1) NOT NULL DEFAULT '0',
  `import_general_uuid` tinyint(1) NOT NULL DEFAULT '0',
  `ocs_version` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date_mod` (`date_mod`),
  KEY `is_active` (`is_active`),
  KEY `use_massimport` (`use_massimport`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

### Dump table glpi_registrykeys

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


INSERT INTO `glpi_plugin_ocsinventoryng_networkporttypes` VALUES (NULL, 'Unkown port', '*', '*', 'PluginOcsinventoryngNetworkPort', NULL, NULL,NULL, NULL);
INSERT INTO `glpi_plugin_ocsinventoryng_networkporttypes` VALUES (NULL, 'Ethernet port', 'Ethernet', '*', 'NetworkPortEthernet', 'T', 10,NULL, NULL);
INSERT INTO `glpi_plugin_ocsinventoryng_networkporttypes` VALUES (NULL, 'Wifi port', 'Wifi', '*', 'NetworkPortWifi', NULL, NULL, 'a', NULL);
INSERT INTO `glpi_plugin_ocsinventoryng_networkporttypes` VALUES (NULL, 'Loopback port', 'Local', '*', 'NetworkPortLocal', NULL, NULL, NULL, NULL);

INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginOcsinventoryngOcsServer','3','1','0');
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginOcsinventoryngOcsServer','19','2','0');
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginOcsinventoryngOcsServer','6','3','0');

INSERT INTO `glpi_crontasks` VALUES (NULL,'PluginOcsinventoryngOcsServer','ocsng','300',NULL,'0','1','3','0','24','30',NULL,NULL,NULL);
