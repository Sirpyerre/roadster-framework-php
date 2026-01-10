<?php

declare(strict_types=1);

namespace Roadster\Console\Commands;

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\RouteCollector;
use Roadster\App\Container\ContainerFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'routes:list',
    description: 'List all registered routes'
)]
final class RoutesListCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('filter', 'f', InputOption::VALUE_OPTIONAL, 'Filter routes by pattern');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Registered Routes');

        try {
            $routes = $this->getRoutes();
        } catch (\Throwable $e) {
            $io->error('Failed to load routes: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $filter = $input->getOption('filter');

        if ($filter !== null) {
            $routes = array_filter($routes, function (array $route) use ($filter) {
                return stripos($route['path'], $filter) !== false;
            });
        }

        if (empty($routes)) {
            $io->warning('No routes found');
            return Command::SUCCESS;
        }

        $io->table(
            ['Method', 'Path', 'Handler', 'Name'],
            $routes
        );

        $io->writeln('');
        $io->writeln(sprintf('<info>Total: %d routes</info>', count($routes)));

        return Command::SUCCESS;
    }

    /**
     * @return array<int, array{method: string, path: string, handler: string, name: string}>
     */
    private function getRoutes(): array
    {
        $container = ContainerFactory::create();
        $app = $container->get(Application::class);
        $factory = $container->get(MiddlewareFactory::class);

        // Load routes
        $routesFile = dirname(__DIR__, 3) . '/config/routes.php';
        if (file_exists($routesFile)) {
            (require $routesFile)($app, $factory, $container);
        }

        // Get the RouteCollector from the Application
        $routeCollector = $container->get(RouteCollector::class);

        $routes = [];

        // Use reflection to access the routes array
        $reflection = new \ReflectionClass($routeCollector);
        $property = $reflection->getProperty('routes');
        $property->setAccessible(true);
        $registeredRoutes = $property->getValue($routeCollector);

        if (empty($registeredRoutes)) {
            return [];
        }

        foreach ($registeredRoutes as $route) {
            $methods = $route->getAllowedMethods();
            $methodStr = $methods === null ? 'ANY' : implode('|', $methods);

            $middleware = $route->getMiddleware();
            $handlerName = $this->getHandlerName($middleware);

            $routes[] = [
                'method' => $methodStr,
                'path' => $route->getPath(),
                'handler' => $handlerName,
                'name' => $route->getName() ?? '-',
            ];
        }

        // Sort by path
        usort($routes, fn(array $a, array $b) => strcmp($a['path'], $b['path']));

        return $routes;
    }

    private function getHandlerName(mixed $middleware): string
    {
        if (is_string($middleware)) {
            $parts = explode('\\', $middleware);
            return end($parts);
        }

        if (is_object($middleware)) {
            $class = get_class($middleware);

            // Handle LazyLoadingMiddleware
            if (str_contains($class, 'LazyLoadingMiddleware')) {
                $reflection = new \ReflectionClass($middleware);
                $property = $reflection->getProperty('middlewareName');
                $property->setAccessible(true);
                $name = $property->getValue($middleware);
                $parts = explode('\\', $name);
                return end($parts);
            }

            $parts = explode('\\', $class);
            return end($parts);
        }

        if (is_array($middleware)) {
            return 'Pipeline[' . count($middleware) . ']';
        }

        return 'Unknown';
    }
}
