<?php

namespace CubaDevOps\Flexi\Domain\Utils;

use CubaDevOps\Flexi\Infrastructure\Factories\ConfigurationFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait GlobFileReader
{
    /**
     * @param string $glob_path
     * @return string[]
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function readGlob(string $glob_path): array
    {
        if (strpos($glob_path, '/modules') === 0) {
            $config = ConfigurationFactory::getInstance();
            $glob_path = $config->get('ROOT_DIR') . $glob_path;
        }
        return glob($glob_path, GLOB_BRACE | GLOB_NOSORT) ?: [];
    }
}
