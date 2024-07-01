<?php

namespace CubaDevOps\Flexi\Test\Domain\ValueObjects;

use CubaDevOps\Flexi\Domain\ValueObjects\LogLevel;
use PHPUnit\Framework\TestCase;

class LogLevelTest extends TestCase
{
    public function testInvalidLevel(): void
    {
        $level = 'invalid level';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid log level: $level");
        new LogLevel($level);
    }

    public function testGetValue(): void
    {
        $log = new LogLevel(LogLevel::DEBUG);
        $this->assertEquals(LogLevel::DEBUG, $log->getValue());
    }
}
