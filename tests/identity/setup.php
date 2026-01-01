<?php

mkdir(CARTOGRAPHICA_DATA_DIR."/services/identity", 0777, true);
mkdir(CARTOGRAPHICA_DATA_DIR."/services/identity/log", 0777, true);

use cartographica\share\Keys;
use cartographica\services\identity\Config as IdentityConfig;

Keys::ensure(
    IdentityConfig::privateKey(),
    IdentityConfig::publicKey()
);
