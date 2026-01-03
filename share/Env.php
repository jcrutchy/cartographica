<?php

/*

Env.php
=======

Purpose:
Assign top level global configuration constants.

*/

require __DIR__."/utils.php";

$data_dir=dirname(__DIR__)."_data";
if ((isset($_GET["test"])==true) and ($_SERVER["REMOTE_ADDR"]=="::1"))
{
  unset($_GET["test"]);
  $data_dir.="/_testdata";
}

define("CARTOGRAPHICA_DATA_DIR",$data_dir);

define("CARTOGRAPHICA_SHARED_CONFIG",CARTOGRAPHICA_DATA_DIR."/shared/config.json");
