<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\TestData\TestDoubles;

use Flexi\Contracts\Interfaces\CacheInterface;

final class ServicesArrayCache implements CacheInterface
{
    /** @var array<string, mixed> */
    public array $store = [];

    /** @var array<string, int> */
    public array $setCalls = [];

    public function __construct(array $initial = [])
    {
        $this->store = $initial;
    }

    public function get($key, $default = null)
    {
        return $this->store[$key] ?? $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $this->store[$key] = $value;
        $this->setCalls[$key] = ($this->setCalls[$key] ?? 0) + 1;
        return true;
    }

    public function delete($key): bool
    {
        unset($this->store[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->store = [];
        return true;
    }

    public function getMultiple($keys, $default = null)
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->get($key, $default);
        }
        return $results;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function has($key): bool
    {
        return array_key_exists($key, $this->store);
    }
}
