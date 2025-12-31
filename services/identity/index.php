<?php

require __DIR__ . "/../../share/Autoload.php";

use cartographica\share\Request;
use cartographica\share\Router;
use cartographica\share\Logger;
use cartographica\share\Keys;
use cartographica\services\identity\Config;

// Init logging
Logger::init(Config::logFile());

// Ensure identity keypair exists
Keys::ensure(Config::privateKey(), Config::publicKey());

// Build request + router
$request = new Request();
$router  = new Router($request);

// Load routes
require __DIR__ . "/routes.php";

// Dispatch
$router->dispatch();
