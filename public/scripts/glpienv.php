<?php

define("GLPI_DIR_ROOT", "../../../..");

include GLPI_DIR_ROOT . '/vendor/autoload.php';
$kernel = new \Glpi\Kernel\Kernel($options['env'] ?? null);

echo "GLPI_LOG_DIR=" . GLPI_LOG_DIR . "\n";
echo "GLPI_LOCK_DIR=" . GLPI_LOCK_DIR . "\n";
