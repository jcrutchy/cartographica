<?php

function db() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO("sqlite:" . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    init_db($pdo);
    return $pdo;
}


function init_db(PDO $pdo): void
{
  $pdo->exec(file_get_contents("schema.sql"));
}
