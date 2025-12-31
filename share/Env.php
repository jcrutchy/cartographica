<?php

/*

Env.php
=======

Purpose:
Store shared configuration constants.

Usage:
$config=shared_config();
$webRoot=$config["web_root"];

*/

define("CARTOGRAPHICA_DATA_DIR",dirname(__DIR__)."_data");
define("CARTOGRAPHICA_SHARED_CONFIG",CARTOGRAPHICA_DATA_DIR."/shared/config.json");
