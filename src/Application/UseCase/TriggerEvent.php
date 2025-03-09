<?php

namespace CubaDevOps\Flexi\Application\UseCase;

use CubaDevOps\Flexi\Domain\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Domain\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Domain\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Domain\Interfaces\HandlerInterface;
use CubaDevOps\Flexi\Domain\Interfaces\MessageInterface;

class TriggerEvent implements HandlerInterface
{

    private EventBusInterface $event_bus;

    public function __construct(EventBusInterface $event_bus)
    {
        $this->event_bus = $event_bus;
    }

    /**
     * @inheritDoc
     */
    public function handle(DTOInterface $dto): MessageInterface
    {
        $this->event_bus->dispatch($dto);
        return new PlainTextMessage('Event triggered');
    }
}