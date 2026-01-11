<?php

declare(strict_types=1);

use Roadster\Console\Application;

describe('Console Application', function () {
    it('has correct name', function () {
        $app = new Application();

        expect($app->getName())->toBe('Roadster');
    });

    it('has correct version', function () {
        $app = new Application();

        expect($app->getVersion())->toBe('0.1.0');
    });

    it('has init command registered', function () {
        $app = new Application();

        expect($app->has('init'))->toBeTrue();
    });

    it('has serve command registered', function () {
        $app = new Application();

        expect($app->has('serve'))->toBeTrue();
    });

    it('has routes:list command registered', function () {
        $app = new Application();

        expect($app->has('routes:list'))->toBeTrue();
    });
});
