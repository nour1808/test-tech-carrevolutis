<?php

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/db.php';

if (!isset($pdo)) {
    throw new RuntimeException('Database connection ($pdo) not initialized in config/db.php');
}

$app = AppFactory::create();
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->getDefaultErrorHandler()->forceContentType('application/json');

$logger = new Logger('api');
$handler = new StreamHandler('php://stdout', Logger::INFO);
$handler->setFormatter(new JsonFormatter());
$logger->pushHandler($handler);

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

function jsonResponse(Response $response, int $status, array $payload): Response
{
    $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_SLASHES));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status);
}

function recordMetric(PDO $pdo, string $name): void
{
    $stmt = $pdo->prepare('INSERT INTO metrics (name, cnt) VALUES (:name, 1) ON DUPLICATE KEY UPDATE cnt = cnt + 1');
    $stmt->execute(['name' => $name]);
}

$app->post('/apply', function (Request $request, Response $response) use ($pdo, $logger) {
    $body = (string) $request->getBody();
    $data = json_decode($body, true);

    if (!is_array($data)) {
        recordMetric($pdo, 'failed');
        $logger->warning('apply_invalid_json', ['path' => '/apply', 'body' => $body]);
        return jsonResponse($response, 400, ['error' => 'Invalid JSON payload']);
    }

    $errors = [];

    if (!isset($data['offer_id']) || filter_var($data['offer_id'], FILTER_VALIDATE_INT) === false) {
        $errors['offer_id'] = 'offer_id is required and must be an integer';
    }

    if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'A valid email is required';
    }

    if (!isset($data['cv_url']) || empty($data['cv_url'])) {
        $errors['cv_url'] = 'cv_url is required';
    } elseif (!filter_var($data['cv_url'], FILTER_VALIDATE_URL)) {
        $errors['cv_url'] = 'cv_url must be a valid URL';
    }

    if (!empty($errors)) {
        recordMetric($pdo, 'failed');
        $logger->warning('apply_validation_failed', ['path' => '/apply', 'errors' => $errors]);
        return jsonResponse($response, 422, ['errors' => $errors]);
    }

    $created = false;

    try {
        $insert = $pdo->prepare('INSERT IGNORE INTO applications (offer_id, email, cv_url) VALUES (:offer_id, :email, :cv_url)');
        $insert->execute([
            'offer_id' => (int) $data['offer_id'],
            'email' => strtolower($data['email']),
            'cv_url' => $data['cv_url'],
        ]);

        if ($insert->rowCount() > 0) {
            $created = true;
            $applicationId = (int) $pdo->lastInsertId();
        } else {
            $selectId = $pdo->prepare('SELECT id FROM applications WHERE offer_id = :offer_id AND email = :email LIMIT 1');
            $selectId->execute([
                'offer_id' => (int) $data['offer_id'],
                'email' => strtolower($data['email']),
            ]);
            $existing = $selectId->fetch();
            $applicationId = $existing ? (int) $existing['id'] : null;
        }

        $detailsStmt = $pdo->prepare('SELECT id, offer_id, email, cv_url, created_at FROM applications WHERE id = :id');
        $detailsStmt->execute(['id' => $applicationId]);
        $application = $detailsStmt->fetch();

        recordMetric($pdo, 'success');
        $logger->info('apply_success', [
            'path' => '/apply',
            'status' => $created ? 201 : 200,
            'id' => $applicationId,
            'offer_id' => $data['offer_id'],
            'email' => strtolower($data['email']),
        ]);

        return jsonResponse($response, $created ? 201 : 200, [
            'application' => $application,
            'message' => $created ? 'Application created' : 'Application already exists',
        ]);
    } catch (Throwable $e) {
        recordMetric($pdo, 'failed');
        $logger->error('apply_error', [
            'path' => '/apply',
            'error' => $e->getMessage(),
        ]);

        return jsonResponse($response, 500, ['error' => 'Internal server error']);
    }
});

$app->get('/stats', function (Request $request, Response $response) use ($pdo) {
    $applications = $pdo->query('SELECT COUNT(*) AS total FROM applications')->fetch();
    $metricStmt = $pdo->query('SELECT name, cnt FROM metrics');
    $metrics = ['success' => 0, 'failed' => 0];

    foreach ($metricStmt as $row) {
        $metrics[$row['name']] = (int) $row['cnt'];
    }

    return jsonResponse($response, 200, [
        'applies' => (int) $applications['total'],
        'success_calls' => $metrics['success'],
        'failed_calls' => $metrics['failed'],
    ]);
});

$app->get('/health', function (Request $request, Response $response) {
    return jsonResponse($response, 200, ['status' => 'ok']);
});

$app->run();
