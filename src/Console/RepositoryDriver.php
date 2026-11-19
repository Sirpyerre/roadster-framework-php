<?php

declare(strict_types=1);

namespace Roadster\Console;

/**
 * Persistence drivers the CRUD generators can target.
 */
enum RepositoryDriver: string
{
    case Dbal = 'dbal';
    case Json = 'json';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $driver): string => $driver->value, self::cases());
    }

    public static function unsupportedMessage(string $value): string
    {
        return sprintf(
            'Unsupported driver "%s". Available: %s (doctrine and cycle are planned).',
            $value,
            implode(', ', self::values())
        );
    }

    /**
     * Stub under templates/crud/ that generates the repository.
     */
    public function repositoryStub(): string
    {
        return match ($this) {
            self::Dbal => 'DbalRepository.php.stub',
            self::Json => 'JsonRepository.php.stub',
        };
    }

    public function repositoryClass(string $resource): string
    {
        return match ($this) {
            self::Dbal => "Dbal{$resource}Repository",
            self::Json => "Json{$resource}Repository",
        };
    }

    /**
     * Repository file, relative to the project root.
     */
    public function repositoryTarget(string $resource): string
    {
        return "src/Infrastructure/{$resource}/{$this->repositoryClass($resource)}.php";
    }

    public function needsMigration(): bool
    {
        return $this === self::Dbal;
    }

    public function needsStorageDirectory(): bool
    {
        return $this === self::Json;
    }
}
