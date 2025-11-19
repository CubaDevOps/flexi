# Corrección de Dirección de Dependencias - Módulos Instalados

## Problema Identificado

Durante la revisión del código después de implementar el sistema de filtrado de módulos, se identificó una **violación del principio de dirección de dependencias**:

```
contracts (paquete base)
    └── InstalledModulesFilterTrait
            └── depende de ───> InstalledModulesFilter (en src/Infrastructure)
                                    ❌ DEPENDENCIA INVERTIDA
```

**Problema**: El trait en el paquete `contracts` dependía de una clase concreta de `Infrastructure` del core, violando el principio de que las abstracciones no deben depender de detalles de implementación.

## Solución Implementada

Se separó la lógica en dos partes distintas con responsabilidades claras:

### 1. InstalledModulesProviderTrait (en contracts)

**Ubicación**: `contracts/src/Classes/Traits/InstalledModulesProviderTrait.php`

**Responsabilidades**:
- ✅ Lectura de composer.json
- ✅ Detección de módulos instalados
- ✅ Verificación si un módulo está instalado
- ✅ Gestión de caché de módulos

**Métodos expuestos** (protected para uso en traits):
```php
protected function getInstalledModules(): array
protected function isModuleInstalled(string $moduleName): bool
protected function clearModulesCache(): void
```

**Uso**: Puede ser usado tanto por el core como por los módulos para verificar dependencias.

### 2. InstalledModulesFilter (en core)

**Ubicación**: `src/Infrastructure/Classes/InstalledModulesFilter.php`

**Responsabilidades**:
- ✅ Filtrado de archivos de configuración
- ✅ Detección de archivos de módulos
- ✅ Extracción de nombres de módulos de rutas

**Usa**: `InstalledModulesProviderTrait` para obtener lista de módulos instalados

**Métodos públicos**:
```php
public function filterFiles(array $files): array
public function isModuleFile(string $file): bool
public function extractModuleName(string $file): ?string
public function getInstalledModules(): array      // expuesto desde trait
public function isModuleInstalled(string $name): bool  // expuesto desde trait
public function clearCache(): void                // expuesto desde trait
```

**Exposición de métodos del trait**:
```php
class InstalledModulesFilter
{
    use InstalledModulesProviderTrait {
        getInstalledModules as public;
        isModuleInstalled as public;
        clearModulesCache as public clearCache;
    }
}
```

### 3. Eliminación de InstalledModulesFilterTrait

El trait `InstalledModulesFilterTrait` en contracts se volvió redundante porque:
- Las clases del core pueden usar `InstalledModulesFilter` directamente
- Los módulos pueden usar `InstalledModulesProviderTrait` para verificar dependencias
- No había necesidad de una capa intermedia

## Dirección de Dependencias Corregida

```
┌─────────────────────────────────────────────────┐
│                   CONTRACTS                      │
│  (paquete base, sin dependencias externas)      │
│                                                  │
│  ┌────────────────────────────────────────┐    │
│  │  InstalledModulesProviderTrait         │    │
│  │  - getInstalledModules()               │    │
│  │  - isModuleInstalled()                 │    │
│  │  - clearModulesCache()                 │    │
│  └────────────────────────────────────────┘    │
│                    ▲                             │
└────────────────────┼─────────────────────────────┘
                     │
                     │ usa (✅ dirección correcta)
                     │
┌────────────────────┼─────────────────────────────┐
│                    │           CORE               │
│  ┌─────────────────┴──────────────────────┐     │
│  │  InstalledModulesFilter                │     │
│  │  use InstalledModulesProviderTrait     │     │
│  │                                         │     │
│  │  + filterFiles()                       │     │
│  │  + isModuleFile()                      │     │
│  │  + extractModuleName()                 │     │
│  └─────────────────────────────────────────┘    │
│                    ▲                             │
│                    │                             │
│        ┌───────────┼──────────────┐             │
│        │           │              │             │
│   ┌────┴───┐  ┌───┴────┐  ┌─────┴──────┐      │
│   │ Router │  │ Buses  │  │  Services  │      │
│   └────────┘  └────────┘  │   Parser   │      │
│                            └────────────┘      │
└─────────────────────────────────────────────────┘
```

## Cambios Realizados

### Archivos Creados
1. ✅ `contracts/src/Classes/Traits/InstalledModulesProviderTrait.php` - Trait con lógica de detección de módulos

### Archivos Modificados

#### 1. InstalledModulesFilter
```php
// ANTES
class InstalledModulesFilter
{
    // Toda la lógica duplicada aquí
    private function getInstalledModules() { ... }
    private function isModuleInstalled() { ... }
}

// DESPUÉS
class InstalledModulesFilter
{
    use InstalledModulesProviderTrait {
        getInstalledModules as public;
        isModuleInstalled as public;
        clearModulesCache as public clearCache;
    }

    // Solo lógica de filtrado de archivos
    public function filterFiles(array $files): array { ... }
}
```

#### 2. EventBus, CommandBus, QueryBus, Router, ServicesDefinitionParser

**Cambio aplicado en cada uno**:

```php
// ANTES
use Flexi\Contracts\Classes\Traits\InstalledModulesFilterTrait;

class EventBus
{
    use InstalledModulesFilterTrait;

    private function loadGlobListeners(array $listener): void {
        $files = $this->filterInstalledModuleFiles($files);
    }
}

// DESPUÉS
use Flexi\Infrastructure\Classes\InstalledModulesFilter;

class EventBus
{
    private function loadGlobListeners(array $listener): void {
        $filter = new InstalledModulesFilter();
        $files = $filter->filterFiles($files);
    }
}
```

### Archivos Eliminados
1. ✅ `contracts/src/Classes/Traits/InstalledModulesFilterTrait.php` - Ya no es necesario

## Beneficios de la Nueva Arquitectura

### 1. Dirección de Dependencias Correcta ✅
- **Contracts** no depende de **Core**
- **Core** depende de **Contracts** (dirección correcta)
- Respeta el principio de inversión de dependencias

### 2. Separación de Responsabilidades ✅
- **InstalledModulesProviderTrait**: Solo detección de módulos
- **InstalledModulesFilter**: Filtrado de archivos + usa provider trait

### 3. Reutilización en Módulos ✅
Los módulos pueden usar `InstalledModulesProviderTrait` para verificar dependencias:

```php
namespace Flexi\Modules\MyModule;

use Flexi\Contracts\Classes\Traits\InstalledModulesProviderTrait;

class MyModuleService
{
    use InstalledModulesProviderTrait;

    public function checkDependencies(): void
    {
        if (!$this->isModuleInstalled('Auth')) {
            throw new \RuntimeException('MyModule requires Auth module');
        }
    }
}
```

### 4. Código más Limpio ✅
- Eliminado trait redundante
- Clases del core usan servicio concreto directamente
- Sin capas innecesarias de abstracción

## Compatibilidad con PHP 7.4

Como PHP 7.4 no soporta constantes en traits, se usaron métodos privados en su lugar:

```php
// ❌ No funciona en PHP 7.4
trait MyTrait {
    private const MY_CONSTANT = 'value';
}

// ✅ Solución para PHP 7.4
trait MyTrait {
    private function getMyConstant(): string {
        return 'value';
    }
}
```

## Tests

Todos los tests del core pasan exitosamente:

```bash
$ podman exec flexi vendor/bin/phpunit --testsuite=Core

PHPUnit 9.6.29 by Sebastian Bergmann and contributors.
Runtime: PHP 7.4.33

OK (122 tests, 254 assertions)
Time: 00:00.281, Memory: 12.00 MB
```

## Resumen de Mejoras

| Aspecto | Antes | Después |
|---------|-------|---------|
| **Dirección de dependencias** | ❌ Contracts → Core | ✅ Core → Contracts |
| **Código duplicado** | ❌ ~100 líneas en InstalledModulesFilter | ✅ Reutilizado vía trait |
| **Trait redundante** | ❌ InstalledModulesFilterTrait sin valor | ✅ Eliminado |
| **Uso directo de servicio** | ❌ Trait para crear instancia | ✅ new InstalledModulesFilter() |
| **Módulos verificar deps** | ❌ No disponible | ✅ Disponible vía trait |
| **Tests** | ✅ 122 passing | ✅ 122 passing |

## Conclusión

Esta refactorización corrige un problema arquitectónico fundamental:

1. **Antes**: El paquete base (`contracts`) dependía del core (`Infrastructure`)
2. **Después**: El core depende del paquete base, respetando la dirección correcta de dependencias

La nueva arquitectura es más limpia, mantenible y permite a los módulos verificar sus dependencias sin depender del core.
