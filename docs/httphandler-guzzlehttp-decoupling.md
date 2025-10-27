# HttpHandler: Eliminar dependencia de GuzzleHttp

**Problem:** `HttpHandler` en Contracts tiene acoplamiento a `GuzzleHttp\Psr7\HttpFactory`
**Status:** 🔍 ANALYSIS
**Date:** October 27, 2025

---

## El Problema

### Situación Actual

`contracts/src/Classes/HttpHandler.php`:
```php
use GuzzleHttp\Psr7\HttpFactory;  // ❌ Dependencia externa hardcoded
...
public function __construct()
{
    $this->queue = new \SplQueue();
    $this->response_factory = new HttpFactory();  // ❌ Instancia directa
}
```

### Por qué es un Problema

1. **Violación de principios de Contracts**
   - Contracts debe SOLO depender de estándares PSR
   - `contracts/composer.json` NO lista GuzzleHttp como dependencia
   - Pero el código lo importa directamente → Error potencial de autoload

2. **Acoplamiento Innecesario**
   - HttpHandler está acoplado a una librería específica (GuzzleHttp)
   - No se puede reemplazar con otra implementación de ResponseFactory
   - Framework no es agnóstico a la implementación

3. **Violación de Inversión de Dependencias**
   - HttpHandler crea su propia dependencia en lugar de recibirla
   - No es inyectable, no es testeable fácilmente

4. **Referencia a interfaz que nunca se usa**
   ```php
   /**
    * @var RequestFactoryInterface|ResponseFactoryInterface|ServerRequestFactoryInterface|StreamFactoryInterface|UploadedFileFactoryInterface|UriFactoryInterface
    */
   protected $response_factory;  // ❌ Solo usa ResponseFactoryInterface
   ```

---

## Análisis de Uso

### Qué necesita realmente HttpHandler

En toda la clase, `$response_factory` se usa SOLO aquí:

```php
protected function createResponse(int $code = 200, string $reasonPhrase = 'OK'): ResponseInterface
{
    return $this->response_factory->createResponse($code, $reasonPhrase);
}
```

**Solo necesita:** `ResponseFactoryInterface`

### Dónde se inyecta actualmente

Según `src/Config/services.json`:

```json
{
  "name": "Psr\\Http\\Message\\ResponseFactoryInterface",
  "class": {
    "name": "GuzzleHttp\\Psr7\\HttpFactory",
    "arguments": []
  }
}
```

Ya existe una definición de `ResponseFactoryInterface` → `GuzzleHttp\Psr7\HttpFactory`

---

## Opciones de Solución

### ❌ Opción 1: Dejar como está
- Problema: Seguir violando principios de Contracts
- Problema: Código frágil si GuzzleHttp no está en classpath
- Descartada

### ❌ Opción 2: Crear HttpFactoryInterface que extienda todas las interfaces
```php
interface HttpFactoryInterface extends
    RequestFactoryInterface,
    ResponseFactoryInterface,
    ServerRequestFactoryInterface,
    StreamFactoryInterface,
    UploadedFileFactoryInterface,
    UriFactoryInterface {}
```
- Problema: Sobredimensionado (HttpHandler solo usa ResponseFactory)
- Problema: Confuso (¿por qué inyectar una interfaz que no se usa completa?)
- Problema: Acoplamiento innecesario a interfaces no utilizadas

### ✅ Opción 3: Inyectar ResponseFactoryInterface por constructor (RECOMENDADA)

**Cambiar:**
```php
abstract class HttpHandler implements RequestHandlerInterface
{
    protected ResponseFactoryInterface $response_factory;  // ✅ Específica

    public function __construct(ResponseFactoryInterface $response_factory)
    {
        $this->queue = new \SplQueue();
        $this->response_factory = $response_factory;  // ✅ Inyectada
    }
```

**Ventajas:**
- ✅ Elimina dependencia a GuzzleHttp en Contracts
- ✅ Cumple con inversión de dependencias
- ✅ Fácil de testear (mock ResponseFactory)
- ✅ Agnóstico a implementación
- ✅ Explícito: qué necesita, qué recibe
- ✅ Flexible: puedo usar cualquier ResponseFactory

**Impacto:**
- Controladores heredan de HttpHandler
- Necesitan pasar `ResponseFactoryInterface` en `parent::__construct()`
- Solución: Usar inyección de dependencias del contenedor

### 🔵 Opción 4: Crear parámetro opcional con default null (Híbrida)
```php
public function __construct(?ResponseFactoryInterface $response_factory = null)
{
    $this->response_factory = $response_factory ?? new HttpFactory();
}
```
- Problema: Mantiene la dependencia a GuzzleHttp como fallback
- Problema: Peor de ambos mundos
- Descartada

---

## Solución Recomendada: Opción 3

### Paso 1: Actualizar HttpHandler en Contracts

```php
<?php
declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Classes;

use Psr\Http\Message\ResponseFactoryInterface;  // ✅ PSR standard
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class HttpHandler implements RequestHandlerInterface
{
    protected ResponseFactoryInterface $response_factory;  // ✅ Interface PSR
    protected \SplQueue $queue;

    /**
     * @param ResponseFactoryInterface $response_factory Factory para crear respuestas
     */
    public function __construct(ResponseFactoryInterface $response_factory)
    {
        $this->queue = new \SplQueue();
        $this->response_factory = $response_factory;
    }

    // ... resto del código igual
}
```

### Paso 2: Registrar en services.json

Ya existe:
```json
{
  "name": "Psr\\Http\\Message\\ResponseFactoryInterface",
  "class": {
    "name": "GuzzleHttp\\Psr7\\HttpFactory",
    "arguments": []
  }
}
```

Esto continuará siendo la inyección de GuzzleHttp en la capa Infrastructure.

### Paso 3: Actualizar controladores

**Antes:**
```php
public function __construct(QueryBus $query_bus)
{
    parent::__construct();
    $this->query_bus = $query_bus;
}
```

**Después:**
```php
public function __construct(
    ResponseFactoryInterface $response_factory,
    QueryBus $query_bus
)
{
    parent::__construct($response_factory);
    $this->query_bus = $query_bus;
}
```

O mejor, usar autowiring del contenedor DI:

```php
public function __construct(QueryBus $query_bus)
{
    parent::__construct($GLOBALS['container']->get(ResponseFactoryInterface::class));
    $this->query_bus = $query_bus;
}
```

O incluso mejor, que el contenedor gestione toda la inyección automáticamente.

---

## Impacto del Cambio

### Archivos que necesitan actualización

**1. contracts/src/Classes/HttpHandler.php**
- Eliminar `use GuzzleHttp\Psr7\HttpFactory`
- Cambiar `public function __construct()` a `public function __construct(ResponseFactoryInterface $response_factory)`
- Actualizar property type

**2. Todos los controladores que extienden HttpHandler**
- `modules/Home/Infrastructure/Controllers/HomeController.php`
- `modules/Ui/Infrastructure/Controllers/...` (si existen)
- `modules/Auth/Infrastructure/Controllers/...` (si existen)
- `tests/TestData/TestDoubles/TestHttpHandler.php`

**3. Documentación**
- `Readme.md` - Actualizar ejemplos
- `docs/middleware-architecture-improvements.md` - Actualizar ejemplos

### ¿Hay tests?

Sí, necesitamos verificar:
- `tests/Infrastructure/DependencyInjection/ContainerTest.php`
- Tests específicos de controladores

---

## Beneficios

✅ **Contracts puro**
- Solo depende de PSR standards
- Elimina dependencia transitiva de GuzzleHttp en Contracts

✅ **Mejor inversión de dependencias**
- HttpHandler recibe lo que necesita
- No crea sus propias dependencias

✅ **Más testeable**
- Fácil hacer mock de ResponseFactoryInterface
- Pruebas unitarias más limpias

✅ **Más flexible**
- Puedo usar cualquier implementación de ResponseFactory
- No estoy ligado a GuzzleHttp

✅ **Conforme a principios**
- Dependency Injection
- Inversion of Control
- Contracts como abstracción pura

---

## Riesgos y Mitigación

| Riesgo | Mitigación |
|--------|-----------|
| Cambio breaking | Versión mayor, deprecation warning temporal |
| Controllers que no inyecten | Tests unitarios lo detectarán inmediatamente |
| Complejidad | Autowiring del contenedor maneja la inyección |
| Performance | Negligible (una inyección menos) |

---

## Próximos Pasos

1. ✅ Análisis completado
2. ⏳ Consultar con usuario si es la dirección correcta
3. ⏳ Implementar cambios en HttpHandler
4. ⏳ Actualizar todos los controladores
5. ⏳ Ejecutar tests (171/171 debe pasar)
6. ⏳ Actualizar documentación
7. ⏳ Commit

---

**Recomendación: Proceder con Opción 3**

Esta es la solución más elegante y conforme a principios SOLID.
