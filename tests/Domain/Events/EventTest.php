<?php

declare(strict_types=1);

namespace Flexi\Test\Domain\Events;

use Flexi\Domain\Events\Event;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    private const EVENT_NAME = 'eventName';
    private const EVENT_TRIGGER = 'eventTrigger';
    private const EVENT_DATA = [
        '1-tst-data' => [1, 2, 3],
        '2-tst-data' => 'event-data',
    ];

    private \DateTimeImmutable $now;
    private Event $event;

    public function setUp(): void
    {
        $this->event = new Event(
            self::EVENT_NAME,
            self::EVENT_TRIGGER,
            self::EVENT_DATA
        );

        $this->now = $this->event->occurredOn();
    }

    public function testGet(): void
    {
        $this->assertEquals(self::EVENT_NAME, $this->event->get('event'));
        $this->assertEquals(self::EVENT_TRIGGER, $this->event->get('fired_by'));
        $this->assertEquals(self::EVENT_DATA, $this->event->get('data'));
        $this->assertEquals($this->now->format(DATE_ATOM), $this->event->get('occurred_on'));
    }

    public function testGetEventName(): void
    {
        $this->assertNotEmpty($this->event->getName());
        $this->assertEquals(self::EVENT_NAME, $this->event->getName());
        $this->assertEquals(self::EVENT_NAME, $this->event->__toString());
    }

    public function testGetEventTrigger(): void
    {
        $this->assertNotEmpty($this->event->firedBy());
        $this->assertEquals(self::EVENT_TRIGGER, $this->event->firedBy());
    }

    public function testGetEventData(): void
    {
        $expected = [
            'event' => self::EVENT_NAME,
            'data' => self::EVENT_DATA,
            'fired_by' => self::EVENT_TRIGGER,
            'occurred_on' => $this->now->format(DATE_ATOM),
        ];

        $this->assertEquals($expected, $this->event->toArray());
    }

    public function testCreateEventFromArray(): void
    {
        $data = [
            'event' => self::EVENT_NAME,
            'data' => self::EVENT_DATA,
            'fired_by' => self::EVENT_TRIGGER,
        ];

        $newEvent = Event::fromArray($data);

        $this->assertNotNull($newEvent);
        $this->assertInstanceOf(Event::class, $newEvent);
        $this->assertEquals(self::EVENT_NAME, $newEvent->getName());
        $this->assertEquals(self::EVENT_TRIGGER, $newEvent->firedBy());
        $this->assertEquals(self::EVENT_DATA, $newEvent->get('data'));
    }

    public function testCreateEventFromArrayInvalidData(): void
    {
        $data = [
            'event' => self::EVENT_NAME,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid parameters provided for '.Event::class);

        Event::fromArray($data);
    }

    /**
     * @throws \JsonException
     */
    public function testSerialize(): void
    {
        $expected =
            '{"event":"'.self::EVENT_NAME.
            '","data":'.json_encode(self::EVENT_DATA, JSON_THROW_ON_ERROR).
            ',"fired_by":"'.self::EVENT_TRIGGER.
            '","occurred_on":"'.$this->now->format(DATE_ATOM).'"}';

        $serialized = $this->event->serialize();

        $this->assertNotEmpty($serialized);
        $this->assertEquals($expected, $serialized);
    }

    public function testSetAndGetData(): void
    {
        $this->event->set('custom_key', 'custom_value');

        $this->assertEquals('custom_value', $this->event->get('custom_key'));
    }

    public function testHasMethod(): void
    {
        // Test with existing keys from initial data
        $this->assertTrue($this->event->has('1-tst-data'));
        $this->assertTrue($this->event->has('2-tst-data'));

        // Test with non-existing key
        $this->assertFalse($this->event->has('non_existing_key'));

        // Test after setting a new key
        $this->event->set('new_key', 'new_value');
        $this->assertTrue($this->event->has('new_key'));
    }

    public function testStopPropagation(): void
    {
        // Initially, propagation should not be stopped
        $this->assertFalse($this->event->isPropagationStopped());

        // Stop propagation
        $this->event->stopPropagation();

        // Now propagation should be stopped
        $this->assertTrue($this->event->isPropagationStopped());
    }

    public function testSetOverwritesExistingData(): void
    {
        // Set initial value
        $this->event->set('test_key', 'initial_value');
        $this->assertEquals('initial_value', $this->event->get('test_key'));

        // Overwrite with new value
        $this->event->set('test_key', 'updated_value');
        $this->assertEquals('updated_value', $this->event->get('test_key'));
    }
}
