# Module Environment Variables Management

Esta funcionalidad permite que cada módulo tenga su propio archivo `.env` interno con variables de configuración que se integran automáticamente con el archivo `.env` principal de la aplicación durante la activación/desactivación del módulo.

## Características

### 1. **Gestión Automática de Variables de Entorno**
- Los módulos pueden incluir un archivo `.env` en su directorio raíz
- Al activar un módulo, sus variables se agregan al `.env` principal automáticamente
- Al desactivar un módulo, sus variables se remueven del `.env` principal
- Las variables se organizan en bloques comentados para identificar fácilmente su origen

### 2. **Preservación de Modificaciones del Usuario**
- Los desarrolladores pueden modificar los valores en el `.env` principal sin afectar el módulo
- Las modificaciones del usuario se preservan durante las actualizaciones del módulo
- Solo se agregan nuevas variables, no se sobrescriben las existentes

### 3. **Formato de Integración**
Las variables se agregan al `.env` principal con el siguiente formato:

```env
# === MODULE EXAMPLE-AUTH ENVIRONMENT VARIABLES ===
# Environment variables for module: example-auth
# You can modify these values as needed for your environment
AUTH_JWT_SECRET="your-super-secret-jwt-key-change-this-in-production"
AUTH_JWT_EXPIRATION_TIME=3600
AUTH_JWT_ALGORITHM="HS256"
# ... más variables
# === END MODULE EXAMPLE-AUTH ENVIRONMENT VARIABLES ===
```

## Uso

### Activar un Módulo con Variables de Entorno
```bash
php bin/console modules:activate example-auth
```

**Respuesta de ejemplo:**
```json
{
    "success": true,
    "message": "Module 'example-auth' has been activated successfully",
    "module": "example-auth",
    "status": "activated",
    "details": {
        "type": "local",
        "path": "/path/to/modules/example-auth",
        "package": "cubadevops/flexi-module-example-auth",
        "version": "1.0.0",
        "last_modified": "2025-11-17 10:30:45",
        "modified_by": "user",
        "has_env_file": true,
        "env_vars_integrated": true
    }
}
```

### Desactivar un Módulo con Variables de Entorno
```bash
php bin/console modules:deactivate example-auth
```

**Respuesta de ejemplo:**
```json
{
    "success": true,
    "message": "Module 'example-auth' has been deactivated successfully",
    "module": "example-auth",
    "status": "deactivated",
    "details": {
        "type": "local",
        "path": "/path/to/modules/example-auth",
        "package": "cubadevops/flexi-module-example-auth",
        "version": "1.0.0",
        "last_modified": "2025-11-17 10:35:22",
        "modified_by": "user",
        "had_env_file": true,
        "env_vars_removed": true
    }
}
```

## Estructura del Módulo

Para que un módulo soporte variables de entorno, debe tener la siguiente estructura:

```
modules/your-module/
├── composer.json          # Metadatos del módulo
├── .env                  # Variables de entorno del módulo (nuevo)
├── src/                  # Código fuente del módulo
└── config/               # Archivos de configuración
```

### Ejemplo de `.env` del Módulo

```env
# Authentication Configuration Variables
# These variables control the behavior of the Example Auth module

# JWT Configuration
AUTH_JWT_SECRET="your-super-secret-jwt-key-change-this-in-production"
AUTH_JWT_EXPIRATION_TIME=3600
AUTH_JWT_ALGORITHM="HS256"

# Session Configuration
AUTH_SESSION_NAME="flexi_auth_session"
AUTH_SESSION_COOKIE_LIFETIME=7200
AUTH_SESSION_COOKIE_SECURE=false

# Password Security
AUTH_PASSWORD_MIN_LENGTH=8
AUTH_PASSWORD_REQUIRE_UPPERCASE=true
AUTH_PASSWORD_REQUIRE_SPECIAL_CHARS=true
```

## Buenas Prácticas

### 1. **Nomenclatura de Variables**
- Usar un prefijo único para el módulo (ej: `AUTH_`, `CACHE_`, `EMAIL_`)
- Seguir convenciones de nomenclatura consistentes
- Usar nombres descriptivos y claros

### 2. **Valores por Defecto**
- Proporcionar valores por defecto sensatos para desarrollo
- Incluir comentarios explicativos para cada variable
- Marcar claramente qué variables necesitan ser cambiadas en producción

### 3. **Seguridad**
- No incluir valores secretos reales en el `.env` del módulo
- Usar valores placeholder para variables sensibles
- Documentar qué variables requieren configuración específica del entorno

### 4. **Documentación**
- Comentar cada variable explicando su propósito
- Agrupar variables relacionadas
- Proporcionar ejemplos de valores válidos

## Gestión de Errores

### Advertencias en la Respuesta
Si hay problemas con la gestión de variables de entorno, se incluyen en la respuesta:

```json
{
    "success": true,
    "message": "Module 'example-auth' has been activated successfully",
    "env_warnings": [
        "Failed to integrate module environment variables with main .env file"
    ],
    "module": "example-auth"
}
```

### Casos de Error Común
1. **Permisos de archivo**: El archivo `.env` principal no es escribible
2. **Formato inválido**: El archivo `.env` del módulo tiene formato incorrecto
3. **Conflictos de variables**: Variables ya definidas por otros módulos

## API Interna

### ModuleEnvironmentManagerInterface

La funcionalidad se implementa a través del `ModuleEnvironmentManagerInterface` que proporciona:

- `readModuleEnvironment()`: Lee variables del archivo `.env` del módulo
- `addModuleEnvironment()`: Agrega variables al `.env` principal
- `removeModuleEnvironment()`: Remueve variables del `.env` principal
- `hasModuleEnvironment()`: Verifica si el módulo tiene variables integradas
- `updateModuleEnvironment()`: Actualiza variables preservando modificaciones del usuario

### Integración con Casos de Uso

Los casos de uso `ActivateModule` y `DeactivateModule` han sido extendidos para:

1. Detectar automáticamente si el módulo tiene un archivo `.env`
2. Leer las variables del archivo `.env` del módulo
3. Integrar/remover las variables del archivo `.env` principal
4. Reportar el estado de la integración en la respuesta
5. Manejar errores y proporcionar advertencias apropiadas

## Ejemplo Completo

### Antes de Activar el Módulo
`.env` principal:
```env
#Application
debug=true
dispatch_mode=1

#Logger
log_enabled=true
log_file_path="./var/logs/app.log"
```

### Después de Activar el Módulo
`.env` principal:
```env
#Application
debug=true
dispatch_mode=1

#Logger
log_enabled=true
log_file_path="./var/logs/app.log"

# === MODULE EXAMPLE-AUTH ENVIRONMENT VARIABLES ===
# Environment variables for module: example-auth
# You can modify these values as needed for your environment
AUTH_JWT_SECRET="your-super-secret-jwt-key-change-this-in-production"
AUTH_JWT_EXPIRATION_TIME=3600
AUTH_JWT_ALGORITHM="HS256"
AUTH_SESSION_NAME="flexi_auth_session"
AUTH_SESSION_COOKIE_LIFETIME=7200
AUTH_SESSION_COOKIE_SECURE=false
# === END MODULE EXAMPLE-AUTH ENVIRONMENT VARIABLES ===
```

### Después de Desactivar el Módulo
El `.env` principal vuelve a su estado original:
```env
#Application
debug=true
dispatch_mode=1

#Logger
log_enabled=true
log_file_path="./var/logs/app.log"
```

Esta funcionalidad facilita significativamente la gestión de configuraciones de módulos, permitiendo que cada módulo sea autocontenido mientras mantiene la flexibilidad para que los desarrolladores ajusten la configuración según sus necesidades específicas.