<?php

declare(strict_types=1);

namespace Roadster\Infrastructure\Todo;

use Roadster\Domain\Todo\Todo;
use Roadster\Domain\Todo\TodoRepositoryInterface;

final class JsonTodoRepository implements TodoRepositoryInterface
{
    private string $filePath;

    public function __construct(?string $filePath = null)
    {
        $this->filePath = $filePath ?? basePath('storage/todos.json');
        $this->ensureStorageExists();
    }

    /**
     * @return array<Todo>
     */
    public function findAll(): array
    {
        $data = $this->readData();

        return array_map(
            fn(array $item) => new Todo($item),
            $data
        );
    }

    public function findById(string $id): ?Todo
    {
        $data = $this->readData();

        foreach ($data as $item) {
            if ($item['id'] === $id) {
                return new Todo($item);
            }
        }

        return null;
    }

    public function save(Todo $todo): Todo
    {
        $data = $this->readData();
        $found = false;

        foreach ($data as $index => $item) {
            if ($item['id'] === $todo->getId()) {
                $data[$index] = $todo->toArray();
                $found = true;
                break;
            }
        }

        if (!$found) {
            $data[] = $todo->toArray();
        }

        $this->writeData($data);

        return $todo;
    }

    public function delete(string $id): bool
    {
        $data = $this->readData();
        $initialCount = count($data);

        $data = array_values(array_filter(
            $data,
            fn(array $item) => $item['id'] !== $id
        ));

        if (count($data) === $initialCount) {
            return false;
        }

        $this->writeData($data);

        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readData(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $content = file_get_contents($this->filePath);

        if ($content === false || $content === '') {
            return [];
        }

        $data = json_decode($content, true);

        return is_array($data) ? $data : [];
    }

    /**
     * @param array<int, array<string, mixed>> $data
     */
    private function writeData(array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($json !== false) {
            file_put_contents($this->filePath, $json);
        }
    }

    private function ensureStorageExists(): void
    {
        $dir = dirname($this->filePath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
