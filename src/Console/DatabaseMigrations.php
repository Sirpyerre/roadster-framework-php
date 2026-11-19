<?php

declare(strict_types=1);

namespace Roadster\Console;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;

/**
 * Builds the Doctrine Migrations factory from config/migrations.php.
 *
 * Without an explicit connection the database is resolved from the container
 * only when a migration command actually needs it, so merely registering the
 * commands never reads the DB_* configuration.
 */
final class DatabaseMigrations
{
    public static function dependencyFactory(?Connection $connection = null, ?string $migrationsPath = null): DependencyFactory
    {
        /** @var array<string, mixed> $config */
        $config = require configPath('migrations.php');

        if ($migrationsPath !== null) {
            $config['migrations_paths'] = [self::namespace($config) => $migrationsPath];
        }

        return DependencyFactory::fromConnection(
            new ConfigurationArray($config),
            $connection !== null ? new ExistingConnection($connection) : new LazyContainerConnection(),
        );
    }

    /**
     * Namespace configured for migration classes.
     *
     * @param array<string, mixed>|null $config
     */
    public static function namespace(?array $config = null): string
    {
        $config ??= require configPath('migrations.php');
        /** @var array<string, string> $paths */
        $paths = $config['migrations_paths'];

        return (string) array_key_first($paths);
    }
}
