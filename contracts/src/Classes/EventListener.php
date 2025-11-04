<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Classes;

use CubaDevOps\Flexi\Contracts\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\EventInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\EventListenerInterface;

abstract class EventListener implements EventListenerInterface
{
    public function handle(DTOInterface $dto): void
    {
        if ($dto instanceof EventInterface) {
            $this->handleEvent($dto);
        }
    }
}
