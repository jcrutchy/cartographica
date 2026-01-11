<?php

namespace Cartographica\Supervisor;

class ProcessManager
{
    private IslandRegistry $registry;
    private Logger $logger;

    public function __construct(IslandRegistry $registry, Logger $logger)
    {
        $this->registry = $registry;
        $this->logger = $logger;
    }

    public function start(string $id): array
    {
        $proc = $this->registry->get($id);
        if (!$proc) {
            return ['ok' => false, 'error' => 'unknown_island'];
        }

        if ($proc->isRunning()) {
            return ['ok' => true, 'already_running' => true];
        }

        $ok = $proc->start();
        return ['ok' => $ok];
    }

    public function stop(string $id, int $timeoutSeconds = 5): array
    {
        $proc = $this->registry->get($id);
        if (!$proc) {
            return ['ok' => false, 'error' => 'unknown_island'];
        }

        if (!$proc->isRunning()) {
            return ['ok' => true, 'already_stopped' => true];
        }

        $proc->stop($timeoutSeconds);
        return ['ok' => true];
    }

    public function list(): array
    {
        $out = [];

        foreach ($this->registry->all() as $proc) {
            $out[] = [
                'id'      => $proc->getId(),
                'running' => $proc->isRunning(),
                'pid'     => $proc->getPid(),
                'cwd'     => $proc->getCwd(),
                'command' => $proc->getCommand(),
            ];
        }

        return $out;
    }

    /**
     * Helper for SupervisorCore: return only running processes
     */
    public function getRunningProcesses(): array
    {
        $running = [];

        foreach ($this->registry->all() as $proc) {
            if ($proc->isRunning()) {
                $running[] = $proc;
            }
        }

        return $running;
    }
}
