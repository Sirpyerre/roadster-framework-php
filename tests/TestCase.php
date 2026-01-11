<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Roadster\App\Container\ContainerFactory;

abstract class TestCase extends BaseTestCase
{
    protected ?ContainerInterface $container = null;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getContainer(): ContainerInterface
    {
        if ($this->container === null) {
            $this->container = ContainerFactory::create();
        }

        return $this->container;
    }
}
