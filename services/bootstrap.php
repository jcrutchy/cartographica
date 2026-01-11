<?php

$common_root=realpath(__DIR__."/../..");
$bootstrap=[];

$bootstrap["repo_root"]=$common_root."/cartographica";
$bootstrap["data_root"]=$bootstrap["repo_root"]."/cartographica_data"; # TODO: DATA ROOT TO BE MOVED OUTSIDE OF GIT REPO & WWW
$bootstrap["test_data"]=$bootstrap["data_root"]."/_testdata";
$bootstrap["php_cli_exec"]="c:/php/php";

# TODO: add derivative paths for services, share, sland_data, etc

# normalize paths
foreach ($bootstrap as $k => $v)
{
  $bootstrap[$k]=str_replace("\\","/",$v);
}

return $bootstrap;
