<?php

declare(strict_types=1);

/**
 * PHPUnit Bootstrap for Testing Environment
 *
 * This bootstrap file is executed before running tests.
 * It loads the .env.testing file instead of .env to ensure
 * test-specific configuration is used.
 */

// Load Composer autoloader (one level up from tests directory)
require_once __DIR__ . '/../vendor/autoload.php';

// Set testing flag first
if (!defined('TESTING_ENVIRONMENT')) {
    define('TESTING_ENVIRONMENT', true);
}

// Load test environment variables
// Using createUnsafeImmutable to match ConfigurationRepository behavior
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..', '.env.testing');
try {
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    // If .env.testing doesn't exist, use .env as fallback
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..', '.env');
    $dotenv->safeLoad();
}

// Ensure test directories exist (relative to project root)
$rootDir = dirname(__DIR__);
$testDirs = [
    $rootDir . '/var/logs',
    $rootDir . '/var/cache/test',
];

foreach ($testDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Clear test cache before running tests
$testCacheDir = $rootDir . '/var/cache/test';
if (is_dir($testCacheDir)) {
    $files = glob($testCacheDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}
