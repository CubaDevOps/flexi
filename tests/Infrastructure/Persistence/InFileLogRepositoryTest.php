<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Persistence;

use CubaDevOps\Flexi\Contracts\Interfaces\LogInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\MessageInterface;
use CubaDevOps\Flexi\Contracts\ValueObjects\LogLevel;
use CubaDevOps\Flexi\Infrastructure\Persistence\InFileLogRepository;
use CubaDevOps\Flexi\Test\TestData\TestDoubles\FileHandler;
use PHPUnit\Framework\TestCase;

class InFileLogRepositoryTest extends TestCase
{
    private $log;
    private InFileLogRepository $repository;
    private string $log_path;

    public function __construct()
    {
        parent::__construct();
        $this->log_path = './var/logs/test.log';
        $file_handler = new FileHandler();
        $file_handler->createFileIfNotExist($this->log_path);
    }

    public function setUp(): void
    {
        $this->log = $this->createMock(LogInterface::class);

        $this->repository = new InFileLogRepository(
            $this->log_path,
            '[{level} - {time}]: {message} - {context}'
        );
    }

    public function testSave(): void
    {
        $level = LogLevel::INFO;
        $logLevel = new LogLevel($level);

        $this->log->expects($this->exactly(2))
            ->method('getLogLevel')->willReturn($logLevel);

        $message = $this->createMock(MessageInterface::class);
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

        // Running the test alone fails but running the collection is does not
        $this->assertFileExists($this->log_path);
        $this->assertStringContainsString(
            '['.$level.' - 2005-08-15T15:52:01+00:00]: message info - line 23',
            file_get_contents($this->log_path)
        );
        $this->assertEquals(LogLevel::INFO, $this->log->getLogLevel()->getValue());
    }
}
