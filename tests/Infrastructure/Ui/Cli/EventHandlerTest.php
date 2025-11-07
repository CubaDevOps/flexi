<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Ui\Cli;

use CubaDevOps\Flexi\Domain\Events\Event;
use CubaDevOps\Flexi\Infrastructure\Ui\Cli\CliInput;
use CubaDevOps\Flexi\Infrastructure\Ui\Cli\EventHandler;
use Flexi\Contracts\Interfaces\EventBusInterface;
use PHPUnit\Framework\TestCase;

class EventHandlerTest extends TestCase
{
    public function testConstructorAssignsEventBus(): void
    {
        $eventBusMock = $this->createMock(EventBusInterface::class);
        $handler = new EventHandler($eventBusMock);

        $this->assertInstanceOf(EventHandler::class, $handler);
    }

    public function testHandleWithHelpFlag(): void
    {
        $eventBusMock = $this->createMock(EventBusInterface::class);
        $handler = new EventHandler($eventBusMock);

        $input = new CliInput('any-event', [], 'event', true);

        $result = $handler->handle($input);

        $this->assertIsString($result);
        $this->assertStringContainsString('Usage:', $result);
        $this->assertStringContainsString('--event', $result);
        $this->assertStringContainsString('trigger|listeners', $result);
    }

    public function testHandleWithTriggerCommand(): void
    {
        $eventBusMock = $this->createMock(EventBusInterface::class);
        $eventBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(Event::class));

        $handler = new EventHandler($eventBusMock);

        $arguments = [
            'name' => 'test-event',
            'fired_by' => 'cli-test',
            'data' => '{"key": "value", "test": true}'
        ];
        $input = new CliInput('trigger', $arguments, 'event', false);

        $result = $handler->handle($input);

        $this->assertIsString($result);
        $this->assertStringContainsString('Event "test-event" triggered:', $result);
    }

    public function testHandleWithTriggerCommandDefaultValues(): void
    {
        $eventBusMock = $this->createMock(EventBusInterface::class);
        $eventBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(Event::class));

        $handler = new EventHandler($eventBusMock);

        $arguments = ['name' => 'simple-event'];
        $input = new CliInput('trigger', $arguments, 'event', false);

        $result = $handler->handle($input);

        $this->assertIsString($result);
        $this->assertStringContainsString('Event "simple-event" triggered:', $result);
    }

    public function testHandleWithListenersCommand(): void
    {
        $eventBusMock = $this->createMock(EventBusInterface::class);
        $eventBusMock->expects($this->once())
            ->method('getListeners')
            ->with('test-event')
            ->willReturn(['Listener1', 'Listener2', 'Listener3']);

        $handler = new EventHandler($eventBusMock);

        $arguments = ['name' => 'test-event'];
        $input = new CliInput('listeners', $arguments, 'event', false);

        $result = $handler->handle($input);

        $this->assertEquals("Listener1\nListener2\nListener3", $result);
    }

    public function testHandleWithListenersCommandNoListeners(): void
    {
        $eventBusMock = $this->createMock(EventBusInterface::class);
        $eventBusMock->expects($this->once())
            ->method('getListeners')
            ->with('no-listeners-event')
            ->willReturn([]);

        $handler = new EventHandler($eventBusMock);

        $arguments = ['name' => 'no-listeners-event'];
        $input = new CliInput('listeners', $arguments, 'event', false);

        $result = $handler->handle($input);

        $this->assertEquals('', $result);
    }

    public function testHandleWithUnknownCommand(): void
    {
        $eventBusMock = $this->createMock(EventBusInterface::class);
        $eventBusMock->expects($this->never())->method('dispatch');
        $eventBusMock->expects($this->never())->method('getListeners');

        $handler = new EventHandler($eventBusMock);

        $input = new CliInput('unknown-command', [], 'event', false);

        $result = $handler->handle($input);

        $this->assertEquals('unknown-command command related to events not found', $result);
    }

    public function testHandleWithComplexJSONData(): void
    {
        $eventBusMock = $this->createMock(EventBusInterface::class);
        $eventBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(Event::class));

        $handler = new EventHandler($eventBusMock);

        $complexData = json_encode([
            'nested' => ['level2' => 'deep'],
            'array' => ['item1', 'item2'],
            'number' => 42
        ]);

        $arguments = [
            'name' => 'complex-event',
            'data' => $complexData
        ];
        $input = new CliInput('trigger', $arguments, 'event', false);

        $result = $handler->handle($input);

        $this->assertStringContainsString('Event "complex-event" triggered:', $result);
    }
}