ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` DROP `states_id_default`;
ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` DROP `import_user`;
ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `import_user_group_default` tinyint(1) NOT NULL DEFAULT '1';