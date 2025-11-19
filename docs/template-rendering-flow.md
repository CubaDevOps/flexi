# Template Rendering Flow

## Summary

This document describes the optimized template rendering flow in Flexi, designed to improve the developer experience while maintaining hexagonal architecture principles.

## Architecture

### Actors and Responsibilities

#### 1. **TemplateInterface** (Domain - Port)

- **Location**: `src/Domain/Interfaces/TemplateInterface.php`
- **Responsibility**: Defines the contract for template objects
- **Methods**:
  - `getContent(): string` - Gets the template content
  - `getTemplatePath(): string` - Gets the absolute path to the template
  - `getTemplateName(): string` - Gets the filename
  - `getTemplateExtension(): string` - Gets the file extension

#### 2. **TemplateLocatorInterface** (Domain - Port)

- **Location**: `src/Domain/Interfaces/TemplateLocatorInterface.php`
- **Responsibility**: Defines the contract for locating and preparing templates
- **Methods**:
  - `locate(string $templatePath): TemplateInterface` - Locates a template by its path

#### 3. **TemplateEngineInterface** (Domain - Port)

- **Location**: `src/Domain/Interfaces/TemplateEngineInterface.php`
- **Responsibility**: Defines the contract for rendering engines
- **Methods**:
  - `render(TemplateInterface|string $template, array $vars = []): string` - Renders a template
  - **Important note**: Accepts both a `TemplateInterface` object and a `string` (path)

#### 4. **Template** (Infrastructure - Adapter)

- **Location**: `src/Infrastructure/Ui/Template.php`
- **Responsibility**: Concrete implementation of `TemplateInterface`
- **Functionality**:
  - Validates that the template file exists
  - Reads file content
  - Normalizes paths

#### 5. **TemplateLocator** (Infrastructure - Adapter)

- **Location**: `src/Infrastructure/Ui/TemplateLocator.php`
- **Responsibility**: Concrete implementation of `TemplateLocatorInterface`
- **Functionality**:
  - Creates `Template` instances from paths
  - Encapsulates location logic

#### 6. **HtmlRender** (Infrastructure - Adapter)

- **Location**: `src/Infrastructure/Ui/HtmlRender.php`
- **Responsibility**: HTML rendering engine implementation
- **Dependencies**:
  - `TemplateLocatorInterface` (injected in constructor)
- **Functionality**:
  - **Smart**: Detects if it receives a `string` or a `TemplateInterface`
  - If it receives a `string`, uses the locator internally to get the template
  - If it receives a `TemplateInterface`, uses it directly
  - Replaces `{{variable}}` placeholders with values from the `$vars` array

## Developer Usage Flow

### Typical Scenario (Application Layer)

```php
class RenderHome implements HandlerInterface
{
    private TemplateEngineInterface $html_render;

    public function __construct(TemplateEngineInterface $html_render)
    {
        $this->html_render = $html_render;
    }

    public function handle(DTOInterface $dto): MessageInterface
    {
        // ✅ Simple and straightforward: just pass the path
        return new PlainTextMessage(
            $this->html_render->render(
                $dto->get('template'),
                ['doc_url' => 'https://flexi.cubadevops.com']
            )
        );
    }
}
```

### What the Developer Needs to Know

**BEFORE (Complex):**

```php
// ❌ Had to inject two dependencies
public function __construct(
    TemplateEngineInterface $html_render,
    TemplateLocatorInterface $template_locator
) {
    // ...
}

// ❌ Had to manually locate the template
$template = $this->template_locator->locate($dto->get('template'));
$html = $this->html_render->render($template, $vars);
```

**NOW (Simple):**

```php
// ✅ Only injects one dependency
public function __construct(TemplateEngineInterface $html_render) {
    // ...
}

// ✅ Directly passes the path
$html = $this->html_render->render($dto->get('template'), $vars);
```

## Flow Diagram

```text
┌─────────────────────────────────────────────────────────────┐
│                    Application Layer                        │
│                                                             │
│  RenderHome                                                 │
│    │                                                        │
│    └─> html_render->render("/path/to/template.html", [...])│
│                          │                                  │
└──────────────────────────┼──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                  Infrastructure Layer                       │
│                                                             │
│  HtmlRender                                                 │
│    │                                                        │
│    ├─> Is it a string?                                     │
│    │     │                                                  │
│    │     └─> YES ──> template_locator->locate(path)        │
│    │                      │                                 │
│    │                      └─> Template (object)             │
│    │                                                        │
│    └─> render(TemplateInterface)                           │
│          │                                                  │
│          └─> Replace placeholders                          │
│                │                                            │
│                └─> Rendered HTML                           │
└─────────────────────────────────────────────────────────────┘
```

## Advantages of this Architecture

### 1. **Improved Developer Experience**

- Only needs to know about `TemplateEngineInterface`
- Single dependency in the constructor
- Single method call

### 2. **Complexity Encapsulation**

- Location logic is hidden inside `HtmlRender`
- Developer doesn't need to know how templates are located

### 3. **Flexibility**

- Accepts both strings and `TemplateInterface` objects
- Allows advanced use cases when needed

### 4. **Respects Hexagonal Architecture**

- Application layer only depends on domain interfaces
- Correct dependency direction: Application → Domain ← Infrastructure
- Dependency inversion principle respected

### 5. **Testable**

- Easy to create `TemplateEngineInterface` mocks
- No need to mock `TemplateLocatorInterface` in application tests

## Service Configuration

In `src/Config/services.json`:

```json
{
  "name": "html_render",
  "class": {
    "name": "Flexi\\Infrastructure\\Ui\\HtmlRender",
    "arguments": [
      "@Flexi\\Domain\\Interfaces\\TemplateLocatorInterface"
    ]
  }
},
{
  "name": "Flexi\\Domain\\Interfaces\\TemplateLocatorInterface",
  "class": {
    "name": "Flexi\\Infrastructure\\Ui\\TemplateLocator",
    "arguments": []
  }
}
```

## Advanced Use Cases

### If you need direct access to the Template object

```php
// You can still create a Template directly in Infrastructure
$template = new Template('/path/to/template.html');
$html = $this->html_render->render($template, $vars);
```

### If you need a custom locator

```php
class CustomTemplateLocator implements TemplateLocatorInterface
{
    public function locate(string $templatePath): TemplateInterface
    {
        // Custom logic (e.g., search in multiple directories)
        return new Template($this->findTemplate($templatePath));
    }
}
```

Then register in `services.json`:

```json
{
  "name": "Flexi\\Domain\\Interfaces\\TemplateLocatorInterface",
  "class": {
    "name": "YourNamespace\\CustomTemplateLocator",
    "arguments": []
  }
}
```

## Tests

### HtmlRender test with string

```php
public function testRenderWithStringPath(): void
{
    $templatePath = '/path/to/template.html';

    $rendered = $this->htmlRender->render($templatePath, ['var' => 'value']);

    $this->assertNotEmpty($rendered);
}
```

### HtmlRender test with object

```php
public function testRenderWithTemplateObject(): void
{
    $template = $this->createMock(TemplateInterface::class);

    $rendered = $this->htmlRender->render($template, ['var' => 'value']);

    $this->assertNotEmpty($rendered);
}
```

## Conclusion

This optimized architecture provides:

- **Simplicity** for 99% of use cases
- **Flexibility** for advanced cases
- **Clear separation** of responsibilities
- **Improved** development experience
- **Compliance** with SOLID principles and hexagonal architecture
