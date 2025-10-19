<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use CubaDevOps\Flexi\Infrastructure\Classes\ObjectBuilder;
use CubaDevOps\Flexi\Infrastructure\Factories\CacheFactory;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\Container;
use CubaDevOps\Flexi\Domain\Utils\ServicesDefinitionParser;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ContainerFactory
{
    private static ?Container $instance = null;

    /**
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public static function getInstance(string $file = ''): Container
    {
        if (!self::$instance) {
            $cache = CacheFactory::getInstance();
            $object_builder = new ObjectBuilder();
            $container = new Container($cache, $object_builder);
            if ($file) {
                $services_parser = new ServicesDefinitionParser($cache);
                $services = $services_parser->parse($file);
                foreach ($services as $name => $service) {
                    $container->set($name, $service);
                }
            }
            self::$instance = $container;
        }

        return self::$instance;
    }

    /**
     * Reset the singleton instance.
     * This method is intended for testing purposes only.
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
