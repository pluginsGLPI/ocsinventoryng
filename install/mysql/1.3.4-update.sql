ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `import_proxysetting` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `import_winusers` TINYINT(1) NOT NULL DEFAULT '0';

CREATE TABLE `glpi_plugin_ocsinventoryng_proxysettings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `computers_id` INT(11) NOT NULL DEFAULT '0',
  `user` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `enabled` INT(11) NOT NULL DEFAULT '0',
  `autoconfigurl` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `address` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `override` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `glpi_plugin_ocsinventoryng_winusers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `computers_id` INT(11) NOT NULL DEFAULT '0',
  `name` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `disabled` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sid` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;