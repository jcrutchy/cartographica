<?php

require_once __DIR__ . '/../../bootstrap.php';

$path = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

if (isset($_GET["cmd"]))
{
  switch ($_GET["cmd"])
  {
    case "status":
      require 'handlers/status.php';
      exit;
    case "start":
      require 'handlers/start.php';
      exit;
    case "stop":
      require 'handlers/stop.php';
      exit;
  }
}

http_response_code(404);
echo json_encode(["error" => "Not found"]);
