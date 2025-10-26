<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts;

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;

/**
 * Cache Contract - Pure PSR-16 implementation
 * No need for additional methods, PSR-16 covers all cache needs.
 */
interface CacheContract extends PsrCacheInterface
{
}
