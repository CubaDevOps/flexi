<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\DevTools\Application\UseCase;

use CubaDevOps\Flexi\Contracts\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Contracts\DTOContract;
use CubaDevOps\Flexi\Contracts\HandlerContract;
use CubaDevOps\Flexi\Contracts\MessageContract;
use CubaDevOps\Flexi\Contracts\BusContract;
use CubaDevOps\Flexi\Modules\DevTools\Application\Queries\ListQueriesQuery;

class ListQueries implements HandlerContract
{
    private BusContract $queryBus;

    public function __construct(BusContract $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    /**
     * @param ListQueriesQuery $dto
     *
     * @throws \JsonException
     */
    public function handle(DTOContract $dto): MessageContract
    {
        $list = json_encode($this->queryBus->getHandlersDefinition($dto->withAliases()), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        return new PlainTextMessage($list);
    }
}
