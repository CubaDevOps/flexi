<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use CubaDevOps\Flexi\Contracts\CacheContract;
use CubaDevOps\Flexi\Contracts\ObjectBuilderContract;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\ServicesDefinitionParser;
use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use CubaDevOps\Flexi\Infrastructure\Classes\ConfigurationRepository;
use CubaDevOps\Flexi\Infrastructure\Classes\ObjectBuilder;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\Container;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ContainerFactory
{
    private CacheContract $cache;
    private ObjectBuilderContract $object_builder;

    public function __construct(CacheContract $cache, ObjectBuilderContract $object_builder)
    {
        $this->cache = $cache;
        $this->object_builder = $object_builder;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getInstance(string $file = ''): Container
    {
        $container = new Container($this->cache, $this->object_builder);
        if ($file) {
            $services_parser = new ServicesDefinitionParser($this->cache);
            $services = $services_parser->parse($file);
            foreach ($services as $name => $service) {
                $container->set($name, $service);
            }
        }

        return $container;
    }

    public static function createDefault(
        string $file = '',
        ?ConfigurationRepository $configRepo = null,
        ?Configuration $configuration = null,
        ?CacheFactory $cacheFactory = null,
        ?ObjectBuilder $objectBuilder = null
    ): Container {
        $configRepo = $configRepo ?? new ConfigurationRepository();
        $configuration = $configuration ?? new Configuration($configRepo);
        $cacheFactory = $cacheFactory ?? new CacheFactory($configuration);
        $cache = $cacheFactory->getInstance();
        $objectBuilder = $objectBuilder ?? new ObjectBuilder($cache);

        return (new self($cache, $objectBuilder))->getInstance($file);
    }
}
