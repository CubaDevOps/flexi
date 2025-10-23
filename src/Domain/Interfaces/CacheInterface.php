<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;

interface CacheInterface extends PsrCacheInterface,DomainCacheInterface
{
}
