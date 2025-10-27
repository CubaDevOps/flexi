<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Persistence;

use CubaDevOps\Flexi\Contracts\Interfaces\LogInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\LogRepositoryInterface;
use CubaDevOps\Flexi\Infrastructure\Utils\FileHandlerTrait;

class InFileLogRepository implements LogRepositoryInterface
{
    use FileHandlerTrait;

    private string $path;
    private string $format;

    public function __construct(string $path, string $format)
    {
        try {
            $this->ensureFileExists($path);
        } catch (\Throwable $th) {
            $file_path = $this->normalize($path);
            $this->createFile($file_path);
        }
        $this->path = $path;
        $this->format = $format;
    }

    public function save(LogInterface $log): void
    {
        $this->writeToFile(
            $this->path,
            $this->formatMessage($log).PHP_EOL,
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
