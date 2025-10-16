# Refactoring: Separación de Responsabilidades - Route y Router

## Problema Identificado

En la implementación anterior, la clase `Route` (dominio) tenía la responsabilidad de configurar los middlewares en el `HttpHandler` (infraestructura), violando los principios de **Clean Architecture** y **Domain-Driven Design**.

### Código Anterior (Problemático)

```php
// src/Domain/Classes/Route.php
namespace CubaDevOps\Flexi\Domain\Classes;

use CubaDevOps\Flexi\Infrastructure\Classes\HttpHandler; // ❌ Dominio depende de Infraestructura
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
            $handler->setMiddlewares($middlewares); // ❌ Dominio manipula Infraestructura
        }
        return $handler;
    }
}
```

### Problemas Identificados

1. **Violación de Clean Architecture**: El dominio (`Route`) conoce detalles de infraestructura (`HttpHandler`)
2. **Acoplamiento incorrecto**: Dependencia de dominio → infraestructura
3. **Responsabilidad incorrecta**: `Route` debería solo representar el concepto de una ruta
4. **Dificultad de testing**: Más difícil testear por el acoplamiento

## Solución Implementada

### Principio de Diseño Aplicado

**Separation of Concerns (SoC)** + **Dependency Inversion Principle (DIP)**

- **Dominio (Route)**: Solo almacena y representa el concepto de una ruta
- **Infraestructura (Router)**: Orquesta la configuración de middlewares en handlers

### Código Refactorizado

#### 1. Route - Concepto Puro de Dominio

```php
// src/Domain/Classes/Route.php
namespace CubaDevOps\Flexi\Domain\Classes;

class Route // ✅ Sin dependencias de infraestructura
{
    private string $path;
    private string $controller;
    private string $method;
    private array $parameters;
    private string $name;
    private array $middlewares;

    // Solo getters y lógica de dominio pura
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

#### 2. Router - Orquestación en Infraestructura

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

        // ✅ Router (infraestructura) configura middlewares
        if ($route->hasMiddlewares()) {
            $handler = $this->configureMiddlewares($handler, $route);
        }

        return $handler->handle($request);
    }

    /**
     * ✅ Responsabilidad de configuración en la capa de infraestructura
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

## Beneficios del Refactoring

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

- **Route**: Representa el concepto de una ruta (path, controller, method, middlewares)
- **Router**: Orquesta el dispatch de requests y configuración de middlewares

### 3. Testabilidad Mejorada

```php
// Tests de Route más simples - solo dominio
public function testGetMiddlewares()
{
    $route = new Route('test', '/test', 'Controller', 'GET', [], ['Middleware1']);
    $this->assertEquals(['Middleware1'], $route->getMiddlewares());
}

// Tests de Router - orquestación de infraestructura
public function testConfigureMiddlewares()
{
    // Test que el Router configura correctamente los middlewares
    // en el handler
}
```

### 4. Mejor Mantenibilidad

- Cambios en cómo se configuran middlewares: Solo afecta a `Router`
- Cambios en el concepto de Route: Solo afecta a `Route`
- No hay acoplamiento entre capas

### 5. Adherencia a DDD

```php
// Route es ahora un Value Object/Entity puro
$route = new Route(
    name: 'api.users.create',
    path: '/api/users',
    controller: UserController::class,
    method: 'POST',
    parameters: [],
    middlewares: [AuthMiddleware::class, ValidationMiddleware::class]
);

// El dominio no sabe cómo se ejecutan los middlewares
// Solo sabe que la ruta TIENE middlewares
```

## Comparación Antes vs Después

| Aspecto | Antes | Después |
|---------|-------|---------|
| **Acoplamiento** | Dominio → Infraestructura ❌ | Infraestructura → Dominio ✅ |
| **Responsabilidad Route** | Representación + Configuración ❌ | Solo Representación ✅ |
| **Responsabilidad Router** | Solo Dispatch ❌ | Dispatch + Orquestación ✅ |
| **Testing** | Tests complejos y acoplados ❌ | Tests aislados y simples ✅ |
| **Mantenibilidad** | Cambios afectan múltiples capas ❌ | Cambios localizados ✅ |
| **Clean Architecture** | Violada ❌ | Cumplida ✅ |

## Flujo de Ejecución Actualizado

```
1. Request llega al Router
                ↓
2. Router encuentra la Route por path
                ↓
3. Router construye el Handler (Controller)
                ↓
4. Router verifica si Route tiene middlewares
                ↓
5. SI tiene middlewares:
   → Router::configureMiddlewares()
   → Construye instancias de middlewares
   → Configura en HttpHandler
                ↓
6. Router ejecuta handler->handle($request)
                ↓
7. HttpHandler ejecuta cadena de middlewares automáticamente
                ↓
8. Response retorna
```

## Principios SOLID Aplicados

### ✅ Single Responsibility Principle (SRP)
- **Route**: Solo representa una ruta
- **Router**: Solo orquesta el dispatch

### ✅ Open/Closed Principle (OCP)
- Abierto a extensión: Nuevos tipos de handlers
- Cerrado a modificación: La lógica core no cambia

### ✅ Liskov Substitution Principle (LSP)
- Cualquier `RequestHandlerInterface` puede usarse
- `HttpHandler` es una especialización válida

### ✅ Interface Segregation Principle (ISP)
- `Route` no implementa interfaces innecesarias
- Solo expone métodos relevantes

### ✅ Dependency Inversion Principle (DIP)
- Dominio no depende de infraestructura
- Infraestructura depende de abstracciones del dominio

## Testing

### Resultados
```bash
PHPUnit 9.6.29
OK (175 tests, 345 assertions)
```

✅ Todos los tests pasan
✅ Se eliminaron tests obsoletos de `Route::throughMiddlewares()`
✅ La funcionalidad se mantiene intacta

## Conclusión

Este refactoring mejora significativamente la arquitectura del framework:

1. **Cumple con Clean Architecture**: Dependencias apuntan hacia el dominio
2. **Mejor separación de responsabilidades**: Cada clase tiene un propósito claro
3. **Más fácil de mantener**: Cambios localizados
4. **Más fácil de testear**: Menos acoplamiento
5. **Dominio puro**: `Route` es un concepto de dominio limpio

El framework ahora sigue las mejores prácticas de arquitectura de software y diseño orientado al dominio.

---

**Fecha**: 16 de octubre de 2025
**Autor**: Refactoring de arquitectura
**Estado**: ✅ Implementado y Testeado
**Principios**: Clean Architecture, DDD, SOLID
