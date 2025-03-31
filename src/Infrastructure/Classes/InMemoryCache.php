<?php

namespace CubaDevOps\Flexi\Infrastructure\Classes;

use CubaDevOps\Flexi\Domain\Exceptions\InvalidArgumentCacheException;
use CubaDevOps\Flexi\Domain\Interfaces\CacheInterface;

class InMemoryCache implements CacheInterface
{

    private array $cache;

    public function __construct()
    {
        $this->cache = [];
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        $this->cache = [];
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null): iterable
    {
        $this->assertIsIterable($keys);
        return array_map(function ($key) use ($default) {
            return $this->get($key, $default);
        }, (array)$keys);
    }

    /**
     * @param mixed $values
     * @return void
     * @throws InvalidArgumentCacheException
     */
    private function assertIsIterable($values): void
    {
        if (!is_iterable($values)) {
            throw new InvalidArgumentCacheException("Values must be an iterable");
        }
    }

    /**
     * @return mixed
     * @throws InvalidArgumentCacheException
     */
    public function get($key, $default = null)
    {
        $this->assertIsValidKey($key);
        return $this->cache[$key] ?? $default;
    }

    /**
     * @param mixed $key
     * @return void
     * @throws InvalidArgumentCacheException
     */
    private function assertIsValidKey($key): void
    {
        if (!is_string($key) || strlen($key) > 100) {
            throw new InvalidArgumentCacheException("Key must be a string with a maximum length of 100 characters");
        }
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null): bool
    {
        $this->assertIsIterable($values);

        $result = true;
        foreach ($values as $key => $value) {
            $result &= $this->set($key, $value, $ttl);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null): bool
    {
        $this->assertIsValidKey($key);
        $this->cache[$key] = $value;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys): bool
    {
        $this->assertIsIterable($keys);

        $result = true;
        foreach ((array)$keys as $key) {
            $result &= $this->delete($key);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function delete($key): bool
    {
        $this->assertIsValidKey($key);

        if (!$this->has($key)) {
            return false;
        }
        unset($this->cache[$key]);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function has($key): bool
    {
        $this->assertIsValidKey($key);
        return isset($this->cache[$key]);
    }
}