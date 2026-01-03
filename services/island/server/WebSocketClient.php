<?php

class WebSocketClient
{
    private $sock;
    private string $buffer = '';
    /** @var callable */
    private $onMessage;

    public function __construct(callable $onMessage)
    {
        $this->onMessage = $onMessage;
    }

    public function connect(string $host, int $port, string $path = "/"): void
    {
        $errno = 0;
        $errstr = '';
        $this->sock = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 5);

        if (!$this->sock) {
            throw new RuntimeException("Failed to connect to $host:$port - $errstr");
        }

        stream_set_blocking($this->sock, true);

        $key = base64_encode(random_bytes(16));
        $headers = "GET {$path} HTTP/1.1\r\n"
                 . "Host: {$host}:{$port}\r\n"
                 . "Upgrade: websocket\r\n"
                 . "Connection: Upgrade\r\n"
                 . "Sec-WebSocket-Key: {$key}\r\n"
                 . "Sec-WebSocket-Version: 13\r\n\r\n";

        fwrite($this->sock, $headers);

        $response = stream_get_contents($this->sock, 1024);
        if ($response === false || strpos($response, "101") === false) {
            throw new RuntimeException("WebSocket handshake failed: " . ($response ?? 'no response'));
        }

        stream_set_blocking($this->sock, false);
    }

    public function send(string $payload): void
    {
        $frame = WebSocketServer::encodeFrameStatic($payload, 0x1);
        fwrite($this->sock, $frame);
    }

    public function tick(): void
    {
        if (!$this->sock) {
            return;
        }

        $data = @fread($this->sock, 8192);
        if ($data === '' || $data === false) {
            return;
        }

        $this->buffer .= $data;

        while (strlen($this->buffer) >= 2) {
            $decoded = WebSocketServer::decodeFrameStatic($this->buffer);
            if ($decoded === null) {
                break;
            }
            $this->buffer = substr($this->buffer, $decoded['frame_len']);

            if ($decoded['opcode'] === 0x1) {
                ($this->onMessage)($decoded['payload']);
            }
        }
    }

    public static function encodeFrameStatic(string $payload, int $opcode = 0x1): string
    {
        $fin = 0x80;
        $maskBit = 0x80;
    
        $payloadLen = strlen($payload);
        $frame = chr($fin | $opcode);
    
        if ($payloadLen < 126) {
            $frame .= chr($maskBit | $payloadLen);
        } elseif ($payloadLen < 65536) {
            $frame .= chr($maskBit | 126) . pack("n", $payloadLen);
        } else {
            $frame .= chr($maskBit | 127) . pack("J", $payloadLen);
        }
    
        $mask = random_bytes(4);
        $frame .= $mask;
    
        $maskedPayload = '';
        for ($i = 0; $i < $payloadLen; $i++) {
            $maskedPayload .= $payload[$i] ^ $mask[$i % 4];
        }
    
        return $frame . $maskedPayload;
    }
    
    
    public static function decodeFrameStatic(string $buffer): ?array
    {
        if (strlen($buffer) < 2) return null;
    
        $byte1 = ord($buffer[0]);
        $byte2 = ord($buffer[1]);
    
        $fin = ($byte1 & 0x80) !== 0;
        $opcode = $byte1 & 0x0f;
        $masked = ($byte2 & 0x80) !== 0;
        $payloadLen = $byte2 & 0x7f;
    
        $offset = 2;
    
        if ($payloadLen === 126) {
            if (strlen($buffer) < 4) return null;
            $payloadLen = unpack("n", substr($buffer, 2, 2))[1];
            $offset = 4;
        } elseif ($payloadLen === 127) {
            if (strlen($buffer) < 10) return null;
            $payloadLen = unpack("J", substr($buffer, 2, 8))[1];
            $offset = 10;
        }
    
        if ($masked) {
            if (strlen($buffer) < $offset + 4) return null;
            $mask = substr($buffer, $offset, 4);
            $offset += 4;
        }
    
        if (strlen($buffer) < $offset + $payloadLen) return null;
    
        $payload = substr($buffer, $offset, $payloadLen);
    
        if ($masked) {
            $decoded = '';
            for ($i = 0; $i < $payloadLen; $i++) {
                $decoded .= $payload[$i] ^ $mask[$i % 4];
            }
            $payload = $decoded;
        }
    
        return [
            "fin" => $fin,
            "opcode" => $opcode,
            "payload" => $payload,
            "frame_len" => $offset + $payloadLen
        ];
    }

}
