# Visualización de la Refactorización del Core

## Diagrama: Arquitectura Hexagonal Actual

```text
┌──────────────────────────────────────────────────────────────────┐
│                        EXTERIOR (USERS/HTTP/CLI)                │
└──────────────────────────────────────────────────────────────────┘
              │
              ↓
┌──────────────────────────────────────────────────────────────────┐
│                    PUERTOS DE ENTRADA (ADAPTERS)                 │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │ • Router (Http)                                              │ │
│  │ • ConsoleApplication (CLI)                                   │ │
│  │ • WebApplication                                             │ │
│  └─────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────┘
              │
              ↓
┌──────────────────────────────────────────────────────────────────┐
│                    CAPA DE APLICACIÓN                            │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │ Buses (CQRS):                                               │ │
│  │ • CommandBus → ejecuta comandos → Event                     │ │
│  │ • QueryBus → ejecuta queries → Response                     │ │
│  │ • EventBus → dispatch eventos → Listeners                   │ │
│  │                                                              │ │
│  │ Services: DTOFactory, LoggerEventListener                   │ │
│  └─────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────┘
              │
              ↓
┌──────────────────────────────────────────────────────────────────┐
│                    CAPA DE DOMINIO                               │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │ Event (implementa EventInterface)                           │ │
│  │ EventListener (abstracta genérica) ← ❌ DEBE IR A CONTRACTS │ │
│  │ AnyCriteria (genérica para filtrado)                        │ │
│  │ ValueObjects: Operator, Order, ServiceType ← ❌ MOVIMIENTO  │ │
│  │ Exceptions del Framework                                    │ │
│  └─────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────┘
              │
              ↓
┌──────────────────────────────────────────────────────────────────┐
│                    INFRAESTRUCTURA                               │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │ DI Container (corazón)                                      │ │
│  │ Configuration Management                                    │ │
│  │ Cache (File, InMemory)                                      │ │
│  │ Session Storage (Native)                                    │ │
│  │ Persistence (Logging)                                       │ │
│  │ Middlewares (Auth) ← ❌ DEBEN IR A MÓDULO                   │ │
│  │ UI (Rendering, Templates) ← ⚠️ REVISAR MOVIMIENTO          │ │
│  │ Utils (Traits genéricas) ← ❌ DEBEN IR A CONTRACTS         │ │
│  └─────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────┘
              │
              ↓
┌──────────────────────────────────────────────────────────────────┐
│                PUERTOS DE SALIDA / EXTERNALS                     │
│  • DB, File System, APIs externas, Logger                       │
└──────────────────────────────────────────────────────────────────┘
```

## 🔄 Comparativa: ANTES vs DESPUÉS

### ANTES (Incorrecto)

```
src/Domain/ValueObjects/
├── Operator.php           ← Específica de DI, no de dominio
├── Order.php              ← Específica de DI, no de dominio
├── ServiceType.php        ← Específica de DI, no de dominio
└── ...

src/Domain/Events/
├── EventListener.php      ← Genérica, debería ser reusable
└── ...

src/Infrastructure/Utils/
├── CacheKeyGeneratorTrait.php   ← Genérica, necesitada por módulos
├── FileHandlerTrait.php         ← Genérica, necesitada por módulos
├── GlobFileReader.php           ← Genérica, necesitada por módulos
└── JsonFileReader.php           ← Genérica, necesitada por módulos

src/Infrastructure/Middlewares/
├── AuthCheckMiddleware.php      ← Negocio (Auth), no infraestructura
├── JWTAuthMiddleware.php        ← Negocio (Auth), no infraestructura
└── ...

src/Infrastructure/Classes/
├── PsrLogger.php          ← Genérica, debería estar en Contracts
└── ...

src/Infrastructure/Ui/
├── Template.php           ← Genérica, reutilizable
├── HtmlRender.php         ← Genérica, reutilizable
├── TemplateLocator.php    ← Genérica, reutilizable
└── ...
```

### DESPUÉS (Correcto)

```
contracts/src/Classes/
├── EventListener.php      ← Movido: ahora reusable
├── PsrLogger.php          ← Movido: ahora reusable (opcional)
├── Traits/
│   ├── CacheKeyGeneratorTrait.php   ← Movido: ahora reusable
│   ├── FileHandlerTrait.php         ← Movido: ahora reusable
│   ├── GlobFileReader.php           ← Movido: ahora reusable
│   └── JsonFileReader.php           ← Movido: ahora reusable
└── ...

src/Infrastructure/DependencyInjection/ValueObjects/
├── Operator.php           ← Movido: donde realmente pertenece
├── Order.php              ← Movido: donde realmente pertenece
└── ServiceType.php        ← Movido: donde realmente pertenece

modules/Auth/
├── Infrastructure/Middlewares/
│   ├── AuthCheckMiddleware.php      ← Movido: lógica de negocio
│   └── JWTAuthMiddleware.php        ← Movido: lógica de negocio
└── ...

modules/Ui/  (Opcional - si se requiere reutilización)
├── Infrastructure/
│   └── Ui/
│       ├── Template.php             ← Movido: rendering genérico
│       ├── HtmlRender.php           ← Movido: rendering genérico
│       └── TemplateLocator.php      ← Movido: rendering genérico
└── ...

src/Infrastructure/  (Limpio y enfocado)
├── Bus/
│   ├── CommandBus.php           ✅ CQRS
│   ├── QueryBus.php             ✅ CQRS
│   └── EventBus.php             ✅ Event Sourcing
├── DependencyInjection/
│   ├── Container.php            ✅ Core DI
│   ├── Service.php              ✅ Core DI
│   └── ValueObjects/            ✅ Específicas de DI
├── Http/
│   ├── Route.php                ✅ Enrutamiento
│   └── Router.php               ✅ Enrutamiento
├── Cache/
│   ├── FileCache.php            ✅ Implementación
│   └── InMemoryCache.php        ✅ Implementación
└── ... (Solo orquestación del framework)
```

---

## 🎯 Flujo de Dependencias Después

```
┌────────────────────────────────────────────────────────┐
│          MÓDULOS (Plug & Play)                         │
│  • modules/Auth                                        │
│  • modules/Home                                        │
│  • modules/WebHooks                                    │
│  • modules/HealthCheck                                │
│  • modules/DevTools                                    │
│  • modules/ErrorHandling                              │
│  • modules/Ui (nuevo)                                 │
└────────────────────────────────────────────────────────┘
        ↓ (SOLO dependen de)
┌────────────────────────────────────────────────────────┐
│          CONTRACTS (Único punto de coupling)           │
│  • Interfaces PSR-compatibles                         │
│  • Clases base genéricas (EventListener, etc)         │
│  • ValueObjects genéricas (ID, LogLevel, etc)         │
│  • Traits reutilizables                               │
│  • MessageInterface, DTOInterface, etc                │
└────────────────────────────────────────────────────────┘
        ↑ (implementan)
┌────────────────────────────────────────────────────────┐
│          CORE (src - Orquestación)                     │
│  • Buses: CommandBus, QueryBus, EventBus              │
│  • DI: Container, Service Definitions                 │
│  • Config: Configuration, Repository                  │
│  • Http: Router                                        │
│  • Cache: FileCache, InMemoryCache                    │
│  • App: DTOFactory, NotFoundCommand                   │
│  • Domain: Event, Exceptions, AnyCriteria            │
└────────────────────────────────────────────────────────┘
```

**Key point:** Módulos NUNCA importan de `src/`, SOLO de `contracts/`

---

## 📊 Tabla de Decisiones por Clase

| Clase | Ubicación Actual | Ubicación Nueva | Razón | Prioridad |
|-------|------------------|-----------------|-------|-----------|
| EventListener | Domain/Events | contracts/Classes | Genérica reutilizable | ALTA |
| CacheKeyGeneratorTrait | Utils | contracts/Classes/Traits | Genérica reutilizable | ALTA |
| FileHandlerTrait | Utils | contracts/Classes/Traits | Genérica reutilizable | ALTA |
| GlobFileReader | Utils | contracts/Classes/Traits | Genérica reutilizable | ALTA |
| JsonFileReader | Utils | contracts/Classes/Traits | Genérica reutilizable | ALTA |
| Operator | Domain/ValueObjects | Infra/DependencyInjection/ValueObjects | Específica de DI | ALTA |
| Order | Domain/ValueObjects | Infra/DependencyInjection/ValueObjects | Específica de DI | ALTA |
| ServiceType | Domain/ValueObjects | Infra/DependencyInjection/ValueObjects | Específica de DI | ALTA |
| AuthCheckMiddleware | Infra/Middlewares | modules/Auth/Infra/Middlewares | Lógica de negocio | MEDIA |
| JWTAuthMiddleware | Infra/Middlewares | modules/Auth/Infra/Middlewares | Lógica de negocio | MEDIA |
| PsrLogger | Infra/Classes | contracts/Classes | Genérica reutilizable | MEDIA |
| Template | Infra/Ui | modules/Ui/Infra/Ui o mantener | Revisar necesidad | BAJA |
| HtmlRender | Infra/Ui | modules/Ui/Infra/Ui o mantener | Revisar necesidad | BAJA |
| TemplateLocator | Infra/Ui | modules/Ui/Infra/Ui o mantener | Revisar necesidad | BAJA |
| HttpHandler | Infra/Classes | ? | Revisar uso | BAJA |
| Route | Infra/Http | ¿Mantener o contracts? | Revisar si es genérica | BAJA |
| InFileLogRepository | Infra/Persistence | ¿Mantener? | Implementación default | BAJA |
| NativeSessionStorage | Infra/Session | ¿Mantener? | Implementación default | BAJA |

---

## 🔗 Mapa de Impacto de Cambios

### Alto Impacto (Requiere actualizar muchos archivos)

```
Mover EventListener
├─ src/Application/EventListeners/LoggerEventListener.php
│  ├─ usa: extends EventListener
│  └─ update: use Flexi\Contracts\Classes\EventListener
├─ Potencialmente: todos los módulos que tengan listeners
└─ Necesita: regenerar composer autoload

Mover Traits de Utils
├─ src/Infrastructure/Bus/CommandBus.php
│  ├─ usa: use JsonFileReader; use GlobFileReader;
│  └─ update: use Flexi\Contracts\Classes\Traits\JsonFileReader;
├─ src/Infrastructure/Bus/QueryBus.php
│  ├─ usa: use JsonFileReader; use GlobFileReader;
│  └─ update: idem
├─ src/Infrastructure/Bus/EventBus.php
│  ├─ usa: use JsonFileReader; use GlobFileReader;
│  └─ update: idem
└─ Potencialmente: módulos que usen estos traits
```

### Medio Impacto (Requiere actualizar algunos archivos)

```
Mover ValueObjects de DI
├─ src/Infrastructure/DependencyInjection/ServicesDefinitionParser.php
│  ├─ usa: ServiceType
│  └─ update: use Flexi\Infrastructure\DependencyInjection\ValueObjects\ServiceType;
├─ Posiblemente: Container, Service, ServiceClassDefinition
└─ Necesita: verificar que no hay imports en módulos

Crear módulo Auth y mover Middlewares
├─ Crear: modules/Auth/Infrastructure/Middlewares/
├─ Mover: AuthCheckMiddleware.php, JWTAuthMiddleware.php
├─ Crear: modules/Auth/Config/services.json (si usar DI)
└─ Actualizar: rutas/configuraciones que referencien middlewares
```

### Bajo Impacto (Cambios internos o sin cambios)

```
Revisar Clases Especiales
├─ Route.php: si es interna de Router, sin cambios
├─ Template.php: revisar si módulos la necesitan
├─ HttpHandler.php: analizar si es necesaria
└─ PsrLogger.php: solo si se decide mover a contracts
```

---

## 🧪 Validaciones Post-Refactor

### 1. Verificar que no hay imports cíclicos

```bash
# En el root del proyecto
composer validate --strict

# Si hay problemas:
composer dump-autoload -o
```

### 2. Verificar que los buses funcionan

```bash
podman exec flexi vendor/bin/phpunit tests/Infrastructure/Bus/ -v
```

### 3. Verificar que el DI funciona

```bash
podman exec flexi vendor/bin/phpunit tests/Infrastructure/DependencyInjection/ -v
```

### 4. Verificar que los módulos cargan correctamente

```bash
podman exec flexi vendor/bin/phpunit tests/ -v
```

### 5. Verificar que los namespaces están correctos

```bash
# Buscar imports antiguos
grep -r "use Flexi\\Domain\\ValueObjects\\Operator" src/
grep -r "use Flexi\\Domain\\Events\\EventListener" src/
grep -r "use Flexi\\Infrastructure\\Utils\\.*Trait" src/
```

---

## 📈 Beneficios de Cada Cambio

### Mover EventListener a Contracts
- ✅ Los módulos pueden crear sus propios listeners genéricos
- ✅ No hay duplicación de código base
- ✅ Máxima reutilización

### Mover Traits a Contracts
- ✅ Módulos pueden usar CacheKeyGenerator, FileHandler, etc
- ✅ Centraliza utilidades comunes
- ✅ Evita duplicación

### Mover ValueObjects de DI a Infrastructure
- ✅ Claridad: son específicas del sistema de inyección
- ✅ Domain queda puro sin detalles de infraestructura
- ✅ Respeta capas hexagonales

### Crear módulo Auth
- ✅ Middlewares de autenticación en su propio contexto
- ✅ Fácil de remover/reemplazar
- ✅ Sigue patrón de módulos plug-and-play
- ✅ Otros módulos pueden depender de Auth

---

## 🎓 Resultado Final: Arquitectura Limpia

```
FLUJO DE ARQUITECTURA LIMPIA

Request/Command/Event
        ↓
    Router/Bus
        ↓
  Application Layer  (DTOFactory, Services)
        ↓
  Domain Layer  (Event, Criteria, Exceptions)
        ↓
  Infrastructure  (Container, Config, Persistence)
        ↓
Response/Event Dispatch/Repository Result


SEPARACIÓN CLARA:

Módulos                    Core                    Contracts
─────────────────────────────────────────────────────────────
Home/           ─────────→ only depends on ──────→ Interfaces
Auth/                      Contracts              Classes
WebHooks/                  never imports          ValueObjects
HealthCheck/               from src/              Traits
ErrorHandling/                                    Utils
Ui/

✅ Arquitectura hexagonal perfecta
✅ CQRS bien implementado
✅ Event Sourcing activo
✅ Sin dependencias cruzadas
✅ Máxima modularidad
✅ Máxima reusabilidad
```

---

## 🚀 Checklist de Implementación

### Pre-Refactor
- [ ] Crear rama `refactor/core-architecture-cleanup`
- [ ] Backup actual working
- [ ] Todos los tests verdes

### Fase 1: Traits a Contracts
- [ ] Crear `contracts/src/Classes/Traits/`
- [ ] Copiar traits
- [ ] Actualizar imports en core y buses
- [ ] Tests verdes
- [ ] Commit

### Fase 2: EventListener a Contracts
- [ ] Copiar `EventListener.php` a `contracts/src/Classes/`
- [ ] Actualizar imports en `LoggerEventListener`
- [ ] Tests verdes
- [ ] Commit

### Fase 3: ValueObjects de DI
- [ ] Crear `src/Infrastructure/DependencyInjection/ValueObjects/`
- [ ] Mover Operator, Order, ServiceType
- [ ] Actualizar imports
- [ ] Tests verdes
- [ ] Commit

### Fase 4: Módulo Auth
- [ ] Crear estructura `modules/Auth/`
- [ ] Mover middlewares
- [ ] Crear Config si es necesario
- [ ] Actualizar servicios.json si aplica
- [ ] Tests verdes
- [ ] Commit

### Fase 5: Limpieza
- [ ] Eliminar archivos duplicados del core
- [ ] Ejecutar full test suite
- [ ] `composer dump-autoload -o`
- [ ] Validar imports
- [ ] PR review
- [ ] Merge a rama principal

---

## ✨ Conclusión

Esta refactorización transforma el core de un lugar "mixto" en una **máquina de orquestación limpia y enfocada**, mientras que **todas las clases reutilizables viven en Contracts** y **la lógica de negocio reside en módulos especializados**.

El resultado es un framework **profesional, escalable y mantenible**. 🎉
