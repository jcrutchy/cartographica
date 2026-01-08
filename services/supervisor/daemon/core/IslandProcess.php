<?php

namespace Cartographica\Supervisor;

class IslandProcess
{
    private string $id;
    private string $command;
    private string $cwd;
    private Logger $logger;

    private $proc = null;
    private $pipes = [];

    public function __construct(string $id, string $command, string $cwd, Logger $logger)
    {
        $this->id = $id;
        $this->command = $command;
        $this->cwd = $cwd;
        $this->logger = $logger;
    }

    public function getPid(): ?int
    {
        if (!is_resource($this->proc)) return null;
        $status = proc_get_status($this->proc);
        return $status['pid'] ?? null;
    }

    public function write(string $data): void
    {
        if (is_resource($this->pipes[0])) {
            fwrite($this->pipes[0], $data . "\n");
        }
    }

    public function start(): bool
    {
        $cmd = str_replace('/', '\\', $this->command);
        $cwd = str_replace('/', '\\', $this->cwd);
    
        $this->logger->info("Starting island {$this->id}");
        $this->logger->info("  CMD: {$cmd}");
        $this->logger->info("  CWD: {$cwd}");
    
        $descriptorSpec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"],
        ];
    
        $this->proc = @proc_open($cmd, $descriptorSpec, $this->pipes, $cwd);
    
        if (!is_resource($this->proc)) {
            $this->logger->error("Failed to start island {$this->id}");
            return false;
        }
    
        #stream_set_blocking($this->pipes[1], false);
        #stream_set_blocking($this->pipes[2], false);
    
        return true;
    }
    
    public function tick(): void
    {
        if (!is_resource($this->proc)) {
            return;
        }

        $status = proc_get_status($this->proc);
        
        if (!$status['running']) {
            // Clean up pipes
            foreach ($this->pipes as $p) {
                if (is_resource($p)) fclose($p);
            }
        
            proc_close($this->proc);
            $this->proc = null;
        
            $this->logger->info("Island {$this->id} has exited");
        }
    
        $read = [];
        if (isset($this->pipes[1])) $read[] = $this->pipes[1];
        if (isset($this->pipes[2])) $read[] = $this->pipes[2];
    
        $write = null;
        $except = null;
    
        $changed = stream_select($read, $write, $except, 0);
    
        if ($changed === false || $changed === 0) {
            return;
        }
    
        foreach ($read as $pipe) {

            $data=[];
            do
            {
              $buffer=fgets($pipe);
              $this->logger->info($buffer);
              #$buffer=fread($pipe,1024);
              /*if ($buffer===false)
              {
                $this->logger->error("pipe read error",31);
                break;
              }*/
              $data[]=$buffer;
            }
            while (strlen($buffer)>0);
            #$this->logger->info($data);

            /*if ($data !== false && $data !== "") {
                foreach (explode("\n", $data) as $line) {
                    $line = trim($line);
                    if ($line === "") continue;
    
                    if ($pipe === $this->pipes[1]) {
                        $this->logger->info("[{$this->id}] STDOUT: {$line}");
                    } else {
                        $this->logger->error("[{$this->id}] STDERR: {$line}");
                    }
                }
            }*/
        }
    }

    public function isRunning(): bool
    {
        if (!is_resource($this->proc)) {
            return false;
        }

        $status = proc_get_status($this->proc);
        return $status['running'];
    }

    public function stop(): void
    {
        if (!is_resource($this->proc)) {
            return;
        }

        $this->logger->info("Stopping island {$this->id}");

        proc_terminate($this->proc);
        #proc_close($this->proc);

        #$this->proc = null;
    }
}
