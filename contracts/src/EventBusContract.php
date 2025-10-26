<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts;

use Psr\EventDispatcher\EventDispatcherInterface;

interface EventBusContract extends BusContract, EventDispatcherInterface
{
    public function getListeners(string $event): array;
}
