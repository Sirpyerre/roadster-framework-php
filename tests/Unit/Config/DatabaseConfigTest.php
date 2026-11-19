<?php

declare(strict_types=1);

/**
 * Loads config/database.php with the given DB_* variables, restoring the
 * previous environment afterwards.
 *
 * @param array<string, string|null> $vars
 * @return array<string, mixed>
 */
function loadDatabaseConfig(array $vars): array
{
    $keys = ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];
    $saved = [];

    foreach ($keys as $key) {
        $saved[$key] = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null, getenv($key)];
        unset($_ENV[$key], $_SERVER[$key]);
        putenv($key);
    }

    foreach ($vars as $key => $value) {
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }

    try {
        return require configPath('database.php');
    } finally {
        foreach ($keys as $key) {
            [$env, $server, $getenv] = $saved[$key];
            unset($_ENV[$key], $_SERVER[$key]);

            if ($env !== null) {
                $_ENV[$key] = $env;
            }

            if ($server !== null) {
                $_SERVER[$key] = $server;
            }

            $getenv === false ? putenv($key) : putenv("{$key}={$getenv}");
        }
    }
}

describe('config/database.php', function () {
    it('defaults to SQLite at storage/database.sqlite', function () {
        expect(loadDatabaseConfig([]))->toBe([
            'driver' => 'pdo_sqlite',
            'path' => basePath('storage/database.sqlite'),
        ]);
    });

    it('maps MySQL variables', function () {
        $config = loadDatabaseConfig([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => 'db.local',
            'DB_PORT' => '3307',
            'DB_DATABASE' => 'app',
            'DB_USERNAME' => 'user',
            'DB_PASSWORD' => 'secret',
        ]);

        expect($config)->toBe([
            'driver' => 'pdo_mysql',
            'host' => 'db.local',
            'port' => 3307,
            'dbname' => 'app',
            'user' => 'user',
            'password' => 'secret',
            'charset' => 'utf8mb4',
        ]);
    });

    it('maps PostgreSQL variables with their default port', function () {
        $config = loadDatabaseConfig([
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => 'pg.local',
            'DB_DATABASE' => 'app',
            'DB_USERNAME' => 'user',
            'DB_PASSWORD' => 'secret',
        ]);

        expect($config['driver'])->toBe('pdo_pgsql');
        expect($config['host'])->toBe('pg.local');
        expect($config['port'])->toBe(5432);
        expect($config['dbname'])->toBe('app');
    });

    it('resolves a relative SQLite path against the project root', function () {
        $config = loadDatabaseConfig(['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => 'var/app.db']);

        expect($config['path'])->toBe(basePath('var/app.db'));
    });

    it('keeps an absolute SQLite path', function () {
        $config = loadDatabaseConfig(['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => '/tmp/app.db']);

        expect($config['path'])->toBe('/tmp/app.db');
    });

    it('supports in-memory SQLite', function () {
        $config = loadDatabaseConfig(['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:']);

        expect($config)->toBe(['driver' => 'pdo_sqlite', 'memory' => true]);
    });

    it('rejects an unsupported driver naming the value and the supported ones', function () {
        loadDatabaseConfig(['DB_CONNECTION' => 'oracle']);
    })->throws(InvalidArgumentException::class, 'Unsupported DB_CONNECTION "oracle". Supported: sqlite, mysql, pgsql.');
});
