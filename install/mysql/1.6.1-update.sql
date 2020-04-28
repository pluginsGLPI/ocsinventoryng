ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `import_customapp` TINYINT(1) NOT NULL DEFAULT '0';

CREATE TABLE `glpi_plugin_ocsinventoryng_customapps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computers_id` int(11) NOT NULL DEFAULT '0',
  `path` varchar(255) DEFAULT NULL,
  `text` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `glpi_plugin_ocsinventoryng_configs` CHANGE `use_newocs_alert` `use_newocs_alert` INT(11) NOT NULL DEFAULT '-1';
ALTER TABLE `glpi_plugin_ocsinventoryng_ocsalerts` CHANGE `use_newocs_alert` `use_newocs_alert` INT(11) NOT NULL DEFAULT '-1';
ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `cleancron_nb_days` int(11) NOT NULL DEFAULT '90';