<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\TestData\TestDoubles;

use CubaDevOps\Flexi\Contracts\CacheContract;

/**
 * Dummy cache that doesn't cache anything.
 * Used for testing to avoid cache interference between tests.
 */
class DummyCache implements CacheContract
{
    public function get($key, $default = null)
    {
        return $default;
    }

    public function set($key, $value, $ttl = null)
    {
        return true;
    }

    public function delete($key)
    {
        return true;
    }

    public function clear()
    {
        return true;
    }

    public function has($key)
    {
        return false;
    }

    public function getMultiple($keys, $default = null)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $default;
        }
        return $result;
    }

    public function setMultiple($values, $ttl = null)
    {
        return true;
    }

    public function deleteMultiple($keys)
    {
        return true;
    }
}
