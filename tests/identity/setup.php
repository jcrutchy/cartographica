<?php

mkdir(CARTOGRAPHICA_DATA_DIR."/services/identity", 0777, true);
mkdir(CARTOGRAPHICA_DATA_DIR."/services/identity/log", 0777, true);

use cartographica\share\Keys;
use cartographica\services\identity\Config as IdentityConfig;

$config=new IdentityConfig();

Keys::ensure($config->privateKey(),$config->publicKey());
