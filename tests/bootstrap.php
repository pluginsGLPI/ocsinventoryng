<?php

/**
 * PHPUnit bootstrap for the ocsinventoryng plugin.
 * Does not require a full GLPI installation; loads autoloaders and defines
 * translation stub functions so unit tests can run in isolation.
 */

// Define stub translation functions so plugin code can be loaded without GLPI.
if (!function_exists('__')) {
    function __(string $str, string $domain = ''): string
    {
        return $str;
    }
}

if (!function_exists('_n')) {
    function _n(string $singular, string $plural, int $nb, string $domain = ''): string
    {
        return $nb === 1 ? $singular : $plural;
    }
}

if (!function_exists('_x')) {
    function _x(string $ctx, string $str, string $domain = ''): string
    {
        return $str;
    }
}

// Load GLPI vendor autoloader when GLPI_ROOT is provided.
$glpiRoot = getenv('GLPI_ROOT');
if ($glpiRoot !== false && $glpiRoot !== '') {
    $glpiAutoload = $glpiRoot . '/vendor/autoload.php';
    if (file_exists($glpiAutoload)) {
        require_once $glpiAutoload;
    }
}

// Load the plugin's own vendor autoloader if present.
$pluginAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($pluginAutoload)) {
    require_once $pluginAutoload;
}
