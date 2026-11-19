<?php

declare(strict_types=1);

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Roadster\Console\Application;
use Roadster\Console\Commands\ScaffoldCrudCommand;
use Roadster\Console\DatabaseMigrations;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Scaffolds a CRUD with the DBAL driver into a temp directory, loads the generated
 * classes, runs the generated migration on a temp SQLite file and exercises the
 * generated repository end to end.
 */
describe('generated DBAL CRUD', function () {
    beforeEach(function () {
        $this->dir = sys_get_temp_dir() . '/roadster_e2e_' . uniqid();
        mkdir($this->dir);

        // PHP cannot redeclare classes, so every test scaffolds a uniquely named resource
        $this->resource = 'Widget' . bin2hex(random_bytes(3));
        $this->table = strtolower($this->resource) . 's';

        (new CommandTester(new ScaffoldCrudCommand($this->dir)))->execute(['resource' => $this->resource]);

        foreach ([
            "src/Domain/{$this->resource}/{$this->resource}.php",
            "src/Domain/{$this->resource}/{$this->resource}RepositoryInterface.php",
            "src/Infrastructure/{$this->resource}/Dbal{$this->resource}Repository.php",
        ] as $file) {
            require_once $this->dir . '/' . $file;
        }

        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => $this->dir . '/db.sqlite',
        ]);

        // Each CLI command is its own process, so build a fresh Application per call
        $this->cli = function (string $command): CommandTester {
            $app = new Application(DatabaseMigrations::dependencyFactory(
                $this->connection,
                $this->dir . '/database/migrations'
            ));
            $tester = new CommandTester($app->find($command));
            $tester->execute([], ['interactive' => false]);

            return $tester;
        };

        ($this->cli)('migrate');

        $entity = "Roadster\\Domain\\{$this->resource}\\{$this->resource}";
        $repository = "Roadster\\Infrastructure\\{$this->resource}\\Dbal{$this->resource}Repository";
        $this->make = fn (array $data = []) => new $entity($data + ['name' => 'Widget']);
        $this->repo = new $repository($this->connection);
    });

    afterEach(function () {
        $this->connection->close();

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->dir);
    });

    it('creates the resource table through the generated migration', function () {
        expect($this->connection->createSchemaManager()->tablesExist([$this->table]))->toBeTrue();

        $columns = array_keys($this->connection->createSchemaManager()->listTableColumns($this->table));

        expect($columns)->toBe(['id', 'name', 'created_at', 'updated_at']);
    });

    it('saves and finds an entity', function () {
        $entity = ($this->make)(['name' => 'Sprocket']);

        $this->repo->save($entity);
        $found = $this->repo->findById($entity->getId());

        expect($found)->not->toBeNull();
        expect($found->getId())->toBe($entity->getId());
        expect($found->getName())->toBe('Sprocket');
        expect($found->getCreatedAt()->getTimestamp())->toBe($entity->getCreatedAt()->getTimestamp());
        expect($found->getUpdatedAt())->toBeNull();
    });

    it('returns null for an unknown id', function () {
        expect($this->repo->findById('does-not-exist'))->toBeNull();
    });

    it('updates an existing entity without adding a row', function () {
        $entity = ($this->make)();
        $this->repo->save($entity);

        $entity->update(['name' => 'Renamed']);
        $this->repo->save($entity);

        $all = $this->repo->findAll();

        expect($all)->toHaveCount(1);
        expect($all[0]->getName())->toBe('Renamed');
        expect($all[0]->getUpdatedAt())->not->toBeNull();
    });

    it('saves identical data twice without a duplicate or an error', function () {
        $entity = ($this->make)();

        $this->repo->save($entity);
        $this->repo->save($entity);

        expect($this->repo->findAll())->toHaveCount(1);
    });

    it('deletes an existing id and reports false for an unknown one', function () {
        $entity = ($this->make)();
        $this->repo->save($entity);

        expect($this->repo->delete($entity->getId()))->toBeTrue();
        expect($this->repo->findById($entity->getId()))->toBeNull();
        expect($this->repo->delete($entity->getId()))->toBeFalse();
    });

    it('lists entities ordered by creation time', function () {
        foreach (['2026-01-03T10:00:00+00:00', '2026-01-01T10:00:00+00:00', '2026-01-02T10:00:00+00:00'] as $createdAt) {
            $this->repo->save(($this->make)(['createdAt' => $createdAt, 'name' => $createdAt]));
        }

        $names = array_map(fn ($e) => $e->getName(), $this->repo->findAll());

        expect($names)->toBe(['2026-01-01T10:00:00+00:00', '2026-01-02T10:00:00+00:00', '2026-01-03T10:00:00+00:00']);
    });

    it('stores timestamps in UTC and round-trips the instant under a non-UTC timezone', function () {
        $previous = date_default_timezone_get();
        date_default_timezone_set('America/Mexico_City');

        try {
            $entity = ($this->make)();
            $this->repo->save($entity);
            $found = $this->repo->findById($entity->getId());
            $stored = $this->connection->fetchOne("SELECT created_at FROM {$this->table}");

            expect($found->getCreatedAt()->getTimestamp())->toBe($entity->getCreatedAt()->getTimestamp());
            expect($stored)->toBe(gmdate('Y-m-d H:i:s', $entity->getCreatedAt()->getTimestamp()));
        } finally {
            date_default_timezone_set($previous);
        }
    });

    it('drops the table when the migration is rolled back', function () {
        $tester = ($this->cli)('migrate:rollback');

        expect($tester->getStatusCode())->toBe(0);
        expect($this->connection->createSchemaManager()->tablesExist([$this->table]))->toBeFalse();
    });
});
