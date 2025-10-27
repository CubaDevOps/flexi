# UI Module

El módulo UI encapsula toda la lógica de renderizado de templates y generación de contenido HTML para la aplicación.

## Componentes

### Template
Clase que gestiona la carga y validación de archivos de plantilla.

**Ubicación:** `Infrastructure/Ui/Template.php`

**Responsabilidades:**
- Validar que la plantilla existe en el filesystem
- Cargar contenido del archivo
- Proporcionar metadatos (nombre, extensión, ruta)

**Interfaz:** `CubaDevOps\Flexi\Contracts\Interfaces\TemplateInterface` (en Contracts)

### TemplateLocator
Factory que crea instancias de Template a partir de rutas.

**Ubicación:** `Infrastructure/Ui/TemplateLocator.php`

**Responsabilidades:**
- Localizar y crear instancias de Template

**Interfaz:** `CubaDevOps\Flexi\Contracts\Interfaces\TemplateLocatorInterface` (en Contracts)

### HtmlRender
Motor de renderizado que procesa templates y reemplaza placeholders.

**Ubicación:** `Infrastructure/Ui/HtmlRender.php`

**Responsabilidades:**
- Renderizar templates con variables
- Aceptar rutas (strings) o objetos TemplateInterface
- Reemplazar placeholders en formato `{{key}}`

**Interfaz:** `CubaDevOps\Flexi\Contracts\Interfaces\TemplateEngineInterface` (en Contracts)

## Configuración

Las definiciones de servicios están en `Config/services.json`:
- `html_render` - Servicio de renderizado
- `CubaDevOps\Flexi\Contracts\Interfaces\TemplateLocatorInterface` - Factory de templates

## Uso

En el DI Container:

```php
$htmlRender = $container->get('html_render');
$content = $htmlRender->render($templatePath, ['variable' => 'value']);
```

## Tests

Los tests del módulo están en `tests/Infrastructure/Ui/`:
- `TemplateTest.php` - Validación de carga de templates
- `HtmlRenderTest.php` - Validación de renderizado

Para ejecutar tests del módulo:
```bash
phpunit modules/Ui/tests/
```

## Notas de Arquitectura

- **Separación de Concerns:** El módulo UI maneja SOLO renderizado, no lógica de negocio
- **Inyección de Dependencias:** Todas las dependencias se inyectan vía constructor
- **Contratos en Packages:** Las interfaces están en `Contracts` para permitir que otros módulos usen las abstracciones sin conocer la implementación
- **Core Clean:** El core de Flexi no contiene referencias a este módulo
