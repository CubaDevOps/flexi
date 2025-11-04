<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Interfaces;

interface SecretProviderInterface
{
    /**
     * Get the secret key for JWT or other cryptographic operations.
     *
     * @return string The secret key
     */
    public function getSecret(): string;
}
