<?php
// Simple child that sleeps long enough to be killed by tests
$seconds = isset($argv[1]) ? (int)$argv[1] : 60;
// On POSIX, spawn a grandchild to test recursive kill
if (strtoupper(substr(PHP_OS,0,3)) !== 'WIN') {
    // spawn a detached child
    if (pcntl_fork() == 0) {
        // child
        sleep($seconds);
        exit(0);
    }
}
sleep($seconds);
