<?php

declare(strict_types=1);

use Roadster\Domain\Todo\TodoRepositoryInterface;
use Roadster\Infrastructure\Todo\JsonTodoRepository;

return [
    'aliases' => [
        TodoRepositoryInterface::class => JsonTodoRepository::class,
    ],
];
