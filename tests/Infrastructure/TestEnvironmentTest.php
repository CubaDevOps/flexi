<?php

declare(strict_types=1);

namespace Flexi\Test\Infrastructure;

use PHPUnit\Framework\TestCase;

/**
 * Test to verify that .env.testing is loaded correctly during tests.
 */
class TestEnvironmentTest extends TestCase
{
    /**
     * Verify that TESTING_ENVIRONMENT constant is defined.
     */
    public function testTestingEnvironmentIsSet(): void
    {
        $this->assertTrue(defined('TESTING_ENVIRONMENT'));
        $this->assertTrue(TESTING_ENVIRONMENT);
    }

    /**
     * Verify that test-specific environment variables are loaded.
     */
    public function testTestEnvironmentVariablesAreLoaded(): void
    {
        // Verify debug is enabled in tests
        $debug = getenv('debug');
        $this->assertNotFalse($debug, 'debug variable should be set');
        $this->assertEquals('true', $debug);

        // Verify dispatch_mode is synchronous (0) for tests
        $dispatchMode = getenv('dispatch_mode');
        $this->assertNotFalse($dispatchMode, 'dispatch_mode variable should be set');
        $this->assertEquals('0', $dispatchMode, 'Tests should use synchronous dispatch mode');

        // Verify test-specific log file path
        $logPath = getenv('log_file_path');
        $this->assertNotFalse($logPath, 'log_file_path variable should be set');
        $this->assertStringContainsString('test.log', $logPath);

        // Verify test-specific cache driver
        $cacheDriver = getenv('cache_driver');
        $this->assertNotFalse($cacheDriver, 'cache_driver variable should be set');
        $this->assertEquals('memory', $cacheDriver, 'Tests should use in-memory cache');
    }

    /**
     * Verify that test directories are created.
     */
    public function testTestDirectoriesExist(): void
    {
        $rootDir = dirname(__DIR__, 2);

        $this->assertDirectoryExists($rootDir.'/var/logs');
        $this->assertDirectoryExists($rootDir.'/var/cache/test');
        $this->assertDirectoryIsWritable($rootDir.'/var/logs');
        $this->assertDirectoryIsWritable($rootDir.'/var/cache/test');
    }

    /**
     * Verify that environment variables are available in $_ENV.
     */
    public function testEnvironmentVariablesInEnvArray(): void
    {
        $this->assertArrayHasKey('debug', $_ENV);
        $this->assertArrayHasKey('dispatch_mode', $_ENV);
        $this->assertArrayHasKey('log_file_path', $_ENV);
        $this->assertArrayHasKey('cache_driver', $_ENV);
    }
}
