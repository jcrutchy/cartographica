<?php

require __DIR__."/../../share/Autoload.php";

use cartographica\share\Request;
use cartographica\share\Router;
use cartographica\share\Logger;
use cartographica\share\Keys;
use cartographica\share\Env;
use cartographica\services\atlas\Config;

$config=new Config();
Logger::init($config->logFile());
Keys::ensure($config->privateKey(),$config->publicKey());

$request=new Request();
$router=new Router($request);

require __DIR__ . "/routes.php";

$router->dispatch();
