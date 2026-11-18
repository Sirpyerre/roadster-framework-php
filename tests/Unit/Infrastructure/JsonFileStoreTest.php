<?php

declare(strict_types=1);

use Roadster\Infrastructure\Storage\JsonFileStore;

describe('JsonFileStore', function () {
    beforeEach(function () {
        $this->storeDir = sys_get_temp_dir() . '/roadster_store_' . uniqid();
        mkdir($this->storeDir);
        $this->storePath = $this->storeDir . '/items.json';
        $this->store = new JsonFileStore($this->storePath);
    });

    afterEach(function () {
        if (is_dir($this->storeDir)) {
            chmod($this->storeDir, 0755);
            array_map('unlink', glob($this->storeDir . '/*') ?: []);
            rmdir($this->storeDir);
        }
    });

    it('reads an empty list when the file does not exist', function () {
        expect($this->store->read())->toBe([]);
    });

    it('reads an empty list when the file is empty', function () {
        file_put_contents($this->storePath, '');

        expect($this->store->read())->toBe([]);
    });

    it('reads stored records', function () {
        file_put_contents($this->storePath, '[{"id":"1"},{"id":"2"}]');

        expect($this->store->read())->toBe([['id' => '1'], ['id' => '2']]);
    });

    it('throws on invalid JSON and leaves the file unchanged', function () {
        file_put_contents($this->storePath, '{not json');

        expect(fn() => $this->store->read())->toThrow(RuntimeException::class, 'invalid JSON');
        expect(file_get_contents($this->storePath))->toBe('{not json');
    });

    it('throws when the JSON is not a list of records', function (string $content) {
        file_put_contents($this->storePath, $content);

        expect(fn() => $this->store->read())->toThrow(RuntimeException::class, 'list of records');
    })->with(['object' => '{"a":1}', 'scalar' => '"text"', 'list of scalars' => '[1,2]']);

    it('creates the file on first write', function () {
        $this->store->transaction(fn(array $data) => [...$data, ['id' => '1']]);

        expect(json_decode((string) file_get_contents($this->storePath), true))->toBe([['id' => '1']]);
    });

    it('leaves the file untouched when the callback returns null', function () {
        file_put_contents($this->storePath, '[{"id":"1"}]');

        $this->store->transaction(fn(array $data) => null);

        expect(file_get_contents($this->storePath))->toBe('[{"id":"1"}]');
    });

    it('throws on corrupt data in a transaction and does not overwrite it', function () {
        file_put_contents($this->storePath, '{not json');

        expect(fn() => $this->store->transaction(fn(array $data) => [['id' => '1']]))
            ->toThrow(RuntimeException::class);
        expect(file_get_contents($this->storePath))->toBe('{not json');
    });

    it('keeps the previous contents when the callback throws', function () {
        file_put_contents($this->storePath, '[{"id":"1"}]');

        expect(fn() => $this->store->transaction(function (array $data) {
            throw new LogicException('boom');
        }))->toThrow(LogicException::class);
        expect(file_get_contents($this->storePath))->toBe('[{"id":"1"}]');
    });

    it('throws and keeps the previous contents when the directory is not writable', function () {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('Permissions are not enforced for root.');
        }

        file_put_contents($this->storePath, '[{"id":"1"}]');
        chmod($this->storeDir, 0555);

        expect(fn() => $this->store->transaction(fn(array $data) => [['id' => '2']]))
            ->toThrow(RuntimeException::class);
        expect(file_get_contents($this->storePath))->toBe('[{"id":"1"}]');
    });

    it('leaves no temp files behind', function () {
        $this->store->transaction(fn(array $data) => [...$data, ['id' => '1']]);

        $files = array_map('basename', glob($this->storeDir . '/*') ?: []);
        sort($files);

        expect($files)->toBe(['items.json', 'items.json.lock']);
    });

    it('applies every write from concurrent processes', function () {
        $processes = 5;
        $perProcess = 20;
        $running = [];

        for ($p = 0; $p < $processes; $p++) {
            $running[] = proc_open(
                [PHP_BINARY, __DIR__ . '/../../Support/store_worker.php', $this->storePath, "p{$p}", (string) $perProcess],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );
        }

        foreach ($running as $process) {
            expect(proc_close($process))->toBe(0);
        }

        $ids = array_column($this->store->read(), 'id');

        expect($ids)->toHaveCount($processes * $perProcess);
        expect(array_unique($ids))->toHaveCount($processes * $perProcess);
    });
});
