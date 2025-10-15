<?php

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use CubaDevOps\Flexi\Domain\Interfaces\CacheInterface;
use CubaDevOps\Flexi\Infrastructure\Utils\FileHandlerTrait;
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
    public static function getInstance($driver = null): CacheInterface
    {
        $configuration = ConfigurationFactory::getInstance();

        $cache_driver = $driver ?? $configuration->get('cache_driver');

        //Todo: implement other cache drivers
        switch ($cache_driver) {
            case 'array':
            case 'memory':
                return new InMemoryCache();
            case 'file':
            default:
                $cache_dir = (new self)->normalize($configuration->get('cache_dir') ?? $configuration->get('ROOT_DIR') . '/var/cache');
                return new FileCache($cache_dir);
        }
    }
}