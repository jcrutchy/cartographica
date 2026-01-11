<?php

namespace cartographica\tests\supervisor;

use cartographica\tests\TestCase;

class IPCIntegrationTest extends TestCase
{
    protected function test(): void
    {
        $php = 'c:/php/php';
        $script = realpath(__DIR__ . '/dummy_ipc_server.php');

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        // --------------------
        // Responsive IPC server
        // --------------------
        $cmd1 = "\"$php\" \"$script\" respond 9001";
        $proc1 = proc_open($cmd1, $descriptors, $pipes1);
        if (!is_resource($proc1)) {
            $this->assertTrue(false, 'Failed to start dummy IPC server (respond)');
            return;
        }

        // Wait for server READY on stdout
        stream_set_blocking($pipes1[1], false);
        $ready = false;
        $start = time();
        while ((time() - $start) < 5) {
            $line = fgets($pipes1[1]);
            if ($line !== false && strpos($line, 'READY') !== false) { $ready = true; break; }
            usleep(100000);
        }
        $this->assertTrue($ready, 'Dummy IPC server failed to bind');

        // Run the start handler script via wrapper to avoid -r quoting issues
        $wrapper = realpath(__DIR__ . '/run_start_handler.php');
        $cmdRun = "\"$php\" \"$wrapper\" island_01";
        exec($cmdRun, $out1, $ret1);
        $output1 = implode("\n", $out1);
        $json1 = json_decode($output1, true);

        $this->assertIsArray($json1, 'Start handler did not return valid JSON: ' . $output1);
        $this->assertTrue($json1['ok'] ?? false, 'Expected ok:true from start handler (responsive IPC)');

        // Clean up
        proc_terminate($proc1);
        proc_close($proc1);

        // --------------------
        // Delayed IPC server (should timeout/return error quickly)
        // --------------------
        $cmd2 = "\"$php\" \"$script\" delay 9001";
        $proc2 = proc_open($cmd2, $descriptors, $pipes2);
        if (!is_resource($proc2)) {
            $this->assertTrue(false, 'Failed to start dummy IPC server (delay)');
            return;
        }

        // Wait for server READY on stdout
        stream_set_blocking($pipes2[1], false);
        $ready = false;
        $start = time();
        while ((time() - $start) < 5) {
            $line = fgets($pipes2[1]);
            if ($line !== false && strpos($line, 'READY') !== false) { $ready = true; break; }
            usleep(100000);
        }
        $this->assertTrue($ready, 'Dummy IPC server failed to bind');

        exec($cmdRun, $out2, $ret2);
        $output2 = implode("\n", $out2);
        $json2 = json_decode($output2, true);

        $this->assertIsArray($json2, 'Start handler (delayed IPC) did not return valid JSON: ' . $output2);
        $this->assertFalse($json2['ok'] ?? true, 'Expected start handler to fail when IPC responds slowly');

        proc_terminate($proc2);
        proc_close($proc2);

        // --------------------
        // Stop handler tests
        // --------------------
        $cmd3 = "\"$php\" \"$script\" respond 9001";
        $proc3 = proc_open($cmd3, $descriptors, $pipes3);
        if (!is_resource($proc3)) {
            $this->assertTrue(false, 'Failed to start dummy IPC server (respond) for stop');
            return;
        }

        // Wait for server READY on stdout
        stream_set_blocking($pipes3[1], false);
        $ready = false;
        $start = time();
        while ((time() - $start) < 5) {
            $line = fgets($pipes3[1]);
            if ($line !== false && strpos($line, 'READY') !== false) { $ready = true; break; }
            usleep(100000);
        }
        $this->assertTrue($ready, 'Dummy IPC server failed to bind');

        $wrapperStop = realpath(__DIR__ . '/run_stop_handler.php');
        $cmdRunStop = "\"$php\" \"$wrapperStop\" island_01";
        // Try the stop handler up to a few times to mitigate transient race conditions
        $attempts = 0;
        $json3 = null;
        $server_out = '';
        while ($attempts++ < 3) {
            $out3 = [];
            exec($cmdRunStop, $out3, $ret3);
            $output3 = implode("\n", $out3);
            $json3 = json_decode($output3, true);

            // capture server stdout for debugging
            stream_set_blocking($pipes3[1], false);
            $server_out = '';
            $start = time();
            while ((time() - $start) < 2) {
                $line = fgets($pipes3[1]);
                if ($line !== false) { $server_out .= $line; }
                usleep(100000);
            }

            if (is_array($json3) && ($json3['ok'] ?? false)) {
                break; // success
            }

            usleep(100000);
        }

        $this->assertIsArray($json3, 'Stop handler did not return valid JSON: ' . ($output3 ?? '') . ' | server_out: ' . $server_out);
        $this->assertTrue($json3['ok'] ?? false, 'Expected ok:true from stop handler (responsive IPC) | server_out: ' . $server_out);

        proc_terminate($proc3);
        proc_close($proc3);

        // Delayed stop test
        $cmd4 = "\"$php\" \"$script\" delay 9001";
        $proc4 = proc_open($cmd4, $descriptors, $pipes4);
        if (!is_resource($proc4)) {
            $this->assertTrue(false, 'Failed to start dummy IPC server (delay) for stop');
            return;
        }

        // Wait for server READY on stdout
        stream_set_blocking($pipes4[1], false);
        $ready = false;
        $start = time();
        while ((time() - $start) < 5) {
            $line = fgets($pipes4[1]);
            if ($line !== false && strpos($line, 'READY') !== false) { $ready = true; break; }
            usleep(100000);
        }
        $this->assertTrue($ready, 'Dummy IPC server failed to bind');

        exec($cmdRunStop, $out4, $ret4);
        $output4 = implode("\n", $out4);
        $json4 = json_decode($output4, true);

        $this->assertIsArray($json4, 'Stop handler (delayed IPC) did not return valid JSON: ' . $output4);
        $this->assertFalse($json4['ok'] ?? true, 'Expected stop handler to fail when IPC responds slowly');

        proc_terminate($proc4);
        proc_close($proc4);
    }
}
