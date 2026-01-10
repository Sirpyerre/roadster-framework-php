<?php

declare(strict_types=1);

namespace Roadster\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'init',
    description: 'Initialize a new Roadster project'
)]
final class InitCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Roadster Project Initialization');

        $basePath = dirname(__DIR__, 3);

        // Step 1: Create .env file
        $this->createEnvFile($io, $basePath);

        // Step 2: Verify RoadRunner installation
        $this->verifyRoadRunner($io);

        // Step 3: Verify PHP extensions
        $this->verifyPhpExtensions($io);

        // Step 4: Generate APP_KEY if needed
        $this->generateAppKey($io, $basePath);

        // Step 5: Create required directories
        $this->createDirectories($io, $basePath);

        $io->success('Project initialized successfully!');

        $io->section('Next steps');
        $io->listing([
            'Configure your .env file',
            'Run: ./bin/roadster serve',
        ]);

        return Command::SUCCESS;
    }

    private function createEnvFile(SymfonyStyle $io, string $basePath): void
    {
        $envFile = $basePath . '/.env';
        $envExample = $basePath . '/.env.example';

        if (file_exists($envFile)) {
            $io->note('.env file already exists');
            return;
        }

        if (!file_exists($envExample)) {
            $io->warning('.env.example not found');
            return;
        }

        copy($envExample, $envFile);
        $io->success('.env file created');
    }

    private function verifyRoadRunner(SymfonyStyle $io): void
    {
        $rrBinary = $this->findRoadRunnerBinary();

        if ($rrBinary !== null) {
            $io->success('RoadRunner binary found: ' . $rrBinary);
            return;
        }

        $io->warning('RoadRunner binary not found. Run: ./vendor/bin/rr get-binary');
    }

    private function findRoadRunnerBinary(): ?string
    {
        $basePath = dirname(__DIR__, 3);

        $possiblePaths = [
            $basePath . '/rr',
            $basePath . '/vendor/bin/rr',
            '/usr/local/bin/rr',
            '/usr/bin/rr',
        ];

        foreach ($possiblePaths as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        // Check PATH
        $which = shell_exec('which rr 2>/dev/null');
        if ($which !== null && trim($which) !== '') {
            return trim($which);
        }

        return null;
    }

    private function verifyPhpExtensions(SymfonyStyle $io): void
    {
        $required = ['json', 'mbstring', 'sockets'];
        $missing = [];

        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }

        if (empty($missing)) {
            $io->success('All required PHP extensions are installed');
            return;
        }

        $io->error('Missing PHP extensions: ' . implode(', ', $missing));
    }

    private function generateAppKey(SymfonyStyle $io, string $basePath): void
    {
        $envFile = $basePath . '/.env';

        if (!file_exists($envFile)) {
            return;
        }

        $content = file_get_contents($envFile);

        if ($content === false) {
            return;
        }

        // Check if APP_KEY is empty
        if (!preg_match('/^APP_KEY=\s*$/m', $content)) {
            $io->note('APP_KEY already set');
            return;
        }

        $key = 'base64:' . base64_encode(random_bytes(32));
        $content = preg_replace('/^APP_KEY=\s*$/m', 'APP_KEY=' . $key, $content);

        if ($content !== null) {
            file_put_contents($envFile, $content);
            $io->success('APP_KEY generated');
        }
    }

    private function createDirectories(SymfonyStyle $io, string $basePath): void
    {
        $dirs = [
            'src/Domain',
            'src/Infrastructure',
            'src/Web/Rest',
            'tests/Feature',
            'tests/Unit',
            'templates',
        ];

        $created = 0;
        foreach ($dirs as $dir) {
            $path = $basePath . '/' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
                $created++;
            }
        }

        if ($created > 0) {
            $io->success("Created {$created} directories");
        } else {
            $io->note('All directories already exist');
        }
    }
}
