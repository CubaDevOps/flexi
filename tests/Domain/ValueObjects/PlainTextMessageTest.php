<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Domain\ValueObjects;

use Flexi\Contracts\Classes\PlainTextMessage;
use PHPUnit\Framework\TestCase;

class PlainTextMessageTest extends TestCase
{
    private PlainTextMessage $plainTextMessage;
    private \DateTimeImmutable $now;

    public function setUp(): void
    {
        $this->plainTextMessage = new PlainTextMessage('body');
        $this->now = $this->plainTextMessage->createdAt();
    }

    public function testToString(): void
    {
        $this->assertEquals('body', $this->plainTextMessage->__toString());
    }

    public function testToArray(): void
    {
        $expected = [
            'body' => 'body',
            'created_at' => $this->now,
        ];

        $this->assertEquals($expected, $this->plainTextMessage->toArray());
    }

    public function testFromArray(): void
    {
        $data = [
            'body' => 'body',
        ];

        $newPlainTextMessage = PlainTextMessage::fromArray($data);

        $this->assertInstanceOf(PlainTextMessage::class, $newPlainTextMessage);
        $this->assertEquals($data['body'], $newPlainTextMessage->get('body'));
    }

    public function testFromArrayInvalidData(): void
    {
        $data = [
            'test' => 'body',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data provided for '.PlainTextMessage::class);

        PlainTextMessage::fromArray($data);
    }
}
