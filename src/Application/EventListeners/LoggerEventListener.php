<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\EventListeners;

use CubaDevOps\Flexi\Contracts\Classes\EventListener;
use CubaDevOps\Flexi\Contracts\Interfaces\EventInterface;
use CubaDevOps\Flexi\Contracts\ValueObjects\LogLevel;
use Psr\Log\LoggerInterface;

class LoggerEventListener extends EventListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handleEvent(EventInterface $event): void
    {
        $message = sprintf(
            'Event %s was triggered from %s',
            $event->getName(),
            $event->firedBy()
        );
        $this->logger->log(LogLevel::INFO, $message, $event->toArray());
    }
}
