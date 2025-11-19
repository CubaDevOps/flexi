# Mejoras de Arquitectura Hexagonal - Core Refactoring

**Fecha:** 19 de noviembre de 2025
**Branch:** `feat-improve-modules-management`

## Resumen

Se realizó una refactorización completa de la arquitectura del core para corregir violaciones de la arquitectura hexagonal, moviendo interfaces desde la capa de Infrastructure a la capa de Domain, respetando el Principio de Inversión de Dependencias.

## Cambios Realizados

### 1. Nuevos Directorios Creados

- `src/Domain/Interfaces/` - Contiene todas las interfaces de puertos del dominio
- `src/Domain/Commands/` - Comandos del dominio (patrón Null Object)

### 2. Interfaces Movidas a Domain

#### De `Infrastructure/Interfaces/` a `Domain/Interfaces/`:

1. **ModuleStateManagerInterface**
   - Gestión del estado de activación de módulos
   - Define el puerto para persistencia de estados

2. **ModuleEnvironmentManagerInterface**
   - Gestión de variables de entorno de módulos
   - Puerto para integración con archivos .env

3. **ModuleCacheManagerInterface**
   - Gestión de caché de descubrimiento de módulos
   - Puerto para optimización de performance

4. **ConfigurationFilesProviderInterface**
   - Proveedor de archivos de configuración
   - Puerto para acceso a configuraciones de módulos

#### De `Infrastructure/Factories/` a `Domain/Interfaces/`:

5. **ModuleDetectorInterface**
   - Interfaz para detección de módulos
   - Puerto para descubrimiento de módulos (local, vendor, híbrido)

### 3. Comandos Movidos a Domain

#### De `Application/Commands/` a `Domain/Commands/`:

1. **NotFoundCommand**
   - Patrón Null Object para comandos no encontrados
   - DTO que representa ausencia de handler

## Archivos Modificados

### Application Layer (7 archivos)
- Todos los casos de uso ahora importan interfaces desde `Domain/Interfaces`
- `ActivateModule.php`
- `DeactivateModule.php`
- `GetModuleInfo.php`
- `GetModuleStatus.php`
- `ListModules.php`
- `UpdateModuleEnvironment.php`
- `ValidateModules.php`

### Infrastructure Layer (18 archivos)
- Todas las implementaciones actualizadas para usar interfaces de Domain
- **Bus:**
  - `CommandBus.php`
  - `QueryBus.php`
  - `EventBus.php`
- **Classes:**
  - `ConfigurationFilesProvider.php`
  - `ModuleCacheManager.php`
  - `ModuleEnvironmentManager.php`
  - `ModuleStateManager.php`
- **Factories:**
  - `BusFactory.php`
  - `ContainerFactory.php`
  - `DefaultCacheFactory.php`
  - `HybridModuleDetector.php`
  - `LocalModuleDetector.php`
  - `RouterFactory.php`
  - `VendorModuleDetector.php`
- **UI/CLI:**
  - `CommandHandler.php`
  - `DTOFactory.php`
  - `QueryHandler.php`

### Archivos Eliminados (6 archivos)
- `src/Application/Commands/NotFoundCommand.php` → movido a Domain
- `src/Infrastructure/Interfaces/ModuleStateManagerInterface.php` → movido a Domain
- `src/Infrastructure/Interfaces/ModuleEnvironmentManagerInterface.php` → movido a Domain
- `src/Infrastructure/Interfaces/ModuleCacheManagerInterface.php` → movido a Domain
- `src/Infrastructure/Interfaces/ConfigurationFilesProviderInterface.php` → movido a Domain
- `src/Infrastructure/Factories/ModuleDetectorInterface.php` → movido a Domain

## Beneficios

### 1. Inversión de Dependencias Correcta
- **Antes:** Application dependía de Infrastructure (VIOLACIÓN)
- **Después:** Application depende de Domain, Infrastructure depende de Domain (CORRECTO)

### 2. Independencia de Capas
- **Application:** Solo conoce Domain (interfaces y value objects)
- **Infrastructure:** Implementa interfaces definidas en Domain
- **Domain:** No conoce ninguna otra capa (100% puro)

### 3. Testabilidad Mejorada
- Los casos de uso pueden ser testeados con mocks de interfaces de Domain
- No hay acoplamiento con implementaciones concretas de Infrastructure

### 4. Flexibilidad
- Facilita cambiar implementaciones de Infrastructure sin afectar Application
- Las interfaces están en el centro del dominio, no en detalles de implementación

## Arquitectura Resultante

```
Domain/
├── Commands/              ← Comandos del dominio
│   └── NotFoundCommand
├── Events/               ← Eventos del dominio
├── Exceptions/           ← Excepciones del dominio
├── Interfaces/           ← PUERTOS (Nuevos)
│   ├── ConfigurationFilesProviderInterface
│   ├── ModuleCacheManagerInterface
│   ├── ModuleDetectorInterface
│   ├── ModuleEnvironmentManagerInterface
│   └── ModuleStateManagerInterface
└── ValueObjects/         ← Objetos de valor

Application/
├── Commands/             ← DTOs de comandos
├── Services/            ← Puertos de salida de Application
│   └── CommandExecutorInterface
└── UseCase/             ← Casos de uso (ahora dependen solo de Domain)

Infrastructure/
├── Bus/                 ← Implementaciones de buses
├── Classes/             ← ADAPTADORES (implementan interfaces de Domain)
├── DependencyInjection/
├── Factories/           ← ADAPTADORES (implementan ModuleDetectorInterface)
├── Http/
├── Services/            ← ADAPTADORES (implementan Application/Services)
└── Ui/                  ← Adaptadores de entrada
```

## Principios Aplicados

### ✅ Dependency Inversion Principle (DIP)
- Los módulos de alto nivel (Application) no dependen de módulos de bajo nivel (Infrastructure)
- Ambos dependen de abstracciones (Domain/Interfaces)

### ✅ Separation of Concerns
- Domain: Lógica de negocio y definiciones de puertos
- Application: Casos de uso y orquestación
- Infrastructure: Detalles de implementación y adaptadores

### ✅ Clean Architecture / Hexagonal Architecture
- Flujo de dependencias: Infrastructure → Application → Domain
- Las interfaces (puertos) están en Domain, no en Infrastructure

## Validación

### Sin Errores de Compilación
```bash
# Verificación de errores (0 encontrados)
✓ No hay errores de sintaxis
✓ Todas las referencias actualizadas correctamente
✓ Los imports son consistentes
```

### Tests Sugeridos
1. Verificar que todos los casos de uso funcionan correctamente
2. Ejecutar suite de tests unitarios
3. Validar integración end-to-end

## Próximos Pasos Recomendados

1. **Actualizar Tests**
   - Revisar y actualizar tests unitarios para usar nuevas ubicaciones
   - Asegurar cobertura de casos de uso con mocks de Domain/Interfaces

2. **Documentación**
   - Actualizar diagramas de arquitectura
   - Documentar convenciones de uso de puertos

3. **Validación de Módulos**
   - Verificar que módulos externos no dependan de rutas antiguas
   - Actualizar documentación de módulos si es necesario

## Impacto en Módulos Externos

⚠️ **BREAKING CHANGE para módulos que usen interfaces del core:**

Si algún módulo externo estaba importando:
```php
use Flexi\Infrastructure\Interfaces\ModuleStateManagerInterface;
use Flexi\Infrastructure\Factories\ModuleDetectorInterface;
use Flexi\Application\Commands\NotFoundCommand;
```

Debe actualizar a:
```php
use Flexi\Domain\Interfaces\ModuleStateManagerInterface;
use Flexi\Domain\Interfaces\ModuleDetectorInterface;
use Flexi\Domain\Commands\NotFoundCommand;
```

## Conclusión

Esta refactorización corrige las violaciones arquitecturales identificadas, alineando el código con los principios de arquitectura hexagonal y SOLID. El sistema ahora tiene una separación clara de responsabilidades y un flujo de dependencias correcto, lo que mejora significativamente la mantenibilidad, testabilidad y extensibilidad del core.

---

**Referencias:**
- [Hexagonal Architecture](https://alistair.cockburn.us/hexagonal-architecture/)
- [Clean Architecture by Robert C. Martin](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
- [Dependency Inversion Principle](https://en.wikipedia.org/wiki/Dependency_inversion_principle)
