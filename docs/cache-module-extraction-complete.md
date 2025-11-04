# Cache Module Extraction - Migration Complete

**Fecha:** 30 de octubre de 2025
**Branch:** refactor-complete-psr-compatibility

## Resumen

Se ha extraído exitosamente toda la funcionalidad de Cache del core de Flexi a un módulo dedicado `modules/Cache/`, eliminando la responsabilidad de gestión de caché del núcleo del framework mientras se mantiene la compatibilidad mediante la interfaz `CacheInterface` del paquete `contracts`.

## Motivación

- **Separación de responsabilidades**: El core del framework no debe contener implementaciones concretas de cache
- **Modularidad**: Facilita la extensión y reemplazo de implementaciones de cache
- **Mantenibilidad**: Código relacionado con cache agrupado en un solo módulo
- **Principio de inversión de dependencias**: El core depende solo de la interfaz (contracts), no de las implementaciones

## Cambios Realizados

### 1. Nuevo Módulo Cache

Se creó el módulo `modules/Cache/` con la siguiente estructura:

```
modules/Cache/
├── README.md                           # Documentación del módulo
├── Config/
│   └── services.json                   # Configuración de servicios
├── Domain/
│   └── Exceptions/
│       └── InvalidArgumentCacheException.php
├── Infrastructure/
│   ├── Cache/
│   │   ├── FileCache.php              # Implementación basada en archivos
│   │   └── InMemoryCache.php          # Implementación en memoria
│   └── Factories/
│       └── CacheFactory.php            # Factory para crear instancias
└── tests/
    └── Infrastructure/
        └── Cache/
            └── InMemoryCacheTest.php   # Tests unitarios
```

### 2. Componentes Movidos

#### Del Core al Módulo

**Implementaciones:**
- `src/Infrastructure/Cache/FileCache.php` → `modules/Cache/Infrastructure/Cache/FileCache.php`
- `src/Infrastructure/Cache/InMemoryCache.php` → `modules/Cache/Infrastructure/Cache/InMemoryCache.php`

**Factory:**
- `src/Infrastructure/Factories/CacheFactory.php` → `modules/Cache/Infrastructure/Factories/CacheFactory.php`

**Excepciones:**
- `src/Domain/Exceptions/InvalidArgumentCacheException.php` → `modules/Cache/Domain/Exceptions/InvalidArgumentCacheException.php`

**Tests:**
- `tests/Infrastructure/Cache/InMemoryCacheTest.php` → `modules/Cache/tests/Infrastructure/Cache/InMemoryCacheTest.php`

#### Archivos Eliminados del Core

```
src/Infrastructure/Cache/                           [ELIMINADO]
├── FileCache.php
└── InMemoryCache.php

src/Infrastructure/Factories/CacheFactory.php       [ELIMINADO]
src/Domain/Exceptions/InvalidArgumentCacheException.php [ELIMINADO]
tests/Infrastructure/Cache/InMemoryCacheTest.php    [ELIMINADO]
```

### 3. Actualizaciones de Namespaces

Todos los componentes ahora usan el namespace `Modules\Cache\`:

```php
// Antes
namespace CubaDevOps\Flexi\Infrastructure\Cache;
namespace CubaDevOps\Flexi\Infrastructure\Factories;
namespace CubaDevOps\Flexi\Domain\Exceptions;

// Después
namespace Modules\Cache\Infrastructure\Cache;
namespace Modules\Cache\Infrastructure\Factories;
namespace Modules\Cache\Domain\Exceptions;
```

### 4. Configuración de Servicios

**Eliminado de `src/Config/services.json`:**
```json
{
  "name": "CubaDevOps\\Flexi\\Contracts\\Interfaces\\CacheInterface",
  "factory": {
    "class": "CubaDevOps\\Flexi\\Infrastructure\\Factories\\CacheFactory",
    "method": "createDefault",
    "arguments": ["@CubaDevOps\\Flexi\\Contracts\\Interfaces\\ConfigurationInterface"]
  }
},
{
  "name": "cache",
  "alias": "CubaDevOps\\Flexi\\Contracts\\Interfaces\\CacheInterface"
}
```

**Agregado en `modules/Cache/Config/services.json`:**
```json
{
  "services": [
    {
      "name": "CubaDevOps\\Flexi\\Contracts\\Interfaces\\CacheInterface",
      "factory": {
        "class": "Modules\\Cache\\Infrastructure\\Factories\\CacheFactory",
        "method": "createDefault",
        "arguments": ["@CubaDevOps\\Flexi\\Contracts\\Interfaces\\ConfigurationInterface"]
      }
    },
    {
      "name": "cache",
      "alias": "CubaDevOps\\Flexi\\Contracts\\Interfaces\\CacheInterface"
    }
  ]
}
```

### 5. Actualización de ContainerFactory

Se modificó `src/Infrastructure/Factories/ContainerFactory.php` para eliminar la dependencia directa de `CacheFactory`:

**Antes:**
```php
public static function createDefault(
    string $file = '',
    ?ConfigurationRepository $configRepo = null,
    ?Configuration $configuration = null,
    ?CacheFactory $cacheFactory = null,  // ❌ Dependencia del core
    ?ObjectBuilder $objectBuilder = null
): Container {
    // ...
    $cacheFactory = $cacheFactory ?? new CacheFactory($configuration);
    $cache = $cacheFactory->getInstance();
    // ...
}
```

**Después:**
```php
public static function createDefault(
    string $file = '',
    ?ConfigurationRepository $configRepo = null,
    ?Configuration $configuration = null,
    ?CacheInterface $cache = null,  // ✅ Interfaz del contrato
    ?ObjectBuilder $objectBuilder = null
): Container {
    // ...
    if (null === $cache) {
        if (class_exists('Modules\\Cache\\Infrastructure\\Cache\\InMemoryCache')) {
            $cache = new \Modules\Cache\Infrastructure\Cache\InMemoryCache();
        } else {
            throw new \RuntimeException('Cache implementation not available...');
        }
    }
    // ...
}
```

### 6. Actualización de Tests

Se actualizó `tests/Domain/Utils/ClassFactoryTest.php`:

```php
// Antes
use CubaDevOps\Flexi\Infrastructure\Factories\CacheFactory;

// Después
use Modules\Cache\Infrastructure\Factories\CacheFactory;
```

### 7. Configuración de Autoload

Se actualizó `composer.json` para incluir el namespace `Modules\`:

```json
"autoload": {
  "psr-4": {
    "CubaDevOps\\Flexi\\": "src/",
    "CubaDevOps\\Flexi\\Modules\\": "modules/",
    "Modules\\": "modules/"  // ← Nuevo
  }
}
```

## Componentes que Permanecen en Contracts

Los siguientes componentes **permanecen en el paquete contracts** porque son parte del contrato público:

- ✅ `contracts/src/Interfaces/CacheInterface.php` - Interfaz PSR-16
- ✅ `contracts/src/Classes/Traits/CacheKeyGeneratorTrait.php` - Utilidad genérica

## Compatibilidad

### El Core Sigue Usando Cache

El core del framework sigue utilizando cache internamente a través de la interfaz:

```php
// src/Infrastructure/DependencyInjection/Container.php
use CubaDevOps\Flexi\Contracts\Interfaces\CacheInterface;

class Container {
    public function __construct(CacheInterface $cache, ...) {
        // ...
    }
}

// src/Infrastructure/Classes/ObjectBuilder.php
use CubaDevOps\Flexi\Contracts\Interfaces\CacheInterface;

class ObjectBuilder {
    public function __construct(CacheInterface $cache) {
        // ...
    }
}
```

### Inyección de Dependencias

El módulo Cache se registra automáticamente gracias a la configuración de servicios:

```json
// src/Config/services.json
{
  "glob": "./modules/*/Config/services.json"
}
```

Esto asegura que `modules/Cache/Config/services.json` sea cargado y el servicio de cache esté disponible en el contenedor.

## Migración para Desarrolladores

Si tienes código que dependía directamente de las clases del core:

### ❌ Antes (acoplado al core)
```php
use CubaDevOps\Flexi\Infrastructure\Cache\FileCache;
use CubaDevOps\Flexi\Infrastructure\Factories\CacheFactory;

$cache = new FileCache('/path/to/cache');
```

### ✅ Después (usando el módulo o la interfaz)
```php
// Opción 1: Usar el módulo directamente
use Modules\Cache\Infrastructure\Cache\FileCache;
use Modules\Cache\Infrastructure\Factories\CacheFactory;

$cache = new FileCache('/path/to/cache');

// Opción 2: Usar el contenedor (RECOMENDADO)
$cache = $container->get('cache');
// o
$cache = $container->get(CacheInterface::class);
```

## Verificación

Para verificar que la migración fue exitosa:

1. **Tests unitarios:**
   ```bash
   podman exec flexi vendor/bin/phpunit modules/Cache/tests/
   ```

2. **Tests de integración:**
   ```bash
   podman exec flexi vendor/bin/phpunit
   ```

3. **Verificar servicios:**
   ```php
   $container = ContainerFactory::createDefault('./src/Config/services.json');
   $cache = $container->get('cache');
   assert($cache instanceof CacheInterface);
   ```

## Beneficios

1. ✅ **Separación de responsabilidades**: Cache está fuera del core
2. ✅ **Modularidad**: Fácil de extender o reemplazar
3. ✅ **Testabilidad**: Tests agrupados con la implementación
4. ✅ **Mantenibilidad**: Código cohesivo en un solo lugar
5. ✅ **Compatibilidad**: El core sigue funcionando sin cambios en la API
6. ✅ **Principios SOLID**: Inversión de dependencias respetada
7. ✅ **PSR compatible**: Implementa PSR-16 (Simple Cache)

## Próximos Pasos

- [ ] Agregar tests para `FileCache`
- [ ] Implementar drivers adicionales (Redis, Memcached, etc.)
- [ ] Documentar configuración avanzada
- [ ] Crear ejemplos de uso personalizados

## Referencias

- PSR-16: Simple Cache: https://www.php-fig.org/psr/psr-16/
- Arquitectura hexagonal: https://alistair.cockburn.us/hexagonal-architecture/
- Principio de inversión de dependencias: https://en.wikipedia.org/wiki/Dependency_inversion_principle
