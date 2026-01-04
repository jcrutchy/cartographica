<?php

namespace cartographica\services\atlas;

use cartographica\share\SharedConfig;
use cartographica\share\Db;
use PDO;

class Config extends SharedConfig
{
  private const BASE=CARTOGRAPHICA_DATA_DIR."/services/atlas";
  private PDO $pdo;

  function __construct()
  {
    $this->pdo=Db::connect($this->sqlitePath(),__DIR__."/schema.sql");
  }

  public function validator(): AtlasValidator
  {
    return new AtlasValidator($this->pdo);
  }

  public function pdo(): PDO
  {
    return $this->pdo;
  }

  public function privateKey(): string
  {
    return self::BASE."/atlas_private.pem";
  }

  public function publicKey(): string
  {
    return self::BASE."/atlas_public.pem";
  }

  public static function sqlitePath(): string
  {
    return self::BASE."/atlas.sqlite";
  }

  public static function logFile(): string
  {
    return self::BASE."/log/atlas.log";
  }
}
