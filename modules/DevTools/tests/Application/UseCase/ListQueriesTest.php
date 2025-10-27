<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\DevTools\Test\Application\UseCase;

use CubaDevOps\Flexi\Contracts\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Contracts\Interfaces\BusInterface;
use CubaDevOps\Flexi\Modules\DevTools\Application\Queries\ListQueriesQuery;
use CubaDevOps\Flexi\Modules\DevTools\Application\UseCase\ListQueries;
use PHPUnit\Framework\TestCase;

class ListQueriesTest extends TestCase
{
    private BusInterface $queryBus;
    private ListQueries $listQueries;

    public function setUp(): void
    {
        $this->queryBus = $this->createMock(BusInterface::class);
        $this->listQueries = new ListQueries($this->queryBus);
    }

    public function testHandleEvent(): void
    {
        $dto = $this->createMock(ListQueriesQuery::class);
        $queries = ['query1' => 'Handler1', 'query2' => 'Handler2'];

        $dto->expects($this->once())
            ->method('withAliases')->willReturn(true);

        $this->queryBus->expects($this->once())
            ->method('getHandlersDefinition')
            ->with(true)
            ->willReturn($queries);

        $message = $this->listQueries->handle($dto);

        $this->assertEquals(
            $queries, json_decode((string) $message, true, 512, JSON_THROW_ON_ERROR)
        );

        $this->assertInstanceOf(PlainTextMessage::class, $message);
    }
}
