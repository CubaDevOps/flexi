<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Classes\Traits;

trait CacheKeyGeneratorTrait
{
    public function getCacheKey(string $class, string $method, array $arguments): string
    {
        return md5($class.$method.serialize($arguments));
    }
}
