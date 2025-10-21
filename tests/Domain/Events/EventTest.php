<?php

namespace CubaDevOps\Flexi\Test\Domain\Events;

use CubaDevOps\Flexi\Domain\Events\Event;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    private const EVENT_NAME = 'eventName';
    private const EVENT_TRIGGER = 'eventTrigger';
    private const EVENT_DATA = [
        '1-tst-data' => [1, 2, 3],
        '2-tst-data' => 'event-data'
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
            'event'       => self::EVENT_NAME,
            'data'        => self::EVENT_DATA,
            'fired_by'    => self::EVENT_TRIGGER,
            'occurred_on' => $this->now->format(DATE_ATOM),
        ];

        $this->assertEquals($expected, $this->event->toArray());
    }

    public function testCreateEventFromArray(): void
    {
        $data = [
            'event'    => self::EVENT_NAME,
            'data'     => self::EVENT_DATA,
            'fired_by' => self::EVENT_TRIGGER
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
            'event' => self::EVENT_NAME
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid parameters provided for '. Event::class);

        Event::fromArray($data);
    }

    /**
     * @throws \JsonException
     */
    public function testSerialize(): void
    {
        $expected =
            '{"event":"'. self::EVENT_NAME .
            '","data":'. json_encode(self::EVENT_DATA, JSON_THROW_ON_ERROR) .
            ',"fired_by":"'. self::EVENT_TRIGGER .
            '","occurred_on":"'. $this->now->format(DATE_ATOM) .'"}';

        $serialized = $this->event->serialize();

        $this->assertNotEmpty($serialized);
        $this->assertEquals($expected, $serialized);
    }
}
