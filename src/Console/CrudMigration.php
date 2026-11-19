<?php

declare(strict_types=1);

namespace Roadster\Console;

/**
 * Generates the "create table" migration of a CRUD resource, once.
 */
final class CrudMigration
{
    public function __construct(
        private readonly string $basePath,
        private readonly StubWriter $writer,
    ) {
    }

    /**
     * Relative path of an existing create-table migration for the resource, if any.
     */
    public function existing(string $routeName): ?string
    {
        $suffix = self::name($routeName);
        $matches = glob($this->basePath . '/database/migrations/Version*' . $suffix . '.php') ?: [];

        if ($matches === []) {
            return null;
        }

        sort($matches);

        return 'database/migrations/' . basename($matches[0]);
    }

    /**
     * Writes the migration and returns its relative path.
     */
    public function write(string $routeName): string
    {
        $name = self::name($routeName);
        $class = DatabaseMigrations::className($name);
        $target = "database/migrations/{$class}.php";

        $this->writer->write('migration.php.stub', $target, [
            'namespace' => DatabaseMigrations::namespace(),
            'class' => $class,
            'description' => 'Create ' . $routeName . ' table',
            'routeName' => $routeName,
        ]);

        return $target;
    }

    /**
     * "products" => "CreateProductsTable".
     */
    private static function name(string $routeName): string
    {
        return 'Create' . ucfirst($routeName) . 'Table';
    }
}
