# Cache Module

Este módulo proporciona la implementación del sistema de caché para Flexi Framework.

## Características

- **Múltiples drivers**: Soporte para diferentes implementaciones de caché
  - `FileCache`: Almacenamiento en sistema de archivos
  - `InMemoryCache`: Almacenamiento en memoria (array)

- **PSR-16 compatible**: Implementa la interfaz PSR Simple Cache
- **Factory pattern**: Gestión centralizada de instancias de caché
- **Configuración flexible**: Selección de driver a través de configuración

## Uso

El módulo se registra automáticamente a través de la configuración de servicios y proporciona:

- `CubaDevOps\Flexi\Contracts\Interfaces\CacheInterface`: Interfaz principal
- `cache`: Alias de servicio

### Configuración

En tu archivo de configuración `.env` o configuración, define:

```php
'cache_driver' => 'file', // 'file', 'array', 'memory'
'cache_dir' => '/path/to/cache', // Solo para driver 'file'
```

### Ejemplo de uso

```php
// Obtener instancia desde el contenedor
$cache = $container->get('cache');

// Operaciones básicas
$cache->set('key', 'value', 3600);
$value = $cache->get('key', 'default');
$cache->delete('key');
$cache->clear();
```

## Arquitectura

- **Domain**: Excepciones específicas del dominio de caché
- **Infrastructure**: Implementaciones concretas y factory
- **Tests**: Suite de pruebas unitarias
