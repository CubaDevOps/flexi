<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Classes;

use CubaDevOps\Flexi\Contracts\ConfigurationRepositoryContract;
use Psr\Container\ContainerInterface;

class Configuration implements ContainerInterface
{
    private ConfigurationRepositoryContract $configurationRepository;

    public function __construct(ConfigurationRepositoryContract $configurationRepository)
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
