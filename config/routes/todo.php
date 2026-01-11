<?php

declare(strict_types=1);

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;
use Roadster\Web\Rest\Todo\IndexTodoHandler;
use Roadster\Web\Rest\Todo\CreateTodoHandler;
use Roadster\Web\Rest\Todo\ShowTodoHandler;
use Roadster\Web\Rest\Todo\UpdateTodoHandler;
use Roadster\Web\Rest\Todo\DeleteTodoHandler;

return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->get('/api/todos', IndexTodoHandler::class, 'todos.index');
    $app->post('/api/todos', CreateTodoHandler::class, 'todos.create');
    $app->get('/api/todos/{id}', ShowTodoHandler::class, 'todos.show');
    $app->put('/api/todos/{id}', UpdateTodoHandler::class, 'todos.update');
    $app->delete('/api/todos/{id}', DeleteTodoHandler::class, 'todos.delete');
};
