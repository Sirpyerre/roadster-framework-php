<?php

declare(strict_types=1);

namespace Roadster\Domain\Todo;

interface TodoRepositoryInterface
{
    /**
     * @return array<Todo>
     */
    public function findAll(): array;

    public function findById(string $id): ?Todo;

    public function save(Todo $todo): Todo;

    public function delete(string $id): bool;
}
