<?php

declare(strict_types=1);

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;

if (!function_exists('env')) {
    /**
     * Get an environment variable value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}

if (!function_exists('jsonResponse')) {
    /**
     * Create a JSON response.
     *
     * @param mixed $data
     * @param int $status
     * @param array<string, string> $headers
     * @return ResponseInterface
     */
    function jsonResponse(mixed $data, int $status = 200, array $headers = []): ResponseInterface
    {
        return new JsonResponse($data, $status, $headers);
    }
}

if (!function_exists('basePath')) {
    /**
     * Get the base path of the application.
     *
     * @param string $path
     * @return string
     */
    function basePath(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);

        return $path ? $base . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : $base;
    }
}

if (!function_exists('configPath')) {
    /**
     * Get the config path.
     *
     * @param string $path
     * @return string
     */
    function configPath(string $path = ''): string
    {
        return basePath('config' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}
