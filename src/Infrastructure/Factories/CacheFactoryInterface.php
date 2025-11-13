<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Factories;

use Flexi\Contracts\Interfaces\CacheInterface;

/**
 * Interface for cache factory creation
 */
interface CacheFactoryInterface
{
    public function createCache(): CacheInterface;
}