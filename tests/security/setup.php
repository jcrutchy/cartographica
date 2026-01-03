<?php

use cartographica\share\Keys;
use cartographica\services\atlas\Config as AtlasConfig;
use cartographica\services\identity\Config as IdentityConfig;

$atlas_config=new AtlasConfig();
$identity_config=new IdentityConfig();

Keys::ensure(
  $atlas_config->privateKey(),
  $atlas_config->publicKey()
);

Keys::ensure(
  $identity_config->privateKey(),
  $identity_config->publicKey()
);
