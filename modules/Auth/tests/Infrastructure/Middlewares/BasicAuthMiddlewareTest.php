<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Auth\Tests\Infrastructure\Middlewares;

use CubaDevOps\Flexi\Contracts\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Modules\Auth\Domain\Interfaces\CredentialsRepositoryInterface;
use CubaDevOps\Flexi\Modules\Auth\Domain\Interfaces\CredentialsVerifierInterface;
use CubaDevOps\Flexi\Modules\Auth\Domain\ValueObjects\Credentials;
use CubaDevOps\Flexi\Modules\Auth\Infrastructure\Middlewares\BasicAuthMiddleware;
use CubaDevOps\Flexi\Test\TestData\TestDoubles\DummyResponseFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class BasicAuthMiddlewareTest extends TestCase
{
    private BasicAuthMiddleware $middleware;
    private CredentialsRepositoryInterface $repository;
    private CredentialsVerifierInterface $verifier;
    private ServerRequestInterface $request;
    private RequestHandlerInterface $handler;
    private ResponseFactoryInterface $response_factory;
    private EventBusInterface $event_bus;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CredentialsRepositoryInterface::class);
        $this->verifier = $this->createMock(CredentialsVerifierInterface::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->response_factory = new DummyResponseFactory();
        $this->event_bus = $this->createMock(EventBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->middleware = new BasicAuthMiddleware(
            $this->repository,
            $this->verifier,
            $this->createMock(\CubaDevOps\Flexi\Contracts\Interfaces\SessionStorageInterface::class),
            $this->response_factory,
            $this->event_bus,
            $this->logger
        );
    }

    public function testProcessWithValidCredentials(): void
    {
        // Setup valid credentials in Base64 format
        // username:password => base64(username:password)
        $auth_header = 'Basic ' . base64_encode('admin:password123');

        $this->request
            ->expects($this->atLeastOnce())
            ->method('getHeaderLine')
            ->willReturnMap([
                ['Authorization', $auth_header],
                ['X-Forwarded-For', ''],
                ['X-Real-IP', ''],
            ]);

        $this->request
            ->method('getServerParams')
            ->willReturn(['REMOTE_ADDR' => '127.0.0.1']);

        // User found in repository
        $this->repository
            ->expects($this->once())
            ->method('findByUsername')
            ->with('admin')
            ->willReturn([
                'username' => 'admin',
                'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
                'user_id' => 1,
            ]);

        // Credentials verified successfully
        $this->verifier
            ->expects($this->once())
            ->method('verify')
            ->willReturn(true);

        // Handler returns success response
        $success_response = $this->response_factory->createResponse(200);
        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->willReturn($success_response);

        $response = $this->middleware->process($this->request, $this->handler);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testProcessWithMissingAuthorizationHeader(): void
    {
        $this->request
            ->expects($this->atLeastOnce())
            ->method('getHeaderLine')
            ->willReturnMap([
                ['Authorization', ''],
                ['X-Forwarded-For', ''],
                ['X-Real-IP', ''],
            ]);

        $this->request
            ->method('getServerParams')
            ->willReturn(['REMOTE_ADDR' => '127.0.0.1']);

        $response = $this->middleware->process($this->request, $this->handler);

        self::assertEquals(401, $response->getStatusCode());
        // Reset stream position and verify body
        $response->getBody()->rewind();
        $body_content = $response->getBody()->getContents();
        self::assertStringContainsString('Unauthorized', $body_content);
    }

    public function testProcessWithInvalidBase64(): void
    {
        $this->request
            ->expects($this->atLeastOnce())
            ->method('getHeaderLine')
            ->willReturnMap([
                ['Authorization', 'Basic !!!invalid!!!'],
                ['X-Forwarded-For', ''],
                ['X-Real-IP', ''],
            ]);

        $this->request
            ->method('getServerParams')
            ->willReturn(['REMOTE_ADDR' => '127.0.0.1']);

        $response = $this->middleware->process($this->request, $this->handler);

        self::assertEquals(401, $response->getStatusCode());
    }

    public function testProcessWithUserNotFound(): void
    {
        $auth_header = 'Basic ' . base64_encode('nonexistent:password');

        $this->request
            ->expects($this->atLeastOnce())
            ->method('getHeaderLine')
            ->willReturnMap([
                ['Authorization', $auth_header],
                ['X-Forwarded-For', ''],
                ['X-Real-IP', ''],
            ]);

        $this->request
            ->method('getServerParams')
            ->willReturn(['REMOTE_ADDR' => '127.0.0.1']);

        $this->repository
            ->expects($this->once())
            ->method('findByUsername')
            ->with('nonexistent')
            ->willReturn(null);

        $response = $this->middleware->process($this->request, $this->handler);

        self::assertEquals(401, $response->getStatusCode());
    }

    public function testProcessWithInvalidPassword(): void
    {
        $auth_header = 'Basic ' . base64_encode('admin:wrongpassword');

        $this->request
            ->expects($this->atLeastOnce())
            ->method('getHeaderLine')
            ->willReturnMap([
                ['Authorization', $auth_header],
                ['X-Forwarded-For', ''],
                ['X-Real-IP', ''],
            ]);

        $this->request
            ->method('getServerParams')
            ->willReturn(['REMOTE_ADDR' => '127.0.0.1']);

        $this->repository
            ->expects($this->once())
            ->method('findByUsername')
            ->with('admin')
            ->willReturn([
                'username' => 'admin',
                'password_hash' => password_hash('correctpassword', PASSWORD_BCRYPT),
                'user_id' => 1,
            ]);

        $this->verifier
            ->expects($this->once())
            ->method('verify')
            ->willReturn(false);

        $response = $this->middleware->process($this->request, $this->handler);

        self::assertEquals(401, $response->getStatusCode());
    }
}
