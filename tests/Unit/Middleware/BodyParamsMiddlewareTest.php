<?php

declare(strict_types=1);

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Roadster\App\Middleware\BodyParamsMiddleware;

describe('BodyParamsMiddleware', function () {
    it('parses JSON body', function () {
        $middleware = new BodyParamsMiddleware();

        $body = new Stream('php://temp', 'wb+');
        $body->write('{"title":"Test","completed":true}');
        $body->rewind();

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body);

        $capturedRequest = null;
        $handler = new class ($capturedRequest) implements RequestHandlerInterface {
            public function __construct(private ?ServerRequestInterface &$captured) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->captured = $request;
                return new Response();
            }
        };

        $middleware->process($request, $handler);

        $parsedBody = $capturedRequest->getParsedBody();
        expect($parsedBody)->toBeArray();
        expect($parsedBody)->toHaveKey('title', 'Test');
        expect($parsedBody)->toHaveKey('completed', true);
    });

    it('ignores non-JSON content types', function () {
        $middleware = new BodyParamsMiddleware();

        $body = new Stream('php://temp', 'wb+');
        $body->write('not json');
        $body->rewind();

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('Content-Type', 'text/plain')
            ->withBody($body);

        $capturedRequest = null;
        $handler = new class ($capturedRequest) implements RequestHandlerInterface {
            public function __construct(private ?ServerRequestInterface &$captured) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->captured = $request;
                return new Response();
            }
        };

        $middleware->process($request, $handler);

        expect($capturedRequest->getParsedBody())->toBeNull();
    });

    it('handles invalid JSON gracefully', function () {
        $middleware = new BodyParamsMiddleware();

        $body = new Stream('php://temp', 'wb+');
        $body->write('{invalid json}');
        $body->rewind();

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body);

        $capturedRequest = null;
        $handler = new class ($capturedRequest) implements RequestHandlerInterface {
            public function __construct(private ?ServerRequestInterface &$captured) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->captured = $request;
                return new Response();
            }
        };

        $middleware->process($request, $handler);

        expect($capturedRequest->getParsedBody())->toBeNull();
    });

    it('handles empty body', function () {
        $middleware = new BodyParamsMiddleware();

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('Content-Type', 'application/json');

        $capturedRequest = null;
        $handler = new class ($capturedRequest) implements RequestHandlerInterface {
            public function __construct(private ?ServerRequestInterface &$captured) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->captured = $request;
                return new Response();
            }
        };

        $middleware->process($request, $handler);

        expect($capturedRequest->getParsedBody())->toBeNull();
    });
});
