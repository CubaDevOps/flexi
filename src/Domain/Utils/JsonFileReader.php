<?php

namespace CubaDevOps\Flexi\Domain\Utils;

use CubaDevOps\Flexi\Infrastructure\Factories\ConfigurationFactory;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

trait JsonFileReader
{
    /**
     * @param string $file_path
     * @return array
     * @throws JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function readJsonFile(string $file_path): array
    {
        $config = ConfigurationFactory::getInstance();
        $absolute_path = realpath($file_path) ?: realpath($config->get('ROOT_DIR') . $file_path);
        $this->assertThatFilePathExist($absolute_path);
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
