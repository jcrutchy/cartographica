<?php

mkdir(CARTOGRAPHICA_DATA_DIR."/services/atlas", 0777, true);
mkdir(CARTOGRAPHICA_DATA_DIR."/services/atlas/log", 0777, true);

use cartographica\share\Keys;
use cartographica\services\atlas\Config as AtlasConfig;

$config=new AtlasConfig();

Keys::ensure(
  $config->privateKey(),
  $config->publicKey()
);
