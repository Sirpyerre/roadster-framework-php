<?php

declare(strict_types=1);

namespace Roadster\Web\Rest;

use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class HealthCheckHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly ?Connection $connection = null,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $payload = [
            'status' => 'healthy',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        if ($this->connection === null) {
            return jsonResponse($payload);
        }

        $databaseOk = $this->databaseResponds($this->connection);

        $payload['status'] = $databaseOk ? 'healthy' : 'unhealthy';
        $payload['checks'] = ['database' => $databaseOk ? 'ok' : 'error'];

        return jsonResponse($payload, $databaseOk ? 200 : 503);
    }

    private function databaseResponds(Connection $connection): bool
    {
        try {
            $connection->fetchOne('SELECT 1');

            return true;
        } catch (Throwable) {
            // Details are deliberately not exposed in the response
            return false;
        }
    }
}
