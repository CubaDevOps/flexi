# Migración Práctica: De Domain/Interfaces → Contracts

## 🎯 **Solución Simplificada y Correcta**

### ✅ **Lo que SÍ haremos:**
1. **Contracts Package** - Solo interfaces puras
2. **Mantener JSON Config** - Tu sistema de autodescubrimiento funciona perfecto
3. **Zero ModuleInterface** - No necesitas lógica repetitiva
4. **Migración gradual** - Cambiar imports progresivamente

### ❌ **Lo que NO haremos:**
- ❌ ModuleInterface con lógica repetitiva
- ❌ Cambiar sistema JSON de autodescubrimiento
- ❌ Framework registry complex
- ❌ Romper la funcionalidad existente

---

## 🔧 **Implementación Práctica**

### **Step 1: Update composer.json** ✅
```bash
composer update  # Para instalar el paquete contracts
```

### **Step 2: Ejemplo de Migración**

#### Antes (actual):
```php
// modules/Home/Application/RenderHome.php
use Flexi\Domain\Interfaces\DTOInterface;
use Flexi\Domain\Interfaces\HandlerInterface;

class RenderHome implements HandlerInterface
{
    public function handle(DTOInterface $dto): MessageInterface
    {
        // lógica...
    }
}
```

#### Después (con contracts):
```php
// modules/Home/Application/RenderHome.php
use Flexi\Contracts\DTOInterface;
use Flexi\Contracts\HandlerInterface;

class RenderHome implements HandlerInterface
{
    public function handle(DTOInterface $dto): MessageInterface
    {
        // lógica... (sin cambios)
    }
}
```

### **Step 3: Tu JSON Config NO CAMBIA**

```json
// modules/Home/Config/services.json (SIN CAMBIOS)
{
  "services": [
    {
      "name": "Flexi\\Modules\\Home\\Application\\RenderHome",
      "class": {
        "name": "Flexi\\Modules\\Home\\Application\\RenderHome",
        "arguments": ["@template_engine"]
      }
    }
  ]
}
```

```json
// modules/Home/Config/queries.json (SIN CAMBIOS)
{
  "handlers": [
    {
      "id": "Flexi\\Modules\\Home\\Domain\\HomePageDTO",
      "handler": "Flexi\\Modules\\Home\\Application\\RenderHome"
    }
  ]
}
```

### **Step 4: Core Autodiscovery NO CAMBIA**

```php
// Tu código existente en el core SIGUE IGUAL:
// - Container sigue leyendo services.json con glob patterns
// - Router sigue leyendo routes.json con glob patterns
// - Buses siguen leyendo commands/queries.json con glob patterns
// - Event system sigue leyendo listeners.json con glob patterns

// SOLO cambian los imports de interfaces
```

---

## 🎉 **Resultado Final**

### **Antes:**
```
Módulos → Domain/Interfaces → PSR (vendor)
        ↓
      Dependencia circular + Interfaces duplicadas
```

### **Después:**
```
Módulos → Contracts (PSR-first) ← Core
        ↓
      Zero dependencias circulares + Interfaces únicas
```

### **Beneficios Concretos:**
- ✅ **Zero cambios** en tu lógica JSON de autodescubrimiento
- ✅ **Zero ModuleInterface** innecesario
- ✅ **Solo cambiar imports** en los archivos existentes
- ✅ **Contracts puros** sin dependencies vendor en Domain
- ✅ **Mantener** toda la funcionalidad actual

---

## 🚀 **Plan de Ejecución**

1. **Install contracts package** ✅
2. **Migrate interfaces**: Domain/Interfaces → contracts/src (1 by 1)
3. **Update imports**: Find/replace en módulos y core
4. **Test**: Verificar que todo funciona igual
5. **Clean up**: Eliminar interfaces viejas

**Tiempo estimado**: 2-3 horas
**Risk**: Muy bajo (solo cambios de imports)
**Impact**: Alto (arquitectura limpia sin breaking changes)
