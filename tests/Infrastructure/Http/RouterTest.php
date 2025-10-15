<?php

namespace CubaDevOps\Flexi\Test\Infrastructure\Http;

use CubaDevOps\Flexi\Domain\Classes\Route;
use CubaDevOps\Flexi\Infrastructure\Http\Router;
use CubaDevOps\Flexi\Domain\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Domain\Interfaces\ObjectBuilderInterface;
use CubaDevOps\Flexi\Domain\Interfaces\SessionStorageInterface;
use CubaDevOps\Flexi\Infrastructure\Controllers\HealthController;
use CubaDevOps\Flexi\Test\TestData\TestDoubles\RouterMock;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class RouterTest extends TestCase
{
    private const ROUTE_NAME = 'health';
    private const ROUTE_PATH = '/health';
    private const ROUTE_CTRL = 'TestingControllerFactory';

    private SessionStorageInterface $session;
    private EventBusInterface $event_bus;
    private ObjectBuilderInterface $class_factory;
    private ResponseFactoryInterface $response_factory;
    private \Psr\Container\ContainerInterface $container;

    private Router $router;

    /**
     * @throws \JsonException
     */
    public function setUp(): void
    {
        $this->session = $this->createMock(SessionStorageInterface::class);
        $this->event_bus = $this->createMock(EventBusInterface::class);
        $this->class_factory = $this->createMock(ObjectBuilderInterface::class);
        $this->response_factory = $this->createMock(ResponseFactoryInterface::class);
        $this->container = $this->createMock(\Psr\Container\ContainerInterface::class);

        $this->router = new RouterMock($this->session, $this->event_bus, $this->class_factory, $this->response_factory, $this->container);

        $route = new Route(
            self::ROUTE_NAME,
            self::ROUTE_PATH,
            self::ROUTE_CTRL,
            'GET',
            ['test' => 'param']
        );
        $this->router->addRoute($route);
    }

    public function testAddRoute(): void
    {
        $route = new Route(
            self::ROUTE_NAME,
            self::ROUTE_PATH,
            self::ROUTE_CTRL,
            'GET',
            ['test' => 'param']
        );
        $this->router->addRoute($route);
        $this->assertEquals(2, $this->router->route_counter);
    }

    public function testDispatch(): void
    {
        $_SERVER['HTTP_HOST']       = 'flexi';
        $_SERVER['REQUEST_SCHEME']  = 'https';

        $route = new Route(
            self::ROUTE_NAME,
            self::ROUTE_PATH,
            self::ROUTE_CTRL,
            'GET',
            ['test' => 'param']
        );
        $this->router->addRoute($route);

        $uriInterfaceMock = $this->createMock(UriInterface::class);
        $healthHandlerMock = $this->createMock(HealthController::class);
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);
        $responseInterfaceMock = $this->createMock(ResponseInterface::class);

        $serverRequestMock->expects($this->once())
            ->method('getUri')->willReturn($uriInterfaceMock);

        $uriInterfaceMock->expects($this->once())
            ->method('getPath')->willReturn(self::ROUTE_PATH);

        $serverRequestMock->expects($this->once())
            ->method('getMethod')->willReturn('GET');

        $this->event_bus->expects($this->exactly(2))
            ->method('dispatch')->willReturnSelf();

        $this->class_factory->expects($this->once())
            ->method('build')->willReturn($healthHandlerMock);

        $healthHandlerMock->expects($this->once())
            ->method('handle')->willReturn($responseInterfaceMock);

        $response = $this->router->dispatch($serverRequestMock);

        $this->assertNotEmpty($response);
        $this->assertFalse($this->router->redirect_to_not_found_spy);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws \ReflectionException
     * @throws \JsonException
     */
    public function testDispatchNoRoutes(): void
    {
        $_SERVER['HTTP_HOST']       = 'flexi';
        $_SERVER['REQUEST_SCHEME']  = 'https';

        $emptyRouter = new Router(
            $this->session, $this->event_bus, $this->class_factory, $this->response_factory, $this->container
        );

        $uriInterfaceMock = $this->createMock(UriInterface::class);
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);

        $serverRequestMock->expects($this->once())
            ->method('getUri')->willReturn($uriInterfaceMock);

        $uriInterfaceMock->expects($this->once())
            ->method('getPath')->willReturn(self::ROUTE_PATH);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Define at least one route');

        $emptyRouter->dispatch($serverRequestMock);
        $this->assertFalse($this->router->redirect_to_not_found_spy);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     */
    public function testDispatchParameterRequired(): void
    {
        $_SERVER['HTTP_HOST']       = 'flexi';
        $_SERVER['REQUEST_SCHEME']  = 'https';

        $requiredParam = 'user';
        $route = new Route(
            self::ROUTE_NAME,
            self::ROUTE_PATH,
            self::ROUTE_CTRL,
            'GET',
            [
                0 => [
                    'required' => true,
                    'name'     => $requiredParam
                ]
            ]
        );
        $this->router->addRoute($route);

        $uriInterfaceMock = $this->createMock(UriInterface::class);
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);

        $serverRequestMock->expects($this->once())
            ->method('getUri')->willReturn($uriInterfaceMock);

        $uriInterfaceMock->expects($this->once())
            ->method('getPath')->willReturn(self::ROUTE_PATH);

        $serverRequestMock->expects($this->once())
            ->method('getMethod')->willReturn('GET');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Parameter '{$requiredParam}' is required");

        $this->router->dispatch($serverRequestMock);
        $this->assertFalse($this->router->redirect_to_not_found_spy);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     */
    public function testDispatchInvalidMethod(): void
    {
        $_SERVER['HTTP_HOST']       = 'flexi';
        $_SERVER['REQUEST_SCHEME']  = 'https';
        $invalidMethod = 'POST';

        $uriInterfaceMock = $this->createMock(UriInterface::class);
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);

        $serverRequestMock->expects($this->once())
            ->method('getUri')->willReturn($uriInterfaceMock);

        $uriInterfaceMock->expects($this->once())
            ->method('getPath')->willReturn(self::ROUTE_PATH);

        $serverRequestMock->expects($this->exactly(2))
            ->method('getMethod')->willReturn($invalidMethod);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Method $invalidMethod is not allowed for this route");

        $this->router->dispatch($serverRequestMock);
        $this->assertFalse($this->router->redirect_to_not_found_spy);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     */
    public function testDispatchDirectToNotFound(): void
    {
        $_SERVER['HTTP_HOST']       = 'flexi';
        $_SERVER['REQUEST_SCHEME']  = 'https';

        $uriInterfaceMock = $this->createMock(UriInterface::class);
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);
        $responseInterfaceMock = $this->createMock(ResponseInterface::class);
        $responseInterfaceMock->expects($this->once())
            ->method('getStatusCode')->willReturn(404);

        $serverRequestMock->expects($this->once())
            ->method('getUri')->willReturn($uriInterfaceMock);

        $uriInterfaceMock->expects($this->once())
            ->method('getPath')->willReturn('/invalid');

        $this->response_factory->expects($this->once())
            ->method('createResponse')->willReturn($responseInterfaceMock);

        $response = $this->router->dispatch($serverRequestMock);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertTrue($this->router->redirect_to_not_found_spy);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testGetByNameDoesNotExist(): void
    {
        $invalidRouteName = '/invalid-route-name';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Route '{$invalidRouteName}' not found");

        $this->router->getByName($invalidRouteName);
    }
}
