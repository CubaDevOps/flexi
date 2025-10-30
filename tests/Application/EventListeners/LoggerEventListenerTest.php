<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Application\EventListeners;

use CubaDevOps\Flexi\Modules\Logging\Application\EventListeners\LoggerEventListener;
use CubaDevOps\Flexi\Contracts\Interfaces\EventInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoggerEventListenerTest extends TestCase
{
    private LoggerInterface $logger;
    private LoggerEventListener $loggerListener;

    public function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->loggerListener = new LoggerEventListener($this->logger);
    }

    public function testHandleEvent(): void
    {
        $event = $this->createMock(EventInterface::class);

        $event->expects($this->once())
            ->method('getName')->willReturn('test event');

        $event->expects($this->once())
            ->method('firedBy')
            ->willReturn('trigger');

        $event->expects($this->once())
            ->method('toArray')
            ->willReturn([]);

        $this->logger->expects($this->once())
            ->method('log')
            ->willReturnSelf();

        $this->loggerListener->handleEvent($event);
    }
}
