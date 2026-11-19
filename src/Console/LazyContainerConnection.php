<?php

declare(strict_types=1);

namespace Roadster\Console;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Connection\ConnectionLoader;
use Roadster\App\Container\ContainerFactory;

/**
 * Resolves the shared DBAL connection from the container on first use.
 */
final class LazyContainerConnection implements ConnectionLoader
{
    private ?Connection $connection = null;

    public function getConnection(?string $name = null): Connection
    {
        return $this->connection ??= ContainerFactory::create()->get(Connection::class);
    }
}
