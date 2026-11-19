<?php

declare(strict_types=1);

use Roadster\Console\Application;
use Roadster\Console\Commands\ServeReloadCommand;
use Roadster\Console\Commands\ServeStatusCommand;
use Roadster\Console\Commands\ServeStopCommand;
use Roadster\Console\RoadRunnerServer;
use Symfony\Component\Console\Tester\CommandTester;

describe('serve lifecycle commands', function () {
    beforeEach(function () {
        $this->dir = sys_get_temp_dir() . '/roadster_cmd_' . uniqid();
        mkdir($this->dir);
        $this->server = new RoadRunnerServer($this->dir);
    });

    afterEach(function () {
        if (is_file($this->dir . '/.pid')) {
            unlink($this->dir . '/.pid');
        }
        rmdir($this->dir);
    });

    it('registers serve, serve:stop, serve:reload and serve:status', function () {
        $app = new Application();

        foreach (['serve', 'serve:stop', 'serve:reload', 'serve:status'] as $name) {
            expect($app->has($name))->toBeTrue();
        }
    });

    it('serve:stop fails with a hint when no server is running', function () {
        $tester = new CommandTester(new ServeStopCommand($this->server));

        expect($tester->execute([]))->toBe(1);
        expect($tester->getDisplay())->toContain('No server running');
        expect($tester->getDisplay())->toContain('roadster serve');
    });

    it('serve:reload fails with a hint when no server is running', function () {
        $tester = new CommandTester(new ServeReloadCommand($this->server));

        expect($tester->execute([]))->toBe(1);
        expect($tester->getDisplay())->toContain('No server running');
    });

    it('serve:status reports not running with exit 1', function () {
        $tester = new CommandTester(new ServeStatusCommand($this->server));

        expect($tester->execute([]))->toBe(1);
        expect($tester->getDisplay())->toContain('not running');
    });

    it('serve:status reports the pid with exit 0', function () {
        file_put_contents($this->dir . '/.pid', (string) getmypid());
        $tester = new CommandTester(new ServeStatusCommand($this->server));

        expect($tester->execute([]))->toBe(0);
        expect($tester->getDisplay())->toContain('pid ' . getmypid());
    });

    it('serve:status removes a stale pid file', function () {
        file_put_contents($this->dir . '/.pid', '4194303');
        $tester = new CommandTester(new ServeStatusCommand($this->server));

        expect($tester->execute([]))->toBe(1);
        expect(file_exists($this->dir . '/.pid'))->toBeFalse();
    })->skip(!function_exists('posix_kill'), 'posix extension not available');
});
