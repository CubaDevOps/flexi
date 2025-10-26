<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Events;

use CubaDevOps\Flexi\Contracts\DTOContract;
use CubaDevOps\Flexi\Contracts\EventContract;
use CubaDevOps\Flexi\Contracts\EventListenerContract;

abstract class EventListener implements EventListenerContract
{
    public function handle(DTOContract $dto): void
    {
        if ($dto instanceof EventContract) {
            $this->handleEvent($dto);
        }
    }
}
