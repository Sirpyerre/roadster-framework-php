<?php

declare(strict_types=1);

/**
 * Database connection parameters in Doctrine DBAL format, built from the
 * environment (DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME,
 * DB_PASSWORD). SQLite at storage/database.sqlite is the default.
 */
$driver = strtolower((string) env('DB_CONNECTION', 'sqlite'));

return match ($driver) {
    'sqlite' => (function (): array {
        $database = (string) env('DB_DATABASE', 'storage/database.sqlite');

        if ($database === ':memory:') {
            return ['driver' => 'pdo_sqlite', 'memory' => true];
        }

        $isAbsolute = str_starts_with($database, '/') || preg_match('#^[A-Za-z]:[\\\\/]#', $database) === 1;

        return [
            'driver' => 'pdo_sqlite',
            'path' => $isAbsolute ? $database : basePath($database),
        ];
    })(),

    'mysql' => [
        'driver' => 'pdo_mysql',
        'host' => (string) env('DB_HOST', '127.0.0.1'),
        'port' => (int) env('DB_PORT', 3306),
        'dbname' => (string) env('DB_DATABASE', 'roadster'),
        'user' => (string) env('DB_USERNAME', 'root'),
        'password' => (string) env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],

    'pgsql' => [
        'driver' => 'pdo_pgsql',
        'host' => (string) env('DB_HOST', '127.0.0.1'),
        'port' => (int) env('DB_PORT', 5432),
        'dbname' => (string) env('DB_DATABASE', 'roadster'),
        'user' => (string) env('DB_USERNAME', 'postgres'),
        'password' => (string) env('DB_PASSWORD', ''),
        'charset' => 'utf8',
    ],

    default => throw new InvalidArgumentException(
        "Unsupported DB_CONNECTION \"{$driver}\". Supported: sqlite, mysql, pgsql."
    ),
};
