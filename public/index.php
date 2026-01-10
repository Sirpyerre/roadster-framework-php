<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Mezzio\Application;
use Mezzio\Handler\NotFoundHandler;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware;
use Mezzio\Router\Middleware\MethodNotAllowedMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use Roadster\App\Container\ContainerFactory;
use Roadster\App\Middleware\ErrorHandlerMiddleware;
use Roadster\App\Middleware\JsonResponseMiddleware;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\Worker;

require dirname(__DIR__) . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// Create the container
$container = ContainerFactory::create();

// Create the Mezzio application
$app = $container->get(Application::class);
$factory = $container->get(MiddlewareFactory::class);

// Set up the middleware pipeline
$app->pipe(ErrorHandlerMiddleware::class);
$app->pipe(JsonResponseMiddleware::class);
$app->pipe(RouteMiddleware::class);
$app->pipe(ImplicitHeadMiddleware::class);
$app->pipe(ImplicitOptionsMiddleware::class);
$app->pipe(MethodNotAllowedMiddleware::class);
$app->pipe(DispatchMiddleware::class);
$app->pipe(NotFoundHandler::class);

// Load routes
(require dirname(__DIR__) . '/config/routes.php')($app, $factory, $container);

// Check if running under RoadRunner or standard PHP server
if (isset($_SERVER['RR_MODE'])) {
    // RoadRunner mode
    $worker = Worker::create();
    $psrFactory = new \Laminas\Diactoros\ServerRequestFactory();
    $streamFactory = new \Laminas\Diactoros\StreamFactory();
    $uploadsFactory = new \Laminas\Diactoros\UploadedFileFactory();

    $psr7 = new PSR7Worker($worker, $psrFactory, $streamFactory, $uploadsFactory);

    while ($request = $psr7->waitRequest()) {
        try {
            $response = $app->handle($request);
            $psr7->respond($response);
        } catch (\Throwable $e) {
            $psr7->respond(
                new \Laminas\Diactoros\Response\JsonResponse(
                    [
                        'error' => true,
                        'message' => env('APP_DEBUG', false) ? $e->getMessage() : 'Internal Server Error',
                    ],
                    500
                )
            );
        }
    }
} else {
    // Standard PHP server mode (for development/debugging)
    $request = ServerRequestFactory::fromGlobals();
    $response = $app->handle($request);
    (new SapiEmitter())->emit($response);
}
