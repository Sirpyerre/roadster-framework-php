<?php

declare(strict_types=1);

use Roadster\Domain\Todo\Todo;
use Roadster\Domain\Todo\TodoRepositoryInterface;
use Roadster\Domain\Todo\TodoService;

describe('TodoService', function () {
    it('finds all todos', function () {
        $repository = Mockery::mock(TodoRepositoryInterface::class);
        $repository->shouldReceive('findAll')->andReturn([
            new Todo(['title' => 'Todo 1']),
            new Todo(['title' => 'Todo 2']),
        ]);

        $service = new TodoService($repository);
        $todos = $service->findAll();

        expect($todos)->toHaveCount(2);
    });

    it('finds todo by id', function () {
        $todo = new Todo(['id' => 'test-id', 'title' => 'Test']);

        $repository = Mockery::mock(TodoRepositoryInterface::class);
        $repository->shouldReceive('findById')->with('test-id')->andReturn($todo);

        $service = new TodoService($repository);
        $result = $service->findById('test-id');

        expect($result)->toBe($todo);
    });

    it('returns null when todo not found', function () {
        $repository = Mockery::mock(TodoRepositoryInterface::class);
        $repository->shouldReceive('findById')->with('non-existent')->andReturnNull();

        $service = new TodoService($repository);
        $result = $service->findById('non-existent');

        expect($result)->toBeNull();
    });

    it('creates a todo', function () {
        $repository = Mockery::mock(TodoRepositoryInterface::class);
        $repository->shouldReceive('save')->andReturnUsing(fn($todo) => $todo);

        $service = new TodoService($repository);
        $todo = $service->create(['title' => 'New Todo']);

        expect($todo)->toBeInstanceOf(Todo::class);
        expect($todo->getTitle())->toBe('New Todo');
    });

    it('updates a todo', function () {
        $existingTodo = new Todo(['id' => 'test-id', 'title' => 'Original']);

        $repository = Mockery::mock(TodoRepositoryInterface::class);
        $repository->shouldReceive('findById')->with('test-id')->andReturn($existingTodo);
        $repository->shouldReceive('save')->andReturnUsing(fn($todo) => $todo);

        $service = new TodoService($repository);
        $updated = $service->update('test-id', ['title' => 'Updated']);

        expect($updated)->toBeInstanceOf(Todo::class);
        expect($updated->getTitle())->toBe('Updated');
    });

    it('returns null when updating non-existent todo', function () {
        $repository = Mockery::mock(TodoRepositoryInterface::class);
        $repository->shouldReceive('findById')->with('non-existent')->andReturnNull();

        $service = new TodoService($repository);
        $result = $service->update('non-existent', ['title' => 'Updated']);

        expect($result)->toBeNull();
    });

    it('deletes a todo', function () {
        $repository = Mockery::mock(TodoRepositoryInterface::class);
        $repository->shouldReceive('delete')->with('test-id')->andReturnTrue();

        $service = new TodoService($repository);
        $result = $service->delete('test-id');

        expect($result)->toBeTrue();
    });
});
