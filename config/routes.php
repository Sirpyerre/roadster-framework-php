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

    // Add your routes here
    // $app->get('/api/users', UserIndexHandler::class, 'users.index');
    // $app->post('/api/users', UserCreateHandler::class, 'users.create');
    // $app->get('/api/users/{id}', UserShowHandler::class, 'users.show');
    // $app->put('/api/users/{id}', UserUpdateHandler::class, 'users.update');
    // $app->delete('/api/users/{id}', UserDeleteHandler::class, 'users.delete');
};
