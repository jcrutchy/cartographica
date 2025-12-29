<?php
function db() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO("sqlite:" . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $pdo;
}
