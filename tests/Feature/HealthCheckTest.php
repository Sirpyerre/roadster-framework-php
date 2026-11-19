<?php

declare(strict_types=1);

use Roadster\Web\Rest\HealthCheckHandler;

describe('HealthCheckHandler', function () {
    it('returns healthy status', function () {
        $handler = new HealthCheckHandler();
        $request = createRequest('GET', '/health');

        $response = $handler->handle($request);

        expect($response->getStatusCode())->toBe(200);
    });

    it('returns JSON response', function () {
        $handler = new HealthCheckHandler();
        $request = createRequest('GET', '/health');

        $response = $handler->handle($request);

        expect($response->getHeaderLine('Content-Type'))->toContain('application/json');
    });

    it('contains status and timestamp keys', function () {
        $handler = new HealthCheckHandler();
        $request = createRequest('GET', '/health');

        $response = $handler->handle($request);
        $body = json_decode((string) $response->getBody(), true);

        expect($body)
            ->toHaveKey('status', 'healthy')
            ->toHaveKey('timestamp');
    });

    it('returns valid ISO 8601 timestamp', function () {
        $handler = new HealthCheckHandler();
        $request = createRequest('GET', '/health');

        $response = $handler->handle($request);
        $body = json_decode((string) $response->getBody(), true);

        $timestamp = $body['timestamp'];
        $date = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $timestamp);

        expect($date)->toBeInstanceOf(\DateTimeImmutable::class);
    });
});

describe('HealthCheckHandler database check', function () {
    it('reports healthy with a reachable database', function () {
        $connection = \Doctrine\DBAL\DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

        $response = (new HealthCheckHandler($connection))->handle(createRequest('GET', '/health'));
        $body = json_decode((string) $response->getBody(), true);

        expect($response->getStatusCode())->toBe(200);
        expect($body)
            ->toHaveKey('status', 'healthy')
            ->toHaveKey('timestamp')
            ->toHaveKey('checks', ['database' => 'ok']);
    });

    it('reports unhealthy with 503 when the database fails', function () {
        $connection = \Doctrine\DBAL\DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => '/nonexistent-dir-roadster/secret-name.db',
        ]);

        $response = (new HealthCheckHandler($connection))->handle(createRequest('GET', '/health'));
        $body = json_decode((string) $response->getBody(), true);

        expect($response->getStatusCode())->toBe(503);
        expect($body)
            ->toHaveKey('status', 'unhealthy')
            ->toHaveKey('checks', ['database' => 'error']);
    });

    it('does not leak connection details or exception messages when it fails', function () {
        $connection = \Doctrine\DBAL\DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => '/nonexistent-dir-roadster/secret-name.db',
        ]);

        $response = (new HealthCheckHandler($connection))->handle(createRequest('GET', '/health'));
        $raw = (string) $response->getBody();

        expect($raw)->not->toContain('nonexistent-dir-roadster');
        expect($raw)->not->toContain('secret-name');
        expect($raw)->not->toContain('unable to open');
        expect(array_keys(json_decode($raw, true)))->toBe(['status', 'timestamp', 'checks']);
    });

    it('is autowired with the container connection', function () {
        $handler = \Roadster\App\Container\ContainerFactory::create()->get(HealthCheckHandler::class);
        $body = json_decode((string) $handler->handle(createRequest('GET', '/health'))->getBody(), true);

        expect($body['checks'])->toBe(['database' => 'ok']);
    });
});
