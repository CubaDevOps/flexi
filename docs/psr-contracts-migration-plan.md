# Plan de Migración: Framework → PSR + Contracts

## 🎯 Objetivo
Resolver el enigma de dependencias manteniendo compatibilidad PSR y separación de responsabilidades.

## 📋 Fases de Implementación

### **Fase 1: Preparación de Contratos** ✅
- [x] Crear paquete `contracts/` independiente
- [x] Definir interfaces puras (sin dependencias vendor)
- [x] Extender PSR solo cuando sea apropiado
- [x] Crear contratos para: DTO, Handler, Module, Bus, Framework

### **Fase 2: Refactoring del Core**
```
src/
├── Application/           # ← Casos de uso, orquestadores
│   ├── Bootstrap/         # ← Framework initialization
│   ├── Registry/          # ← Module registry
│   └── Services/          # ← Core services
├── Infrastructure/        # ← Implementaciones concretas PSR
│   ├── Bus/              # ← Bus implementations
│   ├── Http/             # ← PSR-7, PSR-15 implementations
│   ├── Cache/            # ← PSR-6, PSR-16 implementations
│   └── DependencyInjection/ # ← PSR-11 implementation
└── Domain/               # ← Solo conceptos puros de dominio
    ├── ValueObjects/
    ├── Entities/
    └── Collections/
```

### **Fase 3: Mantener Autodescubrimiento JSON**
```
modules/
├── Home/
│   ├── Config/
│   │   ├── services.json   # ← Ya existe, mantener
│   │   ├── routes.json     # ← Ya existe, mantener
│   │   ├── queries.json    # ← Ya existe, mantener
│   │   ├── commands.json   # ← Ya existe, mantener
│   │   └── listeners.json  # ← Ya existe, mantener
│   ├── Application/        # ← Use cases específicos
│   ├── Domain/            # ← Conceptos del módulo
│   └── Infrastructure/     # ← Implementaciones específicas
└── DevTools/
    └── Config/
        └── services.json   # ← Sistema actual funciona perfectamente
```

### **Fase 4: Aprovechar Sistema Existente**
- ✅ Modules usan JSON config (ya implementado)
- ✅ Core autodescubre via glob patterns (ya funciona)
- ✅ Zero configuración adicional requerida
- ✅ Comunicación solo via contracts + PSR events

## 🔄 Flujo de Dependencias

### ✅ CORRECTO (Desired State)
```
┌─────────────────┐    ┌──────────────────┐
│    Modules      │───▶│    Contracts     │
│                 │    │   (Pure PSR)     │
└─────────────────┘    └──────────────────┘
                                ▲
┌─────────────────┐             │
│   Core/Infra    │─────────────┘
│                 │
└─────────────────┘
```

### ❌ INCORRECTO (Current State)
```
┌─────────────────┐    ┌──────────────────┐
│    Modules      │───▶│   Core/Domain    │
│                 │    │  (Mixed PSR +    │
└─────────────────┘    │   Framework)     │
                       └──────────────────┘
```

## 🛠️ Implementación

### Step 1: Update composer.json
```json
{
  "repositories": [
    {
      "type": "path",
      "url": "./contracts"
    }
  ],
  "require": {
    "cubadevops/flexi-contracts": "@dev"
  }
}
```

### **Step 2: Migrate Current Interfaces**
```php
// En lugar de crear ModuleContract, migrar interfaces existentes:
src/Domain/Interfaces/DTOInterface.php → contracts/src/DTOContract.php
src/Domain/Interfaces/HandlerInterface.php → contracts/src/HandlerContract.php
src/Domain/Interfaces/EntityInterface.php → contracts/src/EntityContract.php
src/Domain/Interfaces/ValueObjectInterface.php → contracts/src/ValueObjectContract.php
src/Domain/Interfaces/RepositoryInterface.php → contracts/src/RepositoryContract.php

// Mantener estructura JSON existente - NO cambiar
modules/*/Config/*.json ← KEEP AS IS
```

### **Step 3: Update Core to Use Contracts**
```php
// Core usa contracts en lugar de interfaces locales
// Autodescubrimiento JSON sigue funcionando igual
// Zero cambios en la lógica de glob patterns
```

## 📊 Beneficios

### 🟢 Ventajas Arquitectónicas
- **Zero Circular Dependencies**: Direccional clara hacia contratos
- **PSR Compliance**: Solo donde tiene sentido
- **Module Isolation**: Módulos completamente independientes
- **Easy Testing**: Contracts are mockeable
- **Plugin Architecture**: Modules as plugins

### 🟢 Ventajas Prácticas
- **Faster Development**: Contracts claros para módulos
- **Easier Maintenance**: Separación clara de responsabilidades
- **Better Documentation**: Contracts son auto-documentación
- **Framework Evolution**: Core puede cambiar sin afectar modules

## 🚀 Next Steps

1. **Migrate Current Interfaces** → Move to contracts/
2. **Update Module Structure** → Implement ModuleContract
3. **Create Framework Registry** → Central service discovery
4. **Update Dependencies** → Point to contracts instead of core
5. **Test & Validate** → Ensure no circular deps

---

**Status**: 🟡 Design Complete - Ready for Implementation
**Complexity**: Medium-High (architectural change)
**Impact**: High (solves the core design problem)
**Risk**: Medium (requires careful migration)