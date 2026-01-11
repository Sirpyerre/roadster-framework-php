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
