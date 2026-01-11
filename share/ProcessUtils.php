<?php

/**
 * Cross-platform process utilities
 * - is_running($pid)
 * - get_child_pids($pid)
 * - graceful_terminate($pid, $timeout)
 * - kill_recursive($pid)
 *
 * On Windows: uses tasklist/taskkill/wmic where available.
 * On POSIX: uses posix_kill and ps.
 */

// Provide SIG constants fallback on systems (e.g., Windows) where they're not defined
if (!defined('SIGKILL')) { define('SIGKILL', 9); }
if (!defined('SIGTERM')) { define('SIGTERM', 15); }

class ProcessUtils
{
    public static function is_windows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    public static function is_running(int $pid): bool
    {
        if ($pid <= 0) return false;
        if (self::is_windows()) {
            $out = [];
            @exec("tasklist /FI \"PID eq $pid\" /FO CSV /NH", $out, $rc);
            if ($rc !== 0) return false;
            if (count($out) === 0) return false;
            $line = trim($out[0]);
            return $line !== '' && strpos($line, 'No tasks') === false;
        } else {
            if (function_exists('posix_kill')) {
                return @posix_kill($pid, 0);
            }
            // fallback: check /proc
            return is_dir("/proc/" . $pid);
        }
    }

    public static function get_child_pids(int $pid): array
    {
        $result = [];
        if ($pid <= 0) return $result;
        if (self::is_windows()) {
            $out = [];
            @exec("wmic process where (ParentProcessId=$pid) get ProcessId 2>nul", $out, $rc);
            if ($rc !== 0) return $result;
            foreach ($out as $line) {
                $line = trim($line);
                if ($line === '' || strtolower($line) === 'processid') continue;
                if (ctype_digit($line)) $result[] = (int)$line;
            }
            return $result;
        } else {
            $out = [];
            @exec("ps -o pid= --ppid " . (int)$pid, $out, $rc);
            if ($rc !== 0) return $result;
            foreach ($out as $line) {
                $line = trim($line);
                if ($line === '') continue;
                if (ctype_digit($line)) $result[] = (int)$line;
            }
            return $result;
        }
    }

    public static function kill_recursive(int $pid, int $signal = SIGKILL): bool
    {
        if ($pid <= 0) return false;
        if (self::is_windows()) {
            // taskkill /T kills the process tree
            @exec("taskkill /PID " . (int)$pid . " /T /F 2>nul", $out, $rc);
            return ($rc === 0);
        } else {
            // recursively kill children first
            $children = self::get_child_pids($pid);
            foreach ($children as $child) {
                self::kill_recursive($child, $signal);
            }
            if (function_exists('posix_kill')) {
                return @posix_kill($pid, $signal);
            }
            return false;
        }
    }

    /**
     * Try graceful termination then force if timeout elapsed.
     */
    public static function graceful_terminate(int $pid, int $timeoutSeconds = 5): bool
    {
        if ($pid <= 0) return false;
        if (!self::is_running($pid)) return true;
        if (self::is_windows()) {
            // try gentle taskkill (no /F)
            @exec("taskkill /PID " . (int)$pid . " 2>nul", $out, $rc);
            $end = microtime(true) + $timeoutSeconds;
            while (microtime(true) < $end) {
                if (!self::is_running($pid)) return true;
                usleep(100000);
            }
            // force kill tree
            return self::kill_recursive($pid);
        } else {
            if (function_exists('posix_kill')) {
                @posix_kill($pid, SIGTERM);
            }
            $end = microtime(true) + $timeoutSeconds;
            while (microtime(true) < $end) {
                if (!self::is_running($pid)) return true;
                usleep(100000);
            }
            // escalate
            return self::kill_recursive($pid, SIGKILL);
        }
    }
}
