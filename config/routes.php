<?php

declare(strict_types=1);

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;
use Roadster\Web\Rest\HealthCheckHandler;

/**
 * Configure application routes.
 */
return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    // Health check endpoint
    $app->get('/health', HealthCheckHandler::class, 'health');

    // Every file in config/routes/ returns a function that registers its routes
    $routeFiles = glob(configPath('routes/*.php')) ?: [];
    sort($routeFiles);

    foreach ($routeFiles as $routeFile) {
        (require $routeFile)($app, $factory, $container);
    }
};
