# Sistema de Gestión Modular Automática con Composer

## Resumen

Este documento describe el sistema de gestión modular **automática** para Flexi Framework, donde los módulos son autodescubiertos y gestionados sin necesidad de editar manualmente el `composer.json`.

## Filosofía del Sistema

### Principios Clave

1. **Autodescubrimiento**: Los módulos en `modules/` son detectados automáticamente
2. **Sin Edición Manual**: No necesitas editar `composer.json` principal
3. **Symlinks Automáticos**: Composer crea symlinks desde `vendor/` hacia `modules/`
4. **Comandos CLI**: Gestiona módulos con comandos simples
5. **Sincronización Automática**: `composer install/update` sincronizan módulos automáticamente

## Arquitectura

### Flujo de Trabajo

```
modules/Auth/
    └── composer.json (define: cubadevops/flexi-module-auth)
           ↓
    [módulos:sync detecta el módulo]
           ↓
    Actualiza composer.json automáticamente:
    - Agrega repository: path ./modules/Auth
    - Agrega require: cubadevops/flexi-module-auth @dev
           ↓
    Composer crea symlink:
    vendor/cubadevops/flexi-module-auth → modules/Auth
```

### Modos de Operación

El sistema soporta dos modos transparentemente:

#### Modo Desarrollo (Local)

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "./modules/Auth",
      "options": { "symlink": true }
    }
  ],
  "require": {
    "cubadevops/flexi-module-auth": "@dev"
  }
}
```

**Ventajas**:
- ✅ Cambios inmediatos (symlink)
- ✅ No requiere commits
- ✅ Desarrollo rápido

#### Modo Producción (Git)

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/cubadevops/flexi-module-auth"
    }
  ],
  "require": {
    "cubadevops/flexi-module-auth": "^1.0"
  }
}
```

**Ventajas**:
- ✅ Versionado estable
- ✅ Control de versiones
- ✅ Instalación desde Git

## Comandos Disponibles

### 1. Sincronizar Módulos (Auto-descubrimiento)

```bash
php bin/console modules:sync
```

**Qué hace**:
- Escanea `modules/` en busca de directorios con `composer.json`
- Actualiza `composer.json` principal automáticamente
- Agrega repositories tipo `path` con symlinks
- Agrega paquetes a `require` con versión `@dev`
- Ejecuta `composer update` para aplicar cambios
- Remueve módulos que ya no existen

**Cuándo usarlo**:
- Después de clonar el repositorio
- Después de crear un nuevo módulo
- Después de eliminar un módulo
- Para sincronizar cambios

**Ejemplo de salida**:
```json
{
  "discovered": 10,
  "added": 2,
  "updated": 8,
  "removed": 0,
  "modules": {
    "Auth": "already exists",
    "MyNewModule": "added"
  },
  "composer_update": {
    "executed": true,
    "success": true
  }
}
```

### 2. Instalar Módulo

```bash
php bin/console modules:install Auth
```

**Qué hace**:
- Verifica que el módulo existe en `modules/Auth`
- Lee su `composer.json` para obtener el nombre del paquete
- Actualiza `composer.json` principal (repository + require)
- Ejecuta `composer update {paquete}`

**Ejemplo**:
```bash
# Instalar módulo Auth
php bin/console modules:install auth

# Salida:
{
  "success": true,
  "message": "Module 'Auth' installed successfully",
  "package": "cubadevops/flexi-module-auth",
  "version": "1.0.0",
  "action": "installed"
}
```

### 3. Desinstalar Módulo

```bash
php bin/console modules:uninstall Auth
```

**Qué hace**:
- Lee el `composer.json` del módulo para obtener el nombre del paquete
- Actualiza `composer.json` principal (remueve require y repository)
- Ejecuta `composer remove {paquete}`
- **NOTA**: No elimina el directorio físico `modules/Auth`

**Ejemplo**:
```bash
# Desinstalar módulo Auth
php bin/console modules:uninstall auth

# Salida:
{
  "success": true,
  "message": "Module 'Auth' uninstalled successfully",
  "package": "cubadevops/flexi-module-auth",
  "action": "uninstalled"
}
```

### 4. Listar Módulos

```bash
php bin/console modules:list
```

Muestra todos los módulos disponibles con su estado.

### 5. Información de Módulo

```bash
php bin/console modules:info Auth
```

Muestra detalles completos de un módulo específico.

### 6. Validar Módulos

```bash
php bin/console modules:validate
```

Valida la configuración de todos los módulos.

## Scripts de Composer

El `composer.json` principal incluye scripts útiles:

### Sincronización Automática

```bash
composer modules:sync
```

Equivalente a `php bin/console modules:sync`

### Listar y Validar

```bash
composer modules:list
composer modules:validate
```

### Hooks Automáticos

El sistema se sincroniza automáticamente después de `composer install` o `composer update`:

```json
{
  "scripts": {
    "post-install-cmd": ["@modules:sync"],
    "post-update-cmd": ["@modules:sync"]
  }
}
```

## Flujos de Trabajo Comunes

### Crear un Nuevo Módulo

```bash
# 1. Crear estructura de directorios
mkdir -p modules/Payment/{Domain,Infrastructure,Config}

# 2. Crear composer.json
cat > modules/Payment/composer.json << 'EOF'
{
  "name": "cubadevops/flexi-module-payment",
  "description": "Payment processing module",
  "version": "1.0.0",
  "type": "flexi-module",
  "require": {
    "php": ">=7.4",
    "cubadevops/flexi-contracts": "@dev",
    "stripe/stripe-php": "^10.0"
  },
  "autoload": {
    "psr-4": {
      "Flexi\\Modules\\Payment\\": ""
    }
  },
  "extra": {
    "flexi": {
      "module-name": "Payment",
      "module-type": "infrastructure",
      "provides": ["payment-processing", "stripe-integration"]
    }
  }
}
EOF

# 3. Sincronizar (esto actualiza composer.json automáticamente)
php bin/console modules:sync

# ¡Listo! El módulo está instalado y disponible
```

### Eliminar un Módulo

```bash
# Opción 1: Desinstalar pero mantener código fuente
php bin/console modules:uninstall Payment
# modules/Payment/ sigue existiendo pero no está en vendor/

# Opción 2: Desinstalar y eliminar código
php bin/console modules:uninstall Payment
rm -rf modules/Payment/
```

### Clonar el Proyecto

```bash
# 1. Clonar repositorio
git clone https://github.com/cubadevops/flexi.git
cd flexi

# 2. Instalar dependencias
composer install
# ↑ Esto ejecuta automáticamente modules:sync

# ¡Listo! Todos los módulos están instalados con symlinks
```

### Agregar Dependencias a un Módulo

```bash
# 1. Editar modules/Auth/composer.json
# Agregar: "monolog/monolog": "^3.0"

# 2. Actualizar
composer update cubadevops/flexi-module-auth

# Composer instala automáticamente la nueva dependencia
```

## Migración de Modo Desarrollo a Producción

### Preparar Módulos para Producción

Cuando tus módulos están listos para producción, puedes publicarlos en repositorios Git separados:

#### 1. Crear Repositorio Git por Módulo

```bash
# Para cada módulo:
cd modules/Auth
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/cubadevops/flexi-module-auth
git push -u origin main
git tag v1.0.0
git push --tags
```

#### 2. Actualizar composer.json Principal

Cambia de `path` a `vcs`:

**Antes (Desarrollo)**:
```json
{
  "repositories": [
    {
      "type": "path",
      "url": "./modules/Auth",
      "options": { "symlink": true }
    }
  ],
  "require": {
    "cubadevops/flexi-module-auth": "@dev"
  }
}
```

**Después (Producción)**:
```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/cubadevops/flexi-module-auth"
    }
  ],
  "require": {
    "cubadevops/flexi-module-auth": "^1.0"
  }
}
```

#### 3. Reinstalar desde Git

```bash
rm -rf vendor/
rm composer.lock
composer install
```

Ahora Composer descarga los módulos desde Git en lugar de usar symlinks.

### Usar Ambos Modos Simultáneamente

Puedes tener algunos módulos en modo desarrollo y otros en producción:

```json
{
  "repositories": [
    // Módulo en desarrollo (local)
    {
      "type": "path",
      "url": "./modules/Payment",
      "options": { "symlink": true }
    },
    // Módulos en producción (Git)
    {
      "type": "vcs",
      "url": "https://github.com/cubadevops/flexi-module-auth"
    },
    {
      "type": "vcs",
      "url": "https://github.com/cubadevops/flexi-module-cache"
    }
  ],
  "require": {
    "cubadevops/flexi-module-payment": "@dev",  // Local
    "cubadevops/flexi-module-auth": "^1.0",     // Git
    "cubadevops/flexi-module-cache": "^1.0"     // Git
  }
}
```

## Ventajas del Sistema

### ✅ Sin Edición Manual

```bash
# Antes (manual):
nano composer.json  # Editar repository
nano composer.json  # Editar require
composer update

# Ahora (automático):
php bin/console modules:sync
# o simplemente:
composer install  # Se sincroniza automáticamente
```

### ✅ Autodescubrimiento

El sistema detecta automáticamente:
- Nuevos módulos en `modules/`
- Módulos eliminados
- Cambios en `composer.json` de módulos

### ✅ Desarrollo Eficiente

Los symlinks permiten:
- Cambios inmediatos sin reinstalar
- Editar directamente en `modules/`
- Ver cambios reflejados en `vendor/`

### ✅ Transición Fácil a Producción

Cambiar de desarrollo a producción es simple:
- Publicar módulo en Git
- Cambiar `type: path` por `type: vcs`
- Cambiar versión de `@dev` a `^1.0`

### ✅ Gestión de Dependencias

Cada módulo define sus propias dependencias:
- Composer resuelve conflictos automáticamente
- Dependencias se instalan en `vendor/` compartido
- Versiones compatibles garantizadas

## Limitaciones y Consideraciones

### ⚠️ Symlinks en Windows

Los symlinks pueden requerir permisos especiales en Windows.

**Solución**:
```bash
# Ejecutar terminal como Administrador
# O usar modo Developer de Windows 10+
```

### ⚠️ Dependencias Duplicadas

Si dos módulos requieren versiones incompatibles de una librería:

```bash
# Ver conflictos
composer why-not stripe/stripe-php 9.0

# Actualizar módulo problemático
nano modules/Payment/composer.json
# Cambiar versión compatible
composer update
```

### ⚠️ Performance en composer install

Con muchos módulos, `modules:sync` puede ser lento.

**Solución**:
```bash
# Desactivar sync temporal
composer install --no-scripts

# Sync manual cuando necesites
composer modules:sync
```

## Comparación: Desarrollo vs Producción

| Característica | Modo Desarrollo | Modo Producción |
|----------------|----------------|-----------------|
| Repository type | `path` | `vcs` |
| Symlinks | ✅ Sí | ❌ No |
| Cambios inmediatos | ✅ Sí | ❌ No |
| Requiere Git commits | ❌ No | ✅ Sí |
| Versionado estable | ❌ No | ✅ Sí |
| Para desarrollo | ✅ Ideal | ❌ No recomendado |
| Para producción | ❌ No recomendado | ✅ Ideal |

## Mejores Prácticas

### 1. Usa modules:sync Regularmente

```bash
# Después de git pull
git pull
composer modules:sync

# Después de crear un módulo
mkdir modules/NewModule
# ... crear archivos ...
composer modules:sync
```

### 2. Valida Módulos Frecuentemente

```bash
composer modules:validate
```

### 3. Documenta Dependencias

En cada módulo, documenta en el README:
- Qué dependencias necesita
- Por qué las necesita
- Versiones específicas requeridas

### 4. Versiona con Cuidado

Para módulos en Git:
- Usa semantic versioning
- Documenta breaking changes
- Mantén CHANGELOG.md

### 5. CI/CD

En tu pipeline:

```yaml
# .github/workflows/test.yml
- name: Install dependencies
  run: composer install  # Sincroniza automáticamente

- name: Validate modules
  run: composer modules:validate

- name: Run tests
  run: composer test
```

## Preguntas Frecuentes

### ¿Puedo seguir usando symlinks en producción?

Técnicamente sí, pero no se recomienda. Es mejor usar repositorios Git para producción.

### ¿Qué pasa si elimino un módulo físicamente?

```bash
rm -rf modules/Auth
composer modules:sync  # Detecta la eliminación y actualiza composer.json
```

### ¿Puedo tener módulos privados?

Sí, usa repositorios Git privados:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:mycompany/flexi-module-private.git"
    }
  ]
}
```

### ¿Cómo actualizo todos los módulos?

```bash
composer update "cubadevops/flexi-module-*"
```

### ¿Puedo usar Packagist?

Sí, publica tus módulos en Packagist y ya no necesitas definir repositories:

```bash
# Sin repositories necesarios
composer require cubadevops/flexi-module-auth
```

## Conclusión

Este sistema proporciona:

✅ **Gestión automática**: No editar `composer.json` manualmente
✅ **Autodescubrimiento**: Módulos detectados automáticamente
✅ **Symlinks transparentes**: Desarrollo eficiente
✅ **Fácil migración**: De desarrollo a producción
✅ **CLI poderoso**: Comandos simples para todo
✅ **Composer estándar**: Sin herramientas custom

El flujo es simple:

```bash
# Desarrollo
mkdir modules/MyModule
# ... crear archivos ...
composer modules:sync  # ¡Listo!

# Producción
git push  # Publicar módulo
# Cambiar a type: vcs en composer.json
composer update
```

**Próximo paso**: Ejecuta `composer modules:sync` para activar el sistema.
