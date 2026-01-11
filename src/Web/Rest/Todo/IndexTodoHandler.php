<?php

declare(strict_types=1);

namespace Roadster\Web\Rest\Todo;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Roadster\Domain\Todo\TodoService;

final class IndexTodoHandler implements RequestHandlerInterface
{
    public function __construct(
        private TodoService $service,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $items = $this->service->findAll();

        return jsonResponse([
            'data' => $items,
            'meta' => [
                'total' => count($items),
            ],
        ]);
    }
}
