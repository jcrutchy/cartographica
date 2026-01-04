<?php

mkdir($test_data_directory."/services/atlas", 0777, true);
mkdir($test_data_directory."/services/atlas/logs", 0777, true);

use cartographica\share\Keys;
use cartographica\services\atlas\Config as AtlasConfig;

$config=new AtlasConfig();

Keys::ensure(
  $config->privateKey(),
  $config->publicKey()
);
