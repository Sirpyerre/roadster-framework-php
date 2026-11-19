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

    it('scaffold:crud generates the binding and routes files and writes only under the base path', function () {
        $tester = new CommandTester(new ScaffoldCrudCommand($this->dir));

        expect($tester->execute(['resource' => 'Product']))->toBe(0);

        $files = generatedFiles($this->dir);

        expect($files)->toContain('config/dependencies/product.php');
        expect($files)->toContain('config/routes/product.php');
        expect($files)->toContain('src/Infrastructure/Product/JsonProductRepository.php');
        expect($files)->toHaveCount(11);
    });

    it('scaffold:crud generates valid PHP and an interface-to-implementation alias', function () {
        (new CommandTester(new ScaffoldCrudCommand($this->dir)))->execute(['resource' => 'Product']);

        foreach (generatedFiles($this->dir) as $file) {
            if (str_ends_with($file, '.php')) {
                exec('php -l ' . escapeshellarg($this->dir . '/' . $file) . ' 2>&1', $out, $code);
                expect($code)->toBe(0, "{$file}: " . implode("\n", $out));
            }
        }

        $deps = require $this->dir . '/config/dependencies/product.php';

        expect($deps['aliases'])->toBe([
            'Roadster\Domain\Product\ProductRepositoryInterface' => 'Roadster\Infrastructure\Product\JsonProductRepository',
        ]);
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

    it('make:repository creates entity, interface, implementation and binding', function () {
        $tester = new CommandTester(new MakeRepositoryCommand($this->dir));

        expect($tester->execute(['name' => 'Invoice']))->toBe(0);

        expect(generatedFiles($this->dir))->toBe([
            'config/dependencies/invoice.php',
            'src/Domain/Invoice/Invoice.php',
            'src/Domain/Invoice/InvoiceRepositoryInterface.php',
            'src/Infrastructure/Invoice/JsonInvoiceRepository.php',
        ]);
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
        expect(file_exists($this->dir . '/src/Infrastructure/Invoice/JsonInvoiceRepository.php'))->toBeTrue();
    });

    it('make:repository fails and writes nothing when the repository exists', function () {
        mkdir($this->dir . '/src/Infrastructure/Invoice', 0755, true);
        file_put_contents($this->dir . '/src/Infrastructure/Invoice/JsonInvoiceRepository.php', 'custom');

        $tester = new CommandTester(new MakeRepositoryCommand($this->dir));

        expect($tester->execute(['name' => 'Invoice']))->toBe(1);
        expect(generatedFiles($this->dir))->toBe(['src/Infrastructure/Invoice/JsonInvoiceRepository.php']);
    });

    it('registers make:repository in the application', function () {
        expect((new \Roadster\Console\Application())->has('make:repository'))->toBeTrue();
    });
});
