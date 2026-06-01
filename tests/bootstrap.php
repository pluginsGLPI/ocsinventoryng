<?php

$loader = require dirname(__DIR__, 3) . '/vendor/autoload.php';

$loader->addPsr4('GlpiPlugin\\Ocsinventoryng\\', dirname(__DIR__) . '/src/');
$loader->addPsr4('GlpiPlugin\\Ocsinventoryng\\Tests\\Units\\', dirname(__DIR__) . '/tests/units/');
$loader->addPsr4('GlpiPlugin\\Ocsinventoryng\\Tests\\Integration\\', dirname(__DIR__) . '/tests/integration/');
$loader->addPsr4('GlpiPlugin\\Ocsinventoryng\\Tests\\Fixtures\\', dirname(__DIR__) . '/tests/fixtures/');
