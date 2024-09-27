<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

use Psr\EventDispatcher\EventDispatcherInterface;

interface EventBusInterface extends BusInterface, EventDispatcherInterface
{
    public function getListeners(string $event): array;
}
