<?php

// ------------------------------------------------------------
// Cartographica Cluster Supervisor (MVP with TCP control)
// ------------------------------------------------------------

declare(strict_types=1);

$DATA_PATH = realpath(__DIR__ . '/../../cartographica_data/services/cluster');
if (!$DATA_PATH) {
    echo "ERROR: Could not locate cartographica_data/services/cluster\n";
    exit(1);
}

$configFile = $DATA_PATH . '/config.json';
if (!file_exists($configFile)) {
    echo "ERROR: Missing config.json in cluster data folder\n";
    exit(1);
}

$config = json_decode(file_get_contents($configFile), true);
if (!$config) {
    echo "ERROR: Failed to parse config.json\n";
    exit(1);
}

$stateFile = $DATA_PATH . '/cluster_state.json';
$logDir    = $DATA_PATH . '/logs';
$pidDir    = $DATA_PATH . '/pids';

if (!is_dir($logDir)) mkdir($logDir, 0777, true);
if (!is_dir($pidDir)) mkdir($pidDir, 0777, true);

$processes   = []; // id => [proc, pid, port, status, started_at]
$startTimes  = []; // id => timestamp
$worldGraphs = loadWorldGraphs($DATA_PATH);

// ------------------------------------------------------------
// World graph loading (for positions)
// ------------------------------------------------------------
function loadWorldGraphs(string $DATA_PATH): array
{
    $worldsDir = $DATA_PATH . '/worlds';
    $graphs = [];

    if (!is_dir($worldsDir)) {
        return $graphs;
    }

    foreach (glob($worldsDir . '/*.json') as $file) {
        $data = json_decode(file_get_contents($file), true);
        if (!isset($data['id'])) continue;
        $graphs[$data['id']] = $data;
    }

    return $graphs;
}

function getIslandPosition(array $worldGraphs, string $worldId, string $islandId): ?array
{
    if (!isset($worldGraphs[$worldId])) return null;
    $world = $worldGraphs[$worldId];

    if (isset($world['cached_positions'][$islandId])) {
        return $world['cached_positions'][$islandId];
    }

    return null;
}

// ------------------------------------------------------------
// Island process management
// ------------------------------------------------------------
function startIsland(array $island, string $worldId, string $DATA_PATH, array &$processes, array &$startTimes): void
{
    $id         = $island['id'];
    $port       = $island['port'];
    $configPath = $DATA_PATH . '/' . $island['config'];

    $logDir = $DATA_PATH . '/logs';
    $pidDir = $DATA_PATH . '/pids';

    if (!is_dir($logDir)) mkdir($logDir, 0777, true);
    if (!is_dir($pidDir)) mkdir($pidDir, 0777, true);

    $logFile = $logDir . "/{$id}.log";

    // TODO: adjust path to your actual island entrypoint
    $islandEntrypoint = __DIR__ . "/../island/island.php";

    $cmd = "php " . escapeshellarg($islandEntrypoint) .
        " --config " . escapeshellarg($configPath) .
        " --port " . escapeshellarg((string)$port);

    $descriptors = [
        0 => ["pipe", "r"],
        1 => ["file", $logFile, "a"],
        2 => ["file", $logFile, "a"]
    ];

    $proc = proc_open($cmd, $descriptors, $pipes);

    if (!is_resource($proc)) {
        fwrite(STDERR, "Failed to start island {$id}\n");
        return;
    }

    $status = proc_get_status($proc);
    $pid    = $status['pid'];

    file_put_contents($pidDir . "/{$id}.pid", (string)$pid);

    $processes[$id] = [
        'proc'       => $proc,
        'pipes'      => $pipes,
        'port'       => $port,
        'pid'        => $pid,
        'status'     => 'running',
        'world_id'   => $worldId,
        'last_check' => time(),
    ];

    $startTimes[$id] = time();
}

function stopIsland(string $id, array &$processes): void
{
    if (!isset($processes[$id])) return;

    $proc = $processes[$id]['proc'];
    @proc_terminate($proc);
    @proc_close($proc);

    $processes[$id]['status'] = 'stopped';
}

function restartIsland(array $island, string $worldId, string $DATA_PATH, array &$processes, array &$startTimes): void
{
    stopIsland($island['id'], $processes);
    startIsland($island, $worldId, $DATA_PATH, $processes, $startTimes);
}

// ------------------------------------------------------------
// Initial start of all islands
// ------------------------------------------------------------
foreach ($config['worlds'] as $worldId => $world) {
    foreach ($world['islands'] as $island) {
        startIsland($island, $worldId, $DATA_PATH, $processes, $startTimes);
    }
}

// ------------------------------------------------------------
// TCP command server setup
// ------------------------------------------------------------
$address = '127.0.0.1';
$port    = 9400;

$server = @stream_socket_server("tcp://{$address}:{$port}", $errno, $errstr);
if (!$server) {
    fwrite(STDERR, "ERROR: Could not create TCP server: {$errstr} ({$errno})\n");
    exit(1);
}
stream_set_blocking($server, false);

$clients = [];

// ------------------------------------------------------------
// Helpers
// ------------------------------------------------------------
function formatUptime(int $seconds): string
{
    return gmdate("H:i:s", $seconds);
}

function writeClusterState(
    string $stateFile,
    array $config,
    array $processes,
    array $startTimes,
    array $worldGraphs
): void {
    $worldStates = [];

    foreach ($config['worlds'] as $worldId => $worldConfig) {
        $islandsState = [];

        foreach ($worldConfig['islands'] as $islandCfg) {
            $id   = $islandCfg['id'];
            $port = $islandCfg['port'];

            $procInfo = $processes[$id] ?? null;
            $status   = $procInfo['status'] ?? 'not_started';
            $pid      = $procInfo['pid'] ?? null;
            $uptime   = isset($startTimes[$id]) ? (time() - $startTimes[$id]) : 0;

            $position = getIslandPosition($worldGraphs, $worldId, $id);

            $islandsState[] = [
                'id'       => $id,
                'port'     => $port,
                'pid'      => $pid,
                'status'   => $status,
                'uptime'   => $uptime,
                'position' => $position,
            ];
        }

        $worldStates[] = [
            'id'      => $worldId,
            'name'    => $worldConfig['name'] ?? $worldId,
            'islands' => $islandsState,
        ];
    }

    $state = [
        'updated_at' => time(),
        'worlds'     => $worldStates,
    ];

    file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT));
}

// ------------------------------------------------------------
// Main loop: process supervision + TCP handling
// ------------------------------------------------------------
echo "Cluster supervisor running on {$address}:{$port}\n";

while (true) {
    // Accept new clients
    $newClient = @stream_socket_accept($server, 0);
    if ($newClient) {
        stream_set_blocking($newClient, false);
        $clients[] = $newClient;
    }

    // Read from clients
    foreach ($clients as $idx => $client) {
        $data = @fgets($client);
        if ($data === false) {
            continue;
        }

        $data = trim($data);
        if ($data === '') {
            continue;
        }

        $request = json_decode($data, true);
        if (!$request || !isset($request['command'])) {
            fwrite($client, json_encode(['ok' => false, 'error' => 'invalid_request']) . "\n");
            continue;
        }

        $response = handleCommand($request, $config, $DATA_PATH, $processes, $startTimes);
        fwrite($client, json_encode($response) . "\n");
    }

    // Update process statuses, restart crashed
    foreach ($config['worlds'] as $worldId => $world) {
        foreach ($world['islands'] as $island) {
            $id = $island['id'];

            if (!isset($processes[$id])) {
                continue;
            }

            $proc   = $processes[$id]['proc'];
            $status = proc_get_status($proc);

            if (!$status['running'] && $processes[$id]['status'] === 'running') {
                // crashed
                $processes[$id]['status'] = 'crashed';
                // auto-restart
                restartIsland($island, $worldId, $DATA_PATH, $processes, $startTimes);
            }
        }
    }

    // Persist state
    writeClusterState($stateFile, $config, $processes, $startTimes, $worldGraphs);

    usleep(200000); // 0.2s
}

// ------------------------------------------------------------
// Command handler
// ------------------------------------------------------------
function handleCommand(
    array $request,
    array $config,
    string $DATA_PATH,
    array &$processes,
    array &$startTimes
): array {
    $cmd = $request['command'];

    switch ($cmd) {
        case 'start':
        case 'stop':
        case 'restart':
            if (empty($request['island'])) {
                return ['ok' => false, 'error' => 'missing_island'];
            }
            $islandId = $request['island'];

            // locate island in config
            foreach ($config['worlds'] as $worldId => $world) {
                foreach ($world['islands'] as $islandCfg) {
                    if ($islandCfg['id'] === $islandId) {
                        if ($cmd === 'start') {
                            startIsland($islandCfg, $worldId, $DATA_PATH, $processes, $startTimes);
                            return ['ok' => true, 'message' => "started {$islandId}"];
                        } elseif ($cmd === 'stop') {
                            stopIsland($islandId, $processes);
                            return ['ok' => true, 'message' => "stopped {$islandId}"];
                        } elseif ($cmd === 'restart') {
                            restartIsland($islandCfg, $worldId, $DATA_PATH, $processes, $startTimes);
                            return ['ok' => true, 'message' => "restarted {$islandId}"];
                        }
                    }
                }
            }

            return ['ok' => false, 'error' => 'unknown_island'];

        case 'ping':
            return ['ok' => true, 'message' => 'pong'];

        default:
            return ['ok' => false, 'error' => 'unknown_command'];
    }
}
