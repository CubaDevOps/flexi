<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Cache;

use CubaDevOps\Flexi\Contracts\CacheContract;
use CubaDevOps\Flexi\Domain\Exceptions\InvalidArgumentCacheException;

class InMemoryCache implements CacheContract
{
    private array $cache;

    public function __construct()
    {
        $this->cache = [];
    }

    public function clear(): bool
    {
        $this->cache = [];

        return true;
    }

    public function getMultiple($keys, $default = null): iterable
    {
        $this->assertIsIterable($keys);

        return array_map(function ($key) use ($default) {
            return $this->get($key, $default);
        }, (array) $keys);
    }

    /**
     * @throws InvalidArgumentCacheException
     */
    private function assertIsIterable($values): void
    {
        if (!is_iterable($values)) {
            throw new InvalidArgumentCacheException('Values must be an iterable');
        }
    }

    /**
     * @throws InvalidArgumentCacheException
     */
    public function get($key, $default = null)
    {
        $this->assertIsValidKey($key);

        return $this->cache[$key] ?? $default;
    }

    /**
     * @throws InvalidArgumentCacheException
     */
    private function assertIsValidKey($key): void
    {
        if (!is_string($key) || strlen($key) > 100) {
            throw new InvalidArgumentCacheException('Key must be a string with a maximum length of 100 characters');
        }
    }

    public function setMultiple($values, $ttl = null): bool
    {
        $this->assertIsIterable($values);

        $result = true;
        foreach ($values as $key => $value) {
            $result &= $this->set($key, $value, $ttl);
        }

        return (bool) $result;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $this->assertIsValidKey($key);
        $this->cache[$key] = $value;

        return true;
    }

    public function deleteMultiple($keys): bool
    {
        $this->assertIsIterable($keys);

        $result = true;
        foreach ((array) $keys as $key) {
            $result &= $this->delete($key);
        }

        return (bool) $result;
    }

    public function delete($key): bool
    {
        $this->assertIsValidKey($key);

        if (!$this->has($key)) {
            return false;
        }
        unset($this->cache[$key]);

        return true;
    }

    public function has($key): bool
    {
        $this->assertIsValidKey($key);

        return isset($this->cache[$key]);
    }
}
