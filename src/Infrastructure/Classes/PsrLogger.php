<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Classes;

use CubaDevOps\Flexi\Contracts\Classes\Log;
use CubaDevOps\Flexi\Contracts\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Contracts\Interfaces\LogRepositoryInterface;
use CubaDevOps\Flexi\Contracts\ValueObjects\LogLevel;
use Psr\Log\AbstractLogger;

class PsrLogger extends AbstractLogger
{
    private LogRepositoryInterface $log_repository;
    private Configuration $configuration;

    public function __construct(LogRepositoryInterface $log_repository, Configuration $configuration)
    {
        $this->log_repository = $log_repository;
        $this->configuration = $configuration;
    }

    public function log($level, $message, array $context = []): void
    {
        $log_level = new LogLevel($level);

        if (!$this->configuration->get('log_enabled') || $log_level->isBelowThreshold(new LogLevel($this->configuration->get('log_level')))) {
            return;
        }

        $log = new Log(
            $log_level,
            new PlainTextMessage($message),
            $context
        );
        $this->log_repository->save($log);
    }
}
