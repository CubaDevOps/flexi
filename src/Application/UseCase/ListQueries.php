<?php

namespace CubaDevOps\Flexi\Application\UseCase;

use CubaDevOps\Flexi\Domain\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Domain\Classes\QueryBus;
use CubaDevOps\Flexi\Domain\DTO\CliDTO;
use CubaDevOps\Flexi\Domain\DTO\QueryListDTO;
use CubaDevOps\Flexi\Domain\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Domain\Interfaces\HandlerInterface;
use CubaDevOps\Flexi\Domain\Interfaces\MessageInterface;
use JsonException;

class ListQueries implements HandlerInterface
{

    private QueryBus $queryBus;

    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    /**
     * @param QueryListDTO $dto
     * @return MessageInterface
     * @throws JsonException
     */
    public function handle(DTOInterface $dto)
    {
        $list = json_encode($this->queryBus->getHandlersDefinition($dto->withAliases()), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        return new PlainTextMessage($list);
    }
}