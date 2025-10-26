<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Application\EventListeners;

use CubaDevOps\Flexi\Contracts\EventContract;
use CubaDevOps\Flexi\Contracts\ValueObjects\LogLevel;
use CubaDevOps\Flexi\Domain\Events\EventListener;
use Psr\Log\LoggerInterface;

class LoggerEventListener extends EventListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handleEvent(EventContract $event): void
    {
        $message = sprintf(
            'Event %s was triggered at %s from %s',
            $event->getName(),
            $event->occurredOn()->format(DATE_ATOM),
            $event->firedBy()
        );
        $this->logger->log(LogLevel::INFO, $message, [__CLASS__]);
    }
}
