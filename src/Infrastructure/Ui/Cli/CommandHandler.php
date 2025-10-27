<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Ui\Cli;

use CubaDevOps\Flexi\Contracts\Interfaces\CliDTOInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\BusInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\HandlerInterface;
use CubaDevOps\Flexi\Application\Services\DTOFactory;
use Psr\Container\ContainerInterface;
use CubaDevOps\Flexi\Infrastructure\Bus\CommandBus;
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
        if ($dto instanceof CliDTOInterface && $input->showHelp()) {
            return $dto->usage();
        }

        return (string) $this->commandBus->execute($dto);
    }
}
