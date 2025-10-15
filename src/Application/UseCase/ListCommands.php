<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\UseCase;

use CubaDevOps\Flexi\Infrastructure\Bus\CommandBus;
use CubaDevOps\Flexi\Domain\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Domain\DTO\CommandListDTO;
use CubaDevOps\Flexi\Domain\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Domain\Interfaces\HandlerInterface;
use CubaDevOps\Flexi\Domain\Interfaces\MessageInterface;

class ListCommands implements HandlerInterface
{
    private CommandBus $command_bus;

    public function __construct(CommandBus $command_bus)
    {
        $this->command_bus = $command_bus;
    }

    /**
     * @param CommandListDTO $dto
     *
     * @return MessageInterface
     *
     * @throws \JsonException
     */
    public function handle(DTOInterface $dto)
    {
        $list = json_encode($this->command_bus->getHandlersDefinition($dto->withAliases()), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        return new PlainTextMessage($list);
    }
}
