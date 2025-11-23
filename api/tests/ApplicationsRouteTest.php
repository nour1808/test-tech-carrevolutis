<?php

use App\Controller\ApplicationController;
use App\Repository\ApplicationRepository;
use App\Validator\ApplicationValidator;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class ApplicationsRouteTest extends TestCase
{
    public function testListApplicationsRouteReturnsData(): void
    {
        $app = $this->createAppWithFakeRepo();

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/applications');
        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $payload = json_decode((string) $response->getBody(), true);
        $this->assertEquals(2, count($payload['applications']));
    }

    public function testApplyRouteWithValidPayload(): void
    {
        $app = $this->createAppWithFakeRepo();
        $body = json_encode([
            'offer_id' => 10,
            'email' => 'user@example.com',
            'cv_url' => 'https://example.com/cv.pdf',
        ]);

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/apply')
            ->withHeader('Content-Type', 'application/json');
        $request->getBody()->write($body);
        $request->getBody()->rewind();

        $response = $app->handle($request);

        $this->assertSame(201, $response->getStatusCode());
        $payload = json_decode((string) $response->getBody(), true);
        $this->assertSame('Application created', $payload['message']);
    }

    private function createAppWithFakeRepo(): App
    {
        $repo = new class extends ApplicationRepository {
            public array $applications = [];

            public function __construct()
            {
                // Override parent constructor.
                $this->applications = [
                    [
                        'id' => 1,
                        'offer_id' => 10,
                        'email' => 'a@example.com',
                        'cv_url' => 'https://example.com/a.pdf',
                        'created_at' => '2024-01-01 00:00:00',
                    ],
                    [
                        'id' => 2,
                        'offer_id' => 11,
                        'email' => 'b@example.com',
                        'cv_url' => 'https://example.com/b.pdf',
                        'created_at' => '2024-01-02 00:00:00',
                    ],
                ];
            }

            public function createOrGetApplication(int $offerId, string $email, string $cvUrl): array
            {
                $newId = count($this->applications) + 1;
                $this->applications[] = [
                    'id' => $newId,
                    'offer_id' => $offerId,
                    'email' => $email,
                    'cv_url' => $cvUrl,
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                return ['created' => true, 'id' => $newId];
            }

            public function findApplicationById(int $id): ?array
            {
                foreach ($this->applications as $app) {
                    if ($app['id'] === $id) {
                        return $app;
                    }
                }
                return null;
            }

            public function fetchStats(): array
            {
                return ['applications_total' => count($this->applications), 'metrics' => ['success' => 0, 'failed' => 0]];
            }

            public function fetchAllApplications(): array
            {
                return $this->applications;
            }
        };

        $validator = new ApplicationValidator();
        $logger = new Logger('test');

        $app = AppFactory::create();
        $controller = new ApplicationController($repo, $validator, $logger);

        $app->post('/apply', [$controller, 'apply']);
        $app->get('/applications', [$controller, 'list']);
        $app->get('/stats', [$controller, 'stats']);

        return $app;
    }
}
