<?php

declare(strict_types=1);

namespace Roadster\Domain\Todo;

final class TodoService
{
    public function __construct(
        private TodoRepositoryInterface $repository,
    ) {
    }

    /**
     * @return array<Todo>
     */
    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    public function findById(string $id): ?Todo
    {
        return $this->repository->findById($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Todo
    {
        $item = new Todo($data);

        return $this->repository->save($item);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): ?Todo
    {
        $item = $this->repository->findById($id);

        if ($item === null) {
            return null;
        }

        $item->update($data);

        return $this->repository->save($item);
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }
}
