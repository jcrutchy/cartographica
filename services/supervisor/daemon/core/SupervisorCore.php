<?php
namespace Cartographica\Supervisor;

class SupervisorCore
{
    private ProcessManager $pm;
    private Logger $logger;
    private IpcServer $ipc;

    public function __construct(ProcessManager $pm, IpcServer $ipc, Logger $logger)
    {
        $this->pm = $pm;
        $this->ipc = $ipc;
        $this->logger = $logger;
    }

    public function run(): void
    {
        $this->logger->info("Supervisor started");

        while (true) {

            // Handle IPC requests
            $this->ipc->tick();

            // Poll running island processes
            foreach ($this->pm->getRunningProcesses() as $proc) {
                $proc->tick();
            }

            // Prevent CPU flogging
            usleep(50000);
        }
    }

    // Called by IpcServer for each request
    public function handle(array $req): array
    {
        $action = $req['action'] ?? null;

        switch ($action) {
            case 'list':
                return [
                    'ok' => true,
                    'islands' => $this->pm->list()
                ];

            case 'start':
                return $this->pm->start($req['island_id'] ?? '');

            case 'stop':
                return $this->pm->stop($req['island_id'] ?? '');

            default:
                return [
                    'ok' => false,
                    'error' => 'unknown_action'
                ];
        }
    }
}
