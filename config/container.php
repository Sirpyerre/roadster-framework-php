<?php

declare(strict_types=1);

use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
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
        // Concrete classes with typed constructor dependencies are autowired
        'abstract_factories' => [
            ReflectionBasedAbstractFactory::class,
        ],
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
        ],
    ]
);

// Interface bindings and extra factories live in config/dependencies/*.php.
// Files are loaded alphabetically; each may only define 'aliases' and 'factories',
// and a later file replaces an earlier entry with the same key.
$dependencyFiles = glob(__DIR__ . '/dependencies/*.php') ?: [];
sort($dependencyFiles);

foreach ($dependencyFiles as $dependencyFile) {
    $extra = require $dependencyFile;

    foreach (['aliases', 'factories'] as $section) {
        $dependencies[$section] = array_replace($dependencies[$section] ?? [], $extra[$section] ?? []);
    }
}

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
