<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Bus;

use CubaDevOps\Flexi\Contracts\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Contracts\EventBusContract;
use CubaDevOps\Flexi\Contracts\ObjectBuilderContract;
use CubaDevOps\Flexi\Domain\DTO\DummyDTO;
use CubaDevOps\Flexi\Infrastructure\Bus\QueryBus;
use CubaDevOps\Flexi\Modules\DevTools\Application\Queries\ListQueriesQuery;
use CubaDevOps\Flexi\Modules\DevTools\Application\UseCase\ListQueries;
use CubaDevOps\Flexi\Modules\HealthCheck\Application\Queries\GetVersionQuery;
use CubaDevOps\Flexi\Modules\HealthCheck\Application\UseCase\Health;
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
        $this->event_bus = $this->createMock(EventBusContract::class);
        $this->class_factory = $this->createMock(ObjectBuilderContract::class);

        $this->queryBus = new QueryBus($this->container, $this->event_bus, $this->class_factory);

        $this->queryBus->loadHandlersFromJsonFile('./tests/TestData/Configurations/queries-bus-test.json');
    }

    /**
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testExecute(): void
    {
        $handlerMock = $this->createMock(ListQueries::class);

        $this->event_bus
            ->expects($this->exactly(2))
            ->method('dispatch')->willReturnSelf();

        $this->class_factory
            ->expects($this->once())
            ->method('build')
            ->with($this->container, ListQueries::class)
            ->willReturn($handlerMock);

        $handlerMock
            ->expects($this->once())
            ->method('handle')
            ->willReturn(new PlainTextMessage('message'));

        $message = $this->queryBus->execute(new ListQueriesQuery());

        $this->assertNotNull($message);
        $this->assertInstanceOf(PlainTextMessage::class, $message);
        $this->assertEquals('message', $message->get('body'));
    }

    public function testGetHandler(): void
    {
        $this->assertEquals(Health::class, $this->queryBus->getHandler(GetVersionQuery::class));
        $this->assertEquals(ListQueries::class, $this->queryBus->getHandler(ListQueriesQuery::class));
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

        $this->assertFalse($this->queryBus->hasHandler(DummyDTO::class));
    }

    public function testGetHandlersDefinitions(): void
    {
        $expectedDefinitions = [
            'version' => Health::class,
            'query:list' => ListQueries::class,
            GetVersionQuery::class => Health::class,
            ListQueriesQuery::class => ListQueries::class,
        ];

        $definitions = $this->queryBus->getHandlersDefinition();

        $this->assertNotEmpty($definitions);
        $this->assertEquals($expectedDefinitions, $definitions);
    }
}
