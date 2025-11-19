# Refactoring: Separation of Responsibilities - Route and Router

## Identified Problem

In the previous implementation, the `Route` class (domain) had the responsibility of configuring middlewares in the `HttpHandler` (infrastructure), violating **Clean Architecture** and **Domain-Driven Design** principles.

### Previous Code (Problematic)

```php
// src/Domain/Classes/Route.php
namespace Flexi\Domain\Classes;

use Flexi\Infrastructure\Classes\HttpHandler; // ❌ Domain depends on Infrastructure
use Psr\Container\ContainerInterface;

class Route
{
    public function throughMiddlewares(
        ContainerInterface $container,
        ObjectBuilderInterface $factory,
        RequestHandlerInterface $handler
    ): RequestHandlerInterface {
        if ($handler instanceof HttpHandler && $this->hasMiddlewares()) {
            $middlewares = [];
            foreach ($this->getMiddlewares() as $middleware) {
                $middlewares[] = $factory->build($container, $middleware);
            }
            $handler->setMiddlewares($middlewares); // ❌ Domain manipulates Infrastructure
        }
        return $handler;
    }
}
```

### Identified Problems

1. **Clean Architecture Violation**: Domain (`Route`) knows infrastructure details (`HttpHandler`)
2. **Incorrect coupling**: Domain dependency → infrastructure
3. **Incorrect responsibility**: `Route` should only represent the route concept
4. **Testing difficulty**: Harder to test due to coupling

## Implemented Solution

### Applied Design Principle

**Separation of Concerns (SoC)** + **Dependency Inversion Principle (DIP)**

- **Domain (Route)**: Only stores and represents the route concept
- **Infrastructure (Router)**: Orchestrates middleware configuration in handlers

### Refactored Code

#### 1. Route - Pure Domain Concept

```php
// src/Domain/Classes/Route.php
namespace Flexi\Domain\Classes;

class Route // ✅ No infrastructure dependencies
{
    private string $path;
    private string $controller;
    private string $method;
    private array $parameters;
    private string $name;
    private array $middlewares;

    // Only getters and pure domain logic
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function hasMiddlewares(): bool
    {
        return !empty($this->middlewares);
    }
}
```

#### 2. Router - Infrastructure Orchestration

```php
// src/Infrastructure/Http/Router.php
class Router
{
    private function executeStack(
        Route $route,
        ServerRequestInterface $request
    ): ResponseInterface {
        $handler = $this->class_factory->build(
            $this->container,
            $route->getController()
        );

        // ✅ Router (infrastructure) configures middlewares
        if ($route->hasMiddlewares()) {
            $handler = $this->configureMiddlewares($handler, $route);
        }

        return $handler->handle($request);
    }

    /**
     * ✅ Configuration responsibility in infrastructure layer
     */
    private function configureMiddlewares(
        RequestHandlerInterface $handler,
        Route $route
    ): RequestHandlerInterface {
        if (!$handler instanceof HttpHandler) {
            return $handler;
        }

        $middlewares = [];
        foreach ($route->getMiddlewares() as $middlewareClass) {
            $middlewares[] = $this->class_factory->build(
                $this->container,
                $middlewareClass
            );
        }

        $handler->setMiddlewares($middlewares);

        return $handler;
    }
}
```

## Refactoring Benefits

### 1. Clean Architecture Compliance

```
┌─────────────────────────────────────────┐
│           Domain Layer                  │
│  ┌─────────────────────────────────┐   │
│  │  Route (Pure Domain Entity)     │   │
│  │  - No infrastructure deps       │   │
│  │  - Only business logic          │   │
│  └─────────────────────────────────┘   │
└─────────────────────────────────────────┘
                   ↑
                   │ No dependencies
                   │
┌──────────────────┴──────────────────────┐
│       Infrastructure Layer              │
│  ┌─────────────────────────────────┐   │
│  │  Router (Orchestrator)          │   │
│  │  - Depends on Domain            │   │
│  │  - Configures middlewares       │   │
│  └─────────────────────────────────┘   │
└─────────────────────────────────────────┘
```

### 2. Single Responsibility Principle

- **Route**: Represents the route concept (path, controller, method, middlewares)
- **Router**: Orchestrates request dispatch and middleware configuration

### 3. Improved Testability

```php
// Route tests simpler - domain only
public function testGetMiddlewares()
{
    $route = new Route('test', '/test', 'Controller', 'GET', [], ['Middleware1']);
    $this->assertEquals(['Middleware1'], $route->getMiddlewares());
}

// Router tests - infrastructure orchestration
public function testConfigureMiddlewares()
{
    // Test that Router correctly configures middlewares
    // in the handler
}
```

### 4. Better Maintainability

- Changes in how middlewares are configured: Only affects `Router`
- Changes in Route concept: Only affects `Route`
- No coupling between layers

### 5. DDD Adherence

```php
// Route is now a pure Value Object/Entity
$route = new Route(
    name: 'api.users.create',
    path: '/api/users',
    controller: UserController::class,
    method: 'POST',
    parameters: [],
    middlewares: [AuthMiddleware::class, ValidationMiddleware::class]
);

// Domain doesn't know how middlewares are executed
// Only knows that the route HAS middlewares
```

## Before vs After Comparison

| Aspect | Before | After |
|---------|-------|---------|
| **Coupling** | Domain → Infrastructure ❌ | Infrastructure → Domain ✅ |
| **Route Responsibility** | Representation + Configuration ❌ | Only Representation ✅ |
| **Router Responsibility** | Only Dispatch ❌ | Dispatch + Orchestration ✅ |
| **Testing** | Complex and coupled tests ❌ | Isolated and simple tests ✅ |
| **Maintainability** | Changes affect multiple layers ❌ | Localized changes ✅ |
| **Clean Architecture** | Violated ❌ | Compliant ✅ |

## Updated Execution Flow

```
1. Request arrives at Router
                ↓
2. Router finds Route by path
                ↓
3. Router builds Handler (Controller)
                ↓
4. Router verifies if Route has middlewares
                ↓
5. IF has middlewares:
   → Router::configureMiddlewares()
   → Builds middleware instances
   → Configures in HttpHandler
                ↓
6. Router executes handler->handle($request)
                ↓
7. HttpHandler executes middleware chain automatically
                ↓
8. Response returns
```

## Applied SOLID Principles

### ✅ Single Responsibility Principle (SRP)
- **Route**: Solo representa una ruta
- **Router**: Solo orquesta el dispatch

### ✅ Open/Closed Principle (OCP)
- Open to extension: New handler types
- Closed to modification: Core logic doesn't change

### ✅ Liskov Substitution Principle (LSP)
- Any `RequestHandlerInterface` can be used
- `HttpHandler` is a valid specialization

### ✅ Interface Segregation Principle (ISP)
- `Route` doesn't implement unnecessary interfaces
- Only exposes relevant methods

### ✅ Dependency Inversion Principle (DIP)
- Domain doesn't depend on infrastructure
- Infrastructure depends on domain abstractions

## Testing

### Results
```bash
PHPUnit 9.6.29
OK (175 tests, 345 assertions)
```

✅ All tests pass
✅ Obsolete `Route::throughMiddlewares()` tests removed
✅ Functionality remains intact

## Conclusion

This refactoring significantly improves the framework's architecture:

1. **Complies with Clean Architecture**: Dependencies point toward domain
2. **Better separation of responsibilities**: Each class has a clear purpose
3. **Easier to maintain**: Localized changes
4. **Easier to test**: Less coupling
5. **Pure domain**: `Route` is a clean domain concept

The framework now follows software architecture and domain-driven design best practices.

---

**Date**: October 16, 2025
**Author**: Architecture refactoring
**Status**: ✅ Implemented and Tested
**Principles**: Clean Architecture, DDD, SOLID

````
