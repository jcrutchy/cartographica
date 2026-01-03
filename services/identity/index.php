<?php

require __DIR__ . "/../../share/Autoload.php";
require __DIR__ . "/../../share/Env.php";

use cartographica\share\Request;
use cartographica\share\Router;
use cartographica\share\Logger;
use cartographica\share\Keys;
use cartographica\services\identity\Config;

Logger::init(Config::logFile());

$config=new Config();
Keys::ensure($config->privateKey(),$config->publicKey());

$request=new Request();
$router=new Router($request);

require __DIR__ . "/routes.php";

$router->dispatch();
