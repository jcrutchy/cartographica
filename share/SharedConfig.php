<?php

/*

SharedConfig.php
================

Purpose:
Load and cache shared configuration from the external data folder.
Provides a clean interface for retrieving config values.

Usage:
use cartographica\share\SharedConfig;
$webRoot=SharedConfig::get("web_root");
$from=SharedConfig::get("smtp_from_email");
$admin=SharedConfig::get("admin_email");

Usage:
- extend the class
- override any of the functions if default isn't appropriate, ie: dbPath()
- in controller:
    $config=new Config();
    $pdo=$config->pdo(); # only if database access is required
    $validator=$config->validator();

*/

namespace cartographica\share;

use cartographica\share\Env;
use cartographica\share\Db;
use PDO;
use Exception;

abstract class SharedConfig
{
  private static ?array $cache=null;
  protected static string $service;
  private ?PDO $pdo=null;

  function __construct()
  {
  }

  protected static function load(): array
  {
    if (self::$cache !== null)
    {
      return self::$cache;
    }
    $path = Env::sharedConfig();
    if (!file_exists($path))
    {
      throw new \RuntimeException("Shared config not found: $path");
    }
    $json=file_get_contents($path);
    self::$cache=json_decode($json,true);
    return self::$cache;
  }

  public function pdo(): PDO
  {
    if ($this->pdo === null)
    {
      $this->pdo=Db::connect($this->dbPath(),$this->schemaPath());
    }
    return $this->pdo;
  }

  public static function get(string $key,$default=null)
  {
    $config=self::load();
    return $config[$key] ?? $default;
  }

  public static function base(): string
  {
    return Env::serviceData(static::$service);
  }

  public function configFile(): string
  {
    return static::base()."/config.json";
  }

  public function logs(): string
  {
    return static::base()."/logs";
  }

  public function pids(): string
  {
    return static::base()."/pids";
  }

  public static function sharedConfig(): string
  {
    return Env::sharedConfig();
  }

  public function schemaPath(): string
  {
    return Env::servicePath(static::$service)."/schema.sql";
  }

  public function privateKey(): string
  {
    return static::base()."/".static::$service."_private.pem";
  }

  public function publicKey(): string
  {
    return static::base()."/".static::$service."_public.pem";
  }

  public function dbPath(): string
  {
    return static::base()."/".static::$service.".sqlite";
  }

  public function logFile(): string
  {
    return $this->logs()."/".static::$service.".log";
  }
}
