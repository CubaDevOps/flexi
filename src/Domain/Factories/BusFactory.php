<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Factories;

use CubaDevOps\Flexi\Domain\Classes\CommandBus;
use CubaDevOps\Flexi\Domain\Classes\EventBus;
use CubaDevOps\Flexi\Domain\Classes\QueryBus;
use CubaDevOps\Flexi\Domain\Interfaces\BusInterface;
use CubaDevOps\Flexi\Domain\Utils\ClassFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class BusFactory
{
    private static array $instance = []; // Todo replace with an independent cache system

    /**
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public static function getInstance(
        ContainerInterface $container,
        string $type,
        string $file = ''
    ): BusInterface {
        if (!isset(self::$instance[$type])) {
            /** @var ClassFactory $class_factory */
            $class_factory = $container->get(ClassFactory::class);
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
