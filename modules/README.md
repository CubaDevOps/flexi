# Módulos de Flexi Framework

Este directorio contiene todos los módulos del framework. Cada módulo es un paquete Composer independiente que se autodescubre y gestiona automáticamente.

## Gestión Rápida

### Ver módulos disponibles

```bash
composer modules:list
```

### Sincronizar módulos

```bash
composer modules:sync
```

### Instalar un módulo

```bash
php bin/console modules:install ModuleName
```

### Desinstalar un módulo

```bash
php bin/console modules:uninstall ModuleName
```

## Crear un Nuevo Módulo

```bash
# 1. Crear estructura
mkdir -p modules/MyModule/{Domain,Infrastructure,Config}

# 2. Crear composer.json
cat > modules/MyModule/composer.json << 'EOF'
{
  "name": "cubadevops/flexi-module-mymodule",
  "version": "1.0.0",
  "type": "flexi-module",
  "require": {
    "php": ">=7.4",
    "cubadevops/flexi-contracts": "@dev"
  },
  "autoload": {
    "psr-4": {
      "CubaDevOps\\Flexi\\Modules\\MyModule\\": ""
    }
  }
}
EOF

# 3. Sincronizar
composer modules:sync
```

## Cómo Funciona

1. Cada módulo tiene su propio `composer.json`
2. El comando `modules:sync` autodescubre módulos
3. Composer crea symlinks: `vendor/cubadevops/flexi-module-*` → `modules/*`
4. Los cambios son inmediatos (no requiere reinstalar)

## Documentación Completa

- [Sistema Modular Automático](../docs/modular-system-automatic.md)
- [Guía de Migración](../docs/MIGRATION-GUIDE.md)

## Módulos Disponibles

- **Auth**: Autenticación y autorización (JWT, Basic Auth)
- **Cache**: Sistema de cache (File, InMemory)
- **DevTools**: Herramientas de desarrollo
- **ErrorHandling**: Manejo de errores
- **HealthCheck**: Endpoints de salud
- **Home**: Página de inicio
- **Logging**: Sistema de logs (PSR-3)
- **Session**: Gestión de sesiones
- **Ui**: Motor de templates
- **WebHooks**: Manejo de webhooks
