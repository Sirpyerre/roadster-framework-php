<?php

declare(strict_types=1);

namespace Roadster\Console;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Roadster\Console\Commands\InitCommand;
use Roadster\Console\Commands\MakeHandlerCommand;
use Roadster\Console\Commands\MakeMiddlewareCommand;
use Roadster\Console\Commands\MakeMigrationCommand;
use Roadster\Console\Commands\MakeRepositoryCommand;
use Roadster\Console\Commands\MakeServiceCommand;
use Roadster\Console\Commands\MigrateRollbackCommand;
use Roadster\Console\Commands\RoutesListCommand;
use Roadster\Console\Commands\ScaffoldCrudCommand;
use Roadster\Console\Commands\ServeCommand;
use Roadster\Console\Commands\ServeReloadCommand;
use Roadster\Console\Commands\ServeStatusCommand;
use Roadster\Console\Commands\ServeStopCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

final class Application extends SymfonyApplication
{
    private const NAME = 'Roadster';
    private const VERSION = '0.1.0';

    public function __construct(?DependencyFactory $migrations = null)
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->registerCommands($migrations ?? DatabaseMigrations::dependencyFactory());
    }

    private function registerCommands(DependencyFactory $migrations): void
    {
        // Doctrine's engine under Roadster command names (no migrations:* aliases)
        $migrate = new MigrateCommand($migrations, 'migrate');
        $migrate->setAliases([]);
        $status = new StatusCommand($migrations, 'migrate:status');
        $status->setAliases([]);

        $this->addCommands([
            // Server commands
            new InitCommand(),
            new ServeCommand(),
            new ServeStopCommand(),
            new ServeReloadCommand(),
            new ServeStatusCommand(),
            new RoutesListCommand(),

            // Generator commands
            new MakeHandlerCommand(),
            new MakeServiceCommand(),
            new MakeRepositoryCommand(),
            new MakeMiddlewareCommand(),
            new ScaffoldCrudCommand(),
            new MakeMigrationCommand(),

            // Database commands
            $migrate,
            $status,
            new MigrateRollbackCommand($migrations),
        ]);
    }
}
