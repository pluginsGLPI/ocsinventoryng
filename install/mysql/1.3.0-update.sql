ALTER TABLE `glpi_plugin_ocsinventoryng_snmpocslinks` ADD `linked` TINYINT(1) NOT NULL DEFAULT '0';

ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `import_antivirus` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `use_locks` TINYINT(1) NOT NULL DEFAULT '1';
ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `importsnmp_printermemory` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers`
  ADD `linksnmp_name` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `linksnmp_serial` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `linksnmp_comment` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `linksnmp_contact` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `linksnmp_location` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `linksnmp_domain` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `linksnmp_manufacturer` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `linksnmp_createport` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `linksnmp_last_pages_counter` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `linksnmp_firmware` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `linksnmp_power` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `linksnmp_fan` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `linksnmp_printermemory` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `importsnmp_computernetworkcards` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `importsnmp_computermemory` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `importsnmp_computerprocessors` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `importsnmp_computersoftwares` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `importsnmp_computervm` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `linksnmp_computernetworkcards` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `linksnmp_computermemory` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `linksnmp_computerprocessors` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `linksnmp_computersoftwares` TINYINT(1) NOT NULL DEFAULT '0',
  ADD `linksnmp_computervm` TINYINT(1) NOT NULL DEFAULT '0';

ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` CHANGE `history_sofware` `history_software` TINYINT(1) NOT NULL DEFAULT '0';