<?php

declare(strict_types=1);

namespace Roadster\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'make:middleware',
    description: 'Generate a new Middleware class'
)]
final class MakeMiddlewareCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Middleware name (e.g., Auth or AuthMiddleware)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $input->getArgument('name');

        if (!is_string($name)) {
            $io->error('Middleware name is required');
            return Command::FAILURE;
        }

        $basePath = dirname(__DIR__, 3);

        // Remove Middleware suffix if present for naming
        $className = str_ends_with($name, 'Middleware') ? substr($name, 0, -10) : $name;

        $targetDir = $basePath . '/src/App/Middleware';
        $targetFile = $targetDir . '/' . $className . 'Middleware.php';

        if (file_exists($targetFile)) {
            $io->error("Middleware already exists: {$targetFile}");
            return Command::FAILURE;
        }

        // Read template
        $templatePath = $basePath . '/templates/middleware.php.stub';

        if (!file_exists($templatePath)) {
            $io->error("Template not found: {$templatePath}");
            return Command::FAILURE;
        }

        $template = file_get_contents($templatePath);

        if ($template === false) {
            $io->error('Failed to read template');
            return Command::FAILURE;
        }

        // Replace placeholders
        $content = str_replace(
            ['{{className}}'],
            [$className],
            $template
        );

        // Create directory if needed
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Write file
        file_put_contents($targetFile, $content);

        $io->success("Middleware created: src/App/Middleware/{$className}Middleware.php");

        $io->section('Next steps');
        $io->listing([
            "Implement the process() method",
            "Register in config/container.php",
            "Add to pipeline in public/index.php:",
            "  \$app->pipe({$className}Middleware::class);",
        ]);

        return Command::SUCCESS;
    }
}
