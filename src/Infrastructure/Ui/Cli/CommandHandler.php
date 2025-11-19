<?php

declare(strict_types=1);

namespace Flexi\Infrastructure\Ui\Cli;

use Flexi\Domain\Commands\NotFoundCommand;
use Flexi\Contracts\Interfaces\CliDTOInterface;
use Flexi\Contracts\Interfaces\BusInterface;
use Flexi\Contracts\Interfaces\HandlerInterface;
use Psr\Container\ContainerInterface;
use Flexi\Infrastructure\Bus\CommandBus;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CommandHandler
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(CliInput $input): string
    {
        $dto = DTOFactory::fromArray($this->commandBus, $input->getCommandName(), $input->getArguments());
        if($dto instanceof NotFoundCommand) {
            return $dto->__toString();
        }
        if ($dto instanceof CliDTOInterface && $input->showHelp()) {
            return $dto->usage();
        }

        return (string) $this->commandBus->execute($dto);
    }
}
