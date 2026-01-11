<?php

declare(strict_types=1);

use Roadster\Console\Commands\RoutesListCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

describe('RoutesListCommand', function () {
    it('has correct name', function () {
        $command = new RoutesListCommand();

        expect($command->getName())->toBe('routes:list');
    });

    it('has description', function () {
        $command = new RoutesListCommand();

        expect($command->getDescription())->toBe('List all registered routes');
    });

    it('has filter option', function () {
        $command = new RoutesListCommand();
        $definition = $command->getDefinition();

        expect($definition->hasOption('filter'))->toBeTrue();
    });

    it('executes successfully', function () {
        $application = new Application();
        $application->add(new RoutesListCommand());

        $command = $application->find('routes:list');
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        expect($exitCode)->toBe(0);
    });

    it('shows registered routes title', function () {
        $application = new Application();
        $application->add(new RoutesListCommand());

        $command = $application->find('routes:list');
        $tester = new CommandTester($command);

        $tester->execute([]);
        $output = $tester->getDisplay();

        expect($output)->toContain('Registered Routes');
    });

    it('shows health route', function () {
        $application = new Application();
        $application->add(new RoutesListCommand());

        $command = $application->find('routes:list');
        $tester = new CommandTester($command);

        $tester->execute([]);
        $output = $tester->getDisplay();

        expect($output)->toContain('/health');
        expect($output)->toContain('HealthCheckHandler');
    });

    it('shows total routes count', function () {
        $application = new Application();
        $application->add(new RoutesListCommand());

        $command = $application->find('routes:list');
        $tester = new CommandTester($command);

        $tester->execute([]);
        $output = $tester->getDisplay();

        expect($output)->toContain('Total:');
    });
});
