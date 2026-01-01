<?php

mkdir(CARTOGRAPHICA_DATA_DIR."/services/island", 0777, true);
mkdir(CARTOGRAPHICA_DATA_DIR."/services/island/log", 0777, true);

copy(CARTOGRAPHICA_DEV_DATA_DIR."/services/island/island_config.json",
    CARTOGRAPHICA_DATA_DIR."/services/island/island_config.json"
);
