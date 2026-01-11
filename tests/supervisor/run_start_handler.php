<?php
// Wrapper to run the start handler with $_GET['id'] set from CLI
$_GET['id'] = $argv[1] ?? 'island_01';
include __DIR__ . '/../../services/supervisor/rest-api/handlers/start.php';
