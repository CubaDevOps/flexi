<?php

namespace CubaDevOps\Flexi\Test\Domain\Classes;

use CubaDevOps\Flexi\Domain\Classes\Route;
use CubaDevOps\Flexi\Domain\Utils\ClassFactory;
use CubaDevOps\Flexi\Infrastructure\Classes\HttpHandler;
use CubaDevOps\Flexi\Test\TestData\TestTools\RouteMock;
use CubaDevOps\Flexi\Test\TestData\TestTools\RouteVisitor\MiddlewareTestVisitor;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteTest extends TestCase
{
    private const ROUTE_NAME = 'testing';
    private const ROUTE_PATH = 'v1/testing';
    private const ROUTE_CTRL = 'TestingControllerFactory';
    private const ROUTE_METHOD = 'POST';
    private const ROUTE_PARAMS =  [
        'message' => 'testing',
        'to'      => 'John Doe'
    ];
    private const ROUTE_MIDDLEWARES =  [MiddlewareInterface::class];

    private Route $route;

    public function setUp(): void
    {
        $this->route = new Route(
            self::ROUTE_NAME,
            self::ROUTE_PATH,
        self::ROUTE_CTRL,
            self::ROUTE_METHOD,
            self::ROUTE_PARAMS,
            self::ROUTE_MIDDLEWARES,
        );
    }

    public function testInvalidMethod(): void
    {
        $invalidMethod = 'INVALID';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid method: $invalidMethod");

        $this->route = new Route(
            self::ROUTE_NAME,
            self::ROUTE_PATH,
            self::ROUTE_CTRL,
            $invalidMethod,
            self::ROUTE_PARAMS,
            self::ROUTE_MIDDLEWARES,
        );
    }

    public function testGetController(): void
    {
        $this->assertEquals(self::ROUTE_CTRL, $this->route->getController());
    }

    public function testGetMethod(): void
    {
        $this->assertEquals(self::ROUTE_METHOD, $this->route->getMethod());
    }

    public function testGetParameters(): void
    {
        $this->assertEquals(self::ROUTE_PARAMS, $this->route->getParameters());
    }

    public function testGetPath(): void
    {
        $this->assertEquals(self::ROUTE_PATH, $this->route->getPath());
    }

    public function testGetAbsoluteUrl(): void
    {
        $base_url = 'http://flexi.local';
        $_SERVER['REQUEST_SCHEME'] = $base_url;

        $this->assertEquals($base_url. self::ROUTE_PATH, $this->route->getAbsoluteUrl($base_url));
    }

    public function testGetAbsoluteUrlInvalidBaseUrl(): void
    {
        $base_url = 'http://invalid.local';
        $_SERVER['REQUEST_SCHEME'] = 'http://flexi.local';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Base URL is not valid');

        $this->route->getAbsoluteUrl($base_url);
    }

    public function testGetName(): void
    {
        $this->assertEquals(self::ROUTE_NAME, $this->route->getName());
    }

    public function testHasParameters(): void
    {
        $this->assertTrue($this->route->hasParameters());
    }

    public function testGetMiddlewares(): void
    {
        $this->assertEquals(self::ROUTE_MIDDLEWARES, $this->route->getMiddlewares());
    }

    public function testHasMiddlewares(): void
    {
        $this->assertTrue($this->route->hasMiddlewares());
    }

    public function testThroughMiddlewaresSetsMiddlewares()
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = $this->createMock(ClassFactory::class);
        $handler = $this->createMock(HttpHandler::class);

        $middlewares = ['middleware1', 'middleware2'];
        $route = new RouteMock('test_route', '/test', 'TestController', 'GET', [], $middlewares);

        $factory->method('build')
            ->will($this->onConsecutiveCalls('middleware1_instance', 'middleware2_instance'));

        $handler->expects($this->once())
            ->method('setMiddlewares')
            ->with($this->equalTo(['middleware1_instance', 'middleware2_instance']));

        $route->throughMiddlewares($container, $factory, $handler);

        $this->assertTrue($route->has_middlewares_spy);
        $this->assertSame($middlewares, $route->getMiddlewares());
    }

    public function testThroughMiddlewaresDoesNotSetMiddlewares()
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = $this->createMock(ClassFactory::class);
        $handler = $this->createMock(HttpHandler::class);

        $route = new RouteMock('test_route', '/test', 'TestController', 'GET');

        $route->throughMiddlewares($container, $factory, $handler);

        $this->assertFalse($route->has_middlewares_spy);
        $this->assertSame([], $route->getMiddlewares());
    }
}
