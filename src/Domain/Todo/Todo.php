<?php

declare(strict_types=1);

namespace Roadster\Domain\Todo;

final class Todo implements \JsonSerializable
{
    private string $id;
    private string $title;
    private bool $completed;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? $this->generateId();
        $this->title = $data['title'] ?? '';
        $this->completed = (bool) ($data['completed'] ?? false);
        $this->createdAt = isset($data['createdAt'])
            ? new \DateTimeImmutable($data['createdAt'])
            : new \DateTimeImmutable();

        if (isset($data['updatedAt'])) {
            $this->updatedAt = new \DateTimeImmutable($data['updatedAt']);
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(array $data): void
    {
        if (isset($data['title'])) {
            $this->title = $data['title'];
        }

        if (isset($data['completed'])) {
            $this->completed = (bool) $data['completed'];
        }

        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'completed' => $this->completed,
            'createdAt' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'updatedAt' => $this->updatedAt?->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    private function generateId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
