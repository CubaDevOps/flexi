<?php

namespace CubaDevOps\Flexi\Test\Infrastructure\Commands;

use CubaDevOps\Flexi\Domain\Classes\Event;
use CubaDevOps\Flexi\Domain\Classes\EventBus;
use CubaDevOps\Flexi\Infrastructure\Commands\DispatchEventCommand;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DispatchEventCommandTest extends TestCase
{
    private EventBus $eventBusMock;
    private DispatchEventCommand $dispatchEventCommand;

    protected function setUp(): void
    {
        $this->eventBusMock = $this->createMock(EventBus::class);
        $this->dispatchEventCommand = new DispatchEventCommand($this->eventBusMock);
    }

    public function testExecuteSuccess(): void
    {
        $data = [
            'event' => 'test_event',
            'fired_by' => 'tester',
            'data' => ['key' => 'value'],
        ];

        $this->eventBusMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(Event::class));

        $this->dispatchEventCommand->execute($data);
        $this->assertTrue(true, 'Dispatch executed successfully.');
    }

    public function testExecuteValidationError(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The event field is required and must be a string.');

        $data = [
            'event' => null,
            'fired_by' => 'tester',
            'data' => ['key' => 'value'],
        ];

        $this->dispatchEventCommand->execute($data);
    }

    public function testExecuteInvalidFiredBy(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The fired_by field is required and must be a string.');

        $data = [
            'event' => 'test_event',
            'fired_by' => null,
            'data' => ['key' => 'value'],
        ];

        $this->dispatchEventCommand->execute($data);
    }

    public function testExecuteMissingDataField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The data field is required and must be a valid JSON string.');

        $data = [
            'event' => 'test_event',
            'fired_by' => 'tester',
        ];

        $this->dispatchEventCommand->execute($data);
    }

    public function testExecuteDispatchFailure(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Dispatch failed');

        $data = [
            'event' => 'test_event',
            'fired_by' => 'tester',
            'data' => ['key' => 'value'],
        ];

        $this->eventBusMock
            ->method('dispatch')
            ->willThrowException(new \Exception('Dispatch failed'));

        $this->dispatchEventCommand->execute($data);
    }
}
