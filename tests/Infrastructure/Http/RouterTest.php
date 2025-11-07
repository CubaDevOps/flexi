<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Http;

use Flexi\Contracts\Interfaces\EventBusInterface;
use Flexi\Contracts\Interfaces\ObjectBuilderInterface;
use Flexi\Contracts\Interfaces\CacheInterface;
use CubaDevOps\Flexi\Infrastructure\Classes\Collection;
use CubaDevOps\Flexi\Infrastructure\Http\Route;
use CubaDevOps\Flexi\Infrastructure\Http\Router;
use CubaDevOps\Flexi\Test\TestData\TestDoubles\RouterMock;
use CubaDevOps\Flexi\Test\TestData\TestDoubles\TestHttpHandler;
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

    private $event_bus;
    private $class_factory;
    private ResponseFactoryInterface $response_factory;
    private \Psr\Container\ContainerInterface $container;

    private RouterMock $router;

    /**
     * @throws \JsonException
     */
    public function setUp(): void
    {
        $this->event_bus = $this->createMock(EventBusInterface::class);
        $this->class_factory = $this->createMock(ObjectBuilderInterface::class);
        $this->response_factory = $this->createMock(ResponseFactoryInterface::class);
        $this->container = $this->createMock(\Psr\Container\ContainerInterface::class);

        $this->router = new RouterMock($this->event_bus, $this->class_factory, $this->response_factory, $this->container);

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
        $_SERVER['HTTP_HOST'] = 'flexi';
        $_SERVER['REQUEST_SCHEME'] = 'https';

        $route = new Route(
            self::ROUTE_NAME,
            self::ROUTE_PATH,
            self::ROUTE_CTRL,
            'GET',
            ['test' => 'param']
        );
        $this->router->addRoute($route);

        $uriInterfaceMock = $this->createMock(UriInterface::class);
        $testHandler = new TestHttpHandler();
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);
        $responseInterfaceMock = $this->createMock(ResponseInterface::class);

        $testHandler->setMockResponse($responseInterfaceMock);

        $serverRequestMock->expects($this->exactly(2))
            ->method('getUri')->willReturn($uriInterfaceMock);

        $uriInterfaceMock->expects($this->once())
            ->method('getPath')->willReturn(self::ROUTE_PATH);

        $serverRequestMock->expects($this->once())
            ->method('getMethod')->willReturn('GET');

        $this->event_bus->expects($this->exactly(2))
            ->method('dispatch')->willReturnSelf();

        $this->class_factory->expects($this->once())
            ->method('build')->willReturn($testHandler);

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
        $_SERVER['HTTP_HOST'] = 'flexi';
        $_SERVER['REQUEST_SCHEME'] = 'https';

        $emptyRouter = new Router(
            $this->event_bus, $this->class_factory, $this->response_factory, $this->container
        );

        $uriInterfaceMock = $this->createMock(UriInterface::class);
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);

        $serverRequestMock->expects($this->exactly(2))
            ->method('getUri')->willReturn($uriInterfaceMock);

        $uriInterfaceMock->expects($this->once())
            ->method('getPath')->willReturn(self::ROUTE_PATH);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Define at least one route');

        $emptyRouter->dispatch($serverRequestMock);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     */
    public function testDispatchParameterRequired(): void
    {
        $_SERVER['HTTP_HOST'] = 'flexi';
        $_SERVER['REQUEST_SCHEME'] = 'https';

        $requiredParam = 'user';
        $route = new Route(
            self::ROUTE_NAME,
            self::ROUTE_PATH,
            self::ROUTE_CTRL,
            'GET',
            [
                0 => [
                    'required' => true,
                    'name' => $requiredParam,
                ],
            ]
        );
        $this->router->addRoute($route);

        $uriInterfaceMock = $this->createMock(UriInterface::class);
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);

        $serverRequestMock->expects($this->exactly(2))
            ->method('getUri')->willReturn($uriInterfaceMock);

        $uriInterfaceMock->expects($this->once())
            ->method('getPath')->willReturn(self::ROUTE_PATH);

        $serverRequestMock->expects($this->once())
            ->method('getMethod')->willReturn('GET');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Parameter '{$requiredParam}' is required");

        $this->router->dispatch($serverRequestMock);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     */
    public function testDispatchInvalidMethod(): void
    {
        $_SERVER['HTTP_HOST'] = 'flexi';
        $_SERVER['REQUEST_SCHEME'] = 'https';
        $invalidMethod = 'POST';

        $uriInterfaceMock = $this->createMock(UriInterface::class);
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);

        $serverRequestMock->expects($this->exactly(2))
            ->method('getUri')->willReturn($uriInterfaceMock);

        $uriInterfaceMock->expects($this->once())
            ->method('getPath')->willReturn(self::ROUTE_PATH);

        $serverRequestMock->expects($this->exactly(2))
            ->method('getMethod')->willReturn($invalidMethod);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Method $invalidMethod is not allowed for this route");

        $this->router->dispatch($serverRequestMock);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     */
    public function testDispatchDirectToNotFound(): void
    {
        $_SERVER['HTTP_HOST'] = 'flexi';
        $_SERVER['REQUEST_SCHEME'] = 'https';

        $uriInterfaceMock = $this->createMock(UriInterface::class);
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);
        $responseInterfaceMock = $this->createMock(ResponseInterface::class);
        $responseInterfaceMock->expects($this->once())
            ->method('getStatusCode')->willReturn(404);

        $serverRequestMock->expects($this->exactly(2))
            ->method('getUri')->willReturn($uriInterfaceMock);

        $uriInterfaceMock->expects($this->once())
            ->method('getPath')->willReturn('/invalid');

        $this->response_factory->expects($this->once())
            ->method('createResponse')->willReturn($responseInterfaceMock);

        $response = $this->router->dispatch($serverRequestMock);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testGetByNameDoesNotExist(): void
    {
        $invalidRouteName = '/invalid-route-name';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Route '{$invalidRouteName}' not found");

        $this->router->getByName($invalidRouteName);
    }

    public function testGetByNameExistingRoute(): void
    {
        $route = $this->router->getByName(self::ROUTE_NAME);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(self::ROUTE_NAME, $route->getName());
        $this->assertEquals(self::ROUTE_PATH, $route->getPath());
        $this->assertEquals(self::ROUTE_CTRL, $route->getController());
    }

    public function testGetUrlBase(): void
    {
        $_SERVER['REQUEST_SCHEME'] = 'https';
        $_SERVER['HTTP_HOST'] = 'flexi-test.local';

        $urlBase = Router::getUrlBase();

        $this->assertEquals('https://flexi-test.local', $urlBase);
    }

    public function testGetUrlBaseHttp(): void
    {
        $_SERVER['REQUEST_SCHEME'] = 'http';
        $_SERVER['HTTP_HOST'] = 'localhost:8080';

        $urlBase = Router::getUrlBase();

        $this->assertEquals('http://localhost:8080', $urlBase);
    }

    public function testAddRouteReturnsRouter(): void
    {
        $route = new Route(
            'api-route',
            '/api/test',
            'ApiController',
            'POST',
            []
        );

        $result = $this->router->addRoute($route);

        $this->assertSame($this->router, $result);
        $this->assertEquals(2, $this->router->route_counter); // We already have one route in setUp
    }

    public function testDispatchWithMiddlewares(): void
    {
        $_SERVER['HTTP_HOST'] = 'flexi';
        $_SERVER['REQUEST_SCHEME'] = 'https';

        // Create route with middlewares
        $routeWithMiddleware = new Route(
            'middleware-route',
            '/middleware',
            'MiddlewareController',
            'GET',
            [],
            ['AuthMiddleware', 'LoggingMiddleware']
        );
        $this->router->addRoute($routeWithMiddleware);

        $uriInterfaceMock = $this->createMock(UriInterface::class);
        $testHandler = new TestHttpHandler();
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);
        $responseInterfaceMock = $this->createMock(ResponseInterface::class);

        $testHandler->setMockResponse($responseInterfaceMock);

        $serverRequestMock->expects($this->exactly(2))
            ->method('getUri')->willReturn($uriInterfaceMock);

        $uriInterfaceMock->expects($this->once())
            ->method('getPath')->willReturn('/middleware');

        $serverRequestMock->expects($this->once())
            ->method('getMethod')->willReturn('GET');

        $this->event_bus->expects($this->exactly(2))
            ->method('dispatch')->willReturnSelf();

        // Handler first, then middlewares
        $this->class_factory->expects($this->exactly(3))
            ->method('build')
            ->withConsecutive(
                [$this->container, 'MiddlewareController'],
                [$this->container, 'AuthMiddleware'],
                [$this->container, 'LoggingMiddleware']
            )
            ->willReturnOnConsecutiveCalls(
                $testHandler,
                $this->createMock(\Psr\Http\Server\MiddlewareInterface::class),
                $this->createMock(\Psr\Http\Server\MiddlewareInterface::class)
            );

        $response = $this->router->dispatch($serverRequestMock);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testDispatchWithOptionalParameters(): void
    {
        $_SERVER['HTTP_HOST'] = 'flexi';
        $_SERVER['REQUEST_SCHEME'] = 'https';

        // Create route with optional parameters
        $routeWithParams = new Route(
            'params-route',
            '/params',
            'ParamsController',
            'GET',
            [
                [
                    'required' => false,
                    'name' => 'optional_param',
                ]
            ]
        );
        $this->router->addRoute($routeWithParams);

        $uriInterfaceMock = $this->createMock(UriInterface::class);
        $testHandler = new TestHttpHandler();
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);
        $responseInterfaceMock = $this->createMock(ResponseInterface::class);

        $testHandler->setMockResponse($responseInterfaceMock);

        $serverRequestMock->expects($this->exactly(2))
            ->method('getUri')->willReturn($uriInterfaceMock);

        $uriInterfaceMock->expects($this->once())
            ->method('getPath')->willReturn('/params');

        $serverRequestMock->expects($this->once())
            ->method('getMethod')->willReturn('GET');

        $this->event_bus->expects($this->exactly(2))
            ->method('dispatch')->willReturnSelf();

        $this->class_factory->expects($this->once())
            ->method('build')->willReturn($testHandler);

        $response = $this->router->dispatch($serverRequestMock);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testDispatchWithValidRequiredParameter(): void
    {
        $_SERVER['HTTP_HOST'] = 'flexi';
        $_SERVER['REQUEST_SCHEME'] = 'https';
        $_GET['user_id'] = '123'; // Set required parameter

        $routeWithRequiredParam = new Route(
            'user-route',
            '/user',
            'UserController',
            'GET',
            [
                [
                    'required' => true,
                    'name' => 'user_id',
                ]
            ]
        );
        $this->router->addRoute($routeWithRequiredParam);

        $uriInterfaceMock = $this->createMock(UriInterface::class);
        $testHandler = new TestHttpHandler();
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);
        $responseInterfaceMock = $this->createMock(ResponseInterface::class);

        $testHandler->setMockResponse($responseInterfaceMock);

        $serverRequestMock->expects($this->exactly(2))
            ->method('getUri')->willReturn($uriInterfaceMock);

        $uriInterfaceMock->expects($this->once())
            ->method('getPath')->willReturn('/user');

        $serverRequestMock->expects($this->once())
            ->method('getMethod')->willReturn('GET');

        $this->event_bus->expects($this->exactly(2))
            ->method('dispatch')->willReturnSelf();

        $this->class_factory->expects($this->once())
            ->method('build')->willReturn($testHandler);

        $response = $this->router->dispatch($serverRequestMock);

        $this->assertInstanceOf(ResponseInterface::class, $response);

        // Cleanup
        unset($_GET['user_id']);
    }

    public function testDispatchWithPostParameter(): void
    {
        $_SERVER['HTTP_HOST'] = 'flexi';
        $_SERVER['REQUEST_SCHEME'] = 'https';
        $_POST['form_data'] = 'test'; // Set POST parameter

        $routeWithPostParam = new Route(
            'post-route',
            '/submit',
            'SubmitController',
            'POST',
            [
                [
                    'required' => true,
                    'name' => 'form_data',
                ]
            ]
        );
        $this->router->addRoute($routeWithPostParam);

        $uriInterfaceMock = $this->createMock(UriInterface::class);
        $testHandler = new TestHttpHandler();
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);
        $responseInterfaceMock = $this->createMock(ResponseInterface::class);

        $testHandler->setMockResponse($responseInterfaceMock);

        $serverRequestMock->expects($this->exactly(2))
            ->method('getUri')->willReturn($uriInterfaceMock);

        $uriInterfaceMock->expects($this->once())
            ->method('getPath')->willReturn('/submit');

        $serverRequestMock->expects($this->once())
            ->method('getMethod')->willReturn('POST');

        $this->event_bus->expects($this->exactly(2))
            ->method('dispatch')->willReturnSelf();

        $this->class_factory->expects($this->once())
            ->method('build')->willReturn($testHandler);

        $response = $this->router->dispatch($serverRequestMock);

        $this->assertInstanceOf(ResponseInterface::class, $response);

        // Cleanup
        unset($_POST['form_data']);
    }
}
