<?php

namespace CubaDevOps\Flexi\Test\Domain\Classes;

use CubaDevOps\Flexi\Application\UseCase\Health;
use CubaDevOps\Flexi\Application\UseCase\ListCommands;
use CubaDevOps\Flexi\Application\UseCase\ListQueries;
use CubaDevOps\Flexi\Domain\Classes\CommandBus;
use CubaDevOps\Flexi\Domain\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Domain\DTO\CommandListDTO;
use CubaDevOps\Flexi\Domain\DTO\DummyDTO;
use CubaDevOps\Flexi\Domain\DTO\EmptyVersionDTO;
use CubaDevOps\Flexi\Domain\DTO\QueryListDTO;
use CubaDevOps\Flexi\Domain\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Domain\Utils\ClassFactory;
use CubaDevOps\Flexi\Modules\Home\Application\RenderHome;
use CubaDevOps\Flexi\Modules\Home\Domain\HomePageDTO;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class CommandBusTest extends TestCase
{
    private CommandBus $commandBus;
    private ContainerInterface $container;
    private EventBusInterface $event_bus;
    private ClassFactory $class_factory;

    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->event_bus = $this->createMock(EventBusInterface::class);
        $this->class_factory = $this->createMock(ClassFactory::class);

        $this->commandBus = new CommandBus($this->container, $this->event_bus, $this->class_factory);

        $this->commandBus->loadHandlersFromJsonFile(dirname(__DIR__, 3) .'/src/Config/commands.json');
        $this->commandBus->loadHandlersFromJsonFile(dirname(__DIR__, 3) .'/src/Config/queries.json');
    }

    public function testExecute(): void
    {
        $handlerMock = $this->createMock(ListCommands::class);

        $this->event_bus
            ->expects($this->exactly(2))
            ->method('notify')->willReturnSelf();

        $this->class_factory
            ->expects($this->once())
            ->method('build')
            ->with($this->container, ListCommands::class)
            ->willReturn($handlerMock);

        $handlerMock
            ->expects($this->once())
            ->method('handle')
            ->willReturn($this->createMock(PlainTextMessage::class));

        $message = $this->commandBus->execute(new CommandListDTO());

        $this->assertNotNull($message);
        $this->assertInstanceOf(PlainTextMessage::class, $message);
    }

    public function testGetHandler(): void
    {
        $this->assertEquals(Health::class, $this->commandBus->getHandler(EmptyVersionDTO::class));
        $this->assertEquals(ListQueries::class, $this->commandBus->getHandler(QueryListDTO::class));
        $this->assertEquals(ListCommands::class, $this->commandBus->getHandler(CommandListDTO::class));
        $this->assertEquals(RenderHome::class, $this->commandBus->getHandler(HomePageDTO::class));
    }

    public function testGetHandlerDoesNotExist(): void
    {
        $testHandler = DummyDTO::class;
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Not handler found for $testHandler command");
        $this->commandBus->getHandler($testHandler);
    }

    public function testHasHandler(): void
    {
        $this->assertTrue($this->commandBus->hasHandler(EmptyVersionDTO::class));
        $this->assertTrue($this->commandBus->hasHandler(QueryListDTO::class));
        $this->assertTrue($this->commandBus->hasHandler(CommandListDTO::class));

        $this->assertFalse($this->commandBus->hasHandler(DummyDTO::class));
    }

    public function testGetHandlersDefinitions(): void
    {
        $expectedDefinitions = [
            EmptyVersionDTO::class  => Health::class,
            QueryListDTO::class     => ListQueries::class,
            CommandListDTO::class   => ListCommands::class,
            'version'               => Health::class,
            'query:list'            => ListQueries::class,
            'command:list'          => ListCommands::class,
            HomePageDTO::class      => RenderHome::class,
        ];

        $definitions = $this->commandBus->getHandlersDefinition(true);

        $this->assertNotEmpty($definitions);
        $this->assertEquals($expectedDefinitions, $definitions);
    }
}
