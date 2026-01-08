<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/socket_client.php';

$DATA_PATH = realpath(__DIR__ . '/../../../cartographica_data/services/supervisor');
$logDir    = $DATA_PATH . '/logs';

$id    = $_GET['id'] ?? null;
$lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 200;

if (!$id) {
    json_response(['ok' => false, 'error' => 'missing_id'], 400);
}

$logFile = $logDir . '/' . $id . '.log';
if (!file_exists($logFile)) {
    json_response(['ok' => false, 'error' => 'log_not_found'], 404);
}

$content = tailFile($logFile, $lines);

json_response(['ok' => true, 'id' => $id, 'lines' => $lines, 'log' => $content]);

function tailFile(string $file, int $lines): array
{
    $f = fopen($file, 'r');
    if (!$f) return [];

    $buffer = '';
    $pos = -1;
    $lineCount = 0;
    $result = [];

    fseek($f, 0, SEEK_END);
    while ($lineCount <= $lines && ftell($f) > 0) {
        fseek($f, $pos, SEEK_END);
        $char = fgetc($f);
        if ($char === "\n") {
            if ($buffer !== '') {
                $result[] = strrev($buffer);
                $buffer = '';
                $lineCount++;
            }
        } else {
            $buffer .= $char;
        }
        $pos--;
    }

    if ($buffer !== '') {
        $result[] = strrev($buffer);
    }

    fclose($f);

    return array_reverse($result);
}
