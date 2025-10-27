<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Classes\Traits;

trait JsonFileReader
{
    use FileHandlerTrait;

    /**
     * @throws \JsonException
     */
    public function readJsonFile(string $file_path): array
    {
        $contents = $this->readFromFile($file_path);

        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param int $flags see https://www.php.net/manual/en/function.file-put-contents.php
     *
     * @throws \JsonException
     */
    public function writeJsonFileFromArray(
        string $file_path,
        array $data,
        int $flags = 0,
        bool $try_create_it = false
    ): void {
        $this->writeToFile(
            $file_path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            $flags,
            $try_create_it
        );
    }
}
