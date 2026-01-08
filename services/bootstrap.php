<?php

$common_root=realpath(__DIR__."/../..");
$bootstrap=[];

$bootstrap["repo_root"]=$common_root."/cartographica";
$bootstrap["data_root"]=$common_root."/cartographica_data";
$bootstrap["test_data"]=$bootstrap["data_root"]."/_testdata";
$bootstrap["php_cli_exec"]="c:/php/php";

# TODO: add derivative paths for services, share, sland_data, etc

return $bootstrap;
