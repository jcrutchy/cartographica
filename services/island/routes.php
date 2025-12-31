<?php

use cartographica\services\island\controllers\Handshake;
use cartographica\services\island\controllers\GetIslandInfo;

$router->post("handshake", Handshake::class);
$router->get("island_info", GetIslandInfo::class);
