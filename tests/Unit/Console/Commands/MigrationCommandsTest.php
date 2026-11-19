<?php

declare(strict_types=1);

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Roadster\Console\Application;
use Roadster\Console\Commands\MakeMigrationCommand;
use Roadster\Console\DatabaseMigrations;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Writes a migration class into $dir. $order controls the execution order
 * (class names are sorted alphabetically); the class name is unique per call
 * because PHP cannot redeclare a class.
 */
function writeMigration(string $dir, int $order, string $up, string $down, bool $throws = false): string
{
    $class = 'Version' . $order . 'T' . bin2hex(random_bytes(4));
    $throw = $throws ? "throw new \\RuntimeException('boom');" : '';

    file_put_contents($dir . '/' . $class . '.php', <<<PHP
        <?php

        declare(strict_types=1);

        namespace Database\\Migrations;

        use Doctrine\\DBAL\\Schema\\Schema;
        use Doctrine\\Migrations\\AbstractMigration;

        final class {$class} extends AbstractMigration
        {
            public function getDescription(): string
            {
                return 'Fixture {$order}';
            }

            public function up(Schema \$schema): void
            {
                \$this->addSql('{$up}');
                {$throw}
            }

            public function down(Schema \$schema): void
            {
                \$this->addSql('{$down}');
            }
        }
        PHP);

    return $class;
}

/**
 * @return list<string>
 */
function tables(Connection $connection): array
{
    return array_values(array_filter(
        $connection->createSchemaManager()->listTableNames(),
        fn (string $t) => $t !== 'migrations'
    ));
}

describe('migration commands', function () {
    beforeEach(function () {
        $this->dir = sys_get_temp_dir() . '/roadster_mig_' . uniqid();
        mkdir($this->dir . '/migrations', 0755, true);
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => $this->dir . '/db.sqlite',
        ]);
        // Each CLI invocation is a new process, so build a fresh Application (and
        // migrations factory, whose migration instances are single-use) per command
        $this->newApp = fn (): Application => new Application(
            DatabaseMigrations::dependencyFactory($this->connection, $this->dir . '/migrations')
        );
        $this->app = ($this->newApp)();
        $this->run = function (string $command, array $input = []): CommandTester {
            $tester = new CommandTester(($this->newApp)()->find($command));
            $tester->execute($input, ['interactive' => false]);

            return $tester;
        };
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

    it('registers the Roadster command names and not the migrations:* ones', function () {
        foreach (['migrate', 'migrate:status', 'migrate:rollback', 'make:migration'] as $name) {
            expect($this->app->has($name))->toBeTrue();
        }

        expect($this->app->has('migrations:migrate'))->toBeFalse();
        expect($this->app->has('migrations:status'))->toBeFalse();
    });

    it('applies pending migrations in order and records them', function () {
        writeMigration($this->dir . '/migrations', 2, 'CREATE TABLE b (id INTEGER)', 'DROP TABLE b');
        writeMigration($this->dir . '/migrations', 1, 'CREATE TABLE a (id INTEGER)', 'DROP TABLE a');

        $tester = ($this->run)('migrate');

        expect($tester->getStatusCode())->toBe(0);
        expect(tables($this->connection))->toBe(['a', 'b']);
        expect($this->connection->fetchFirstColumn('SELECT version FROM migrations ORDER BY version'))->toHaveCount(2);
    });

    it('does nothing when no migration is pending', function () {
        writeMigration($this->dir . '/migrations', 1, 'CREATE TABLE a (id INTEGER)', 'DROP TABLE a');
        ($this->run)('migrate');

        $tester = ($this->run)('migrate');

        expect($tester->getStatusCode())->toBe(0);
        expect($this->connection->fetchFirstColumn('SELECT version FROM migrations'))->toHaveCount(1);
    });

    it('fails and records nothing when a migration throws (all or nothing)', function () {
        writeMigration($this->dir . '/migrations', 1, 'CREATE TABLE a (id INTEGER)', 'DROP TABLE a');
        writeMigration($this->dir . '/migrations', 2, 'CREATE TABLE b (id INTEGER)', 'DROP TABLE b', throws: true);

        expect(fn () => ($this->run)('migrate'))->toThrow(RuntimeException::class, 'boom');

        expect(tables($this->connection))->toBe([]);
        expect($this->connection->fetchFirstColumn('SELECT version FROM migrations'))->toHaveCount(0);
    });

    it('rolls back only the last executed migration', function () {
        writeMigration($this->dir . '/migrations', 1, 'CREATE TABLE a (id INTEGER)', 'DROP TABLE a');
        writeMigration($this->dir . '/migrations', 2, 'CREATE TABLE b (id INTEGER)', 'DROP TABLE b');
        ($this->run)('migrate');

        $tester = ($this->run)('migrate:rollback');

        expect($tester->getStatusCode())->toBe(0);
        expect(tables($this->connection))->toBe(['a']);
        expect($this->connection->fetchFirstColumn('SELECT version FROM migrations'))->toHaveCount(1);
    });

    it('rolls back the only executed migration', function () {
        writeMigration($this->dir . '/migrations', 1, 'CREATE TABLE a (id INTEGER)', 'DROP TABLE a');
        ($this->run)('migrate');

        $tester = ($this->run)('migrate:rollback');

        expect($tester->getStatusCode())->toBe(0);
        expect(tables($this->connection))->toBe([]);
        expect($this->connection->fetchFirstColumn('SELECT version FROM migrations'))->toHaveCount(0);
    });

    it('reports nothing to roll back when no migration is executed', function () {
        writeMigration($this->dir . '/migrations', 1, 'CREATE TABLE a (id INTEGER)', 'DROP TABLE a');

        $tester = ($this->run)('migrate:rollback');

        expect($tester->getStatusCode())->toBe(0);
        expect($tester->getDisplay())->toContain('Nothing to roll back');
        expect(tables($this->connection))->toBe([]);
    });

    it('reports executed, available and new counts in migrate:status', function () {
        writeMigration($this->dir . '/migrations', 1, 'CREATE TABLE a (id INTEGER)', 'DROP TABLE a');
        ($this->run)('migrate');
        writeMigration($this->dir . '/migrations', 2, 'CREATE TABLE b (id INTEGER)', 'DROP TABLE b');

        $display = preg_replace('/\s+/', ' ', ($this->run)('migrate:status')->getDisplay());

        expect($display)->toMatch('/Executed \| 1 /');
        expect($display)->toMatch('/Available \| 2 /');
        expect($display)->toMatch('/New \| 1 /');
    });

    it('shows the current, next and latest versions in migrate:status', function () {
        $first = writeMigration($this->dir . '/migrations', 1, 'CREATE TABLE a (id INTEGER)', 'DROP TABLE a');
        ($this->run)('migrate');
        $second = writeMigration($this->dir . '/migrations', 2, 'CREATE TABLE b (id INTEGER)', 'DROP TABLE b');

        $display = preg_replace('/\s+/', ' ', ($this->run)('migrate:status')->getDisplay());

        expect($display)->toContain("Current | Database\\Migrations\\{$first}");
        expect($display)->toContain("Next | Database\\Migrations\\{$second}");
        expect($display)->toContain("Latest | Database\\Migrations\\{$second}");
    });
});

describe('make:migration', function () {
    beforeEach(function () {
        $this->dir = sys_get_temp_dir() . '/roadster_mkmig_' . uniqid();
        mkdir($this->dir);
    });

    afterEach(function () {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->dir);
    });

    it('creates a timestamped class with a description derived from the name', function () {
        $tester = new CommandTester(new MakeMigrationCommand($this->dir));

        expect($tester->execute(['name' => 'CreateProducts']))->toBe(0);

        $files = glob($this->dir . '/database/migrations/Version*CreateProducts.php');
        expect($files)->toHaveCount(1);

        $content = file_get_contents($files[0]);
        $class = basename($files[0], '.php');

        expect($class)->toMatch('/^Version\d{14}CreateProducts$/');
        expect($content)->toContain("final class {$class} extends AbstractMigration");
        expect($content)->toContain('namespace Database\Migrations;');
        expect($content)->toContain("return 'Create products';");

        exec('php -l ' . escapeshellarg($files[0]) . ' 2>&1', $out, $code);
        expect($code)->toBe(0);
    });

    it('rejects names that are not valid class name fragments', function (string $name) {
        $tester = new CommandTester(new MakeMigrationCommand($this->dir));

        expect($tester->execute(['name' => $name]))->toBe(1);
        expect(is_dir($this->dir . '/database'))->toBeFalse();
    })->with(['create products', '1Create', 'Create-Products', 'Create/Products']);

    it('does not overwrite an existing migration file', function () {
        mkdir($this->dir . '/database/migrations', 0755, true);
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        // Cover a clock tick while the command runs
        foreach ([-1, 0, 1, 2] as $offset) {
            $stamp = $now->modify("{$offset} seconds")->format('YmdHis');
            file_put_contents($this->dir . "/database/migrations/Version{$stamp}CreateProducts.php", 'custom');
        }

        $tester = new CommandTester(new MakeMigrationCommand($this->dir));

        expect($tester->execute(['name' => 'CreateProducts']))->toBe(1);

        foreach (glob($this->dir . '/database/migrations/*.php') as $file) {
            expect(file_get_contents($file))->toBe('custom');
        }
    });
});

describe('database laziness', function () {
    it('keeps unrelated commands working with an invalid DB_CONNECTION', function () {
        exec(
            'DB_CONNECTION=oracle php ' . escapeshellarg(basePath('bin/roadster')) . ' routes:list 2>&1',
            $output,
            $code
        );

        expect($code)->toBe(0);
        expect(implode("\n", $output))->toContain('/health');
    });
});
