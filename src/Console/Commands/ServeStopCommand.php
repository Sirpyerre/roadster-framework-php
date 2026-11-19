<?php

declare(strict_types=1);

namespace Roadster\Console\Commands;

use Roadster\Console\RoadRunnerServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'serve:stop',
    description: 'Stop the RoadRunner server'
)]
final class ServeStopCommand extends Command
{
    public function __construct(private readonly RoadRunnerServer $server = new RoadRunnerServer())
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->server->isRunning()) {
            $io->error('No server running. Start it with: roadster serve');

            return Command::FAILURE;
        }

        $rrBinary = $this->server->findBinary();

        if ($rrBinary === null) {
            $io->error('RoadRunner binary not found');

            return Command::FAILURE;
        }

        $io->writeln('<fg=yellow>Stopping RoadRunner server...</>');

        passthru($this->server->command($rrBinary, 'stop'), $exitCode);

        if ($exitCode === 0) {
            // rr leaves the pid file behind; the process is gone, so drop it
            if (is_file($this->server->pidFile())) {
                unlink($this->server->pidFile());
            }

            $io->success('Server stopped');
        }

        return $exitCode === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
