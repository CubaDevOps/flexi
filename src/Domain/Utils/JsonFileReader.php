<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Utils;

trait JsonFileReader
{
    use FileHandlerTrait;

    /**
     * @throws \JsonException
     */
    public function readJsonFile(string $file_path): array
    {
        $absolute_path = $this->normalize($file_path);
        $contents = file_get_contents($absolute_path);

        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }

    private function assertThatFilePathExist(string $file_path): void
    {
        if (!file_exists($file_path)) {
            throw new \RuntimeException("File $file_path doesn't exist");
        }
    }
}
