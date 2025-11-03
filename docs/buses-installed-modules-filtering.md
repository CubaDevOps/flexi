# Filtrado de Módulos Instalados en Buses

## Resumen

Se ha implementado el filtrado de módulos instalados en todos los buses del framework (`EventBus`, `CommandBus`, `QueryBus`) para que solo procesen archivos de configuración de módulos que estén actualmente instalados según el `composer.json`, siguiendo el mismo patrón implementado en el `ServicesDefinitionParser`.

## Problema Identificado

Los buses (`EventBus`, `CommandBus`, `QueryBus`) procesaban todos los archivos encontrados por patrones glob sin verificar si los módulos correspondientes estaban instalados. Esto podía causar:

1. Carga de configuraciones de módulos no instalados
2. Errores al intentar instanciar clases que no existen
3. Inconsistencia con el comportamiento del contenedor de servicios

## Solución Implementada

### Patrón Aplicado

Se replicó el mismo patrón de filtrado utilizado en `ServicesDefinitionParser`:

1. **Lectura de módulos instalados**: Se lee el `composer.json` para obtener la lista de módulos instalados (paquetes que comienzan con `cubadevops/flexi-module-`)

2. **Extracción del nombre del módulo**: Se extrae el nombre del módulo de la ruta del archivo usando expresiones regulares

3. **Filtrado**: Solo se procesan archivos de módulos que estén en la lista de instalados

### Cambios por Archivo

#### EventBus

**Archivo**: `src/Infrastructure/Bus/EventBus.php`

✅ Agregada constante `COMPOSER_JSON_PATH`
✅ Agregada propiedad `$installedModules`
✅ Modificado `loadGlobListeners()` para filtrar archivos
✅ Agregados métodos:
- `filterInstalledModuleFiles()` - Filtra archivos según módulos instalados
- `isModuleFile()` - Verifica si un archivo pertenece a un módulo
- `extractModuleName()` - Extrae el nombre del módulo de la ruta
- `getInstalledModules()` - Obtiene lista de módulos instalados del composer.json

```php
public function loadGlobListeners(array $listener): void
{
    $files = $this->readGlob($listener['glob']);

    // Filter files to only include installed modules
    $files = $this->filterInstalledModuleFiles($files);

    foreach ($files as $file) {
        $this->loadHandlersFromJsonFile($file);
    }
}
```

#### CommandBus

**Archivo**: `src/Infrastructure/Bus/CommandBus.php`

✅ Agregada constante `COMPOSER_JSON_PATH`
✅ Agregada propiedad `$installedModules`
✅ Modificado `loadGlobHandlers()` para filtrar archivos
✅ Agregados los mismos 4 métodos de filtrado

```php
private function loadGlobHandlers(string $glob_path): void
{
    $handlers = $this->readGlob($glob_path);

    // Filter files to only include installed modules
    $handlers = $this->filterInstalledModuleFiles($handlers);

    foreach ($handlers as $handler) {
        $this->loadHandlersFromJsonFile($handler);
    }
}
```

#### QueryBus

**Archivo**: `src/Infrastructure/Bus/QueryBus.php`

✅ Agregada constante `COMPOSER_JSON_PATH`
✅ Agregada propiedad `$installedModules`
✅ Modificado `loadGlobFiles()` para filtrar archivos
✅ Agregados los mismos 4 métodos de filtrado

```php
private function loadGlobFiles(array $handler): void
{
    $files = $this->readGlob($handler['glob']);

    // Filter files to only include installed modules
    $files = $this->filterInstalledModuleFiles($files);

    foreach ($files as $file) {
        $this->loadHandlersFromJsonFile($file);
    }
}
```

## Lógica de Filtrado

### 1. Identificación de Archivos de Módulo

Se utiliza una expresión regular para identificar si un archivo pertenece a un módulo:

```php
private function isModuleFile(string $file): bool
{
    return (bool) preg_match('#/modules/([^/]+)/#', $file);
}
```

### 2. Extracción del Nombre del Módulo

```php
private function extractModuleName(string $file): ?string
{
    if (preg_match('#/modules/([^/]+)/#', $file, $matches)) {
        return $matches[1];
    }
    return null;
}
```

### 3. Lectura de Módulos Instalados

```php
private function getInstalledModules(): array
{
    // Lee composer.json
    // Busca paquetes que comienzan con 'cubadevops/flexi-module-'
    // Extrae el nombre del módulo del nombre del paquete
    // Retorna array asociativo [NombreModulo => nombre-paquete]
}
```

**Ejemplo**:
- Paquete: `cubadevops/flexi-module-auth`
- Módulo extraído: `Auth`

### 4. Aplicación del Filtro

```php
private function filterInstalledModuleFiles(array $files): array
{
    $installedModules = $this->getInstalledModules();

    return array_filter($files, function ($file) use ($installedModules) {
        // Si no es archivo de módulo, incluirlo
        if (!$this->isModuleFile($file)) {
            return true;
        }

        // Extraer nombre del módulo
        $moduleName = $this->extractModuleName($file);

        // Solo incluir si está instalado
        return $moduleName && isset($installedModules[$moduleName]);
    });
}
```

## Comportamiento

### Antes
```
modules/
  ├── Auth/Config/listeners.json          (procesado siempre)
  ├── ErrorHandling/Config/listeners.json (procesado siempre)
  └── Billing/Config/listeners.json       (procesado siempre, aunque no esté instalado)
```

### Después
```
composer.json:
{
  "require": {
    "cubadevops/flexi-module-auth": "^1.0",
    "cubadevops/flexi-module-errorhandling": "^1.0"
  }
}

modules/
  ├── Auth/Config/listeners.json          ✅ (instalado - procesado)
  ├── ErrorHandling/Config/listeners.json ✅ (instalado - procesado)
  └── Billing/Config/listeners.json       ❌ (NO instalado - ignorado)
```

## Beneficios

1. **Consistencia**: Todos los componentes del framework (Container, EventBus, CommandBus, QueryBus) ahora tienen el mismo comportamiento de filtrado

2. **Seguridad**: No se intentan cargar clases de módulos no instalados, evitando errores de clase no encontrada

3. **Performance**: Se evita el procesamiento innecesario de archivos de módulos no utilizados

4. **Mantenibilidad**: El código es más predecible y fácil de entender

5. **Modularidad**: Respeta el principio de que solo los módulos instalados deben ser cargados

## Archivos Modificados

1. ✅ `src/Infrastructure/Bus/EventBus.php`
2. ✅ `src/Infrastructure/Bus/CommandBus.php`
3. ✅ `src/Infrastructure/Bus/QueryBus.php`

## Testing Recomendado

1. **Test con módulos instalados**: Verificar que se cargan correctamente los listeners/handlers de módulos instalados

2. **Test con módulos no instalados**: Verificar que se ignoran archivos de módulos no instalados

3. **Test con archivos del core**: Verificar que archivos fuera de `/modules/` se procesan normalmente

4. **Test de composer.json faltante**: Verificar comportamiento cuando no existe composer.json

5. **Test de módulos sin prefijo**: Verificar que solo se consideran paquetes con prefijo `cubadevops/flexi-module-`

## Compatibilidad con Refactorización Anterior

Esta implementación es totalmente compatible con la refactorización anterior de eliminación de dependencias del core hacia módulos. Específicamente:

- El módulo `ErrorHandling` con su nuevo `RouteNotFoundListener` solo se cargará si está instalado
- Si no está instalado, el core usará su respuesta 404 por defecto
- El sistema es ahora completamente modular y desacoplado

## Conclusión

La implementación del filtrado de módulos instalados en los buses completa la arquitectura modular del framework, asegurando que solo se procesen configuraciones de módulos que estén efectivamente instalados, manteniendo consistencia con el comportamiento del contenedor de dependencias y mejorando la robustez del sistema.
