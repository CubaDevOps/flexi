# Análisis y Mejora de la Gestión de Middlewares

## 📊 Análisis de la Situación Actual

### Arquitectura Actual

El framework implementa un sistema de middlewares basado en PSR-15 con los siguientes componentes:

1. **`HttpHandler`** (clase abstracta):
   - Implementa `RequestHandlerInterface`
   - Gestiona una cola (`SplQueue`) de middlewares
   - Proporciona métodos para agregar middlewares
   - Requiere que cada controlador implemente `handle()`

2. **Controladores** (extienden `HttpHandler`):
   - `WebHookController`: Implementa manualmente la lógica de cola de middlewares
   - `HealthController`: No implementa middlewares (cola vacía)
   - `NotFoundController`: No implementa middlewares (cola vacía)

3. **`Router`**:
   - Lee la configuración de rutas desde JSON
   - Aplica middlewares a rutas específicas mediante `Route::throughMiddlewares()`
   - Inyecta middlewares en el handler antes de ejecutar

### Problemas Identificados

#### 1. **Duplicación de Código Obligatoria**

Cada controlador debe implementar manualmente esta lógica:

```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    if (!$this->queue->isEmpty()) {
        return $this->getNextMiddleware()->process($request, $this);
    }

    // Lógica del controlador...
}
```

**Ejemplo en `WebHookController`:**
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

#### 2. **Violación de Principios SOLID**

- **SRP (Single Responsibility)**: El controlador debe conocer la implementación interna de la cola
- **OCP (Open/Closed)**: No se puede cambiar la estrategia de ejecución sin modificar todos los controladores
- **DRY (Don't Repeat Yourself)**: Código repetitivo en cada controlador

#### 3. **Carga Cognitiva Innecesaria**

Los desarrolladores deben:
- Recordar implementar la verificación de la cola
- Conocer el orden correcto de ejecución
- Entender cómo funciona `SplQueue`
- Implementar correctamente la recursión del middleware

#### 4. **Propenso a Errores**

- Olvidar verificar `isEmpty()` causa que los middlewares no se ejecuten
- Invertir el orden de la verificación rompe la cadena
- No es type-safe (la cola puede contener cualquier cosa)

## 💡 Propuesta de Mejora

### Objetivo

Implementar un sistema transparente de ejecución de middlewares que:
1. Respete completamente PSR-15
2. Elimine la necesidad de código repetitivo
3. Sea automático y transparente para el desarrollador
4. Mantenga compatibilidad hacia atrás

### Solución: Patrón Template Method

Implementar el patrón Template Method en `HttpHandler` para gestionar automáticamente la cadena de middlewares.

#### Cambios en `HttpHandler`

```php
<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Classes;

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
     * Template Method: Gestiona automáticamente la cadena de middlewares
     * y delega la lógica específica al método abstracto process()
     *
     * Este método es final para evitar que los controladores lo sobrescriban
     * y garantizar que la cadena de middlewares siempre se ejecute correctamente.
     */
    final public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->queue->isEmpty()) {
            return $this->getNextMiddleware()->process($request, $this);
        }

        return $this->process($request);
    }

    /**
     * Método abstracto que cada controlador debe implementar con su lógica específica.
     *
     * Este método se invoca automáticamente después de que todos los middlewares
     * hayan sido procesados.
     *
     * @param ServerRequestInterface $request La petición HTTP (potencialmente modificada por middlewares)
     * @return ResponseInterface La respuesta HTTP
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

#### Cambios en los Controladores

**`WebHookController` (ANTES):**
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

**`WebHookController` (DESPUÉS):**
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

**`HealthController` (ANTES):**
```php
public function handle(ServerRequestInterface $request): ResponseInterface
{
    $version = $this->query_bus->execute(new GetVersionQuery());
    $response = $this->createResponse();
    $response->getBody()->write('Version: '.$version);

    return $response;
}
```

**`HealthController` (DESPUÉS):**
```php
protected function process(ServerRequestInterface $request): ResponseInterface
{
    $version = $this->query_bus->execute(new GetVersionQuery());
    $response = $this->createResponse();
    $response->getBody()->write('Version: '.$version);

    return $response;
}
```

**`NotFoundController` (ANTES):**
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

**`NotFoundController` (DESPUÉS):**
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

## 🎯 Beneficios de la Mejora

### 1. **Simplicidad**
- Los desarrolladores solo escriben la lógica de negocio
- No necesitan conocer la implementación interna de middlewares
- Reduce el código boilerplate en un 100%

### 2. **Conformidad con PSR-15**
- `handle()` sigue siendo el punto de entrada público (PSR-15)
- La implementación interna respeta el flujo de middlewares
- Completamente compatible con el estándar

### 3. **Mantenibilidad**
- Un solo lugar para cambiar la lógica de ejecución de middlewares
- Más fácil de testear
- Menos propenso a errores

### 4. **Principios SOLID**
- **SRP**: Los controladores solo se encargan de su lógica
- **OCP**: Se puede extender sin modificar controladores existentes
- **LSP**: Todos los controladores se comportan consistentemente
- **DIP**: Los controladores dependen de abstracciones, no de implementaciones

### 5. **Type Safety**
- El método `process()` tiene una firma clara y documentada
- No hay ambigüedad sobre qué debe implementar cada controlador

### 6. **Documentación Clara**
- Los PHPDoc explican el flujo automático
- Los desarrolladores entienden inmediatamente qué hacer

## 🔄 Flujo de Ejecución

### Antes
```
Router → Handler::handle()
         ↓
         Desarrollador verifica manualmente isEmpty()
         ↓
         Si hay middlewares → getNextMiddleware()->process()
         ↓
         Middleware → Handler::handle() (recursión)
         ↓
         Lógica del controlador
```

### Después
```
Router → Handler::handle() (final)
         ↓
         Verificación automática isEmpty()
         ↓
         Si hay middlewares → getNextMiddleware()->process()
         ↓
         Middleware → Handler::handle() (recursión automática)
         ↓
         Handler::process() (método abstracto implementado por el controlador)
         ↓
         Lógica del controlador
```

## 📝 Plan de Implementación

### Fase 1: Actualizar `HttpHandler`
1. Hacer `handle()` final con la lógica automática de middlewares
2. Crear método abstracto `process()`
3. Actualizar documentación PHPDoc

### Fase 2: Actualizar Controladores Existentes
1. `WebHookController`: Cambiar `handle()` por `process()`
2. `HealthController`: Cambiar `handle()` por `process()`
3. `NotFoundController`: Cambiar `handle()` por `process()`

### Fase 3: Testing
1. Ejecutar tests existentes
2. Crear tests específicos para la cadena de middlewares
3. Verificar que todos los controladores funcionan correctamente

### Fase 4: Documentación
1. Actualizar documentación del framework
2. Crear guía de migración para desarrolladores
3. Actualizar ejemplos en README

## 🧪 Tests Recomendados

```php
// Test que verifica que los middlewares se ejecutan automáticamente
public function testMiddlewaresAreExecutedAutomatically(): void
{
    $middleware = new TestMiddleware();
    $controller = new TestController();
    $controller->addMiddleware($middleware);

    $request = $this->createMock(ServerRequestInterface::class);
    $response = $controller->handle($request);

    $this->assertTrue($middleware->wasExecuted());
}

// Test que verifica que process() se llama después de los middlewares
public function testProcessIsCalledAfterMiddlewares(): void
{
    $middleware = new TestMiddleware();
    $controller = new TestController();
    $controller->addMiddleware($middleware);

    $request = $this->createMock(ServerRequestInterface::class);
    $controller->handle($request);

    $this->assertTrue($controller->processWasCalled());
}

// Test que verifica que process() se llama directamente sin middlewares
public function testProcessIsCalledDirectlyWithoutMiddlewares(): void
{
    $controller = new TestController();
    $request = $this->createMock(ServerRequestInterface::class);

    $controller->handle($request);

    $this->assertTrue($controller->processWasCalled());
}
```

## 🎓 Conclusión

Esta mejora transforma la gestión de middlewares de un proceso manual y repetitivo a uno automático y transparente, manteniendo la compatibilidad con PSR-15 y mejorando significativamente la experiencia del desarrollador. El framework se vuelve más robusto, mantenible y fácil de usar sin sacrificar flexibilidad ni potencia.
