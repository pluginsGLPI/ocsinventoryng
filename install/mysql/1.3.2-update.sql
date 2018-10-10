ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `import_uptime` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `import_officepack` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `import_winupdatestate` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `action_cleancron` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `use_restorationcron` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `delay_restorationcron` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `glpi_plugin_ocsinventoryng_ocslinks` ADD `uptime` VARCHAR(64) COLLATE utf8_unicode_ci DEFAULT NULL;

CREATE TABLE `glpi_plugin_ocsinventoryng_winupdates` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `computers_id` INT(11) NOT NULL DEFAULT '0',
  `auoptions` INT(11) NOT NULL DEFAULT '0',
  `scheduleinstalldate` DATETIME DEFAULT NULL,
  `lastsuccesstime` DATETIME DEFAULT NULL,
  `detectsuccesstime` DATETIME DEFAULT NULL,
  `downloadsuccesstime` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;