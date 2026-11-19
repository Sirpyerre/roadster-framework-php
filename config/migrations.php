<?php

declare(strict_types=1);

/**
 * Doctrine Migrations configuration (ConfigurationArray format).
 */
return [
    'migrations_paths' => [
        'Database\\Migrations' => basePath('database/migrations'),
    ],

    'table_storage' => [
        'table_name' => 'migrations',
    ],

    // Run all pending migrations of one command in a single transaction
    // (effective only on databases with transactional DDL: SQLite, PostgreSQL)
    'all_or_nothing' => true,

    // Wrap each migration in its own transaction
    'transactional' => true,
];
