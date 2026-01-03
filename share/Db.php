<?php

/*

Db.php
======

Purpose:
Provides services with a simple SQLite database access.

Features:
- accept a path to the SQLite file
- accept a path to the schema .sql file
- create the DB if missing
- run the schema file
- return a PDO connection

Responsibilities:
Db::connect($dbPath,$schemaPath)

*/

namespace cartographica\share;

use PDO;
use Exception;

class Db
{
  private static array $connections = [];

  public static function connect(string $dbPath, string $schemaPath): PDO
  {
    if (isset(self::$connections[$dbPath]))
    {
      return self::$connections[$dbPath];
    }
    $init=!file_exists($dbPath);
    $pdo=new PDO("sqlite:".$dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    if ($init)
    {
      if (!file_exists($schemaPath))
      {
        throw new Exception("Missing schema file: $schemaPath");
      }
      $schemaSql=file_get_contents($schemaPath);
      $pdo->exec($schemaSql);
    }
    self::$connections[$dbPath]=$pdo;
    return $pdo;
  }
}
