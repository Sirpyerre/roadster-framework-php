<?php

declare(strict_types=1);

use Roadster\Domain\Todo\Todo;

describe('Todo Entity', function () {
    it('creates with default values', function () {
        $todo = new Todo();

        expect($todo->getId())->toBeString()->not->toBeEmpty();
        expect($todo->getTitle())->toBe('');
        expect($todo->isCompleted())->toBeFalse();
        expect($todo->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
        expect($todo->getUpdatedAt())->toBeNull();
    });

    it('creates with provided data', function () {
        $todo = new Todo([
            'id' => 'test-id',
            'title' => 'Test Todo',
            'completed' => true,
        ]);

        expect($todo->getId())->toBe('test-id');
        expect($todo->getTitle())->toBe('Test Todo');
        expect($todo->isCompleted())->toBeTrue();
    });

    it('updates title', function () {
        $todo = new Todo(['title' => 'Original']);

        $todo->update(['title' => 'Updated']);

        expect($todo->getTitle())->toBe('Updated');
        expect($todo->getUpdatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('updates completed status', function () {
        $todo = new Todo(['completed' => false]);

        $todo->update(['completed' => true]);

        expect($todo->isCompleted())->toBeTrue();
    });

    it('serializes to JSON', function () {
        $todo = new Todo([
            'id' => 'test-id',
            'title' => 'Test Todo',
            'completed' => false,
        ]);

        $json = $todo->jsonSerialize();

        expect($json)->toHaveKey('id', 'test-id');
        expect($json)->toHaveKey('title', 'Test Todo');
        expect($json)->toHaveKey('completed', false);
        expect($json)->toHaveKey('createdAt');
        expect($json)->toHaveKey('updatedAt');
    });

    it('converts to array', function () {
        $todo = new Todo(['title' => 'Test']);

        $array = $todo->toArray();

        expect($array)->toBeArray();
        expect($array)->toHaveKey('title', 'Test');
    });
});
