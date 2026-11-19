<?php

declare(strict_types=1);

use Roadster\Console\DatabaseMigrations;
use Roadster\Console\RepositoryDriver;

describe('RepositoryDriver', function () {
    it('parses the available drivers and rejects unknown ones', function () {
        expect(RepositoryDriver::tryFrom('dbal'))->toBe(RepositoryDriver::Dbal);
        expect(RepositoryDriver::tryFrom('json'))->toBe(RepositoryDriver::Json);
        expect(RepositoryDriver::tryFrom('cycle'))->toBeNull();
        expect(RepositoryDriver::tryFrom('doctrine'))->toBeNull();
    });

    it('lists the values and names them in the unsupported message', function () {
        expect(RepositoryDriver::values())->toBe(['dbal', 'json']);
        expect(RepositoryDriver::unsupportedMessage('cycle'))
            ->toBe('Unsupported driver "cycle". Available: dbal, json (doctrine and cycle are planned).');
    });

    it('names the repository class and file per driver', function () {
        expect(RepositoryDriver::Dbal->repositoryClass('Product'))->toBe('DbalProductRepository');
        expect(RepositoryDriver::Json->repositoryClass('Product'))->toBe('JsonProductRepository');
        expect(RepositoryDriver::Dbal->repositoryTarget('Product'))
            ->toBe('src/Infrastructure/Product/DbalProductRepository.php');
        expect(RepositoryDriver::Dbal->repositoryStub())->toBe('DbalRepository.php.stub');
        expect(RepositoryDriver::Json->repositoryStub())->toBe('JsonRepository.php.stub');
    });

    it('knows which drivers need a migration and a storage directory', function () {
        expect(RepositoryDriver::Dbal->needsMigration())->toBeTrue();
        expect(RepositoryDriver::Json->needsMigration())->toBeFalse();
        expect(RepositoryDriver::Json->needsStorageDirectory())->toBeTrue();
        expect(RepositoryDriver::Dbal->needsStorageDirectory())->toBeFalse();
    });
});

describe('DatabaseMigrations::className', function () {
    it('prefixes the UTC timestamp so names sort chronologically', function () {
        $now = new DateTimeImmutable('2026-11-18 23:30:45', new DateTimeZone('America/Mexico_City'));

        expect(DatabaseMigrations::className('CreateProducts', $now))->toBe('Version20261119053045CreateProducts');
    });

    it('uses the current time by default', function () {
        expect(DatabaseMigrations::className('X'))->toMatch('/^Version\d{14}X$/');
    });
});
