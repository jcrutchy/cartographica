<?php
// Minimal Logger stub used by IslandProcess
namespace Cartographica\Supervisor {
    class Logger {
        public function info($m) {}
        public function warning($m) {}
        public function error($m) {}
    }
}

// revert to global namespace for the test harness
namespace {
    file_put_contents(__DIR__ . '/debug_islandproc.txt', "started\n");
    echo "RUNNING IslandProcessTest\n";
    require_once(__DIR__ . '/../../services/supervisor/daemon/core/IslandProcess.php');
    require_once(__DIR__ . '/child_sleep.php');
    file_put_contents(__DIR__ . '/debug_islandproc.txt', "after require\n", FILE_APPEND);

use Cartographica\Supervisor\IslandProcess;

$php = PHP_BINARY;
$child = __DIR__ . '/child_sleep.php';
$cmd = escapeshellcmd($php) . ' ' . escapeshellarg($child) . ' 60';

$logger = new \Cartographica\Supervisor\Logger();
$proc = new IslandProcess('test', $cmd, __DIR__, $logger);
$ok = $proc->start();
if (!$ok) { echo "FAIL: start returned false\n"; exit(2); }

$running = $proc->isRunning();
echo "isRunning after start: ".($running ? 'yes' : 'no')."\n";

$pid = $proc->getPid();
echo "pid: " . var_export($pid, true) . "\n";
if (!$pid) { echo "FAIL: no pid after start\n"; exit(2); }

echo "Started island pid: $pid\n";

$proc->stop(1);

echo "stop() called\n";

usleep(200000);

$runningAfter = $proc->isRunning();
echo "isRunning after stop: ".($runningAfter ? 'yes' : 'no')."\n";

if ($runningAfter) { echo "FAIL: process still running after stop\n"; exit(2); }

echo "OK\n";
}

