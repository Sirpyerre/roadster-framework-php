<?php

declare(strict_types=1);

namespace Roadster\Web\Rest\Todo;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Roadster\Domain\Todo\TodoService;

final class CreateTodoHandler implements RequestHandlerInterface
{
    public function __construct(
        private TodoService $service,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();

        if (!is_array($data)) {
            return jsonResponse(['error' => 'Invalid request body'], 400);
        }

        $item = $this->service->create($data);

        return jsonResponse([
            'data' => $item,
            'message' => 'Todo created successfully',
        ], 201);
    }
}
