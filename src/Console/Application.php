<?php

declare(strict_types=1);

namespace Roadster\Console;

use Roadster\Console\Commands\InitCommand;
use Roadster\Console\Commands\RoutesListCommand;
use Roadster\Console\Commands\ServeCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

final class Application extends SymfonyApplication
{
    private const NAME = 'Roadster';
    private const VERSION = '0.1.0';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->registerCommands();
    }

    private function registerCommands(): void
    {
        $this->addCommands([
            new InitCommand(),
            new ServeCommand(),
            new RoutesListCommand(),
        ]);
    }
}
