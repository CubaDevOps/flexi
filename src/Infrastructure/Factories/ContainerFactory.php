<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use Flexi\Contracts\Interfaces\CacheInterface;
use Flexi\Contracts\Interfaces\ObjectBuilderInterface;
use CubaDevOps\Flexi\Infrastructure\Classes\InMemoryCache;
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

        // Container relies on Cache module if available, otherwise uses core InMemoryCache implementation
        if (null === $cache) {
            if (self::isModuleInstalled('cache')) {
                try {
                    $cacheFactoryClass = 'CubaDevOps\\Flexi\\Modules\\Cache\\Infrastructure\\Factories\\CacheFactory';
                    /** @var \CubaDevOps\Flexi\Modules\Cache\Infrastructure\Factories\CacheFactory $cacheFactory */
                    $cacheFactory = new $cacheFactoryClass($configuration);
                    $cache = $cacheFactory->getInstance();
                } catch (\Throwable $e) {
                    // If Cache module fails to load, fall back to InMemoryCache
                    $cache = new InMemoryCache();
                }
            } else {
                $cache = new InMemoryCache();
            }
        }

        $objectBuilder = $objectBuilder ?? new ObjectBuilder($cache);

        return (new self($cache, $objectBuilder))->getInstance($file);
    }

    /**
     * Checks if a module is installed by verifying its presence in composer.json.
     *
     * @param string $moduleName Module name (e.g., 'cache', 'logging', 'session')
     * @return bool True if module is installed, false otherwise
     */
    private static function isModuleInstalled(string $moduleName): bool
    {
        static $installedModules = null;

        if ($installedModules === null) {
            $installedModules = [];
            $composerJsonPath = './composer.json';

            if (file_exists($composerJsonPath)) {
                try {
                    $composerData = json_decode(
                        file_get_contents($composerJsonPath),
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    );

                    if (isset($composerData['require'])) {
                        foreach ($composerData['require'] as $package => $version) {
                            // Check if it's a flexi module package
                            if (preg_match('/^cubadevops\/flexi-module-(.+)$/', $package, $matches)) {
                                $installedModules[$matches[1]] = true;
                            }
                        }
                    }
                } catch (\JsonException $e) {
                    // If composer.json is invalid, assume no modules are installed
                    return false;
                }
            }
        }

        return isset($installedModules[strtolower($moduleName)]);
    }
}
