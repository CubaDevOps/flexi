<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use Flexi\Contracts\Interfaces\CacheInterface;
use Flexi\Contracts\Interfaces\ObjectBuilderInterface;
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
    private ServicesDefinitionParser $services_definition_parser;

    public function __construct(
        CacheInterface $cache,
        ObjectBuilderInterface $object_builder,
        ServicesDefinitionParser $services_definition_parser
    ) {
        $this->cache = $cache;
        $this->object_builder = $object_builder;
        $this->services_definition_parser = $services_definition_parser;
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
            $services = $this->services_definition_parser->parse($file);
            foreach ($services as $name => $service) {
                $container->set($name, $service);
            }
        }

        return $container;
    }

    public static function createDefault(
        string $file = ''
    ): Container {
        $configurationRepository = new ConfigurationRepository();
        $configuration = new Configuration($configurationRepository);
        $cache = (new DefaultCacheFactory(new ComposerModuleDetector(), $configuration))->createCache();
        $objectBuilder = new ObjectBuilder($cache);
        $servicesDefinitionParser = new ServicesDefinitionParser($cache);

        return (new self($cache, $objectBuilder, $servicesDefinitionParser))->getInstance($file);
    }
}
