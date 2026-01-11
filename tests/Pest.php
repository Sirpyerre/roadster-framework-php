<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeJsonResponse', function () {
    return $this->toBeInstanceOf(\Psr\Http\Message\ResponseInterface::class)
        ->and($this->value->getHeaderLine('Content-Type'))->toContain('application/json');
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function createRequest(string $method = 'GET', string $uri = '/'): \Psr\Http\Message\ServerRequestInterface
{
    return new \Laminas\Diactoros\ServerRequest(
        serverParams: [],
        uploadedFiles: [],
        uri: $uri,
        method: $method
    );
}
