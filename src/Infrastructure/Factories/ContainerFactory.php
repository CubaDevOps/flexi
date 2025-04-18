<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use CubaDevOps\Flexi\Infrastructure\Factories\CacheFactory;
use CubaDevOps\Flexi\Domain\Classes\Container;
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
            $container = new Container($cache);
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
}
