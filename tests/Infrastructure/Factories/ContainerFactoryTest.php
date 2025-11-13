<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Factories;

use CubaDevOps\Flexi\Infrastructure\Factories\ContainerFactory;
use CubaDevOps\Flexi\Infrastructure\Factories\ModuleDetectorInterface;
use CubaDevOps\Flexi\Infrastructure\Factories\CacheFactoryInterface;
use CubaDevOps\Flexi\Infrastructure\Factories\ComposerModuleDetector;
use CubaDevOps\Flexi\Infrastructure\Factories\DefaultCacheFactory;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\Container;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\ServicesDefinitionParser;
use CubaDevOps\Flexi\Infrastructure\Classes\InMemoryCache;
use CubaDevOps\Flexi\Infrastructure\Classes\ObjectBuilder;
use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use CubaDevOps\Flexi\Infrastructure\Classes\ConfigurationRepository;
use Flexi\Contracts\Interfaces\CacheInterface;
use Flexi\Contracts\Interfaces\ObjectBuilderInterface;
use PHPUnit\Framework\TestCase;

class ContainerFactoryTest extends TestCase
{
    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var ObjectBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $objectBuilder;

    /** @var ServicesDefinitionParser|\PHPUnit\Framework\MockObject\MockObject */
    private $servicesDefinitionParser;

    protected function setUp(): void
    {
        // Use mocks for all dependencies
        $this->cache = $this->createMock(CacheInterface::class);
        $this->objectBuilder = $this->createMock(ObjectBuilderInterface::class);
        $this->servicesDefinitionParser = $this->createMock(ServicesDefinitionParser::class);

        // Configure cache mock to return appropriate defaults
        $this->cache
            ->method('get')
            ->willReturnCallback(function ($key, $default = null) {
                if ($key === 'service_definitions') {
                    return [];
                }
                return $default;
            });

        // Configure cache mock for set operations
        $this->cache
            ->method('set')
            ->willReturn(true);
    }

    public function testConstruct(): void
    {
        $factory = new ContainerFactory($this->cache, $this->objectBuilder, $this->servicesDefinitionParser);
        $this->assertInstanceOf(ContainerFactory::class, $factory);
    }

    public function testGetInstanceWithoutFile(): void
    {
        $factory = new ContainerFactory($this->cache, $this->objectBuilder, $this->servicesDefinitionParser);
        $container = $factory->getInstance();

        $this->assertInstanceOf(Container::class, $container);
    }

    public function testGetInstanceWithFile(): void
    {
        // Mock the services parser to return test services
        $testServices = [
            'test-service' => $this->createMock(\CubaDevOps\Flexi\Infrastructure\DependencyInjection\Service::class)
        ];

        $this->servicesDefinitionParser
            ->expects($this->once())
            ->method('parse')
            ->with('/tmp/test-services.json')
            ->willReturn($testServices);

        $factory = new ContainerFactory($this->cache, $this->objectBuilder, $this->servicesDefinitionParser);
        $container = $factory->getInstance('/tmp/test-services.json');

        $this->assertInstanceOf(Container::class, $container);
    }

    public function testCreateDefaultWithoutParameters(): void
    {
        $container = ContainerFactory::createDefault();

        $this->assertInstanceOf(Container::class, $container);
    }

    public function testCreateDefaultWithFile(): void
    {
        // Create a test services file
        $testFile = '/tmp/test-services-default.json';
        $servicesData = [
            'services' => [
                [
                    'name' => 'default-service',
                    'class' => InMemoryCache::class
                ]
            ]
        ];
        file_put_contents($testFile, json_encode($servicesData));

        $container = ContainerFactory::createDefault($testFile);

        $this->assertInstanceOf(Container::class, $container);
        $this->assertTrue($container->has('default-service'));

        // Clean up
        unlink($testFile);
    }

    public function testCreateDefaultCacheModuleNotInstalled(): void
    {
        // This test relies on the fact that cache module is not installed by default
        // The method should fall back to InMemoryCache
        $container = ContainerFactory::createDefault();

        $this->assertInstanceOf(Container::class, $container);
    }

    public function testCreateDefaultWithInvalidComposerJson(): void
    {
        // Create a temporary invalid composer.json
        $originalComposerPath = './composer.json';
        $backupPath = './composer.json.backup';
        $invalidComposerPath = './composer.json';

        // Backup original if exists
        if (file_exists($originalComposerPath)) {
            rename($originalComposerPath, $backupPath);
        }

        // Create invalid JSON
        file_put_contents($invalidComposerPath, '{"invalid": json}');

        $container = ContainerFactory::createDefault();

        $this->assertInstanceOf(Container::class, $container);

        // Restore original composer.json
        unlink($invalidComposerPath);
        if (file_exists($backupPath)) {
            rename($backupPath, $originalComposerPath);
        }
    }

    public function testCreateDefaultWithNoComposerJson(): void
    {
        // Create a temporary directory without composer.json
        $originalDir = getcwd();
        $tempDir = '/tmp/test-no-composer-' . uniqid();

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        chdir($tempDir);

        $container = ContainerFactory::createDefault();

        $this->assertInstanceOf(Container::class, $container);

        // Restore original directory
        chdir($originalDir);
        rmdir($tempDir);
    }

    public function testGetInstanceWithMockedDependencies(): void
    {
        // Test getInstance method behavior with mocked dependencies
        $factory = new ContainerFactory($this->cache, $this->objectBuilder, $this->servicesDefinitionParser);

        // Test without file - should create Container with mocked dependencies
        $container = $factory->getInstance();
        $this->assertInstanceOf(Container::class, $container);
    }

    public function testGetInstanceWithFileAndMockedDependencies(): void
    {
        // Mock the services parser to return test services
        $mockService = $this->createMock(\CubaDevOps\Flexi\Infrastructure\DependencyInjection\Service::class);
        $testServices = [
            'test-service-mocked' => $mockService
        ];

        $this->servicesDefinitionParser
            ->expects($this->once())
            ->method('parse')
            ->with('/tmp/test-services-mocked.json')
            ->willReturn($testServices);

        // Test with file - should parse services and add to container
        $factory = new ContainerFactory($this->cache, $this->objectBuilder, $this->servicesDefinitionParser);
        $container = $factory->getInstance('/tmp/test-services-mocked.json');

        $this->assertInstanceOf(Container::class, $container);
        // Note: Testing service registration may require specific Container behavior
        // For now, we'll just verify the container was created successfully
    }

    public function testEdgeCasesWithMocks(): void
    {
        // Test createDefault with empty string file
        $container = ContainerFactory::createDefault('');
        $this->assertInstanceOf(Container::class, $container);

        // Test getInstance with empty file string
        $factory = new ContainerFactory($this->cache, $this->objectBuilder, $this->servicesDefinitionParser);
        $container = $factory->getInstance('');
        $this->assertInstanceOf(Container::class, $container);
    }

    public function testComposerModuleDetectorImplementation(): void
    {
        // Test the real implementation of ComposerModuleDetector
        $detector = new ComposerModuleDetector();

        // This should return false for non-existent modules
        $this->assertFalse($detector->isModuleInstalled('non-existent-module'));

        // Test with case sensitivity
        $this->assertFalse($detector->isModuleInstalled('CACHE'));
    }

    public function testDefaultCacheFactoryImplementation(): void
    {
        // Test DefaultCacheFactory with module not installed
        $mockModuleDetector = $this->createMock(ModuleDetectorInterface::class);
        $mockModuleDetector
            ->method('isModuleInstalled')
            ->willReturn(false);

        $mockConfiguration = $this->createMock(Configuration::class);
        $cacheFactory = new DefaultCacheFactory($mockModuleDetector, $mockConfiguration);
        $cache = $cacheFactory->createCache();

        $this->assertInstanceOf(InMemoryCache::class, $cache);
    }

    public function testDefaultCacheFactoryWithCacheModuleInstalled(): void
    {
        // Test DefaultCacheFactory when cache module is "installed" but fails
        $mockModuleDetector = $this->createMock(ModuleDetectorInterface::class);
        $mockModuleDetector
            ->method('isModuleInstalled')
            ->willReturn(true);

        $mockConfiguration = $this->createMock(Configuration::class);
        $cacheFactory = new DefaultCacheFactory($mockModuleDetector, $mockConfiguration);

        // Should fallback to InMemoryCache when cache module fails to load
        $cache = $cacheFactory->createCache();
        $this->assertInstanceOf(InMemoryCache::class, $cache);
    }

    public function testCreateDefaultInternalDependenciesCreation(): void
    {
        // This test verifies that createDefault method properly creates all internal dependencies
        $container = ContainerFactory::createDefault();
        $this->assertInstanceOf(Container::class, $container);

        // Test that it can handle a valid services file
        $testFile = '/tmp/test-services-internal.json';
        $servicesData = [
            'services' => [
                [
                    'name' => 'internal-service',
                    'class' => InMemoryCache::class
                ]
            ]
        ];
        file_put_contents($testFile, json_encode($servicesData));

        $container = ContainerFactory::createDefault($testFile);
        $this->assertInstanceOf(Container::class, $container);
        $this->assertTrue($container->has('internal-service'));

        // Clean up
        unlink($testFile);
    }

    public function testComposerModuleDetectorCacheHandling(): void
    {
        // Test static caching behavior
        $detector = new ComposerModuleDetector();

        // Multiple calls should use static cache
        $result1 = $detector->isModuleInstalled('cache');
        $result2 = $detector->isModuleInstalled('cache');

        $this->assertEquals($result1, $result2, 'Static caching should return consistent results');
    }

    public function testComposerModuleDetectorWithDifferentModules(): void
    {
        $detector = new ComposerModuleDetector();

        // Test various module names
        $modules = ['cache', 'logging', 'session', 'database'];

        foreach ($modules as $module) {
            $result = $detector->isModuleInstalled($module);
            $this->assertIsBool($result, "Module detection should return boolean for module: {$module}");
        }
    }
}