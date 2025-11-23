<?php

use App\Controller\ApplicationController;
use App\Repository\ApplicationRepository;
use App\Validator\ApplicationValidator;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class ApplicationControllerTest extends TestCase
{
    private ServerRequestInterface $request;
    private ResponseInterface $response;
    private ApplicationValidator $validator;
    private Logger $logger;

    protected function setUp(): void
    {
        $this->request = (new ServerRequestFactory())->createServerRequest('POST', '/apply');
        $this->response = (new ResponseFactory())->createResponse();
        $this->validator = new ApplicationValidator();
        $this->logger = new Logger('test');
    }

    public function testApplySuccessReturns201(): void
    {
        $payload = [
            'offer_id' => 10,
            'email' => 'user@example.com',
            'cv_url' => 'https://example.com/cv.pdf',
        ];

        $repo = $this->createMock(ApplicationRepository::class);
        $repo->expects($this->once())->method('createOrGetApplication')->willReturn(['created' => true, 'id' => 1]);
        $repo->expects($this->once())->method('findApplicationById')->willReturn([
            'id' => 1,
            'offer_id' => 10,
            'email' => 'user@example.com',
            'cv_url' => 'https://example.com/cv.pdf',
            'created_at' => '2024-01-01 00:00:00',
        ]);
        $repo->expects($this->atLeastOnce())->method('recordMetric');

        $controller = new ApplicationController($repo, $this->validator, $this->logger);
        $request = $this->request->withParsedBody(null)->withBody($this->createStream($payload));

        $res = $controller->apply($request, $this->response);

        $this->assertSame(201, $res->getStatusCode());
        $body = json_decode((string) $res->getBody(), true);
        $this->assertEquals(1, $body['application']['id']);
    }

    public function testApplyValidationErrorReturns422(): void
    {
        $repo = $this->createMock(ApplicationRepository::class);
        $repo->expects($this->any())->method('recordMetric');

        $controller = new ApplicationController($repo, $this->validator, $this->logger);
        $request = $this->request->withBody($this->createStream([
            'offer_id' => 'abc',
        ]));

        $res = $controller->apply($request, $this->response);

        $this->assertSame(422, $res->getStatusCode());
        $body = json_decode((string) $res->getBody(), true);
        $this->assertArrayHasKey('errors', $body);
    }

    private function createStream(array $payload)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, json_encode($payload));
        rewind($stream);
        return new \Slim\Psr7\Stream($stream);
    }
}
