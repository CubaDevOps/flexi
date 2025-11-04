<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Bus;

use Flexi\Contracts\Classes\PlainTextMessage;
use Flexi\Contracts\Interfaces\EventBusInterface;
use Flexi\Contracts\Interfaces\ObjectBuilderInterface;
use CubaDevOps\Flexi\Test\TestData\Queries\TestQuery;
use CubaDevOps\Flexi\Test\TestData\Handlers\TestQueryHandler;
use CubaDevOps\Flexi\Infrastructure\Bus\QueryBus;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class QueryBusTest extends TestCase
{
    private QueryBus $queryBus;
    private $container;
    private $event_bus;
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
        $this->event_bus = $this->createMock(EventBusInterface::class);
        $this->class_factory = $this->createMock(ObjectBuilderInterface::class);

        $this->queryBus = new QueryBus($this->container, $this->event_bus, $this->class_factory);

        $this->queryBus->loadHandlersFromJsonFile('./tests/TestData/Configurations/queries-bus-core-test.json');
    }

    /**
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testExecute(): void
    {
        $handlerMock = $this->createMock(TestQueryHandler::class);

        $this->event_bus
            ->expects($this->exactly(2))
            ->method('dispatch')->willReturnSelf();

        $this->class_factory
            ->expects($this->once())
            ->method('build')
            ->with($this->container, TestQueryHandler::class)
            ->willReturn($handlerMock);

        $handlerMock
            ->expects($this->once())
            ->method('handle')
            ->willReturn(new PlainTextMessage('message'));

        $message = $this->queryBus->execute(new TestQuery());

        $this->assertNotNull($message);
        $this->assertInstanceOf(PlainTextMessage::class, $message);
        $this->assertEquals('message', $message->get('body'));
    }

    public function testGetHandler(): void
    {
        $this->assertEquals(TestQueryHandler::class, $this->queryBus->getHandler(TestQuery::class));
    }

    public function testGetHandlerDoesNotExist(): void
    {
        $testHandler = 'NonExistentQuery';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Not handler found for $testHandler command");
        $this->queryBus->getHandler($testHandler);
    }

    public function testHasHandler(): void
    {
        $this->assertTrue($this->queryBus->hasHandler(TestQuery::class));
        $this->assertFalse($this->queryBus->hasHandler('NonExistentQuery'));
    }

    public function testGetHandlersDefinitions(): void
    {
        $expectedDefinitions = [
            'test-query' => TestQueryHandler::class,
            TestQuery::class => TestQueryHandler::class,
        ];

        $definitions = $this->queryBus->getHandlersDefinition();

        $this->assertNotEmpty($definitions);
        $this->assertEquals($expectedDefinitions, $definitions);
    }
}
