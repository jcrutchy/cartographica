<?php

/*

Autoload.php
============

Purpose:
Cartographica PSR-4 autoloader.
Maps namespaces under "cartographica\" to the project root.

Usage:
Namespace → file path
class: cartographica\share\Logger → /cartographica/share/Logger.php
controller: cartographica\services\identity\controllers\RequestLogin → /cartographica/services/identity/controllers/RequestLogin.php
No need for long list of requires. Only need following line at top of services/tools:
require __DIR__."/../../share/Autoload.php";
Followed by use statements as required:
use cartographica\share\Request;
use cartographica\share\Router;
use cartographica\share\Response;

*/

spl_autoload_register(function ($class)
{

    // Only handle Cartographica namespaces
    $prefix = "cartographica\\";
    $prefixLen = strlen($prefix);

    if (strncmp($class, $prefix, $prefixLen) !== 0) {
        return; // not our namespace
    }

    // Convert namespace → file path
    $relative = substr($class, $prefixLen);

    // Replace namespace separators with directory separators
    $relativePath = str_replace("\\", DIRECTORY_SEPARATOR, $relative);

    // Base directory = project root
    $baseDir = dirname(__DIR__); // one level above share/

    // Full file path
    $file = $baseDir . DIRECTORY_SEPARATOR . $relativePath . ".php";

    if (file_exists($file)) {
        require $file;
    }
});
