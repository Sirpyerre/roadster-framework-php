<?php

declare(strict_types=1);

namespace Roadster\Infrastructure\Todo;

use Roadster\Domain\Todo\Todo;
use Roadster\Domain\Todo\TodoRepositoryInterface;
use Roadster\Infrastructure\Storage\JsonFileStore;

final class JsonTodoRepository implements TodoRepositoryInterface
{
    private JsonFileStore $store;

    public function __construct(?string $filePath = null)
    {
        $this->store = new JsonFileStore($filePath ?? basePath('storage/todos.json'));
    }

    /**
     * @return array<Todo>
     */
    public function findAll(): array
    {
        return array_map(
            fn(array $item) => new Todo($item),
            $this->store->read()
        );
    }

    public function findById(string $id): ?Todo
    {
        foreach ($this->store->read() as $item) {
            if ($item['id'] === $id) {
                return new Todo($item);
            }
        }

        return null;
    }

    public function save(Todo $todo): Todo
    {
        $this->store->transaction(function (array $data) use ($todo): array {
            foreach ($data as $index => $item) {
                if ($item['id'] === $todo->getId()) {
                    $data[$index] = $todo->toArray();

                    return $data;
                }
            }

            $data[] = $todo->toArray();

            return $data;
        });

        return $todo;
    }

    public function delete(string $id): bool
    {
        $deleted = false;

        $this->store->transaction(function (array $data) use ($id, &$deleted): ?array {
            $remaining = array_values(array_filter(
                $data,
                fn(array $item) => $item['id'] !== $id
            ));

            $deleted = count($remaining) !== count($data);

            return $deleted ? $remaining : null;
        });

        return $deleted;
    }
}
