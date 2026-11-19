<?php

declare(strict_types=1);

namespace Roadster\Console\Commands;

use Roadster\Console\RoadRunnerServer;
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
    public function __construct(private readonly RoadRunnerServer $server = new RoadRunnerServer())
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'Server port', '8080')
            ->addOption('workers', 'w', InputOption::VALUE_OPTIONAL, 'Number of workers', '4')
            ->addOption('debug', 'd', InputOption::VALUE_NONE, 'Enable debug mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        return $this->startServer($io, $input);
    }

    private function startServer(SymfonyStyle $io, InputInterface $input): int
    {
        $rrBinary = $this->server->findBinary();

        if ($rrBinary === null) {
            $io->error('RoadRunner binary not found. Run: ./vendor/bin/rr get-binary');
            return Command::FAILURE;
        }

        $configFile = $this->server->configFile();
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
        $io->writeln('<comment>Press Ctrl+C to stop, or run serve:stop from another terminal</comment>');
        $io->writeln('');

        // Start RoadRunner
        $command = $this->server->command($rrBinary, 'serve', ['-p']);

        passthru($command, $exitCode);

        return $exitCode === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
