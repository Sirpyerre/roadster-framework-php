<?php

declare(strict_types=1);

namespace Roadster\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'serve',
    description: 'Start the RoadRunner server'
)]
final class ServeCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'Server port', '8080')
            ->addOption('workers', 'w', InputOption::VALUE_OPTIONAL, 'Number of workers', '4')
            ->addOption('debug', 'd', InputOption::VALUE_NONE, 'Enable debug mode')
            ->addOption('stop', null, InputOption::VALUE_NONE, 'Stop the server')
            ->addOption('reload', null, InputOption::VALUE_NONE, 'Reload workers');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('stop')) {
            return $this->stopServer($io);
        }

        if ($input->getOption('reload')) {
            return $this->reloadServer($io);
        }

        return $this->startServer($io, $input);
    }

    private function startServer(SymfonyStyle $io, InputInterface $input): int
    {
        $basePath = dirname(__DIR__, 3);
        $rrBinary = $this->findRoadRunnerBinary($basePath);

        if ($rrBinary === null) {
            $io->error('RoadRunner binary not found. Run: ./vendor/bin/rr get-binary');
            return Command::FAILURE;
        }

        $configFile = $basePath . '/.rr.yaml';
        if (!file_exists($configFile)) {
            $io->error('RoadRunner config not found at: ' . $configFile);
            return Command::FAILURE;
        }

        $port = $input->getOption('port');
        $workers = $input->getOption('workers');
        $debug = $input->getOption('debug');

        // Set environment variables
        putenv("SERVER_PORT={$port}");
        putenv("RR_WORKERS={$workers}");

        if ($debug) {
            putenv('APP_DEBUG=true');
            putenv('LOG_LEVEL=debug');
        }

        $io->writeln('');
        $io->writeln('<fg=green>🚀 Roadster server starting...</>');
        $io->writeln('');

        $io->table(
            [],
            [
                ['Server', "<info>http://localhost:{$port}</info>"],
                ['Workers', "<info>{$workers}</info>"],
                ['Config', "<info>{$configFile}</info>"],
            ]
        );

        $io->writeln('');
        $io->writeln('<comment>Press Ctrl+C to stop</comment>');
        $io->writeln('');

        // Start RoadRunner
        $command = sprintf(
            '%s serve -c %s',
            escapeshellarg($rrBinary),
            escapeshellarg($configFile)
        );

        passthru($command, $exitCode);

        return $exitCode === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function stopServer(SymfonyStyle $io): int
    {
        $basePath = dirname(__DIR__, 3);
        $rrBinary = $this->findRoadRunnerBinary($basePath);

        if ($rrBinary === null) {
            $io->error('RoadRunner binary not found');
            return Command::FAILURE;
        }

        $io->writeln('<fg=yellow>Stopping RoadRunner server...</>');

        $command = sprintf('%s stop', escapeshellarg($rrBinary));
        passthru($command, $exitCode);

        if ($exitCode === 0) {
            $io->success('Server stopped');
        }

        return $exitCode === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function reloadServer(SymfonyStyle $io): int
    {
        $basePath = dirname(__DIR__, 3);
        $rrBinary = $this->findRoadRunnerBinary($basePath);

        if ($rrBinary === null) {
            $io->error('RoadRunner binary not found');
            return Command::FAILURE;
        }

        $io->writeln('<fg=cyan>Reloading workers...</>');

        $command = sprintf('%s reset', escapeshellarg($rrBinary));
        passthru($command, $exitCode);

        if ($exitCode === 0) {
            $io->success('Workers reloaded');
        }

        return $exitCode === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function findRoadRunnerBinary(string $basePath): ?string
    {
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
}
