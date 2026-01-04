<?php

mkdir($test_data_directory."/services/identity",0777,true);
mkdir($test_data_directory."/services/identity/logs",0777,true);

use cartographica\share\Keys;
use cartographica\services\identity\Config as IdentityConfig;

$config=new IdentityConfig();

Keys::ensure($config->privateKey(),$config->publicKey());
