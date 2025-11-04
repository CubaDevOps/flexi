# Propuesta de Refactorización de la Arquitectura del Core

**Fecha:** 27 de octubre de 2025
**Objetivo:** Asegurar que el core contenga SOLO clases necesarias para orquestar la lógica del framework, mientras que clases genéricas se trasladan a `Contracts` o a módulos específicos.

---

## 📋 Resumen Ejecutivo

Actualmente, el `src` (core) contiene:
- ✅ **Correcto:** Buses (CQRS), Contenedor DI, Configuración, Routing
- ❌ **Incorrecto:** Clases genéricas, middlewares de negocio, utilidades compartibles, ValueObjects específicos de DI

**Resultado esperado:** Un core limpio y enfocado en orquestación, con máxima reutilización a través de Contracts y máxima modularidad.

---

## 🔍 Análisis de la Estructura Actual

### `src/Domain`

#### ✅ Está bien ubicado:
- **`Events/Event.php`** - Implementación core de eventos para CQRS + Event Sourcing
- **`Exceptions/`** - Excepciones específicas del framework (mantener aquí)

#### ⚠️ Incorrectamente ubicado:
- **`ValueObjects/Operator.php`** - Específica del sistema de DI, no del dominio
- **`ValueObjects/Order.php`** - Específica del sistema de DI, no del dominio
- **`ValueObjects/ServiceType.php`** - Específica del sistema de DI, no del dominio
- **`Events/EventListener.php`** - Clase abstracta genérica → debería estar en Contracts

**Razón:** Estos ValueObjects no representan conceptos de dominio; son internos del framework. Pertenecen a `Infrastructure/DependencyInjection/ValueObjects`.

---

### `src/Application`

#### ✅ Está bien ubicado:
- **`Commands/NotFoundCommand.php`** - Null object pattern para el bus de comandos (caso de uso interno)
- **`Services/DTOFactory.php`** - Factory para crear DTOs desde data (servicio del framework)
- **`EventListeners/LoggerEventListener.php`** - Listener específico para logging del core

#### ℹ️ Sin cambios necesarios:
La capa Application está correcta, pero es muy simple. Principalmente orquesta casos de uso internos del framework.

---

### `src/Infrastructure`

#### ✅ Está bien ubicado (Core de Orquestación):
- **`Bus/`** - CommandBus, QueryBus, EventBus (núcleo CQRS)
- **`Cache/`** - Implementaciones de cache del framework
- **`DependencyInjection/`** - Container, Service definitions, parser
- **`Factories/`** - Factories para componentes principales del framework
- **`Http/Router.php`** - Enrutador central del framework

#### ⚠️ Incorrectamente ubicado:

**1. Middlewares de Negocio:**
```
src/Infrastructure/Middlewares/
├── AuthCheckMiddleware.php    ← ❌ Negocio, no infraestructura
└── JWTAuthMiddleware.php      ← ❌ Negocio, no infraestructura
```

**Problema:** Estos middlewares son de **lógica de negocio** (autenticación), no de infraestructura.
**Solución:** Mover a módulo `modules/Auth/Infrastructure/Middlewares/` o similar.

---

**2. Rutas Específicas (Route.php):**
```
src/Infrastructure/Http/Route.php
```

**Problema:** Route es una entidad que los módulos también necesitarán para definir rutas.
**Solución:** Considerar mover a Contracts como ValueObject genérico, o dejar aquí si los módulos usan Router del core.

---

**3. Session Storage:**
```
src/Infrastructure/Session/NativeSessionStorage.php
```

**Problema:** Específica de PHP nativo. Si hay otros tipos de session (Redis, etc), pueden estar en módulos.
**Análisis:** Podría quedarse en core si es la implementación por defecto, o moverse a módulo.

---

**4. Template/UI Classes:**
```
src/Infrastructure/Ui/
├── HtmlRender.php            ← ⚠️ Genérico de rendering
├── Template.php              ← ⚠️ Genérico de templates
├── TemplateLocator.php       ← ⚠️ Genérico de ubicación de templates
├── Cli/                      ← ℹ️ Específico de CLI (podría ser módulo)
└── Web/                      ← ℹ️ Específico de Web (podría ser módulo)
```

**Problema:** Clases genéricas de rendering que los módulos necesitarán.
**Solución:** Mover a Contracts o crear un módulo `modules/Ui/`.

---

**5. Persistence Layer:**
```
src/Infrastructure/Persistence/InFileLogRepository.php
```

**Problema:** Específica de logs del framework.
**Análisis:** Podría quedarse como implementación por defecto o moverse a módulo de logging.

---

**6. Utilities (Traits):**
```
src/Infrastructure/Utils/
├── CacheKeyGeneratorTrait.php     ← ❌ Reutilizable en módulos
├── FileHandlerTrait.php           ← ❌ Reutilizable en módulos
├── GlobFileReader.php             ← ❌ Reutilizable en módulos
├── JsonFileReader.php             ← ❌ Reutilizable en módulos
├── OSDetector.php                 ← ℹ️ Específica del framework
└── ...
```

**Problema:** Traits genéricas que deberían estar disponibles para módulos.
**Solución:** Mover a `contracts/src/Traits/` o `contracts/src/Classes/`.

---

**7. Classes Genéricas:**
```
src/Infrastructure/Classes/
├── Configuration.php               ← ✅ Core del framework
├── ConfigurationRepository.php     ← ✅ Core del framework
├── HttpHandler.php                 ← ⚠️ Revisar si es genérica
├── ObjectBuilder.php               ← ❌ Genérica, debería tener interfaz en Contracts
└── PsrLogger.php                   ← ❌ Genérica, debería estar en Contracts
```

---

## 📍 Propuesta de Reorganización

### 1️⃣ Mover a `contracts/src/Classes/`

**Por qué:** Clases genéricas y traits que los módulos necesitarán reutilizar.

```
contracts/src/Classes/
├── Collection.php                  ✅ Ya está
├── Log.php                         ✅ Ya está
├── PlainTextMessage.php            ✅ Ya está
├── ObjectCollection.php            ✅ Ya está
├── EventListener.php               ← MOVER: src/Domain/Events/EventListener.php
├── PsrLogger.php                   ← MOVER: src/Infrastructure/Classes/PsrLogger.php
├── AbstractObjectBuilder.php       ← NUEVO: Clase base para ObjectBuilder
├── Traits/
│   ├── CacheKeyGeneratorTrait.php  ← MOVER: src/Infrastructure/Utils/
│   ├── FileHandlerTrait.php        ← MOVER: src/Infrastructure/Utils/
│   ├── GlobFileReader.php          ← MOVER: src/Infrastructure/Utils/
│   └── JsonFileReader.php          ← MOVER: src/Infrastructure/Utils/
└── Utils/
    └── OSDetector.php              ← MOVER: src/Infrastructure/Utils/
```

**Nota:** Actualizar todos los `use` statements en el core y en los buses.

---

### 2️⃣ Mover a `contracts/src/ValueObjects/`

**Por qué:** ValueObjects genéricas que módulos también necesitarán.

```
contracts/src/ValueObjects/
├── CollectionType.php              ✅ Ya está
├── ID.php                          ✅ Ya está
├── LogLevel.php                    ✅ Ya está
├── Version.php                     ✅ Ya está
└── Route.php                       ← MOVER: src/Infrastructure/Http/Route.php
                                      (O mantener en core si es específica)
```

---

### 3️⃣ Reorganizar en `src/Infrastructure/`

**Estructura mejorada del core:**

```
src/
├── Domain/
│   ├── Events/
│   │   ├── Event.php               ✅ Mantener
│   │   └── EventListener.php       ❌ MOVER a contracts/src/Classes/
│   ├── Criteria/
│   │   └── AnyCriteria.php         ✅ Mantener (genérica pero interna)
│   ├── Exceptions/
│   │   ├── ContainerException.php  ✅ Mantener
│   │   ├── ServiceNotFoundException.php ✅ Mantener
│   │   └── InvalidArgumentCacheException.php ✅ Mantener
│   └── ValueObjects/
│       ├── Operator.php            ❌ MOVER a Infrastructure/DependencyInjection/ValueObjects/
│       ├── Order.php               ❌ MOVER a Infrastructure/DependencyInjection/ValueObjects/
│       └── ServiceType.php         ❌ MOVER a Infrastructure/DependencyInjection/ValueObjects/
│
├── Application/
│   ├── Commands/
│   │   └── NotFoundCommand.php     ✅ Mantener
│   ├── Services/
│   │   └── DTOFactory.php          ✅ Mantener
│   └── EventListeners/
│       └── LoggerEventListener.php ✅ Mantener
│
└── Infrastructure/
    ├── Bus/
    │   ├── CommandBus.php          ✅ Mantener
    │   ├── QueryBus.php            ✅ Mantener
    │   └── EventBus.php            ✅ Mantener
    ├── Cache/
    │   ├── FileCache.php           ✅ Mantener
    │   └── InMemoryCache.php       ✅ Mantener
    ├── Classes/
    │   ├── Configuration.php       ✅ Mantener
    │   ├── ConfigurationRepository.php ✅ Mantener
    │   ├── HttpHandler.php         ✅ Revisar
    │   ├── ObjectBuilder.php       ✅ Mantener (PERO implementar ObjectBuilderInterface de Contracts)
    │   └── PsrLogger.php           ❌ MOVER a contracts/src/Classes/
    ├── DependencyInjection/
    │   ├── Container.php           ✅ Mantener
    │   ├── Service.php             ✅ Mantener
    │   ├── ServiceClassDefinition.php ✅ Mantener
    │   ├── ServiceFactoryDefinition.php ✅ Mantener
    │   ├── ServicesDefinitionParser.php ✅ Mantener
    │   └── ValueObjects/           ← NUEVA CARPETA
    │       ├── Operator.php        ← MOVER desde Domain/ValueObjects/
    │       ├── Order.php           ← MOVER desde Domain/ValueObjects/
    │       └── ServiceType.php     ← MOVER desde Domain/ValueObjects/
    ├── Factories/
    │   ├── BusFactory.php          ✅ Mantener
    │   ├── CacheFactory.php        ✅ Mantener
    │   ├── ContainerFactory.php    ✅ Mantener
    │   └── RouterFactory.php       ✅ Mantener
    ├── Http/
    │   ├── Route.php               ⚠️ REVISAR (ver sección siguiente)
    │   └── Router.php              ✅ Mantener
    ├── Middlewares/
    │   ├── AuthCheckMiddleware.php ❌ MOVER a modules/Auth/Infrastructure/Middlewares/
    │   └── JWTAuthMiddleware.php   ❌ MOVER a modules/Auth/Infrastructure/Middlewares/
    ├── Persistence/
    │   └── InFileLogRepository.php ℹ️ Considerar mover a módulo de logging
    ├── Session/
    │   └── NativeSessionStorage.php ✅ Mantener (implementación por defecto)
    ├── Ui/
    │   ├── HtmlRender.php          ⚠️ REVISAR (ver sección siguiente)
    │   ├── Template.php            ⚠️ REVISAR (ver sección siguiente)
    │   ├── TemplateLocator.php     ⚠️ REVISAR (ver sección siguiente)
    │   ├── Cli/
    │   │   ├── CliInput.php        ✅ Específico de CLI
    │   │   ├── CliInputParser.php  ✅ Específico de CLI
    │   │   ├── CliType.php         ✅ Específico de CLI
    │   │   ├── CommandHandler.php  ✅ Específico de CLI
    │   │   ├── ConsoleApplication.php ✅ Específico de CLI
    │   │   ├── ConsoleOutputFormatter.php ✅ Específico de CLI
    │   │   ├── EventHandler.php    ✅ Específico de CLI
    │   │   └── QueryHandler.php    ✅ Específico de CLI
    │   └── Web/
    │       └── Application.php     ✅ Específico de Web
    └── Utils/
        ├── CacheKeyGeneratorTrait.php ❌ MOVER a contracts/src/Classes/Traits/
        ├── FileHandlerTrait.php       ❌ MOVER a contracts/src/Classes/Traits/
        ├── GlobFileReader.php         ❌ MOVER a contracts/src/Classes/Traits/
        ├── JsonFileReader.php         ❌ MOVER a contracts/src/Classes/Traits/
        └── OSDetector.php             ❌ MOVER a contracts/src/Utils/
```

---

### 4️⃣ Crear Módulo de Autenticación

**Crear estructura:**
```
modules/Auth/
├── Config/
├── Domain/
├── Infrastructure/
│   └── Middlewares/
│       ├── AuthCheckMiddleware.php ← MOVER
│       └── JWTAuthMiddleware.php   ← MOVER
├── Application/
├── tests/
└── README.md
```

---

### 5️⃣ Decisiones sobre Clases "REVISAR"

#### A. `Route.php`

**Opciones:**
1. ✅ **Mantener en core:** Si solo el framework define rutas
2. ⚠️ **Mover a Contracts:** Si los módulos necesitan definir rutas
3. 🤔 **Crear en módulo web:** Si es específica de HTTP

**Recomendación:** Hacer que los módulos definen rutas a través de archivos de configuración (como ya hace CommandBus/QueryBus), no mediante clases. Mantener `Route.php` como interna del Router.

---

#### B. `Template.php`, `HtmlRender.php`, `TemplateLocator.php`

**Opciones:**
1. ✅ **Mantener en core:** Son genéricas pero específicas de rendering
2. ⚠️ **Mover a Contracts:** Si múltiples módulos las necesitarán
3. 🤔 **Mover a módulo Web UI:** Si son solo para web

**Recomendación:** Mover a `modules/Ui/Infrastructure/` como módulo compartible. Exponer interfaces en Contracts.

---

#### C. `InFileLogRepository.php`

**Opciones:**
1. ✅ **Mantener como default:** Implementación por defecto de logging
2. ⚠️ **Mover a módulo:** Si hay otras implementaciones
3. 🤔 **Generalizar:** Crear interfaz en Contracts

**Recomendación:** Mantener en core como implementación por defecto. Permitir que otros módulos proporcionen implementaciones alternativas a través del Contenedor.

---

#### D. `ObjectBuilder.php`

**Problema actual:** ObjectBuilder es una clase concreta en Infrastructure.
**Análisis:** ¿Existe ObjectBuilderInterface en Contracts?

**Recomendación:**
1. Si no existe, crear `ObjectBuilderInterface` en Contracts
2. Hacer que ObjectBuilder implemente dicha interfaz
3. Permitir que módulos proporcionen implementaciones alternativas

---

## 📊 Impacto de Cambios

### Archivos a Mover (Prioridad Alta)

| Archivo Actual | Destino | Razón |
|---|---|---|
| `src/Domain/Events/EventListener.php` | `contracts/src/Classes/EventListener.php` | Clase genérica reutilizable |
| `src/Infrastructure/Utils/CacheKeyGeneratorTrait.php` | `contracts/src/Classes/Traits/CacheKeyGeneratorTrait.php` | Trait genérica |
| `src/Infrastructure/Utils/FileHandlerTrait.php` | `contracts/src/Classes/Traits/FileHandlerTrait.php` | Trait genérica |
| `src/Infrastructure/Utils/GlobFileReader.php` | `contracts/src/Classes/Traits/GlobFileReader.php` | Trait genérica |
| `src/Infrastructure/Utils/JsonFileReader.php` | `contracts/src/Classes/Traits/JsonFileReader.php` | Trait genérica |
| `src/Domain/ValueObjects/Operator.php` | `src/Infrastructure/DependencyInjection/ValueObjects/Operator.php` | Específica de DI |
| `src/Domain/ValueObjects/Order.php` | `src/Infrastructure/DependencyInjection/ValueObjects/Order.php` | Específica de DI |
| `src/Domain/ValueObjects/ServiceType.php` | `src/Infrastructure/DependencyInjection/ValueObjects/ServiceType.php` | Específica de DI |

### Archivos a Mover (Prioridad Media)

| Archivo Actual | Destino | Razón | Estado |
|---|---|---|---|
| `src/Infrastructure/Middlewares/AuthCheckMiddleware.php` | `modules/Auth/Infrastructure/Middlewares/` | Lógica de negocio | Crear módulo Auth |
| `src/Infrastructure/Middlewares/JWTAuthMiddleware.php` | `modules/Auth/Infrastructure/Middlewares/` | Lógica de negocio | Crear módulo Auth |
| `src/Infrastructure/Classes/PsrLogger.php` | `contracts/src/Classes/PsrLogger.php` | Genérica, pero revisar necesidad |
| `src/Infrastructure/Ui/*` | `modules/Ui/Infrastructure/` | Genérica de rendering | Crear módulo Ui |

### Archivos a Revisar (Prioridad Baja)

| Archivo | Análisis | Acción |
|---|---|---|
| `src/Infrastructure/Http/Route.php` | ¿Genérica o específica de core? | Mantener en core si es interna del Router |
| `src/Infrastructure/Persistence/InFileLogRepository.php` | ¿Default o específica? | Mantener como implementación por defecto |
| `src/Infrastructure/Session/NativeSessionStorage.php` | ¿Default o específica? | Mantener como implementación por defecto |
| `src/Infrastructure/Classes/HttpHandler.php` | ¿Genérica o específica? | Revisar uso en el codebase |

---

## 🔄 Actualizaciones de Namespaces Requeridas

### Actualizar imports en Contracts

Si `EventListener.php` se mueve a Contracts:
```php
// Antes
use CubaDevOps\Flexi\Domain\Events\EventListener;

// Después
use CubaDevOps\Flexi\Contracts\Classes\EventListener;
```

**Archivos a actualizar:**
- `src/Application/EventListeners/LoggerEventListener.php`
- Cualquier módulo que extienda EventListener

---

### Actualizar imports en Core

Si ValueObjects de DI se mueven:
```php
// Antes
use CubaDevOps\Flexi\Domain\ValueObjects\Operator;

// Después
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\ValueObjects\Operator;
```

**Archivos a actualizar:**
- `src/Infrastructure/DependencyInjection/Container.php` (si usa ServiceType)
- Tests relacionados

---

## ✅ Validación Post-Refactor

### Tests a ejecutar:
```bash
# Tests del core
podman exec flexi vendor/bin/phpunit tests/

# Verificar que los buses siguen funcionando
podman exec flexi vendor/bin/phpunit tests/Infrastructure/Bus/

# Verificar que el DI sigue funcionando
podman exec flexi vendor/bin/phpunit tests/Infrastructure/DependencyInjection/
```

### Verificaciones:
- [ ] Todos los imports se actualizan correctamente
- [ ] Tests pasan
- [ ] Módulos aún pueden importar desde Contracts
- [ ] No hay imports cíclicos
- [ ] Composer autoload se regenera

---

## 🎯 Beneficios de Esta Reorganización

| Beneficio | Descripción |
|-----------|------------|
| **Claridad** | El core es estrictamente orquestación; no hay confusión |
| **Reusabilidad** | Traits y clases genéricas en Contracts disponibles para módulos |
| **Mantenibilidad** | Lógica de negocio (Auth) separada del framework |
| **Escalabilidad** | Estructura lista para crecer con nuevos módulos |
| **Hexagonal** | Arquitectura perfectamente alineada |
| **Modularidad** | Módulos plug-and-play sin dependencias del core |

---

## 🚀 Plan de Implementación

### Fase 1: Preparación (1 sesión)
- [ ] Crear rama de feature: `refactor/core-architecture-cleanup`
- [ ] Documentar estado actual (este documento)
- [ ] Identificar puntos de quiebre

### Fase 2: Movimientos de Bajo Impacto (1-2 sesiones)
- [ ] Mover traits a Contracts
- [ ] Mover EventListener a Contracts
- [ ] Mover ValueObjects de DI a Infrastructure/DependencyInjection
- [ ] Actualizar imports
- [ ] Tests verdes

### Fase 3: Movimientos de Medio Impacto (1-2 sesiones)
- [ ] Crear módulo Auth
- [ ] Mover middlewares de Auth a módulo
- [ ] Configurar módulo Auth correctamente
- [ ] Tests verdes

### Fase 4: Revisión de Casos Especiales (1 sesión)
- [ ] Revisar Route, Template, HttpHandler, PsrLogger
- [ ] Tomar decisiones finales
- [ ] Implementar si corresponde

### Fase 5: Limpieza Final (1 sesión)
- [ ] Eliminar archivos movidos del core
- [ ] Regenerar composer autoload
- [ ] Run full test suite
- [ ] PR review y merge

---

## 📝 Conclusión

Esta refactorización asegura que:
1. **El core es limpio:** Solo lógica de orquestación del framework
2. **Las clases genéricas son compartibles:** Disponibles en Contracts para módulos
3. **La arquitectura es clara:** Hexagonal + CQRS + Event Sourcing
4. **Los módulos son independientes:** Dependen de Contracts, no del core
5. **La escalabilidad es posible:** Nueva estructura lista para crecer

El resultado es un framework más mantenible, testeable y profesional. 🎉
