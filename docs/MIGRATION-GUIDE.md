# Migración al Sistema Modular con Composer

Este documento describe los pasos para migrar el framework Flexi al nuevo sistema modular con gestión de dependencias por Composer.

## Estado Actual

✅ Cada módulo ya tiene su propio `composer.json`
✅ El `composer.json` principal está configurado con repositories tipo `path`
✅ Comandos CLI para gestión de módulos creados
✅ Documentación completa del sistema

## Pasos de Migración

### 1. Backup del Proyecto

```bash
# Crear backup completo
cp -r /Users/cbatista8a/Sites/flexi /Users/cbatista8a/Sites/flexi-backup

# O usar git
git add .
git commit -m "Backup antes de migración modular"
git tag pre-modular-migration
```

### 2. Limpiar Autoloader Anterior

El autoloader de módulos en el composer.json principal ya fue removido:

```json
// REMOVIDO:
"Flexi\\Modules\\": "modules/"
```

### 3. Reinstalar Dependencias

```bash
cd /Users/cbatista8a/Sites/flexi

# Limpiar cache de composer
composer clear-cache

# Remover vendor y lock
rm -rf vendor/
rm composer.lock

# Instalar todo desde cero
composer install
```

Composer automáticamente:
- Creará symlinks desde `vendor/cubadevops/flexi-module-*` → `modules/*`
- Instalará las dependencias específicas de cada módulo
- Resolverá conflictos de versiones
- Generará el autoloader con las rutas correctas

### 4. Verificar Symlinks

```bash
# Verificar que los symlinks se crearon correctamente
ls -la vendor/cubadevops/

# Deberías ver algo como:
# flexi-contracts -> ../../../contracts
# flexi-module-auth -> ../../../modules/Auth
# flexi-module-cache -> ../../../modules/Cache
# etc.
```

### 5. Regenerar Autoloader

```bash
composer dump-autoload -o
```

### 6. Validar Módulos

```bash
# Usar el nuevo comando CLI
php bin/console modules:validate

# Listar todos los módulos
php bin/console modules:list

# Información detallada de un módulo
php bin/console modules:info Auth
```

### 7. Ejecutar Tests

```bash
# Tests unitarios
composer test

# Con cobertura
composer coverage

# Análisis estático
composer psalm
```

### 8. Verificar que Todo Funciona

```bash
# Si usas Docker
docker-compose up -d
podman exec flexi vendor/bin/phpunit

# Si es desarrollo local
php -S localhost:8000 -t public/
```

## Posibles Problemas y Soluciones

### Problema: Symlinks no se crean

**Causa**: Permisos o sistema de archivos no soporta symlinks

**Solución**:
```bash
# Forzar recreación de symlinks
rm -rf vendor/cubadevops/flexi-module-*
composer install --prefer-source
```

### Problema: Clases no encontradas

**Causa**: Autoloader desactualizado

**Solución**:
```bash
composer dump-autoload -o
```

### Problema: Conflictos de versiones

**Causa**: Dependencias incompatibles entre módulos

**Solución**:
```bash
# Ver árbol de dependencias
composer show --tree cubadevops/flexi-module-auth

# Actualizar dependencias
composer update
```

### Problema: Tests fallan

**Causa**: Paths incorrectos o módulos no cargados

**Solución**:
```bash
# Verificar que los módulos están instalados
composer show | grep flexi-module

# Limpiar cache de tests
rm -rf var/cache/*
```

## Gestión de Módulos Post-Migración

### Instalar un Módulo

```bash
composer require cubadevops/flexi-module-mynewmodule:@dev
```

### Desinstalar un Módulo

```bash
composer remove cubadevops/flexi-module-auth
```

### Actualizar Módulos

```bash
# Todos los módulos
composer update "cubadevops/flexi-module-*"

# Un módulo específico
composer update cubadevops/flexi-module-auth
```

### Agregar un Nuevo Módulo

1. Crear directorio:
```bash
mkdir -p modules/MyNewModule/{Domain,Infrastructure,Config}
```

2. Crear `composer.json`:
```bash
cat > modules/MyNewModule/composer.json << 'EOF'
{
  "name": "cubadevops/flexi-module-mynewmodule",
  "version": "1.0.0",
  "type": "flexi-module",
  "require": {
    "php": ">=7.4",
    "cubadevops/flexi-contracts": "@dev"
  },
  "autoload": {
    "psr-4": {
      "Flexi\\Modules\\MyNewModule\\": ""
    }
  },
  "extra": {
    "flexi": {
      "module-name": "MyNewModule",
      "module-type": "application"
    }
  }
}
EOF
```

3. Agregar al composer.json principal:
```bash
# Editar composer.json manualmente o usar:
composer config repositories.mynewmodule '{"type": "path", "url": "./modules/MyNewModule", "options": {"symlink": true}}'
composer require cubadevops/flexi-module-mynewmodule:@dev
```

## Comandos CLI Disponibles

### Listar Módulos

```bash
php bin/console modules:list
```

Salida esperada:
```
Total Modules: 10
Installed: 10

╔═══════════════╦════════════════════════════════════╦═════════╦═══════════╗
║ Module        ║ Package                            ║ Version ║ Status    ║
╠═══════════════╬════════════════════════════════════╬═════════╬═══════════╣
║ Auth          ║ cubadevops/flexi-module-auth       ║ 1.0.0   ║ Installed ║
║ Cache         ║ cubadevops/flexi-module-cache      ║ 1.0.0   ║ Installed ║
...
╚═══════════════╩════════════════════════════════════╩═════════╩═══════════╝
```

### Información de Módulo

```bash
php bin/console modules:info Auth
```

Salida esperada:
```json
{
  "name": "Auth",
  "package": "cubadevops/flexi-module-auth",
  "version": "1.0.0",
  "description": "Authentication and Authorization module",
  "dependencies": {
    "firebase/php-jwt": "^6.10",
    "psr/http-message": "^1.0|^2.0"
  },
  "provides": [
    "authentication",
    "authorization",
    "jwt-middleware"
  ]
}
```

### Validar Módulos

```bash
php bin/console modules:validate
```

Salida esperada:
```
Validating modules...

✓ Auth: Valid
✓ Cache: Valid
⚠ Home: 1 warning - Missing tests directory
✗ MyModule: Invalid - Missing composer.json

Total: 10 | Valid: 8 | Invalid: 1 | Warnings: 1
```

## Verificación Final

Checklist de verificación post-migración:

- [ ] `composer install` ejecutado sin errores
- [ ] Symlinks creados en `vendor/cubadevops/`
- [ ] `composer dump-autoload` ejecutado
- [ ] Tests unitarios pasando: `composer test`
- [ ] Análisis estático sin errores: `composer psalm`
- [ ] `modules:list` muestra todos los módulos
- [ ] `modules:validate` sin errores críticos
- [ ] Aplicación web funciona correctamente
- [ ] Comandos de consola funcionan

## Rollback (Si es necesario)

Si algo sale mal, puedes revertir:

```bash
# Opción 1: Desde backup
rm -rf /Users/cbatista8a/Sites/flexi
mv /Users/cbatista8a/Sites/flexi-backup /Users/cbatista8a/Sites/flexi

# Opción 2: Desde git tag
git reset --hard pre-modular-migration
```

## Próximos Pasos

Una vez completada la migración:

1. **Actualizar CI/CD**: Asegurar que el pipeline usa `composer install`
2. **Documentar para el equipo**: Compartir el nuevo flujo de trabajo
3. **Crear módulos adicionales**: Usar el nuevo sistema para nuevas funcionalidades
4. **Publicar módulos**: Considerar publicar módulos en repositorios Git separados
5. **Versionar contracts**: Establecer versionado estable para `flexi-contracts`

## Soporte

Si encuentras problemas:

1. Verifica los logs: `var/logs/app.log`
2. Ejecuta `modules:validate` para diagnóstico
3. Revisa la documentación: `docs/modular-system-with-composer.md`
4. Consulta el estado de composer: `composer diagnose`

## Beneficios Obtenidos

✅ Módulos completamente independientes
✅ Gestión automática de dependencias
✅ Versionado granular por módulo
✅ Instalación/desinstalación dinámica
✅ Symlinks para desarrollo eficiente
✅ Herramientas CLI de gestión
✅ Arquitectura lista para producción

---

**Última actualización**: 2 de noviembre de 2025
