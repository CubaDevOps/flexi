<?php

namespace CubaDevOps\Flexi\Test\Domain\Classes;

use CubaDevOps\Flexi\Domain\Classes\InFileLogRepository;
use CubaDevOps\Flexi\Domain\Interfaces\LogInterface;
use CubaDevOps\Flexi\Domain\Interfaces\MessageInterface;
use CubaDevOps\Flexi\Domain\ValueObjects\LogLevel;
use PHPUnit\Framework\TestCase;

class InFileLogRepositoryTest extends TestCase
{
    private LogInterface $log;
    private InFileLogRepository $repository;

    public function setUp(): void
    {
        $this->log = $this->createMock(LogInterface::class);

        $this->repository = new InFileLogRepository(
            './var/logs/inFileLogRepositoryTest.log',
            '[{level} - {time}]: {message} - {context}'
        );
    }

    public function testSave(): void
    {
        $logLevel = $this->createMock(LogLevel::class);

        $this->log->expects($this->once())
            ->method('getLogLevel')->willReturn($logLevel);

        $logLevel->expects($this->once())
            ->method('getValue')->willReturn('INFO');

        $message   = $this->createMock(MessageInterface::class);
        $createdAt = $this->createMock(\DateTimeImmutable::class);

        $this->log->expects($this->exactly(2))
            ->method('getMessage')->willReturn($message);

        $message->expects($this->once())
            ->method('createdAt')->willReturn($createdAt);
        $createdAt->expects($this->once())
            ->method('format')->willReturn('2005-08-15T15:52:01+00:00'); // Y-m-d\TH:i:sP

        $message->expects($this->once())
            ->method('__toString')->willReturn('message info');

        $this->log->expects($this->once())
            ->method('getContext')->willReturn(['context' => 'line 23']);

        $this->repository->save($this->log);
    }
}
