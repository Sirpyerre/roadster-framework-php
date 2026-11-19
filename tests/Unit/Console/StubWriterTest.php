<?php

declare(strict_types=1);

use Roadster\Console\StubWriter;

describe('StubWriter', function () {
    beforeEach(function () {
        $this->root = sys_get_temp_dir() . '/roadster_stub_' . uniqid();
        $this->templates = $this->root . '/templates';
        $this->out = $this->root . '/out';
        mkdir($this->templates, 0755, true);
        mkdir($this->out, 0755, true);
        file_put_contents($this->templates . '/a.stub', "Hello {{name}} {{name}} {{other}}");
        $this->writer = new StubWriter($this->out, $this->templates);
    });

    afterEach(function () {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->root);
    });

    it('renders every occurrence of each placeholder', function () {
        expect($this->writer->render('a.stub', ['name' => 'X', 'other' => 'Y']))->toBe('Hello X X Y');
    });

    it('creates the file and missing directories', function () {
        $created = $this->writer->write('a.stub', 'deep/dir/file.txt', ['name' => 'X', 'other' => 'Y']);

        expect($created)->toBeTrue();
        expect(file_get_contents($this->out . '/deep/dir/file.txt'))->toBe('Hello X X Y');
    });

    it('does not overwrite an existing file', function () {
        file_put_contents($this->out . '/keep.txt', 'original');

        $created = $this->writer->write('a.stub', 'keep.txt', ['name' => 'X', 'other' => 'Y']);

        expect($created)->toBeFalse();
        expect(file_get_contents($this->out . '/keep.txt'))->toBe('original');
    });

    it('throws when the template is missing', function () {
        $this->writer->render('missing.stub', []);
    })->throws(RuntimeException::class, 'Template not found');
});
