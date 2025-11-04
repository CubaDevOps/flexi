<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Ui\Cli;

use Flexi\Contracts\Interfaces\BusInterface;
use Flexi\Contracts\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Application\Commands\NotFoundCommand;

class DTOFactory
{
    public static function fromArray(BusInterface $bus, string $id, array $data): DTOInterface
    {
        if (!$bus->hasHandler($id)) {
            return new NotFoundCommand();
        }

        /** @var DTOInterface $dto */
        $dto = class_exists($id) ? $id : $bus->getDtoClassFromAlias($id);

        return $dto::fromArray($data);
    }
}
