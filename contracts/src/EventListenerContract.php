<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts;

interface EventListenerContract extends HandlerContract
{
    public function handleEvent(EventContract $event);
}
