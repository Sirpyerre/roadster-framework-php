<?php

declare(strict_types=1);

namespace Roadster\Web\Rest\Todo;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Roadster\Domain\Todo\TodoService;

final class UpdateTodoHandler implements RequestHandlerInterface
{
    public function __construct(
        private TodoService $service,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $data = $request->getParsedBody();

        if (!is_array($data)) {
            return jsonResponse(['error' => 'Invalid request body'], 400);
        }

        $item = $this->service->update($id, $data);

        if ($item === null) {
            return jsonResponse(['error' => 'Todo not found'], 404);
        }

        return jsonResponse([
            'data' => $item,
            'message' => 'Todo updated successfully',
        ]);
    }
}
