<?php

namespace Cartographica\Supervisor;

class IpcServer
{
    private string $host;
    private int $port;
    private $serverSocket;
    private Logger $logger;
    private $handler; // callable: function(array $request): array

    public function __construct(string $host, int $port, callable $handler, Logger $logger)
    {
        $this->host = $host;
        $this->port = $port;
        $this->handler = $handler;
        $this->logger = $logger;

        $this->initServer();
    }

    private function initServer(): void
    {
        $address = "tcp://{$this->host}:{$this->port}";
        $this->serverSocket = stream_socket_server($address, $errno, $errstr);

        if ($this->serverSocket === false) {
            throw new \RuntimeException("IPC server failed to bind to $address: $errstr");
        }

        stream_set_blocking($this->serverSocket, false);

        $this->logger->info("IPC TCP server listening on {$this->host}:{$this->port}");
    }

    /**
     * Called once per supervisor tick.
     * Non-blocking. Handles at most one client per call.
     */
    public function tick(): void
    {
        $read = [$this->serverSocket];
        $write = null;
        $except = null;

        $changed = @stream_select($read, $write, $except, 0);

        if ($changed === false) {
            $this->logger->error("IPC server: stream_select() failed");
            return;
        }

        if ($changed > 0) {
            $client = @stream_socket_accept($this->serverSocket, 0);

            if ($client !== false && $client !== null) {
                $this->handleClient($client);
            }
        }
    }

    private function handleClient($client): void
    {
        stream_set_blocking($client, false);

        $line = fgets($client);

        if ($line === false || trim($line) === '') {
            fclose($client);
            return;
        }

        $line = trim($line);

        $request = json_decode($line, true);

        if (!is_array($request)) {
            $this->sendResponse($client, [
                "ok" => false,
                "error" => "invalid_json",
                "raw" => $line
            ]);
            fclose($client);
            return;
        }

        // Delegate to handler (SupervisorCore)
        try {
            $response = call_user_func($this->handler, $request);
        } catch (\Throwable $e) {
            $response = [
                "ok" => false,
                "error" => "exception",
                "message" => $e->getMessage()
            ];
        }

        $this->sendResponse($client, $response);

        fclose($client);
    }

    private function sendResponse($client, array $response): void
    {
        $json = json_encode($response, JSON_UNESCAPED_SLASHES);
        fwrite($client, $json . "\n");
    }
}
