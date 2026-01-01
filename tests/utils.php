<?php

/*
  0 = <reset>
  39 = default
  30 = black
  31 = red
  32 = green
  33 = yellow
  34 = blue
  35 = magenta
  36 = cyan
  37 = light gray
  90 = dark gray
  91 = light red
  92 = light green
  93 = light yellow
  94 = light blue
  95 = light magenta
  96 = light cyan
  97 = white
*/

function test_result_message($result,$class,$message="")
{
  global $pass_code;
  global $fail_code;
  $skip_code=33;
  switch ($result)
  {
    case "SKIP":
      echo "\033[".$skip_code."m[SKIP]\033[0m Could not find class $class\n";
      break;
    case "PASS":
      echo "\033[".$pass_code."m[PASS]\033[0m $class - $message\n";
      break;
    case "FAIL":
      echo "\033[".$fail_code."m[FAIL]\033[0m $class - $message\n";
      break;
  }
}

function test_banner_message($message,$code)
{
  echo "\033[".$code."m".$message."\033[0m".PHP_EOL;
}

function rrmdir(string $dir): void
{
  if (!is_dir($dir))
  {
    return;
  }
  $items = scandir($dir);
  foreach ($items as $item)
  {
    if ($item === '.' || $item === '..')
    {
      continue;
    }
    $path=$dir.DIRECTORY_SEPARATOR.$item;
    if (is_dir($path))
    {
      rrmdir($path);
    }
    else
    {
      unlink($path);
    }
  }
  rmdir($dir);
}

function copy_files(string $sourceDir, string $destDir): void
{
  foreach (glob($sourceDir . '/*') as $file)
  {
    if (is_file($file))
    {
      $filename = basename($file);
      copy($file, $destDir . '/' . $filename);
    }
  }
}
