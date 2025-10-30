<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\DevTools\Application\UseCase;

use CubaDevOps\Flexi\Contracts\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Contracts\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\HandlerInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\MessageInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\BusInterface;
use CubaDevOps\Flexi\Modules\DevTools\Application\Queries\ListQueriesQuery;

class ListQueries implements HandlerInterface
{
    private BusInterface $queryBus;

    public function __construct(BusInterface $query_bus)
    {
        $this->queryBus = $query_bus;
    }

    /**
     * @param ListQueriesQuery $dto
     *
     * @throws \JsonException
     */
    public function handle(DTOInterface $dto): MessageInterface
    {
        $list = json_encode($this->queryBus->getHandlersDefinition($dto->withAliases()), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        return new PlainTextMessage($list);
    }
}
