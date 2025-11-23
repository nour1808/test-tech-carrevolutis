<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ApplicationRepository;
use App\Validator\ApplicationValidator;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ApplicationController
{
    public function __construct(
        private ApplicationRepository $repository,
        private ApplicationValidator $validator,
        private Logger $logger
    ) {
    }

    public function apply(Request $request, Response $response): Response
    {
        $body = (string) $request->getBody();
        $data = json_decode($body, true);

        if (!is_array($data)) {
            $this->repository->recordMetric('failed');
            $this->logger->warning('apply_invalid_json', ['path' => '/apply', 'body' => $body]);
            return self::jsonResponse($response, 400, ['error' => 'Invalid JSON payload']);
        }

        $errors = $this->validator->validateApplyPayload($data);

        if (!empty($errors)) {
            $this->repository->recordMetric('failed');
            $this->logger->warning('apply_validation_failed', ['path' => '/apply', 'errors' => $errors]);
            return self::jsonResponse($response, 422, ['errors' => $errors]);
        }

        try {
            $offerId = (int) $data['offer_id'];
            $email = strtolower($data['email']);
            $cvUrl = $data['cv_url'];

            $result = $this->repository->createOrGetApplication($offerId, $email, $cvUrl);
            $application = $this->repository->findApplicationById($result['id']);

            if ($application === null) {
                throw new \RuntimeException('Failed to load application after persistence');
            }

            $this->repository->recordMetric('success');
            $this->logger->info('apply_success', [
                'path' => '/apply',
                'status' => $result['created'] ? 201 : 200,
                'id' => $result['id'],
                'offer_id' => $offerId,
                'email' => $email,
            ]);

            return self::jsonResponse($response, $result['created'] ? 201 : 200, [
                'application' => $application,
                'message' => $result['created'] ? 'Application created' : 'Application already exists',
            ]);
        } catch (\Throwable $e) {
            $this->repository->recordMetric('failed');
            $this->logger->error('apply_error', [
                'path' => '/apply',
                'error' => $e->getMessage(),
            ]);

            return self::jsonResponse($response, 500, ['error' => 'Internal server error']);
        }
    }

    public function stats(Request $request, Response $response): Response
    {
        $stats = $this->repository->fetchStats();

        return self::jsonResponse($response, 200, [
            'applies' => $stats['applications_total'],
            'success_calls' => $stats['metrics']['success'],
            'failed_calls' => $stats['metrics']['failed'],
        ]);
    }

    public function list(Request $request, Response $response): Response
    {
        $applications = $this->repository->fetchAllApplications();

        return self::jsonResponse($response, 200, [
            'applications' => $applications,
        ]);
    }

    public function health(Request $request, Response $response): Response
    {
        return self::jsonResponse($response, 200, ['status' => 'ok']);
    }

    private static function jsonResponse(Response $response, int $status, array $payload): Response
    {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_SLASHES));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
