<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\Services;

use CubaDevOps\Flexi\Contracts\BusContract;
use CubaDevOps\Flexi\Contracts\DTOContract;
use CubaDevOps\Flexi\Application\Commands\NotFoundCommand;

class DTOFactory
{
    public static function fromArray(BusContract $bus, string $id, array $data): DTOContract
    {
        if (!$bus->hasHandler($id)) {
            return new NotFoundCommand();
        }

        /** @var DTOContract $dto */
        $dto = class_exists($id) ? $id : $bus->getDtoClassFromAlias($id);

        return $dto::fromArray($data);
    }
}