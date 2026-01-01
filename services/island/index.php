<?php

require __DIR__ . "/../../share/Autoload.php";
require __DIR__ . "/../../share/Env.php";

use cartographica\share\Request;
use cartographica\share\Router;
use cartographica\share\Logger;
use cartographica\share\Keys;
use cartographica\services\island\Config;

// Init logging
Logger::init(Config::logFile());

// Ensure keypair exists
Keys::ensure(Config::privateKey(), Config::publicKey());

// Build request + router
$request = new Request();
$router  = new Router($request);

// Load routes
require __DIR__ . "/routes.php";

// Dispatch
$router->dispatch();
