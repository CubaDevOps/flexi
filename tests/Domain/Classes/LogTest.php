<?php

namespace CubaDevOps\Flexi\Test\Domain\Classes;

use CubaDevOps\Flexi\Domain\Classes\Log;
use CubaDevOps\Flexi\Domain\Interfaces\MessageInterface;
use CubaDevOps\Flexi\Domain\ValueObjects\LogLevel;
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{
    private const LOG_CONTEXT = [
        'class' => 'namespace/ClassName',
        'line'  => 23
    ];

    private LogLevel $logLevel;
    private MessageInterface $message;
    private Log $log;

    public function setUp(): void
    {
        $this->logLevel = $this->createMock(LogLevel::class);
        $this->message  = $this->createMock(MessageInterface::class);

        $this->log = new Log($this->logLevel, $this->message, self::LOG_CONTEXT);
    }

    public function testGetLogLevel(): void
    {
        //Todo This assertion can probably be skipped (argument implicitly declares return type).
        // maybe it's better to use $this->logLevel->method('getValue')->willReturn('INFO'); and then assert the value
        $this->assertInstanceOf(LogLevel::class, $this->log->getLogLevel());
    }

    public function testGetMessage(): void
    {
        $expected = 'message';

        $this->message->expects($this->once())
            ->method('__toString')->willReturn($expected);

        $actual = $this->log->__toString();

        $this->assertEquals($expected, $actual);
        //Todo This assertion can probably be skipped (argument implicitly declares return type).
        $this->assertInstanceOf(MessageInterface::class, $this->log->getMessage());
    }

    public function testGetLogContext(): void
    {
        $this->assertEquals(self::LOG_CONTEXT, $this->log->getContext());
    }
}
