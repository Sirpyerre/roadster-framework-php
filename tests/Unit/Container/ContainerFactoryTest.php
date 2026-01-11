<?php

declare(strict_types=1);

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;
use Roadster\App\Container\ContainerFactory;
use Roadster\App\Middleware\ErrorHandlerMiddleware;
use Roadster\App\Middleware\JsonResponseMiddleware;
use Roadster\Web\Rest\HealthCheckHandler;

describe('ContainerFactory', function () {
    it('creates a PSR-11 container', function () {
        $container = ContainerFactory::create();

        expect($container)->toBeInstanceOf(ContainerInterface::class);
    });

    it('has config service', function () {
        $container = ContainerFactory::create();

        expect($container->has('config'))->toBeTrue();
    });

    it('can resolve Mezzio Application', function () {
        $container = ContainerFactory::create();

        expect($container->has(Application::class))->toBeTrue();

        $app = $container->get(Application::class);
        expect($app)->toBeInstanceOf(Application::class);
    });

    it('can resolve MiddlewareFactory', function () {
        $container = ContainerFactory::create();

        expect($container->has(MiddlewareFactory::class))->toBeTrue();

        $factory = $container->get(MiddlewareFactory::class);
        expect($factory)->toBeInstanceOf(MiddlewareFactory::class);
    });

    it('can resolve ErrorHandlerMiddleware', function () {
        $container = ContainerFactory::create();

        expect($container->has(ErrorHandlerMiddleware::class))->toBeTrue();

        $middleware = $container->get(ErrorHandlerMiddleware::class);
        expect($middleware)->toBeInstanceOf(ErrorHandlerMiddleware::class);
    });

    it('can resolve JsonResponseMiddleware', function () {
        $container = ContainerFactory::create();

        expect($container->has(JsonResponseMiddleware::class))->toBeTrue();

        $middleware = $container->get(JsonResponseMiddleware::class);
        expect($middleware)->toBeInstanceOf(JsonResponseMiddleware::class);
    });

    it('can resolve HealthCheckHandler', function () {
        $container = ContainerFactory::create();

        expect($container->has(HealthCheckHandler::class))->toBeTrue();

        $handler = $container->get(HealthCheckHandler::class);
        expect($handler)->toBeInstanceOf(HealthCheckHandler::class);
    });
});
