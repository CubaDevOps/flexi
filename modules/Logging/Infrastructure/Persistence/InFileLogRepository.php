<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Logging\Infrastructure\Persistence;

use CubaDevOps\Flexi\Contracts\Classes\Traits\FileHandlerTrait;
use CubaDevOps\Flexi\Contracts\Interfaces\LogInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\LogRepositoryInterface;

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
            '{context}' => $this->serializeToJson($log->getContext()),
        ];

        return str_replace(
            array_keys($values),
            array_values($values),
            $this->format
        );
    }

    private function serializeToJson(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR);
    }
}
