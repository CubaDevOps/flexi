<?php

declare(strict_types=1);

namespace Flexi\Test\Application\Commands;

use Flexi\Domain\Commands\NotFoundCommand;
use Flexi\Contracts\Interfaces\DTOInterface;
use PHPUnit\Framework\TestCase;

class NotFoundCommandTest extends TestCase
{
    private NotFoundCommand $notFoundCommand;

    public function setUp(): void
    {
        $this->notFoundCommand = new NotFoundCommand();
    }

    public function testImplementsDTOInterface(): void
    {
        $this->assertInstanceOf(DTOInterface::class, $this->notFoundCommand);
    }

    public function testToArray(): void
    {
        $expected = [
            'error' => 'Command not found',
            'handler' => false
        ];

        $this->assertEquals($expected, $this->notFoundCommand->toArray());
    }

    public function testToString(): void
    {
        $expected = 'NotFoundCommand: No handler registered for this command';

        $this->assertEquals($expected, $this->notFoundCommand->__toString());
        $this->assertEquals($expected, (string)$this->notFoundCommand);
    }

    public function testFromArray(): void
    {
        $data = ['some' => 'data'];
        $command = NotFoundCommand::fromArray($data);

        $this->assertInstanceOf(NotFoundCommand::class, $command);
        $this->assertEquals(['error' => 'Command not found', 'handler' => false], $command->toArray());
    }

    public function testValidate(): void
    {
        $this->assertTrue(NotFoundCommand::validate([]));
        $this->assertTrue(NotFoundCommand::validate(['any' => 'data']));
    }

    public function testGet(): void
    {
        $this->assertEquals('Command not found', $this->notFoundCommand->get('error'));
        $this->assertFalse($this->notFoundCommand->get('handler'));
        $this->assertNull($this->notFoundCommand->get('nonexistent'));
    }
}