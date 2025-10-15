# Testing Environment Configuration

This directory contains the test suite and testing environment configuration for the Flexi application.

## Overview

The testing environment uses a separate `.env.testing` file to ensure tests run with specific configurations that don't interfere with development or production environments.

## Files

### `bootstrap.php`
PHPUnit bootstrap file that:
- Loads the Composer autoloader
- Loads environment variables from `.env.testing` (with fallback to `.env`)
- Sets the `TESTING_ENVIRONMENT` constant
- Creates necessary test directories (`var/logs`, `var/cache/test`)
- Clears the test cache before running tests

### Configuration Files

#### `.env.testing` (Project Root)
Test-specific environment variables:

```ini
# Application - Testing Environment
debug=true

# Events Dispatch mode -> 0: Synchronous (better for testing)
dispatch_mode=0

# Logger - Testing
log_file_path="./var/logs/test.log"
log_format="[{level} - {time}]: {message} - {context}"
min_log_level="debug"

# Secret - Testing
webhook_secret = "test-web-secret-for-testing"

# Cache - Testing (use in-memory or dummy cache)
cache_driver="memory"
cache_dir="./var/cache/test"
```

## Key Features

### 1. Environment Isolation
- Tests use `.env.testing` instead of `.env`
- ConfigurationRepository uses `safeLoad()`, so test variables won't be overwritten
- `TESTING_ENVIRONMENT` constant allows code to detect test context

### 2. Synchronous Events
- `dispatch_mode=0` ensures events are dispatched synchronously during tests
- Makes tests more predictable and easier to debug
- Avoids race conditions in test assertions

### 3. Separate Logs and Cache
- Test logs go to `var/logs/test.log` instead of `var/logs/app.log`
- Test cache uses `var/cache/test/` directory
- Cache is automatically cleared before each test run

### 4. In-Memory Cache
- `cache_driver="memory"` uses InMemoryCache instead of FileCache
- Faster tests without disk I/O
- No cache persistence between test runs

## Running Tests

```bash
# Run all tests
./vendor/bin/phpunit tests/

# Run specific test file
./vendor/bin/phpunit tests/Infrastructure/TestEnvironmentTest.php

# Run specific test
./vendor/bin/phpunit --filter testTestEnvironmentVariablesAreLoaded

# Run with coverage
./vendor/bin/phpunit tests/ --coverage-html coverage/
```

## Creating New Tests

Tests automatically benefit from the testing environment. No special configuration needed:

```php
<?php

namespace CubaDevOps\Flexi\Test\YourNamespace;

use PHPUnit\Framework\TestCase;

class YourTest extends TestCase
{
    public function testSomething(): void
    {
        // Test code here
        // Environment variables from .env.testing are already loaded
        
        if (defined('TESTING_ENVIRONMENT') && TESTING_ENVIRONMENT) {
            // This code only runs during tests
        }
    }
}
```

## Customizing Test Environment

To add or modify test environment variables:

1. Edit `.env.testing` in the project root
2. Add or modify environment variables
3. Tests will automatically pick up the new values

Example:
```ini
# Add custom test variable
custom_test_setting="test_value"
```

## Test Doubles

The `TestData/TestDoubles/` directory contains test doubles like:

- **DummyCache**: No-op cache implementation for testing without cache interference

Example usage:
```php
$container->set(CacheInterface::class, new DummyCache());
```

## Troubleshooting

### Environment Variables Not Loading

Verify that:
1. `.env.testing` exists in project root
2. Variables are properly formatted (no spaces around `=` for non-string values)
3. The bootstrap file is being executed (check `phpunit.xml`)

### Cache Issues

The bootstrap automatically clears `var/cache/test/` before each test run. If you're experiencing cache-related issues:

1. Manually delete `var/cache/test/` directory
2. Verify `cache_dir` in `.env.testing` points to test directory
3. Consider using `DummyCache` in tests that shouldn't use cache

### Tests Failing After Environment Changes

After modifying `.env.testing`:
1. Clear the test cache: `rm -rf var/cache/test/*`
2. Re-run the tests

## Best Practices

1. **Keep tests isolated**: Don't rely on order of execution
2. **Use synchronous dispatch**: Keeps tests predictable
3. **Clean up after tests**: Use `tearDown()` to clean up any test data
4. **Use test doubles**: Prefer DummyCache and mocks over real implementations
5. **Check TESTING_ENVIRONMENT**: Use the constant to conditionally run code during tests
