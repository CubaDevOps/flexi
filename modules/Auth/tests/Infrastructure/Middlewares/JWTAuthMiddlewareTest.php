<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Modules\Auth\Infrastructure\Middlewares;

use CubaDevOps\Flexi\Contracts\Interfaces\SecretProviderInterface;
use CubaDevOps\Flexi\Modules\Auth\Infrastructure\Middlewares\JWTAuthMiddleware;
use CubaDevOps\Flexi\Test\TestData\TestDoubles\DummyResponseFactory;
use CubaDevOps\Flexi\Test\TestData\TestDoubles\TestHttpHandler;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JWTAuthMiddlewareTest extends TestCase
{
    private SecretProviderInterface $secret_provider;
    private DummyResponseFactory $responseFactory;
    private RequestHandlerInterface $handler;
    private ServerRequestInterface $request;

    public function testValidJWT()
    {
        $key = 'secret_key';
        $payload = ['user_id' => 123];
        $jwt = JWT::encode($payload, $key, 'HS256');

        $this->secret_provider->method('getSecret')->willReturn($key);

        $this->request->method('getHeaderLine')
            ->with('Authorization')
            ->willReturn("Bearer $jwt");

        $this->request->method('withAttribute')->willReturnSelf();

        // Set the expected response on TestHttpHandler
        $this->handler->setMockResponse($this->responseFactory->createResponse(200, 'OK'));

        $middleware = new JWTAuthMiddleware($this->secret_provider, $this->responseFactory);
        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testInvalidJWT()
    {
        $key = 'secret_key';
        $invalidJwt = 'invalid.jwt.token';

        $this->secret_provider->method('getSecret')->willReturn($key);

        $this->request->method('getHeaderLine')
            ->with('Authorization')
            ->willReturn("Bearer $invalidJwt");

        $middleware = new JWTAuthMiddleware($this->secret_provider, $this->responseFactory);
        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($response->getStatusCode(), 401);
        $this->assertSame('Malformed UTF-8 characters', $response->getReasonPhrase());
    }

    public function testMissingAuthorizationHeader()
    {
        $this->request->method('getHeaderLine')
            ->with('Authorization')
            ->willReturn('');

        $middleware = new JWTAuthMiddleware($this->secret_provider, $this->responseFactory);
        $response = $middleware->process($this->request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Authorization header not found', $response->getReasonPhrase());
    }

    protected function setUp(): void
    {
        $this->secret_provider = $this->createMock(SecretProviderInterface::class);
        $this->responseFactory = new DummyResponseFactory();
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = new TestHttpHandler($this->responseFactory);
    }
}
