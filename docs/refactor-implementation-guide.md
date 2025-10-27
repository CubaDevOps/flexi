# Guía Paso a Paso: Implementación de Refactor

## 🚀 Antes de Empezar

```bash
# 1. Verificar que todo está limpio
git status
# Debe estar todo commiteado

# 2. Crear rama de trabajo
git checkout -b refactor/core-architecture-cleanup

# 3. Ejecutar tests (baseline)
podman exec flexi vendor/bin/phpunit
```

---

## FASE 1: Mover Traits a Contracts (Bajo Riesgo)

### Paso 1.1: Crear estructura en Contracts

```bash
# En host
mkdir -p /Users/cbatista8a/Sites/flexi/contracts/src/Classes/Traits
```

### Paso 1.2: Copiar los traits

**Archivos a copiar:**

```
src/Infrastructure/Utils/CacheKeyGeneratorTrait.php     → contracts/src/Classes/Traits/
src/Infrastructure/Utils/FileHandlerTrait.php           → contracts/src/Classes/Traits/
src/Infrastructure/Utils/GlobFileReader.php             → contracts/src/Classes/Traits/
src/Infrastructure/Utils/JsonFileReader.php             → contracts/src/Classes/Traits/
```

**Actualizar namespaces en cada archivo copiado:**

```php
// ANTES:
namespace CubaDevOps\Flexi\Infrastructure\Utils;

// DESPUÉS:
namespace CubaDevOps\Flexi\Contracts\Classes\Traits;
```

### Paso 1.3: Actualizar imports en src/Infrastructure/Bus

**Archivos a actualizar:**
- `src/Infrastructure/Bus/CommandBus.php`
- `src/Infrastructure/Bus/QueryBus.php`
- `src/Infrastructure/Bus/EventBus.php`

**Cambios en cada archivo (ejemplo para CommandBus):**

```php
// ANTES:
use CubaDevOps\Flexi\Infrastructure\Utils\GlobFileReader;
use CubaDevOps\Flexi\Infrastructure\Utils\JsonFileReader;

// DESPUÉS:
use CubaDevOps\Flexi\Contracts\Classes\Traits\GlobFileReader;
use CubaDevOps\Flexi\Contracts\Classes\Traits\JsonFileReader;
```

### Paso 1.4: Actualizar composer autoload

```bash
podman exec flexi composer dump-autoload -o
```

### Paso 1.5: Tests

```bash
podman exec flexi vendor/bin/phpunit tests/Infrastructure/Bus/ -v
```

**Si todo pasa: COMMIT**

```bash
git add contracts/src/Classes/Traits/
git add src/Infrastructure/Bus/
git commit -m "refactor: move utility traits to contracts"
```

---

## FASE 2: Mover EventListener a Contracts

### Paso 2.1: Copiar EventListener

```bash
# Copiar desde:
# src/Domain/Events/EventListener.php
# A:
# contracts/src/Classes/EventListener.php
```

**Actualizar namespace:**

```php
// ANTES:
namespace CubaDevOps\Flexi\Domain\Events;

// DESPUÉS:
namespace CubaDevOps\Flexi\Contracts\Classes;
```

### Paso 2.2: Actualizar imports

**Archivos a actualizar:**
- `src/Application/EventListeners/LoggerEventListener.php`

**Cambio:**

```php
// ANTES:
use CubaDevOps\Flexi\Domain\Events\EventListener;

// DESPUÉS:
use CubaDevOps\Flexi\Contracts\Classes\EventListener;
```

### Paso 2.3: Actualizar composer autoload

```bash
podman exec flexi composer dump-autoload -o
```

### Paso 2.4: Tests

```bash
podman exec flexi vendor/bin/phpunit tests/Application/EventListeners/ -v
```

**Si todo pasa: COMMIT**

```bash
git add contracts/src/Classes/EventListener.php
git add src/Application/EventListeners/LoggerEventListener.php
git commit -m "refactor: move generic EventListener base class to contracts"
```

---

## FASE 3: Mover ValueObjects de DI

### Paso 3.1: Crear estructura en src/Infrastructure/DependencyInjection

```bash
mkdir -p /Users/cbatista8a/Sites/flexi/src/Infrastructure/DependencyInjection/ValueObjects
```

### Paso 3.2: Mover archivos

```bash
# Movimiento (no copia):
mv src/Domain/ValueObjects/Operator.php \
   src/Infrastructure/DependencyInjection/ValueObjects/

mv src/Domain/ValueObjects/Order.php \
   src/Infrastructure/DependencyInjection/ValueObjects/

mv src/Domain/ValueObjects/ServiceType.php \
   src/Infrastructure/DependencyInjection/ValueObjects/
```

### Paso 3.3: Actualizar namespaces

**En cada archivo:**

```php
// ANTES:
namespace CubaDevOps\Flexi\Domain\ValueObjects;

// DESPUÉS:
namespace CubaDevOps\Flexi\Infrastructure\DependencyInjection\ValueObjects;
```

### Paso 3.4: Buscar y actualizar todos los imports

```bash
# Buscar todos los imports de estos ValueObjects
grep -r "use CubaDevOps\\\\Flexi\\\\Domain\\\\ValueObjects\\\\Operator" /Users/cbatista8a/Sites/flexi/src/
grep -r "use CubaDevOps\\\\Flexi\\\\Domain\\\\ValueObjects\\\\Order" /Users/cbatista8a/Sites/flexi/src/
grep -r "use CubaDevOps\\\\Flexi\\\\Domain\\\\ValueObjects\\\\ServiceType" /Users/cbatista8a/Sites/flexi/src/
```

**Actualizar en archivos encontrados:**

```php
// ANTES:
use CubaDevOps\Flexi\Domain\ValueObjects\Operator;
use CubaDevOps\Flexi\Domain\ValueObjects\Order;
use CubaDevOps\Flexi\Domain\ValueObjects\ServiceType;

// DESPUÉS:
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\ValueObjects\Operator;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\ValueObjects\Order;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\ValueObjects\ServiceType;
```

### Paso 3.5: Actualizar composer autoload

```bash
podman exec flexi composer dump-autoload -o
```

### Paso 3.6: Tests

```bash
podman exec flexi vendor/bin/phpunit tests/Infrastructure/DependencyInjection/ -v
```

**Si todo pasa: COMMIT**

```bash
git add src/Infrastructure/DependencyInjection/ValueObjects/
git remove src/Domain/ValueObjects/Operator.php
git remove src/Domain/ValueObjects/Order.php
git remove src/Domain/ValueObjects/ServiceType.php
git commit -m "refactor: move DI-specific ValueObjects to Infrastructure layer"
```

---

## FASE 4: Crear Módulo Auth

### Paso 4.1: Crear estructura

```bash
mkdir -p /Users/cbatista8a/Sites/flexi/modules/Auth/Infrastructure/Middlewares
mkdir -p /Users/cbatista8a/Sites/flexi/modules/Auth/Config
mkdir -p /Users/cbatista8a/Sites/flexi/modules/Auth/tests
```

### Paso 4.2: Mover middlewares

```bash
# Copiar (no mover aún):
cp src/Infrastructure/Middlewares/AuthCheckMiddleware.php \
   modules/Auth/Infrastructure/Middlewares/

cp src/Infrastructure/Middlewares/JWTAuthMiddleware.php \
   modules/Auth/Infrastructure/Middlewares/
```

### Paso 4.3: Actualizar namespaces

**En cada archivo de módulo:**

```php
// ANTES (AuthCheckMiddleware.php):
namespace CubaDevOps\Flexi\Infrastructure\Middlewares;

// DESPUÉS:
namespace CubaDevOps\Flexi\Modules\Auth\Infrastructure\Middlewares;
```

### Paso 4.4: Crear composer.json para el módulo (opcional)

```json
{
  "name": "cubadevops/flexi-module-auth",
  "description": "Authentication module for Flexi Framework",
  "autoload": {
    "psr-4": {
      "CubaDevOps\\Flexi\\Modules\\Auth\\": "."
    }
  }
}
```

### Paso 4.5: Actualizar imports en módulos que usen Auth

**Buscar uso de AuthCheckMiddleware en rutas/config:**

```bash
grep -r "AuthCheckMiddleware" /Users/cbatista8a/Sites/flexi/
```

**Actualizar imports encontrados:**

```php
// ANTES:
use CubaDevOps\Flexi\Infrastructure\Middlewares\AuthCheckMiddleware;

// DESPUÉS:
use CubaDevOps\Flexi\Modules\Auth\Infrastructure\Middlewares\AuthCheckMiddleware;
```

### Paso 4.6: Actualizar composer autoload

```bash
podman exec flexi composer dump-autoload -o
```

### Paso 4.7: Tests

```bash
podman exec flexi vendor/bin/phpunit -v
```

**Si todo pasa: COMMIT**

```bash
git add modules/Auth/
git commit -m "refactor: create Auth module and move authentication middlewares"
```

---

## FASE 5: Limpieza Final

### Paso 5.1: Eliminar archivos viejos del core

```bash
# Después de confirmar que todo funciona sin ellos:
rm src/Infrastructure/Utils/CacheKeyGeneratorTrait.php
rm src/Infrastructure/Utils/FileHandlerTrait.php
rm src/Infrastructure/Utils/GlobFileReader.php
rm src/Infrastructure/Utils/JsonFileReader.php
rm src/Domain/Events/EventListener.php
rm src/Infrastructure/Middlewares/AuthCheckMiddleware.php
rm src/Infrastructure/Middlewares/JWTAuthMiddleware.php
```

### Paso 5.2: Verificar que Utils esté limpia (si quedó vacía)

```bash
ls -la src/Infrastructure/Utils/
# Si solo quedan archivos necesarios (OSDetector, etc.), está bien
```

### Paso 5.3: Ejecutar full test suite

```bash
podman exec flexi vendor/bin/phpunit
```

### Paso 5.4: Validar que no hay imports antiguos

```bash
# Estos búsquedas deben devolver CERO resultados:
grep -r "use CubaDevOps\\\\Flexi\\\\Domain\\\\ValueObjects\\\\Operator" src/
grep -r "use CubaDevOps\\\\Flexi\\\\Domain\\\\ValueObjects\\\\Order" src/
grep -r "use CubaDevOps\\\\Flexi\\\\Domain\\\\ValueObjects\\\\ServiceType" src/
grep -r "use CubaDevOps\\\\Flexi\\\\Domain\\\\Events\\\\EventListener" src/
grep -r "use CubaDevOps\\\\Flexi\\\\Infrastructure\\\\Middlewares\\\\Auth" src/
```

### Paso 5.5: Validar composer

```bash
podman exec flexi composer validate --strict
```

### Paso 5.6: Final cleanup COMMIT

```bash
git add -A
git commit -m "refactor: cleanup old files after core reorganization"
```

---

## 🧪 Validación Completa

### Verificación 1: Tests

```bash
podman exec flexi vendor/bin/phpunit -v --coverage-text
```

**Esperado:** ✅ Todos los tests pasan

### Verificación 2: Code Quality

```bash
podman exec flexi vendor/bin/phpstan analyze src/ --level=max
```

**Esperado:** ✅ Sin errores critales

### Verificación 3: No hay imports cíclicos

```bash
podman exec flexi composer validate
```

**Esperado:** ✅ `The composer.json file is valid`

### Verificación 4: Estructura correcta

```bash
# Verificar que contratos en Contracts
ls -la contracts/src/Classes/ | grep -E "Trait|EventListener"

# Verificar que Utils están limpias
ls -la src/Infrastructure/Utils/

# Verificar que módulo Auth existe
ls -la modules/Auth/Infrastructure/Middlewares/
```

---

## 📝 PR Checklist

Antes de hacer push:

- [ ] Todas las migraciones completadas
- [ ] Tests verdes (100% pass rate)
- [ ] No hay imports antiguos
- [ ] Composer validate pasa
- [ ] Documentación actualizada
- [ ] Todos los commits son claros y descriptivos

---

## 🔄 Si algo falla

### Test falla

```bash
# 1. Identificar test fallido
# 2. Buscar imports mal actualizados
# 3. Revertir último commit y revisar
git reset --soft HEAD~1
```

### Composer validation falla

```bash
# Posible ciclo de dependencia
# Solución: verificar que Contracts no importa de src/

grep -r "use CubaDevOps\\\\Flexi\\\\Infrastructure" contracts/src/
grep -r "use CubaDevOps\\\\Flexi\\\\Application" contracts/src/
grep -r "use CubaDevOps\\\\Flexi\\\\Domain" contracts/src/

# Si hay resultados, revisar y eliminar esas dependencias
```

### Imports encontrados antiguos

```bash
# Buscar el archivo con import viejo
grep -r "use CubaDevOps\\\\Flexi\\\\Domain\\\\ValueObjects\\\\Operator" .

# Actualizar manualmente
# O ejecutar search/replace en editor
```

---

## ✅ Verificación Final

Cuando TODO está completo:

```bash
# 1. Hacer merge a rama de desarrollo
git checkout develop
git pull origin develop
git merge refactor/core-architecture-cleanup
git push origin develop

# 2. Crear PR a main
# Descripción: "Core Architecture Refactor: Clean separation of concerns"

# 3. Esperar review y tests CI/CD
```

---

## 📚 Archivos de Referencia

Durante la implementación, tener a mano:

1. `core-refactor-architecture-proposal.md` - Análisis detallado
2. `core-refactor-visualization.md` - Diagramas y tablas
3. `refactor-summary.md` - Resumen ejecutivo

---

## 🎉 ¡Listo!

Una vez completado, el framework tendrá:

✅ Core limpio y enfocado
✅ Clases genéricas centralizadas
✅ Módulos independientes
✅ Arquitectura hexagonal perfecta
✅ Sin dependencias cruzadas

**¡Framework profesional! 🚀**
