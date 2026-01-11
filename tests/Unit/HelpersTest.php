<?php

declare(strict_types=1);

describe('env() helper', function () {
    beforeEach(function () {
        // Clear any existing env values
        putenv('TEST_ENV_VAR');
        unset($_ENV['TEST_ENV_VAR'], $_SERVER['TEST_ENV_VAR']);
    });

    it('returns default value when env var is not set', function () {
        $result = env('NON_EXISTENT_VAR', 'default');

        expect($result)->toBe('default');
    });

    it('returns null when env var is not set and no default', function () {
        $result = env('NON_EXISTENT_VAR');

        expect($result)->toBeNull();
    });

    it('reads from putenv', function () {
        putenv('TEST_ENV_VAR=putenv_value');

        $result = env('TEST_ENV_VAR');

        expect($result)->toBe('putenv_value');

        putenv('TEST_ENV_VAR');
    });

    it('reads from $_ENV', function () {
        $_ENV['TEST_ENV_VAR'] = 'env_value';

        $result = env('TEST_ENV_VAR');

        expect($result)->toBe('env_value');

        unset($_ENV['TEST_ENV_VAR']);
    });

    it('converts "true" string to boolean true', function () {
        putenv('TEST_ENV_VAR=true');

        $result = env('TEST_ENV_VAR');

        expect($result)->toBeTrue();

        putenv('TEST_ENV_VAR');
    });

    it('converts "false" string to boolean false', function () {
        putenv('TEST_ENV_VAR=false');

        $result = env('TEST_ENV_VAR');

        expect($result)->toBeFalse();

        putenv('TEST_ENV_VAR');
    });

    it('converts "null" string to null', function () {
        putenv('TEST_ENV_VAR=null');

        $result = env('TEST_ENV_VAR');

        expect($result)->toBeNull();

        putenv('TEST_ENV_VAR');
    });

    it('converts "empty" string to empty string', function () {
        putenv('TEST_ENV_VAR=empty');

        $result = env('TEST_ENV_VAR');

        expect($result)->toBe('');

        putenv('TEST_ENV_VAR');
    });
});

describe('jsonResponse() helper', function () {
    it('creates a JSON response with default status 200', function () {
        $response = jsonResponse(['message' => 'Hello']);

        expect($response->getStatusCode())->toBe(200);
    });

    it('creates a JSON response with custom status code', function () {
        $response = jsonResponse(['error' => 'Not Found'], 404);

        expect($response->getStatusCode())->toBe(404);
    });

    it('sets Content-Type header to application/json', function () {
        $response = jsonResponse(['data' => 'test']);

        expect($response->getHeaderLine('Content-Type'))->toContain('application/json');
    });

    it('encodes data as JSON in body', function () {
        $data = ['name' => 'John', 'age' => 30];
        $response = jsonResponse($data);

        $body = json_decode((string) $response->getBody(), true);

        expect($body)->toBe($data);
    });

    it('accepts custom headers', function () {
        $response = jsonResponse(['data' => 'test'], 200, ['X-Custom-Header' => 'CustomValue']);

        expect($response->getHeaderLine('X-Custom-Header'))->toBe('CustomValue');
    });
});

describe('basePath() helper', function () {
    it('returns the base path of the application', function () {
        $basePath = basePath();

        expect($basePath)->toBeString();
        expect(file_exists($basePath . '/composer.json'))->toBeTrue();
    });

    it('appends path to base path', function () {
        $path = basePath('config/routes.php');

        expect($path)->toEndWith('config/routes.php');
        expect(file_exists($path))->toBeTrue();
    });
});

describe('configPath() helper', function () {
    it('returns the config directory path', function () {
        $configPath = configPath();

        expect($configPath)->toEndWith('config');
        expect(is_dir($configPath))->toBeTrue();
    });

    it('appends file to config path', function () {
        $path = configPath('routes.php');

        expect($path)->toEndWith('config/routes.php');
        expect(file_exists($path))->toBeTrue();
    });
});
