<?php

declare(strict_types=1);

use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Roadster\App\Middleware\JsonResponseMiddleware;

describe('JsonResponseMiddleware', function () {
    it('passes through JsonResponse unchanged', function () {
        $middleware = new JsonResponseMiddleware();
        $request = createRequest();

        $expectedResponse = new JsonResponse(['data' => 'test']);

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

    it('adds Content-Type header for JSON body without Content-Type', function () {
        $middleware = new JsonResponseMiddleware();
        $request = createRequest();

        $jsonBody = json_encode(['data' => 'test']);
        $response = new Response();
        $response->getBody()->write($jsonBody);

        $handler = new class ($response) implements RequestHandlerInterface {
            public function __construct(private ResponseInterface $response) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };

        $processedResponse = $middleware->process($request, $handler);

        expect($processedResponse->getHeaderLine('Content-Type'))->toBe('application/json');
    });

    it('does not modify response with existing Content-Type', function () {
        $middleware = new JsonResponseMiddleware();
        $request = createRequest();

        $response = (new Response())
            ->withHeader('Content-Type', 'text/plain');
        $response->getBody()->write('plain text');

        $handler = new class ($response) implements RequestHandlerInterface {
            public function __construct(private ResponseInterface $response) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };

        $processedResponse = $middleware->process($request, $handler);

        expect($processedResponse->getHeaderLine('Content-Type'))->toBe('text/plain');
    });

    it('does not add Content-Type for non-JSON body', function () {
        $middleware = new JsonResponseMiddleware();
        $request = createRequest();

        $response = new Response();
        $response->getBody()->write('plain text content');

        $handler = new class ($response) implements RequestHandlerInterface {
            public function __construct(private ResponseInterface $response) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };

        $processedResponse = $middleware->process($request, $handler);

        expect($processedResponse->getHeaderLine('Content-Type'))->toBe('');
    });

    it('detects JSON array body', function () {
        $middleware = new JsonResponseMiddleware();
        $request = createRequest();

        $response = new Response();
        $response->getBody()->write('[1, 2, 3]');

        $handler = new class ($response) implements RequestHandlerInterface {
            public function __construct(private ResponseInterface $response) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };

        $processedResponse = $middleware->process($request, $handler);

        expect($processedResponse->getHeaderLine('Content-Type'))->toBe('application/json');
    });
});
