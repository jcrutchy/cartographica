<?php

require __DIR__ . "/Utils.php";

test_banner_message("CARTOGRAPHICA TEST SUITE",34);

$bootstrap=require dirname(__DIR__)."/bootstrap.php";
$test_data_directory=$bootstrap["test_data"];

if (is_dir($test_data_directory))
{
  rrmdir($test_data_directory);
}

mkdir($test_data_directory."/shared",0777,true);

$real_data_directory=$bootstrap["data_root"];
copy_files($real_data_directory."/shared",$test_data_directory."/shared");

require __DIR__."/../share/Autoload.php";

foreach (glob(__DIR__ . "/*/setup.php") as $setup)
{
  require $setup;
}

$base=__DIR__;
$testFiles=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));

$pass_code=32;
$fail_code=31;

$total=0;
$passed=0;
$failed=0;

foreach ($testFiles as $file)
{
  if (!str_ends_with($file,"Test.php"))
  {
    continue;
  }
  require_once $file;
  $relative=str_replace($base,"",$file);
  $relative=str_replace("\\","/",$relative);
  $relative=ltrim($relative,"/");
  $relative=substr($relative,0,-4);
  $class="cartographica\\tests\\".str_replace("/","\\",$relative);
  if (!class_exists($class))
  {
    test_result_message("SKIP",$class);
    continue;
  }
  $test=new $class();
  $results=$test->run();
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

$summaryColor=$failed === 0 ? $pass_code : $fail_code;

test_banner_message("\nSummary: $passed passed, $failed failed, $total total",$summaryColor);

rrmdir($test_data_directory);
