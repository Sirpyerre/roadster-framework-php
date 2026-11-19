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
    name: 'serve:status',
    description: 'Show whether the RoadRunner server is running'
)]
final class ServeStatusCommand extends Command
{
    public function __construct(private readonly RoadRunnerServer $server = new RoadRunnerServer())
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $pid = $this->server->pid();

        if ($pid === null) {
            $io->writeln('Server is not running.');

            return Command::FAILURE;
        }

        $io->writeln("Server is running (pid {$pid}).");

        return Command::SUCCESS;
    }
}
