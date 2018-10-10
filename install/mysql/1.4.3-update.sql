ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `import_osinstall` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `import_networkshare` TINYINT(1) NOT NULL DEFAULT '0';

ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `import_user` TINYINT(1) NOT NULL DEFAULT '1';
ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `import_user_location` TINYINT(1) NOT NULL DEFAULT '1';
ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `import_user_group` TINYINT(1) NOT NULL DEFAULT '1';

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