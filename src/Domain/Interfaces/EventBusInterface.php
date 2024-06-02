<?php

namespace CubaDevOps\Flexi\Domain\Interfaces;

interface EventBusInterface extends BusInterface
{
    public function notify(EventInterface $dto);

    public function getListeners(string $event): array;
}
