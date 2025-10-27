<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use CubaDevOps\Flexi\Contracts\Classes\Traits\FileHandlerTrait;
use CubaDevOps\Flexi\Contracts\Interfaces\CacheInterface;
use CubaDevOps\Flexi\Infrastructure\Cache\FileCache;
use CubaDevOps\Flexi\Infrastructure\Cache\InMemoryCache;
use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CacheFactory
{
    use FileHandlerTrait;

    private Configuration $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getInstance($driver = null): CacheInterface
    {
        $cache_driver = $driver ?? $this->configuration->get('cache_driver');

        // Todo: implement other cache drivers
        switch ($cache_driver) {
            case 'array':
            case 'memory':
                return new InMemoryCache();
            case 'file':
            default:
                $cache_dir = $this->normalize($this->configuration->get('cache_dir') ?? $this->configuration->get('ROOT_DIR').'/var/cache');

                return new FileCache($cache_dir);
        }
    }

    public static function createDefault(Configuration $configuration)
    {
        return (new self($configuration))->getInstance();
    }
}
