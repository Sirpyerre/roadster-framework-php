<?php

declare(strict_types=1);

use Roadster\Console\RoadRunnerServer;

describe('RoadRunnerServer', function () {
    beforeEach(function () {
        $this->dir = sys_get_temp_dir() . '/roadster_rr_' . uniqid();
        mkdir($this->dir);
        $this->server = new RoadRunnerServer($this->dir);
    });

    afterEach(function () {
        if (is_file($this->dir . '/.pid')) {
            unlink($this->dir . '/.pid');
        }
        rmdir($this->dir);
    });

    it('is not running without a pid file', function () {
        expect($this->server->pid())->toBeNull();
        expect($this->server->isRunning())->toBeFalse();
    });

    it('reports a live pid as running', function () {
        file_put_contents($this->dir . '/.pid', (string) getmypid());

        expect($this->server->pid())->toBe(getmypid());
        expect($this->server->isRunning())->toBeTrue();
    });

    it('ignores a pid file with invalid content', function () {
        file_put_contents($this->dir . '/.pid', 'not-a-pid');

        expect($this->server->isRunning())->toBeFalse();
    });

    it('removes a stale pid file', function () {
        // Highest pid values are practically never in use
        file_put_contents($this->dir . '/.pid', '4194303');

        expect($this->server->isRunning())->toBeFalse();
        expect(file_exists($this->dir . '/.pid'))->toBeFalse();
    })->skip(!function_exists('posix_kill'), 'posix extension not available');

    it('builds commands with working directory and config', function () {
        $command = $this->server->command('/usr/bin/rr', 'serve', ['-p']);

        expect($command)->toBe(sprintf(
            "'/usr/bin/rr' -w '%s' -c '%s/.rr.yaml' -p serve",
            $this->dir,
            $this->dir
        ));
    });
});
