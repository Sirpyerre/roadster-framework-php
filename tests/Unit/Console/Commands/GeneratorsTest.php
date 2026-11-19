<?php

declare(strict_types=1);

use Roadster\Console\Commands\MakeRepositoryCommand;
use Roadster\Console\Commands\ScaffoldCrudCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Lists every file under $dir, relative to it, sorted.
 *
 * @return list<string>
 */
function generatedFiles(string $dir): array
{
    $files = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));

    foreach ($it as $file) {
        $files[] = substr($file->getPathname(), strlen($dir) + 1);
    }

    sort($files);

    return $files;
}

describe('generators with a temp base path', function () {
    beforeEach(function () {
        $this->dir = sys_get_temp_dir() . '/roadster_gen_' . uniqid();
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

    it('scaffold:crud defaults to the DBAL driver with a table migration', function () {
        $tester = new CommandTester(new ScaffoldCrudCommand($this->dir));

        expect($tester->execute(['resource' => 'Product']))->toBe(0);

        $files = generatedFiles($this->dir);

        expect($files)->toContain('config/dependencies/product.php');
        expect($files)->toContain('config/routes/product.php');
        expect($files)->toContain('src/Infrastructure/Product/DbalProductRepository.php');
        expect($files)->not->toContain('src/Infrastructure/Product/JsonProductRepository.php');
        expect(glob($this->dir . '/database/migrations/Version*CreateProductsTable.php'))->toHaveCount(1);
        expect($files)->toHaveCount(12);
        expect(is_dir($this->dir . '/storage'))->toBeFalse();
    });

    it('scaffold:crud --driver=json generates the same set as before, with no migration', function () {
        $tester = new CommandTester(new ScaffoldCrudCommand($this->dir));

        expect($tester->execute(['resource' => 'Product', '--driver' => 'json']))->toBe(0);

        $files = generatedFiles($this->dir);

        expect($files)->toContain('src/Infrastructure/Product/JsonProductRepository.php');
        expect($files)->not->toContain('src/Infrastructure/Product/DbalProductRepository.php');
        expect(is_dir($this->dir . '/database'))->toBeFalse();
        expect($files)->toHaveCount(11);
        expect(is_dir($this->dir . '/storage'))->toBeTrue();
    });

    it('scaffold:crud rejects an unsupported driver and writes nothing', function () {
        $tester = new CommandTester(new ScaffoldCrudCommand($this->dir));

        expect($tester->execute(['resource' => 'Product', '--driver' => 'cycle']))->toBe(1);
        expect(preg_replace('/\s+/', ' ', $tester->getDisplay()))->toContain('Unsupported driver "cycle". Available: dbal, json');
        expect(generatedFiles($this->dir))->toBe([]);
    });

    it('scaffold:crud generates valid PHP and the driver binding', function (string $driver, string $class) {
        (new CommandTester(new ScaffoldCrudCommand($this->dir)))->execute(['resource' => 'Product', '--driver' => $driver]);

        foreach (generatedFiles($this->dir) as $file) {
            if (str_ends_with($file, '.php')) {
                exec('php -l ' . escapeshellarg($this->dir . '/' . $file) . ' 2>&1', $out, $code);
                expect($code)->toBe(0, "{$file}: " . implode("\n", $out));
            }
        }

        $deps = require $this->dir . '/config/dependencies/product.php';

        expect($deps['aliases'])->toBe([
            'Roadster\\Domain\\Product\\ProductRepositoryInterface' => 'Roadster\\Infrastructure\\Product\\' . $class,
        ]);
    })->with([
        'dbal' => ['dbal', 'DbalProductRepository'],
        'json' => ['json', 'JsonProductRepository'],
    ]);

    it('scaffold:crud generates a migration that creates and drops the resource table', function () {
        (new CommandTester(new ScaffoldCrudCommand($this->dir)))->execute(['resource' => 'Product']);

        $file = glob($this->dir . '/database/migrations/Version*CreateProductsTable.php')[0];
        $content = file_get_contents($file);

        expect($content)->toContain("createTable('products')");
        expect($content)->toContain("dropTable('products')");
        expect($content)->toContain("return 'Create products table';");
        expect($content)->toContain('namespace Database\Migrations;');
    });

    it('scaffold:crud does not create a second migration on a re-run', function () {
        $first = new CommandTester(new ScaffoldCrudCommand($this->dir));
        $first->execute(['resource' => 'Product']);

        $second = new CommandTester(new ScaffoldCrudCommand($this->dir));
        $second->execute(['resource' => 'Product']);

        expect(glob($this->dir . '/database/migrations/*.php'))->toHaveCount(1);
        expect(preg_replace('/\s+/', ' ', $second->getDisplay()))->toContain('CreateProductsTable.php');
        expect($second->getDisplay())->toContain('already exists');
    });

    it('scaffold:crud tells DBAL users to run migrate but not JSON users', function () {
        $dbal = new CommandTester(new ScaffoldCrudCommand($this->dir));
        $dbal->execute(['resource' => 'Product']);

        $json = new CommandTester(new ScaffoldCrudCommand($this->dir));
        $json->execute(['resource' => 'Order', '--driver' => 'json']);

        expect($dbal->getDisplay())->toContain('./bin/roadster migrate');
        expect($json->getDisplay())->not->toContain('./bin/roadster migrate');
    });

    it('scaffold:crud does not ask for manual registration', function () {
        $tester = new CommandTester(new ScaffoldCrudCommand($this->dir));
        $tester->execute(['resource' => 'Product']);

        expect($tester->getDisplay())->not->toContain('config/container.php');
        expect($tester->getDisplay())->not->toContain('Include routes in config/routes.php');
    });

    it('scaffold:crud skips existing files and generates the rest', function () {
        mkdir($this->dir . '/src/Domain/Product', 0755, true);
        file_put_contents($this->dir . '/src/Domain/Product/Product.php', 'custom');

        $tester = new CommandTester(new ScaffoldCrudCommand($this->dir));
        $tester->execute(['resource' => 'Product']);

        expect(file_get_contents($this->dir . '/src/Domain/Product/Product.php'))->toBe('custom');
        expect(file_exists($this->dir . '/config/dependencies/product.php'))->toBeTrue();
        expect($tester->getDisplay())->toContain('already exists');
    });

    it('make:repository creates entity, interface, DBAL repository, binding and migration by default', function () {
        $tester = new CommandTester(new MakeRepositoryCommand($this->dir));

        expect($tester->execute(['name' => 'Invoice']))->toBe(0);

        $files = generatedFiles($this->dir);
        $migration = glob($this->dir . '/database/migrations/Version*CreateInvoicesTable.php');

        expect($migration)->toHaveCount(1);
        expect(array_values(array_filter($files, fn (string $f) => !str_starts_with($f, 'database/'))))->toBe([
            'config/dependencies/invoice.php',
            'src/Domain/Invoice/Invoice.php',
            'src/Domain/Invoice/InvoiceRepositoryInterface.php',
            'src/Infrastructure/Invoice/DbalInvoiceRepository.php',
        ]);
    });

    it('make:repository --driver=json creates the JSON repository and no migration', function () {
        $tester = new CommandTester(new MakeRepositoryCommand($this->dir));

        expect($tester->execute(['name' => 'Invoice', '--driver' => 'json']))->toBe(0);

        expect(generatedFiles($this->dir))->toBe([
            'config/dependencies/invoice.php',
            'src/Domain/Invoice/Invoice.php',
            'src/Domain/Invoice/InvoiceRepositoryInterface.php',
            'src/Infrastructure/Invoice/JsonInvoiceRepository.php',
        ]);
    });

    it('make:repository rejects an unsupported driver and writes nothing', function () {
        $tester = new CommandTester(new MakeRepositoryCommand($this->dir));

        expect($tester->execute(['name' => 'Invoice', '--driver' => 'doctrine']))->toBe(1);
        expect(generatedFiles($this->dir))->toBe([]);
    });

    it('make:repository accepts the Repository suffix', function () {
        $tester = new CommandTester(new MakeRepositoryCommand($this->dir));
        $tester->execute(['name' => 'InvoiceRepository']);

        expect(file_exists($this->dir . '/src/Domain/Invoice/InvoiceRepositoryInterface.php'))->toBeTrue();
    });

    it('make:repository keeps an existing entity untouched', function () {
        mkdir($this->dir . '/src/Domain/Invoice', 0755, true);
        file_put_contents($this->dir . '/src/Domain/Invoice/Invoice.php', 'custom');

        $tester = new CommandTester(new MakeRepositoryCommand($this->dir));

        expect($tester->execute(['name' => 'Invoice']))->toBe(0);
        expect(file_get_contents($this->dir . '/src/Domain/Invoice/Invoice.php'))->toBe('custom');
        expect(file_exists($this->dir . '/src/Infrastructure/Invoice/DbalInvoiceRepository.php'))->toBeTrue();
    });

    it('make:repository fails and writes nothing when the selected repository exists', function () {
        mkdir($this->dir . '/src/Infrastructure/Invoice', 0755, true);
        file_put_contents($this->dir . '/src/Infrastructure/Invoice/DbalInvoiceRepository.php', 'custom');

        $tester = new CommandTester(new MakeRepositoryCommand($this->dir));

        expect($tester->execute(['name' => 'Invoice']))->toBe(1);
        expect(generatedFiles($this->dir))->toBe(['src/Infrastructure/Invoice/DbalInvoiceRepository.php']);
    });

    it('make:repository does not duplicate an existing table migration', function () {
        (new CommandTester(new MakeRepositoryCommand($this->dir)))->execute(['name' => 'Invoice']);
        unlink($this->dir . '/src/Infrastructure/Invoice/DbalInvoiceRepository.php');

        (new CommandTester(new MakeRepositoryCommand($this->dir)))->execute(['name' => 'Invoice']);

        expect(glob($this->dir . '/database/migrations/*.php'))->toHaveCount(1);
    });

    it('registers make:repository in the application', function () {
        expect((new \Roadster\Console\Application())->has('make:repository'))->toBeTrue();
    });
});
