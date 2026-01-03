<?php

class WebSocketServer
{
    /** @var resource */
    private $server;

    /** @var array<int, resource> */
    private array $clients = [];

    /** @var array<int, array> */
    private array $info = [];

    private int $nextId = 1;
    private int $maxMessageBytes;
    private int $tickMs;
    private array $subprotocols;

    /** @var callable|null */
    public $onOpen = null;

    /** @var callable|null */
    public $onMessage = null;

    /** @var callable|null */
    public $onClose = null;

    /** @var callable|null */
    public $onTick = null;

    public function __construct(string $host, int $port, array $options = [])
    {
        $this->maxMessageBytes = $options['max_message_bytes'] ?? (8 * 1024 * 1024);
        $this->tickMs          = $options['tick_ms'] ?? 100;
        $this->subprotocols    = (isset($options['subprotocols']) && is_array($options['subprotocols']))
            ? $options['subprotocols']
            : [];

        $backlog = $options['backlog'] ?? 128;

        $errno = 0;
        $errstr = '';
        $this->server = @stream_socket_server(
            'tcp://' . $host . ':' . $port,
            $errno,
            $errstr,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            stream_context_create([
                'socket' => [
                    'backlog' => $backlog,
                ],
            ])
        );

        if (!$this->server) {
            throw new \RuntimeException("Failed to create server socket: $errstr ($errno)");
        }

        stream_set_blocking($this->server, false);
    }

    public function run(): void
    {
        $lastTick = (int)(hrtime(true) / 1_000_000);

        while (true) {
            $read   = [$this->server];
            $write  = [];
            $except = [];

            foreach ($this->clients as $cid => $sock) {
                $read[] = $sock;
            }

            $timeoutSec  = 0;
            $timeoutUsec = (int)($this->tickMs * 1000);
            $numChanged  = @stream_select($read, $write, $except, $timeoutSec, $timeoutUsec);

            if ($numChanged === false) {
                // ignore select error; continue
            }

            foreach ($read as $sock) {
                if ($sock === $this->server) {
                    $this->acceptClient();
                } else {
                    $cid = $this->findClientId($sock);
                    if ($cid === 0) {
                        continue;
                    }
                    $this->readFromClient($cid);
                }
            }

            $now = (int)(hrtime(true) / 1_000_000);
            if ($now - $lastTick >= $this->tickMs) {
                $lastTick = $now;
                if ($this->onTick) {
                    ($this->onTick)();
                }
            }
        }
    }

    // -----------------------
    // Public send helpers
    // -----------------------

    public function send(int $clientId, string|array $msg): void
    {
        if (!isset($this->clients[$clientId]) || !is_resource($this->clients[$clientId])) {
            error_log("ws send: invalid client $clientId");
            return;
        }

        if (is_array($msg)) {
            $msg = json_encode($msg);
        }

        $frame = $this->frameText($msg);
        $this->safeWrite($this->clients[$clientId], $frame);
        Logger::info("Server → Client #$clientId: $msg");
    }

    public function broadcast(string|array $msg, ?int $excludeId = null): void
    {
        if (is_array($msg)) {
            $msg = json_encode($msg);
        }

        $frame = $this->frameText($msg);

        foreach ($this->clients as $cid => $sock) {
            if ($excludeId !== null && $cid === $excludeId) {
                continue;
            }
            if (!is_resource($sock)) {
                continue;
            }
            $this->safeWrite($sock, $frame);
        }
    }

    // -----------------------
    // Internal: accept / read
    // -----------------------

    private function acceptClient(): void
    {
        $client = @stream_socket_accept($this->server, 0);
        if (!$client) {
            return;
        }

        stream_set_blocking($client, false);

        $cid = $this->nextId++;
        $this->clients[$cid] = $client;
        $this->info[$cid] = [
            'buffer'      => '',
            'handshaked'  => false,
            'fragOpcode'  => null,
            'fragBuffer'  => '',
            'closing'     => false,
            'maskExpected'=> true,
            'protocol'    => null,
        ];
        Logger::info("Client #$cid connected");
    }

    private function readFromClient(int $cid): void
    {
        $sock  = $this->clients[$cid] ?? null;
        if (!$sock || !is_resource($sock)) {
            return;
        }

        $chunk = @fread($sock, 8192);
        if ($chunk === false) {
            $this->closeClient($cid, null);
            return;
        }

        if ($chunk === '') {
            if (feof($sock)) {
                $this->closeClient($cid, null);
            }
            return;
        }

        $this->info[$cid]['buffer'] .= $chunk;

        if (!$this->info[$cid]['handshaked']) {
            if ($this->processHandshake($cid)) {
                $this->info[$cid]['handshaked'] = true;
                Logger::info("Client #$cid handshake complete");
                if ($this->onOpen) {
                    ($this->onOpen)($cid, ['protocol' => $this->info[$cid]['protocol']]);
                }
            }
        }

        if ($this->info[$cid]['handshaked']) {
            $this->processFrames($cid);
        }
    }

    private function findClientId($sock): int
    {
        foreach ($this->clients as $cid => $rs) {
            if ($rs === $sock) {
                return $cid;
            }
        }
        return 0;
    }

    private function closeClient(int $cid, ?int $code): void
    {
        if (!isset($this->clients[$cid])) {
            return;
        }

        $sock = $this->clients[$cid];

        if (is_resource($sock)) {
            @fclose($sock);
        }

        unset($this->clients[$cid], $this->info[$cid]);

        if ($this->onClose) {
            ($this->onClose)($cid, $code);
        }
    }

    // -----------------------
    // Frame processing
    // -----------------------

    private function processFrames(int $cid): void
    {
        $sock = $this->clients[$cid];
        $buf  = $this->info[$cid]['buffer'];
    
        while (strlen($buf) >= 2) {
    
            // Decode frame
            try {
                $decoded = $this->decodeFrame($buf, true);
            } catch (InvalidArgumentException $e) {
                $msg = $e->getMessage();
                $isTrunc = (strpos($msg, 'Truncated frame') !== false);
    
                if ($isTrunc) {
                    break; // wait for more data
                }
    
                // Protocol error
                $this->sendCloseFrame($sock, 1002, 'Protocol error');
                $this->closeClient($cid, 1002);
                return;
            }
    
            // Consume frame from buffer
            $buf = substr($buf, $decoded['frame_len']);
            $this->info[$cid]['buffer'] = $buf;
    
            $opcode  = $decoded['opcode'];
            $fin     = $decoded['fin'];
            $payload = $decoded['payload'];
    
            // -------------------------
            // PING → PONG
            // -------------------------
            if ($opcode === 0x9) {
                $pong = $this->encodeFrame($payload, 0xA, true);
                $this->safeWrite($sock, $pong);
    
                if ($this->onPing) {
                    ($this->onPing)($cid, $payload);
                }
                continue;
            }
    
            // -------------------------
            // PONG
            // -------------------------
            if ($opcode === 0xA) {
                if ($this->onPong) {
                    ($this->onPong)($cid, $payload);
                }
                continue;
            }
    
            // -------------------------
            // CLOSE
            // -------------------------
            if ($opcode === 0x8) {
                $code   = $decoded['close_code'];
                $reason = $decoded['close_reason'];
    
                // Echo close
                $replyPayload = '';
                if ($code !== null) {
                    $replyPayload = pack('n', $code);
                    if ($reason !== null) {
                        $replyPayload .= $reason;
                    }
                }
    
                $closeFrame = $this->encodeFrame($replyPayload, 0x8, true);
                $this->safeWrite($sock, $closeFrame);
    
                $this->closeClient($cid, $code);
                return;
            }
    
            // -------------------------
            // CONTINUATION FRAME
            // -------------------------
            if ($opcode === 0x0) {
                if ($this->info[$cid]['fragOpcode'] === null) {
                    $this->sendCloseFrame($sock, 1002, 'Unexpected continuation');
                    $this->closeClient($cid, 1002);
                    return;
                }
    
                $this->info[$cid]['fragBuffer'] .= $payload;
    
                if (strlen($this->info[$cid]['fragBuffer']) > $this->maxMessageBytes) {
                    $this->sendCloseFrame($sock, 1009, 'Message too big');
                    $this->closeClient($cid, 1009);
                    return;
                }
    
                if ($fin) {
                    $complete = $this->info[$cid]['fragBuffer'];
                    $origOp   = $this->info[$cid]['fragOpcode'];
    
                    $this->info[$cid]['fragBuffer'] = '';
                    $this->info[$cid]['fragOpcode'] = null;
    
                    if ($origOp === 0x1 && !$this->utf8IsValid($complete)) {
                        $this->sendCloseFrame($sock, 1007, 'Invalid UTF-8');
                        $this->closeClient($cid, 1007);
                        return;
                    }
    
                    // Deliver completed message
                    if ($origOp === 0x1) {
                        $this->handleTextFrame($cid, $complete);
                    } elseif ($origOp === 0x2 && $this->onMessage) {
                        ($this->onMessage)($cid, $complete);
                    }
                }
    
                continue;
            }
    
            // -------------------------
            // TEXT or BINARY FRAME
            // -------------------------
            if ($opcode === 0x1 || $opcode === 0x2) {
    
                // Fragmented start
                if (!$fin) {
                    if ($this->info[$cid]['fragOpcode'] !== null) {
                        $this->sendCloseFrame($sock, 1002, 'Fragmented message in progress');
                        $this->closeClient($cid, 1002);
                        return;
                    }
    
                    $this->info[$cid]['fragOpcode'] = $opcode;
                    $this->info[$cid]['fragBuffer'] = $payload;
    
                    if (strlen($payload) > $this->maxMessageBytes) {
                        $this->sendCloseFrame($sock, 1009, 'Message too big');
                        $this->closeClient($cid, 1009);
                        return;
                    }
    
                    continue;
                }
    
                // Unfragmented TEXT
                if ($opcode === 0x1) {
                    if (!$this->utf8IsValid($payload)) {
                        $this->sendCloseFrame($sock, 1007, 'Invalid UTF-8');
                        $this->closeClient($cid, 1007);
                        return;
                    }
    
                    $this->handleTextFrame($cid, $payload);
                    continue;
                }
    
                // Unfragmented BINARY
                if ($opcode === 0x2 && $this->onMessage) {
                    ($this->onMessage)($cid, $payload);
                    continue;
                }
            }
    
            // -------------------------
            // UNKNOWN OPCODE
            // -------------------------
            $this->sendCloseFrame($sock, 1003, 'Unsupported data');
            $this->closeClient($cid, 1003);
            return;
        }
    }

    private function encodeFrame(string $payload, int $opcode, bool $fin = true): string
    {
        // Server frames MUST be unmasked
        $len = strlen($payload);
        $firstByte = ($fin ? 0x80 : 0x00) | ($opcode & 0x0F);
        $frame = chr($firstByte);
    
        if ($len <= 125) {
            $frame .= chr($len);
        } elseif ($len <= 0xFFFF) {
            $frame .= chr(126) . pack('n', $len);
        } else {
            $hi = ($len >> 32) & 0xFFFFFFFF;
            $lo = $len & 0xFFFFFFFF;
            $frame .= chr(127) . pack('N2', $hi, $lo);
        }
    
        return $frame . $payload;
    }

    private function decodeFrame(string $frame, bool $expectMasked): array
    {
        $total = strlen($frame);
        if ($total < 2) {
            throw new InvalidArgumentException('Truncated frame: need at least 2 bytes');
        }
    
        $b1 = ord($frame[0]);
        $b2 = ord($frame[1]);
    
        $fin    = (($b1 & 0x80) !== 0);
        $rsv1   = (($b1 & 0x40) !== 0) ? 1 : 0;
        $rsv2   = (($b1 & 0x20) !== 0) ? 1 : 0;
        $rsv3   = (($b1 & 0x10) !== 0) ? 1 : 0;
        $opcode = $b1 & 0x0F;
    
        if ($rsv1 || $rsv2 || $rsv3) {
            throw new InvalidArgumentException('RSV bits set without extensions');
        }
    
        $masked = (($b2 & 0x80) !== 0);
        if ($expectMasked && !$masked) {
            throw new InvalidArgumentException('Expected masked frame from client');
        }
    
        $len7 = $b2 & 0x7F;
        $pos = 2;
        $payloadLen = 0;
    
        if ($len7 === 126) {
            if ($total < $pos + 2) {
                throw new InvalidArgumentException('Truncated frame: 16-bit length missing');
            }
            $payloadLen = unpack('n', substr($frame, $pos, 2))[1];
            $pos += 2;
        } elseif ($len7 === 127) {
            if ($total < $pos + 8) {
                throw new InvalidArgumentException('Truncated frame: 64-bit length missing');
            }
            $parts = unpack('N2', substr($frame, $pos, 8));
            $pos += 8;
    
            if (($parts[1] & 0x80000000) !== 0) {
                throw new InvalidArgumentException('Invalid 64-bit length MSB');
            }
    
            $payloadLen = ($parts[1] * 4294967296) + $parts[2];
        } else {
            $payloadLen = $len7;
        }
    
        $maskKey = '';
        if ($masked) {
            if ($total < $pos + 4) {
                throw new InvalidArgumentException('Truncated frame: mask key missing');
            }
            $maskKey = substr($frame, $pos, 4);
            $pos += 4;
        }
    
        if ($total < $pos + $payloadLen) {
            throw new InvalidArgumentException('Truncated frame: payload incomplete');
        }
    
        $payload = substr($frame, $pos, $payloadLen);
        $frameLen = $pos + $payloadLen;
    
        if ($masked && $payloadLen > 0) {
            $unmasked = '';
            for ($i = 0; $i < $payloadLen; $i++) {
                $unmasked .= chr(ord($payload[$i]) ^ ord($maskKey[$i % 4]));
            }
            $payload = $unmasked;
        }
    
        $isControl = ($opcode === 0x8 || $opcode === 0x9 || $opcode === 0xA);
        if ($isControl) {
            if (!$fin) {
                throw new InvalidArgumentException('Control frames must be final');
            }
            if ($payloadLen > 125) {
                throw new InvalidArgumentException('Control frame payload too long');
            }
        }
    
        $closeCode = null;
        $closeReason = null;
    
        if ($opcode === 0x8) {
            if ($payloadLen === 1) {
                throw new InvalidArgumentException('Close payload length 1 invalid');
            }
            if ($payloadLen >= 2) {
                $closeCode = unpack('n', substr($payload, 0, 2))[1];
                $closeReason = substr($payload, 2);
    
                if (!is_valid_close_code($closeCode)) {
                    throw new InvalidArgumentException('Invalid close code');
                }
                if ($closeReason !== '' && !$this->utf8IsValid($closeReason)) {
                    throw new InvalidArgumentException('Invalid UTF-8 in close reason');
                }
            }
        }
    
        return [
            'fin'         => $fin,
            'rsv1'        => $rsv1,
            'rsv2'        => $rsv2,
            'rsv3'        => $rsv3,
            'opcode'      => $opcode,
            'masked'      => $masked,
            'mask_key'    => $maskKey,
            'payload'     => $payload,
            'payload_len' => $payloadLen,
            'frame_len'   => $frameLen,
            'close_code'  => $closeCode,
            'close_reason'=> $closeReason,
        ];
    }

    private function utf8IsValid(string $s): bool
    {
        $len = strlen($s);
        $i = 0;
    
        while ($i < $len) {
            $b = ord($s[$i]);
    
            if ($b <= 0x7F) {
                $i++;
                continue;
            }
    
            if ($b >= 0xC2 && $b <= 0xDF) {
                if ($i + 1 >= $len) return false;
                $b1 = ord($s[$i + 1]);
                if (($b1 & 0xC0) !== 0x80) return false;
                $i += 2;
                continue;
            }
    
            if ($b >= 0xE0 && $b <= 0xEF) {
                if ($i + 2 >= $len) return false;
                $b1 = ord($s[$i + 1]);
                $b2 = ord($s[$i + 2]);
                if (($b1 & 0xC0) !== 0x80 || ($b2 & 0xC0) !== 0x80) return false;
                if ($b === 0xE0 && ($b1 < 0xA0 || $b1 > 0xBF)) return false;
                if ($b === 0xED && ($b1 < 0x80 || $b1 > 0x9F)) return false;
                $i += 3;
                continue;
            }
    
            if ($b >= 0xF0 && $b <= 0xF4) {
                if ($i + 3 >= $len) return false;
                $b1 = ord($s[$i + 1]);
                $b2 = ord($s[$i + 2]);
                $b3 = ord($s[$i + 3]);
                if (($b1 & 0xC0) !== 0x80 || ($b2 & 0xC0) !== 0x80 || ($b3 & 0xC0) !== 0x80) return false;
                if ($b === 0xF0 && ($b1 < 0x90 || $b1 > 0xBF)) return false;
                if ($b === 0xF4 && ($b1 < 0x80 || $b1 > 0x8F)) return false;
                $i += 4;
                continue;
            }
    
            return false;
        }
    
        return true;
    }

    private function handleTextFrame(int $cid, string $payload): void
    {
        Logger::info("Client #$cid → $payload");
        if ($this->onMessage) {
            ($this->onMessage)($cid, $payload);
        }
    }

    private function sendCloseFrame($sock, int $code, string $reason): void
    {
        $payload = pack('n', $code) . $reason;
        $frame   = ws_encode_frame($payload, 0x8, true);
        $this->safeWrite($sock, $frame);
    }

    // -----------------------
    // Handshake
    // -----------------------

    private function processHandshake(int $cid): bool
    {
        $sock   = $this->clients[$cid] ?? null;
        $buffer = $this->info[$cid]['buffer'];

        // Minimal version that assumes entire header is in buffer
        if (!str_contains($buffer, "\r\n\r\n")) {
            return false;
        }

        $parts = explode("\r\n\r\n", $buffer, 2);
        $header = $parts[0];
        $this->info[$cid]['buffer'] = $parts[1];

        $lines = explode("\r\n", $header);
        $requestLine = array_shift($lines);

        $headers = [];
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                [$k, $v] = explode(':', $line, 2);
                $headers[strtolower(trim($k))] = trim($v);
            }
        }

        if (
            !isset($headers['upgrade']) ||
            strtolower($headers['upgrade']) !== 'websocket' ||
            !isset($headers['sec-websocket-key'])
        ) {
            $this->closeClient($cid, null);
            return false;
        }

        $key = $headers['sec-websocket-key'];
        $accept = base64_encode(
            sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true)
        );

        $protocolHeader = '';
        if (!empty($this->subprotocols) && isset($headers['sec-websocket-protocol'])) {
            $clientProtocols = array_map('trim', explode(',', $headers['sec-websocket-protocol']));
            foreach ($clientProtocols as $cp) {
                if (in_array($cp, $this->subprotocols, true)) {
                    $this->info[$cid]['protocol'] = $cp;
                    $protocolHeader = "Sec-WebSocket-Protocol: $cp\r\n";
                    break;
                }
            }
        }

        $response = "HTTP/1.1 101 Switching Protocols\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Accept: $accept\r\n"
            . $protocolHeader
            . "\r\n";

        $this->safeWrite($sock, $response);

        return true;
    }

    // -----------------------
    // Framing + I/O
    // -----------------------

    private function frameText(string $payload): string
    {
        $len = strlen($payload);
        $b1 = 0x81; // FIN + text

        if ($len <= 125) {
            return chr($b1) . chr($len) . $payload;
        } elseif ($len <= 65535) {
            return chr($b1) . chr(126) . pack('n', $len) . $payload;
        } else {
            return chr($b1) . chr(127) . pack('J', $len) . $payload;
        }
    }

    private function safeWrite($sock, string $data): void
    {
        if (!is_resource($sock)) {
            return;
        }

        $total   = strlen($data);
        $written = 0;

        while ($written < $total) {
            $n = @fwrite($sock, substr($data, $written));
            if ($n === false) {
                break;
            }
            if ($n === 0) {
                usleep(1000);
            } else {
                $written += $n;
            }
        }
    }
}
