<?php

declare(strict_types=1);

// Child process used by JsonFileStoreTest: appends N distinct records to a store.
// Usage: php store_worker.php <file> <prefix> <count>

use Roadster\Infrastructure\Storage\JsonFileStore;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

[, $file, $prefix, $count] = $argv;

$store = new JsonFileStore($file);

for ($i = 0; $i < (int) $count; $i++) {
    $store->transaction(function (array $data) use ($prefix, $i): array {
        $data[] = ['id' => "{$prefix}-{$i}"];

        return $data;
    });
}
