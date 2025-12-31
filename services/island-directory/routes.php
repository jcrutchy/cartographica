<?php

use cartographica\services\islanddirectory\controllers\RegisterIsland;
use cartographica\services\islanddirectory\controllers\VerifyCertificate;

$router->post("register_island", RegisterIsland::class);
$router->post("verify_certificate", VerifyCertificate::class);
