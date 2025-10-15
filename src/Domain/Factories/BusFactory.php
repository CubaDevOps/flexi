<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Factories;

use CubaDevOps\Flexi\Infrastructure\Bus\CommandBus;
use CubaDevOps\Flexi\Infrastructure\Bus\EventBus;
use CubaDevOps\Flexi\Infrastructure\Bus\QueryBus;
use CubaDevOps\Flexi\Domain\Interfaces\BusInterface;
use CubaDevOps\Flexi\Domain\Interfaces\ObjectBuilderInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class BusFactory
{
    private static array $instance = []; // Todo replace with an independent cache system

    /**
     * @param ContainerInterface $container
     * @param string $type
     * @param string $file
     * @return BusInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \JsonException
     * @throws \ReflectionException
     */
    public static function getInstance(
        ContainerInterface $container,
        string $type,
        string $file = ''
    ): BusInterface {
        if (!isset(self::$instance[$type])) {
            /** @var ObjectBuilderInterface $class_factory */
            $class_factory = $container->get(ObjectBuilderInterface::class);
            switch ($type) {
                case CommandBus::class:
                    self::$instance[$type] = new CommandBus($container, new EventBus($container, $class_factory), $class_factory);
                    break;
                case QueryBus::class:
                    self::$instance[$type] = new QueryBus($container, new EventBus($container, $class_factory), $class_factory);
                    break;
                case EventBus::class:
                    self::$instance[$type] = new EventBus($container, $class_factory);
                    break;
                default:
                    throw new \InvalidArgumentException('Invalid bus type');
            }
            self::$instance[$type]->loadHandlersFromJsonFile($file);
        }

        return self::$instance[$type];
    }
}
