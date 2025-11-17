<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use CubaDevOps\Flexi\Infrastructure\Classes\ModuleCacheManager;
use CubaDevOps\Flexi\Infrastructure\Classes\ModuleStateRepository;
use Flexi\Contracts\Interfaces\CacheInterface;
use Flexi\Contracts\Interfaces\ObjectBuilderInterface;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\ServicesDefinitionParser;
use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use CubaDevOps\Flexi\Infrastructure\Classes\ConfigurationRepository;
use CubaDevOps\Flexi\Infrastructure\Classes\ObjectBuilder;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\Container;
use CubaDevOps\Flexi\Infrastructure\Interfaces\ConfigurationFilesProviderInterface;
use CubaDevOps\Flexi\Domain\ValueObjects\ConfigurationType;
use CubaDevOps\Flexi\Infrastructure\Classes\ConfigurationFilesProvider;
use CubaDevOps\Flexi\Infrastructure\Classes\ModuleStateManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ContainerFactory
{
    private CacheInterface $cache;
    private ObjectBuilderInterface $object_builder;
    private ServicesDefinitionParser $services_definition_parser;
    private ConfigurationFilesProviderInterface $config_files_provider;

    public function __construct(
        CacheInterface $cache,
        ObjectBuilderInterface $object_builder,
        ServicesDefinitionParser $services_definition_parser,
        ConfigurationFilesProviderInterface $config_files_provider
    ) {
        $this->cache = $cache;
        $this->object_builder = $object_builder;
        $this->services_definition_parser = $services_definition_parser;
        $this->config_files_provider = $config_files_provider;
    }

    /**
     * Create container instance with automatic service discovery
     *
     * @param bool $useConfigProvider Whether to use config provider for automatic discovery
     * @param string $fallbackFile Fallback service file if config provider not available
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function getInstance(): Container
    {
        $container = new Container($this->cache, $this->object_builder);

        // Use configuration files provider to discover all service files
        $serviceFiles = $this->config_files_provider->getConfigurationFiles(ConfigurationType::services());

        foreach ($serviceFiles as $file) {
            $services = $this->services_definition_parser->parse($file);
            foreach ($services as $name => $service) {
                $container->set($name, $service);
            }
        }

        return $container;
    }

    public static function createDefault(
    ): Container {
        $configurationRepository = new ConfigurationRepository();
        $configuration = new Configuration($configurationRepository);
        $modulesStateManager = new ModuleStateManager(new ModuleStateRepository("./var/modules-state.json"));
        $moduleDetector = new HybridModuleDetector( new LocalModuleDetector(), new VendorModuleDetector(new ModuleCacheManager()));
        $cache = (new DefaultCacheFactory($moduleDetector, $configuration))->createCache();
        $objectBuilder = new ObjectBuilder($cache);
        $servicesDefinitionParser = new ServicesDefinitionParser($cache);
        $configFilesProvider = new ConfigurationFilesProvider($modulesStateManager, $moduleDetector);

        return (new self($cache, $objectBuilder, $servicesDefinitionParser, $configFilesProvider))->getInstance();
    }
}
