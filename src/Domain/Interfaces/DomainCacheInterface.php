<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

interface DomainCacheInterface
{
    public function get(string $key, $default = null);

    public function set(string $key, $value, $ttl = null);

    public function has($key);

    public function delete(string $key);

    public function clear();
}
