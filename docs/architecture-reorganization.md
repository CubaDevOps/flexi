# Reorganización de Arquitectura - Clean Architecture y CQRS

## Resumen de Cambios Implementados

### 1. Nueva Estructura de Directorios

Se han creado las siguientes estructuras siguiendo Clean Architecture:

```
src/
├── Domain/
│   ├── Events/                    # ← NUEVO: Eventos de dominio
│   ├── Services/                  # ← NUEVO: Servicios de dominio puros
│   └── (mantiene Entities, ValueObjects, Interfaces, Exceptions, etc.)
│
├── Application/
│   ├── Commands/                  # ← NUEVO: Comandos CQRS
│   ├── Queries/                   # ← NUEVO: Queries CQRS
│   ├── DTO/                       # ← NUEVO: DTOs de aplicación
│   └── (mantiene UseCase, EventListeners)
│
└── Infrastructure/
    └── (mantiene Bus, Cache, Controllers, etc.)
```

### 2. Movimientos de Clases Realizados

#### A. DTOs Movidos a Application Layer (CQRS)

**Comandos:**
- `Domain/DTO/CommandListDTO.php` → `Application/Commands/ListCommandsCommand.php`

**Queries:**
- `Domain/DTO/QueryListDTO.php` → `Application/Queries/ListQueriesQuery.php`
- `Domain/DTO/EmptyVersionDTO.php` → `Application/Queries/GetVersionQuery.php`

**Justificación:** Estos DTOs son específicos de casos de uso de aplicación (comandos y queries), no son parte del dominio puro. Siguen el patrón CQRS correctamente.

#### B. Eventos de Dominio Movidos

**De `Domain/Classes/` a `Domain/Events/`:**
- `Event.php` → `Domain/Events/Event.php`
- `EventListener.php` → `Domain/Events/EventListener.php`

**Justificación:** Los eventos son conceptos de dominio pero merecen su propio namespace para claridad y separación de concerns.

#### C. Template Movido a Infrastructure

**De `Domain/Classes/` a `Infrastructure/Ui/`:**
- `Template.php` → `Infrastructure/Ui/Template.php`

**Justificación:** Template maneja I/O de archivos (file_exists, pathinfo, etc.), lo cual es una preocupación de infraestructura, no de dominio.

### 3. Clases que Permanecen en Domain/DTO

Las siguientes clases se mantienen en `Domain/DTO/` porque son utilidades compartidas:

- **DummyDTO.php** - DTO base para testing y casos especiales
- **NotFoundCliCommand.php** - Null Object pattern usado por los buses

### 4. Actualizaciones de Imports

Se actualizaron todos los imports en los siguientes archivos:

**Application Layer:**
- `Application/UseCase/ListCommands.php` - Ahora usa `ListCommandsCommand`
- `Application/UseCase/ListQueries.php` - Ahora usa `ListQueriesQuery`
- `Application/EventListeners/LoggerEventListener.php` - Ahora usa `Domain\Events\EventListener`

**Infrastructure Layer:**
- `Infrastructure/Controllers/HealthController.php` - Ahora usa `GetVersionQuery`
- `Infrastructure/Controllers/NotFoundController.php` - Ahora usa `Infrastructure\Ui\Template`
- `Infrastructure/Controllers/WebHookController.php` - Ahora usa `Domain\Events\Event`
- `Infrastructure/Bus/CommandBus.php` - Ahora usa `Domain\Events\Event`
- `Infrastructure/Bus/QueryBus.php` - Ahora usa `Domain\Events\Event`
- `Infrastructure/Http/Router.php` - Ahora usa `Domain\Events\Event`
- `Infrastructure/Ui/HtmlRender.php` - Ahora usa la nueva ubicación de `Template`
- `Infrastructure/Ui/Cli/EventHandler.php` - Ahora usa `Domain\Events\Event`

**Domain Interfaces:**
- `Domain/Interfaces/TemplateEngineInterface.php` - Ahora usa `Infrastructure\Ui\Template`

### 5. Archivos de Configuración Actualizados

**src/Config/queries.json:**
```json
{
  "handlers": [
    {
      "id": "CubaDevOps\\Flexi\\Application\\Queries\\GetVersionQuery",
      "cli_alias": "version",
      "handler": "CubaDevOps\\Flexi\\Application\\UseCase\\Health"
    },
    {
      "id": "CubaDevOps\\Flexi\\Application\\Queries\\ListQueriesQuery",
      "cli_alias": "query:list",
      "handler": "CubaDevOps\\Flexi\\Application\\UseCase\\ListQueries"
    },
    {
      "id": "CubaDevOps\\Flexi\\Application\\Commands\\ListCommandsCommand",
      "cli_alias": "command:list",
      "handler": "CubaDevOps\\Flexi\\Application\\UseCase\\ListCommands"
    }
  ]
}
```

### 6. Principios de Clean Architecture Aplicados

✅ **Regla de Dependencias Respetada:**
- Domain NO depende de Application ni Infrastructure
- Application orquesta Domain usando sus interfaces
- Infrastructure implementa interfaces definidas en Domain

✅ **Separación de Concerns:**
- DTOs de aplicación (Commands/Queries) → Application layer
- Eventos de dominio → Domain/Events
- Preocupaciones de I/O (Template) → Infrastructure

✅ **CQRS Implementado:**
- Comandos claramente separados en Application/Commands
- Queries claramente separados en Application/Queries
- Nomenclatura consistente: *Command para comandos, *Query para queries

### 7. Próximos Pasos Recomendados

Las siguientes clases en `Domain/Classes/` aún necesitan revisión para determinar su ubicación correcta:

1. **Collection.php, ObjectCollection.php**
   - Considerar mover a `Domain/Collections/` o evaluar si son realmente Aggregates

2. **Route.php**
   - Considerar mover a `Infrastructure/Http/Route.php` (es una preocupación de HTTP)

3. **Service.php, ServiceClassDefinition.php, ServiceFactoryDefinition.php**
   - Considerar mover a `Infrastructure/DependencyInjection/` (son preocupaciones de DI)

4. **Log.php**
   - Evaluar si debe estar en `Domain/Entities/` o `Domain/Services/`

5. **PlainTextMessage.php**
   - Evaluar si debe estar en `Domain/ValueObjects/` o `Infrastructure/`

### 8. Archivos Nuevos Creados

- `src/Application/Commands/ListCommandsCommand.php`
- `src/Application/Queries/ListQueriesQuery.php`
- `src/Application/Queries/GetVersionQuery.php`
- `src/Domain/Events/Event.php`
- `src/Domain/Events/EventListener.php`
- `src/Infrastructure/Ui/Template.php`

### 9. Notas Importantes

⚠️ **Los archivos originales en Domain/Classes y Domain/DTO aún existen.**
Se recomienda:
1. Ejecutar los tests para verificar que todo funciona correctamente
2. Eliminar los archivos antiguos una vez confirmado que la migración es exitosa
3. Actualizar los tests que referencien las ubicaciones antiguas

⚠️ **Tests que necesitan actualización:**
- `tests/Domain/Classes/EventTest.php`
- `tests/Domain/Classes/TemplateTest.php`
- `tests/Domain/Classes/HtmlRenderTest.php`
- `tests/Infrastructure/Controllers/WebHookControllerTest.php`

### 10. Beneficios de Esta Reorganización

✅ **Claridad arquitectónica**: Cada capa tiene responsabilidades bien definidas
✅ **CQRS explícito**: Comandos y queries claramente separados
✅ **Testabilidad mejorada**: Dependencias más claras y separadas
✅ **Mantenibilidad**: Estructura más fácil de entender y navegar
✅ **Escalabilidad**: Base sólida para agregar nuevos features
✅ **Conformidad con principios SOLID y Clean Architecture**

---

**Fecha de reorganización:** 15 de octubre de 2025
**Rama:** architecture-improvements
