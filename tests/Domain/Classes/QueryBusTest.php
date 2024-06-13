<?php

namespace CubaDevOps\Flexi\Test\Domain\Classes;

use CubaDevOps\Flexi\Application\UseCase\Health;
use CubaDevOps\Flexi\Application\UseCase\ListCommands;
use CubaDevOps\Flexi\Application\UseCase\ListQueries;
use CubaDevOps\Flexi\Domain\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Domain\Classes\QueryBus;
use CubaDevOps\Flexi\Domain\DTO\CommandListDTO;
use CubaDevOps\Flexi\Domain\DTO\DummyDTO;
use CubaDevOps\Flexi\Domain\DTO\EmptyVersionDTO;
use CubaDevOps\Flexi\Domain\DTO\QueryListDTO;
use CubaDevOps\Flexi\Domain\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Domain\Utils\ClassFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class QueryBusTest extends TestCase
{
    private QueryBus $queryBus;
    private ContainerInterface $container;
    private EventBusInterface $event_bus;
    private ClassFactory $class_factory;

    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->event_bus = $this->createMock(EventBusInterface::class);
        $this->class_factory = $this->createMock(ClassFactory::class);

        $this->queryBus = new QueryBus($this->container, $this->event_bus, $this->class_factory);

        $this->queryBus->loadHandlersFromJsonFile(dirname(__DIR__, 3) .'/src/Config/queries.json');
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

        $message = $this->queryBus->execute(new CommandListDTO());

        $this->assertNotNull($message);
        $this->assertInstanceOf(PlainTextMessage::class, $message);
    }

    public function testGetHandler(): void
    {
        $this->assertEquals(Health::class, $this->queryBus->getHandler(EmptyVersionDTO::class));
        $this->assertEquals(ListQueries::class, $this->queryBus->getHandler(QueryListDTO::class));
        $this->assertEquals(ListCommands::class, $this->queryBus->getHandler(CommandListDTO::class));
    }

    public function testGetHandlerDoesNotExist(): void
    {
        $testHandler = DummyDTO::class;
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Not handler found for $testHandler command");
        $this->queryBus->getHandler($testHandler);
    }

    public function testHasHandler(): void
    {
        $this->assertTrue($this->queryBus->hasHandler(EmptyVersionDTO::class));
        $this->assertTrue($this->queryBus->hasHandler(QueryListDTO::class));
        $this->assertTrue($this->queryBus->hasHandler(CommandListDTO::class));

        $this->assertFalse($this->queryBus->hasHandler(DummyDTO::class));
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
        ];

        $definitions = $this->queryBus->getHandlersDefinition(true);

        $this->assertNotEmpty($definitions);
        $this->assertEquals($expectedDefinitions, $definitions);
    }
}
