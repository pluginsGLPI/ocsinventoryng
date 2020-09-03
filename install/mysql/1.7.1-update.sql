ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `import_bitlocker` TINYINT(1) NOT NULL DEFAULT '0';

CREATE TABLE `glpi_plugin_ocsinventoryng_bitlockerstatuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computers_id` int(11) NOT NULL DEFAULT '0',
  `item_disks_id` int(11) NOT NULL DEFAULT '0',
  `volume_type` varchar(255) DEFAULT NULL,
  `protection_status` varchar(255) DEFAULT NULL,
  `init_project` varchar(255) DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `computers_id` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;