<?php

declare(strict_types=1);

namespace Flexi\Test\Infrastructure\Classes;

use Flexi\Infrastructure\Classes\Configuration;
use Flexi\Contracts\Interfaces\ConfigurationRepositoryInterface;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    private Configuration $configuration;
    /** @var ConfigurationRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $configRepository;

    protected function setUp(): void
    {
        $this->configRepository = $this->createMock(ConfigurationRepositoryInterface::class);
        $this->configuration = new Configuration($this->configRepository);
    }

    /**
     * Tests getting configuration value from repository
     */
    public function testGet(): void
    {
        $expectedValue = 'test-value';
        $configKey = 'test.config.key';

        $this->configRepository
            ->expects($this->once())
            ->method('get')
            ->with($configKey)
            ->willReturn($expectedValue);

        $actualValue = $this->configuration->get($configKey);
        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * Tests getting null value from repository
     */
    public function testGetWithNullValue(): void
    {
        $configKey = 'nonexistent.key';

        $this->configRepository
            ->expects($this->once())
            ->method('get')
            ->with($configKey)
            ->willReturn(null);

        $actualValue = $this->configuration->get($configKey);
        $this->assertNull($actualValue);
    }

    /**
     * Tests getting array value from repository
     */
    public function testGetWithArrayValue(): void
    {
        $expectedArray = ['key1' => 'value1', 'key2' => 'value2'];
        $configKey = 'array.config';

        $this->configRepository
            ->expects($this->once())
            ->method('get')
            ->with($configKey)
            ->willReturn($expectedArray);

        $actualValue = $this->configuration->get($configKey);
        $this->assertEquals($expectedArray, $actualValue);
    }

    /**
     * Tests checking if configuration key exists
     */
    public function testHasWithExistingKey(): void
    {
        $configKey = 'existing.key';

        $this->configRepository
            ->expects($this->once())
            ->method('has')
            ->with($configKey)
            ->willReturn(true);

        $result = $this->configuration->has($configKey);
        $this->assertTrue($result);
    }

    /**
     * Tests checking if configuration key does not exist
     */
    public function testHasWithNonExistentKey(): void
    {
        $configKey = 'nonexistent.key';

        $this->configRepository
            ->expects($this->once())
            ->method('has')
            ->with($configKey)
            ->willReturn(false);

        $result = $this->configuration->has($configKey);
        $this->assertFalse($result);
    }

    /**
     * Tests constructor dependency injection
     */
    public function testConstructorDependencyInjection(): void
    {
        /** @var ConfigurationRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $newRepository */
        $newRepository = $this->createMock(ConfigurationRepositoryInterface::class);
        $newConfiguration = new Configuration($newRepository);

        // Test that the new configuration uses the new repository
        $newRepository
            ->expects($this->once())
            ->method('get')
            ->with('test.key')
            ->willReturn('injected-value');

        $result = $newConfiguration->get('test.key');
        $this->assertEquals('injected-value', $result);
    }

    /**
     * Tests delegation behavior - Configuration should delegate all calls
     */
    public function testDelegationBehavior(): void
    {
        $testKey = 'delegation.test.key';
        $testValue = 'delegation-value';

        $this->configRepository
            ->expects($this->once())
            ->method('get')
            ->with($testKey)
            ->willReturn($testValue);

        $this->configRepository
            ->expects($this->once())
            ->method('has')
            ->with($testKey)
            ->willReturn(true);

        $this->assertEquals($testValue, $this->configuration->get($testKey));
        $this->assertTrue($this->configuration->has($testKey));
    }

    /**
     * Tests method calls with different data types
     */
    public function testWithStringDataType(): void
    {
        $this->configRepository
            ->expects($this->once())
            ->method('get')
            ->with('string.key')
            ->willReturn('string-value');

        $this->assertEquals('string-value', $this->configuration->get('string.key'));
    }

    /**
     * Tests method calls with integer data type
     */
    public function testWithIntegerDataType(): void
    {
        $this->configRepository
            ->expects($this->once())
            ->method('get')
            ->with('int.key')
            ->willReturn(123);

        $this->assertEquals(123, $this->configuration->get('int.key'));
    }

    /**
     * Tests method calls with boolean data type
     */
    public function testWithBooleanDataType(): void
    {
        $this->configRepository
            ->expects($this->once())
            ->method('get')
            ->with('bool.key')
            ->willReturn(true);

        $this->assertTrue($this->configuration->get('bool.key'));
    }

    /**
     * Tests method calls with float data type
     */
    public function testWithFloatDataType(): void
    {
        $this->configRepository
            ->expects($this->once())
            ->method('get')
            ->with('float.key')
            ->willReturn(3.14);

        $this->assertEquals(3.14, $this->configuration->get('float.key'));
    }
}