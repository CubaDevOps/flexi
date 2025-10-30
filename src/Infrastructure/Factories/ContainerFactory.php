<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use CubaDevOps\Flexi\Contracts\Interfaces\CacheInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\ObjectBuilderInterface;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\ServicesDefinitionParser;
use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use CubaDevOps\Flexi\Infrastructure\Classes\ConfigurationRepository;
use CubaDevOps\Flexi\Infrastructure\Classes\ObjectBuilder;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\Container;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ContainerFactory
{
    private CacheInterface $cache;
    private ObjectBuilderInterface $object_builder;

    public function __construct(CacheInterface $cache, ObjectBuilderInterface $object_builder)
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
        ?CacheInterface $cache = null,
        ?ObjectBuilder $objectBuilder = null
    ): Container {
        $configRepo = $configRepo ?? new ConfigurationRepository();
        $configuration = $configuration ?? new Configuration($configRepo);

        // Container depends on Cache module
        if (null === $cache) {
            if (class_exists('CubaDevOps\\Flexi\\Modules\\Cache\\Infrastructure\\Factories\\CacheFactory')) {
                $cacheFactoryClass = 'CubaDevOps\\Flexi\\Modules\\Cache\\Infrastructure\\Factories\\CacheFactory';
                /** @var \CubaDevOps\Flexi\Modules\Cache\Infrastructure\Factories\CacheFactory $cacheFactory */
                $cacheFactory = new $cacheFactoryClass($configuration);
                $cache = $cacheFactory->getInstance();
            }else {
                throw new \RuntimeException('Cache implementation not available. Please ensure Cache module is installed.');
            }
        }

        $objectBuilder = $objectBuilder ?? new ObjectBuilder($cache);

        return (new self($cache, $objectBuilder))->getInstance($file);
    }
}
