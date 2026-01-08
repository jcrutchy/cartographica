<?php

namespace cartographica\share;

class Env
{
    private static ?string $root = null;
    private static ?string $dataRoot = null;
    private static ?bool $isTest = null;
    private static ?array $bootstrap = null;

    private static function bootstrap(): array
    {
        if (self::$bootstrap === null) {
            self::$bootstrap = require self::root() . '/services/bootstrap.php';
        }
        return self::$bootstrap;
    }
    
    public static function repoRoot(): string
    {
        return self::bootstrap()['repo_root'];
    }
    
    public static function dataRoot(): string
    {
        $base = self::bootstrap()['data_root'];
    
        if (self::isTestMode()) {
            $base = self::bootstrap()['test_data'];
        }
    
        return realpath($base) ?: $base;
    }

    public static function root(): string
    {
        if (self::$root === null) {
            self::$root = realpath(__DIR__ . '/..');
        }
        return self::$root;
    }

    public static function isTestMode(): bool
    {
        return is_dir(self::bootstrap()['test_data']);
    }

    public static function servicePath(string $service): string
    {
        return self::root() . "/services/{$service}";
    }

    public static function serviceData(string $service): string
    {
        return self::dataRoot() . "/services/{$service}";
    }

    public static function sharedConfig(): string
    {
        return self::dataRoot() . "/shared/config.json";
    }
}
