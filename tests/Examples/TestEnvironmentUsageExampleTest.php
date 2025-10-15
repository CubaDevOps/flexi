<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Examples;

use CubaDevOps\Flexi\Infrastructure\Classes\ConfigurationRepository;
use PHPUnit\Framework\TestCase;

/**
 * Example test demonstrating how to use the testing environment
 * 
 * This is a practical example showing how the testing environment
 * automatically provides test-specific configurations.
 */
class TestEnvironmentUsageExampleTest extends TestCase
{
    private ConfigurationRepository $config;

    public function setUp(): void
    {
        $this->config = new ConfigurationRepository();
    }

    /**
     * Example: Verify that events are dispatched synchronously in tests
     * 
     * This is important because synchronous dispatch makes tests predictable.
     * You can assert on event handlers' side effects immediately.
     */
    public function testEventsAreSynchronousInTests(): void
    {
        $dispatchMode = $this->config->get('dispatch_mode');
        
        // In testing, dispatch_mode should be 0 (synchronous)
        $this->assertEquals('0', $dispatchMode);
        
        // This means:
        // - Events are processed immediately
        // - No race conditions in tests
        // - Side effects are visible right away
    }

    /**
     * Example: Verify that tests use in-memory cache
     * 
     * In-memory cache is faster and doesn't persist between test runs.
     */
    public function testUsesInMemoryCache(): void
    {
        $cacheDriver = $this->config->get('cache_driver');
        
        // Tests should use memory cache for speed and isolation
        $this->assertEquals('memory', $cacheDriver);
        
        // Benefits:
        // - No disk I/O
        // - Faster tests
        // - No cache pollution between tests
    }

    /**
     * Example: Verify that test logs go to separate file
     * 
     * Separate logs make it easier to debug test failures.
     */
    public function testUsesTestLogFile(): void
    {
        $logPath = $this->config->get('log_file_path');
        
        // Tests should write to test.log, not app.log
        $this->assertStringContainsString('test.log', $logPath);
        
        // Benefits:
        // - Don't pollute development logs
        // - Easy to find test-related logs
        // - Can be cleaned up after tests
    }

    /**
     * Example: Detect if running in test environment
     * 
     * Sometimes you need to know if code is running in tests.
     */
    public function testCanDetectTestEnvironment(): void
    {
        // The TESTING_ENVIRONMENT constant is only defined during tests
        $this->assertTrue(defined('TESTING_ENVIRONMENT'));
        $this->assertTrue(TESTING_ENVIRONMENT);
        
        // Use case: conditionally enable/disable features in tests
        if (defined('TESTING_ENVIRONMENT') && TESTING_ENVIRONMENT) {
            // This code only runs during tests
            $this->assertTrue(true);
        }
    }

    /**
     * Example: Custom test configuration
     * 
     * You can add custom variables to .env.testing for your tests.
     */
    public function testCanAccessCustomTestVariables(): void
    {
        // If you add "custom_test_var=test_value" to .env.testing,
        // you can access it like this:
        $customVar = getenv('webhook_secret');
        
        // This should be the test-specific webhook secret
        $this->assertNotFalse($customVar);
        $this->assertEquals('test-web-secret-for-testing', $customVar);
    }

    /**
     * Example: Debug mode is always enabled in tests
     */
    public function testDebugModeIsEnabled(): void
    {
        $debugMode = $this->config->get('DEBUG_MODE');
        
        // Debug should always be enabled during tests for better error messages
        $this->assertEquals('true', $debugMode);
    }
}
