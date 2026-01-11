<?php

declare(strict_types=1);

use Roadster\Domain\Todo\Todo;
use Roadster\Infrastructure\Todo\JsonTodoRepository;

describe('JsonTodoRepository', function () {
    beforeEach(function () {
        $this->testFile = sys_get_temp_dir() . '/roadster_test_todos_' . uniqid() . '.json';
        $this->repository = new JsonTodoRepository($this->testFile);
    });

    afterEach(function () {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    });

    it('returns empty array when no todos exist', function () {
        $todos = $this->repository->findAll();

        expect($todos)->toBeArray()->toBeEmpty();
    });

    it('saves and retrieves a todo', function () {
        $todo = new Todo(['title' => 'Test Todo']);

        $saved = $this->repository->save($todo);
        $retrieved = $this->repository->findById($saved->getId());

        expect($retrieved)->toBeInstanceOf(Todo::class);
        expect($retrieved->getTitle())->toBe('Test Todo');
    });

    it('updates existing todo', function () {
        $todo = new Todo(['title' => 'Original']);
        $this->repository->save($todo);

        $todo->update(['title' => 'Updated']);
        $this->repository->save($todo);

        $todos = $this->repository->findAll();
        expect($todos)->toHaveCount(1);
        expect($todos[0]->getTitle())->toBe('Updated');
    });

    it('deletes a todo', function () {
        $todo = new Todo(['title' => 'To Delete']);
        $this->repository->save($todo);

        $result = $this->repository->delete($todo->getId());

        expect($result)->toBeTrue();
        expect($this->repository->findAll())->toBeEmpty();
    });

    it('returns false when deleting non-existent todo', function () {
        $result = $this->repository->delete('non-existent-id');

        expect($result)->toBeFalse();
    });

    it('returns null when finding non-existent todo', function () {
        $result = $this->repository->findById('non-existent-id');

        expect($result)->toBeNull();
    });

    it('persists data to file', function () {
        $todo = new Todo(['title' => 'Persistent Todo']);
        $this->repository->save($todo);

        // Create new repository instance with same file
        $newRepository = new JsonTodoRepository($this->testFile);
        $todos = $newRepository->findAll();

        expect($todos)->toHaveCount(1);
        expect($todos[0]->getTitle())->toBe('Persistent Todo');
    });
});
