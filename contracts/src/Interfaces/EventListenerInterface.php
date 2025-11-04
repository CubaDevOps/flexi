<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Interfaces;

interface EventListenerInterface extends HandlerInterface
{
    public function handleEvent(EventInterface $event);
}
