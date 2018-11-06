CREATE TABLE `glpi_plugin_ocsinventoryng_ruleimportentities` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `use_checkruleimportentity` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `import_teamviewer` TINYINT(1) NOT NULL DEFAULT '0';

ALTER TABLE `glpi_plugin_ocsinventoryng_winupdates` ADD `entities_id` INT(11) NOT NULL DEFAULT '0';

CREATE TABLE `glpi_plugin_ocsinventoryng_teamviewers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `computers_id` INT(11) NOT NULL DEFAULT '0',
  `twid` VARCHAR(255) DEFAULT NULL,
  `version` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;