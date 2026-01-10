<?php

declare(strict_types=1);

namespace Roadster\App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as Whoops;

final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    private bool $debug;

    public function __construct(?bool $debug = null)
    {
        $this->debug = $debug ?? (env('APP_DEBUG', false) === true);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            return $this->handleException($e, $request);
        }
    }

    private function handleException(Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        if ($this->debug && $this->isHtmlRequest($request)) {
            return $this->handleWithWhoops($e);
        }

        return $this->handleAsJson($e);
    }

    private function isHtmlRequest(ServerRequestInterface $request): bool
    {
        $accept = $request->getHeaderLine('Accept');

        return str_contains($accept, 'text/html');
    }

    private function handleWithWhoops(Throwable $e): ResponseInterface
    {
        $whoops = new Whoops();
        $whoops->allowQuit(false);
        $whoops->writeToOutput(false);
        $whoops->pushHandler(new PrettyPageHandler());

        $html = $whoops->handleException($e);

        return new \Laminas\Diactoros\Response\HtmlResponse($html, 500);
    }

    private function handleAsJson(Throwable $e): ResponseInterface
    {
        $statusCode = $this->getStatusCode($e);

        $payload = [
            'error' => true,
            'message' => $this->debug ? $e->getMessage() : 'Internal Server Error',
        ];

        if ($this->debug) {
            $payload['exception'] = [
                'type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => array_slice($e->getTrace(), 0, 10),
            ];
        }

        return jsonResponse($payload, $statusCode);
    }

    private function getStatusCode(Throwable $e): int
    {
        $code = $e->getCode();

        if ($code >= 400 && $code < 600) {
            return $code;
        }

        return 500;
    }
}
