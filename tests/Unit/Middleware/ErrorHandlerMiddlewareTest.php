<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Roadster\App\Middleware\ErrorHandlerMiddleware;

describe('ErrorHandlerMiddleware', function () {
    it('passes through when no exception is thrown', function () {
        $middleware = new ErrorHandlerMiddleware(false);
        $request = createRequest();

        $expectedResponse = jsonResponse(['success' => true]);

        $handler = new class ($expectedResponse) implements RequestHandlerInterface {
            public function __construct(private ResponseInterface $response) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };

        $response = $middleware->process($request, $handler);

        expect($response)->toBe($expectedResponse);
    });

    it('catches exceptions and returns JSON error response', function () {
        $middleware = new ErrorHandlerMiddleware(false);
        $request = createRequest();

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \RuntimeException('Test error');
            }
        };

        $response = $middleware->process($request, $handler);

        expect($response->getStatusCode())->toBe(500);

        $body = json_decode((string) $response->getBody(), true);
        expect($body)
            ->toHaveKey('error', true)
            ->toHaveKey('message', 'Internal Server Error');
    });

    it('shows detailed error message in debug mode', function () {
        $middleware = new ErrorHandlerMiddleware(true);
        $request = createRequest();

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \RuntimeException('Detailed error message');
            }
        };

        $response = $middleware->process($request, $handler);

        $body = json_decode((string) $response->getBody(), true);
        expect($body['message'])->toBe('Detailed error message');
        expect($body)->toHaveKey('exception');
    });

    it('uses exception code as status code when valid HTTP code', function () {
        $middleware = new ErrorHandlerMiddleware(false);
        $request = createRequest();

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \RuntimeException('Not found', 404);
            }
        };

        $response = $middleware->process($request, $handler);

        expect($response->getStatusCode())->toBe(404);
    });

    it('defaults to 500 for invalid HTTP codes', function () {
        $middleware = new ErrorHandlerMiddleware(false);
        $request = createRequest();

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \RuntimeException('Error', 0);
            }
        };

        $response = $middleware->process($request, $handler);

        expect($response->getStatusCode())->toBe(500);
    });
});
