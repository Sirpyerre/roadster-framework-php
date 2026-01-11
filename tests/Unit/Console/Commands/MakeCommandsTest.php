<?php

declare(strict_types=1);

use Roadster\Console\Commands\MakeHandlerCommand;
use Roadster\Console\Commands\MakeMiddlewareCommand;
use Roadster\Console\Commands\MakeServiceCommand;
use Roadster\Console\Commands\ScaffoldCrudCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

describe('MakeHandlerCommand', function () {
    it('has correct name', function () {
        $command = new MakeHandlerCommand();
        expect($command->getName())->toBe('make:handler');
    });

    it('requires name argument', function () {
        $command = new MakeHandlerCommand();
        $definition = $command->getDefinition();
        expect($definition->hasArgument('name'))->toBeTrue();
        expect($definition->getArgument('name')->isRequired())->toBeTrue();
    });

    it('creates handler file', function () {
        $application = new Application();
        $application->add(new MakeHandlerCommand());

        $command = $application->find('make:handler');
        $tester = new CommandTester($command);

        $tester->execute(['name' => 'TestGenerated']);

        $targetFile = basePath('src/Web/Rest/TestGeneratedHandler.php');

        expect(file_exists($targetFile))->toBeTrue();
        expect(file_get_contents($targetFile))->toContain('class TestGeneratedHandler');

        // Cleanup
        unlink($targetFile);
    });
});

describe('MakeServiceCommand', function () {
    it('has correct name', function () {
        $command = new MakeServiceCommand();
        expect($command->getName())->toBe('make:service');
    });

    it('creates service file', function () {
        $application = new Application();
        $application->add(new MakeServiceCommand());

        $command = $application->find('make:service');
        $tester = new CommandTester($command);

        $tester->execute(['name' => 'TestGenerated']);

        $targetFile = basePath('src/Domain/TestGenerated/TestGeneratedService.php');

        expect(file_exists($targetFile))->toBeTrue();
        expect(file_get_contents($targetFile))->toContain('class TestGeneratedService');

        // Cleanup
        unlink($targetFile);
        rmdir(basePath('src/Domain/TestGenerated'));
    });
});

describe('MakeMiddlewareCommand', function () {
    it('has correct name', function () {
        $command = new MakeMiddlewareCommand();
        expect($command->getName())->toBe('make:middleware');
    });

    it('creates middleware file', function () {
        $application = new Application();
        $application->add(new MakeMiddlewareCommand());

        $command = $application->find('make:middleware');
        $tester = new CommandTester($command);

        $tester->execute(['name' => 'TestGenerated']);

        $targetFile = basePath('src/App/Middleware/TestGeneratedMiddleware.php');

        expect(file_exists($targetFile))->toBeTrue();
        expect(file_get_contents($targetFile))->toContain('class TestGeneratedMiddleware');

        // Cleanup
        unlink($targetFile);
    });
});

describe('ScaffoldCrudCommand', function () {
    it('has correct name', function () {
        $command = new ScaffoldCrudCommand();
        expect($command->getName())->toBe('scaffold:crud');
    });

    it('requires resource argument', function () {
        $command = new ScaffoldCrudCommand();
        $definition = $command->getDefinition();
        expect($definition->hasArgument('resource'))->toBeTrue();
        expect($definition->getArgument('resource')->isRequired())->toBeTrue();
    });
});
