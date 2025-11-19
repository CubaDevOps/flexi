<?php

declare(strict_types=1);

namespace Flexi\Infrastructure\Factories;

use Flexi\Domain\Interfaces\ConfigurationFilesProviderInterface;
use Flexi\Infrastructure\DependencyInjection\RoutesDefinitionParser;
use Flexi\Domain\ValueObjects\ConfigurationType;
use Flexi\Contracts\Interfaces\EventBusInterface;
use Flexi\Contracts\Interfaces\ObjectBuilderInterface;
use Flexi\Infrastructure\Http\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class RouterFactory
{
    private EventBusInterface $event_bus;
    private ObjectBuilderInterface $class_factory;
    private ResponseFactoryInterface $response_factory;
    private ContainerInterface $container;
    private ConfigurationFilesProviderInterface $config_files_provider;
    private RoutesDefinitionParser $routes_parser;

    public function __construct(
        EventBusInterface $event_bus,
        ObjectBuilderInterface $class_factory,
        ResponseFactoryInterface $response_factory,
        ContainerInterface $container,
        RoutesDefinitionParser $routes_parser,
        ConfigurationFilesProviderInterface $config_files_provider
    ) {
        $this->event_bus = $event_bus;
        $this->class_factory = $class_factory;
        $this->response_factory = $response_factory;
        $this->container = $container;
        $this->routes_parser = $routes_parser;
        $this->config_files_provider = $config_files_provider;
    }

    /**
     * Create router instance with automatic routes discovery
     *
     * @param bool $useConfigProvider Whether to use config provider for automatic discovery
     * @param string $fallbackFile Fallback route file if config provider not available
     * @throws \JsonException
     */
    public function getInstance(): Router {
        $router = new Router(
            $this->event_bus,
            $this->class_factory,
            $this->response_factory,
            $this->container
        );

        // Use configuration files provider to discover all route files
        $routeFiles = $this->config_files_provider->getConfigurationFiles(ConfigurationType::routes());

        foreach ($routeFiles as $file) {
            $routes = $this->routes_parser->parse($file);
            foreach ($routes as $route) {
                $router->addRoute($route);
            }
        }
        return $router;
    }

    /**
     * Create router with automatic route file discovery using ConfigurationFilesProvider
     *
     * @param EventBusInterface $eventBus
     * @param ObjectBuilderInterface $classFactory
     * @param ResponseFactoryInterface $responseFactory
     * @param ContainerInterface $container
     * @param ConfigurationFilesProviderInterface $configFilesProvider
     * @param RoutesDefinitionParser $routesParser
     * @return Router
     * @throws \JsonException
     */
    public static function create(
        ContainerInterface $container
    ): Router {
        $eventBus = $container->get(EventBusInterface::class);
        $classFactory = $container->get(ObjectBuilderInterface::class);
        $responseFactory = $container->get(ResponseFactoryInterface::class);
        $configFilesProvider = $container->get(ConfigurationFilesProviderInterface::class);
        $routesParser = $container->get(RoutesDefinitionParser::class);

        return (new self(
            $eventBus,
            $classFactory,
            $responseFactory,
            $container,
            $routesParser,
            $configFilesProvider
        ))->getInstance();
    }
}
