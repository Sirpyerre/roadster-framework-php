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
    name: 'make:service',
    description: 'Generate a new Service class'
)]
final class MakeServiceCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Service name (e.g., UserService or User)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $input->getArgument('name');

        if (!is_string($name)) {
            $io->error('Service name is required');
            return Command::FAILURE;
        }

        $basePath = dirname(__DIR__, 3);

        // Remove Service suffix if present for domain naming
        $className = str_ends_with($name, 'Service') ? substr($name, 0, -7) : $name;
        $domain = $className;

        $targetDir = $basePath . '/src/Domain/' . $domain;
        $targetFile = $targetDir . '/' . $className . 'Service.php';

        if (file_exists($targetFile)) {
            $io->error("Service already exists: {$targetFile}");
            return Command::FAILURE;
        }

        // Read template
        $templatePath = $basePath . '/templates/service.php.stub';

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
            ['{{domain}}', '{{className}}'],
            [$domain, $className],
            $template
        );

        // Create directory if needed
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Write file
        file_put_contents($targetFile, $content);

        $io->success("Service created: src/Domain/{$domain}/{$className}Service.php");

        $io->section('Next steps');
        $io->listing([
            "Inject repositories in the constructor",
            "Add business logic methods",
            "No container registration needed: the service is autowired when its dependencies are typed",
        ]);

        return Command::SUCCESS;
    }
}
