<?php

declare(strict_types=1);

use App\Controller\ApplicationController;
use App\Repository\ApplicationRepository;
use App\Service\LoggerFactory;
use App\Validator\ApplicationValidator;
use Doctrine\DBAL\Connection;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\App;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/db.php';

if (!isset($db) || !$db instanceof Connection) {
    throw new RuntimeException('Database connection ($db) not initialized in config/db.php');
}

function createApp(Connection $db): App
{
    $app = AppFactory::create();
    $app->addRoutingMiddleware();
    $errorMiddleware = $app->addErrorMiddleware(true, true, true);
    $errorMiddleware->getDefaultErrorHandler()->forceContentType('application/json');

    $loggerFactory = new LoggerFactory(stream: 'php://stdout', level: Logger::INFO);
    $logger = $loggerFactory->create('api');
    $repository = new ApplicationRepository($db);
    $validator = new ApplicationValidator();
    $controller = new ApplicationController($repository, $validator, $logger);

    // Basic CORS to allow frontend to call the API.
    $app->add(function (Request $request, RequestHandler $handler): Response {
        $response = $handler->handle($request);
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    });

    $app->options('/{routes:.+}', function (Request $request, Response $response): Response {
        return $response;
    });

    $app->post('/apply', [$controller, 'apply']);
    $app->get('/stats', [$controller, 'stats']);
    $app->get('/health', [$controller, 'health']);

    return $app;
}

return createApp($db);
