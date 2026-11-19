<?php

declare(strict_types=1);

namespace Roadster\Console\Commands;

use DateTimeImmutable;
use DateTimeZone;
use Roadster\Console\DatabaseMigrations;
use Roadster\Console\StubWriter;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'make:migration',
    description: 'Generate a new database migration'
)]
final class MakeMigrationCommand extends Command
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
            ->addArgument('name', InputArgument::REQUIRED, 'Migration name in CamelCase (e.g., CreateProducts)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $input->getArgument('name');

        if (!is_string($name) || preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $name) !== 1) {
            $io->error('Migration name must be CamelCase letters and digits, starting with a letter (e.g., CreateProducts)');
            return Command::FAILURE;
        }

        $name = ucfirst($name);
        $timestamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('YmdHis');
        $class = "Version{$timestamp}{$name}";
        $target = "database/migrations/{$class}.php";

        $writer = new StubWriter($this->basePath, dirname(__DIR__, 3) . '/templates');

        try {
            $created = $writer->write('migration.php.stub', $target, [
                'namespace' => DatabaseMigrations::namespace(),
                'class' => $class,
                'description' => $this->description($name),
            ]);
        } catch (RuntimeException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        if (!$created) {
            $io->error("Migration already exists: {$target}");
            return Command::FAILURE;
        }

        $io->success("Migration created: {$target}");

        $io->section('Next steps');
        $io->listing([
            'Fill in up() and down() with $this->addSql(...) or the Schema API',
            'Run: ./bin/roadster migrate',
            'Undo the last one with: ./bin/roadster migrate:rollback',
        ]);

        return Command::SUCCESS;
    }

    /**
     * "CreateProducts" => "Create products".
     */
    private function description(string $name): string
    {
        $words = preg_replace('/(?<!^)(?=[A-Z])/', ' ', $name) ?? $name;

        return ucfirst(strtolower($words));
    }
}
