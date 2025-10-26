<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Utils;

use CubaDevOps\Flexi\Contracts\BusContract;
use CubaDevOps\Flexi\Contracts\DTOContract;
use CubaDevOps\Flexi\Domain\DTO\NotFoundCliCommand;

class DTOFactory
{
    public static function fromArray(BusContract $bus, string $id, array $data): DTOContract
    {
        if (!$bus->hasHandler($id)) {
            return new NotFoundCliCommand();
        }

        /** @var DTOContract $dto */
        $dto = class_exists($id) ? $id : $bus->getDtoClassFromAlias($id);

        return $dto::fromArray($data);
    }
}
