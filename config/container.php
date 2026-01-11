<?php

declare(strict_types=1);

use Laminas\ServiceManager\Factory\InvokableFactory;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\RouterInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\ResponseFactory;
use Roadster\App\Middleware\BodyParamsMiddleware;
use Roadster\App\Middleware\ErrorHandlerMiddleware;
use Roadster\App\Middleware\JsonResponseMiddleware;
use Roadster\Web\Rest\HealthCheckHandler;
use Roadster\Web\Rest\Todo\IndexTodoHandler;
use Roadster\Web\Rest\Todo\CreateTodoHandler;
use Roadster\Web\Rest\Todo\ShowTodoHandler;
use Roadster\Web\Rest\Todo\UpdateTodoHandler;
use Roadster\Web\Rest\Todo\DeleteTodoHandler;
use Roadster\Domain\Todo\TodoService;
use Roadster\Domain\Todo\TodoRepositoryInterface;
use Roadster\Infrastructure\Todo\JsonTodoRepository;

// Get Mezzio default configuration
$mezzioConfig = (new \Mezzio\ConfigProvider())();
$routerConfig = (new \Mezzio\Router\ConfigProvider())();
$fastRouteConfig = (new \Mezzio\Router\FastRouteRouter\ConfigProvider())();

// Merge all dependency configurations
$dependencies = array_merge_recursive(
    $mezzioConfig['dependencies'] ?? [],
    $routerConfig['dependencies'] ?? [],
    $fastRouteConfig['dependencies'] ?? [],
    [
        'factories' => [
            // Override router with FastRoute
            RouterInterface::class => function () {
                return new FastRouteRouter();
            },

            // PSR-17 Factories
            StreamFactoryInterface::class => function () {
                return new StreamFactory();
            },
            ResponseFactoryInterface::class => function () {
                return new ResponseFactory();
            },

            // Roadster Middleware
            BodyParamsMiddleware::class => InvokableFactory::class,
            ErrorHandlerMiddleware::class => InvokableFactory::class,
            JsonResponseMiddleware::class => InvokableFactory::class,

            // Handlers
            HealthCheckHandler::class => InvokableFactory::class,

            // Todo API - Repository
            TodoRepositoryInterface::class => function () {
                return new JsonTodoRepository();
            },

            // Todo API - Service
            TodoService::class => function ($container) {
                return new TodoService(
                    $container->get(TodoRepositoryInterface::class)
                );
            },

            // Todo API - Handlers
            IndexTodoHandler::class => function ($container) {
                return new IndexTodoHandler(
                    $container->get(TodoService::class)
                );
            },
            CreateTodoHandler::class => function ($container) {
                return new CreateTodoHandler(
                    $container->get(TodoService::class)
                );
            },
            ShowTodoHandler::class => function ($container) {
                return new ShowTodoHandler(
                    $container->get(TodoService::class)
                );
            },
            UpdateTodoHandler::class => function ($container) {
                return new UpdateTodoHandler(
                    $container->get(TodoService::class)
                );
            },
            DeleteTodoHandler::class => function ($container) {
                return new DeleteTodoHandler(
                    $container->get(TodoService::class)
                );
            },
        ],
    ]
);

return [
    'dependencies' => $dependencies,

    // Mezzio configuration
    'mezzio' => [
        'error_handler' => [
            'template_404' => '',
            'template_error' => '',
        ],
    ],
];
