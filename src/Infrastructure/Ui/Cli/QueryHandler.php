<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Ui\Cli;

use CubaDevOps\Flexi\Contracts\Interfaces\CliDTOInterface;
use CubaDevOps\Flexi\Infrastructure\Bus\QueryBus;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class QueryHandler
{
    private QueryBus $query_bus;

    public function __construct(QueryBus $query_bus)
    {
        $this->query_bus = $query_bus;
    }

    /**
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(CliInput $input): string
    {
        $dto = DTOFactory::fromArray($this->query_bus, $input->getCommandName(), $input->getArguments());
        if ($dto instanceof CliDTOInterface && $input->showHelp()) {
            return $dto->usage();
        }

        return (string) $this->query_bus->execute($dto);
    }
}
