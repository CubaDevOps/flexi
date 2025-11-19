# Console Error Formatting - Mejora de Experiencia de Usuario

## Descripción

Se ha implementado un nuevo sistema de formateo de errores para la interfaz de consola que hace que los errores sean mucho más fáciles de leer y comprender, tanto en modo DEBUG como en modo normal.

## Características

### 1. Nuevo Formateador de Excepciones (`ConsoleExceptionFormatter`)

Se ha creado una nueva clase que formatea las excepciones de manera amigable para la terminal:

- **Cabecera visual**: Líneas de separación y nombre del tipo de error en rojo destacado
- **Mensaje claro**: El mensaje de error se muestra con formato legible y wrap automático
- **Información de ubicación**: En modo DEBUG, muestra el archivo y línea donde ocurrió el error
- **Stack trace formateado**: Muestra el stack trace de forma estructurada y fácil de seguir (limitado a las primeras 10 entradas para evitar saturación)
- **Excepciones previas**: Muestra la cadena de excepciones si existen
- **Rutas acortadas**: Las rutas de archivos se acortan para mejorar la legibilidad

### 2. Modos de Visualización

#### Modo Normal (DEBUG_MODE = false)
```
================================================================================
  ERROR: RuntimeException
================================================================================

Message:
  El comando 'invalid:command' no fue encontrado

Tip: Enable DEBUG_MODE to see detailed error information

================================================================================
```

#### Modo DEBUG (DEBUG_MODE = true)
```
================================================================================
  ERROR: RuntimeException
================================================================================

Message:
  El comando 'invalid:command' no fue encontrado

Location:
  File: ./src/Infrastructure/Bus/CommandBus.php
  Line: 42

Stack Trace:
  #0 Flexi\Infrastructure\Ui\Cli\CommandHandler->handle
      ./src/Infrastructure/Ui/Cli/CommandHandler.php:25
  #1 Flexi\Infrastructure\Ui\Cli\ConsoleApplication::handle
      ./src/Infrastructure/Ui/Cli/ConsoleApplication.php:68
  #2 Flexi\Infrastructure\Ui\Cli\ConsoleApplication::run
      ./src/Infrastructure/Ui/Cli/ConsoleApplication.php:41
  ... más frames

Previous Exception:
  InvalidArgumentException
  Message: Command handler not registered for 'invalid:command'
  at ./src/Infrastructure/Bus/CommandBus.php:35

================================================================================
```

### 3. Funciones Auxiliares

El formateador también incluye métodos para mensajes de:
- **Errores simples**: `formatSimpleError()`
- **Éxito**: `formatSuccess()`
- **Advertencias**: `formatWarning()`

## Ejemplo de Uso

```php
// En modo normal
try {
    // código que puede fallar
} catch (\Exception $e) {
    echo ConsoleExceptionFormatter::format($e, false);
}

// En modo debug
try {
    // código que puede fallar
} catch (\Exception $e) {
    echo ConsoleExceptionFormatter::format($e, true);
}

// Mensajes simples
echo ConsoleExceptionFormatter::formatSuccess('Operación completada exitosamente');
echo ConsoleExceptionFormatter::formatWarning('Esta acción puede tardar algunos minutos');
echo ConsoleExceptionFormatter::formatSimpleError('No se pudo conectar a la base de datos');
```

## Beneficios

1. **Mejor legibilidad**: Los errores son fáciles de leer y entender
2. **Información contextual**: En modo DEBUG, obtienes toda la información necesaria para depurar
3. **No más JSON ilegible**: Los errores se muestran en formato texto con colores
4. **Consistencia**: Todos los errores se muestran con el mismo formato
5. **Stack trace limitado**: Solo muestra las entradas más relevantes para evitar saturación
6. **Rutas optimizadas**: Las rutas largas se acortan para mejorar la legibilidad

## Cambios en ConsoleApplication

- Se eliminó el lanzamiento de excepciones en formato JSON
- Todos los errores ahora se capturan y formatean con `ConsoleExceptionFormatter`
- El modo DEBUG se pasa como parámetro a través de toda la aplicación
- Los errores se manejan de manera consistente en todos los puntos de captura

## Testing

Puedes probar el nuevo formato ejecutando comandos inválidos en la consola:

```bash
# Sin modo DEBUG
bin/console --command invalid:command

# Con modo DEBUG
# (Edita tu archivo .env para establecer DEBUG_MODE=true)
bin/console --command invalid:command
```

## Compatibilidad

- ✅ Compatible con todas las versiones existentes de comandos y queries
- ✅ No requiere cambios en los handlers existentes
- ✅ Mantiene la funcionalidad de `ConsoleOutputFormatter` existente
- ✅ Todos los tests existentes pasan sin cambios

