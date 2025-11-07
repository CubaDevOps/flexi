<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Factories;

use CubaDevOps\Flexi\Infrastructure\Factories\RouterFactory;
use CubaDevOps\Flexi\Infrastructure\Http\Router;
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

    public function setUp(): void
    {
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->objectBuilder = $this->createMock(ObjectBuilderInterface::class);
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->routerFactory = new RouterFactory(
            $this->eventBus,
            $this->objectBuilder,
            $this->responseFactory,
            $this->container
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
        $router = $this->routerFactory->getInstance($routeFile);

        $this->assertInstanceOf(Router::class, $router);
    }

    public function testGetInstanceWithInvalidFile(): void
    {
        $this->expectException(\JsonException::class);

        // Create file with invalid JSON in temp directory
        $invalidFile = sys_get_temp_dir() . '/invalid_routes_' . uniqid() . '.json';
        file_put_contents($invalidFile, '{invalid json}');

        try {
            $this->routerFactory->getInstance($invalidFile);
        } finally {
            if (file_exists($invalidFile)) {
                unlink($invalidFile);
            }
        }
    }

    public function testGetInstanceWithNonExistentFile(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->routerFactory->getInstance('/non/existent/file.json');
    }
}