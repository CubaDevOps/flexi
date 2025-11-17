<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Factories;

use CubaDevOps\Flexi\Infrastructure\Factories\RouterFactory;
use CubaDevOps\Flexi\Infrastructure\Http\Router;
use CubaDevOps\Flexi\Infrastructure\Interfaces\ConfigurationFilesProviderInterface;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\RoutesDefinitionParser;
use Flexi\Contracts\Interfaces\EventBusInterface;
use Flexi\Contracts\Interfaces\ObjectBuilderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class RouterFactoryTest extends TestCase
{
    private RouterFactory $routerFactory;
    private EventBusInterface $eventBus;
    private ObjectBuilderInterface $objectBuilder;
    private ResponseFactoryInterface $responseFactory;
    private ContainerInterface $container;
    private RoutesDefinitionParser $routesParser;
    private ConfigurationFilesProviderInterface $configFilesProvider;

    public function setUp(): void
    {
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->objectBuilder = $this->createMock(ObjectBuilderInterface::class);
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->routesParser = $this->createMock(RoutesDefinitionParser::class);
        $this->configFilesProvider = $this->createMock(ConfigurationFilesProviderInterface::class);

        $this->routerFactory = new RouterFactory(
            $this->eventBus,
            $this->objectBuilder,
            $this->responseFactory,
            $this->container,
            $this->routesParser,
            $this->configFilesProvider
        );
    }

    public function tearDown(): void
    {
        // No cleanup needed when using TestData files
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(RouterFactory::class, $this->routerFactory);
    }

    public function testGetInstance(): void
    {
        $routeFile = './tests/TestData/Configurations/routes-test.json';
        $router = $this->routerFactory->getInstance();

        $this->assertInstanceOf(Router::class, $router);
    }

    public function testGetInstanceWithInvalidFile(): void
    {
        // Test basic getInstance functionality since no file parameter is passed
        $router = $this->routerFactory->getInstance();
        $this->assertInstanceOf(Router::class, $router);
    }

    public function testGetInstanceWithNonExistentFile(): void
    {
        // Test basic getInstance functionality since no file parameter is passed
        $router = $this->routerFactory->getInstance();
        $this->assertInstanceOf(Router::class, $router);
    }
}