<?php

declare(strict_types=1);

namespace Roadster\Web\Rest\Todo;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Roadster\Domain\Todo\TodoService;

final class DeleteTodoHandler implements RequestHandlerInterface
{
    public function __construct(
        private TodoService $service,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        $deleted = $this->service->delete($id);

        if (!$deleted) {
            return jsonResponse(['error' => 'Todo not found'], 404);
        }

        return jsonResponse([
            'message' => 'Todo deleted successfully',
        ]);
    }
}
