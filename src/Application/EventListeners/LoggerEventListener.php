<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\EventListeners;

use CubaDevOps\Flexi\Domain\Classes\EventListener;
use CubaDevOps\Flexi\Domain\Interfaces\EventInterface;
use CubaDevOps\Flexi\Domain\ValueObjects\LogLevel;
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
            'Event %s was triggered at %s from %s.',
            $event->getName(),
            $event->occurredOn()->format(DATE_ATOM),
            $event->firedBy()
        );
        $this->logger->log(LogLevel::INFO, $message, [__CLASS__]);
    }
}
