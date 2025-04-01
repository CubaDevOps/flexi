<?php

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use CubaDevOps\Flexi\Domain\Interfaces\CacheInterface;
use CubaDevOps\Flexi\Domain\Utils\FileHandlerTrait;
use CubaDevOps\Flexi\Infrastructure\Cache\FileCache;
use CubaDevOps\Flexi\Infrastructure\Cache\InMemoryCache;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CacheFactory
{
    use FileHandlerTrait;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getInstance(): CacheInterface
    {
        $configuration = ConfigurationFactory::getInstance();
        $cache_driver = $configuration->get('cache_driver');
        $cache_dir = (new self)->normalize($configuration->get('cache_dir') ?? $configuration->get('ROOT_DIR') . '/var/cache');

        //Todo: implement other cache drivers
        switch ($cache_driver) {
            case 'array':
            case 'memory':
                return new InMemoryCache();
            case 'file':
                return new FileCache($cache_dir);
        }

        return new FileCache($cache_dir);
    }
}