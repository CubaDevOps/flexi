<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Factories;

use Flexi\Contracts\Interfaces\ConfigurationRepositoryInterface;
use Flexi\Contracts\Interfaces\ObjectBuilderInterface;
use CubaDevOps\Flexi\Infrastructure\Bus\CommandBus;
use CubaDevOps\Flexi\Infrastructure\Bus\EventBus;
use CubaDevOps\Flexi\Infrastructure\Bus\QueryBus;
use CubaDevOps\Flexi\Infrastructure\Factories\BusFactory;
use CubaDevOps\Flexi\Infrastructure\Classes\InMemoryCache;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\HandlersDefinitionParser;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\ListenersDefinitionParser;
use CubaDevOps\Flexi\Infrastructure\Interfaces\ConfigurationFilesProviderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class BusFactoryTest extends TestCase
{
    private $container;
    private $objectBuilder;
    private $configRepo;
    private $logger;
    private $handlersParser;
    private $listenersParser;
    private $configFilesProvider;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->objectBuilder = $this->createMock(ObjectBuilderInterface::class);
        $this->configRepo = $this->createMock(ConfigurationRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handlersParser = $this->createMock(HandlersDefinitionParser::class);
        $this->listenersParser = $this->createMock(ListenersDefinitionParser::class);
        $this->configFilesProvider = $this->createMock(ConfigurationFilesProviderInterface::class);

        // Configure default behavior for mocks
        $this->configFilesProvider->method('getConfigurationFiles')->willReturn([]);
        $this->handlersParser->method('parse')->willReturn([]);
        $this->listenersParser->method('parse')->willReturn([]);

        // Configure container mock to return our mocked services
        $this->container
            ->method('get')
            ->willReturnCallback(function ($id) {
                switch ($id) {
                    case 'logger':
                        return $this->logger;
                    case ObjectBuilderInterface::class:
                        return $this->objectBuilder;
                    case ConfigurationRepositoryInterface::class:
                        return $this->configRepo;
                    case HandlersDefinitionParser::class:
                        return $this->handlersParser;
                    case ListenersDefinitionParser::class:
                        return $this->listenersParser;
                    case ConfigurationFilesProviderInterface::class:
                        return $this->configFilesProvider;
                    default:
                        throw new \Exception("Service not found: " . $id);
                }
            });
    }    /**
     * Helper method to create BusFactory instance with all mocks
     */
    private function createBusFactory(): BusFactory
    {
        return new BusFactory(
            $this->container,
            $this->handlersParser,
            $this->listenersParser,
            $this->configFilesProvider
        );
    }

    public function testConstruct(): void
    {
        $factory = $this->createBusFactory();
        $this->assertInstanceOf(BusFactory::class, $factory);
    }

    public function testGetInstanceCommandBus(): void
    {
        $factory = $this->createBusFactory();

        $bus = $factory->getInstance(CommandBus::class);

        $this->assertInstanceOf(CommandBus::class, $bus);
    }

    public function testGetInstanceQueryBus(): void
    {
        $factory = $this->createBusFactory();

        $bus = $factory->getInstance(QueryBus::class);

        $this->assertInstanceOf(QueryBus::class, $bus);
    }

    public function testGetInstanceEventBus(): void
    {
        $factory = $this->createBusFactory();

        $bus = $factory->getInstance(EventBus::class);

        $this->assertInstanceOf(EventBus::class, $bus);
    }

    public function testGetInstanceInvalidType(): void
    {
        $factory = $this->createBusFactory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid bus type');

        $factory->getInstance('InvalidBusType');
    }

    public function testGetInstanceWithoutLoggerService(): void
    {
        // Setup container to throw exception when getting logger
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('get')->willReturnCallback(function ($service) {
            if ($service === 'logger') {
                throw new \Exception('Logger service not available');
            }
            if ($service === ObjectBuilderInterface::class) {
                return $this->objectBuilder;
            }
            if ($service === ConfigurationRepositoryInterface::class) {
                return $this->configRepo;
            }
            throw new \Exception("Unknown service: $service");
        });

        $factory = new BusFactory(
            $containerMock,
            $this->handlersParser,
            $this->listenersParser,
            $this->configFilesProvider
        );

        // Should use NullLogger and still work
        $bus = $factory->getInstance(CommandBus::class);

        $this->assertInstanceOf(CommandBus::class, $bus);
    }

    public function testCreateCommandBus(): void
    {
        $bus = BusFactory::createCommandBus($this->container);

        $this->assertInstanceOf(CommandBus::class, $bus);
    }

    public function testCreateQueryBus(): void
    {
        $bus = BusFactory::createQueryBus($this->container);

        $this->assertInstanceOf(QueryBus::class, $bus);
    }

    public function testCreateEventBus(): void
    {
        $bus = BusFactory::createEventBus($this->container);

        $this->assertInstanceOf(EventBus::class, $bus);
    }
}