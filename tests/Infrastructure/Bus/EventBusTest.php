<?php

namespace CubaDevOps\Flexi\Test\Infrastructure\Bus;

use CubaDevOps\Flexi\Application\EventListeners\LoggerEventListener;
use CubaDevOps\Flexi\Infrastructure\Bus\EventBus;
use CubaDevOps\Flexi\Infrastructure\Persistence\InFileLogRepository;
use CubaDevOps\Flexi\Domain\Interfaces\EventInterface;
use CubaDevOps\Flexi\Domain\Interfaces\ObjectBuilderInterface;
use CubaDevOps\Flexi\Domain\Interfaces\ConfigurationRepositoryInterface;
use CubaDevOps\Flexi\Domain\Interfaces\LogRepositoryInterface;
use CubaDevOps\Flexi\Infrastructure\Classes\PsrLogger;
use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
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
        $configuration = new Configuration($configRepo);

        $logger = new PsrLogger(
            $this->createMock(LogRepositoryInterface::class),
            $configuration
        );

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
