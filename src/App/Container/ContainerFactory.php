<?php

declare(strict_types=1);

namespace Roadster\App\Container;

use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerInterface;

final class ContainerFactory
{
    public static function create(): ContainerInterface
    {
        $config = require dirname(__DIR__, 3) . '/config/container.php';

        // Create the service manager with dependencies config
        $container = new ServiceManager($config['dependencies'] ?? []);

        // Store the full config in the container
        $container->setService('config', $config);

        return $container;
    }
}
