<?php

use cartographica\share\Keys;
use cartographica\services\atlas\Config as AtlasConfig;
use cartographica\services\identity\Config as IdentityConfig;

Keys::ensure(
    IdentityConfig::privateKey(),
    IdentityConfig::publicKey()
);

Keys::ensure(
    AtlasConfig::privateKey(),
    AtlasConfig::publicKey()
);
