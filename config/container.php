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
use Roadster\App\Middleware\ErrorHandlerMiddleware;
use Roadster\App\Middleware\JsonResponseMiddleware;
use Roadster\Web\Rest\HealthCheckHandler;

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
            ErrorHandlerMiddleware::class => InvokableFactory::class,
            JsonResponseMiddleware::class => InvokableFactory::class,

            // Handlers
            HealthCheckHandler::class => InvokableFactory::class,
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
