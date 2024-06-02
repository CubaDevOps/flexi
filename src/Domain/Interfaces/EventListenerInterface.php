<?php

namespace CubaDevOps\Flexi\Domain\Interfaces;

interface EventListenerInterface extends HandlerInterface
{
    public function handleEvent(EventInterface $event);
}
