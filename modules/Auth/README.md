# Auth Module - Authentication Implementation

## Overview

The Auth module provides a **secure, extensible authentication system** for the Flexi framework. It implements HTTP Basic Authentication with support for multiple credential storage backends and advanced security features.

### Key Features

- ✅ **HTTP Basic Authentication** via Authorization header
- ✅ **Secure Password Verification** (prevents timing attacks)
- ✅ **Agnostic Storage** (DB, File, LDAP, Custom)
- ✅ **Session Management** (secure session creation)
- ✅ **Event Dispatching** (audit trail)
- ✅ **Logging & Monitoring** (all auth attempts)
- ✅ **IP Tracking** (including proxy support)
- ✅ **REST Compliant** (proper HTTP status codes)

## Components Overview

### BasicAuthMiddleware ⭐ NEW
**Secure HTTP Basic Authentication Middleware**

Handles authentication via `Authorization: Basic base64(username:password)` header.

**Features:**
- Extracts and validates Basic auth credentials
- Integrates with pluggable credential storage (CredentialsRepositoryInterface)
- Uses secure password verification (timing-attack safe)
- Creates authenticated sessions
- Dispatches events for audit trail
- Logs all authentication attempts (success + failure)
- Tracks client IP (with proxy support: X-Forwarded-For, X-Real-IP)

**Inyecciones:**
- `CredentialsRepositoryInterface` - Get user credentials
- `CredentialsVerifierInterface` - Verify passwords securely
- `SessionStorageInterface` - Create authenticated session
- `ResponseFactoryInterface` - Create responses
- `EventBusInterface` - Dispatch events
- `LoggerInterface` - Audit logging

### LoginController ⭐ NEW
**REST Login Endpoint Handler**

Handles POST requests to `/auth/login` endpoint. Only called if BasicAuthMiddleware successfully authenticated the request.

**Inyecciones:**
- `ResponseFactoryInterface` - Create responses
- `SessionStorageInterface` - Access session data
- `EventBusInterface` - Dispatch events

**Response (200 OK):**
```json
{
  "success": true,
  "message": "User authenticated successfully",
  "user_id": 1,
  "username": "admin",
  "authenticated_at": "2025-10-27T10:30:00+00:00",
  "user_data": {
    "full_name": "Administrator",
    "email": "admin@example.com",
    "roles": ["admin"]
  }
}
```

### AuthCheckMiddleware
**Session Validation Middleware**

Validates that user session exists. Used for subsequent requests after initial login.

### JWTAuthMiddleware
**JWT Token Authentication Middleware**

Validates JWT tokens in Authorization header. Alternative to Basic Auth for stateless requests.

---

## Architecture

### Component Structure

```
Domain/
├── Interfaces/
│   ├── CredentialsRepositoryInterface     # Abstraction for credential storage
│   └── CredentialsVerifierInterface       # Abstraction for password verification
└── ValueObjects/
    ├── Credentials                        # Username + Password value object
    └── AuthenticationResult               # Auth result with user data

Infrastructure/
├── Middlewares/
│   ├── BasicAuthMiddleware               # Main HTTP Basic auth middleware ⭐ NEW
│   ├── AuthCheckMiddleware               # Session validation
│   └── JWTAuthMiddleware                 # JWT-based auth
├── Adapters/
│   ├── ConfigurationCredentialsRepository # File-based credential storage ⭐ NEW
│   ├── DefaultCredentialsVerifier        # Secure password verification ⭐ NEW
│   └── ConfigurationSecretProvider       # Secret management
└── Controllers/
    └── LoginController                    # Login endpoint handler ⭐ NEW

Config/
├── routes.json                            # Route definitions
├── services.json                          # Service bindings
└── credentials.json                       # Credential storage (example) ⭐ NEW
```

## Authentication Flow

### HTTP Basic Auth Sequence

```
Client                 BasicAuthMiddleware         LoginController
  │                           │                           │
  ├─ POST /auth/login ────────>│                           │
  │  Authorization: Basic...   │                           │
  │                            ├─ Extract credentials      │
  │                            ├─ Query CredRepo           │
  │                            ├─ Verify password          │
  │                            ├─ Create session           │
  │                            ├─ Dispatch event           │
  │                            ├─ Pass to next handler ────>│
  │                            │                           ├─ Return 200 OK
  │                            │                           ├─ Dispatch event
  │<───── 200 OK with user ────┼───────────────────────────┤
  │       data (JSON)          │                           │
```

### Step-by-Step Flow

1. **Client sends request** with `Authorization: Basic base64(username:password)` header
2. **BasicAuthMiddleware** intercepts request:
   - Extracts credentials from Authorization header (Base64 decoded)
   - Searches for user in CredentialsRepository
   - Verifies password using CredentialsVerifier (timing-attack safe)
   - Creates authenticated session in SessionStorageInterface
   - Dispatches `user.authenticated` event for audit
   - Logs successful authentication with IP and timestamp
3. **LoginController** handles successful authentication:
   - Returns 200 OK with user data in JSON
   - Dispatches `user.login.success` event
4. **Future requests** validate session via AuthCheckMiddleware

---

## Usage

### 1. Create Password Hash

Generate a bcrypt hash for your password:

```php
$password = 'your_secure_password_123';
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
echo $hash;  // $2y$10$...
```

### 2. Configure Credentials

Edit `modules/Auth/Config/credentials.json`:

```json
{
  "auth": {
    "credentials": {
      "users": [
        {
          "username": "admin",
          "password_hash": "$2y$10$abcd1234efgh5678ijkl9012mnopqrstuvwxyz",
          "user_id": 1,
          "full_name": "Administrator",
          "email": "admin@example.com",
          "roles": ["admin"]
        },
        {
          "username": "user",
          "password_hash": "$2y$10$1234abcdefgh5678ijklmnopqrstuvwxyzABCD",
          "user_id": 2,
          "full_name": "Regular User",
          "email": "user@example.com",
          "roles": ["user"]
        }
      ]
    }
  }
}
```

### 3. Authenticate User

**Using cURL:**

```bash
# Method 1: Using --user flag
curl -X POST http://localhost:8000/auth/login \
  --user admin:password123

# Method 2: Using Authorization header
curl -X POST http://localhost:8000/auth/login \
  -H "Authorization: Basic $(echo -n 'admin:password123' | base64)"

# Response (200 OK):
{
  "success": true,
  "message": "User authenticated successfully",
  "user_id": 1,
  "username": "admin",
  "authenticated_at": "2025-10-27T10:30:00+00:00",
  "user_data": {
    "full_name": "Administrator",
    "email": "admin@example.com",
    "roles": ["admin"]
  }
}
```

**Using PHP:**

```php
$username = 'admin';
$password = 'password123';
$url = 'http://localhost:8000/auth/login';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

$response = curl_exec($ch);
$data = json_decode($response, true);

echo "User ID: " . $data['user_id'];
echo "Username: " . $data['username'];
```

### 4. Create Custom Credentials Repository

For production, implement your own repository (Database, LDAP, etc.):

```php
namespace MyApp\Auth;

use CubaDevOps\Flexi\Modules\Auth\Domain\Interfaces\CredentialsRepositoryInterface;

class DatabaseCredentialsRepository implements CredentialsRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }
}
```

**Register in `modules/Auth/Config/services.json`:**

```json
{
  "name": "CubaDevOps\\Flexi\\Modules\\Auth\\Domain\\Interfaces\\CredentialsRepositoryInterface",
  "class": {
    "name": "MyApp\\Auth\\DatabaseCredentialsRepository",
    "arguments": ["@PDO"]
  }
}
```

### 5. Create Custom Password Verifier

Implement for different hash algorithms:

```php
namespace MyApp\Auth;

use CubaDevOps\Flexi\Modules\Auth\Domain\Interfaces\CredentialsVerifierInterface;
use CubaDevOps\Flexi\Modules\Auth\Domain\ValueObjects\Credentials;

class Argon2CredentialsVerifier implements CredentialsVerifierInterface
{
    public function verify(Credentials $credentials, string $password_hash): bool
    {
        // Verify with Argon2, prevent timing attacks
        if (!password_verify($credentials->getPassword(), $password_hash)) {
            return false;
        }

        $computed = password_hash($credentials->getPassword(), PASSWORD_ARGON2ID);
        return hash_equals($password_hash, $computed);
    }
}
```

---

## Security Features

### ✅ Implemented

1. **Timing Attack Prevention**
   - `hash_equals()` for constant-time comparison
   - `password_verify()` with secure implementation
   - No password timing leaks

2. **Secure Password Storage**
   - Never stores plain text passwords
   - Bcrypt/Argon2 hashing support
   - Configurable hash algorithms

3. **Comprehensive Logging**
   - All auth attempts logged (success + failure)
   - Includes username, reason, and IP
   - Timestamps for all events
   - Non-sensitive data only in logs

4. **Event Dispatching**
   - `user.authenticated` - successful login
   - `user.authentication.failed` - failed attempt
   - `user.login.success` - successful response sent
   - Can trigger additional security measures

5. **Session Security**
   - Secure session created after auth
   - Session data stored via SessionStorageInterface
   - All user data available in session

6. **IP Address Tracking**
   - Detects proxied requests
   - Checks `X-Forwarded-For` header
   - Checks `X-Real-IP` header
   - Falls back to `REMOTE_ADDR`

### ⚠️ Best Practices

1. **HTTPS Required**
   - Basic Auth sends base64-encoded credentials
   - HTTPS prevents interception
   - Never use HTTP in production

2. **Credential Storage**
   - Use database in production (not JSON files)
   - Never store credentials in git
   - Use secure vaults/KMS for secrets

3. **Rate Limiting**
   - Implement at middleware level
   - Block after N failed attempts
   - Track attempts by IP/username

4. **Password Policy**
   - Enforce strong passwords (12+ chars)
   - Require complexity (uppercase, numbers, symbols)
   - Regular password rotations

5. **Session Management**
   - Implement token expiration
   - Rotate sessions periodically
   - Clear on logout

---

## Events

### user.authenticated

Fired when user successfully authenticates (before response):

```php
Event(
    'user.authenticated',
    'CubaDevOps\Flexi\Modules\Auth\Infrastructure\Middlewares\BasicAuthMiddleware',
    [
        'user_id' => 1,
        'username' => 'admin',
        'ip' => '192.168.1.100',
        'timestamp' => '2025-10-27T10:30:00+00:00',
    ]
)
```

### user.authentication.failed

Fired when authentication fails:

```php
Event(
    'user.authentication.failed',
    'CubaDevOps\Flexi\Modules\Auth\Infrastructure\Middlewares\BasicAuthMiddleware',
    [
        'reason' => 'invalid_password',  // 'user_not_found', 'missing_credentials', etc.
        'ip' => '192.168.1.100',
        'timestamp' => '2025-10-27T10:30:00+00:00',
    ]
)
```

### user.login.success

Fired after successful login (before returning response):

```php
Event(
    'user.login.success',
    'CubaDevOps\Flexi\Modules\Auth\Infrastructure\Controllers\LoginController',
    [
        'user_id' => 1,
        'username' => 'admin',
        'ip' => '192.168.1.100',
        'timestamp' => '2025-10-27T10:30:00+00:00',
    ]
)
```

---

## API Reference

### POST /auth/login

Authenticate user with HTTP Basic credentials.

**Request:**
```http
POST /auth/login HTTP/1.1
Host: localhost:8000
Authorization: Basic YWRtaW46cGFzc3dvcmQxMjM=
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "User authenticated successfully",
  "user_id": 1,
  "username": "admin",
  "authenticated_at": "2025-10-27T10:30:00+00:00",
  "user_data": {
    "full_name": "Administrator",
    "email": "admin@example.com",
    "roles": ["admin"]
  }
}
```

**Error Response (401 Unauthorized):**
```
HTTP/1.1 401 Unauthorized
Content-Type: text/plain

Unauthorized
```

---

## Testing

### Run Auth Module Tests

```bash
# All auth tests
./vendor/bin/phpunit modules/Auth/tests/

# Specific test
./vendor/bin/phpunit modules/Auth/tests/Infrastructure/Middlewares/BasicAuthMiddlewareTest.php
```

### Example Test

```php
public function testSuccessfulAuthentication()
{
    $credentials = base64_encode('admin:password123');
    $request = $this->createMock(ServerRequestInterface::class);
    $request->method('getHeaderLine')
        ->with('Authorization')
        ->willReturn("Basic $credentials");

    $middleware = new BasicAuthMiddleware(
        $this->repository,
        $this->verifier,
        $this->session,
        $this->responseFactory,
        $this->eventBus,
        $this->logger
    );

    $response = $middleware->process($request, $this->handler);

    $this->assertEquals(200, $response->getStatusCode());
}
```

---

## Interfaces & Contracts

### CredentialsRepositoryInterface

Abstraction for credential storage backend.

```php
public function findByUsername(string $username): ?array;
```

**Returns:**
```php
[
    'username' => 'admin',
    'password_hash' => '$2y$10$...',
    'user_id' => 1,
    'full_name' => 'Administrator',  // Optional
    'email' => 'admin@example.com',   // Optional
    'roles' => ['admin'],             // Optional
    // ... any other user data
]
```

### CredentialsVerifierInterface

Abstraction for password verification.

```php
public function verify(Credentials $credentials, string $password_hash): bool;
```

### Credentials (ValueObject)

Immutable representation of username + password.

```php
$creds = new Credentials('admin', 'password123');
$creds->getUsername();  // 'admin'
$creds->getPassword();  // 'password123'
```

### AuthenticationResult (ValueObject)

Immutable representation of successful authentication.

```php
$result = new AuthenticationResult(
    user_id: 1,
    username: 'admin',
    user_data: ['full_name' => 'Administrator']
);

$result->getUserId();         // 1
$result->getUsername();       // 'admin'
$result->getUserData();       // ['full_name' => '...']
$result->toSessionData();     // Ready for session storage
```

---

## Migration Guide

### From Auth0/External Auth

1. Create CredentialsRepositoryInterface implementation
2. Create CredentialsVerifierInterface implementation (if needed)
3. Register in services.json
4. Update routes to use BasicAuthMiddleware

### From Manual Auth in Controllers

1. Remove auth logic from controllers
2. Use BasicAuthMiddleware instead
3. Remove manual session creation
4. Let LoginController handle responses

---

## Troubleshooting

### "401 Unauthorized" on Valid Credentials

- Verify password hash matches actual password
- Check credentials.json exists and is readable
- Ensure ConfigurationCredentialsRepository can read file
- Check logs for specific failure reason

### Events Not Firing

- Verify EventBusInterface is registered
- Check listeners.json includes auth events
- Verify event bus is properly configured

### IP Address Wrong

- Check proxy headers: X-Forwarded-For, X-Real-IP
- Verify middleware is executed before auth
- Check REMOTE_ADDR in $_SERVER

---

## Architecture Notes

The Auth module demonstrates key framework principles:

1. **Domain Interfaces** - CredentialsRepositoryInterface, CredentialsVerifierInterface
2. **Value Objects** - Credentials, AuthenticationResult
3. **Infrastructure Adapters** - DefaultCredentialsVerifier, ConfigurationCredentialsRepository
4. **Middleware Pattern** - BasicAuthMiddleware for request interception
5. **Event Dispatching** - Integration with EventBus
6. **Dependency Injection** - All dependencies injected via constructor
7. **PSR Standards** - HTTP Messages (PSR-7), Logging (PSR-3)

---

Última actualización: 27 de octubre de 2025

## Architecture

### Component Structure

```
Domain/
├── Interfaces/
│   ├── CredentialsRepositoryInterface     # Abstraction for credential storage
│   └── CredentialsVerifierInterface       # Abstraction for password verification
└── ValueObjects/
    ├── Credentials                        # Username + Password value object
    └── AuthenticationResult               # Auth result with user data

Infrastructure/
├── Middlewares/
│   ├── BasicAuthMiddleware               # Main authentication middleware
│   ├── AuthCheckMiddleware               # Session validation
│   └── JWTAuthMiddleware                 # JWT-based auth
├── Adapters/
│   ├── ConfigurationCredentialsRepository # File-based credential storage
│   ├── DefaultCredentialsVerifier        # Secure password verification
│   └── ConfigurationSecretProvider       # Secret management
└── Controllers/
    └── LoginController                    # Login endpoint handler

Config/
├── routes.json                            # Route definitions
├── services.json                          # Service bindings
└── credentials.json                       # Credential storage (example)
```

## Authentication Flow

### Sequence Diagram

```
POST /auth/login with Authorization: Basic base64(username:password)
              ↓
   BasicAuthMiddleware
        ↓        ↓        ↓
    Extract   Verify   Create
    Creds     Password Session
              ↓        ↓
           Success  Event
              ↓
      LoginController
              ↓
         Return 200 OK
         JSON Response
```

### Step-by-Step Flow

1. **Client sends request** with `Authorization: Basic base64(username:password)` header
2. **BasicAuthMiddleware** intercepts request
   - Extracts credentials from Authorization header
   - Searches for user in CredentialsRepository
   - Verifies password using CredentialsVerifier (with timing attack protection)
   - Creates authenticated session
   - Dispatches events for audit
3. **LoginController** handles successful authentication
   - Returns 200 OK with user data
   - Dispatches login success event
4. **Future requests** can validate session via AuthCheckMiddleware

## Usage Examples

### 1. Configure Credentials (Development)

Edit `modules/Auth/Config/credentials.json`:

```json
{
  "auth": {
    "credentials": {
      "users": [
        {
          "username": "admin",
          "password_hash": "$2y$10$...",
          "user_id": 1,
          "full_name": "Administrator",
          "email": "admin@example.com",
          "roles": ["admin"]
        }
      ]
    }
  }
}
```

**Generate password hash:**

```php
$hash = password_hash('password123', PASSWORD_BCRYPT, ['cost' => 10]);
// Result: $2y$10$...
```

### 2. Authenticate User

**Using cURL:**

```bash
# Basic auth with username:password
curl -X POST http://localhost:8000/auth/login \
  -H "Authorization: Basic $(echo -n 'admin:password123' | base64)"

# Response (200 OK):
{
  "success": true,
  "message": "User authenticated successfully",
  "user_id": 1,
  "username": "admin",
  "authenticated_at": "2025-10-27T10:30:00+00:00",
  "user_data": {
    "full_name": "Administrator",
    "email": "admin@example.com",
    "roles": ["admin"]
  }
}
```

**Using PHP:**

```php
$username = 'admin';
$password = 'password123';
$encoded = base64_encode("$username:$password");

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Authorization: Basic $encoded\r\n",
    ]
]);

$response = file_get_contents('http://localhost:8000/auth/login', false, $context);
```

### 3. Create Custom Credentials Repository

Implement `CredentialsRepositoryInterface` for your storage backend:

```php
namespace MyApp\Auth;

use CubaDevOps\Flexi\Modules\Auth\Domain\Interfaces\CredentialsRepositoryInterface;

class DatabaseCredentialsRepository implements CredentialsRepositoryInterface
{
    private PDO $pdo;

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
```

**Register in services.json:**

```json
{
  "name": "CubaDevOps\\Flexi\\Modules\\Auth\\Domain\\Interfaces\\CredentialsRepositoryInterface",
  "class": "MyApp\\Auth\\DatabaseCredentialsRepository"
}
```

### 4. Create Custom Password Verifier

Implement `CredentialsVerifierInterface` for different hash algorithms:

```php
namespace MyApp\Auth;

use CubaDevOps\Flexi\Modules\Auth\Domain\Interfaces\CredentialsVerifierInterface;
use CubaDevOps\Flexi\Modules\Auth\Domain\ValueObjects\Credentials;

class Argon2CredentialsVerifier implements CredentialsVerifierInterface
{
    public function verify(Credentials $credentials, string $password_hash): bool
    {
        // Use Argon2 with timing attack prevention
        if (!password_verify($credentials->getPassword(), $password_hash)) {
            return false;
        }

        $computed_hash = password_hash($credentials->getPassword(), PASSWORD_ARGON2ID);
        return hash_equals($password_hash, $computed_hash);
    }
}
```

## Security Considerations

### ✅ Implemented Security Measures

1. **Timing Attack Prevention**
   - Uses `hash_equals()` for constant-time comparison
   - `password_verify()` with timing-safe implementation

2. **Password Storage**
   - Never stores plain text passwords
   - Uses bcrypt/Argon2 hashing
   - Configurable hash algorithms

3. **Audit Logging**
   - Logs all authentication attempts
   - Tracks successful and failed attempts
   - Records IP addresses (with proxy support)
   - Timestamps for all events

4. **Event Dispatching**
   - `user.authenticated` - successful login
   - `user.authentication.failed` - failed attempt
   - Events can trigger additional security measures

5. **Session Security**
   - Secure session creation after auth
   - Session data stored via SessionStorageInterface
   - All user data in session from first login

6. **IP Address Tracking**
   - Supports `X-Forwarded-For` header (proxy)
   - Supports `X-Real-IP` header
   - Falls back to `REMOTE_ADDR`

### ⚠️ Security Best Practices

1. **HTTPS Required** - Always use HTTPS in production
   - Basic Auth sends base64-encoded credentials
   - Easily decoded if intercepted

2. **Credential Storage**
   - Configuration file approach is for development only
   - Use secure credential management (database, vault) in production
   - Never store credentials in version control

3. **Rate Limiting**
   - Implement rate limiting at middleware level
   - Block after N failed attempts
   - Use cache to track attempts by IP

4. **Password Policy**
   - Enforce strong password requirements
   - Minimum length (12+ characters)
   - Character complexity

5. **Token Refresh**
   - Implement token expiration
   - Provide refresh mechanisms
   - Rotate sessions periodically

## Events

The auth system dispatches events for monitoring and audit:

### user.authenticated

Fired when user successfully authenticates:

```php
Event(
    'user.authenticated',
    BasicAuthMiddleware::class,
    [
        'user_id' => 1,
        'username' => 'admin',
        'ip' => '192.168.1.1',
        'timestamp' => '2025-10-27T10:30:00+00:00',
    ]
)
```

### user.authentication.failed

Fired when authentication fails:

```php
Event(
    'user.authentication.failed',
    BasicAuthMiddleware::class,
    [
        'reason' => 'invalid_password',  // or 'user_not_found', 'missing_credentials'
        'ip' => '192.168.1.1',
        'timestamp' => '2025-10-27T10:30:00+00:00',
    ]
)
```

### user.login.success

Fired after successful login response:

```php
Event(
    'user.login.success',
    LoginController::class,
    [
        'user_id' => 1,
        'username' => 'admin',
        'ip' => '192.168.1.1',
        'timestamp' => '2025-10-27T10:30:00+00:00',
    ]
)
```

## Testing

Example test for BasicAuthMiddleware:

```php
public function testSuccessfulAuthentication()
{
    $credentials = 'admin:password123';
    $encoded = base64_encode($credentials);

    $request = $this->createMock(ServerRequestInterface::class);
    $request->method('getHeaderLine')
        ->with('Authorization')
        ->willReturn("Basic $encoded");

    $middleware = new BasicAuthMiddleware(...);
    $response = $middleware->process($request, $this->handler);

    $this->assertEquals(200, $response->getStatusCode());
}
```

## API Reference

### POST /auth/login

Authenticate user with credentials.

**Request:**
```
POST /auth/login
Authorization: Basic base64(username:password)
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "User authenticated successfully",
  "user_id": 1,
  "username": "admin",
  "authenticated_at": "2025-10-27T10:30:00+00:00",
  "user_data": {
    "full_name": "Administrator",
    "email": "admin@example.com",
    "roles": ["admin"]
  }
}
```

**Error Response (401 Unauthorized):**
```
401 Unauthorized
Unauthorized
```

## Interfaces & Contracts

### CredentialsRepositoryInterface

```php
public function findByUsername(string $username): ?array;
```

Returns array with: `username`, `password_hash`, `user_id`, and optional user data.

### CredentialsVerifierInterface

```php
public function verify(Credentials $credentials, string $password_hash): bool;
```

Returns true if credentials match the hash.

### Credentials (ValueObject)

```php
new Credentials(string $username, string $password)
```

Immutable value object representing a credential pair.

### AuthenticationResult (ValueObject)

```php
new AuthenticationResult(
    int|string $user_id,
    string $username,
    array $user_data = []
)
```

Immutable value object representing successful authentication with session data.

## Migration Path

### From AuthCheckMiddleware to BasicAuthMiddleware

Old approach (session-based):
```json
{
  "middlewares": ["AuthCheckMiddleware"]
}
```

New approach (basic auth):
```json
{
  "middlewares": ["BasicAuthMiddleware"]
}
```

Both can coexist - use appropriate middleware for your use case:
- **BasicAuthMiddleware**: New authentications, stateless requests
- **AuthCheckMiddleware**: Session validation for subsequent requests

## Future Enhancements

- [ ] JWT token generation after auth
- [ ] Rate limiting built-in
- [ ] OAuth2/OIDC support
- [ ] Multi-factor authentication
- [ ] API key authentication
- [ ] Session timeout management
- [ ] Remember-me functionality
- [ ] Audit trail storage

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
