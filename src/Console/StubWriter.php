<?php

declare(strict_types=1);

namespace Roadster\Console;

use RuntimeException;

/**
 * Renders `{{placeholder}}` stubs and writes them under a base path without
 * overwriting existing files.
 */
final class StubWriter
{
    public function __construct(
        private readonly string $basePath,
        private readonly string $templatesPath,
    ) {
    }

    /**
     * @param array<string, string> $variables placeholder name => value
     */
    public function render(string $stub, array $variables): string
    {
        $templateFile = $this->templatesPath . '/' . $stub;

        if (!is_file($templateFile)) {
            throw new RuntimeException("Template not found: {$stub}");
        }

        $template = file_get_contents($templateFile);

        if ($template === false) {
            throw new RuntimeException("Failed to read template: {$stub}");
        }

        $search = array_map(fn (string $name): string => '{{' . $name . '}}', array_keys($variables));

        return str_replace($search, array_values($variables), $template);
    }

    public function exists(string $target): bool
    {
        return file_exists($this->basePath . '/' . $target);
    }

    /**
     * Writes the rendered stub to $target (relative to the base path).
     *
     * @param array<string, string> $variables
     * @return bool true when created, false when the target already existed
     */
    public function write(string $stub, string $target, array $variables): bool
    {
        if ($this->exists($target)) {
            return false;
        }

        $content = $this->render($stub, $variables);
        $targetFile = $this->basePath . '/' . $target;
        $dir = dirname($targetFile);

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Unable to create directory: {$dir}");
        }

        if (file_put_contents($targetFile, $content) === false) {
            throw new RuntimeException("Unable to write file: {$target}");
        }

        return true;
    }
}
