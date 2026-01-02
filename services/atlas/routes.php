<?php

use cartographica\services\atlas\controllers\RegisterIsland;
use cartographica\services\atlas\controllers\VerifyCertificate;

$router->post("register_island", RegisterIsland::class);
$router->post("verify_certificate", VerifyCertificate::class);
