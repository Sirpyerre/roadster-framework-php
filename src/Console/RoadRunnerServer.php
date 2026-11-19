<?php

declare(strict_types=1);

namespace Roadster\Console;

/**
 * Locates the RoadRunner binary, config and pid file of a project and builds
 * the `rr` command lines used to control its server.
 */
final class RoadRunnerServer
{
    private readonly string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? dirname(__DIR__, 2);
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function configFile(): string
    {
        return $this->basePath . '/.rr.yaml';
    }

    public function pidFile(): string
    {
        return $this->basePath . '/.pid';
    }

    public function findBinary(): ?string
    {
        $possiblePaths = [
            $this->basePath . '/rr',
            $this->basePath . '/vendor/bin/rr',
            '/usr/local/bin/rr',
            '/usr/bin/rr',
        ];

        foreach ($possiblePaths as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        $which = shell_exec('which rr 2>/dev/null');
        if ($which !== null && trim($which) !== '') {
            return trim($which);
        }

        return null;
    }

    /**
     * Pid of the running server, or null when none is running. A pid file
     * whose process is gone is removed.
     */
    public function pid(): ?int
    {
        if (!is_file($this->pidFile())) {
            return null;
        }

        $content = file_get_contents($this->pidFile());

        if ($content === false || !ctype_digit(trim($content)) || (int) trim($content) < 1) {
            return null;
        }

        $pid = (int) trim($content);

        if (function_exists('posix_kill') && !posix_kill($pid, 0) && posix_get_last_error() === 3) {
            unlink($this->pidFile());

            return null;
        }

        return $pid;
    }

    public function isRunning(): bool
    {
        return $this->pid() !== null;
    }

    /**
     * @param string $action rr subcommand: serve, stop or reset
     * @param list<string> $flags extra flags placed before the subcommand
     */
    public function command(string $binary, string $action, array $flags = []): string
    {
        $parts = [escapeshellarg($binary), '-w', escapeshellarg($this->basePath), '-c', escapeshellarg($this->configFile())];

        foreach ($flags as $flag) {
            $parts[] = $flag;
        }

        $parts[] = $action;

        return implode(' ', $parts);
    }
}
