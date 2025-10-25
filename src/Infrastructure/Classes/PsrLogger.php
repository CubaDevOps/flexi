<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Classes;

use CubaDevOps\Flexi\Domain\Classes\Log;
use CubaDevOps\Flexi\Domain\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Domain\Interfaces\LogRepositoryInterface;
use CubaDevOps\Flexi\Domain\ValueObjects\LogLevel;
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

        if (!$this->configuration->get('log_enabled') || $log_level->getValue() < (new LogLevel($this->configuration->get('log_level')))->getValue()) {
            return;
        }
        // Use the comparison method from LogLevel
        $threshold_level = new LogLevel($this->configuration->get('log_level'));
        if ($log_level->isBelowThreshold($threshold_level)) {
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
