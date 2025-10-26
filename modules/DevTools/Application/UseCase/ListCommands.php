<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\DevTools\Application\UseCase;

use CubaDevOps\Flexi\Contracts\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Contracts\DTOContract;
use CubaDevOps\Flexi\Contracts\HandlerContract;
use CubaDevOps\Flexi\Contracts\MessageContract;
use CubaDevOps\Flexi\Infrastructure\Bus\CommandBus;
use CubaDevOps\Flexi\Modules\DevTools\Application\Commands\ListCommandsCommand;

class ListCommands implements HandlerContract
{
    private CommandBus $command_bus;

    public function __construct(CommandBus $command_bus)
    {
        $this->command_bus = $command_bus;
    }

    /**
     * @param ListCommandsCommand $dto
     *
     * @throws \JsonException
     */
    public function handle(DTOContract $dto): MessageContract
    {
        $list = json_encode($this->command_bus->getHandlersDefinition($dto->withAliases()), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        return new PlainTextMessage($list);
    }
}
