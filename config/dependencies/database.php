<?php

declare(strict_types=1);

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

return [
    'factories' => [
        // Shared by the whole worker; DBAL connects on the first query
        Connection::class => function (): Connection {
            /** @var array<string, mixed> $params */
            $params = require configPath('database.php');

            return DriverManager::getConnection($params);
        },
    ],
];
