<?php

mkdir($test_data_directory."/services/island",0777,true);
mkdir($test_data_directory."/services/island/logs",0777,true);
mkdir($test_data_directory."/services/island/island_01",0777,true);

copy($real_data_directory."/services/island/island_01/island_config.json",
  $test_data_directory."/services/island/island_01/sland_config.json"
);
