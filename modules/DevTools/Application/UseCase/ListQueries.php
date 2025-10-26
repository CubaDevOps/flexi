<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\DevTools\Application\UseCase;

use CubaDevOps\Flexi\Domain\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Infrastructure\Bus\QueryBus;
use CubaDevOps\Flexi\Modules\DevTools\Application\Queries\ListQueriesQuery;
use CubaDevOps\Flexi\Contracts\DTOContract;
use CubaDevOps\Flexi\Contracts\HandlerContract;
use CubaDevOps\Flexi\Contracts\MessageContract;

class ListQueries implements HandlerContract
{
    private QueryBus $queryBus;

    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    /**
     * @param ListQueriesQuery $dto
     *
     * @return MessageContract
     *
     * @throws \JsonException
     */
    public function handle(DTOContract $dto): MessageContract
    {
        $list = json_encode($this->queryBus->getHandlersDefinition($dto->withAliases()), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        return new PlainTextMessage($list);
    }
}