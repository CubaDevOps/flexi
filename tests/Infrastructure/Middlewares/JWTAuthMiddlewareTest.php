<?php

namespace CubaDevOps\Flexi\Test\Infrastructure\Middlewares;

use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use CubaDevOps\Flexi\Infrastructure\Middlewares\JWTAuthMiddleware;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JWTAuthMiddlewareTest extends TestCase
{
    private Configuration $configuration;
    private ResponseFactoryInterface $responseFactory;
    private RequestHandlerInterface $handler;
    private ServerRequestInterface $request;
    private ResponseInterface $unauthorizedResponse;

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

        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn($this->createMock(ResponseInterface::class));

        $middleware = new JWTAuthMiddleware($this->configuration, $this->responseFactory);
        $response = $middleware->process($this->request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
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

        $this->assertSame($this->unauthorizedResponse, $response);
    }

    public function testMissingAuthorizationHeader()
    {
        $this->request->method('getHeaderLine')
            ->with('Authorization')
            ->willReturn('');

        $this->unauthorizedResponse->expects($this->once())
            ->method('withHeader')
            ->with('WWW-Authenticate', 'Bearer')
            ->willReturnSelf();

        $middleware = new JWTAuthMiddleware($this->configuration, $this->responseFactory);
        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->unauthorizedResponse, $response);
    }

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->unauthorizedResponse = $this->createMock(ResponseInterface::class);

        $this->responseFactory
            ->method('createResponse')
            ->willReturnCallback(function (int $status, string $reason) {
                return $this->unauthorizedResponse;
            });
    }
}
