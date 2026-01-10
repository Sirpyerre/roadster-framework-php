<?php

declare(strict_types=1);

namespace Roadster\App\Middleware;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class JsonResponseMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        // If the response is already a JsonResponse, return it as-is
        if ($response instanceof JsonResponse) {
            return $response;
        }

        // Ensure JSON content type header is set for JSON responses
        $contentType = $response->getHeaderLine('Content-Type');

        if (empty($contentType)) {
            // Check if body looks like JSON
            $body = (string) $response->getBody();

            if ($this->isJsonString($body)) {
                return $response->withHeader('Content-Type', 'application/json');
            }
        }

        return $response;
    }

    private function isJsonString(string $string): bool
    {
        if (empty($string)) {
            return false;
        }

        $firstChar = $string[0];

        if ($firstChar !== '{' && $firstChar !== '[') {
            return false;
        }

        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
