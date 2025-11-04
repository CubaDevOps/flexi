<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Bus;

use Flexi\Contracts\Classes\PlainTextMessage;
use Flexi\Contracts\Interfaces\EventBusInterface;
use Flexi\Contracts\Interfaces\ObjectBuilderInterface;
use CubaDevOps\Flexi\Test\TestData\Commands\TestCommand;
use CubaDevOps\Flexi\Test\TestData\Handlers\TestCommandHandler;
use CubaDevOps\Flexi\Infrastructure\Bus\CommandBus;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class CommandBusTest extends TestCase
{
    private CommandBus $commandBus;
    private ContainerInterface $container;
    private EventBusInterface $event_bus;
    private ObjectBuilderInterface $class_factory;

    /**
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     */
    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->event_bus = $this->createMock(EventBusInterface::class);
        $this->class_factory = $this->createMock(ObjectBuilderInterface::class);

        $this->commandBus = new CommandBus($this->container, $this->event_bus, $this->class_factory);

        $this->commandBus->loadHandlersFromJsonFile('./tests/TestData/Configurations/commands-bus-test.json');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws \ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function testExecute(): void
    {
        $handlerMock = $this->createMock(TestCommandHandler::class);

        $this->event_bus
            ->expects($this->exactly(2))
            ->method('dispatch')->willReturnSelf();

        $this->class_factory
            ->expects($this->once())
            ->method('build')
            ->with($this->container, TestCommandHandler::class)
            ->willReturn($handlerMock);

        $handlerMock
            ->expects($this->once())
            ->method('handle')
            ->willReturn(new PlainTextMessage('message'));

        $message = $this->commandBus->execute(new TestCommand());

        $this->assertNotNull($message);
        $this->assertInstanceOf(PlainTextMessage::class, $message);
        $this->assertEquals('message', $message->get('body'));
    }

    public function testGetHandler(): void
    {
        $this->assertEquals(TestCommandHandler::class, $this->commandBus->getHandler(TestCommand::class));
    }

    public function testGetHandlerDoesNotExist(): void
    {
        $testHandler = 'NonExistentCommand';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Not handler found for $testHandler command");
        $this->commandBus->getHandler($testHandler);
    }

    public function testHasHandler(): void
    {
        $this->assertTrue($this->commandBus->hasHandler(TestCommand::class));
        $this->assertFalse($this->commandBus->hasHandler('NonExistentCommand'));
    }

    public function testGetHandlersDefinitions(): void
    {
        $expectedDefinitions = [
            TestCommand::class => TestCommandHandler::class,
            'test' => TestCommandHandler::class,
        ];

        $definitions = $this->commandBus->getHandlersDefinition(true);

        $this->assertNotEmpty($definitions);
        $this->assertEquals($expectedDefinitions, $definitions);
    }
}
