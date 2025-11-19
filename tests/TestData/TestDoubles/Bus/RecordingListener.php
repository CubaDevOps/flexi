<?php

declare(strict_types=1);

namespace Flexi\Test\TestData\TestDoubles\Bus;

use Flexi\Contracts\Interfaces\DTOInterface;
use Flexi\Contracts\Interfaces\EventInterface;
use Flexi\Contracts\Interfaces\EventListenerInterface;

class RecordingListener implements EventListenerInterface
{
    /** @var EventInterface[] */
    public array $received = [];

    /** @var callable|null */
    private $callback;

    public function __construct(?callable $callback = null)
    {
        $this->callback = $callback;
    }

    public function handle(DTOInterface $dto)
    {
        if ($dto instanceof EventInterface) {
            $this->handleEvent($dto);
        }
    }

    public function handleEvent(EventInterface $event)
    {
        $this->received[] = $event;

        if ($this->callback) {
            ($this->callback)($event);
        }
    }
}
