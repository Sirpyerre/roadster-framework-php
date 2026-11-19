<?php

declare(strict_types=1);

namespace Roadster\Console\Commands;

use Roadster\Console\StubWriter;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'scaffold:crud',
    description: 'Generate complete CRUD for a resource'
)]
final class ScaffoldCrudCommand extends Command
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
            ->addArgument('resource', InputArgument::REQUIRED, 'Resource name in singular (e.g., Product, User)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $resource = $input->getArgument('resource');

        if (!is_string($resource)) {
            $io->error('Resource name is required');
            return Command::FAILURE;
        }

        // Normalize resource name (capitalize first letter)
        $resource = ucfirst($resource);
        $variableName = lcfirst($resource);
        $routeName = strtolower($resource) . 's';

        $io->title("Scaffolding CRUD for {$resource}");

        $writer = new StubWriter($this->basePath, dirname(__DIR__, 3) . '/templates/crud');
        $variables = [
            'resource' => $resource,
            'variableName' => $variableName,
            'routeName' => $routeName,
        ];

        // Storage directory used by the JSON repository
        if (!is_dir($this->basePath . '/storage')) {
            mkdir($this->basePath . '/storage', 0755, true);
        }

        // Stub => target (relative to the base path)
        $files = [
            // Handlers
            'IndexHandler.php.stub' => "src/Web/Rest/{$resource}/Index{$resource}Handler.php",
            'CreateHandler.php.stub' => "src/Web/Rest/{$resource}/Create{$resource}Handler.php",
            'ShowHandler.php.stub' => "src/Web/Rest/{$resource}/Show{$resource}Handler.php",
            'UpdateHandler.php.stub' => "src/Web/Rest/{$resource}/Update{$resource}Handler.php",
            'DeleteHandler.php.stub' => "src/Web/Rest/{$resource}/Delete{$resource}Handler.php",
            // Domain
            'Entity.php.stub' => "src/Domain/{$resource}/{$resource}.php",
            'Service.php.stub' => "src/Domain/{$resource}/{$resource}Service.php",
            'Repository.php.stub' => "src/Domain/{$resource}/{$resource}RepositoryInterface.php",
            // Infrastructure
            'JsonRepository.php.stub' => "src/Infrastructure/{$resource}/Json{$resource}Repository.php",
            // Wiring: interface binding and routes are discovered automatically
            'dependencies.php.stub' => "config/dependencies/{$variableName}.php",
            'routes.php.stub' => "config/routes/{$variableName}.php",
        ];

        $createdFiles = [];

        $io->progressStart(count($files));

        foreach ($files as $stub => $target) {
            try {
                if ($writer->write($stub, $target, $variables)) {
                    $createdFiles[] = $target;
                } else {
                    $io->warning("File already exists, skipping: {$target}");
                }
            } catch (RuntimeException $e) {
                $io->error($e->getMessage());
                return Command::FAILURE;
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        // Summary
        $io->newLine();
        $io->success([
            count($createdFiles) . ' files created',
        ]);

        $io->section('Created files');
        $io->listing($createdFiles);

        $io->section('Next steps');
        $io->listing([
            "Handlers, service and repository are registered automatically (config/dependencies/{$variableName}.php)",
            "Routes are loaded automatically (config/routes/{$variableName}.php)",
            "Run: ./bin/roadster routes:list",
        ]);

        return Command::SUCCESS;
    }
}
