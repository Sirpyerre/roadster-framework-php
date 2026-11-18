<?php

declare(strict_types=1);

use Roadster\Domain\Todo\Todo;
use Roadster\Web\Rest\Todo\IndexTodoHandler;
use Roadster\Web\Rest\Todo\CreateTodoHandler;
use Roadster\Web\Rest\Todo\ShowTodoHandler;
use Roadster\Web\Rest\Todo\UpdateTodoHandler;
use Roadster\Web\Rest\Todo\DeleteTodoHandler;
use Roadster\Domain\Todo\TodoService;
use Roadster\Domain\Todo\TodoRepositoryInterface;
use Roadster\App\Middleware\ErrorHandlerMiddleware;
use Roadster\Infrastructure\Todo\JsonTodoRepository;

function createTodoService(TodoRepositoryInterface $repository): TodoService
{
    return new TodoService($repository);
}

describe('Todo API Handlers', function () {
    describe('IndexTodoHandler', function () {
        it('returns list of todos', function () {
            $todos = [
                new Todo(['id' => '1', 'title' => 'Todo 1']),
                new Todo(['id' => '2', 'title' => 'Todo 2']),
            ];

            $repository = Mockery::mock(TodoRepositoryInterface::class);
            $repository->shouldReceive('findAll')->andReturn($todos);
            $service = createTodoService($repository);

            $handler = new IndexTodoHandler($service);
            $response = $handler->handle(createRequest('GET', '/api/todos'));

            expect($response->getStatusCode())->toBe(200);

            $body = json_decode((string) $response->getBody(), true);
            expect($body)->toHaveKey('data');
            expect($body['data'])->toHaveCount(2);
            expect($body['meta']['total'])->toBe(2);
        });

        it('returns empty array when no todos', function () {
            $repository = Mockery::mock(TodoRepositoryInterface::class);
            $repository->shouldReceive('findAll')->andReturn([]);
            $service = createTodoService($repository);

            $handler = new IndexTodoHandler($service);
            $response = $handler->handle(createRequest('GET', '/api/todos'));

            $body = json_decode((string) $response->getBody(), true);
            expect($body['data'])->toBeEmpty();
            expect($body['meta']['total'])->toBe(0);
        });
    });

    describe('CreateTodoHandler', function () {
        it('creates a todo', function () {
            $repository = Mockery::mock(TodoRepositoryInterface::class);
            $repository->shouldReceive('save')->andReturnUsing(fn($todo) => $todo);
            $service = createTodoService($repository);

            $handler = new CreateTodoHandler($service);

            $request = createRequest('POST', '/api/todos')
                ->withParsedBody(['title' => 'New Todo']);

            $response = $handler->handle($request);

            expect($response->getStatusCode())->toBe(201);

            $body = json_decode((string) $response->getBody(), true);
            expect($body['data']['title'])->toBe('New Todo');
            expect($body['message'])->toContain('created successfully');
        });

        it('returns error for invalid body', function () {
            $repository = Mockery::mock(TodoRepositoryInterface::class);
            $service = createTodoService($repository);

            $handler = new CreateTodoHandler($service);

            $request = createRequest('POST', '/api/todos');
            // No parsed body

            $response = $handler->handle($request);

            expect($response->getStatusCode())->toBe(400);
        });
    });

    describe('ShowTodoHandler', function () {
        it('returns a todo', function () {
            $todo = new Todo(['id' => 'test-id', 'title' => 'Test Todo']);

            $repository = Mockery::mock(TodoRepositoryInterface::class);
            $repository->shouldReceive('findById')->with('test-id')->andReturn($todo);
            $service = createTodoService($repository);

            $handler = new ShowTodoHandler($service);

            $request = createRequest('GET', '/api/todos/test-id')
                ->withAttribute('id', 'test-id');

            $response = $handler->handle($request);

            expect($response->getStatusCode())->toBe(200);

            $body = json_decode((string) $response->getBody(), true);
            expect($body['data']['id'])->toBe('test-id');
            expect($body['data']['title'])->toBe('Test Todo');
        });

        it('returns 404 for non-existent todo', function () {
            $repository = Mockery::mock(TodoRepositoryInterface::class);
            $repository->shouldReceive('findById')->with('non-existent')->andReturnNull();
            $service = createTodoService($repository);

            $handler = new ShowTodoHandler($service);

            $request = createRequest('GET', '/api/todos/non-existent')
                ->withAttribute('id', 'non-existent');

            $response = $handler->handle($request);

            expect($response->getStatusCode())->toBe(404);
        });
    });

    describe('UpdateTodoHandler', function () {
        it('updates a todo', function () {
            $todo = new Todo(['id' => 'test-id', 'title' => 'Original']);

            $repository = Mockery::mock(TodoRepositoryInterface::class);
            $repository->shouldReceive('findById')->with('test-id')->andReturn($todo);
            $repository->shouldReceive('save')->andReturnUsing(fn($t) => $t);
            $service = createTodoService($repository);

            $handler = new UpdateTodoHandler($service);

            $request = createRequest('PUT', '/api/todos/test-id')
                ->withAttribute('id', 'test-id')
                ->withParsedBody(['title' => 'Updated', 'completed' => true]);

            $response = $handler->handle($request);

            expect($response->getStatusCode())->toBe(200);

            $body = json_decode((string) $response->getBody(), true);
            expect($body['data']['title'])->toBe('Updated');
            expect($body['message'])->toContain('updated successfully');
        });

        it('returns 404 for non-existent todo', function () {
            $repository = Mockery::mock(TodoRepositoryInterface::class);
            $repository->shouldReceive('findById')->with('non-existent')->andReturnNull();
            $service = createTodoService($repository);

            $handler = new UpdateTodoHandler($service);

            $request = createRequest('PUT', '/api/todos/non-existent')
                ->withAttribute('id', 'non-existent')
                ->withParsedBody(['title' => 'Updated']);

            $response = $handler->handle($request);

            expect($response->getStatusCode())->toBe(404);
        });
    });

    describe('DeleteTodoHandler', function () {
        it('deletes a todo', function () {
            $repository = Mockery::mock(TodoRepositoryInterface::class);
            $repository->shouldReceive('delete')->with('test-id')->andReturnTrue();
            $service = createTodoService($repository);

            $handler = new DeleteTodoHandler($service);

            $request = createRequest('DELETE', '/api/todos/test-id')
                ->withAttribute('id', 'test-id');

            $response = $handler->handle($request);

            expect($response->getStatusCode())->toBe(200);

            $body = json_decode((string) $response->getBody(), true);
            expect($body['message'])->toContain('deleted successfully');
        });

        it('returns 404 for non-existent todo', function () {
            $repository = Mockery::mock(TodoRepositoryInterface::class);
            $repository->shouldReceive('delete')->with('non-existent')->andReturnFalse();
            $service = createTodoService($repository);

            $handler = new DeleteTodoHandler($service);

            $request = createRequest('DELETE', '/api/todos/non-existent')
                ->withAttribute('id', 'non-existent');

            $response = $handler->handle($request);

            expect($response->getStatusCode())->toBe(404);
        });
    });
});

describe('Todo API with a corrupt storage file', function () {
    it('returns a generic 500 JSON error', function () {
        $path = sys_get_temp_dir() . '/roadster_corrupt_' . uniqid() . '.json';
        file_put_contents($path, '{not json');

        try {
            $handler = new IndexTodoHandler(createTodoService(new JsonTodoRepository($path)));
            $response = (new ErrorHandlerMiddleware(false))->process(createRequest('GET', '/api/todos'), $handler);

            expect($response->getStatusCode())->toBe(500);
            expect($response)->toBeJsonResponse();

            $body = json_decode((string) $response->getBody(), true);
            expect($body['error'])->toBeTrue();
            expect($body['message'])->toBe('Internal Server Error');
            expect(file_get_contents($path))->toBe('{not json');
        } finally {
            @unlink($path);
            @unlink($path . '.lock');
        }
    });
});
