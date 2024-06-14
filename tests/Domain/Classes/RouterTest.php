<?php

namespace CubaDevOps\Flexi\Test\Domain\Classes;

use CubaDevOps\Flexi\Domain\Classes\Route;
use CubaDevOps\Flexi\Domain\Classes\Router;
use CubaDevOps\Flexi\Domain\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Domain\Interfaces\SessionStorageInterface;
use CubaDevOps\Flexi\Domain\Utils\ClassFactory;
use CubaDevOps\Flexi\Infrastructure\Controllers\HealthController;
use PHPUnit\Framework\TestCase;
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
    private ClassFactory $class_factory;
    private ResponseFactoryInterface $response_factory;

    private Router $router;

    public function setUp(): void
    {
        $this->session = $this->createMock(SessionStorageInterface::class);
        $this->event_bus = $this->createMock(EventBusInterface::class);
        $this->class_factory = $this->createMock(ClassFactory::class);
        $this->response_factory = $this->createMock(ResponseFactoryInterface::class);

        $this->router = new Router($this->session, $this->event_bus, $this->class_factory, $this->response_factory);

        $this->router->loadRoutesFile(dirname(__DIR__, 3) .'/src/Config/routes.json');
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

        $this->assertInstanceOf(Route::class, $this->router->getByName(self::ROUTE_NAME));
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
            ->method('notify')->willReturnSelf();

        $this->class_factory->expects($this->once())
            ->method('build')->willReturn($healthHandlerMock);

        $healthHandlerMock->expects($this->once())
            ->method('handle')->willReturn($responseInterfaceMock);

        $response = $this->router->dispatch($serverRequestMock);

        $this->assertNotEmpty($response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testDispatchNoRoutes(): void
    {
        $_SERVER['HTTP_HOST']       = 'flexi';
        $_SERVER['REQUEST_SCHEME']  = 'https';

        $emptyRouter = new Router(
            $this->session, $this->event_bus, $this->class_factory, $this->response_factory
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
    }

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
    }

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
    }

    public function testDispatchDirectToNotFound(): void
    {
        $_SERVER['HTTP_HOST']       = 'flexi';
        $_SERVER['REQUEST_SCHEME']  = 'https';

        $uriInterfaceMock = $this->createMock(UriInterface::class);
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);
        $responseInterfaceMock = $this->createMock(ResponseInterface::class);

        $serverRequestMock->expects($this->once())
            ->method('getUri')->willReturn($uriInterfaceMock);

        $uriInterfaceMock->expects($this->once())
            ->method('getPath')->willReturn('/invalid');

        $this->event_bus->expects($this->exactly(2))
            ->method('notify')->willReturnSelf();

        $this->response_factory->expects($this->once())
            ->method('createResponse')->willReturn($responseInterfaceMock);

        $responseInterfaceMock->expects($this->once())
            ->method('withHeader')->willReturnSelf();

        $responseInterfaceMock->expects($this->once())
            ->method('withStatus')->willReturnSelf();

        $response = $this->router->dispatch($serverRequestMock);

        $this->assertNotEmpty($response);
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
