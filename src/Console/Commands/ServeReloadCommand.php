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
    name: 'serve:reload',
    description: 'Reload the RoadRunner workers'
)]
final class ServeReloadCommand extends Command
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

        $io->writeln('<fg=yellow>Reloading workers...</>');

        passthru($this->server->command($rrBinary, 'reset'), $exitCode);

        if ($exitCode === 0) {
            $io->success('Workers reloaded');
        }

        return $exitCode === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
