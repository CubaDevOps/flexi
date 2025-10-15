# Flexi Framework

Flexi is a modular PHP framework designed to facilitate the development of scalable and maintainable applications. It
leverages Dependency Injection (DI), a flexible routing system, CQRS and an event-driven architecture.

## Table of Contents

- [Features](#features)
- [Structure](#structure)
- [Installation](#installation)
- [Configuration](#configuration)
    - [Services](#services)
    - [Routes](#routes)
    - [Events and Listeners](#events-and-listeners)
    - [Queries](#queries)
    - [Commands](#commands)
- [Usage](#usage)
    - [Router](#router)
    - [Event System](#event-system)
    - [CQRS](#cqrs)
- [Contributing](#contributing)
- [License](#license)

## Features

- **Modularity & Extensibility**: Flexi offers a modular architecture that promotes code organization and reusability. Its extensibility allows developers to customize and extend functionality according to specific project requirements.
- **Dependency Injection**: Built-in support for Dependency Injection simplifies management and injection of dependencies, enhancing code maintainability and testability. DI in Flexi is implemented using a container that manages service with lazy loading instantiation.
- **Flexible Routing**: With a flexible routing system, developers can easily define and manage HTTP request handling, streamlining development of complex routing logic enriched with middleware support.
- **Event-Driven Architecture**: Flexi's event-driven architecture enables asynchronous handling of events by event listeners, fostering loose coupling and flexibility in application design.
- **CQRS & Scalability**: Support for Command Query Responsibility Segregation (CQRS) promotes scalability and performance optimization. Combined with features like asynchronous processing, Flexi facilitates the development of scalable applications capable of handling high traffic and data loads.
- **Configuration via JSON**: Configuration in Flexi is managed through JSON files, making it easy to define services, routes, events, queries, and commands. This approach simplifies configuration management and promotes consistency across projects. Json Schema will be implemented to validate the configuration files.

## Structure

- **Core**: the `src/` directory contains the core classes and interfaces that make up the framework.
- **Modules**: the `modules/` directory contains the all pieces of code that are not part of the core framework. Each module is a separate directory that contains its own controllers, services, and configuration files. There are plug and play plugins that can be added to the application to extend its functionality.
- **Config**: the `src/Config/` and `modules/*/Config` directory contains the configuration files for the framework (`routes`, `commands`, `queries`, `event listeners` and `services`).
- **Console**: the `bin/` directory contains the console application that can be run from the command line. `(coming soon)`

## Installation

You can install Flexi Framework using Composer:

```bash
composer create-project cubadevops/flexi my-app
```

This command creates a minimal ready boilerplate application in the `my-app` directory.

__Steps after install__

To get started with Flexi Framework, ensure you:

- Configure your web server to point to the `public` directory in the root of the project.
- Deny direct access to any directory that is not the `public` directory.
- Serve static files directly and route all other requests to the `index.php` file in the `public` directory.
- Set up the `.env` file with the necessary environment variables for your application.

After setup, browse to the URL of your application to see the welcome page. If you use the default configuration
with Docker, you can access the application at http://localhost:8080.

## Configuration

Configuration for Flexi is managed through JSON files. These files define services, routes, events, queries, and
commands used within your application.

### Services

Services are defined in the `services.json` file located in the `Config` directory. This file outlines how each service
should be instantiated, either directly or via factory methods.

#### Example

```json
{
  "services": [
    {
      "name": "CubaDevOps\\Flexi\\Infrastructure\\Classes\\Configuration",
      "factory": {
        "class": "CubaDevOps\\Flexi\\Infrastructure\\Factories\\ConfigurationFactory",
        "method": "getInstance",
        "arguments": []
      }
    },
    {
      "name": "session",
      "alias": "CubaDevOps\\Flexi\\Domain\\Classes\\NativeSessionStorage"
    },
    {
      "name": "logger",
      "class": {
        "name": "CubaDevOps\\Flexi\\Infrastructure\\Classes\\PsrLogger",
        "arguments": [
          "@CubaDevOps\\Flexi\\Domain\\Classes\\InFileLogRepository"
        ]
      }
    },
    {
      "glob": "/modules/*/Config/services.json"
    }
  ]
}
```

__Note:__

- The `alias` key is used to reference services by a different name.
- Arguments prefixed with `@` are references to other services (can be alias as well)
- Arguments prefixed with `ENV.` are references to environment variables.
- Quoted strings without `@` or `ENV.` prefixes are treated as string values.
- All other values are treated as the standard json_decode values.
- The `glob` key is used to include services from `modules`.

### Routes

Routes are defined in the `routes.json` file. Each route specifies the HTTP method, path, and the controller that should
handle the request.

#### Example

```json
{
  "routes": [
    {
      "name": "health",
      "path": "/health",
      "method": "GET",
      "controller": "CubaDevOps\\Flexi\\Infrastructure\\Controllers\\HealthController",
      "parameters": [],
      "middlewares": [
        "CubaDevOps\\Flexi\\Infrastructure\\Middlewares\\AuthCheckMiddleware"
      ]
    },
    {
      "name": "404",
      "path": "/not-found",
      "method": "GET",
      "controller": "CubaDevOps\\Flexi\\Infrastructure\\Controllers\\NotFoundController"
    },
    {
      "glob": "/modules/*/Config/routes.json"
    }
  ]
}
```

__Note:__

- Controllers should implement the `Psr\Http\Server\RequestHandlerInterface` interface if you want attach middlewares. However they must have a `handle` method that receives a `Psr\Http\Message\ServerRequestInterface` and returns a `Psr\Http\Message\ResponseInterface`.
- The optional `parameters` key is used to define how many params should be passed to the request and if they are required.
- The optional `middlewares` key is used to define the middlewares that should be executed before the controller. They are executed in the order they are defined and can stop the execution chain if they return a response directly or pass the request to the next middleware.
- The `glob` key is used to include additional route definitions from `modules`.

### Events and Listeners

Events and listeners are defined in the `listeners.json` file. This file maps events to their corresponding listeners.

#### Example

```json
[
  {
    "event": "*",
    "listeners": [
      "CubaDevOps\\Flexi\\Application\\EventListeners\\LoggerEventListener"
    ]
  },
  {
    "glob": "/modules/*/Config/listeners.json"
  }
]
```

__Note:__

- The `event` key can be a specific event name or a wildcard `*` to listen to all events.
- The listener class should implement the `CubaDevOps\Flexi\Domain\Interfaces\EventListenerInterface` interface.

### Queries

Queries are defined in the `queries.json` file. Each query handler is mapped to a specific DTO and an optional CLI
alias.

#### Example

```json
{
  "handlers": [
    {
      "id": "CubaDevOps\\Flexi\\Domain\\DTO\\EmptyVersionDTO",
      "cli_alias": "version",
      "handler": "CubaDevOps\\Flexi\\Application\\UseCase\\Health"
    },
    {
      "glob": "/modules/*/Config/queries.json"
    }
  ]
}
```

__Note:__

- Handlers should implement the `CubaDevOps\Flexi\Domain\Interfaces\HandlerInterface` interface.

### Commands

Commands are defined similarly to queries in the `commands.json` file.

#### Example

```json
{
  "handlers": [
    {
      "glob": "/modules/*/Config/commands.json"
    }
  ]
}
```

__Note:__

- Handlers should implement the `CubaDevOps\Flexi\Domain\Interfaces\HandlerInterface` interface.

## Usage

### Router

The `Router` class is responsible for managing routes and dispatching requests to the appropriate controllers.

#### Example Usage

```php
use CubaDevOps\Flexi\Domain\Classes\Router;
use Psr\Http\Message\ServerRequestInterface;
use CubaDevOps\Flexi\Infrastructure\Factories\ContainerFactory;

/** @var Router $router */
$router = ContainerFactory::getInstance()->get(Router::class); // or use router alias
$route = $router->getByName('home'); // Get a route by name
$route->getPath(); // Get the path of the route to pass to the template
```

### Event System

The event system in Flexi is based on the EventBus pattern. Events are dispatched to listeners which can handle them
accordingly.

#### Example

```php
use CubaDevOps\Flexi\Domain\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Domain\Classes\Event;
use CubaDevOps\Flexi\Application\UseCase\Health;
use CubaDevOps\Flexi\Infrastructure\Factories\ContainerFactory;
use CubaDevOps\Flexi\Domain\Classes\EventBus;

$eventBus = ContainerFactory::getInstance()->get(EventBus::class);
$event = new Event('health-check', Health::class, ['from' => $_SERVER['REMOTE_ADDR']);
$eventBus->notify($event);
```

### CQRS

Flexi implements the CQRS pattern with separate handling for commands and queries.

#### Command Example

```php
use CubaDevOps\Flexi\Domain\Classes\CommandBus;

// Assume $command is a class that implements the DTOInterface
$commandBus->execute($command);
```

#### Query Example

```php
use CubaDevOps\Flexi\Domain\Classes\QueryBus;

// Assume $query is a class that implements the DTOInterface
$result = $queryBus->execute($query);
```

### Controllers and Response

The response is a PSR-7 response object that can be returned from a controller or middleware. Controllers that extend the `CubaDevOps\Flexi\Infrastructure\Classes\HttpHandler` have an easy way to build responses using the `createResponse` method. If you you don't extend the `HttpHandler` you can use a factory that implements `Psr\Http\Message\ResponseFactoryInterface` interface to build the response. Flexi use the `GuzzleHttp\Psr7\HttpFactory` as default factory.

#### Example

```php
namespace CubaDevOps\Flexi\Modules\Home\Infrastructure\Controllers;

use CubaDevOps\Flexi\Infrastructure\Classes\HttpHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthenticatedController extends HttpHandler
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->queue->isEmpty()) { // This block allows to execute middlewares
            return $this->getNextMiddleware()->process($request, $this);
        }

        $response = $this->createResponse();
        $response->getBody()->write('Authorized');

        return $response;
    }
}
```

### Middlewares

Middlewares are classes that can be executed before the controller. They can modify the request, response or stop the execution chain. Middlewares should implement the `Psr\Http\Server\MiddlewareInterface` interface.

#### Example

```php
namespace CubaDevOps\Flexi\Infrastructure\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthCheckMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Perform authentication logic here and stop the execution chain if necessary
        // or pass the request
        return $handler->handle($request);
    }
}
```

## Testing

Flexi includes a comprehensive test suite using PHPUnit. Tests use a separate environment configuration to ensure isolation from development and production environments.

### Running Tests

```bash
# Run all tests
./vendor/bin/phpunit tests/

# Run specific test file
./vendor/bin/phpunit tests/Infrastructure/TestEnvironmentTest.php

# Run with coverage
./vendor/bin/phpunit tests/ --coverage-html coverage/
```

### Test Environment

Tests automatically use `.env.testing` configuration file with test-specific settings:

- **Synchronous event dispatch**: Events are dispatched synchronously for predictable testing
- **In-memory cache**: Faster tests without disk I/O
- **Separate logs**: Test logs go to `var/logs/test.log`
- **Isolated cache**: Test cache uses `var/cache/test/` directory

To customize test environment:

1. Copy `.env.testing.example` to `.env.testing`
2. Modify values as needed
3. Tests will automatically use these values

For more details, see [tests/README.md](tests/README.md).

### Writing Tests

```php
<?php

namespace CubaDevOps\Flexi\Test\YourNamespace;

use PHPUnit\Framework\TestCase;

class YourTest extends TestCase
{
    public function testSomething(): void
    {
        // Test environment variables from .env.testing are automatically loaded
        $this->assertTrue(true);
    }
}
```

## Documentation

The documentation is available online at [https://flexi.cubadevops.com](https://flexi.cubadevops.com) (Under construction and not yet available).

## Contributing

Contributions are welcome! Please submit a pull request or open an issue to discuss any changes you would like to make.

## License

**Flexi** is open-source software licensed under the [MIT license](LICENSE).