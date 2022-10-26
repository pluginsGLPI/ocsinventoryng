ALTER TABLE `glpi_plugin_ocsinventoryng_ocsservers` ADD `link_with_user` tinyint NOT NULL DEFAULT '1';
UPDATE `glpi_plugin_ocsinventoryng_networkports` SET `items_devicenetworkcards_id` = '0' WHERE `items_devicenetworkcards_id` = '-1';
