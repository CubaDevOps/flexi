# HttpHandler Dependency Injection Pattern

## Overview

The `HttpHandler` abstract class has been refactored to follow strict dependency injection principles, eliminating direct dependencies on concrete HTTP library implementations (like GuzzleHttp). This ensures the Contracts package remains truly PSR-standards-only and improves testability.

## Problem Statement

Previously, `HttpHandler` in the Contracts package directly instantiated `new HttpFactory()` from GuzzleHttp:

```php
// BEFORE: Direct dependency on GuzzleHttp
use GuzzleHttp\Psr7\HttpFactory;

class HttpHandler implements RequestHandlerInterface {
    private HttpFactory $factory;

    public function __construct() {
        $this->factory = new HttpFactory();  // Direct instantiation
    }
}
```

**Issues:**
1. **Violation of Dependency Inversion Principle**: High-level module (HttpHandler) depended on low-level module (GuzzleHttp)
2. **Testability Problem**: Tests couldn't easily replace the factory implementation
3. **Package Coupling**: Contracts package should only depend on PSR standards, not framework-specific libraries
4. **Classpath Issues**: contracts/composer.json didn't list GuzzleHttp, creating potential runtime failures

## Solution: Constructor Injection

The `HttpHandler` now requires a `ResponseFactoryInterface` dependency injected via constructor:

```php
// AFTER: Injected dependency via constructor
use Psr\Http\Message\ResponseFactoryInterface;

class HttpHandler implements RequestHandlerInterface {
    private ResponseFactoryInterface $response_factory;

    public function __construct(ResponseFactoryInterface $response_factory) {
        $this->response_factory = $response_factory;
    }
}
```

**Benefits:**
1. ✅ **Dependency Inversion**: Depends on PSR interfaces, not concrete implementations
2. ✅ **Testability**: Easy to inject test doubles or mocks
3. ✅ **Package Purity**: Contracts contains only PSR standard interfaces
4. ✅ **Flexibility**: Different implementations can be used (Guzzle, PSR-17, or custom)

## Implementation Details

### HttpHandler Base Class

Location: `contracts/src/Classes/HttpHandler.php`

```php
namespace Flexi\Contracts\Classes;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SplQueue;

abstract class HttpHandler implements RequestHandlerInterface
{
    protected SplQueue $queue;
    protected ResponseFactoryInterface $response_factory;

    public function __construct(ResponseFactoryInterface $response_factory)
    {
        $this->queue = new SplQueue();
        $this->response_factory = $response_factory;
    }

    // ... rest of implementation
}
```

### Subclass Pattern (Controllers)

All controllers extending `HttpHandler` must pass the dependency to parent constructor:

```php
namespace CubaDevOps\Flexi\Modules\Home\Infrastructure\Controllers;

use Flexi\Contracts\Classes\HttpHandler;
use Psr\Http\Message\ResponseFactoryInterface;

class HomeController extends HttpHandler
{
    private QueryBus $query_bus;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        QueryBus $query_bus
    ) {
        parent::__construct($response_factory);
        $this->query_bus = $query_bus;
    }
}
```

### Affected Controllers

1. **modules/Home/Infrastructure/Controllers/HomeController.php**
2. **modules/WebHooks/Infrastructure/Controllers/WebHookController.php**
3. **modules/HealthCheck/Infrastructure/Controllers/HealthController.php**
4. **modules/ErrorHandling/Infrastructure/Controllers/NotFoundController.php**

## Test Double Implementation

### Pure PSR Test Doubles

To eliminate GuzzleHttp dependency from tests, pure PSR implementations were created:

#### DummyStream (StreamInterface)

Location: `tests/TestData/TestDoubles/DummyStream.php`

A complete PSR-7 StreamInterface implementation without external dependencies:

```php
class DummyStream implements StreamInterface
{
    private string $content = '';
    private int $position = 0;
    private bool $readable = true;
    private bool $writable = true;

    public function read($length): string { /* ... */ }
    public function write($string): int { /* ... */ }
    public function seek($offset, $whence = SEEK_SET): void { /* ... */ }
    public function tell(): int { return $this->position; }
    public function eof(): bool { /* ... */ }
    public function getContents(): string { /* ... */ }
    public function getMetadata($key = null) { /* ... */ }
}
```

#### DummyResponse (ResponseInterface)

Location: `tests/TestData/TestDoubles/DummyResponse.php`

A complete PSR-7 ResponseInterface implementation:

```php
class DummyResponse implements ResponseInterface
{
    private int $statusCode;
    private array $headers;
    private StreamInterface $body;
    private string $protocolVersion;
    private string $reasonPhrase;

    public function getStatusCode(): int { /* ... */ }
    public function withStatus($code, $reasonPhrase = ''): ResponseInterface { /* ... */ }
    public function getHeaders(): array { /* ... */ }
    public function withHeader($name, $value): ResponseInterface { /* ... */ }
    public function getBody(): StreamInterface { /* ... */ }
    // ... all other ResponseInterface methods
}
```

#### DummyResponseFactory (ResponseFactoryInterface)

Location: `tests/TestData/TestDoubles/DummyResponseFactory.php`

Replaced GuzzleHttp's HttpFactory with pure PSR implementation:

```php
class DummyResponseFactory implements ResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new DummyResponse($code, [], null, '1.1', $reasonPhrase);
    }
}
```

### TestHttpHandler Enhancement

Location: `tests/TestData/TestDoubles/TestHttpHandler.php`

Enhanced with optional constructor parameter and default factory:

```php
class TestHttpHandler extends HttpHandler
{
    public function __construct(?ResponseFactoryInterface $response_factory = null)
    {
        parent::__construct($response_factory ?? new DummyResponseFactory());
    }
}
```

**Benefits:**
- Can be instantiated with or without explicit factory
- Defaults to DummyResponseFactory (pure PSR, no external dependencies)
- Backward compatible with existing test code

## Test Updates

The following test files were updated to use pure PSR test doubles:

1. **tests/Infrastructure/Http/RouterTest.php**
   - Updated: Pass `$this->response_factory` to TestHttpHandler
   - Uses: Existing ResponseFactoryInterface mock

2. **modules/Auth/tests/Infrastructure/Middlewares/JWTAuthMiddlewareTest.php**
   - Updated: Use DummyResponseFactory instead of mocking
   - Imports: Removed GuzzleHttp\Psr7\Response

3. **modules/WebHooks/tests/Infrastructure/Controllers/WebHookControllerTest.php**
   - Updated: Use DummyResponseFactory instead of mocking
   - Imports: Removed GuzzleHttp dependency

## Validation

✅ **All 168 tests passing** with new implementation
✅ **No GuzzleHttp imports** in Contracts package
✅ **No GuzzleHttp imports** in test code
✅ **All 4 controllers** correctly implement dependency injection
✅ **Pure PSR test doubles** fully functional and tested

## Migration Guide

### For Production Code

If creating a new controller extending HttpHandler:

```php
use Flexi\Contracts\Classes\HttpHandler;
use Psr\Http\Message\ResponseFactoryInterface;

class MyNewController extends HttpHandler
{
    public function __construct(ResponseFactoryInterface $response_factory)
    {
        parent::__construct($response_factory);
    }
}
```

Then wire it in your container/factory with a ResponseFactoryInterface implementation:

```php
// Using Guzzle in production
$factory = new GuzzleHttp\Psr7\HttpFactory();
$controller = new MyNewController($factory);

// Or any other PSR-17 implementation
$factory = new MyCustomResponseFactory();
$controller = new MyNewController($factory);
```

### For Test Code

Use DummyResponseFactory for pure PSR testing without external dependencies:

```php
use CubaDevOps\Flexi\Test\TestData\TestDoubles\DummyResponseFactory;
use CubaDevOps\Flexi\Test\TestData\TestDoubles\TestHttpHandler;

$factory = new DummyResponseFactory();
$handler = new TestHttpHandler($factory);
```

Or use mocks for more control:

```php
$factory = $this->createMock(ResponseFactoryInterface::class);
$factory->method('createResponse')->willReturn(new DummyResponse(200));
$handler = new TestHttpHandler($factory);
```

## Architecture Benefits

1. **Separation of Concerns**: Contracts package isolated from implementation details
2. **Testability**: Multiple testing strategies (doubles, mocks, real implementations)
3. **Flexibility**: Easy to swap implementations (Guzzle, Laminas, custom, etc.)
4. **Maintainability**: Clear dependency graph, easier to understand and modify
5. **Reusability**: Test doubles can be used across multiple test suites
6. **Standards Compliance**: Pure PSR implementation in Contracts ensures compliance

## Related Documentation

- PSR-7: HTTP Message Interfaces
- PSR-15: HTTP Server Request Handlers
- PSR-17: HTTP Factories
- Dependency Injection Pattern
- Test Doubles Pattern
