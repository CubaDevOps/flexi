<?php

namespace CubaDevOps\Flexi\Test\Application\UseCase;

use CubaDevOps\Flexi\Application\UseCase\ListQueries;
use CubaDevOps\Flexi\Domain\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Domain\Classes\QueryBus;
use CubaDevOps\Flexi\Domain\DTO\QueryListDTO;
use PHPUnit\Framework\TestCase;

class ListQueriesTest extends TestCase
{
    private QueryBus $queryBus;
    private ListQueries $listQueries;

    public function setUp(): void
    {
        $this->queryBus = $this->createMock(QueryBus::class);

        $this->listQueries = new ListQueries($this->queryBus);
    }

    public function testHandleEvent(): void
    {
        $dto = $this->createMock(QueryListDTO::class);

        $dto->expects($this->once())
            ->method('withAliases')->willReturn(true);

        $this->queryBus->expects($this->once())
            ->method('getHandlersDefinition')
            ->willReturn(['query1', 'query1']);

        $message = $this->listQueries->handle($dto);
        $this->assertInstanceOf(PlainTextMessage::class, $message);
    }
}
