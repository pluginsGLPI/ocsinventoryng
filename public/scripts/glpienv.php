<?php

define("GLPI_DIR_ROOT", "../../../..");
require_once GLPI_DIR_ROOT . '/src/Glpi/Application/ResourcesChecker.php';
(new \Glpi\Application\ResourcesChecker(GLPI_DIR_ROOT))->checkResources();

include GLPI_DIR_ROOT . '/vendor/autoload.php';
$kernel = new \Glpi\Kernel\Kernel($options['env'] ?? null);
$application = new \Glpi\Console\Application($kernel);

echo "GLPI_LOG_DIR=" . GLPI_LOG_DIR . "\n";
echo "GLPI_LOCK_DIR=" . GLPI_LOCK_DIR . "\n";
