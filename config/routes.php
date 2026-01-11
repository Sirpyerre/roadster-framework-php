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

    // TODO API routes
    (require configPath('routes/todo.php'))($app, $factory, $container);
};
