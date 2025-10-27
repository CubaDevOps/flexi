# Auth Module

El módulo Auth encapsula toda la lógica de autenticación y autorización del framework.

## Componentes

### AuthCheckMiddleware
Middleware que valida si un usuario está autenticado mediante sesión.

**Ubicación:** `Infrastructure/Middlewares/AuthCheckMiddleware.php`

**Responsabilidades:**
- Verificar si existe el flag 'auth' en sesión
- Retornar 401 Unauthorized si no existe
- Continuar la cadena si está autenticado

**Inyecciones:**
- `SessionStorageInterface` - Acceso a sesión del usuario
- `ResponseFactoryInterface` - Factory para crear respuestas

**Uso en rutas:**
```json
{
  "middlewares": [
    "CubaDevOps\\Flexi\\Modules\\Auth\\Infrastructure\\Middlewares\\AuthCheckMiddleware"
  ]
}
```

### JWTAuthMiddleware
Middleware que valida JWT tokens en el header Authorization.

**Ubicación:** `Infrastructure/Middlewares/JWTAuthMiddleware.php`

**Responsabilidades:**
- Extraer Bearer token del header Authorization
- Decodificar y validar JWT
- Adjuntar payload al request como atributo
- Retornar 401 Unauthorized si token es inválido

**Inyecciones:**
- `SecretProviderInterface` - Provee la clave secreta para validar JWT
- `ResponseFactoryInterface` - Factory para crear respuestas

**Característica clave:** Desacoplada de core Configuration mediante `SecretProviderInterface`

**Uso en rutas:**
```json
{
  "middlewares": [
    "CubaDevOps\\Flexi\\Modules\\Auth\\Infrastructure\\Middlewares\\JWTAuthMiddleware"
  ]
}
```

### ConfigurationSecretProvider
Adaptador que implementa `SecretProviderInterface` usando core `Configuration`.

**Ubicación:** `Infrastructure/Adapters/ConfigurationSecretProvider.php`

**Responsabilidades:**
- Leer `webhook_secret` de Configuration
- Proporcionar secreto a través de interfaz genérica

**Por qué existe:** Permite que JWTAuthMiddleware sea genérica sin conocer Configuration.

---

## Configuración

Las definiciones de servicios están en `Config/services.json`:
- `CubaDevOps\Flexi\Contracts\Interfaces\SecretProviderInterface` - Interfaz de secreto
- `auth_check_middleware` - Middleware de sesión
- `jwt_auth_middleware` - Middleware de JWT

El DI container carga esta configuración automáticamente vía glob pattern.

---

## Interfaces en Contracts

Las interfaces permanecen en `contracts/src/Interfaces/`:
- `SecretProviderInterface` - Contrato para provisión de secretos

Esto permite que otros módulos implementen su propio `SecretProviderInterface` sin conocer esta implementación.

---

## Tests

Los tests del módulo están en `tests/Infrastructure/Middlewares/`:
- `JWTAuthMiddlewareTest.php` - Validación de JWT, headers, tokens inválidos

Para ejecutar tests del módulo:
```bash
phpunit modules/Auth/tests/
```

---

## Extensibilidad

### Agregar nuevo scheme de autenticación

1. Crear nuevo middleware en `Infrastructure/Middlewares/NewAuthMiddleware.php`
2. Implementar `Psr\Http\Server\MiddlewareInterface`
3. Registrar en `Config/services.json`
4. Usar en rutas

### Cambiar fuente de secretos

Crear nuevo `SecretProviderInterface` implementation:

```php
class MySecretProvider implements SecretProviderInterface
{
    public function getSecret(): string
    {
        // Tu lógica para obtener secreto
    }
}
```

Registrar en `Config/services.json`:
```json
{
  "name": "CubaDevOps\\Flexi\\Contracts\\Interfaces\\SecretProviderInterface",
  "class": {
    "name": "YourNamespace\\MySecretProvider",
    "arguments": []
  }
}
```

---

## Notas de Arquitectura

- **Separación de Concerns:** Auth module maneja solo autenticación
- **Inyección de Dependencias:** Todas las dependencias se inyectan vía constructor
- **Contratos Genéricos:** SecretProviderInterface permite diferentes implementaciones
- **Modularidad:** Auth module es auto-contenido e independiente
- **Core Clean:** El core no contiene lógica de autenticación
