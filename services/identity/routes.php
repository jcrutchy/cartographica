<?php

use cartographica\services\identity\controllers\RequestLogin;
use cartographica\services\identity\controllers\Redeem;
use cartographica\services\identity\controllers\Verify;

$router->post("request_login", RequestLogin::class);
$router->post("redeem",        Redeem::class);
$router->post("verify",        Verify::class);
