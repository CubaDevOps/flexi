# Analysis and Improvements in Middleware Management

## Current State

### Identified Problem

In the current framework implementation, each controller that extends `HttpHandler` must know and manually implement the queue mechanism to manage middlewares:

```php
// Before: The developer had to do this in each controller
public function handle(ServerRequestInterface $request): ResponseInterface
{
    if (!$this->queue->isEmpty()) {
        return $this->getNextMiddleware()->process($request, $this);
    }
    return $this->implementActualLogic($request);
}
```

### Problems

1. **DRY Violation**: Repetitive code in each controller
2. **Unnecessary technical knowledge**: The developer must understand the chain of responsibility pattern
3. **Error-prone**: Easy to forget to implement the chain or do it incorrectly
4. **Difficult to maintain**: Changes to the mechanism require modifying all controllers

## Implemented Solution

### Template Method Pattern

The **Template Method pattern** was implemented to encapsulate the middleware management logic in the `HttpHandler` base class:

```php
abstract class HttpHandler implements RequestHandlerInterface
{
    protected \SplQueue $queue;

    /**
     * FINAL method that automatically manages the middleware chain
     * This method is final to guarantee that the chain always executes correctly
     */
    final public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->queue->isEmpty()) {
            return $this->getNextMiddleware()->process($request, $this);
        }

        return $this->process($request);
    }

    /**
     * Abstract method that each controller must implement
     * with its specific logic
     */
    abstract protected function process(ServerRequestInterface $request): ResponseInterface;
}
```

### Solution Benefits

1. **Complies with PSR-7 and PSR-15**:
   - `handle()` is public and complies with `RequestHandlerInterface`
   - Middlewares continue implementing `MiddlewareInterface`

2. **Simplicity for the developer**:
```php
// Now: The developer only implements business logic
class WebHookController extends HttpHandler
{
    protected function process(ServerRequestInterface $request): ResponseInterface
    {
        // Only business logic, without worrying about middlewares
        $payload = $request->getAttribute('payload');
        $this->event_bus->dispatch(new Event($payload->event, ...));
        return $this->createResponse();
    }
}
```

3. **Automatic middleware management**:
```php
// The chain is built automatically from Route
$route = new Route(
    'trigger-event',
    '/trigger-event',
    WebHookController::class,
    'POST',
    [],
    [JWTAuthMiddleware::class] // Declarative middlewares
);
```

4. **Flow immutability**: By declaring `handle()` as `final`, we guarantee that:
   - No controller can skip the middleware chain
   - Execution order is predictable and consistent
   - Security and validations cannot be bypassed

### Solution Architecture

```
Request
   ↓
Router::dispatch()
   ↓
Route::throughMiddlewares() → Configures middlewares in HttpHandler
   ↓
HttpHandler::handle() [FINAL] → Executes middleware chain
   ↓
Middleware 1 → process($request, $handler)
   ↓
Middleware 2 → process($request, $handler)
   ↓
Middleware N → process($request, $handler)
   ↓
HttpHandler::handle() → (empty queue)
   ↓
ConcreteController::process() → Business logic
   ↓
Response
```

## Updated Controllers

All framework controllers were updated to use the new pattern:

### 1. HealthController
```php
class HealthController extends HttpHandler
{
    protected function process(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->createResponse();
        $response->getBody()->write(json_encode(['status' => 'ok']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
```

### 2. NotFoundController
```php
class NotFoundController extends HttpHandler
{
    protected function process(ServerRequestInterface $request): ResponseInterface
    {
        $previous_route = $this->session->get('previous_route') ?? '/';
        // ... specific logic
        return $response;
    }
}
```

### 3. WebHookController
```php
class WebHookController extends HttpHandler
{
    protected function process(ServerRequestInterface $request): ResponseInterface
    {
        $payload = $request->getAttribute('payload');
        $this->event_bus->dispatch(new Event(...));
        return $this->createResponse();
    }
}
```

### 4. HomeController (Module)
```php
class HomeController extends HttpHandler
{
    protected function process(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->createResponse();
        $html = $this->template_engine->render('home/index.html');
        $response->getBody()->write($html);
        return $response;
    }
}
```

## PSR Standards Compatibility

### PSR-7: HTTP Message Interface
✅ `ServerRequestInterface` remains as input type
✅ `ResponseInterface` remains as output type

### PSR-15: HTTP Server Request Handlers
✅ `RequestHandlerInterface::handle()` is public and accessible
✅ `MiddlewareInterface::process()` follows the standard contract
✅ The middleware chain works according to the standard

```php
// Standard PSR-15 middleware
class JWTAuthMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Validations before
        if (!$this->isValid($request)) {
            return $this->createUnauthorizedResponse();
        }

        // Continue chain
        return $handler->handle($request);
    }
}
```

## Testing

Tests were updated to reflect the new behavior:

### TestHttpHandler (Test Double)
```php
class TestHttpHandler extends HttpHandler
{
    private ?ResponseInterface $mockResponse = null;

    public function setMockResponse(ResponseInterface $response): void
    {
        $this->mockResponse = $response;
    }

    protected function process(ServerRequestInterface $request): ResponseInterface
    {
        return $this->mockResponse ?? $this->createResponse(200, 'OK');
    }
}
```

### Results
```
PHPUnit 9.6.29
OK (177 tests, 350 assertions)
```

✅ All tests pass successfully

## Conclusions

### Implementation Advantages

1. **Transparency**: Developers don't need to know the internal implementation
2. **Security**: The middleware chain cannot be bypassed
3. **Maintainability**: Changes to the mechanism only affect `HttpHandler`
4. **Standards compliance**: Full compatibility with PSR-7 and PSR-15
5. **Simplicity**: Cleaner and easier to understand code

### Complete Usage Example

```php
// 1. Define route with middlewares (routes.json)
{
  "name": "protected-endpoint",
  "path": "/api/protected",
  "method": "POST",
  "controller": "App\\Controllers\\ProtectedController",
  "middlewares": [
    "App\\Middlewares\\AuthMiddleware",
    "App\\Middlewares\\RateLimitMiddleware"
  ]
}

// 2. Implement controller (business logic only)
class ProtectedController extends HttpHandler
{
    protected function process(ServerRequestInterface $request): ResponseInterface
    {
        // Middlewares already validated authentication and rate limiting
        $data = $this->businessLogic->execute($request);
        return $this->createJsonResponse($data);
    }
}

// 3. Done! The framework manages everything automatically
```

## Future Recommendations

1. **Documentation**: Create user guide explaining the pattern
2. **Middleware Generator**: CLI to generate standard middlewares
3. **Middleware Pipeline Visualization**: Tool to visualize the chain
4. **Performance Monitoring**: Metrics per middleware
5. **Route Caching**: Cache compiled route configuration

---

**Implementation Date**: October 16, 2025
**Author**: Architecture analysis and improvement
**Status**: ✅ Implemented and Tested
