<?php

namespace CubaDevOps\Flexi\Test\Infrastructure\Bus;

use CubaDevOps\Flexi\Application\UseCase\Health;
use CubaDevOps\Flexi\Application\UseCase\ListCommands;
use CubaDevOps\Flexi\Application\UseCase\ListQueries;
use CubaDevOps\Flexi\Domain\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Infrastructure\Bus\QueryBus;
use CubaDevOps\Flexi\Application\Commands\ListCommandsCommand;
use CubaDevOps\Flexi\Domain\DTO\DummyDTO;
use CubaDevOps\Flexi\Application\Queries\GetVersionQuery;
use CubaDevOps\Flexi\Application\Queries\ListQueriesQuery;
use CubaDevOps\Flexi\Domain\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Domain\Interfaces\ObjectBuilderInterface;
use CubaDevOps\Flexi\Modules\Home\Application\RenderHome;
use CubaDevOps\Flexi\Modules\Home\Domain\HomePageDTO;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class QueryBusTest extends TestCase
{
    private QueryBus $queryBus;
    private ContainerInterface $container;
    private EventBusInterface $event_bus;
    private ObjectBuilderInterface $class_factory;

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws \ReflectionException
     * @throws \JsonException
     */
    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->event_bus = $this->createMock(EventBusInterface::class);
        $this->class_factory = $this->createMock(ObjectBuilderInterface::class);

        $this->queryBus = new QueryBus($this->container, $this->event_bus, $this->class_factory);

        $this->queryBus->loadHandlersFromJsonFile('./src/Config/queries.json');
    }

    /**
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testExecute(): void
    {
        $handlerMock = $this->createMock(ListCommands::class);

        $this->event_bus
            ->expects($this->exactly(2))
            ->method('dispatch')->willReturnSelf();

        $this->class_factory
            ->expects($this->once())
            ->method('build')
            ->with($this->container, ListCommands::class)
            ->willReturn($handlerMock);

        $handlerMock
            ->expects($this->once())
            ->method('handle')
            ->willReturn(new PlainTextMessage('message'));

        $message = $this->queryBus->execute(new ListCommandsCommand());

        $this->assertNotNull($message);
        $this->assertInstanceOf(PlainTextMessage::class, $message);
        $this->assertEquals('message', $message->get('body'));
    }

    public function testGetHandler(): void
    {
        $this->assertEquals(Health::class, $this->queryBus->getHandler(GetVersionQuery::class));
        $this->assertEquals(ListQueries::class, $this->queryBus->getHandler(ListQueriesQuery::class));
        $this->assertEquals(ListCommands::class, $this->queryBus->getHandler(ListCommandsCommand::class));
        $this->assertEquals(RenderHome::class, $this->queryBus->getHandler(HomePageDTO::class));
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
        $this->assertTrue($this->queryBus->hasHandler(GetVersionQuery::class));
        $this->assertTrue($this->queryBus->hasHandler(ListQueriesQuery::class));
        $this->assertTrue($this->queryBus->hasHandler(ListCommandsCommand::class));

        $this->assertFalse($this->queryBus->hasHandler(DummyDTO::class));
    }

    public function testGetHandlersDefinitions(): void
    {
        $expectedDefinitions = [
            'version'                       => Health::class,
            'query:list'                    => ListQueries::class,
            'command:list'                  => ListCommands::class,
            HomePageDTO::class              => RenderHome::class,
            GetVersionQuery::class          => Health::class,
            ListQueriesQuery::class         => ListQueries::class,
            ListCommandsCommand::class      => ListCommands::class
        ];

        $definitions = $this->queryBus->getHandlersDefinition();

        $this->assertNotEmpty($definitions);
        $this->assertEquals($expectedDefinitions, $definitions);
    }
}
