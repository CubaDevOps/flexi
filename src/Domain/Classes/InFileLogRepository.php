<?php

namespace CubaDevOps\Flexi\Domain\Classes;

use CubaDevOps\Flexi\Domain\Interfaces\LogInterface;
use CubaDevOps\Flexi\Domain\Interfaces\LogRepositoryInterface;
use CubaDevOps\Flexi\Domain\Utils\FileHandlerTrait;

class InFileLogRepository implements LogRepositoryInterface
{
    use FileHandlerTrait;

    private string $path;
    private string $format;

    /**
     * @param string $path
     * @param string $format
     */
    public function __construct(string $path, string $format)
    {
        $this->ensureFileExists($path);
        $this->path = $path;
        $this->format = $format;
    }

    public function save(LogInterface $log): void
    {
        file_put_contents(
            $this->path,
            $this->formatMessage($log) . PHP_EOL,
            FILE_APPEND
        );
    }

    private function formatMessage(LogInterface $log): string
    {
        $values = [
            '{level}' => $log->getLogLevel()->getValue(),
            '{time}' => $log
                ->getMessage()
                ->createdAt()
                ->format(DATE_ATOM),
            '{message}' => $log->getMessage()->__toString(),
            '{context}' => implode('|', $log->getContext()),
        ];

        return str_replace(
            array_keys($values),
            array_values($values),
            $this->format
        );
    }
}
