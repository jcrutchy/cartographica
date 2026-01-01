<?php

require __DIR__ . "/utils.php";

test_banner_message("CARTOGRAPHICA TEST SUITE",34);

$test_raw=dirname(__DIR__)."_data/_testdata";

if (!is_dir($test_raw))
{
  mkdir($test_raw, 0777, true);
};

define("CARTOGRAPHICA_DATA_DIR",realpath($test_raw));

if (is_dir(CARTOGRAPHICA_DATA_DIR))
{
  rrmdir(CARTOGRAPHICA_DATA_DIR);
}

mkdir(CARTOGRAPHICA_DATA_DIR."/shared", 0777, true);

define("CARTOGRAPHICA_DEV_DATA_DIR",realpath(dirname(__DIR__)."_data"));

copy_files(CARTOGRAPHICA_DEV_DATA_DIR."/shared",CARTOGRAPHICA_DATA_DIR."/shared");

require __DIR__ . "/../share/Autoload.php";

define("CARTOGRAPHICA_SHARED_CONFIG",CARTOGRAPHICA_DATA_DIR."/shared/config.json");

foreach (glob(__DIR__ . "/*/setup.php") as $setup)
{
  require $setup;
}

$base = __DIR__;
$testFiles = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($base)
);

$pass_code=32;
$fail_code=31;

$total = 0;
$passed = 0;
$failed = 0;

foreach ($testFiles as $file)
{
  if (!str_ends_with($file, "Test.php"))
  {
    continue;
  }
  require_once $file;
  $relative = str_replace($base, "", $file);
  $relative = str_replace("\\", "/", $relative);
  $relative = ltrim($relative, "/");
  $relative = substr($relative, 0, -4);
  $class = "cartographica\\tests\\" . str_replace("/", "\\", $relative);
  if (!class_exists($class))
  {
    test_result_message("SKIP",$class);
    continue;
  }
  $test = new $class();
  $results = $test->run();
  foreach ($results as $result)
  {
    $total++;
    if ($result["ok"])
    {
      $passed++;
      test_result_message("PASS",$class,$result['message']);
    }
    else
    {
      $failed++;
      test_result_message("FAIL",$class,$result['message']);
    }
  }
}

$summaryColor = $failed === 0 ? $pass_code : $fail_code;

test_banner_message("\nSummary: $passed passed, $failed failed, $total total",$summaryColor);
