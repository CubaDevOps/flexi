<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Classes;

use CubaDevOps\Flexi\Contracts\Interfaces\ConfigurationRepositoryInterface;
use Psr\Container\ContainerInterface;

class Configuration implements ContainerInterface
{
    private ConfigurationRepositoryInterface $configurationRepository;

    public function __construct(ConfigurationRepositoryInterface $configurationRepository)
    {
        $this->configurationRepository = $configurationRepository;
    }

    public function get(string $id)
    {
        return $this->configurationRepository->get($id);
    }

    public function has(string $id): bool
    {
        return $this->configurationRepository->has($id);
    }
}
