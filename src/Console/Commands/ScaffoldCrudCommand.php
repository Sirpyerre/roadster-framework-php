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
    name: 'scaffold:crud',
    description: 'Generate complete CRUD for a resource'
)]
final class ScaffoldCrudCommand extends Command
{
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

        $basePath = dirname(__DIR__, 3);
        $templatesPath = $basePath . '/templates/crud';
        $createdFiles = [];

        // Create directories
        $directories = [
            $basePath . '/src/Web/Rest/' . $resource,
            $basePath . '/src/Domain/' . $resource,
            $basePath . '/src/Infrastructure/' . $resource,
            $basePath . '/config/routes',
            $basePath . '/storage',
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        // Files to generate
        $files = [
            // Handlers
            [
                'template' => 'IndexHandler.php.stub',
                'target' => "src/Web/Rest/{$resource}/Index{$resource}Handler.php",
            ],
            [
                'template' => 'CreateHandler.php.stub',
                'target' => "src/Web/Rest/{$resource}/Create{$resource}Handler.php",
            ],
            [
                'template' => 'ShowHandler.php.stub',
                'target' => "src/Web/Rest/{$resource}/Show{$resource}Handler.php",
            ],
            [
                'template' => 'UpdateHandler.php.stub',
                'target' => "src/Web/Rest/{$resource}/Update{$resource}Handler.php",
            ],
            [
                'template' => 'DeleteHandler.php.stub',
                'target' => "src/Web/Rest/{$resource}/Delete{$resource}Handler.php",
            ],
            // Domain
            [
                'template' => 'Entity.php.stub',
                'target' => "src/Domain/{$resource}/{$resource}.php",
            ],
            [
                'template' => 'Service.php.stub',
                'target' => "src/Domain/{$resource}/{$resource}Service.php",
            ],
            [
                'template' => 'Repository.php.stub',
                'target' => "src/Domain/{$resource}/{$resource}RepositoryInterface.php",
            ],
            // Infrastructure
            [
                'template' => 'JsonRepository.php.stub',
                'target' => "src/Infrastructure/{$resource}/Json{$resource}Repository.php",
            ],
            // Routes
            [
                'template' => 'routes.php.stub',
                'target' => "config/routes/{$variableName}.php",
            ],
        ];

        $io->progressStart(count($files));

        foreach ($files as $file) {
            $templateFile = $templatesPath . '/' . $file['template'];
            $targetFile = $basePath . '/' . $file['target'];

            if (!file_exists($templateFile)) {
                $io->error("Template not found: {$file['template']}");
                return Command::FAILURE;
            }

            if (file_exists($targetFile)) {
                $io->warning("File already exists, skipping: {$file['target']}");
                $io->progressAdvance();
                continue;
            }

            $template = file_get_contents($templateFile);

            if ($template === false) {
                $io->error("Failed to read template: {$file['template']}");
                return Command::FAILURE;
            }

            // Replace placeholders
            $content = str_replace(
                ['{{resource}}', '{{variableName}}', '{{routeName}}'],
                [$resource, $variableName, $routeName],
                $template
            );

            file_put_contents($targetFile, $content);
            $createdFiles[] = $file['target'];

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
            "Include routes in config/routes.php:",
            "  (require configPath('routes/{$variableName}.php'))(\$app, \$factory, \$container);",
            "",
            "Register services in config/container.php:",
            "  {$resource}Service::class => function (\$container) {",
            "      return new {$resource}Service(",
            "          \$container->get({$resource}RepositoryInterface::class)",
            "      );",
            "  },",
            "  {$resource}RepositoryInterface::class => function () {",
            "      return new Json{$resource}Repository();",
            "  },",
            "",
            "Register handlers in config/container.php:",
            "  Index{$resource}Handler::class => ...,",
            "  Create{$resource}Handler::class => ...,",
            "  Show{$resource}Handler::class => ...,",
            "  Update{$resource}Handler::class => ...,",
            "  Delete{$resource}Handler::class => ...,",
            "",
            "Run: ./bin/roadster routes:list",
        ]);

        return Command::SUCCESS;
    }
}
