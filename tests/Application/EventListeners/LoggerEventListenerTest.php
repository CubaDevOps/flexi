<?php

namespace CubaDevOps\Flexi\Test\Application\EventListeners;

use CubaDevOps\Flexi\Application\EventListeners\LoggerEventListener;
use CubaDevOps\Flexi\Contracts\EventContract;
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
        $event = $this->createMock(EventContract::class);
        $datetime = $this->createMock(\DateTimeImmutable::class);

        $event->expects($this->once())
            ->method('getName')->willReturn('test event');

        $event->expects($this->once())
            ->method('occurredOn')->willReturn($datetime);

        $datetime->expects($this->once())
            ->method('format')
            ->with(DATE_ATOM)
            ->willReturn('2024-01-01T00:00:00+00:00');

        $event->expects($this->once())
            ->method('firedBy')
            ->willReturn('trigger');

        $this->logger->expects($this->once())
            ->method('log')
            ->willReturnSelf();

        $this->loggerListener->handleEvent($event);
    }
}
