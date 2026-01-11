<?php
require_once(__DIR__ . '/../../share/ProcessUtils.php');

function assert_true($cond, $msg='') {
    if (!$cond) {
        echo "FAIL: $msg\n";
        exit(2);
    }
}

// Start a child sleeping process
$php = PHP_BINARY;
$child_script = __DIR__ . '/child_sleep.php';
$cmd = escapeshellcmd($php) . ' ' . escapeshellarg($child_script) . ' 60';
$descriptors = [
    0 => ['pipe','r'],
    1 => ['pipe','w'],
    2 => ['pipe','w']
];
$proc = proc_open($cmd, $descriptors, $pipes);
if (!is_resource($proc)) {
    echo "FAIL: unable to start child process\n";
    exit(2);
}
$status = proc_get_status($proc);
$pid = $status['pid'];

echo "Started child pid: $pid\n";
assert_true(ProcessUtils::is_running($pid), 'child should be running');

// Attempt graceful terminate (short timeout)
$res = ProcessUtils::graceful_terminate($pid, 1);
assert_true($res, 'graceful_terminate returned false');

// give a small grace period
usleep(200000);

assert_true(!ProcessUtils::is_running($pid), 'child should be gone after graceful_terminate');

// cleanup proc resource
@proc_terminate($proc);
@proc_close($proc);

echo "OK\n";
