<?php

declare(strict_types=1);

use Roadster\Console\Commands\InitCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

describe('InitCommand', function () {
    it('has correct name', function () {
        $command = new InitCommand();

        expect($command->getName())->toBe('init');
    });

    it('has description', function () {
        $command = new InitCommand();

        expect($command->getDescription())->toBe('Initialize a new Roadster project');
    });

    it('executes successfully', function () {
        $application = new Application();
        $application->add(new InitCommand());

        $command = $application->find('init');
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        expect($exitCode)->toBe(0);
    });

    it('shows initialization title', function () {
        $application = new Application();
        $application->add(new InitCommand());

        $command = $application->find('init');
        $tester = new CommandTester($command);

        $tester->execute([]);
        $output = $tester->getDisplay();

        expect($output)->toContain('Roadster Project Initialization');
    });

    it('shows success message', function () {
        $application = new Application();
        $application->add(new InitCommand());

        $command = $application->find('init');
        $tester = new CommandTester($command);

        $tester->execute([]);
        $output = $tester->getDisplay();

        expect($output)->toContain('Project initialized successfully');
    });

    it('shows next steps', function () {
        $application = new Application();
        $application->add(new InitCommand());

        $command = $application->find('init');
        $tester = new CommandTester($command);

        $tester->execute([]);
        $output = $tester->getDisplay();

        expect($output)->toContain('Next steps');
        expect($output)->toContain('./bin/roadster serve');
    });
});
