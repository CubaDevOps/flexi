<?php

namespace CubaDevOps\Flexi\Test\Domain\Entities;

use CubaDevOps\Flexi\Domain\Classes\Log;
use CubaDevOps\Flexi\Domain\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Contracts\MessageContract;
use CubaDevOps\Flexi\Domain\ValueObjects\LogLevel;
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{
    private const LOG_CONTEXT = [
        'class' => 'namespace/ClassName',
        'line'  => 23
    ];

    private LogLevel $logLevel;
    private MessageContract $message;
    private Log $log;

    public function setUp(): void
    {
        $this->logLevel = new LogLevel('info');
        $this->message  = new PlainTextMessage('message');

        $this->log = new Log($this->logLevel, $this->message, self::LOG_CONTEXT);
    }

    public function testGetLogLevel(): void
    {
        $this->assertEquals('info', $this->log->getLogLevel()->getValue());
        $this->assertInstanceOf(LogLevel::class, $this->log->getLogLevel());
    }

    public function testGetMessage(): void
    {
        $this->assertEquals($this->message->get('body'), $this->log->__toString());
        $this->assertEquals($this->message->get('body'), $this->log->getMessage()->get('body'));
        $this->assertInstanceOf(MessageContract::class, $this->log->getMessage());
    }

    public function testGetLogContext(): void
    {
        $this->assertEquals(self::LOG_CONTEXT, $this->log->getContext());
    }
}
