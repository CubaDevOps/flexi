<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Factories;

use Flexi\Contracts\Interfaces\ConfigurationRepositoryInterface;
use Flexi\Contracts\Interfaces\ObjectBuilderInterface;
use CubaDevOps\Flexi\Infrastructure\Bus\CommandBus;
use CubaDevOps\Flexi\Infrastructure\Bus\EventBus;
use CubaDevOps\Flexi\Infrastructure\Bus\QueryBus;
use CubaDevOps\Flexi\Infrastructure\Factories\BusFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class BusFactoryTest extends TestCase
{
    private ContainerInterface $container;
    private ObjectBuilderInterface $objectBuilder;
    private ConfigurationRepositoryInterface $configRepo;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->objectBuilder = $this->createMock(ObjectBuilderInterface::class);
        $this->configRepo = $this->createMock(ConfigurationRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Setup basic container returns
        $this->container->method('get')->willReturnMap([
            ['logger', $this->logger],
            [ObjectBuilderInterface::class, $this->objectBuilder],
            [ConfigurationRepositoryInterface::class, $this->configRepo],
        ]);
    }

    public function testConstruct(): void
    {
        $factory = new BusFactory($this->container);
        $this->assertInstanceOf(BusFactory::class, $factory);
    }

    public function testGetInstanceCommandBus(): void
    {
        $factory = new BusFactory($this->container);

        $bus = $factory->getInstance(CommandBus::class, 'tests/TestData/Configurations/commands-bus-test.json');

        $this->assertInstanceOf(CommandBus::class, $bus);
    }

    public function testGetInstanceQueryBus(): void
    {
        $factory = new BusFactory($this->container);

        $bus = $factory->getInstance(QueryBus::class, 'tests/TestData/Configurations/queries-bus-core-test.json');

        $this->assertInstanceOf(QueryBus::class, $bus);
    }

    public function testGetInstanceEventBus(): void
    {
        $factory = new BusFactory($this->container);

        $bus = $factory->getInstance(EventBus::class, 'tests/TestData/Configurations/events-bus-test.json');

        $this->assertInstanceOf(EventBus::class, $bus);
    }

    public function testGetInstanceInvalidType(): void
    {
        $factory = new BusFactory($this->container);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid bus type');

        $factory->getInstance('InvalidBusType', '');
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

        $factory = new BusFactory($containerMock);

        // Should use NullLogger and still work
        $bus = $factory->getInstance(CommandBus::class, 'tests/TestData/Configurations/commands-bus-test.json');

        $this->assertInstanceOf(CommandBus::class, $bus);
    }

    public function testCreateCommandBus(): void
    {
        $bus = BusFactory::createCommandBus($this->container, 'tests/TestData/Configurations/commands-bus-test.json');

        $this->assertInstanceOf(CommandBus::class, $bus);
    }

    public function testCreateQueryBus(): void
    {
        $bus = BusFactory::createQueryBus($this->container, 'tests/TestData/Configurations/queries-bus-core-test.json');

        $this->assertInstanceOf(QueryBus::class, $bus);
    }

    public function testCreateEventBus(): void
    {
        $bus = BusFactory::createEventBus($this->container, 'tests/TestData/Configurations/events-bus-test.json');

        $this->assertInstanceOf(EventBus::class, $bus);
    }
}