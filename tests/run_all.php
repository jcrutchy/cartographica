<?php

$test_raw=dirname(__DIR__)."_data/_testdata";
// realpath only works if directory exists
if (!is_dir($test_raw)) {
    mkdir($test_raw, 0777, true);
};
define("CARTOGRAPHICA_DATA_DIR",realpath($test_raw));

function rrmdir(string $dir): void {
    if (!is_dir($dir)) {
        return;
    }

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            rrmdir($path);
        } else {
            unlink($path);
        }
    }

    rmdir($dir);
}

if (is_dir(CARTOGRAPHICA_DATA_DIR)) {
    rrmdir(CARTOGRAPHICA_DATA_DIR);
}

mkdir(CARTOGRAPHICA_DATA_DIR."/services/island", 0777, true);
mkdir(CARTOGRAPHICA_DATA_DIR."/services/identity", 0777, true);

define("CARTOGRAPHICA_DEV_DATA_DIR",realpath(dirname(__DIR__)."_data"));

copy(CARTOGRAPHICA_DEV_DATA_DIR."/services/island/island_config.json",
    CARTOGRAPHICA_DATA_DIR."/services/island/island_config.json"
);

require __DIR__ . "/../share/Autoload.php";

use cartographica\share\Keys;
use cartographica\services\identity\Config as IdentityConfig;
use cartographica\services\island\Config as IslandConfig;

Keys::ensure(
    IdentityConfig::privateKey(),
    IdentityConfig::publicKey()
);

copy(CARTOGRAPHICA_DEV_DATA_DIR."/services/identity/identity_private.pem",CARTOGRAPHICA_DATA_DIR."/services/identity/identity_private.pem");
copy(CARTOGRAPHICA_DEV_DATA_DIR."/services/identity/identity_public.pem",CARTOGRAPHICA_DATA_DIR."/services/identity/identity_public.pem");

$base = __DIR__;
$testFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($base)
);

$total = 0;
$passed = 0;
$failed = 0;

$GREEN  = "\033[32m";
$RED    = "\033[31m";
$YELLOW = "\033[33m";
$BLUE   = "\033[34m";
$RESET  = "\033[0m";

foreach ($testFiles as $file) {
    if (!str_ends_with($file, "Test.php")) {
        continue;
    }

    require_once $file;

    $relative = str_replace($base, "", $file);
    $relative = str_replace("\\", "/", $relative);
    $relative = ltrim($relative, "/");
    $relative = substr($relative, 0, -4);

    $class = "cartographica\\tests\\" . str_replace("/", "\\", $relative);

    if (!class_exists($class)) {
        echo "{$YELLOW}[SKIP]{$RESET} Could not find class $class\n";
        continue;
    }

    $test = new $class();
    $results = $test->run();

    foreach ($results as $result) {
        $total++;

        if ($result["ok"]) {
            $passed++;
            echo "{$GREEN}[PASS]{$RESET} $class - {$result['message']}\n";
        } else {
            $failed++;
            echo "{$RED}[FAIL]{$RESET} $class - {$result['message']}\n";
        }
    }
}


$summaryColor = $failed === 0 ? $GREEN : $RED;

echo "\n{$summaryColor}Summary: $passed passed, $failed failed, $total total{$RESET}\n";

