<?php

namespace CubaDevOps\Flexi\Modules\DevTools\Test\Application\UseCase;

use CubaDevOps\Flexi\Modules\DevTools\Application\UseCase\ListQueries;
use CubaDevOps\Flexi\Domain\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Infrastructure\Bus\QueryBus;
use CubaDevOps\Flexi\Modules\DevTools\Application\Queries\ListQueriesQuery;
use PHPUnit\Framework\TestCase;

class ListQueriesTest extends TestCase
{
    private $queryBus;
    private $listQueries;

    public function setUp(): void
    {
        $this->queryBus = $this->createMock(QueryBus::class);

        $this->listQueries = new ListQueries($this->queryBus);
    }

    public function testHandleEvent(): void
    {
        $dto = $this->createMock(ListQueriesQuery::class);
        $queries = ['query1', 'query1'];

        $dto->expects($this->once())
            ->method('withAliases')->willReturn(true);

        $this->queryBus->expects($this->once())
            ->method('getHandlersDefinition')
            ->willReturn($queries);

        $message = $this->listQueries->handle($dto);

        $this->assertEquals(
            $queries, json_decode((string)$message, true, 512, JSON_THROW_ON_ERROR)
        );

        $this->assertInstanceOf(PlainTextMessage::class, $message);
    }
}