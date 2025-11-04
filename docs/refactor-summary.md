# Resumen Ejecutivo de Refactorización

## 🎯 Objetivo

Reorganizar el core (`src`) del framework Flexi para:
- Contener SOLO lógica de orquestación necesaria
- Mover clases genéricas a `contracts/` para reutilización
- Mover lógica de negocio a módulos especializados
- Cumplir con Arquitectura Hexagonal + CQRS + Event Sourcing

---

## ✅ Estado Actual vs. Meta

| Aspecto | Actualmente | Meta |
|---------|------------|------|
| **ValueObjects de DI** | En Domain (incorrecto) | En Infrastructure/DependencyInjection |
| **Traits genéricas** | En Infrastructure/Utils | En contracts/Classes/Traits |
| **EventListener base** | En Domain/Events | En contracts/Classes |
| **Middlewares de Auth** | En Infrastructure | En modules/Auth |
| **PsrLogger** | En Infrastructure/Classes | En contracts/Classes |
| **Template/Ui** | En Infrastructure/Ui | Revisar si modularizar |

---

## 📊 Análisis de Impacto

### 🔴 Problemas Críticos Identificados

1. **ValueObjects específicos de DI en Domain**
   - Operator, Order, ServiceType no son conceptos de dominio
   - Deben estar en `Infrastructure/DependencyInjection/ValueObjects/`

2. **Traits genéricas en Infrastructure**
   - CacheKeyGeneratorTrait, FileHandlerTrait, etc.
   - Necesitadas por módulos, deben estar en Contracts

3. **EventListener genérica en Domain**
   - Base para todos los listeners
   - Debe estar en Contracts para que módulos la extiendan

4. **Middlewares de Auth en Infrastructure**
   - Lógica de negocio, no infraestructura del framework
   - Debe estar en módulo Auth especializado

---

## 🗺️ Estructura Después de Refactor

### CORE (src) - Máquina de Orquestación

```
src/
├── Domain/
│   ├── Events/ (Event, no EventListener)
│   ├── Criteria/
│   └── Exceptions/
├── Application/
│   ├── Commands/
│   ├── Services/
│   └── EventListeners/
└── Infrastructure/
    ├── Bus/ (CommandBus, QueryBus, EventBus)
    ├── DependencyInjection/ (Container + ValueObjects propios)
    ├── Http/ (Route, Router)
    ├── Cache/
    ├── Session/
    ├── Persistence/
    └── Factories/
```

### CONTRACTS - Punto Único de Dependencia

```
contracts/src/
├── Classes/
│   ├── EventListener (BASE)
│   ├── PsrLogger
│   ├── Collection
│   ├── Log
│   └── Traits/
│       ├── CacheKeyGeneratorTrait
│       ├── FileHandlerTrait
│       ├── GlobFileReader
│       └── JsonFileReader
├── Interfaces/ (24 interfaces PSR-compatibles)
├── ValueObjects/
│   ├── ID
│   ├── LogLevel
│   ├── CollectionType
│   └── Version
└── Utils/
```

### MÓDULOS - Lógica de Negocio

```
modules/
├── Auth/
│   └── Infrastructure/Middlewares/
│       ├── AuthCheckMiddleware
│       └── JWTAuthMiddleware
├── Home/
├── WebHooks/
├── HealthCheck/
├── ErrorHandling/
├── DevTools/
└── Ui/ (opcional, si modularizar)
```

---

## 🔄 Matriz de Movimientos

### 🔴 PRIORIDAD ALTA (Sin estas, la arquitectura es confusa)

| Clase | De | A | Razón |
|-------|----|----|-------|
| Operator | `src/Domain/ValueObjects/` | `src/Infrastructure/DependencyInjection/ValueObjects/` | Específica de DI |
| Order | `src/Domain/ValueObjects/` | `src/Infrastructure/DependencyInjection/ValueObjects/` | Específica de DI |
| ServiceType | `src/Domain/ValueObjects/` | `src/Infrastructure/DependencyInjection/ValueObjects/` | Específica de DI |
| CacheKeyGeneratorTrait | `src/Infrastructure/Utils/` | `contracts/src/Classes/Traits/` | Reutilizable |
| FileHandlerTrait | `src/Infrastructure/Utils/` | `contracts/src/Classes/Traits/` | Reutilizable |
| GlobFileReader | `src/Infrastructure/Utils/` | `contracts/src/Classes/Traits/` | Reutilizable |
| JsonFileReader | `src/Infrastructure/Utils/` | `contracts/src/Classes/Traits/` | Reutilizable |
| EventListener | `src/Domain/Events/` | `contracts/src/Classes/` | Base genérica |

### 🟡 PRIORIDAD MEDIA (Mejoran la modularidad)

| Clase | De | A | Razón |
|-------|----|----|-------|
| AuthCheckMiddleware | `src/Infrastructure/Middlewares/` | `modules/Auth/Infrastructure/Middlewares/` | Lógica de negocio |
| JWTAuthMiddleware | `src/Infrastructure/Middlewares/` | `modules/Auth/Infrastructure/Middlewares/` | Lógica de negocio |
| PsrLogger | `src/Infrastructure/Classes/` | `contracts/src/Classes/` | Potencialmente reutilizable |

### 🟢 PRIORIDAD BAJA (Revisar necesidad)

| Clase | De | A | Razón |
|-------|----|----|-------|
| Template | `src/Infrastructure/Ui/` | ¿Mantener o mover? | Revisar si genérica |
| HtmlRender | `src/Infrastructure/Ui/` | ¿Mantener o mover? | Revisar si genérica |
| TemplateLocator | `src/Infrastructure/Ui/` | ¿Mantener o mover? | Revisar si genérica |

---

## 📈 Beneficios Esperados

### Claridad
- ✅ Core = Orquestación del framework, nada más
- ✅ Contracts = Punto único de extensión
- ✅ Módulos = Lógica de negocio especializada

### Mantenibilidad
- ✅ Cambios en Domain no afectan infraestructura
- ✅ Clases genéricas centralizadas
- ✅ Menos código duplicado

### Escalabilidad
- ✅ Nuevos módulos fáciles de agregar
- ✅ Reutilización de traits y bases
- ✅ Sin dependencias cruzadas

### Profesionalismo
- ✅ Arquitectura hexagonal perfecta
- ✅ CQRS bien separado
- ✅ Event Sourcing activo
- ✅ PSR-compatibilidad clara

---

## ⚠️ Riesgos y Mitigación

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|--------|-----------|
| Imports no actualizados | MEDIA | ALTO | Buscar imports antiguos, tests |
| Ciclos de dependencia | BAJA | ALTO | `composer validate`, tests |
| Módulos sin actualizar | MEDIA | MEDIO | Generar lista de cambios |
| Tests fallando | MEDIA | MEDIO | Suite completa pre/post |

---

## 🛠️ Plan de Implementación

### Fase 1: Preparación (0.5 días)
- [ ] Crear rama `refactor/core-architecture-cleanup`
- [ ] Documentar estado actual (✅ HECHO)
- [ ] Identificar todos los imports

### Fase 2: Movimientos de Bajo Riesgo (1 día)
- [ ] Mover traits de Utils → contracts/Classes/Traits
- [ ] Mover EventListener → contracts/Classes
- [ ] Actualizar todos los imports
- [ ] Tests verdes

### Fase 3: Movimientos de Medio Riesgo (1 día)
- [ ] Mover ValueObjects de DI
- [ ] Actualizar imports en DependencyInjection
- [ ] Tests verdes

### Fase 4: Crear Módulo Auth (0.5 días)
- [ ] Crear estructura `modules/Auth`
- [ ] Mover middlewares
- [ ] Configurar si necesario
- [ ] Tests verdes

### Fase 5: Validación y Merge (0.5 días)
- [ ] Suite completa de tests
- [ ] Validar imports
- [ ] PR review
- [ ] Merge a main

**Total estimado:** 3-4 días de desarrollo

---

## 📝 Archivos de Documentación Generados

1. **core-refactor-architecture-proposal.md** (Principal)
   - Análisis detallado por carpeta
   - Propuestas específicas
   - Impacto de cambios
   - Validaciones

2. **core-refactor-visualization.md** (Complementario)
   - Diagramas ASCII
   - Tablas de comparativa
   - Checklist de implementación

---

## ✨ Conclusión

Este refactor **NO es cosmético**, es **arquitectónico**:

- **Antes:** Core confuso con mezcla de roles
- **Después:** Arquitectura limpia, profesional y escalable

El framework pasará de ser una estructura "funcional" a una **referencia de buenas prácticas** de arquitectura en PHP.

---

## 📞 Próximos Pasos

1. **Review:** Validar que la propuesta está de acuerdo con la visión
2. **Planificación:** Estimar tiempo y recursos
3. **Implementación:** Ejecutar fases según plan
4. **Testing:** Validar que todo sigue funcionando
5. **Merge:** Integrar cambios a main
6. **Documentación:** Actualizar guías de desarrollo

🚀 **¡A construir un framework profesional!**
