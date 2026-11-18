<?php

declare(strict_types=1);

use Roadster\Console\Commands\ServeCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

describe('ServeCommand', function () {
    it('has correct name', function () {
        $command = new ServeCommand();

        expect($command->getName())->toBe('serve');
    });

    it('has description', function () {
        $command = new ServeCommand();

        expect($command->getDescription())->toBe('Start the RoadRunner server');
    });

    it('has port option', function () {
        $command = new ServeCommand();
        $definition = $command->getDefinition();

        expect($definition->hasOption('port'))->toBeTrue();
        expect($definition->getOption('port')->getDefault())->toBe('8080');
    });

    it('has workers option', function () {
        $command = new ServeCommand();
        $definition = $command->getDefinition();

        expect($definition->hasOption('workers'))->toBeTrue();
        expect($definition->getOption('workers')->getDefault())->toBe('4');
    });

    it('has debug option', function () {
        $command = new ServeCommand();
        $definition = $command->getDefinition();

        expect($definition->hasOption('debug'))->toBeTrue();
    });

    it('has stop option', function () {
        $command = new ServeCommand();
        $definition = $command->getDefinition();

        expect($definition->hasOption('stop'))->toBeTrue();
    });

    it('has reload option', function () {
        $command = new ServeCommand();
        $definition = $command->getDefinition();

        expect($definition->hasOption('reload'))->toBeTrue();
    });

    it('ships a RoadRunner config as a regular file', function () {
        $config = basePath('.rr.yaml');

        expect(is_link($config))->toBeFalse();
        expect(is_file($config))->toBeTrue();
        expect(is_readable($config))->toBeTrue();

        $content = (string) file_get_contents($config);

        expect($content)->toContain('version: "3"');
        expect($content)->toContain('server:');
        expect($content)->toContain('http:');
    });
});
