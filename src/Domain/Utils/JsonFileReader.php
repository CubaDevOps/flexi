<?php

namespace CubaDevOps\Flexi\Domain\Utils;

use JsonException;
use RuntimeException;

trait JsonFileReader
{
    use FileHandlerTrait;

    /**
     * @param string $file_path
     * @return array
     * @throws JsonException
     */
    public function readJsonFile(string $file_path): array
    {
        $absolute_path = $this->normalize($file_path);
        $contents = file_get_contents($absolute_path);

        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param string $file_path
     */
    private function assertThatFilePathExist(string $file_path): void
    {
        if (!file_exists($file_path)) {
            throw new RuntimeException("File $file_path doesn't exist");
        }
    }
}
