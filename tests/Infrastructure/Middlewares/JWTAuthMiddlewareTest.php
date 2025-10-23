<?php

namespace CubaDevOps\Flexi\Test\Infrastructure\Middlewares;

use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use CubaDevOps\Flexi\Infrastructure\Middlewares\JWTAuthMiddleware;
use CubaDevOps\Flexi\Test\TestData\TestDoubles\TestHttpHandler;
use Firebase\JWT\JWT;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JWTAuthMiddlewareTest extends TestCase
{
    private Configuration $configuration;
    private ResponseFactoryInterface $responseFactory;
    private RequestHandlerInterface $handler;
    private ServerRequestInterface $request;

    public function testValidJWT()
    {
        $key = 'secret_key';
        $payload = ['user_id' => 123];
        $jwt = JWT::encode($payload, $key, 'HS256');

        $this->configuration->method('get')->with('webhook_secret')->willReturn($key);

        $this->request->method('getHeaderLine')
            ->with('Authorization')
            ->willReturn("Bearer $jwt");

        $this->request->method('withAttribute')->willReturnSelf();

        $this->handler->method('handle')
            ->willReturn($this->responseFactory->createResponse(200, 'OK'));

        $middleware = new JWTAuthMiddleware($this->configuration, $this->responseFactory);
        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testInvalidJWT()
    {
        $key = 'secret_key';
        $invalidJwt = 'invalid.jwt.token';

        $this->configuration->method('get')->with('webhook_secret')->willReturn($key);

        $this->request->method('getHeaderLine')
            ->with('Authorization')
            ->willReturn("Bearer $invalidJwt");

        $middleware = new JWTAuthMiddleware($this->configuration, $this->responseFactory);
        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($response->getStatusCode(), 401);
        $this->assertSame('Malformed UTF-8 characters', $response->getReasonPhrase());
    }

    public function testMissingAuthorizationHeader()
    {
        $this->request->method('getHeaderLine')
            ->with('Authorization')
            ->willReturn('');

        $middleware = new JWTAuthMiddleware($this->configuration, $this->responseFactory);
        $response = $middleware->process($this->request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Authorization header not found', $response->getReasonPhrase());
    }

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $this->handler = $this->createMock(TestHttpHandler::class);
        $this->request = $this->createMock(ServerRequestInterface::class);

        $callback = function (int $status, string $reason) {
            return new Response($status, [], null, '1.1', $reason);
        };
        $this->responseFactory
            ->method('createResponse')
            ->willReturnCallback($callback);
    }
}
