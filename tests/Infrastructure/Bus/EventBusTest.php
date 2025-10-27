<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Bus;

use CubaDevOps\Flexi\Application\EventListeners\LoggerEventListener;
use CubaDevOps\Flexi\Contracts\Interfaces\ConfigurationRepositoryInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\EventInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\LogRepositoryInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\ObjectBuilderInterface;
use CubaDevOps\Flexi\Infrastructure\Bus\EventBus;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

class EventBusTest extends TestCase
{
    private EventBus $eventBus;
    private $container;
    private $class_factory;

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws \ReflectionException
     * @throws \JsonException
     */
    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->class_factory = $this->createMock(ObjectBuilderInterface::class);

        $configRepo = $this->createMock(ConfigurationRepositoryInterface::class);
        $logRepository = $this->createMock(LogRepositoryInterface::class);

        // Create a simpler logger for testing
        $logger = $this->createMock(LoggerInterface::class);

        $this->eventBus = new EventBus($this->container, $this->class_factory, $logger, $configRepo);

        $this->eventBus->loadHandlersFromJsonFile('./src/Config/listeners.json');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws \ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function testExecute(): void
    {
        $dtoMock = $this->createMock(EventInterface::class);
        $handlerMock = $this->createMock(LoggerEventListener::class);

        $dtoMock->expects($this->once())->method('getName')->willReturn('*');
        $dtoMock->expects($this->atLeastOnce())->method('isPropagationStopped')->willReturn(false);

        $this->class_factory
            ->expects($this->atLeastOnce())
            ->method('build')
            ->with($this->container, LoggerEventListener::class)
            ->willReturn($handlerMock);

        $this->eventBus->execute($dtoMock);
    }

    /**
     * @throws \JsonException
     */
    public function testGetHandler(): void
    {
        $expected = json_encode([LoggerEventListener::class], JSON_THROW_ON_ERROR);
        $actual = $this->eventBus->getHandler('*');

        $this->assertEquals($expected, $actual);
    }

    public function testHasHandler(): void
    {
        $this->assertTrue($this->eventBus->hasHandler('*'));
    }
}
