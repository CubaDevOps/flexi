<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Classes;

use CubaDevOps\Flexi\Domain\Interfaces\CacheInterface;
use CubaDevOps\Flexi\Domain\Utils\CacheKeyGeneratorTrait;
use CubaDevOps\Flexi\Domain\Utils\ClassFactory;
use CubaDevOps\Flexi\Domain\Utils\GlobFileReader;
use CubaDevOps\Flexi\Domain\Utils\JsonFileReader;
use CubaDevOps\Flexi\Domain\ValueObjects\ServiceType;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;

class Container implements ContainerInterface
{
    use JsonFileReader;
    use GlobFileReader;
    use CacheKeyGeneratorTrait;

    /**
     * @var Service[]
     */
    private array $services = [];
    private array $aliases = [];
    private ClassFactory $factory;
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
        $this->factory = new ClassFactory($cache);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface|\JsonException|\ReflectionException
     */
    public function loadServices(string $filename): void
    {
        $services = $this->readJsonFile($filename);
        foreach ($services['services'] as $definition) {
            if ($this->isGlob($definition)) {
                $this->loadGlobServices($definition['glob']);
                continue;
            }
            $name = $definition['name'];
            $this->compileServiceDefinition($name, $definition);
        }
    }

    protected function isGlob($definition): bool
    {
        return isset($definition['glob']);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     */
    public function loadGlobServices($glob): void
    {
        $files = $this->readGlob($glob);
        foreach ($files as $file) {
            $this->loadServices($file);
        }
    }

    protected function compileServiceDefinition($name, $definition): void
    {
        if ($this->has($name)) {
            throw new \RuntimeException('Service already exists: '.$name.', you should decorate it.');
        }
        $service = null;
        if (isset($definition['class'])) {
            $service = $this->getServiceClassFromArray($name, $definition);
        } elseif (isset($definition['factory'])) {
            $service = $this->getServiceFactoryFromArray($name, $definition);
        } elseif (isset($definition['alias'])) {
            $this->assertThatServiceExist($definition['alias']);
            $this->aliases[$name] = $definition['alias'];
        }

        if (null !== $service) {
            $this->addService($name, $service);
        }
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id])
            || $this->isAlias($id)
            || 'container' === $id;
    }

    public function isAlias(string $id): bool
    {
        return isset($this->aliases[$id]);
    }

    private function getServiceClassFromArray(
        string $name,
        array $definition
    ): Service {
        $class = $definition['class'];

        return new Service(
            $name,
            new ServiceType('class'),
            new ServiceClassDefinition($class['name'], $class['arguments'])
        );
    }

    private function getServiceFactoryFromArray(
        string $name,
        array $definition
    ): Service {
        $factory = $definition['factory'];

        return new Service(
            $name,
            new ServiceType('factory'),
            new ServiceFactoryDefinition(
                $factory['class'],
                $factory['method'],
                $factory['arguments']
            )
        );
    }

    private function assertThatServiceExist(string $id): void
    {
        if (!$this->has($id)) {
            throw new \RuntimeException('Service not found: '.$id);
        }
    }

    public function addService(string $id, Service $service): void
    {
        $this->services[$id] = $service;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    public function get(string $id): object
    {
        if ('container' === $id) {
            return $this;
        }
        if ('cache' === $id || CacheInterface::class === $id) {
            return $this->cache;
        }
        if (ClassFactory::class === $id) {
            return $this->factory;
        }

        $this->assertThatServiceExist($id);

        $service_id = $this->isAlias($id) ? $this->aliases[$id] : $id;

        $service = $this->services[$service_id];

        if ($this->cache->has($service_id)) {
            return $this->cache->get($service_id);
        }
        $service_instance = $this->buildFromService($service);
        $this->cache->set($service_id, $service_instance);

        return $service_instance;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     */
    private function buildFromService(Service $service)
    {
        return ServiceType::TYPE_CLASS === $service->getType()->getValue() ? $this->factory->build(
            $this,
            $service->getDefinition()->getClass(),
            $service->getDefinition()->getArguments()
        ) : $this->factory->buildFromFactory(
            $this,
            $service->getDefinition()->getClass(),
            $service->getDefinition()->getMethod(),
            $service->getDefinition()->getArguments()
        );
    }
}
