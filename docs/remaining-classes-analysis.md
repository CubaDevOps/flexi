# Análisis de Clases Restantes en Domain/Classes

## Clases Analizadas

### 1. **Route.php** ❌ NO es Domain
**Ubicación actual:** `Domain/Classes/Route.php`
**Recomendación:** `Infrastructure/Http/Route.php`

**Justificación:**
- Maneja conceptos HTTP (GET, POST, PUT, etc.)
- Es una preocupación de infraestructura de transporte
- Trabaja con `RequestHandlerInterface` de PSR
- Es específico de implementación web/HTTP

---

### 2. **Service.php, ServiceClassDefinition.php, ServiceFactoryDefinition.php** ❌ NO es Domain
**Ubicación actual:** `Domain/Classes/`
**Recomendación:** `Infrastructure/DependencyInjection/`

**Justificación:**
- Son parte del sistema de Dependency Injection
- Configuran cómo se construyen servicios (Factory, Class)
- Es una preocupación de infraestructura, no lógica de negocio
- El contenedor DI ya está en `Infrastructure/DependencyInjection/`

---

### 3. **Log.php** ✅ Puede quedarse en Domain
**Ubicación actual:** `Domain/Classes/Log.php`
**Recomendación:** `Domain/Entities/Log.php` o `Domain/ValueObjects/Log.php`

**Justificación:**
- Representa un concepto de dominio (un log entry)
- NO hace I/O (solo estructura datos)
- Implementa `LogInterface` del dominio
- Es un Value Object o Entity según si tiene identidad o no

**Alternativa:** Si se considera que log es infraestructura pura, mover a `Infrastructure/Logging/`

---

### 4. **PlainTextMessage.php** ⚠️ Borderline (puede estar en Domain o Application)
**Ubicación actual:** `Domain/Classes/PlainTextMessage.php`
**Recomendación:** `Domain/ValueObjects/PlainTextMessage.php` o `Application/DTO/PlainTextMessage.php`

**Justificación:**
- Es un Value Object que representa un mensaje de texto
- Implementa `MessageInterface` del dominio
- Es inmutable (solo se crea con datos)
- NO tiene lógica de negocio compleja
- Es usado como respuesta de los casos de uso

**Decisión:** Mantener en Domain pero en la carpeta correcta (ValueObjects)

---

### 5. **Collection.php, ObjectCollection.php** ✅ Pueden quedarse en Domain
**Ubicación actual:** `Domain/Classes/`
**Recomendación:** `Domain/Collections/Collection.php` y `Domain/Collections/ObjectCollection.php`

**Justificación:**
- Son estructuras de datos reutilizables del dominio
- No tienen dependencias de I/O o infraestructura
- Implementan `CollectionInterface` del dominio
- Son conceptos genéricos que pueden contener entities/value objects

---

### 6. **DummySearchCriteria.php** ✅ Domain
**Ubicación actual:** `Domain/Classes/`
**Recomendación:** `Domain/Criteria/DummySearchCriteria.php`

**Justificación:**
- Implementa `CriteriaInterface` del dominio
- Es un patrón de dominio (Specification Pattern)
- Representa criterios de búsqueda del dominio

---

## Resumen de Movimientos Recomendados

### A Mover a Infrastructure:
```
Domain/Classes/Route.php → Infrastructure/Http/Route.php
Domain/Classes/Service.php → Infrastructure/DependencyInjection/Service.php
Domain/Classes/ServiceClassDefinition.php → Infrastructure/DependencyInjection/ServiceClassDefinition.php
Domain/Classes/ServiceFactoryDefinition.php → Infrastructure/DependencyInjection/ServiceFactoryDefinition.php
```

### A Reorganizar dentro de Domain:
```
Domain/Classes/Log.php → Domain/Entities/Log.php (o Domain/ValueObjects/)
Domain/Classes/PlainTextMessage.php → Domain/ValueObjects/PlainTextMessage.php
Domain/Classes/Collection.php → Domain/Collections/Collection.php
Domain/Classes/ObjectCollection.php → Domain/Collections/ObjectCollection.php
Domain/Classes/DummySearchCriteria.php → Domain/Criteria/DummySearchCriteria.php
```

---

## Nueva Estructura Propuesta

```
src/
├── Domain/
│   ├── Collections/                # ← NUEVO
│   │   ├── Collection.php
│   │   └── ObjectCollection.php
│   ├── Criteria/                   # ← NUEVO
│   │   └── DummySearchCriteria.php
│   ├── Entities/
│   │   └── Log.php                 # ← MOVER (si tiene identidad)
│   ├── Events/                     # ✓ Ya creado
│   ├── ValueObjects/
│   │   ├── PlainTextMessage.php    # ← MOVER
│   │   └── Log.php                 # ← MOVER (si es inmutable)
│   └── Classes/                    # ← ELIMINAR (vacío después de movimientos)
│
└── Infrastructure/
    ├── DependencyInjection/
    │   ├── Container.php
    │   ├── Service.php              # ← MOVER
    │   ├── ServiceClassDefinition.php  # ← MOVER
    │   └── ServiceFactoryDefinition.php # ← MOVER
    └── Http/
        └── Route.php                # ← MOVER
```

---

## Orden de Ejecución Recomendado

1. **Fase 1: Crear nuevas estructuras**
   - `Domain/Collections/`
   - `Domain/Criteria/`

2. **Fase 2: Mover clases de Domain a subdirectorios**
   - Collection → Domain/Collections
   - PlainTextMessage → Domain/ValueObjects
   - Log → Domain/Entities o Domain/ValueObjects
   - DummySearchCriteria → Domain/Criteria

3. **Fase 3: Mover clases de Domain a Infrastructure**
   - Route → Infrastructure/Http
   - Service* → Infrastructure/DependencyInjection

4. **Fase 4: Actualizar imports**
   - Buscar y reemplazar todos los imports
   - Actualizar tests

5. **Fase 5: Ejecutar tests y validar**

6. **Fase 6: Eliminar archivos antiguos**

7. **Fase 7: Eliminar directorio Domain/Classes/ (si está vacío)**

---

## Impacto Estimado

- **Archivos a mover:** 9
- **Tests a actualizar:** ~15-20 aproximadamente
- **Configuraciones a actualizar:** Posiblemente ninguna (no están en JSON)
- **Riesgo:** Medio (muchas clases son utilizadas en varios lugares)

---

**Recomendación Final:** Proceder con movimientos en fases pequeñas, ejecutando tests después de cada fase.
