<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Classes;

use CubaDevOps\Flexi\Domain\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Domain\Interfaces\EventInterface;
use CubaDevOps\Flexi\Domain\Interfaces\EventListenerInterface;

abstract class EventListener implements EventListenerInterface
{
    public function handle(DTOInterface $dto): void
    {
        if ($dto instanceof EventInterface) {
            $this->handleEvent($dto);
        }
    }
}
