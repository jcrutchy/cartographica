<?php

/*
supervisor.php
- Parse CLI args (if any)
- Load config
- Construct SupervisorCore + IpcServer + ProcessManager
- Call SupervisorCore->run()
*/

require_once __DIR__."/../../bootstrap.php";

require __DIR__."/config.php";
require __DIR__."/lib/Logger.php";
require __DIR__."/core/IslandDefinition.php";
require __DIR__."/core/IslandProcess.php";
require __DIR__."/core/IslandRegistry.php";
require __DIR__."/core/ProcessManager.php";
require __DIR__."/core/IpcServer.php";
require __DIR__."/core/SupervisorCore.php";

use Cartographica\Supervisor\Config;
use Cartographica\Supervisor\Logger;
use Cartographica\Supervisor\IslandRegistry;
use Cartographica\Supervisor\ProcessManager;
use Cartographica\Supervisor\IpcServer;
use Cartographica\Supervisor\SupervisorCore;

define("CARTOGRAPHICA_DATA", $bootstrap["data_root"]);

$config_filename = CARTOGRAPHICA_DATA . "/services/supervisor/daemon_config.json";
$config = new Config($config_filename, $bootstrap);

$logger = new Logger($config->getLogFile());

// Registry now requires logger
$registry = IslandRegistry::fromConfig($config, $logger);

// ProcessManager unchanged except for new IslandProcess API
$processManager = new ProcessManager($registry, $logger);

// IpcServer no longer runs its own infinite loop
$ipcServer = new IpcServer(
    $config->getIpcHost(),
    $config->getIpcPort(),
    function(array $req) use (&$supervisor) {
        return $supervisor->handle($req);
    },
    $logger
);

// SupervisorCore now owns the main loop and calls ipc->tick()
$supervisor = new SupervisorCore($processManager, $ipcServer, $logger);

// Start the unified supervisor loop
$supervisor->run();
