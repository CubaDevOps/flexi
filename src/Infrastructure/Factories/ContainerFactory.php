<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use CubaDevOps\Flexi\Domain\Interfaces\CacheInterface;
use CubaDevOps\Flexi\Domain\Utils\ServicesDefinitionParser;
use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use CubaDevOps\Flexi\Infrastructure\Classes\ConfigurationRepository;
use CubaDevOps\Flexi\Infrastructure\Classes\ObjectBuilder;
use CubaDevOps\Flexi\Infrastructure\Factories\CacheFactory;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\Container;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ContainerFactory
{

    private CacheInterface $cache;
    private ObjectBuilder $object_builder;

    public function __construct(CacheInterface $cache, ObjectBuilder $object_builder)
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

    public static function createDefault(string $file = ''): Container
    {
        $configRepo = new ConfigurationRepository();
        $configuration = new Configuration($configRepo);
        $cache = (new CacheFactory($configuration))->getInstance();
        $objectBuilder = new ObjectBuilder();

        return (new self($cache, $objectBuilder))->getInstance($file);
    }

}