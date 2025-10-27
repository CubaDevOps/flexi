<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Auth\Infrastructure\Adapters;

use CubaDevOps\Flexi\Contracts\Interfaces\SecretProviderInterface;
use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;

class ConfigurationSecretProvider implements SecretProviderInterface
{
    private Configuration $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getSecret(): string
    {
        return $this->configuration->get('webhook_secret');
    }
}
