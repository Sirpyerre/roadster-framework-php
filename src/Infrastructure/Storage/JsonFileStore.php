<?php

declare(strict_types=1);

namespace Roadster\Infrastructure\Storage;

use JsonException;
use RuntimeException;

/**
 * Stores a list of records in a JSON file.
 *
 * Read-modify-write cycles run under an exclusive lock on a sidecar file and
 * are written atomically (temp file + rename), so concurrent workers never
 * lose updates and readers never see a partial file.
 */
final class JsonFileStore
{
    public function __construct(private readonly string $filePath)
    {
        $dir = dirname($this->filePath);

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Unable to create storage directory: {$dir}");
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function read(): array
    {
        $lock = $this->acquireLock(LOCK_SH);

        try {
            return $this->load();
        } finally {
            $this->releaseLock($lock);
        }
    }

    /**
     * Runs the callback with the current data under an exclusive lock and
     * persists the array it returns. Returning null leaves the file untouched.
     *
     * @param callable(array<int, array<string, mixed>>): (array<int, array<string, mixed>>|null) $callback
     */
    public function transaction(callable $callback): void
    {
        $lock = $this->acquireLock(LOCK_EX);

        try {
            $result = $callback($this->load());

            if ($result !== null) {
                $this->write($result);
            }
        } finally {
            $this->releaseLock($lock);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function load(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $content = file_get_contents($this->filePath);

        if ($content === false) {
            throw new RuntimeException("Unable to read storage file: {$this->filePath}");
        }

        if ($content === '') {
            return [];
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException("Storage file contains invalid JSON: {$this->filePath}", 0, $e);
        }

        if (!is_array($data) || !array_is_list($data) || in_array(false, array_map('is_array', $data), true)) {
            throw new RuntimeException("Storage file does not contain a list of records: {$this->filePath}");
        }

        return $data;
    }

    /**
     * @param array<int, array<string, mixed>> $data
     */
    private function write(array $data): void
    {
        try {
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException("Unable to encode data for: {$this->filePath}", 0, $e);
        }

        $tmp = tempnam(dirname($this->filePath), basename($this->filePath) . '.tmp');

        if ($tmp === false) {
            throw new RuntimeException("Unable to create temp file for: {$this->filePath}");
        }

        if (file_put_contents($tmp, $json) === false || !chmod($tmp, 0644) || !rename($tmp, $this->filePath)) {
            @unlink($tmp);

            throw new RuntimeException("Unable to write storage file: {$this->filePath}");
        }
    }

    /**
     * @return resource
     */
    private function acquireLock(int $operation)
    {
        if ($operation === LOCK_EX && !is_writable(dirname($this->filePath))) {
            throw new RuntimeException("Storage directory is not writable: {$this->filePath}");
        }

        $handle = @fopen($this->filePath . '.lock', 'c');

        if ($handle === false || !flock($handle, $operation)) {
            throw new RuntimeException("Unable to lock storage file: {$this->filePath}");
        }

        return $handle;
    }

    /**
     * @param resource $handle
     */
    private function releaseLock($handle): void
    {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
