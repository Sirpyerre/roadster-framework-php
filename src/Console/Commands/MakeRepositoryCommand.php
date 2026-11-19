<?php

declare(strict_types=1);

namespace Roadster\Console\Commands;

use Roadster\Console\CrudMigration;
use Roadster\Console\RepositoryDriver;
use Roadster\Console\StubWriter;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'make:repository',
    description: 'Generate a repository interface, JSON implementation and container binding'
)]
final class MakeRepositoryCommand extends Command
{
    private readonly string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? dirname(__DIR__, 3);

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Resource name in singular (e.g., Invoice or InvoiceRepository)')
            ->addOption('driver', null, InputOption::VALUE_REQUIRED, 'Repository driver: ' . implode(', ', RepositoryDriver::values()), RepositoryDriver::Dbal->value);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $input->getArgument('name');

        if (!is_string($name) || $name === '') {
            $io->error('Repository name is required');
            return Command::FAILURE;
        }

        $driverName = $input->getOption('driver');
        $driver = is_string($driverName) ? RepositoryDriver::tryFrom($driverName) : null;

        if ($driver === null) {
            $io->error(RepositoryDriver::unsupportedMessage(is_string($driverName) ? $driverName : ''));
            return Command::FAILURE;
        }

        $resource = ucfirst(str_ends_with($name, 'Repository') ? substr($name, 0, -10) : $name);
        $variableName = lcfirst($resource);

        $writer = new StubWriter($this->basePath, dirname(__DIR__, 3) . '/templates/crud');
        $routeName = strtolower($resource) . 's';
        $implementation = $driver->repositoryTarget($resource);

        // Fail before writing anything so we never leave a half-generated set
        if ($writer->exists($implementation)) {
            $io->error("Repository already exists: {$implementation}");
            return Command::FAILURE;
        }

        $variables = [
            'resource' => $resource,
            'variableName' => $variableName,
            'routeName' => $routeName,
            'repositoryClass' => $driver->repositoryClass($resource),
        ];

        // Stub => target; the entity is only created when it is missing
        $files = [
            'Entity.php.stub' => "src/Domain/{$resource}/{$resource}.php",
            'Repository.php.stub' => "src/Domain/{$resource}/{$resource}RepositoryInterface.php",
            $driver->repositoryStub() => $implementation,
            'dependencies.php.stub' => "config/dependencies/{$variableName}.php",
        ];

        $created = [];
        $skipped = [];

        try {
            foreach ($files as $stub => $target) {
                if ($writer->write($stub, $target, $variables)) {
                    $created[] = $target;
                } else {
                    $skipped[] = $target;
                }
            }

            if ($driver->needsMigration()) {
                $migration = new CrudMigration($this->basePath, $writer);
                $existing = $migration->existing($routeName);

                if ($existing === null) {
                    $created[] = $migration->write($routeName);
                } else {
                    $skipped[] = $existing;
                }
            }
        } catch (RuntimeException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        if ($driver->needsStorageDirectory() && !is_dir($this->basePath . '/storage')) {
            mkdir($this->basePath . '/storage', 0755, true);
        }

        $io->success("Repository generated for {$resource}");
        $io->section('Created files');
        $io->listing($created);

        if ($skipped !== []) {
            $io->section('Already existed (left untouched)');
            $io->listing($skipped);
        }

        $nextSteps = [
            "The binding is registered automatically (config/dependencies/{$variableName}.php)",
            "Inject {$resource}RepositoryInterface in your services; it is autowired",
        ];

        if ($driver->needsMigration()) {
            $nextSteps[] = 'Create the table: ./bin/roadster migrate';
        }

        $io->section('Next steps');
        $io->listing($nextSteps);

        return Command::SUCCESS;
    }
}
