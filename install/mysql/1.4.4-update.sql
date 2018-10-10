ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `import_runningprocess` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `import_service` TINYINT(1) NOT NULL DEFAULT '0';

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

ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `linksnmp_computerdisks` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `importsnmp_computerdisks` TINYINT(1) NOT NULL DEFAULT '0';