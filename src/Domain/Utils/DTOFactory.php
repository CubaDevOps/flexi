<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Utils;

use CubaDevOps\Flexi\Domain\DTO\NotFoundCliCommand;
use CubaDevOps\Flexi\Domain\Interfaces\BusInterface;
use CubaDevOps\Flexi\Domain\Interfaces\DTOInterface;

class DTOFactory
{
    public static function fromArray(BusInterface $bus, string $id, array $data): DTOInterface
    {
        if (!$bus->hasHandler($id)) {
            return new NotFoundCliCommand();
        }

        /** @var DTOInterface $dto */
        $dto = class_exists($id) ? $id : $bus->getDtoClassFromAlias($id);

        return $dto::fromArray($data);
    }
}
