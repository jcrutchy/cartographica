<?php

// Attempt to contact supervisor IPC server with a short timeout; if unavailable, fall back to reading island configs from data_root
$socket = @stream_socket_client("tcp://127.0.0.1:9001", $errno, $errstr, 1);
if ($socket) {
    stream_set_timeout($socket, 1);

    $request = json_encode([
        "action" => "status"
    ]);

    fwrite($socket, $request . "\n");

    $response = fgets($socket);
    $data = json_decode($response, true);
    if (is_array($data)) {
        echo json_encode($data);
        fclose($socket);
        exit;
    }
    // otherwise fall through to config-based status
}

// Fallback: read island configs from data_root
$bootstrap = require dirname(__DIR__, 3) . '/bootstrap.php';
$dataRoot = rtrim($bootstrap['data_root'], "\/");
$daemonCfgFile = $dataRoot . '/services/supervisor/daemon_config.json';
// default islands path
$islandsPath = $dataRoot . '/services/island';
if (file_exists($daemonCfgFile)) {
    $daemonCfg = json_decode(file_get_contents($daemonCfgFile), true);
    if (is_array($daemonCfg) && isset($daemonCfg['islands_dir'])) {
        // expand common placeholders
        $islandsPath = str_replace(
            ['{data_root}', '{repo_root}', '{php_cli_exec}'],
            [$bootstrap['data_root'], $bootstrap['repo_root'], $bootstrap['php_cli_exec']],
            $daemonCfg['islands_dir']
        );
    }
}
// Normalize path and ensure it exists
$islandsPath = rtrim(str_replace('\\', '/', $islandsPath), '/');
$result = ['ok' => true, 'islands' => [], 'islands_dir' => $islandsPath];
if (is_dir($islandsPath)) {
    foreach (scandir($islandsPath) as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $cfgFile = $islandsPath . '/' . $entry . '/island_config.json';
        if (file_exists($cfgFile)) {
            $cfg = json_decode(file_get_contents($cfgFile), true);
            if (is_array($cfg)) {
                $result['islands'][] = [
                    'id' => $cfg['island_id'] ?? $entry,
                    'name' => $cfg['name'] ?? '',
                    'port' => $cfg['port'] ?? null,
                    'running' => false,
                    'pid' => null,
                    'cwd' => $cfg['cwd'] ?? null,
                    'config' => $cfg
                ];
            }
        }
    }
}

echo json_encode($result);
