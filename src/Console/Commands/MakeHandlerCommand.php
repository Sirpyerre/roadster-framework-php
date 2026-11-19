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
    name: 'make:handler',
    description: 'Generate a new Request Handler'
)]
final class MakeHandlerCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Handler name (e.g., CreateUser or User/Create)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $input->getArgument('name');

        if (!is_string($name)) {
            $io->error('Handler name is required');
            return Command::FAILURE;
        }

        $basePath = dirname(__DIR__, 3);

        // Parse name for subdirectory support
        $parts = explode('/', $name);
        $className = array_pop($parts);
        $namespace = !empty($parts) ? '\\' . implode('\\', $parts) : '';
        $directory = !empty($parts) ? '/' . implode('/', $parts) : '';

        // Remove Handler suffix if present (will be added to filename)
        if (str_ends_with($className, 'Handler')) {
            $className = substr($className, 0, -7);
        }

        $targetDir = $basePath . '/src/Web/Rest' . $directory;
        $targetFile = $targetDir . '/' . $className . 'Handler.php';

        if (file_exists($targetFile)) {
            $io->error("Handler already exists: {$targetFile}");
            return Command::FAILURE;
        }

        // Read template
        $templatePath = $basePath . '/templates/handler.php.stub';

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
            ['{{namespace}}', '{{className}}'],
            [$namespace, $className],
            $template
        );

        // Create directory if needed
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Write file
        file_put_contents($targetFile, $content);

        $io->success("Handler created: src/Web/Rest{$directory}/{$className}Handler.php");

        $io->section('Next steps');
        $io->listing([
            "Add a route in a file under config/routes/ (every file there is loaded):",
            "  \$app->get('/api/endpoint', {$className}Handler::class, 'endpoint.name');",
            "Constructor dependencies with typed classes/interfaces are autowired; no container registration needed",
        ]);

        return Command::SUCCESS;
    }
}
