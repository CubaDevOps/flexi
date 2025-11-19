# Analysis and Improvement of Middleware Management

## 📊 Current Situation Analysis

### Current Architecture

The framework implements a PSR-15 based middleware system with the following components:

1. **`HttpHandler`** (abstract class):
   - Implements `RequestHandlerInterface`
   - Manages a queue (`SplQueue`) of middlewares
   - Provides methods to add middlewares
   - Requires each controller to implement `handle()`

2. **Controllers** (extend `HttpHandler`):
   - `WebHookController`: Manually implements middleware queue logic
   - `HealthController`: Does not implement middlewares (empty queue)
   - `NotFoundController`: Does not implement middlewares (empty queue)

3. **`Router`**:
   - Reads route configuration from JSON
   - Applies middlewares to specific routes via `Route::throughMiddlewares()`
   - Injects middlewares into the handler before executing

### Identified Problems

#### 1. **Mandatory Code Duplication**

Each controller must manually implement this logic:

```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    if (!$this->queue->isEmpty()) {
        return $this->getNextMiddleware()->process($request, $this);
    }

    // Controller logic...
}
```

**Example in `WebHookController`:**
```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    if (!$this->queue->isEmpty()) {
        return $this->getNextMiddleware()->process($request, $this);
    }

    try {
        $payload = $request->getAttribute('payload');
        $this->event_bus->dispatch(new Event($payload->event, $payload->fired_by, (array)($payload->data ?? null)));
    } catch (\Exception $e) {
        return $this->createResponse(400, $e->getMessage());
    }

    return $this->createResponse();
}
```

#### 2. **SOLID Principles Violation**

- **SRP (Single Responsibility)**: The controller must know the internal queue implementation
- **OCP (Open/Closed)**: Cannot change execution strategy without modifying all controllers
- **DRY (Don't Repeat Yourself)**: Repetitive code in each controller

#### 3. **Unnecessary Cognitive Load**

Developers must:
- Remember to implement the queue check
- Know the correct execution order
- Understand how `SplQueue` works
- Correctly implement middleware recursion

#### 4. **Error-Prone**

- Forgetting to check `isEmpty()` causes middlewares not to execute
- Inverting the order of verification breaks the chain
- Not type-safe (the queue can contain anything)

## 💡 Improvement Proposal

### Objective

Implement a transparent middleware execution system that:
1. Fully respects PSR-15
2. Eliminates the need for repetitive code
3. Is automatic and transparent to the developer
4. Maintains backward compatibility

### Solution: Template Method Pattern

Implement the Template Method pattern in `HttpHandler` to automatically manage the middleware chain.

#### Changes in `HttpHandler`

```php
<?php

declare(strict_types=1);

namespace Flexi\Infrastructure\Classes;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class HttpHandler implements RequestHandlerInterface
{
    /**
     * @var RequestFactoryInterface|ResponseFactoryInterface|ServerRequestFactoryInterface|StreamFactoryInterface|UploadedFileFactoryInterface|UriFactoryInterface
     */
    protected $response_factory;

    protected \SplQueue $queue;

    public function __construct()
    {
        $this->queue = new \SplQueue();
        $this->response_factory = new HttpFactory();
    }

    public function setMiddlewares(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            $this->addMiddleware($middleware);
        }
    }

    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->queue->enqueue($middleware);

        return $this;
    }

    /**
     * Template Method: Automatically manages the middleware chain
     * and delegates specific logic to the abstract process() method
     *
     * This method is final to prevent controllers from overriding it
     * and to ensure that the middleware chain always executes correctly.
     */
    final public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->queue->isEmpty()) {
            return $this->getNextMiddleware()->process($request, $this);
        }

        return $this->process($request);
    }

    /**
     * Abstract method that each controller must implement with its specific logic.
     *
     * This method is automatically invoked after all middlewares
     * have been processed.
     *
     * @param ServerRequestInterface $request The HTTP request (potentially modified by middlewares)
     * @return ResponseInterface The HTTP response
     */
    abstract protected function process(ServerRequestInterface $request): ResponseInterface;

    protected function getNextMiddleware(): MiddlewareInterface
    {
        return $this->queue->dequeue();
    }

    protected function createResponse(int $code = 200, string $reasonPhrase = 'OK'): ResponseInterface
    {
        return $this->response_factory->createResponse($code, $reasonPhrase);
    }
}
```

#### Changes in Controllers

**`WebHookController` (BEFORE):**
```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    if (!$this->queue->isEmpty()) {
        return $this->getNextMiddleware()->process($request, $this);
    }

    try {
        /** @var StdClass $payload */
        $payload = $request->getAttribute('payload');
        $this->event_bus->dispatch(new Event($payload->event, $payload->fired_by, (array)($payload->data ?? null)));
    } catch (\Exception $e) {
        return $this->createResponse(400, $e->getMessage());
    }

    return $this->createResponse();
}
```

**`WebHookController` (AFTER):**
```php
protected function process(ServerRequestInterface $request): ResponseInterface
{
    try {
        /** @var StdClass $payload */
        $payload = $request->getAttribute('payload');
        $this->event_bus->dispatch(new Event($payload->event, $payload->fired_by, (array)($payload->data ?? null)));
    } catch (\Exception $e) {
        return $this->createResponse(400, $e->getMessage());
    }

    return $this->createResponse();
}
```

**`HealthController` (BEFORE):**
```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    $version = $this->query_bus->execute(new GetVersionQuery());
    $response = $this->createResponse();
    $response->getBody()->write('Version: '.$version);

    return $response;
}
```

**`HealthController` (AFTER):**
```php
protected function process(ServerRequestInterface $request): ResponseInterface
{
    $version = $this->query_bus->execute(new GetVersionQuery());
    $response = $this->createResponse();
    $response->getBody()->write('Version: '.$version);

    return $response;
}
```

**`NotFoundController` (BEFORE):**
```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    $template = new Template($this->normalize('./src/Infrastructure/Ui/Templates/404.html'));

    $previous_url = $this->session->has('previous_route')
        ? $this->session->get('previous_route')
        : '';
    $body = $this->html_render->render($template, [
        'request' => $previous_url,
    ]);
    $this->logger->log(LogLevel::NOTICE, 'Page not found', [
        $previous_url,
        __CLASS__,
    ]);
    $this->session->remove('previous_route');
    $response = $this->createResponse(404);
    $response->getBody()->write($body);

    return $response;
}
```

**`NotFoundController` (AFTER):**
```php
protected function process(ServerRequestInterface $request): ResponseInterface
{
    $template = new Template($this->normalize('./src/Infrastructure/Ui/Templates/404.html'));

    $previous_url = $this->session->has('previous_route')
        ? $this->session->get('previous_route')
        : '';
    $body = $this->html_render->render($template, [
        'request' => $previous_url,
    ]);
    $this->logger->log(LogLevel::NOTICE, 'Page not found', [
        $previous_url,
        __CLASS__,
    ]);
    $this->session->remove('previous_route');
    $response = $this->createResponse(404);
    $response->getBody()->write($body);

    return $response;
}
```

## 🎯 Improvement Benefits

### 1. **Simplicity**
- Developers only write business logic
- No need to know the internal middleware implementation
- Reduces boilerplate code by 100%

### 2. **PSR-15 Compliance**
- `handle()` remains the public entry point (PSR-15)
- Internal implementation respects middleware flow
- Fully compatible with the standard

### 3. **Maintainability**
- Single place to change middleware execution logic
- Easier to test
- Less error-prone

### 4. **SOLID Principles**
- **SRP**: Controllers only handle their logic
- **OCP**: Can be extended without modifying existing controllers
- **LSP**: All controllers behave consistently
- **DIP**: Controllers depend on abstractions, not implementations

### 5. **Type Safety**
- The `process()` method has a clear and documented signature
- No ambiguity about what each controller should implement

### 6. **Clear Documentation**
- PHPDoc explains the automatic flow
- Developers immediately understand what to do

## 🔄 Execution Flow

### Before
```
Router → Handler::handle()
         ↓
         Developer manually checks isEmpty()
         ↓
         If middlewares exist → getNextMiddleware()->process()
         ↓
         Middleware → Handler::handle() (recursion)
         ↓
         Controller logic
```

### After
```
Router → Handler::handle() (final)
         ↓
         Automatic isEmpty() check
         ↓
         If middlewares exist → getNextMiddleware()->process()
         ↓
         Middleware → Handler::handle() (automatic recursion)
         ↓
         Handler::process() (abstract method implemented by controller)
         ↓
         Controller logic
```

## 📝 Implementation Plan

### Phase 1: Update `HttpHandler`
1. Make `handle()` final with automatic middleware logic
2. Create abstract `process()` method
3. Update PHPDoc documentation

### Phase 2: Update Existing Controllers
1. `WebHookController`: Change `handle()` to `process()`
2. `HealthController`: Change `handle()` to `process()`
3. `NotFoundController`: Change `handle()` to `process()`

### Phase 3: Testing
1. Run existing tests
2. Create specific tests for middleware chain
3. Verify that all controllers work correctly

### Phase 4: Documentation
1. Update framework documentation
2. Create migration guide for developers
3. Update examples in README

## 🧪 Recommended Tests

```php
// Test that verifies middlewares are executed automatically
public function testMiddlewaresAreExecutedAutomatically(): void
{
    $middleware = new TestMiddleware();
    $controller = new TestController();
    $controller->addMiddleware($middleware);

    $request = $this->createMock(ServerRequestInterface::class);
    $response = $controller->handle($request);

    $this->assertTrue($middleware->wasExecuted());
}

// Test that verifies process() is called after middlewares
public function testProcessIsCalledAfterMiddlewares(): void
{
    $middleware = new TestMiddleware();
    $controller = new TestController();
    $controller->addMiddleware($middleware);

    $request = $this->createMock(ServerRequestInterface::class);
    $controller->handle($request);

    $this->assertTrue($controller->processWasCalled());
}

// Test that verifies process() is called directly without middlewares
public function testProcessIsCalledDirectlyWithoutMiddlewares(): void
{
    $controller = new TestController();
    $request = $this->createMock(ServerRequestInterface::class);

    $controller->handle($request);

    $this->assertTrue($controller->processWasCalled());
}
```

## 🎓 Conclusion

This improvement transforms middleware management from a manual and repetitive process to an automatic and transparent one, maintaining PSR-15 compatibility and significantly improving the developer experience. The framework becomes more robust, maintainable, and easy to use without sacrificing flexibility or power.
