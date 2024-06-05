<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Classes;

use CubaDevOps\Flexi\Domain\Classes\InFileLogRepository;
use CubaDevOps\Flexi\Domain\Classes\Log;
use CubaDevOps\Flexi\Domain\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Domain\ValueObjects\LogLevel;
use Psr\Log\AbstractLogger;

class PsrLogger extends AbstractLogger
{
    private InFileLogRepository $log_repository;

    public function __construct(InFileLogRepository $log_repository)
    {
        $this->log_repository = $log_repository;
    }

    public function log($level, $message, array $context = []): void
    {
        $log = new Log(
            new LogLevel($level),
            new PlainTextMessage($message),
            $context
        );
        $this->log_repository->save($log);
    }
}
