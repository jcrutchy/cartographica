<?php

namespace cartographica\services\identity;

use cartographica\share\SharedConfig;
use cartographica\share\Db;
use PDO;

class Config extends SharedConfig
{
  private const BASE=CARTOGRAPHICA_DATA_DIR."/services/identity";
  private PDO $pdo;

  function __construct()
  {
    $this->pdo=Db::connect($this->sqlitePath(),__DIR__."/schema.sql");
  }

  public function validator(): IdentityValidator
  {
    return new IdentityValidator();
  }

  public function pdo(): PDO
  {
    return $this->pdo;
  }

  public function privateKey(): string
  {
    return self::BASE."/identity_private.pem";
  }

  public function publicKey(): string
  {
    return self::BASE."/identity_public.pem";
  }

  public static function sqlitePath(): string
  {
    return self::BASE."/identity.sqlite";
  }

  public static function logFile(): string
  {
    return self::BASE."/log/identity.log";
  }

  public static function loginLinkBase(): string
  {
    # TODO: need to get webroot from request to be able to handle different clients?
    return self::get("web_root")."/tools/client?token=";
  }
}
