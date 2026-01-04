<?php

use cartographica\services\atlas\controllers\RegisterIsland;
use cartographica\services\atlas\controllers\VerifyCertificate;
use cartographica\services\atlas\controllers\ListNetworks;
use cartographica\services\atlas\controllers\ListIslands;

$router->post("register_island", RegisterIsland::class);
$router->post("verify_certificate", VerifyCertificate::class);
$router->get("list_networks", ListNetworks::class);
$router->get("list_islands", ListIslands::class);
