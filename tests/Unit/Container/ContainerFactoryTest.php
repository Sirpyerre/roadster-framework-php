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

describe('ContainerFactory autowiring', function () {
    it('resolves the Todo repository interface through config/dependencies', function () {
        $container = ContainerFactory::create();

        expect($container->get(\Roadster\Domain\Todo\TodoRepositoryInterface::class))
            ->toBeInstanceOf(\Roadster\Infrastructure\Todo\JsonTodoRepository::class);
    });

    it('autowires TodoService and the Todo handlers without explicit factories', function () {
        $container = ContainerFactory::create();
        $factories = $container->get('config')['dependencies']['factories'];

        $classes = [
            \Roadster\Domain\Todo\TodoService::class,
            \Roadster\Web\Rest\Todo\IndexTodoHandler::class,
            \Roadster\Web\Rest\Todo\CreateTodoHandler::class,
            \Roadster\Web\Rest\Todo\ShowTodoHandler::class,
            \Roadster\Web\Rest\Todo\UpdateTodoHandler::class,
            \Roadster\Web\Rest\Todo\DeleteTodoHandler::class,
        ];

        foreach ($classes as $class) {
            expect($factories)->not->toHaveKey($class);
            expect($container->get($class))->toBeInstanceOf($class);
        }
    });

    it('uses the default for optional scalar constructor parameters', function () {
        $container = ContainerFactory::create();

        // JsonTodoRepository::__construct(?string $filePath = null)
        expect($container->get(\Roadster\Infrastructure\Todo\JsonTodoRepository::class))
            ->toBeInstanceOf(\Roadster\Infrastructure\Todo\JsonTodoRepository::class);
    });

    it('lets an explicit factory override autowiring', function () {
        $explicit = new \stdClass();
        $config = ContainerFactory::create()->get('config')['dependencies'];
        $config['factories'][\Roadster\Domain\Todo\TodoService::class] = fn () => $explicit;

        $container = new \Laminas\ServiceManager\ServiceManager($config);

        expect($container->get(\Roadster\Domain\Todo\TodoService::class))->toBe($explicit);
    });
});

describe('ContainerFactory database connection', function () {
    it('resolves a DBAL connection from the container', function () {
        $container = ContainerFactory::create();

        expect($container->get(\Doctrine\DBAL\Connection::class))
            ->toBeInstanceOf(\Doctrine\DBAL\Connection::class);
    });

    it('returns the same connection instance every time', function () {
        $container = ContainerFactory::create();

        expect($container->get(\Doctrine\DBAL\Connection::class))
            ->toBe($container->get(\Doctrine\DBAL\Connection::class));
    });

    it('does not connect until a query runs', function () {
        $connection = ContainerFactory::create()->get(\Doctrine\DBAL\Connection::class);

        expect($connection->isConnected())->toBeFalse();
        expect($connection->fetchOne('SELECT 1'))->toBe(1);
        expect($connection->isConnected())->toBeTrue();
    });
});
