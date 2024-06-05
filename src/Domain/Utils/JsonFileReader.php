<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Utils;

use JsonException;

trait JsonFileReader
{
    use FileHandlerTrait;

    /**
     * @throws JsonException
     */
    public function readJsonFile(string $file_path): array
    {
        $contents = $this->readFromFile($file_path);
        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws JsonException
     */
    public function writeJsonFileFromArray(string $file_path, array $data): void
    {
        $this->writeToFile($file_path, json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
}
