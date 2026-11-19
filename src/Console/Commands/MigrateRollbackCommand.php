<?php

declare(strict_types=1);

namespace Roadster\Console\Commands;

use Doctrine\Migrations\DependencyFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'migrate:rollback',
    description: 'Revert the last executed migration'
)]
final class MigrateRollbackCommand extends Command
{
    public function __construct(private readonly DependencyFactory $migrations)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $storage = $this->migrations->getMetadataStorage();
        $storage->ensureInitialized();

        if (count($storage->getExecutedMigrations()) === 0) {
            $io->writeln('Nothing to roll back.');

            return Command::SUCCESS;
        }

        $application = $this->getApplication();

        if ($application === null) {
            $io->error('Command is not attached to an application');

            return Command::FAILURE;
        }

        // "prev" resolves to the migration before the last executed one
        $migrate = new ArrayInput(['command' => 'migrate', 'version' => 'prev']);
        $migrate->setInteractive($input->isInteractive());

        return $application->find('migrate')->run($migrate, $output);
    }
}
