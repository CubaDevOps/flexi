<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Bus;

use Flexi\Contracts\Interfaces\ConfigurationRepositoryInterface;
use Flexi\Contracts\Interfaces\EventInterface;
use Flexi\Contracts\Interfaces\EventListenerInterface;
use Flexi\Contracts\Interfaces\ObjectBuilderInterface;
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
    private const TEST_LISTENER_CLASS = 'TestEventListener';

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

        // Create a simpler logger for testing
        $logger = $this->createMock(LoggerInterface::class);

        $this->eventBus = new EventBus($this->container, $this->class_factory, $logger, $configRepo);

        // Instead of loading from actual files, manually register a test listener
        $this->eventBus->register('test.event', self::TEST_LISTENER_CLASS);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws \ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function testExecute(): void
    {
        $dtoMock = $this->createMock(EventInterface::class);
        $handlerMock = $this->createMock(EventListenerInterface::class);

        $dtoMock->expects($this->once())->method('getName')->willReturn('test.event');
        $dtoMock->expects($this->atLeastOnce())->method('isPropagationStopped')->willReturn(false);

        $this->class_factory
            ->expects($this->atLeastOnce())
            ->method('build')
            ->with($this->container, self::TEST_LISTENER_CLASS)
            ->willReturn($handlerMock);

        $this->eventBus->execute($dtoMock);
    }

    /**
     * @throws \JsonException
     */
    public function testGetHandler(): void
    {
        $expected = json_encode([self::TEST_LISTENER_CLASS], JSON_THROW_ON_ERROR);
        $actual = $this->eventBus->getHandler('test.event');

        $this->assertEquals($expected, $actual);
    }

    public function testHasHandler(): void
    {
        $this->assertTrue($this->eventBus->hasHandler('test.event'));
    }

    public function testHasNoHandler(): void
    {
        $this->assertFalse($this->eventBus->hasHandler('non.existent.event'));
    }

    public function testGetListenersWithExistingEvent(): void
    {
        $listeners = $this->eventBus->getListeners('test.event');
        $this->assertEquals([self::TEST_LISTENER_CLASS], $listeners);
    }

    public function testGetListenersWithNonExistentEvent(): void
    {
        $listeners = $this->eventBus->getListeners('non.existent.event');
        $this->assertEquals([], $listeners);
    }

    public function testRegisterMultipleListenersForSameEvent(): void
    {
        $this->eventBus->register('multi.event', 'FirstListener');
        $this->eventBus->register('multi.event', 'SecondListener');

        $listeners = $this->eventBus->getListeners('multi.event');
        $this->assertEquals(['FirstListener', 'SecondListener'], $listeners);
    }

    public function testDispatchWithNoListeners(): void
    {
        $eventMock = $this->createMock(EventInterface::class);
        $eventMock->method('getName')->willReturn('no.listeners.event');

        $result = $this->eventBus->dispatch($eventMock);
        $this->assertSame($eventMock, $result);
    }

    public function testDispatchWithStoppedPropagation(): void
    {
        $eventMock = $this->createMock(EventInterface::class);
        $eventMock->method('getName')->willReturn('test.event');
        $eventMock->method('isPropagationStopped')->willReturn(true);

        $this->class_factory->expects($this->never())->method('build');

        $result = $this->eventBus->dispatch($eventMock);
        $this->assertSame($eventMock, $result);
    }

    public function testDispatchWithWildcardListeners(): void
    {
        // Register wildcard listener
        $this->eventBus->register('*', 'WildcardListener');

        $eventMock = $this->createMock(EventInterface::class);
        $eventMock->method('getName')->willReturn('any.event');
        $eventMock->method('isPropagationStopped')->willReturn(false);

        $handlerMock = $this->createMock(EventListenerInterface::class);
        $handlerMock->expects($this->once())->method('handle')->with($eventMock);

        $this->class_factory->expects($this->once())
            ->method('build')
            ->with($this->container, 'WildcardListener')
            ->willReturn($handlerMock);

        $this->eventBus->dispatch($eventMock);
    }

    public function testBuildDefinitionWithMultipleListeners(): void
    {
        $listeners = ['FirstListener', 'SecondListener', 'ThirdListener'];
        $this->eventBus->buildDefinition('build.test.event', $listeners);

        $registeredListeners = $this->eventBus->getListeners('build.test.event');
        $this->assertEquals($listeners, $registeredListeners);
    }

    public function testGetDtoClassFromAlias(): void
    {
        $result = $this->eventBus->getDtoClassFromAlias('any.alias');
        $this->assertEquals(\CubaDevOps\Flexi\Application\Commands\NotFoundCommand::class, $result);
    }

    public function testGetHandlersDefinition(): void
    {
        $definition = $this->eventBus->getHandlersDefinition();

        $this->assertIsArray($definition);
        $this->assertArrayHasKey('test.event', $definition);
        $this->assertEquals([self::TEST_LISTENER_CLASS], $definition['test.event']);
    }

    public function testGetHandlersDefinitionWithAliases(): void
    {
        $definition = $this->eventBus->getHandlersDefinition(true);

        $this->assertIsArray($definition);
        // Should return same result as false since EventBus doesn't use aliases differently
        $this->assertArrayHasKey('test.event', $definition);
    }

    public function testAsyncModeDetection(): void
    {
        // Test async mode detection logic
        $result = $this->eventBus->asyncMode();
        $this->assertIsBool($result);
    }

    public function testExecuteWithEventInterface(): void
    {
        $eventMock = $this->createMock(EventInterface::class);
        $eventMock->method('getName')->willReturn('test.event');
        $eventMock->method('isPropagationStopped')->willReturn(false);

        $handlerMock = $this->createMock(EventListenerInterface::class);
        $handlerMock->expects($this->once())->method('handle');

        $this->class_factory->expects($this->once())
            ->method('build')
            ->willReturn($handlerMock);

        $this->eventBus->execute($eventMock);
    }

    public function testExecuteWithNonEventInterface(): void
    {
        $dtoMock = $this->createMock(\Flexi\Contracts\Interfaces\DTOInterface::class);

        // Should not call dispatch since it's not an EventInterface
        $this->class_factory->expects($this->never())->method('build');

        $this->eventBus->execute($dtoMock);
    }
}
