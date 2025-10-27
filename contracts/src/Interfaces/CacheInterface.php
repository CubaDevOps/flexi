<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Interfaces;

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;

/**
 * Cache Interface - Pure PSR-16 implementation
 * No need for additional methods, PSR-16 covers all cache needs.
 */
interface CacheInterface extends PsrCacheInterface
{
}
