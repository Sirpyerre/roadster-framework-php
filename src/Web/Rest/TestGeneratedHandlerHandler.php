<?php

declare(strict_types=1);

namespace Roadster\Web\Rest;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TestGeneratedHandlerHandler implements RequestHandlerInterface
{
    public function __construct(
        // Inject dependencies here
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // TODO: Implement handler logic

        return jsonResponse([
            'message' => 'TestGeneratedHandler handler',
        ]);
    }
}
